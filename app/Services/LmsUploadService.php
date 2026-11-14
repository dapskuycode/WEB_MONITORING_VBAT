<?php

namespace App\Services;

use App\Models\LmsUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LmsUploadService
{
    public function receive(User $user, UploadedFile $file, string $purpose): LmsUpload
    {
        if ($purpose === 'course_material') {
            abort_unless($user->isLmsManager(), 403);
            Validator::make(
                ['file' => $file, 'purpose' => $purpose],
                ['file' => 'required|file|mimes:pdf|max:'.config('lms.max_pdf_kb'), 'purpose' => 'required|in:course_material']
            )->validate();
            $prefix = file_get_contents($file->getRealPath(), false, null, 0, 5);
            abort_unless($file->getMimeType() === 'application/pdf' && is_string($prefix) && str_starts_with($prefix, '%PDF-'), 422, 'Isi berkas harus PDF.');
            $mime = 'application/pdf';
            $ext = 'pdf';
        } else {
            if ($user->isLmsManager() || $user->isSponsor()) {
                Validator::make(['purpose' => $purpose], ['purpose' => 'required|in:course_material'])->validate();
            }
            Validator::make(
                ['file' => $file, 'purpose' => $purpose],
                ['file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:'.config('lms.max_assignment_file_kb', 20480), 'purpose' => 'required|in:assignment_submission']
            )->validate();
            $head = file_get_contents($file->getRealPath(), false, null, 0, 8);
            abort_unless(is_string($head), 422, 'Berkas tidak dapat dibaca.');
            $detectedMime = $file->getMimeType();
            if ($detectedMime === 'application/pdf' && str_starts_with($head, '%PDF-')) {
                $mime = 'application/pdf';
                $ext = 'pdf';
            } elseif ($detectedMime === 'image/jpeg' && str_starts_with($head, "\xFF\xD8\xFF")) {
                $mime = 'image/jpeg';
                $ext = 'jpg';
            } elseif ($detectedMime === 'image/png' && str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
                $mime = 'image/png';
                $ext = 'png';
            } else {
                abort(422, 'Format berkas tugas harus PDF, JPG, atau PNG yang valid.');
            }
        }

        $id = (string) Str::uuid();
        $path = $file->storeAs('quarantine', $id.'.'.$ext, 'lms');
        abort_unless(is_string($path), 503, 'Berkas belum dapat disimpan.');
        try {
            return LmsUpload::create([
                'id' => $id,
                'user_id' => $user->id,
                'purpose' => $purpose,
                'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 200, ''),
                'mime_type' => $mime,
                'size_bytes' => $file->getSize(),
                'sha256' => hash_file('sha256', $file->getRealPath()),
                'path' => $path,
                'status' => 'quarantine',
                'reason' => 'awaiting_scan',
            ]);
        } catch (\Throwable $e) {
            Storage::disk('lms')->delete($path);
            throw $e;
        }
    }

    public function scan(string $id): LmsUpload
    {
        return DB::transaction(function () use ($id) {
            $upload = LmsUpload::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($upload->status === 'ready' || $upload->status === 'rejected') {
                return $upload;
            }
            $upload->update(['status' => 'scanning', 'reason' => null]);
            $path = app(LmsFileService::class)->path('lms', $upload->path);
            if (! $path || hash_file('sha256', $path) !== $upload->sha256) {
                $upload->update(['status' => 'rejected', 'reason' => 'file_integrity_failed']);

                return $upload;
            }
            try {
                if ($upload->mime_type === 'application/pdf') {
                    $pages = app(LmsDocumentInspection::class)->inspect($path);
                    $ext = 'pdf';
                } else {
                    app(LmsDocumentInspection::class)->inspectImage($path);
                    $pages = null;
                    $ext = $upload->mime_type === 'image/png' ? 'png' : 'jpg';
                }
                $folder = $upload->purpose === 'assignment_submission' ? 'assignments/submissions' : 'learning/pdfs';
                $target = $folder.'/'.$upload->id.'.'.$ext;
                if (! Storage::disk('lms')->move($upload->path, $target)) {
                    throw new \RuntimeException('storage_unavailable');
                }
                $upload->update(['status' => 'ready', 'reason' => null, 'path' => $target, 'page_count' => $pages, 'scanned_at' => now()]);
            } catch (\DomainException $e) {
                $upload->update(['status' => 'rejected', 'reason' => $e->getMessage()]);
            } catch (\Throwable $e) {
                $upload->update(['status' => 'quarantine', 'reason' => 'scanner_or_parser_unavailable']);
            }

            return $upload;
        });
    }

    public function ready(string $id, ?User $owner = null): LmsUpload
    {
        $upload = LmsUpload::whereKey($id)->firstOrFail();
        abort_if($owner && $upload->user_id !== $owner->id, 403);
        abort_unless($upload->purpose === 'course_material' && $upload->status === 'ready' && $upload->page_count > 0, 422, 'Berkas belum siap atau ditolak.');
        $path = app(LmsFileService::class)->path('lms', $upload->path);
        abort_unless($path && hash_file('sha256', $path) === $upload->sha256 && ! Storage::disk('public')->exists($upload->path), 422, 'Integritas/privasi berkas tidak valid.');

        return $upload;
    }

    /**
     * @param  list<string>  $allowedMimes
     */
    public function readyForSubmission(string $id, User $owner, array $allowedMimes, int $maxBytes): LmsUpload
    {
        $upload = LmsUpload::whereKey($id)->firstOrFail();
        abort_if((int) $upload->user_id !== (int) $owner->id, 403, 'Lampiran bukan milik akun Anda.');
        abort_unless($upload->purpose === 'assignment_submission' && $upload->status === 'ready', 422, 'Lampiran belum siap atau ditolak oleh pemindai.');
        abort_unless(in_array($upload->mime_type, $allowedMimes, true), 422, 'Tipe berkas lampiran tidak diizinkan untuk tugas ini.');
        abort_unless((int) $upload->size_bytes <= $maxBytes, 422, 'Ukuran berkas lampiran melebihi batas tugas.');
        $path = app(LmsFileService::class)->path('lms', $upload->path);
        abort_unless($path && hash_file('sha256', $path) === $upload->sha256 && ! Storage::disk('public')->exists($upload->path), 422, 'Integritas atau privasi berkas lampiran tidak valid.');

        return $upload;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(LmsUpload $upload): array
    {
        return $upload->only(['id', 'purpose', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'status', 'reason', 'page_count']);
    }
}
