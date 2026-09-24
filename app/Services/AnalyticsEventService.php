<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AnalyticsEventService
{
    /**
     * Valid event types per category.
     */
    public const VALID_EVENT_TYPES = [
        // Traffic
        'page_view', 'session_start', 'feed_scroll',
        // Product
        'product_view', 'product_click', 'wishlist_add', 'wishlist_remove', 'outbound_click',
        // Campaign
        'impression', 'banner_click',
        // Learning
        'course_start', 'lesson_start', 'video_start', 'video_progress', 'video_complete', 'course_complete',
        // Quiz
        'quiz_attempt', 'quiz_pass', 'quiz_fail',
        // Material
        'pdf_open', 'pdf_download', 'video_preview_start', 'video_full_start', 'bookmark_add', 'bookmark_remove',
    ];

    /**
     * Valid target types.
     */
    public const VALID_TARGET_TYPES = [
        'sponsor_product', 'campaign', 'video', 'course', 'lesson', 'learning_material', 'sponsor',
    ];

    /**
     * Log a single event. Actor is ALWAYS derived from auth session.
     */
    public function logEvent(
        string $eventType,
        ?string $sessionId,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $context = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AnalyticsEvent {
        $this->validateEventType($eventType);

        if ($targetType !== null) {
            $this->validateTargetType($targetType);
        }

        $actorId = Auth::id();

        return AnalyticsEvent::create([
            'event_type' => $eventType,
            'actor_id' => $actorId,
            'session_id' => $sessionId ?? (string) Str::uuid(),
            'target_type' => $targetType,
            'target_id' => $targetId,
            'context' => $context,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Batch log multiple events. Returns count of logged events.
     */
    public function logBatch(array $events, ?string $sessionId = null, ?string $ipAddress = null, ?string $userAgent = null): int
    {
        $actorId = Auth::id();
        $now = now();
        $sessionId = $sessionId ?? (string) Str::uuid();
        $ip = $ipAddress ?? request()->ip();
        $ua = $userAgent ?? request()->userAgent();

        $records = [];

        foreach ($events as $event) {
            $type = $event['event_type'] ?? null;
            if (! $type || ! in_array($type, self::VALID_EVENT_TYPES, true)) {
                continue;
            }

            $tType = $event['target_type'] ?? null;
            if ($tType !== null && ! in_array($tType, self::VALID_TARGET_TYPES, true)) {
                continue;
            }

            $records[] = [
                'event_type' => $type,
                'actor_id' => $actorId,
                'session_id' => $event['session_id'] ?? $sessionId,
                'target_type' => $tType,
                'target_id' => $event['target_id'] ?? null,
                'context' => isset($event['context']) ? json_encode($event['context']) : null,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'created_at' => $now,
            ];
        }

        if (empty($records)) {
            return 0;
        }

        DB::table('analytics_events')->insert($records);

        return count($records);
    }

    /**
     * Get aggregated stats for dashboard.
     */
    public function getDashboardStats(string $from, string $to): array
    {
        $baseQuery = AnalyticsEvent::betweenDates($from, $to);

        return [
            'total_events' => (clone $baseQuery)->count(),
            'unique_sessions' => (clone $baseQuery)->distinct('session_id')->count('session_id'),
            'unique_actors' => (clone $baseQuery)->whereNotNull('actor_id')->distinct('actor_id')->count('actor_id'),
            'events_by_type' => (clone $baseQuery)
                ->selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->orderByDesc('count')
                ->pluck('count', 'event_type')
                ->toArray(),
            'product_analytics' => $this->getProductAnalytics($from, $to),
            'learning_analytics' => $this->getLearningAnalytics($from, $to),
            'campaign_analytics' => $this->getCampaignAnalytics($from, $to),
        ];
    }

    /**
     * Product-specific analytics.
     */
    public function getProductAnalytics(string $from, string $to): array
    {
        $base = AnalyticsEvent::betweenDates($from, $to)->where('target_type', 'sponsor_product');

        return [
            'views' => (clone $base)->where('event_type', 'product_view')->count(),
            'clicks' => (clone $base)->where('event_type', 'product_click')->count(),
            'wishlist_adds' => (clone $base)->where('event_type', 'wishlist_add')->count(),
            'wishlist_removes' => (clone $base)->where('event_type', 'wishlist_remove')->count(),
            'outbound_clicks' => (clone $base)->where('event_type', 'outbound_click')->count(),
            'top_products' => (clone $base)
                ->selectRaw('target_id, COUNT(*) as count')
                ->groupBy('target_id')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'target_id')
                ->toArray(),
        ];
    }

    /**
     * Learning-specific analytics.
     */
    public function getLearningAnalytics(string $from, string $to): array
    {
        $base = AnalyticsEvent::betweenDates($from, $to)->whereIn('target_type', ['course', 'video', 'learning_material']);

        return [
            'course_starts' => (clone $base)->where('event_type', 'course_start')->count(),
            'course_completions' => (clone $base)->where('event_type', 'course_complete')->count(),
            'video_starts' => (clone $base)->where('event_type', 'video_start')->count(),
            'video_completions' => (clone $base)->where('event_type', 'video_complete')->count(),
            'quiz_attempts' => AnalyticsEvent::betweenDates($from, $to)->where('event_type', 'quiz_attempt')->count(),
            'quiz_passes' => AnalyticsEvent::betweenDates($from, $to)->where('event_type', 'quiz_pass')->count(),
            'quiz_fails' => AnalyticsEvent::betweenDates($from, $to)->where('event_type', 'quiz_fail')->count(),
        ];
    }

    /**
     * Campaign impression analytics.
     */
    public function getCampaignAnalytics(string $from, string $to): array
    {
        $base = AnalyticsEvent::betweenDates($from, $to)->where('target_type', 'campaign');

        return [
            'impressions' => (clone $base)->where('event_type', 'impression')->count(),
            'banner_clicks' => (clone $base)->where('event_type', 'banner_click')->count(),
            'top_campaigns' => (clone $base)
                ->selectRaw('target_id, COUNT(*) as count')
                ->where('event_type', 'impression')
                ->groupBy('target_id')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'target_id')
                ->toArray(),
        ];
    }

    /**
     * Export events to CSV.
     */
    public function exportToCsv(string $from, string $to, ?string $eventType = null): string
    {
        $query = AnalyticsEvent::betweenDates($from, $to);

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $events = $query->orderBy('created_at')->get();

        $filename = storage_path("app/public/exports/analytics_{$from}_{$to}.csv");
        if (! is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        $handle = fopen($filename, 'w');
        fputcsv($handle, ['id', 'event_type', 'actor_id', 'session_id', 'target_type', 'target_id', 'context', 'ip_address', 'created_at']);

        foreach ($events as $event) {
            fputcsv($handle, [
                $event->id,
                $event->event_type,
                $event->actor_id ?? '',
                $event->session_id ?? '',
                $event->target_type ?? '',
                $event->target_id ?? '',
                json_encode($event->context),
                $event->ip_address ?? '',
                $event->created_at,
            ]);
        }

        fclose($handle);

        return $filename;
    }

    private function validateEventType(string $type): void
    {
        if (! in_array($type, self::VALID_EVENT_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid event type: {$type}");
        }
    }

    private function validateTargetType(string $type): void
    {
        if (! in_array($type, self::VALID_TARGET_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid target type: {$type}");
        }
    }
}
