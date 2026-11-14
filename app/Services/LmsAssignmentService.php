<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LmsAssignment;
use App\Models\LmsAssignmentDraft;
use App\Models\LmsAssignmentSubmission;
use App\Models\LmsUpload;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LmsAssignmentService
{
    public const DEFAULT_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveAssignment(User $actor, array $input, ?int $id = null): LmsAssignment
    {
        return DB::transaction(function () use ($actor, $input, $id) {
            abort_unless($actor->isLmsManager(), 403);
            $assignment = $id ? LmsAssignment::lockForUpdate()->findOrFail($id) : new LmsAssignment;
            $lessonId = (int) ($input['lesson_id'] ?? $assignment->lesson_id);
            $lesson = Lesson::lockForUpdate()->findOrFail($lessonId);
            $course = Course::lockForUpdate()->findOrFail($lesson->course_id);
            abort_unless($actor->canManageCourse($course), 403, 'Anda tidak memiliki akses ke kelas ini.');

            if ($assignment->exists) {
                Validator::make($input, ['expected_revision' => 'required|integer|min:1'])->validate();
                abort_if((int) $input['expected_revision'] !== (int) $assignment->revision, 409, 'Tugas telah berubah. Muat ulang sebelum menyimpan.');
                if ((int) $assignment->lesson_id !== (int) $lesson->id) {
                    throw ValidationException::withMessages(['lesson_id' => 'Tugas tidak dapat dipindahkan ke modul lain.']);
                }
            }

            $data = Validator::make($input, [
                'lesson_id' => 'required|integer|exists:lessons,id',
                'code' => ['nullable', 'alpha_dash', 'max:50', Rule::unique('lms_assignments', 'code')->ignore($id)],
                'title' => 'required|string|max:160',
                'instructions' => 'required|string|max:20000',
                'status' => ['sometimes', Rule::in(['draft', 'published', 'closed', 'archived'])],
                'sort_order' => 'sometimes|integer|min:0',
                'is_required' => 'sometimes|boolean',
                'max_score' => 'sometimes|numeric|min:1|max:1000',
                'passing_score' => 'sometimes|numeric|min:0|max:100',
                'weight' => 'sometimes|numeric|min:0|max:100',
                'rubric' => 'nullable|array|max:25',
                'rubric.*.id' => 'required_with:rubric|string|max:40',
                'rubric.*.label' => 'required_with:rubric|string|max:160',
                'rubric.*.max_points' => 'required_with:rubric|numeric|min:0.01|max:1000',
                'rubric.*.description' => 'nullable|string|max:1000',
                'allowed_mimes' => 'nullable|array|min:1|max:3',
                'allowed_mimes.*' => ['string', Rule::in(self::DEFAULT_MIMES)],
                'max_files' => 'sometimes|integer|min:1|max:'.config('lms.max_assignment_files', 5),
                'max_file_bytes' => 'sometimes|integer|min:1024|max:'.(config('lms.max_assignment_file_kb', 20480) * 1024),
                'requires_text' => 'sometimes|boolean',
                'requires_attachment' => 'sometimes|boolean',
                'due_at' => 'nullable|date',
                'late_submission_allowed' => 'sometimes|boolean',
                'late_until' => 'nullable|date',
            ])->validate();

            $maxScore = (float) ($data['max_score'] ?? ($assignment->exists ? $assignment->max_score : 100));
            $rubric = array_key_exists('rubric', $data) ? $data['rubric'] : ($assignment->exists ? $assignment->rubric : null);
            if (is_array($rubric) && $rubric !== []) {
                $ids = array_column($rubric, 'id');
                if (count(array_unique($ids)) !== count($ids)) {
                    throw ValidationException::withMessages(['rubric' => 'ID kriteria rubrik tidak boleh duplikat.']);
                }
                $sum = array_sum(array_map(fn ($r) => (float) $r['max_points'], $rubric));
                if (abs($sum - $maxScore) > 0.01) {
                    throw ValidationException::withMessages(['rubric' => 'Total poin kriteria rubrik harus sama dengan nilai maksimum tugas.']);
                }
            }

            $dueAt = array_key_exists('due_at', $data)
                ? ($data['due_at'] ? Carbon::parse($data['due_at']) : null)
                : ($assignment->exists ? $assignment->due_at : null);
            $lateAllowed = (bool) ($data['late_submission_allowed'] ?? ($assignment->exists ? $assignment->late_submission_allowed : false));
            $lateUntil = array_key_exists('late_until', $data)
                ? ($data['late_until'] ? Carbon::parse($data['late_until']) : null)
                : ($assignment->exists ? $assignment->late_until : null);
            if ($dueAt && $lateUntil && $lateUntil->lt($dueAt)) {
                throw ValidationException::withMessages(['late_until' => 'Batas akhir keterlambatan harus setelah tenggat utama.']);
            }

            $old = $assignment->getAttributes();
            $status = (string) ($data['status'] ?? ($assignment->exists ? $assignment->status : 'draft'));
            $assignment->fill([
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
                'code' => $data['code'] ?? ($assignment->code ?: 'ASG-'.Str::upper(Str::random(8))),
                'title' => $data['title'],
                'instructions' => $data['instructions'],
                'status' => $status,
                'sort_order' => (int) ($data['sort_order'] ?? ($assignment->exists ? $assignment->sort_order : (($lesson->assignments()->max('sort_order') ?? -1) + 1))),
                'is_required' => (bool) ($data['is_required'] ?? ($assignment->exists ? $assignment->is_required : false)),
                'max_score' => $maxScore,
                'passing_score' => (float) ($data['passing_score'] ?? ($assignment->exists ? $assignment->passing_score : 70)),
                'weight' => (float) ($data['weight'] ?? ($assignment->exists ? $assignment->weight : 1)),
                'rubric' => $rubric,
                'allowed_mimes' => $data['allowed_mimes'] ?? ($assignment->allowed_mimes ?: self::DEFAULT_MIMES),
                'max_files' => (int) ($data['max_files'] ?? ($assignment->exists ? $assignment->max_files : 5)),
                'max_file_bytes' => (int) ($data['max_file_bytes'] ?? ($assignment->exists ? $assignment->max_file_bytes : 20971520)),
                'requires_text' => (bool) ($data['requires_text'] ?? ($assignment->exists ? $assignment->requires_text : false)),
                'requires_attachment' => (bool) ($data['requires_attachment'] ?? ($assignment->exists ? $assignment->requires_attachment : false)),
                'due_at' => $dueAt,
                'late_submission_allowed' => $lateAllowed,
                'late_until' => $lateAllowed ? $lateUntil : null,
            ]);

            if ($status === 'published') {
                $this->assertAssignmentReady($assignment);
                $assignment->published_at = $assignment->published_at ?? now();
            }

            $assignment->revision = $assignment->exists ? $assignment->revision + 1 : 1;
            $assignment->save();

            AuditLog::create([
                'actor_type' => 'admin',
                'actor_id' => $actor->id,
                'action' => 'lms.assignment.'.(! $old ? 'created' : (($old['status'] ?? null) !== $assignment->status ? $assignment->status : 'updated')),
                'auditable_type' => LmsAssignment::class,
                'auditable_id' => $assignment->id,
                'old_values' => $old,
                'new_values' => $assignment->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 200, ''),
            ]);

            return $assignment->fresh();
        });
    }

    public function transitionStatus(User $actor, int $id, string $status, int $expectedRevision): LmsAssignment
    {
        return DB::transaction(function () use ($actor, $id, $status, $expectedRevision) {
            abort_unless($actor->isLmsManager(), 403);
            abort_unless(in_array($status, ['draft', 'published', 'closed', 'archived'], true), 422);
            $assignment = LmsAssignment::lockForUpdate()->findOrFail($id);
            abort_unless($actor->canManageCourse($assignment->course_id), 403, 'Anda tidak memiliki akses ke kelas ini.');
            abort_if((int) $assignment->revision !== $expectedRevision, 409, 'Tugas telah berubah. Muat ulang sebelum menyimpan.');

            if ($status === 'published') {
                $this->assertAssignmentReady($assignment);
                $assignment->published_at = $assignment->published_at ?? now();
            }
            $old = $assignment->getAttributes();
            $assignment->status = $status;
            $assignment->revision = $assignment->revision + 1;
            $assignment->save();

            AuditLog::create([
                'actor_type' => 'admin',
                'actor_id' => $actor->id,
                'action' => 'lms.assignment.'.$status,
                'auditable_type' => LmsAssignment::class,
                'auditable_id' => $assignment->id,
                'old_values' => $old,
                'new_values' => $assignment->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 200, ''),
            ]);

            return $assignment->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function detailForStudent(User $student, int $assignmentId): array
    {
        $assignment = $this->resolveStudentAssignment($student, $assignmentId);
        $draft = LmsAssignmentDraft::where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();
        $submissions = LmsAssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->orderByDesc('version')
            ->get();
        $latest = $submissions->first();

        $draftFiles = [];
        if ($draft && is_array($draft->upload_ids) && $draft->upload_ids !== []) {
            $uploads = LmsUpload::whereIn('id', $draft->upload_ids)->where('user_id', $student->id)->get()->keyBy('id');
            foreach ($draft->upload_ids as $uid) {
                if ($u = $uploads->get($uid)) {
                    $draftFiles[] = app(LmsUploadService::class)->data($u);
                }
            }
        }

        $canEdit = $assignment->status === 'published'
            && ($latest === null || ($latest->status === 'returned' && $latest->resubmit_allowed));

        return [
            'id' => $assignment->id,
            'course_id' => $assignment->course_id,
            'module_id' => $assignment->lesson_id,
            'code' => $assignment->code,
            'title' => $assignment->title,
            'instructions' => $assignment->instructions,
            'status' => $assignment->status,
            'is_required' => (bool) $assignment->is_required,
            'max_score' => (float) $assignment->max_score,
            'passing_score' => (float) $assignment->passing_score,
            'weight' => (float) $assignment->weight,
            'allowed_mimes' => $assignment->allowed_mimes ?: self::DEFAULT_MIMES,
            'max_files' => (int) $assignment->max_files,
            'max_file_bytes' => (int) $assignment->max_file_bytes,
            'requires_text' => (bool) $assignment->requires_text,
            'requires_attachment' => (bool) $assignment->requires_attachment,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'due_at_wib' => $assignment->due_at ? $assignment->due_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : null,
            'late_submission_allowed' => (bool) $assignment->late_submission_allowed,
            'late_until' => $assignment->late_until?->toIso8601String(),
            'can_submit' => $canEdit,
            'student_state' => $this->resolveStudentState($latest, $draft),
            'draft' => $draft ? [
                'text_body' => $draft->text_body ?? '',
                'upload_ids' => array_values($draft->upload_ids ?? []),
                'files' => $draftFiles,
                'version' => (int) $draft->version,
                'allow_resubmit' => (bool) $draft->allow_resubmit,
                'resubmit_deadline_at' => $draft->resubmit_deadline_at?->toIso8601String(),
                'last_return_feedback' => $draft->last_return_feedback,
                'updated_at' => $draft->updated_at?->toIso8601String(),
            ] : [
                'text_body' => '',
                'upload_ids' => [],
                'files' => [],
                'version' => 1,
                'allow_resubmit' => false,
                'resubmit_deadline_at' => null,
                'last_return_feedback' => null,
                'updated_at' => null,
            ],
            'latest_submission' => $latest ? $this->formatStudentSubmission($latest) : null,
            'submission_history' => $submissions->map(fn (LmsAssignmentSubmission $s) => $this->formatStudentSubmission($s))->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function saveDraft(User $student, int $assignmentId, array $input): array
    {
        abort_if($student->isLmsManager() || $student->isSponsor(), 403, 'Hanya siswa yang dapat menyimpan draft tugas.');
        $data = Validator::make($input, [
            'request_id' => 'required|uuid',
            'expected_version' => 'sometimes|nullable|integer|min:1',
            'text_body' => 'nullable|string|max:50000',
            'upload_ids' => 'sometimes|array|max:10',
            'upload_ids.*' => 'uuid',
            'user_id' => 'prohibited',
            'score' => 'prohibited',
            'status' => 'prohibited',
            'grade_status' => 'prohibited',
            'submitted_at' => 'prohibited',
            'is_late' => 'prohibited',
        ])->validate();

        $assignment = $this->resolveStudentAssignment($student, $assignmentId);
        abort_if($assignment->status === 'closed', 409, 'Pengumpulan tugas telah ditutup.');

        return DB::transaction(function () use ($student, $assignment, $data) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $scopeKey = 'draft:'.$assignment->id.':'.$student->id;
            $uploadIds = array_values(array_unique($data['upload_ids'] ?? []));
            $payloadHash = hash('sha256', json_encode([
                'text_body' => $data['text_body'] ?? null,
                'upload_ids' => $uploadIds,
                'expected_version' => $data['expected_version'] ?? null,
            ], JSON_THROW_ON_ERROR));

            $replay = DB::table('lms_submission_requests')
                ->where('scope_key', $scopeKey)
                ->where('request_id', $data['request_id'])
                ->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $payloadHash, 409, 'Request ID sudah digunakan dengan muatan berbeda.');

                return $this->detailForStudent($student, $assignment->id);
            }

            $latest = LmsAssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('user_id', $student->id)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();
            if ($latest !== null && ! ($latest->status === 'returned' && $latest->resubmit_allowed)) {
                abort(409, 'Tugas sudah dikirim dan terkunci.');
            }

            if (count($uploadIds) > (int) $assignment->max_files) {
                throw ValidationException::withMessages(['upload_ids' => 'Maksimal '.$assignment->max_files.' berkas lampiran.']);
            }
            $allowedMimes = $assignment->allowed_mimes ?: self::DEFAULT_MIMES;
            foreach ($uploadIds as $uid) {
                app(LmsUploadService::class)->readyForSubmission($uid, $student, $allowedMimes, (int) $assignment->max_file_bytes);
            }

            $draft = LmsAssignmentDraft::where('assignment_id', $assignment->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($draft) {
                if (isset($data['expected_version']) && (int) $data['expected_version'] !== (int) $draft->version) {
                    abort(409, 'Draft telah berubah di perangkat lain. Muat ulang sebelum menyimpan.');
                }
                $draft->text_body = $data['text_body'] ?? $draft->text_body;
                $draft->upload_ids = $uploadIds;
                $draft->version = (int) $draft->version + 1;
                $draft->save();
            } else {
                $draft = LmsAssignmentDraft::create([
                    'assignment_id' => $assignment->id,
                    'user_id' => $student->id,
                    'text_body' => $data['text_body'] ?? '',
                    'upload_ids' => $uploadIds,
                    'version' => 1,
                    'allow_resubmit' => false,
                ]);
            }

            DB::table('lms_submission_requests')->insert([
                'scope_key' => $scopeKey,
                'request_id' => $data['request_id'],
                'payload_hash' => $payloadHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->detailForStudent($student, $assignment->id);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function submit(User $student, int $assignmentId, array $input): array
    {
        abort_if($student->isLmsManager() || $student->isSponsor(), 403, 'Hanya siswa yang dapat mengirim tugas.');
        $data = Validator::make($input, [
            'request_id' => 'required|uuid',
            'text_body' => 'sometimes|nullable|string|max:50000',
            'upload_ids' => 'sometimes|array|max:10',
            'upload_ids.*' => 'uuid',
            'user_id' => 'prohibited',
            'score' => 'prohibited',
            'status' => 'prohibited',
            'grade_status' => 'prohibited',
            'submitted_at' => 'prohibited',
            'is_late' => 'prohibited',
        ])->validate();

        $assignment = $this->resolveStudentAssignment($student, $assignmentId);

        return DB::transaction(function () use ($student, $assignment, $data) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $scopeKey = 'submit:'.$assignment->id.':'.$student->id;
            $payloadHash = hash('sha256', json_encode([
                'assignment_id' => $assignment->id,
                'text_body' => $data['text_body'] ?? null,
                'upload_ids' => $data['upload_ids'] ?? null,
            ], JSON_THROW_ON_ERROR));

            $existingByReq = LmsAssignmentSubmission::where('user_id', $student->id)
                ->where('request_id', $data['request_id'])
                ->first();
            if ($existingByReq) {
                abort_unless($existingByReq->payload_hash === $payloadHash && (int) $existingByReq->assignment_id === (int) $assignment->id, 409, 'Request ID sudah digunakan untuk transaksi lain.');

                return $this->detailForStudent($student, $assignment->id);
            }

            abort_if($assignment->status === 'closed', 409, 'Pengumpulan tugas telah ditutup.');

            $latest = LmsAssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('user_id', $student->id)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();
            $draft = LmsAssignmentDraft::where('assignment_id', $assignment->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();

            $isResubmit = $latest !== null && $latest->status === 'returned' && $latest->resubmit_allowed;
            if ($latest !== null && ! $isResubmit) {
                abort(409, 'Tugas sudah dikirim dan terkunci. Pengiriman ulang memerlukan pengembalian dari instruktur.');
            }

            $now = now();
            $isLate = false;
            if ($isResubmit && $latest->resubmit_deadline_at !== null) {
                if ($now->gt($latest->resubmit_deadline_at)) {
                    throw ValidationException::withMessages(['due_at' => 'Batas waktu pengiriman ulang tugas telah berakhir.']);
                }
                $isLate = $assignment->due_at ? $now->gt($assignment->due_at) : false;
            } elseif ($assignment->due_at !== null && $now->gt($assignment->due_at)) {
                if (! $assignment->late_submission_allowed) {
                    throw ValidationException::withMessages(['due_at' => 'Batas waktu pengumpulan tugas telah berakhir.']);
                }
                if ($assignment->late_until !== null && $now->gt($assignment->late_until)) {
                    throw ValidationException::withMessages(['due_at' => 'Batas akhir keterlambatan pengumpulan tugas telah berakhir.']);
                }
                $isLate = true;
            }

            $textBody = array_key_exists('text_body', $data) ? (string) ($data['text_body'] ?? '') : (string) ($draft?->text_body ?? '');
            $uploadIds = array_key_exists('upload_ids', $data)
                ? array_values(array_unique($data['upload_ids'] ?? []))
                : array_values(array_unique($draft?->upload_ids ?? []));

            $trimmedText = trim($textBody);
            if ($assignment->requires_text && $trimmedText === '') {
                throw ValidationException::withMessages(['text_body' => 'Jawaban teks wajib diisi untuk tugas ini.']);
            }
            if ($assignment->requires_attachment && $uploadIds === []) {
                throw ValidationException::withMessages(['upload_ids' => 'Lampiran berkas wajib diunggah untuk tugas ini.']);
            }
            if ($trimmedText === '' && $uploadIds === []) {
                throw ValidationException::withMessages(['text_body' => 'Isi jawaban teks atau lampirkan berkas sebelum mengirim tugas.']);
            }
            if (count($uploadIds) > (int) $assignment->max_files) {
                throw ValidationException::withMessages(['upload_ids' => 'Maksimal '.$assignment->max_files.' berkas lampiran.']);
            }

            $allowedMimes = $assignment->allowed_mimes ?: self::DEFAULT_MIMES;
            $filesSnapshot = [];
            foreach ($uploadIds as $uid) {
                $upload = app(LmsUploadService::class)->readyForSubmission($uid, $student, $allowedMimes, (int) $assignment->max_file_bytes);
                $filesSnapshot[] = [
                    'upload_id' => $upload->id,
                    'original_name' => $upload->original_name,
                    'mime_type' => $upload->mime_type,
                    'size_bytes' => (int) $upload->size_bytes,
                    'sha256' => $upload->sha256,
                    'page_count' => $upload->page_count,
                    'storage_path' => $upload->path,
                ];
            }

            $version = $latest ? ((int) $latest->version + 1) : 1;
            $receiptCode = 'SUB-'.$now->copy()->timezone('Asia/Jakarta')->format('Ymd').'-'.Str::upper(Str::random(8));

            $submission = LmsAssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'user_id' => $student->id,
                'version' => $version,
                'receipt_code' => $receiptCode,
                'request_id' => $data['request_id'],
                'payload_hash' => $payloadHash,
                'text_body' => $trimmedText !== '' ? $textBody : null,
                'files_snapshot' => $filesSnapshot,
                'assignment_snapshot' => [
                    'id' => $assignment->id,
                    'code' => $assignment->code,
                    'title' => $assignment->title,
                    'instructions' => $assignment->instructions,
                    'max_score' => (float) $assignment->max_score,
                    'passing_score' => (float) $assignment->passing_score,
                    'weight' => (float) $assignment->weight,
                    'rubric' => $assignment->rubric,
                    'due_at' => $assignment->due_at?->toIso8601String(),
                    'revision' => (int) $assignment->revision,
                ],
                'status' => 'submitted',
                'grade_status' => 'pending_review',
                'grade_version' => 0,
                'published_revision' => null,
                'score' => null,
                'score_percent' => null,
                'is_passed' => false,
                'is_late' => $isLate,
                'submitted_at' => $now,
                'resubmit_allowed' => false,
            ]);

            if ($latest && $latest->resubmit_allowed) {
                $latest->resubmit_allowed = false;
                $latest->save();
            }

            if ($draft) {
                $draft->text_body = $trimmedText !== '' ? $textBody : '';
                $draft->upload_ids = $uploadIds;
                $draft->allow_resubmit = false;
                $draft->resubmit_deadline_at = null;
                $draft->version = (int) $draft->version + 1;
                $draft->save();
            }

            DB::table('lms_submission_requests')->insert([
                'scope_key' => $scopeKey,
                'request_id' => $data['request_id'],
                'payload_hash' => $payloadHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            AuditLog::create([
                'actor_type' => 'user',
                'actor_id' => $student->id,
                'action' => 'lms.submission.'.($version > 1 ? 'resubmitted' : 'submitted'),
                'auditable_type' => LmsAssignmentSubmission::class,
                'auditable_id' => $submission->id,
                'old_values' => null,
                'new_values' => [
                    'assignment_id' => $assignment->id,
                    'version' => $version,
                    'receipt_code' => $receiptCode,
                    'is_late' => $isLate,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 200, ''),
            ]);

            return $this->detailForStudent($student, $assignment->id);
        });
    }

    public function downloadFile(User $actor, int $submissionId, string $uploadId): StreamedResponse
    {
        $submission = LmsAssignmentSubmission::with('assignment')->findOrFail($submissionId);
        $isOwner = (int) $submission->user_id === (int) $actor->id;
        $isReviewer = $actor->isLmsManager() && $actor->canManageCourse($submission->assignment?->course_id);
        abort_unless($isOwner || $isReviewer, 403, 'Anda tidak memiliki akses ke lampiran ini.');

        $fileEntry = collect($submission->files_snapshot ?? [])->firstWhere('upload_id', $uploadId);
        abort_unless(is_array($fileEntry), 404, 'Lampiran tidak ditemukan pada submission ini.');

        $storagePath = (string) ($fileEntry['storage_path'] ?? '');
        $realPath = app(LmsFileService::class)->path('lms', $storagePath);
        abort_unless($realPath && hash_file('sha256', $realPath) === ($fileEntry['sha256'] ?? ''), 404, 'Berkas lampiran tidak tersedia.');

        return Storage::disk('lms')->download($storagePath, (string) ($fileEntry['original_name'] ?? 'lampiran'), [
            'Content-Type' => (string) ($fileEntry['mime_type'] ?? 'application/octet-stream'),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function gradeSubmission(User $actor, int $submissionId, array $input, bool $publish): array
    {
        abort_unless($actor->isLmsManager(), 403);
        $rules = [
            'request_id' => 'required|uuid',
            'expected_version' => 'required|integer|min:0',
            'reason' => 'required|string|min:5|max:2000',
            'score' => $publish ? 'sometimes|nullable|numeric|min:0|max:1000' : 'required|numeric|min:0|max:1000',
            'feedback' => $publish ? 'sometimes|nullable|string|min:3|max:10000' : 'required|string|min:3|max:10000',
            'criteria' => 'sometimes|nullable|array|max:25',
        ];
        $data = Validator::make($input, $rules)->validate();
        $expectedVersion = (int) $data['expected_version'];

        return DB::transaction(function () use ($actor, $submissionId, $data, $expectedVersion, $publish) {
            $submission = LmsAssignmentSubmission::with('assignment')->lockForUpdate()->findOrFail($submissionId);
            abort_unless($actor->canManageCourse($submission->assignment?->course_id), 403, 'Anda tidak memiliki akses ke kelas ini.');
            User::whereKey($submission->user_id)->lockForUpdate()->firstOrFail();

            $scopeKey = 'grade:'.$submission->id;
            $payloadHash = hash('sha256', json_encode([
                'publish' => $publish,
                'expected_version' => $expectedVersion,
                'score' => $data['score'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'criteria' => $data['criteria'] ?? null,
                'reason' => $data['reason'],
            ], JSON_THROW_ON_ERROR));

            $replay = DB::table('lms_submission_requests')
                ->where('scope_key', $scopeKey)
                ->where('request_id', $data['request_id'])
                ->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $payloadHash, 409, 'Request ID sudah digunakan dengan muatan berbeda.');

                return $this->submissionDetail($actor, $submission->id);
            }

            abort_unless((int) $submission->grade_version === $expectedVersion, 409, 'Nilai submission telah berubah. Muat ulang review.');

            $snapshot = $submission->assignment_snapshot ?? [];
            $maxScore = (float) ($snapshot['max_score'] ?? $submission->assignment?->max_score ?? 100);
            $passingScore = (float) ($snapshot['passing_score'] ?? $submission->assignment?->passing_score ?? 70);
            $rubric = $snapshot['rubric'] ?? $submission->assignment?->rubric;

            if ($publish && (! isset($data['score']) || $data['score'] === null)) {
                $draft = DB::table('lms_submission_grade_revisions')
                    ->where('submission_id', $submission->id)
                    ->where('revision', $submission->grade_version)
                    ->where('status', 'draft')
                    ->first();
                abort_unless($draft !== null, 409, 'Simpan draft review terlebih dahulu atau kirim skor lengkap.');
                $score = (float) $draft->score;
                $feedback = (string) $draft->feedback;
                $criteria = $draft->criteria ? json_decode($draft->criteria, true, 512, JSON_THROW_ON_ERROR) : null;
            } else {
                if (! isset($data['score']) || ! isset($data['feedback']) || trim((string) $data['feedback']) === '') {
                    throw ValidationException::withMessages(['score' => 'Skor dan feedback wajib diisi.']);
                }
                $score = (float) $data['score'];
                $feedback = (string) $data['feedback'];
                $criteria = $data['criteria'] ?? null;
            }

            if ($score < 0 || $score > $maxScore) {
                throw ValidationException::withMessages(['score' => 'Skor harus berada di antara 0 dan '.$maxScore.'.']);
            }

            if (is_array($rubric) && $rubric !== [] && is_array($criteria) && $criteria !== []) {
                $allowed = array_column($rubric, 'max_points', 'id');
                if (array_diff(array_keys($criteria), array_keys($allowed)) || count($criteria) !== count($allowed)) {
                    throw ValidationException::withMessages(['criteria' => 'Seluruh kriteria rubrik wajib diisi.']);
                }
                foreach ($criteria as $cid => $cpoints) {
                    if (! is_numeric($cpoints) || (float) $cpoints < 0 || (float) $cpoints > (float) $allowed[$cid]) {
                        throw ValidationException::withMessages(['criteria' => 'Poin kriteria rubrik melampaui batas maksimum.']);
                    }
                }
                if (abs(array_sum(array_map('floatval', $criteria)) - $score) > 0.01) {
                    throw ValidationException::withMessages(['criteria' => 'Jumlah poin kriteria rubrik harus sama dengan skor total.']);
                }
            }

            $scorePercent = $maxScore > 0 ? round($score * 100 / $maxScore, 2) : 0.0;
            $passed = $scorePercent >= $passingScore;
            $nextRevision = (int) $submission->grade_version + 1;
            $status = $publish ? 'published' : 'draft';

            DB::table('lms_submission_grade_revisions')->insert([
                'submission_id' => $submission->id,
                'revision' => $nextRevision,
                'actor_id' => $actor->id,
                'status' => $status,
                'score' => round($score, 2),
                'score_percent' => $scorePercent,
                'passed' => $passed,
                'criteria' => $criteria ? json_encode($criteria, JSON_THROW_ON_ERROR) : null,
                'feedback' => $feedback,
                'reason' => $data['reason'],
                'allow_resubmit' => false,
                'resubmit_deadline_at' => null,
                'published_at' => $publish ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $wasAlreadyPublished = $submission->published_revision !== null;
            $before = $submission->only(['status', 'grade_status', 'grade_version', 'published_revision', 'score', 'score_percent', 'is_passed']);
            $submission->grade_version = $nextRevision;
            if ($publish) {
                $submission->status = 'graded';
                $submission->grade_status = 'published';
                $submission->published_revision = $nextRevision;
                $submission->score = round($score, 2);
                $submission->score_percent = $scorePercent;
                $submission->is_passed = $passed;
                $submission->resubmit_allowed = false;
            }
            $submission->save();

            DB::table('lms_submission_requests')->insert([
                'scope_key' => $scopeKey,
                'request_id' => $data['request_id'],
                'payload_hash' => $payloadHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLog::create([
                'actor_type' => 'admin',
                'actor_id' => $actor->id,
                'action' => 'lms.submission.grade.'.$status,
                'auditable_type' => LmsAssignmentSubmission::class,
                'auditable_id' => $submission->id,
                'old_values' => $before,
                'new_values' => [...$submission->only(['status', 'grade_status', 'grade_version', 'published_revision', 'score', 'score_percent', 'is_passed']), 'reason' => $data['reason']],
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 200, ''),
            ]);

            if ($publish && Schema::hasTable('lms_notification_dispatches')) {
                app(LmsNotificationService::class)->notifyAssignmentGradePublished($submission, $nextRevision, $wasAlreadyPublished);
            }

            return $this->submissionDetail($actor, $submission->id);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function returnSubmission(User $actor, int $submissionId, array $input): array
    {
        abort_unless($actor->isLmsManager(), 403);
        $data = Validator::make($input, [
            'request_id' => 'required|uuid',
            'expected_version' => 'required|integer|min:0',
            'feedback' => 'required|string|min:5|max:10000',
            'reason' => 'required|string|min:5|max:2000',
            'resubmit_deadline_at' => 'nullable|date',
        ])->validate();
        $expectedVersion = (int) $data['expected_version'];

        return DB::transaction(function () use ($actor, $submissionId, $data, $expectedVersion) {
            $submission = LmsAssignmentSubmission::with('assignment')->lockForUpdate()->findOrFail($submissionId);
            abort_unless($actor->canManageCourse($submission->assignment?->course_id), 403, 'Anda tidak memiliki akses ke kelas ini.');
            User::whereKey($submission->user_id)->lockForUpdate()->firstOrFail();

            $scopeKey = 'return:'.$submission->id;
            $payloadHash = hash('sha256', json_encode([
                'expected_version' => $expectedVersion,
                'feedback' => $data['feedback'],
                'reason' => $data['reason'],
                'resubmit_deadline_at' => $data['resubmit_deadline_at'] ?? null,
            ], JSON_THROW_ON_ERROR));

            $replay = DB::table('lms_submission_requests')
                ->where('scope_key', $scopeKey)
                ->where('request_id', $data['request_id'])
                ->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $payloadHash, 409, 'Request ID sudah digunakan dengan muatan berbeda.');

                return $this->submissionDetail($actor, $submission->id);
            }

            abort_unless((int) $submission->grade_version === $expectedVersion, 409, 'Status submission telah berubah. Muat ulang review.');

            $deadline = ! empty($data['resubmit_deadline_at']) ? Carbon::parse($data['resubmit_deadline_at']) : null;
            $nextRevision = (int) $submission->grade_version + 1;

            DB::table('lms_submission_grade_revisions')->insert([
                'submission_id' => $submission->id,
                'revision' => $nextRevision,
                'actor_id' => $actor->id,
                'status' => 'returned',
                'score' => null,
                'score_percent' => null,
                'passed' => false,
                'criteria' => null,
                'feedback' => $data['feedback'],
                'reason' => $data['reason'],
                'allow_resubmit' => true,
                'resubmit_deadline_at' => $deadline,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $before = $submission->only(['status', 'grade_status', 'grade_version', 'resubmit_allowed', 'resubmit_deadline_at']);
            $submission->status = 'returned';
            $submission->grade_status = 'returned';
            $submission->grade_version = $nextRevision;
            $submission->returned_at = now();
            $submission->return_feedback = $data['feedback'];
            $submission->resubmit_allowed = true;
            $submission->resubmit_deadline_at = $deadline;
            $submission->save();

            $draft = LmsAssignmentDraft::where('assignment_id', $submission->assignment_id)
                ->where('user_id', $submission->user_id)
                ->lockForUpdate()
                ->first();
            if ($draft) {
                $draft->allow_resubmit = true;
                $draft->resubmit_deadline_at = $deadline;
                $draft->last_return_feedback = $data['feedback'];
                $draft->version = (int) $draft->version + 1;
                $draft->save();
            } else {
                LmsAssignmentDraft::create([
                    'assignment_id' => $submission->assignment_id,
                    'user_id' => $submission->user_id,
                    'text_body' => $submission->text_body ?? '',
                    'upload_ids' => array_values(array_column($submission->files_snapshot ?? [], 'upload_id')),
                    'version' => 1,
                    'allow_resubmit' => true,
                    'resubmit_deadline_at' => $deadline,
                    'last_return_feedback' => $data['feedback'],
                ]);
            }

            DB::table('lms_submission_requests')->insert([
                'scope_key' => $scopeKey,
                'request_id' => $data['request_id'],
                'payload_hash' => $payloadHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLog::create([
                'actor_type' => 'admin',
                'actor_id' => $actor->id,
                'action' => 'lms.submission.returned',
                'auditable_type' => LmsAssignmentSubmission::class,
                'auditable_id' => $submission->id,
                'old_values' => $before,
                'new_values' => [
                    'status' => 'returned',
                    'grade_version' => $nextRevision,
                    'resubmit_allowed' => true,
                    'resubmit_deadline_at' => $deadline?->toIso8601String(),
                    'reason' => $data['reason'],
                ],
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 200, ''),
            ]);

            if (Schema::hasTable('lms_notification_dispatches')) {
                app(LmsNotificationService::class)->notifyAssignmentReturned($submission, $nextRevision);
            }

            return $this->submissionDetail($actor, $submission->id);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function submissionDetail(User $actor, int $submissionId): array
    {
        abort_unless($actor->isLmsManager(), 403);
        $submission = LmsAssignmentSubmission::with(['assignment', 'user:id,name,email'])->findOrFail($submissionId);
        abort_unless($actor->canManageCourse($submission->assignment?->course_id), 403, 'Anda tidak memiliki akses ke kelas ini.');

        $history = DB::table('lms_submission_grade_revisions')
            ->where('submission_id', $submission->id)
            ->orderBy('revision')
            ->get()
            ->map(fn ($r) => [
                'revision' => (int) $r->revision,
                'actor_id' => $r->actor_id ? (int) $r->actor_id : null,
                'status' => $r->status,
                'score' => $r->score !== null ? (float) $r->score : null,
                'score_percent' => $r->score_percent !== null ? (float) $r->score_percent : null,
                'passed' => (bool) $r->passed,
                'criteria' => $r->criteria ? json_decode($r->criteria, true) : null,
                'feedback' => $r->feedback,
                'reason' => $r->reason,
                'allow_resubmit' => (bool) $r->allow_resubmit,
                'resubmit_deadline_at' => $r->resubmit_deadline_at,
                'published_at' => $r->published_at,
                'created_at' => $r->created_at,
            ])
            ->all();

        $files = array_map(fn (array $f) => [
            'upload_id' => $f['upload_id'],
            'original_name' => $f['original_name'],
            'mime_type' => $f['mime_type'],
            'size_bytes' => (int) $f['size_bytes'],
            'sha256' => $f['sha256'],
            'page_count' => $f['page_count'] ?? null,
            'download_url' => '/api/v1/lms/submissions/'.$submission->id.'/files/'.$f['upload_id'],
        ], $submission->files_snapshot ?? []);

        return [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'course_id' => $submission->assignment?->course_id,
            'user_id' => $submission->user_id,
            'user' => $submission->user ? ['id' => $submission->user->id, 'name' => $submission->user->name, 'email' => $submission->user->email] : null,
            'version' => (int) $submission->version,
            'receipt_code' => $submission->receipt_code,
            'text_body' => $submission->text_body,
            'files' => $files,
            'assignment_snapshot' => $submission->assignment_snapshot,
            'status' => $submission->status,
            'grade_status' => $submission->grade_status,
            'grade_version' => (int) $submission->grade_version,
            'published_revision' => $submission->published_revision,
            'score' => $submission->score !== null ? (float) $submission->score : null,
            'score_percent' => $submission->score_percent !== null ? (float) $submission->score_percent : null,
            'is_passed' => (bool) $submission->is_passed,
            'is_late' => (bool) $submission->is_late,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'returned_at' => $submission->returned_at?->toIso8601String(),
            'return_feedback' => $submission->return_feedback,
            'resubmit_allowed' => (bool) $submission->resubmit_allowed,
            'resubmit_deadline_at' => $submission->resubmit_deadline_at?->toIso8601String(),
            'history' => $history,
        ];
    }

    private function assertAssignmentReady(LmsAssignment $assignment): void
    {
        if (trim($assignment->title) === '' || trim($assignment->instructions) === '') {
            throw ValidationException::withMessages(['instructions' => 'Judul dan instruksi tugas wajib diisi sebelum terbit.']);
        }
        if ((float) $assignment->max_score <= 0) {
            throw ValidationException::withMessages(['max_score' => 'Nilai maksimum tugas harus lebih dari 0.']);
        }
    }

    private function resolveStudentAssignment(User $student, int $assignmentId): LmsAssignment
    {
        $assignment = LmsAssignment::with(['course', 'lesson'])->findOrFail($assignmentId);
        abort_unless(
            in_array($assignment->status, ['published', 'closed'], true)
            && $assignment->course?->status === 'published'
            && ! $assignment->course?->isArchived()
            && $assignment->lesson?->status === 'published',
            404
        );
        $access = app(EntitlementService::class)->courseAccess($student, $assignment->course);
        abort_unless((bool) ($access['has_access'] ?? false), 403, 'Kelas belum terbuka untuk akun Anda.');

        $curriculum = app(LmsProgressService::class)->curriculum($student, $assignment->course);
        $asgUnit = $curriculum['assignments'][$assignment->id] ?? null;
        if (is_array($asgUnit) && ! ($asgUnit['unlocked'] ?? false)) {
            abort(403, 'Selesaikan aktivitas wajib sebelumnya terlebih dahulu.');
        }

        return $assignment;
    }

    private function resolveStudentState(?LmsAssignmentSubmission $latest, ?LmsAssignmentDraft $draft): string
    {
        if ($latest === null) {
            return $draft ? 'draft' : 'not_started';
        }
        if ($latest->status === 'returned') {
            return 'returned';
        }
        if ($latest->grade_status === 'published') {
            return 'graded';
        }

        return 'submitted';
    }

    /**
     * @return array<string, mixed>
     */
    private function formatStudentSubmission(LmsAssignmentSubmission $submission): array
    {
        $publishedFeedback = null;
        if ($submission->grade_status === 'published' && $submission->published_revision) {
            $rev = DB::table('lms_submission_grade_revisions')
                ->where('submission_id', $submission->id)
                ->where('revision', $submission->published_revision)
                ->where('status', 'published')
                ->first();
            $publishedFeedback = $rev?->feedback;
        }

        $files = array_map(fn (array $f) => [
            'upload_id' => $f['upload_id'],
            'original_name' => $f['original_name'],
            'mime_type' => $f['mime_type'],
            'size_bytes' => (int) $f['size_bytes'],
            'sha256' => $f['sha256'],
            'page_count' => $f['page_count'] ?? null,
            'download_url' => '/api/v1/lms/submissions/'.$submission->id.'/files/'.$f['upload_id'],
        ], $submission->files_snapshot ?? []);

        return [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'version' => (int) $submission->version,
            'receipt_code' => $submission->receipt_code,
            'text_body' => $submission->text_body,
            'files' => $files,
            'status' => $submission->status,
            'grade_status' => $submission->grade_status,
            'score' => $submission->grade_status === 'published' && $submission->score !== null ? (float) $submission->score : null,
            'score_percent' => $submission->grade_status === 'published' && $submission->score_percent !== null ? (float) $submission->score_percent : null,
            'is_passed' => $submission->grade_status === 'published' ? (bool) $submission->is_passed : false,
            'feedback' => $submission->status === 'returned' ? $submission->return_feedback : $publishedFeedback,
            'is_late' => (bool) $submission->is_late,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'submitted_at_wib' => $submission->submitted_at ? $submission->submitted_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : null,
            'returned_at' => $submission->returned_at?->toIso8601String(),
            'resubmit_allowed' => (bool) $submission->resubmit_allowed,
            'resubmit_deadline_at' => $submission->resubmit_deadline_at?->toIso8601String(),
        ];
    }
}
