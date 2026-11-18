<?php

namespace App\Services;

use App\Jobs\DispatchLmsNotificationJob;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\LmsAssignment;
use App\Models\LmsAssignmentSubmission;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LmsNotificationService
{
    public function __construct(
        private NotificationService $inbox,
        private EntitlementService $access,
    ) {}

    /**
     * Register or update a device push token for the authenticated user.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function registerDeviceToken(User $user, array $input): array
    {
        $data = Validator::make($input, [
            'device_id' => 'required|string|min:4|max:128|regex:/^[A-Za-z0-9\-_:\.]+$/',
            'push_token' => 'required|string|min:8|max:255',
            'platform' => 'nullable|string|in:android,ios,web',
        ])->validate();

        $now = now();
        abort_if(str_starts_with($data['push_token'], 'fcm_mock_'), 422, 'Token push contoh tidak diizinkan.');
        DB::table('lms_device_push_tokens')->where('push_token', $data['push_token'])->where('user_id', '!=', $user->id)->update(['status' => 'revoked', 'updated_at' => $now]);
        $status = str_starts_with($data['push_token'], 'invalid:') ? 'invalid' : 'active';

        DB::table('lms_device_push_tokens')->updateOrInsert(
            [
                'user_id' => $user->id,
                'device_id' => $data['device_id'],
            ],
            [
                'push_token' => $data['push_token'],
                'platform' => $data['platform'] ?? 'android',
                'status' => $status,
                'last_registered_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return [
            'user_id' => $user->id,
            'device_id' => $data['device_id'],
            'platform' => $data['platform'] ?? 'android',
            'status' => $status,
            'registered_at' => $now->toIso8601String(),
        ];
    }

    /**
     * Revoke a device push token on logout or permission denial.
     */
    public function revokeDeviceToken(User $user, string $deviceId): int
    {
        return DB::table('lms_device_push_tokens')
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->update([
                'status' => 'revoked',
                'updated_at' => now(),
            ]);
    }

    /**
     * Enqueue an LMS notification idempotently by event_key and dispatch job.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function enqueueEvent(
        int $userId,
        string $eventKey,
        string $eventType,
        string $title,
        string $body,
        string $deepLink,
        array $meta = [],
    ): array {
        $existing = DB::table('lms_notification_dispatches')
            ->where('event_key', $eventKey)
            ->first();

        if ($existing) {
            return $this->formatDispatch($existing, true);
        }

        $safeMeta = collect($meta)
            ->except(['score', 'score_percent', 'answers', 'text_body', 'feedback', 'rubric', 'criteria'])
            ->all();

        $now = now();
        $dispatchId = DB::table('lms_notification_dispatches')->insertGetId([
            'user_id' => $userId,
            'notification_id' => null,
            'event_key' => $eventKey,
            'event_type' => $eventType,
            'channel' => 'in_app_push',
            'device_tokens' => null,
            'title' => $title,
            'body' => $body,
            'deep_link' => $deepLink,
            'meta' => json_encode($safeMeta, JSON_THROW_ON_ERROR),
            'status' => 'queued',
            'attempts' => 0,
            'provider_receipt' => null,
            'last_error' => null,
            'dispatched_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DispatchLmsNotificationJob::dispatch($dispatchId)->afterCommit();

        $row = DB::table('lms_notification_dispatches')->where('id', $dispatchId)->first();

        return $this->formatDispatch($row, false);
    }

    /**
     * Process a queued notification dispatch idempotently (safe across job retries).
     *
     * @return array<string, mixed>
     */
    public function processDispatch(int $dispatchId): array
    {
        $lease = (string) Str::uuid();
        $dispatch = DB::transaction(function () use ($dispatchId, $lease) {
            $row = DB::table('lms_notification_dispatches')->where('id', $dispatchId)->lockForUpdate()->first();
            abort_unless($row !== null, 404);
            if ($row->status === 'sent') {
                return $row;
            }
            if ($row->processing_until && Carbon::parse($row->processing_until)->isFuture()) {
                throw new \RuntimeException('push_dispatch_in_progress');
            }
            $notificationId = $row->notification_id;
            if (! $notificationId) {
                $meta = $row->meta ? json_decode($row->meta, true, 512, JSON_THROW_ON_ERROR) : [];
                $notificationId = $this->inbox->create('user', (int) $row->user_id, $row->event_type, $row->title, $row->body, $row->deep_link,
                    [...$meta, 'event_key' => $row->event_key, 'deep_link' => $row->deep_link])->id;
            }
            DB::table('lms_notification_dispatches')->where('id', $dispatchId)->update([
                'notification_id' => $notificationId, 'status' => 'processing', 'attempts' => $row->attempts + 1,
                'processing_token' => $lease, 'processing_until' => now()->addMinutes(2), 'updated_at' => now(),
            ]);

            return DB::table('lms_notification_dispatches')->where('id', $dispatchId)->first();
        });
        if ($dispatch->status === 'sent') {
            return $this->formatDispatch($dispatch, true);
        }

        $tokens = DB::table('lms_device_push_tokens')->where('user_id', $dispatch->user_id)->where('status', 'active')->pluck('push_token')->all();
        $receipts = [];
        $failed = false;
        foreach ($tokens as $token) {
            $hash = hash('sha256', $token);
            $delivery = DB::table('lms_push_deliveries')->where('dispatch_id', $dispatchId)->where('token_hash', $hash)->first();
            if ($delivery && in_array($delivery->status, ['sent', 'invalid'], true)) {
                if ($delivery->provider_receipt) {
                    $receipts[] = $delivery->provider_receipt;
                }

                continue;
            }
            $claimed = DB::table('lms_notification_dispatches')->where('id', $dispatchId)->where('processing_token', $lease)
                ->update(['processing_until' => now()->addMinutes(2), 'updated_at' => now()]);
            if (! $claimed) {
                throw new \RuntimeException('push_dispatch_lease_lost');
            }
            // Do not hold a database transaction while waiting on OAuth or FCM.
            $receipt = null;
            $error = null;
            $status = 'sent';
            try {
                $receipt = app(FcmPushTransport::class)->send($token, $dispatch->title, $dispatch->body,
                    ['deep_link' => $dispatch->deep_link, 'event_key' => $dispatch->event_key]);
                $receipts[] = $receipt;
            } catch (\DomainException $e) {
                $status = 'invalid';
                $error = 'push_token_unregistered';
                DB::table('lms_device_push_tokens')->where('user_id', $dispatch->user_id)->where('push_token', $token)->update(['status' => 'invalid', 'updated_at' => now()]);
            } catch (\Throwable $e) {
                $status = 'failed';
                $failed = true;
                $error = preg_match('/^push_(not_configured|credentials_unavailable|authentication_failed|provider_http_[0-9]{3})$/', $e->getMessage()) ? $e->getMessage() : 'push_delivery_failed';
            }
            DB::table('lms_push_deliveries')->updateOrInsert(['dispatch_id' => $dispatchId, 'token_hash' => $hash], [
                'status' => $status, 'provider_receipt' => $receipt, 'error_code' => $error, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return DB::transaction(function () use ($dispatchId, $dispatch, $lease, $failed, $receipts) {
            $row = DB::table('lms_notification_dispatches')->where('id', $dispatchId)->lockForUpdate()->first();
            if ($row->processing_token !== $lease) {
                throw new \RuntimeException('push_dispatch_lease_lost');
            }
            $status = $failed ? 'failed' : 'sent';
            DB::table('lms_notification_dispatches')->where('id', $dispatchId)->update([
                'status' => $status, 'channel' => $receipts === [] && ! $failed ? 'in_app' : 'in_app_push',
                'provider_receipt' => $receipts[0] ?? null, 'last_error' => $failed ? 'push_delivery_failed' : null,
                'processing_token' => null, 'processing_until' => null, 'dispatched_at' => $failed ? null : now(), 'updated_at' => now(),
            ]);
            AuditLog::create(['actor_type' => 'system', 'action' => 'lms.notification.dispatched', 'auditable_type' => Notification::class,
                'auditable_id' => $dispatch->notification_id, 'new_values' => ['dispatch_id' => $dispatchId, 'status' => $status]]);

            return $this->formatDispatch(DB::table('lms_notification_dispatches')->where('id', $dispatchId)->first(), false);
        });
    }

    /** @return array<string, mixed> */
    public function notifyQuizGradePublished(QuizAttempt $attempt, int $revision, bool $isRevision = false): array
    {
        $quiz = Quiz::find($attempt->quiz_id);
        $quizTitle = (string) ($attempt->snapshot['title'] ?? $quiz->title ?? 'Kuis LMS');
        $courseId = app(LmsGradingService::class)->courseIdForQuiz($quiz);
        $eventType = $isRevision ? 'quiz_grade_revised' : 'quiz_grade_published';
        $eventKey = "quiz_grade:{$attempt->id}:rev:{$revision}";
        $title = $isRevision
            ? "Pembaruan Nilai Kuis: {$quizTitle}"
            : "Hasil Kuis Diterbitkan: {$quizTitle}";
        $body = 'Hasil penilaian kuis Anda telah diperbarui oleh pengajar. Buka aplikasi VBAT untuk melihat rincian hasil secara aman.';
        $deepLink = $courseId
            ? "/learning-results?courseId={$courseId}&quizId={$attempt->quiz_id}"
            : "/quiz?id={$attempt->quiz_id}";

        return $this->enqueueEvent(
            (int) $attempt->user_id,
            $eventKey,
            $eventType,
            $title,
            $body,
            $deepLink,
            [
                'course_id' => $courseId,
                'quiz_id' => (int) $attempt->quiz_id,
                'attempt_id' => (int) $attempt->id,
                'revision' => $revision,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function notifyAssignmentGradePublished(LmsAssignmentSubmission $submission, int $revision, bool $isRevision = false): array
    {
        $assignmentTitle = (string) ($submission->assignment_snapshot['title'] ?? $submission->assignment->title ?? 'Tugas Praktik');
        $courseId = (int) ($submission->assignment->course_id ?? 0);
        $eventType = $isRevision ? 'assignment_grade_revised' : 'assignment_grade_published';
        $eventKey = "assignment_grade:{$submission->id}:rev:{$revision}";
        $title = $isRevision
            ? "Pembaruan Nilai Tugas: {$assignmentTitle}"
            : "Hasil Tugas Diterbitkan: {$assignmentTitle}";
        $body = 'Penilaian tugas praktik Anda telah diterbitkan oleh instruktur. Buka aplikasi VBAT untuk melihat hasil dan umpan balik.';
        $deepLink = "/assignment?id={$submission->assignment_id}&courseId={$courseId}";

        return $this->enqueueEvent(
            (int) $submission->user_id,
            $eventKey,
            $eventType,
            $title,
            $body,
            $deepLink,
            [
                'course_id' => $courseId,
                'assignment_id' => (int) $submission->assignment_id,
                'submission_id' => (int) $submission->id,
                'revision' => $revision,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function notifyAssignmentReturned(LmsAssignmentSubmission $submission, int $revision): array
    {
        $assignmentTitle = (string) ($submission->assignment_snapshot['title'] ?? $submission->assignment->title ?? 'Tugas Praktik');
        $courseId = (int) ($submission->assignment->course_id ?? 0);
        $eventKey = "assignment_return:{$submission->id}:rev:{$revision}";
        $title = "Revisi Tugas Diperlukan: {$assignmentTitle}";
        $body = 'Instruktur mengembalikan tugas Anda untuk diperbaiki. Buka aplikasi VBAT untuk membaca catatan revisi dan mengirim ulang.';
        $deepLink = "/assignment?id={$submission->assignment_id}&courseId={$courseId}";

        return $this->enqueueEvent(
            (int) $submission->user_id,
            $eventKey,
            'assignment_returned',
            $title,
            $body,
            $deepLink,
            [
                'course_id' => $courseId,
                'assignment_id' => (int) $submission->assignment_id,
                'submission_id' => (int) $submission->id,
                'revision' => $revision,
            ],
        );
    }

    /**
     * Dispatch H-1 (within 24h) deadline reminders for published assignments.
     */
    public function sendDeadlineReminders(?Carbon $referenceTime = null): int
    {
        $now = $referenceTime ? $referenceTime->copy() : now();
        $windowHours = (int) config('lms.assignment_reminder_window_hours', 24);
        $windowEnd = $now->copy()->addHours($windowHours);

        $assignments = LmsAssignment::with(['course', 'lesson'])
            ->where('status', 'published')
            ->whereNotNull('due_at')
            ->where('due_at', '>', $now)
            ->where('due_at', '<=', $windowEnd)
            ->get();

        $sentCount = 0;
        foreach ($assignments as $assignment) {
            $course = $assignment->course;
            $lesson = $assignment->lesson;
            if (! $course || $course->status !== 'published' || ! $lesson || $lesson->status !== 'published') {
                continue;
            }

            $students = User::whereIn('role', ['student', 'teknisi'])->get();
            foreach ($students as $student) {
                if (! $this->access->canAccessCourse($student, $course)) {
                    continue;
                }

                $hasSubmitted = LmsAssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('user_id', $student->id)
                    ->whereIn('status', ['submitted', 'graded'])
                    ->exists();

                if ($hasSubmitted) {
                    continue;
                }

                $dueWib = $assignment->due_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB';
                $dueKey = $assignment->due_at->format('YmdHi');
                $eventKey = "assignment_reminder:{$assignment->id}:user:{$student->id}:due:{$dueKey}";

                $result = $this->enqueueEvent(
                    (int) $student->id,
                    $eventKey,
                    'assignment_deadline_reminder',
                    "Pengingat Tenggat Tugas: {$assignment->title}",
                    "Tugas praktik \"{$assignment->title}\" akan berakhir pada {$dueWib}. Segera selesaikan dan kirim sebelum tenggat berakhir.",
                    "/assignment?id={$assignment->id}&courseId={$course->id}",
                    [
                        'course_id' => (int) $course->id,
                        'assignment_id' => (int) $assignment->id,
                        'due_at_wib' => $dueWib,
                    ],
                );

                if (! ($result['idempotent_replay'] ?? false)) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    /**
     * Verify deep-link target ownership and active course access on server before navigation.
     *
     * @return array<string, mixed>
     */
    public function resolveDeepLink(User $user, string $deepLink): array
    {
        $parsed = parse_url(trim($deepLink));
        $path = $parsed['path'] ?? '';
        parse_str($parsed['query'] ?? '', $query);

        if ($path === '/assignment') {
            $assignmentId = isset($query['id']) ? (int) $query['id'] : 0;
            abort_unless($assignmentId > 0, 422, 'Tautan tugas tidak memiliki ID yang valid.');
            $assignment = LmsAssignment::with(['course', 'lesson'])->findOrFail($assignmentId);
            abort_unless(
                $assignment->status === 'published'
                && $assignment->lesson?->status === 'published'
                && $assignment->course !== null,
                404,
                'Tugas tidak tersedia.',
            );
            abort_unless($this->access->canAccessCourse($user, $assignment->course), 403, 'Hak akses ke kelas tugas ini telah berakhir atau tidak tersedia.');

            return [
                'allowed' => true,
                'route' => '/assignment',
                'target_id' => $assignment->id,
                'course_id' => (int) $assignment->course_id,
                'deep_link' => "/assignment?id={$assignment->id}&courseId={$assignment->course_id}",
            ];
        }

        if ($path === '/quiz') {
            $quizId = isset($query['id']) ? (int) $query['id'] : 0;
            abort_unless($quizId > 0, 422, 'Tautan kuis tidak memiliki ID yang valid.');
            $quiz = Quiz::findOrFail($quizId);
            $courseId = app(LmsGradingService::class)->courseIdForQuiz($quiz);
            $course = $courseId ? Course::find($courseId) : null;
            abort_unless($course !== null && $course->status === 'published', 404, 'Kuis tidak tersedia.');
            abort_unless($this->access->canAccessCourse($user, $course), 403, 'Hak akses ke kelas kuis ini telah berakhir atau tidak tersedia.');

            return [
                'allowed' => true,
                'route' => '/quiz',
                'target_id' => $quiz->id,
                'course_id' => (int) $course->id,
                'deep_link' => "/quiz?id={$quiz->id}",
            ];
        }

        if ($path === '/learning-results' || $path === '/course-syllabus') {
            $courseId = isset($query['courseId']) ? (int) $query['courseId'] : (isset($query['id']) ? (int) $query['id'] : 0);
            abort_unless($courseId > 0, 422, 'Tautan kelas tidak memiliki ID yang valid.');
            $course = Course::findOrFail($courseId);
            abort_unless($course->status === 'published', 404, 'Kelas tidak tersedia.');
            if ($path === '/course-syllabus') {
                abort_unless($this->access->canAccessCourse($user, $course), 403, 'Hak akses kelas telah berakhir atau dicabut.');
            }

            return [
                'allowed' => true,
                'route' => $path,
                'target_id' => $course->id,
                'course_id' => (int) $course->id,
                'deep_link' => $deepLink,
            ];
        }

        abort(422, 'Tautan tujuan tidak dikenali oleh LMS.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDispatch(\stdClass $row, bool $replay): array
    {
        return [
            'id' => (int) $row->id,
            'user_id' => (int) $row->user_id,
            'notification_id' => $row->notification_id ? (int) $row->notification_id : null,
            'event_key' => $row->event_key,
            'event_type' => $row->event_type,
            'title' => $row->title,
            'body' => $row->body,
            'deep_link' => $row->deep_link,
            'status' => $row->status,
            'attempts' => (int) $row->attempts,
            'provider_receipt' => $row->provider_receipt,
            'last_error' => $row->last_error,
            'dispatched_at' => $row->dispatched_at ? Carbon::parse($row->dispatched_at)->toIso8601String() : null,
            'idempotent_replay' => $replay,
        ];
    }
}
