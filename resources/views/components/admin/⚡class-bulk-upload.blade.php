<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Support\Str;

new class extends Component
{
    use WithFileUploads;

    public $courses;
    public $selectedCourseId;
    public $csvFile;
    public $uploadSuccessCount = 0;
    public $errorMessage = '';
    public $recentVideos;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Create demo courses if empty
        if (Course::count() === 0) {
            Course::create(['title' => 'Kelas Teknisi Android Level 1', 'slug' => 'teknisi-android-1', 'description' => 'Materi perbaikan hardware & skema Android', 'status' => 'published']);
            Course::create(['title' => 'Kelas Teknisi iPhone Hardware Solution', 'slug' => 'teknisi-iphone-hs', 'description' => 'Jalur VDD, jumper IC, & reballing iPhone', 'status' => 'published']);
        }

        $this->courses = Course::orderBy('title')->get();
        if ($this->courses->isNotEmpty() && !$this->selectedCourseId) {
            $this->selectedCourseId = $this->courses->first()->id;
        }

        $this->recentVideos = Video::with('course')->latest()->take(10)->get();
    }

    public function downloadTemplate()
    {
        $csvContent = "Judul Materi,Deskripsi,Link YouTube\n";
        $csvContent .= "Pengenalan Tegangan VBAT & VPH PWR,Memahami jalur utama sumber daya pada motherboard ponsel,https://www.youtube.com/watch?v=dQw4w9WgXcQ\n";
        $csvContent .= "Teknik Jumper Jalur Putus Jalur Sinyal,Praktik mikrosolder kawat 0.01mm pada PCB dua sisi,https://www.youtube.com/watch?v=dQw4w9WgXcQ\n";
        $csvContent .= "Analisis Konsumsi Arus Power Supply DC,Membaca amperemeter saat ponsel short sebelum dan sesudah tombol power,https://www.youtube.com/watch?v=dQw4w9WgXcQ\n";

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'template_bulk_upload_materi_vbat.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function uploadBulk()
    {
        $this->validate([
            'selectedCourseId' => 'required|exists:courses,id',
            'csvFile' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $this->errorMessage = '';
        $this->uploadSuccessCount = 0;

        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');

        if (!$file) {
            $this->errorMessage = 'Gagal membaca file yang diunggah.';
            return;
        }

        // Header
        $header = fgetcsv($file);
        $imported = 0;

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) >= 3) {
                $title = trim($row[0]);
                $description = trim($row[1]);
                $youtubeUrl = trim($row[2]);

                if (!empty($title) && !empty($youtubeUrl)) {
                    Video::create([
                        'course_id' => $this->selectedCourseId,
                        'title' => $title,
                        'description' => $description,
                        'source_type' => 'youtube',
                        'source_path' => $youtubeUrl,
                        'duration_seconds' => 600, // default 10 mnt
                    ]);
                    $imported++;
                }
            }
        }
        fclose($file);

        $this->uploadSuccessCount = $imported;
        $this->reset('csvFile');
        $this->loadData();
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Bulk Upload Materi Kelas Video (Excel / CSV)
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Unggah puluhan materi video kelas secara massal menggunakan file format Excel/CSV berisi Judul, Deskripsi, dan Link YouTube.
            </p>
        </div>
        <button wire:click="downloadTemplate" class="px-4 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Unduh Format Template Excel (.CSV)
        </button>
    </div>

    <!-- Upload Card -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-5">
        <h2 class="text-base font-bold text-zinc-900 dark:text-white">Form Impor Materi Massal</h2>

        @if ($uploadSuccessCount > 0)
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>Berhasil mengimpor <strong>{{ $uploadSuccessCount }} materi video</strong> ke kelas yang dipilih!</span>
            </div>
        @endif

        @if ($errorMessage)
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">1. Pilih Kelas Target *</label>
                <select wire:model="selectedCourseId" class="w-full px-4 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white font-medium">
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}">{{ $c->title }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-zinc-400 mt-1">Seluruh video dari file akan otomatis dihubungkan ke kelas ini.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">2. Pilih File Template (.CSV / Excel) *</label>
                <input type="file" wire:model="csvFile" accept=".csv,.txt" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <div wire:loading wire:target="csvFile" class="text-xs text-blue-600 mt-1">Mengunggah file...</div>
            </div>
        </div>

        <div class="pt-2 flex justify-end">
            <button wire:click="uploadBulk" wire:loading.attr="disabled" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-sm flex items-center gap-2 transition disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Mulai Proses Import Video
            </button>
        </div>
    </div>

    <!-- Riwayat Video Terakhir -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Daftar Materi Video Terbaru</h3>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($recentVideos as $vid)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-zinc-900 dark:text-white">{{ $vid->title }}</div>
                        <div class="text-xs text-zinc-400 mt-0.5">{{ $vid->course?->title }} • {{ $vid->source_type }}</div>
                    </div>
                    <a href="{{ $vid->source_path }}" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center gap-1 font-medium">
                        Buka Video ↗
                    </a>
                </div>
            @empty
                <p class="text-xs text-zinc-500 py-4 text-center">Belum ada video materi yang diunggah.</p>
            @endforelse
        </div>
    </div>
</div>