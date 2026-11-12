<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\LmsUpload;
use App\Models\User;
use App\Services\EntitlementService;
use App\Services\MediaAuthorizationService;
use App\Services\MediaProxyService;
use App\Services\YouTubeValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LmsAccessMediaTest extends TestCase
{
    use RefreshDatabase;

    private function material(string $type = 'android'): LearningMaterial
    {
        $course = Course::create(['title' => 'Access', 'slug' => fake()->uuid(), 'status' => 'published', 'type' => $type, 'is_free_class' => $type === 'free_class']);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Module']);

        $upload = LmsUpload::create(['id' => (string) Str::uuid(), 'user_id' => User::factory()->create(['role' => 'super_admin'])->id,
            'purpose' => 'course_material', 'original_name' => 'fixture.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => strlen('%PDF-1.4 fixture'),
            'sha256' => hash('sha256', '%PDF-1.4 fixture'), 'path' => 'learning/pdfs/fixture.pdf', 'status' => 'ready', 'page_count' => 1, 'scanned_at' => now()]);

        return LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'published', 'material_type' => 'pdf_document', 'pdf_path' => $upload->path, 'pdf_upload_id' => $upload->id]);
    }

    private function grant(User $user, array $attributes = []): Entitlement
    {
        return Entitlement::create([...['user_id' => $user->id, 'package_type' => 'android', 'status' => 'active', 'starts_at' => now()->subDay(), 'expires_at' => now()->addDay(), 'source' => 'test'], ...$attributes]);
    }

    public function test_package_status_and_time_matrix_is_shared_by_course_and_media(): void
    {
        $this->freezeTime();
        $material = $this->material();
        $student = User::factory()->create();
        $service = app(EntitlementService::class);
        foreach ([
            ['android', 'active', -1, 1, true], ['iphone', 'active', -1, 1, false],
            ['bundling', 'active', -1, 1, true], ['premium', 'active', -1, 1, false],
            ['android', 'revoked', -1, 1, false], ['android', 'expired', -1, 1, false],
            ['android', 'active', 1, 2, false], ['android', 'active', -1, 0, false],
        ] as [$package, $status, $start, $end, $expected]) {
            $student->entitlements()->delete();
            $grant = $this->grant($student, ['package_type' => $package, 'status' => $status, 'starts_at' => now()->addDays($start), 'expires_at' => now()->addDays($end)]);
            $this->assertSame($expected, $service->canAccessCourse($student, $material->lesson->course));
            $this->assertSame($expected, app(MediaAuthorizationService::class)->canAccessMaterial($student, $material));
            $this->assertSame($status === 'active' && $start <= 0 && $end > 0, $grant->isValid());
        }
        $student->entitlements()->delete();
        $this->grant($student, ['starts_at' => now(), 'expires_at' => null]);
        $this->assertTrue($service->canAccessMaterial($student, $material));
        $this->assertNull($service->accessForType($student, 'android')['expires_at']);
        $this->assertFalse($service->canAccessMaterial(User::factory()->create(), $material));
    }

    public function test_free_hardware_and_unpublished_materials_do_not_inherit_other_access(): void
    {
        $student = User::factory()->create();
        $service = app(EntitlementService::class);
        $free = $this->material('free_class');
        $this->assertTrue($service->canAccessMaterial($student, $free));
        $free->update(['status' => 'draft']);
        $this->assertFalse($service->canAccessMaterial($student, $free));
        $free->update(['status' => 'published']);
        $free->lesson->course->update(['archived_at' => now()]);
        $this->assertFalse($service->canAccessMaterial($student, $free));
        $hardware = $this->material('hardware_solution');
        $this->grant($student, ['package_type' => 'hardware_solution']);
        $this->assertFalse($service->canAccessMaterial($student, $hardware));
    }

    public function test_activation_cannot_create_membership_or_entitlement(): void
    {
        $student = User::factory()->create();
        $this->postJson('/api/v1/membership/activate', ['user_id' => $student->id, 'package' => 'bundling'])->assertUnauthorized();
        $this->actingAs($student)->postJson('/api/v1/membership/activate', ['email' => $student->email, 'package' => 'bundling'])->assertStatus(409)->assertJsonPath('error.code', 'PURCHASE_VERIFICATION_REQUIRED');
        $this->postJson('/api/v1/payment/transactions', ['package_id' => 1])->assertStatus(503)->assertJsonPath('error.code', 'PURCHASES_UNAVAILABLE');
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertDatabaseCount('entitlements', 0);
        $this->assertDatabaseCount('memberships', 0);
    }

    public function test_snapshot_is_scoped_to_current_user_and_uses_real_schema(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $this->grant($student);
        $this->actingAs($student)->getJson('/api/v1/entitlements/access')->assertOk()->assertJsonPath('data.access.android.has_access', true)->assertJsonPath('data.access.iphone.has_access', false);
        $this->getJson('/api/v1/entitlements?package_type=android&status=active')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($other)->getJson('/api/v1/entitlements/access?user_id='.$student->id)->assertOk()->assertJsonPath('data.access.android.has_access', false);
    }

    public function test_media_requires_signature_user_and_current_entitlement(): void
    {
        Storage::fake('lms');
        $material = $this->material();
        Storage::disk('lms')->put($material->pdf_path, '%PDF-1.4 fixture');
        $student = User::factory()->create();
        $other = User::factory()->create();
        $grant = $this->grant($student);
        $url = app(MediaProxyService::class)->generateProxyUrl('pdf', $material->id, $student->id);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs($other)->getJson($url)->assertForbidden();
        $this->actingAs($student)->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->getJson('/api/v1/media/serve/pdf/'.$material->id)->assertForbidden();
        $this->getJson($url.'&extra=tampered')->assertForbidden();
        $grant->update(['status' => 'revoked']);
        $this->getJson($url)->assertForbidden();
        $grant->update(['status' => 'active']);
        $material->update(['status' => 'draft']);
        $this->getJson($url)->assertForbidden();
        $material->update(['status' => 'published']);
        $this->travel(6)->minutes();
        $this->getJson($url)->assertForbidden();
    }

    public function test_access_check_and_manifest_only_issue_protected_urls(): void
    {
        $material = $this->material();
        $student = User::factory()->create();
        $this->actingAs($student)->postJson('/api/v1/media/access-check', ['material_ids' => [$material->id]])->assertOk()->assertJsonPath('results.'.$material->id.'.media_url', null);
        $this->grant($student);
        $url = $this->postJson('/api/v1/media/access-check', ['material_ids' => [$material->id]])->assertOk()->json('results.'.$material->id.'.media_url');
        $this->assertStringContainsString('signature=', $url);
        $manifest = $this->getJson('/api/v1/entitlements/courses/'.$material->lesson->course_id.'/manifest')->assertOk();
        $this->assertStringNotContainsString('learning/pdfs/', $manifest->getContent());
        $this->assertStringContainsString('signature=', $manifest->getContent());
        $this->postJson('/api/v1/media/access-check', ['material_ids' => range(1, 51)])->assertUnprocessable();
    }

    public function test_private_pdf_paths_and_public_proxy_cannot_traverse_or_follow_symlinks(): void
    {
        Storage::fake('lms');
        Storage::fake('public');
        $material = $this->material();
        Storage::disk('public')->put($material->pdf_path, '%PDF-secret');
        Storage::disk('public')->put('unreferenced.pdf', '%PDF-secret');
        foreach ([$material->pdf_path, 'unreferenced.pdf', '../private/lms/secret.pdf', 'storage/learning/pdfs/fixture.pdf'] as $path) {
            $this->get('/api/v1/media-file?'.http_build_query(['path' => $path]))->assertNotFound();
            $this->get('/api/v1/storage/'.$path)->assertNotFound();
        }
        Storage::disk('public')->put('pixel.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j5r8AAAAASUVORK5CYII='));
        $this->get('/api/v1/storage/pixel.png')->assertOk();
        symlink(Storage::disk('public')->path($material->pdf_path), Storage::disk('lms')->path('escape.pdf'));
        $student = User::factory()->create();
        $this->grant($student);
        $material->update(['pdf_path' => 'escape.pdf']);
        $url = app(MediaProxyService::class)->generateProxyUrl('pdf', $material->id, $student->id);
        $this->actingAs($student)->getJson($url)->assertNotFound();
    }

    public function test_admin_pdf_upload_uses_private_disk_and_migration_is_verified_and_repeatable(): void
    {
        Storage::fake('lms');
        Storage::fake('public');
        $material = $this->material();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $material->update(['status' => 'draft']);
        $response = $this->actingAs($admin)->postJson('/api/v1/learning-materials/'.$material->id.'/pdf', ['expected_revision' => $material->revision, 'pdf' => UploadedFile::fake()->createWithContent('fixture.pdf', file_get_contents(base_path('tests/Fixtures/b08-reader.pdf')))])->assertStatus(202);
        $upload = LmsUpload::findOrFail($response->json('data.id'));
        $path = $upload->path;
        $this->assertSame('quarantine', $upload->status);
        Storage::disk('lms')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($response->json('data.pdf_url'));
        $material->update(['pdf_path' => 'learning/pdfs/legacy.pdf']);
        Storage::disk('public')->put($material->pdf_path, '%PDF-legacy');
        $this->artisan('lms:privatize-pdfs')->assertSuccessful();
        Storage::disk('public')->assertExists($material->pdf_path);
        $this->artisan('lms:privatize-pdfs --apply')->assertSuccessful();
        Storage::disk('public')->assertMissing($material->pdf_path);
        $this->assertSame('%PDF-legacy', Storage::disk('lms')->get($material->pdf_path));
        $this->artisan('lms:privatize-pdfs --apply')->assertSuccessful();
    }

    public function test_video_metadata_requires_current_grant_and_has_no_fake_provider_expiry(): void
    {
        $material = $this->material();
        $material->update(['material_type' => 'youtube_video', 'pdf_path' => null, 'youtube_video_id' => 'dQw4w9WgXcQ']);
        $student = User::factory()->create();
        $this->grant($student);
        $url = app(MediaProxyService::class)->generateProxyUrl('video', $material->id, $student->id);
        $response = $this->actingAs($student)->getJson($url)->assertOk()
            ->assertJsonPath('provider', 'youtube')->assertJsonPath('video_id', 'dQw4w9WgXcQ')
            ->assertJsonPath('provider_url_expires', false);
        $this->assertStringNotContainsString('access_token', $response->getContent());
        $pdfUrl = app(MediaProxyService::class)->generateProxyUrl('pdf', $material->id, $student->id);
        $this->getJson($pdfUrl)->assertNotFound();
        $material->update(['youtube_video_id' => 'malformed']);
        $this->getJson($url)->assertNotFound();
    }

    public function test_pdf_migration_preserves_public_original_on_private_file_conflict(): void
    {
        Storage::fake('lms');
        Storage::fake('public');
        $material = $this->material();
        Storage::disk('public')->put($material->pdf_path, '%PDF-original');
        Storage::disk('lms')->put($material->pdf_path, '%PDF-different');
        $this->artisan('lms:privatize-pdfs --apply')->assertFailed();
        $this->assertSame('%PDF-original', Storage::disk('public')->get($material->pdf_path));
        $this->assertSame('%PDF-different', Storage::disk('lms')->get($material->pdf_path));
    }

    public function test_youtube_validation_is_real_bounded_and_does_not_claim_unlisted_or_embed_proof(): void
    {
        Http::preventStrayRequests();
        $service = app(YouTubeValidationService::class);
        $this->assertNull($service->extractVideoId('https://evil.test/youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull($service->extractVideoId('https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', $service->extractVideoId('https://www.youtube.com/watch?x=1&v=dQw4w9WgXcQ'));
        Http::fake(['www.youtube.com/*' => Http::sequence()->push(['title' => 'Fixture'], 200)->push([], 404)]);
        $result = $service->validateVideoId('dQw4w9WgXcQ');
        $this->assertTrue($result['accessible']);
        $this->assertNull($result['unlisted']);
        $this->assertFalse($result['embed_verified']);
        $this->assertFalse($service->validateVideoId('dQw4w9WgXcQ')['valid']);
    }
}
