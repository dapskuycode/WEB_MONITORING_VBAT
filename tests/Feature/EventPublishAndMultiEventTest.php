<?php

namespace Tests\Feature;

use App\Models\DiscountEvent;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use App\Models\User;
use Database\Seeders\SponsorTierSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventPublishAndMultiEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SponsorTierSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT-01: Penyederhanaan Alur Publish Event
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_instant_publish_mode_makes_event_immediately_active(): void
    {
        // Event created in "Aktifkan Sekarang" mode: start_at = now(), end_at = now() + 7 days
        $event = DiscountEvent::create([
            'name' => 'SUPER SALE INSTANT',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'banner_text' => 'SUPER SALE 25% HEMAT SEKARANG',
            'start_at' => now(),
            'end_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        $this->assertTrue($event->isOngoing());
        $this->assertFalse($event->isScheduled());
        $this->assertFalse($event->isExpired());

        // Should appear immediately in active events API
        $response = $this->getJson('/api/v1/shop/events/active');
        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('has_active_event', true)
            ->assertJsonPath('data.name', 'SUPER SALE INSTANT');
    }

    #[Test]
    public function test_scheduled_publish_mode_is_inactive_until_start_time_arrives(): void
    {
        // Event scheduled for tomorrow
        $scheduledEvent = DiscountEvent::create([
            'name' => 'PROMO AKHIR PEKAN MENDATANG',
            'discount_type' => 'percentage',
            'discount_value' => 30,
            'banner_text' => 'COMING SOON DISKON 30%',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(5),
            'is_active' => true,
        ]);

        $this->assertFalse($scheduledEvent->isOngoing());
        $this->assertTrue($scheduledEvent->isScheduled());

        // API should report no active events
        $response = $this->getJson('/api/v1/shop/events/active');
        $response->assertOk()
            ->assertJsonPath('has_active_event', false)
            ->assertJsonPath('total_events', 0);

        // Fast-forward time to when the event starts (now + 2 days 1 hour)
        $this->travel(2)->days();
        $this->travel(1)->hours();

        $this->assertTrue($scheduledEvent->fresh()->isOngoing());

        // Should now be automatically active without any manual intervention
        $responseAfterTravel = $this->getJson('/api/v1/shop/events/active');
        $responseAfterTravel->assertOk()
            ->assertJsonPath('has_active_event', true)
            ->assertJsonPath('data.name', 'PROMO AKHIR PEKAN MENDATANG');
    }

    // ─────────────────────────────────────────────────────────────
    // EVENT-02: Multi-Event Diskon di API
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_multiple_active_events_returned_in_events_carousel_array(): void
    {
        $event1 = DiscountEvent::create([
            'name' => 'FLASH SALE SPESIAL VBAT',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'banner_text' => 'FLASH SALE 15% SEMUA PART',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
            'is_active' => true,
        ]);

        $event2 = DiscountEvent::create([
            'name' => 'PROMO GAJIAN TEKNISI',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'banner_text' => 'GAJIAN BERKAH DISKON 20%',
            'start_at' => now()->subHours(2),
            'end_at' => now()->addDays(5),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/shop/events/active');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('has_active_event', true)
            ->assertJsonPath('total_events', 2)
            ->assertJsonCount(2, 'events');

        $eventNames = collect($response->json('events'))->pluck('name')->toArray();
        $this->assertContains('FLASH SALE SPESIAL VBAT', $eventNames);
        $this->assertContains('PROMO GAJIAN TEKNISI', $eventNames);
    }
}
