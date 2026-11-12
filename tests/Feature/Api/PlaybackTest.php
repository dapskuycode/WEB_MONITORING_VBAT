<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialView;
use App\Models\Lesson;
use App\Models\PlaybackProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaybackTest extends TestCase
{
    use RefreshDatabase;

    private string $providerDuration = 'PT1M40S';

    private User $student;

    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        config(['services.youtube.api_key' => 'fixture']);
        Http::preventStrayRequests();
        $this->provider();
        $this->student = User::factory()->create();
        $course = Course::create(['title' => 'Fixture', 'slug' => 'fixture', 'type' => 'free_class', 'status' => 'published']);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Module', 'status' => 'published']);
        $this->material = LearningMaterial::factory()->youtube()->create(['lesson_id' => $lesson->id, 'sort_order' => 1, 'is_required' => true]);
        $this->actingAs($this->student);
    }

    private function provider(string $duration = 'PT1M40S'): void
    {
        Http::fake(['www.googleapis.com/*' => fn () => Http::response(['items' => [['id' => 'dQw4w9WgXcQ',
            'status' => ['embeddable' => true, 'privacyStatus' => 'unlisted', 'uploadStatus' => 'processed'], 'contentDetails' => ['duration' => $this->providerDuration]]]])]);
    }

    private function start(?LearningMaterial $material = null, ?string $key = null): array
    {
        return $this->postJson('/api/v1/lms/materials/'.($material ?? $this->material)->id.'/playback', ['request_id' => $key ?? (string) Str::uuid()])->assertOk()->json('data');
    }

    private function send(array $session, int $sequence, float $position, string $state = 'playing', ?string $eventId = null)
    {
        return $this->postJson('/api/v1/lms/playback/'.$session['session_id'].'/events', ['event_id' => $eventId ?? (string) Str::uuid(), 'sequence' => $sequence, 'position_seconds' => $position, 'state' => $state]);
    }

    private function watch(array $session, int $seconds): void
    {
        $this->send($session, 1, 0)->assertOk();
        for ($position = 5; $position <= $seconds; $position += 5) {
            $this->travel(5)->seconds();
            $this->send($session, 1 + intdiv($position, 5), $position)->assertOk()->assertJsonPath('data.needs_resync', false);
        }
    }

    public function test_95_percent_unique_coverage_unlocks_next_and_resumes_across_sessions(): void
    {
        $next = LearningMaterial::factory()->youtube()->create(['lesson_id' => $this->material->lesson_id, 'sort_order' => 2, 'is_required' => true]);
        $this->postJson('/api/v1/lms/materials/'.$next->id.'/playback', ['request_id' => (string) Str::uuid()])->assertForbidden();
        $session = $this->start();
        $this->assertEquals(100, $session['duration_seconds']);
        $this->watch($session, 90);
        $this->assertNull(PlaybackProgress::first()->completed_at);
        $this->travel(5)->seconds();
        $this->send($session, 20, 95, 'paused')->assertOk()->assertJsonPath('data.progress.completed', true)->assertJsonPath('data.progress.percent', 95);
        $this->getJson('/api/v1/lms/courses/'.$this->material->lesson->course_id)->assertOk()
            ->assertJsonPath('data.course.progress.percent', 50)->assertJsonPath('data.next_unit.id', $next->id)->assertJsonPath('data.modules.0.activities.1.can_open', true);
        $this->start($next);
        $this->send($session, 21, 96)->assertConflict();
        $this->assertEquals(95, PlaybackProgress::where('material_id', $this->material->id)->first()->covered_ms / 1000);
    }

    public function test_retries_are_idempotent_conflicting_replays_and_out_of_order_events_rejected(): void
    {
        $key = (string) Str::uuid();
        $session = $this->start(key: $key);
        $this->assertSame($session['session_id'], $this->start(key: $key)['session_id']);
        $event = (string) Str::uuid();
        $first = $this->send($session, 1, 0, eventId: $event)->assertOk()->json();
        $this->assertSame($first, $this->send($session, 1, 0, eventId: $event)->assertOk()->json());
        $this->send($session, 1, 1, eventId: $event)->assertConflict();
        $this->send($session, 3, 5)->assertConflict();
        $this->assertDatabaseCount('lms_playback_events', 1);
    }

    public function test_seek_end_spoof_and_request_flood_do_not_create_watch_time(): void
    {
        $session = $this->start();
        $this->send($session, 1, 100, 'ended')->assertOk()->assertJsonPath('data.needs_resync', true)->assertJsonPath('data.resume_position', 0);
        $this->send($session, 2, 0)->assertOk();
        for ($i = 3; $i <= 15; $i++) {
            $this->send($session, $i, ($i - 2) / 10)->assertOk();
        }
        $this->assertEquals(0, PlaybackProgress::first()->covered_ms);
        $this->assertNull(PlaybackProgress::first()->completed_at);
    }

    public function test_rewatch_union_and_resume_do_not_duplicate_credit(): void
    {
        $session = $this->start();
        $this->watch($session, 20);
        $this->send($session, 6, 0)->assertOk();
        $this->travel(10)->seconds();
        $this->send($session, 7, 10, 'paused')->assertOk()->assertJsonPath('data.progress.covered_seconds', 20);
        $replacement = $this->start();
        $this->assertEquals(10, $replacement['resume_position']);
        $this->send($session, 8, 11)->assertConflict();
    }

    public function test_ownership_expiry_revocation_and_revision_are_checked_before_replay(): void
    {
        $session = $this->start();
        $event = (string) Str::uuid();
        $this->send($session, 1, 0, eventId: $event)->assertOk();
        $this->actingAs(User::factory()->create());
        $this->send($session, 1, 0, eventId: $event)->assertNotFound();
        $this->actingAs($this->student);
        $this->material->update(['revision' => 2]);
        $this->send($session, 1, 0, eventId: $event)->assertConflict();
        $session = $this->start();
        $this->travel(91)->seconds();
        $this->send($session, 1, 0)->assertConflict();
        $this->material->lesson->course->update(['type' => 'android']);
        $grant = Entitlement::create(['user_id' => $this->student->id, 'package_type' => 'android', 'status' => 'active', 'starts_at' => now()->subDay()]);
        $session = $this->start();
        $this->send($session, 1, 0, eventId: $event)->assertOk();
        $grant->update(['status' => 'revoked']);
        $this->send($session, 1, 0, eventId: $event)->assertConflict();
    }

    public function test_long_heartbeat_gap_pauses_without_credit_and_provider_failure_is_closed(): void
    {
        $session = $this->start();
        $this->send($session, 1, 0)->assertOk();
        $this->travel(30)->seconds();
        $this->send($session, 2, 30)->assertOk()->assertJsonPath('data.needs_resync', true)->assertJsonPath('data.progress.covered_seconds', 0);
        config(['services.youtube.api_key' => null]);
        $this->postJson('/api/v1/lms/materials/'.$this->material->id.'/playback', ['request_id' => (string) Str::uuid()])->assertStatus(503);
    }

    public function test_changed_duration_invalidates_old_content_progress_and_required_quiz_blocks_next(): void
    {
        $session = $this->start();
        $this->watch($session, 95);
        $this->material->update(['quiz_required' => true]);
        $next = LearningMaterial::factory()->youtube()->create(['lesson_id' => $this->material->lesson_id, 'sort_order' => 2, 'is_required' => true]);
        $this->postJson('/api/v1/lms/materials/'.$next->id.'/playback', ['request_id' => (string) Str::uuid()])->assertForbidden();
        $this->providerDuration = 'PT2M';
        $changed = $this->start();
        $this->assertEquals(0, $changed['progress']['percent']);
        $this->assertDatabaseCount('lms_playback_progress', 2);
        $this->send($session, 21, 95)->assertConflict();
    }

    public function test_legacy_percentage_cannot_unlock_and_retired_endpoint_cannot_write(): void
    {
        LearningMaterialView::create(['user_id' => $this->student->id, 'learning_material_id' => $this->material->id, 'viewed_at' => now(), 'completed_at' => now(), 'progress_percent' => 100]);
        $next = LearningMaterial::factory()->youtube()->create(['lesson_id' => $this->material->lesson_id, 'sort_order' => 2, 'is_required' => true]);
        $this->postJson('/api/v1/lms/materials/'.$next->id.'/playback', ['request_id' => (string) Str::uuid()])->assertForbidden();
        $this->postJson('/api/v1/learning-materials/'.$this->material->id.'/progress', ['progress_percent' => 100])->assertStatus(410)->assertJsonPath('code', 'PLAYBACK_SESSION_REQUIRED');
        $this->assertDatabaseCount('lms_playback_progress', 0);
        $this->assertDatabaseCount('learning_material_views', 1);
    }
}
