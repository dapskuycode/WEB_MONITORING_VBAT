<?php

namespace App\Services;

use Livewire\Component;

/**
 * Toast Service for VBAT Ponsel
 * 
 * Provides unified toast notifications using Flux UI toast system.
 * Supports: success, error, warning, info variants with semantic colors.
 * 
 * Usage from Livewire component:
 *   ToastService::success($this, 'Berhasil Disimpan', 'Data mitra sponsor baru telah ditambahkan.');
 *   ToastService::error($this, 'Gagal Mengunggah', 'Ukuran file banner melebihi batas maksimal 2MB.');
 * 
 * Usage from Controller (session flash):
 *   ToastService::flashSuccess('Berhasil Disimpan', 'Data telah disimpan.');
 */
class ToastService
{
    const VARIANT_SUCCESS = 'success';
    const VARIANT_ERROR = 'error';
    const VARIANT_WARNING = 'warning';
    const VARIANT_INFO = 'info';

    // ========================================
    // Livewire Component Methods (dispatch)
    // ========================================

    /**
     * Dispatch a toast via Livewire dispatch
     */
    public static function notify(
        Component $component,
        string $variant,
        string $heading,
        ?string $text = null,
        ?int $duration = null
    ): void {
        $component->dispatch('toast-show',
            duration: $duration ?? self::getDefaultDuration($variant),
            slots: [
                'heading' => $heading,
                'text' => $text,
            ],
            dataset: [
                'variant' => $variant,
            ]
        );
    }

    public static function success(Component $component, string $heading, ?string $text = null): void
    {
        self::notify($component, self::VARIANT_SUCCESS, $heading, $text);
    }

    public static function error(Component $component, string $heading, ?string $text = null): void
    {
        self::notify($component, self::VARIANT_ERROR, $heading, $text, 8000);
    }

    public static function warning(Component $component, string $heading, ?string $text = null): void
    {
        self::notify($component, self::VARIANT_WARNING, $heading, $text, 6000);
    }

    public static function info(Component $component, string $heading, ?string $text = null): void
    {
        self::notify($component, self::VARIANT_INFO, $heading, $text);
    }

    // ========================================
    // Session Flash Methods (for Controllers)
    // ========================================

    public static function flash(string $variant, string $heading, ?string $text = null, ?int $duration = null): void
    {
        $toasts = session()->get('toasts', []);
        
        $toasts[] = [
            'variant' => $variant,
            'heading' => $heading,
            'text' => $text,
            'duration' => $duration ?? self::getDefaultDuration($variant),
        ];

        session()->flash('toasts', $toasts);
    }

    public static function flashSuccess(string $heading, ?string $text = null): void
    {
        self::flash(self::VARIANT_SUCCESS, $heading, $text);
    }

    public static function flashError(string $heading, ?string $text = null): void
    {
        self::flash(self::VARIANT_ERROR, $heading, $text, 8000);
    }

    public static function flashWarning(string $heading, ?string $text = null): void
    {
        self::flash(self::VARIANT_WARNING, $heading, $text, 6000);
    }

    public static function flashInfo(string $heading, ?string $text = null): void
    {
        self::flash(self::VARIANT_INFO, $heading, $text);
    }

    // ========================================
    // Common Pre-defined Messages (Livewire)
    // ========================================

    public static function saved(Component $component, string $item = 'Data'): void
    {
        self::success($component, 'Berhasil Disimpan', "{$item} telah berhasil disimpan.");
    }

    public static function updated(Component $component, string $item = 'Data'): void
    {
        self::success($component, 'Berhasil Diperbarui', "{$item} telah berhasil diperbarui.");
    }

    public static function deleted(Component $component, string $item = 'Data'): void
    {
        self::success($component, 'Berhasil Dihapus', "{$item} telah berhasil dihapus.");
    }

    public static function created(Component $component, string $item = 'Data'): void
    {
        self::success($component, 'Berhasil Dibuat', "{$item} baru telah berhasil ditambahkan.");
    }

    public static function uploadFailed(Component $component, string $reason = 'terjadi kesalahan pada sistem'): void
    {
        self::error($component, 'Gagal Mengunggah', "File tidak dapat diunggah karena {$reason}.");
    }

    public static function uploadTooLarge(Component $component, int $maxMB = 2): void
    {
        self::error($component, 'Gagal Mengunggah', "Ukuran file melebihi batas maksimal {$maxMB}MB.");
    }

    public static function uploadInvalidType(Component $component, string $allowed = 'JPG, PNG, PDF'): void
    {
        self::error($component, 'Format Tidak Didukung', "Format file tidak valid. Gunakan: {$allowed}.");
    }

    public static function saveFailed(Component $component, string $reason = 'terjadi kesalahan pada sistem'): void
    {
        self::error($component, 'Gagal Menyimpan', "Data tidak dapat disimpan karena {$reason}.");
    }

    public static function deleteFailed(Component $component, string $reason = 'data masih digunakan'): void
    {
        self::error($component, 'Gagal Menghapus', "Data tidak dapat dihapus karena {$reason}.");
    }

    public static function notFound(Component $component, string $item = 'Data'): void
    {
        self::error($component, 'Tidak Ditemukan', "{$item} yang Anda cari tidak ditemukan.");
    }

    public static function unauthorized(Component $component): void
    {
        self::warning($component, 'Akses Ditolak', 'Anda tidak memiliki izin untuk melakukan aksi ini.');
    }

    public static function connectionError(Component $component): void
    {
        self::warning($component, 'Koneksi Terputus', 'Gagal memuat data dari server. Periksa koneksi internet Anda.');
    }

    public static function pendingApproval(Component $component, int $count = 1, string $item = 'pengajuan'): void
    {
        self::info($component, 'Menunggu Approval', "Ada {$count} {$item} baru yang memerlukan persetujuan.");
    }

    public static function validationError(Component $component, string $field = 'Input'): void
    {
        self::error($component, 'Validasi Gagal', "{$field} tidak valid. Periksa kembali data yang dimasukkan.");
    }

    public static function approved(Component $component, string $item = 'Data'): void
    {
        self::success($component, 'Berhasil Disetujui', "{$item} telah berhasil disetujui.");
    }

    public static function rejected(Component $component, string $item = 'Data'): void
    {
        self::success($component, 'Berhasil Ditolak', "{$item} telah berhasil ditolak.");
    }

    public static function copied(Component $component): void
    {
        self::success($component, 'Berhasil Disalin', 'Teks telah disalin ke clipboard.');
    }

    public static function noChanges(Component $component): void
    {
        self::info($component, 'Tidak Ada Perubahan', 'Tidak ada data yang diubah.');
    }

    public static function importComplete(Component $component, int $created = 0, int $updated = 0, int $skipped = 0): void
    {
        $details = [];
        if ($created > 0) $details[] = "{$created} ditambahkan";
        if ($updated > 0) $details[] = "{$updated} diperbarui";
        if ($skipped > 0) $details[] = "{$skipped} dilewati";
        
        $message = implode(', ', $details) . '.';
        self::success($component, 'Import Selesai', $message);
    }

    public static function importFailed(Component $component, string $reason = 'format file tidak valid'): void
    {
        self::error($component, 'Import Gagal', "Data tidak dapat diimpor karena {$reason}.");
    }

    // ========================================
    // Helper Methods
    // ========================================

    private static function getDefaultDuration(string $variant): int
    {
        return match ($variant) {
            self::VARIANT_ERROR => 8000,
            self::VARIANT_WARNING => 6000,
            default => 5000,
        };
    }
}
