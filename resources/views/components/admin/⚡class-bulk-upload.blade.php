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
    <!-- Quick Action: Download Template -->
    <div class="flex justify-end">
        <button wire:click="downloadTemplate" class="px-4 py-2.5 border-2 border-[#1a73e8] text-[#1a73e8] hover:bg-[#e8f1fd] rounded-lg text-sm font-semibold flex items-center gap-2 transition">
            <i class="fas fa-download"></i>
            Unduh Format Template Excel (.CSV)
        </button>
    </div>

    <!-- Upload Form Card -->
    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
        <h2 class="text-base font-semibold text-[#212121]">Form Impor Materi Massal</h2>

        @if ($uploadSuccessCount > 0)
            <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span>Berhasil mengimpor <strong>{{ $uploadSuccessCount }} materi video</strong> ke kelas yang dipilih!</span>
            </div>
        @endif

        @if ($errorMessage)
            <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-rose-600"></i>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column: Pilih Kelas -->
            <div>
                <label class="block text-sm font-semibold text-[#212121] mb-2">Pilih Kelas Target</label>
                <select wire:model="selectedCourseId" class="w-full px-4 py-3 text-sm rounded-lg border border-[#e2e8f0] bg-white text-[#212121] focus:border-[#1a73e8] focus:ring-2 focus:ring-[#1a73e8]/20 outline-none transition">
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}">{{ $c->title }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-[#828282] mt-2">Seluruh video dari file akan otomatis dihubungkan ke kelas ini.</p>
            </div>

            <!-- Right Column: Drag & Drop Zone -->
            <div>
                <label class="block text-sm font-semibold text-[#212121] mb-2">Pilih File Template (.CSV / Excel)</label>
                <div class="relative">
                    <label for="csvFileInput" class="block w-full cursor-pointer">
                        <div class="border-2 border-dashed border-[#1a73e8] bg-[#f4f6f8] rounded-lg p-8 text-center hover:bg-[#e8f1fd] transition">
                            <i class="fas fa-cloud-upload-alt text-3xl text-[#1a73e8] mb-3"></i>
                            <p class="text-sm font-medium text-[#212121]">Klik atau seret file template ke sini</p>
                            <p class="text-xs text-[#828282] mt-1">Format: CSV, maksimal 5MB</p>
                        </div>
                    </label>
                    <input 
                        id="csvFileInput" 
                        type="file" 
                        wire:model="csvFile" 
                        accept=".csv,.txt" 
                        class="hidden"
                    >
                </div>
                <div wire:loading wire:target="csvFile" class="text-xs text-[#1a73e8] mt-2 flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i>
                    Mengunggah file...
                </div>
                @if ($csvFile)
                    <div class="text-xs text-emerald-600 mt-2 flex items-center gap-2">
                        <i class="fas fa-file-csv"></i>
                        File dipilih: {{ $csvFile->getClientOriginalName() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-2 flex justify-end">
            <button wire:click="uploadBulk" wire:loading.attr="disabled" class="px-6 py-3 bg-[#1a73e8] hover:bg-[#1557b0] text-white rounded-lg text-sm font-semibold shadow-sm flex items-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-upload"></i>
                Mulai Proses Import Video
            </button>
        </div>
    </div>

    <!-- Recent Uploads Table -->
    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-base font-semibold text-[#212121]">Riwayat Upload Terbaru</h3>
        
        @if ($recentVideos->isEmpty())
            <div class="py-12 text-center">
                <div class="w-14 h-14 bg-[#f4f6f8] rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-video text-[#a0aec0] text-xl"></i>
                </div>
                <p class="text-sm font-medium text-[#212121] mb-1">Belum ada video materi yang diunggah</p>
                <p class="text-xs text-[#828282]">Video yang berhasil diimpor akan muncul di sini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#f4f6f8] border-b border-[#e2e8f0]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#212121] uppercase tracking-wider">Judul Video</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#212121] uppercase tracking-wider">Kelas Target</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#212121] uppercase tracking-wider">Status Import</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#212121] uppercase tracking-wider">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e2e8f0]">
                        @foreach ($recentVideos as $vid)
                            <tr class="hover:bg-[#f4f6f8] transition">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-youtube text-red-500"></i>
                                        <a href="{{ $vid->source_path }}" target="_blank" class="font-medium text-[#212121] hover:text-[#1a73e8] transition">
                                            {{ $vid->title }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[#828282]">
                                    {{ $vid->course?->title ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check-circle text-[10px]"></i>
                                        Sukses
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-[#828282]">
                                    {{ $vid->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>