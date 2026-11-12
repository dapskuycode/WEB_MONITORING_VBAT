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
        abort_unless($user->isSuperAdmin(), 403);
        Validator::make(['file' => $file, 'purpose' => $purpose], ['file' => 'required|file|mimes:pdf|max:'.config('lms.max_pdf_kb'), 'purpose' => 'required|in:course_material'])->validate();
        $prefix = file_get_contents($file->getRealPath(), false, null, 0, 5);
        abort_unless($file->getMimeType() === 'application/pdf' && is_string($prefix) && str_starts_with($prefix, '%PDF-'), 422, 'Isi berkas harus PDF.');
        $id = (string) Str::uuid();
        $path = $file->storeAs('quarantine', $id.'.pdf', 'lms');
        abort_unless(is_string($path), 503, 'Berkas belum dapat disimpan.');
        try {
            return LmsUpload::create(['id' => $id, 'user_id' => $user->id, 'purpose' => $purpose,
                'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 200, ''),
                'mime_type' => 'application/pdf', 'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()),
                'path' => $path, 'status' => 'quarantine', 'reason' => 'awaiting_scan']);
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
                $pages = app(LmsDocumentInspection::class)->inspect($path);
                $target = 'learning/pdfs/'.$upload->id.'.pdf';
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
     * @return array<string, mixed>
     */
    public function data(LmsUpload $upload): array
    {
        return $upload->only(['id', 'purpose', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'status', 'reason', 'page_count']);
    }
}
