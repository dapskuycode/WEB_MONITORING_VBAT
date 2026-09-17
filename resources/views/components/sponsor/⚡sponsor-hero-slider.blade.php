<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Campaign;
use App\Models\Sponsor;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithFileUploads;

    public $campaigns;
    public $sponsor;
    public $showModal = false;

    public $title = '';
    public $media_type = 'image';
    public $mediaFile;
    public $thumbnailFile;
    public $target_url = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $successMessage = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->sponsor = $user?->sponsor ?? Sponsor::first();

        if ($this->sponsor) {
            $this->campaigns = Campaign::where('sponsor_id', $this->sponsor->id)
                ->where('placement_type', 'hero_slider')
                ->latest()
                ->get();
        } else {
            $this->campaigns = collect();
        }
    }

    public function openCreateModal()
    {
        $this->reset(['title', 'target_url', 'description', 'mediaFile', 'thumbnailFile', 'successMessage']);
        $this->media_type = 'image';
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addMonths(1)->format('Y-m-d');
        $this->showModal = true;
    }

    public function submit()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'target_url' => 'required|url',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'mediaFile' => 'required|file|max:20480',
            'thumbnailFile' => 'nullable|image|max:5120',
        ], [
            'mediaFile.required' => 'Wajib memilih file visual banner (gambar atau video)!',
            'mediaFile.max' => 'Ukuran file visual maksimal 20 MB!',
            'thumbnailFile.max' => 'Ukuran thumbnail maksimal 5 MB!',
        ]);

        $mediaPath = 'assets/images/banner_promo_diskon.png';
        if ($this->mediaFile) {
            $mediaPath = $this->mediaFile->store('campaigns/hero', 'public');
            $ext = strtolower($this->mediaFile->getClientOriginalExtension());
            if (in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'avi']) || $this->media_type === 'video') {
                $this->media_type = 'video';
            } else {
                $this->media_type = 'image';
            }
        }

        $thumbnailPath = null;
        if ($this->thumbnailFile) {
            $thumbnailPath = $this->thumbnailFile->store('campaigns/thumbs', 'public');
        } elseif ($this->media_type === 'video') {
            $thumbnailPath = \App\Services\VideoThumbnailService::generateThumbnail($mediaPath);
        }

        Campaign::create([
            'sponsor_id' => $this->sponsor->id,
            'title' => $this->title,
            'placement_type' => 'hero_slider',
            'media_path' => $mediaPath,
            'media_type' => $this->media_type,
            'thumbnail_path' => $thumbnailPath,
            'target_url' => $this->target_url,
            'description' => $this->description,
            'daily_limit' => 3000,
            'weight' => $this->sponsor->weight ?: 3,
            'status' => 'pending',
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);

        $this->successMessage = 'Pengajuan banner Hero Slide berhasil dikirim! Menunggu moderasi Super Admin.';
        $this->showModal = false;
        $this->loadData();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Pengajuan Banner Hero Slide</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-blue-900/60 text-blue-300 border border-blue-700/50">
                    Posisi Atas Beranda (16:9)
                </span>
            </div>
            <p class="text-xs text-zinc-400 mt-1">
                Slider utama paling pertama yang dilihat seluruh pengguna saat membuka aplikasi ponsel VBAT.
            </p>
        </div>

        <button type="button" wire:click="openCreateModal" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
            <flux:icon name="plus" class="w-4 h-4" />
            <span>Ajukan Hero Slide Baru</span>
        </button>
    </div>

    <!-- Alert Success -->
    @if($successMessage)
    <div class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-800/80 text-emerald-300 text-sm flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-400" />
            <span>{{ $successMessage }}</span>
        </div>
        <button type="button" wire:click="$set('successMessage', '')" class="text-xs font-bold text-emerald-400 hover:underline">Tutup</button>
    </div>
    @endif

    <!-- Panduan Spesifikasi Hero Slide (Dark Styled, No Harsh White) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-2xl bg-zinc-900/90 border border-zinc-800 text-xs">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-blue-950/60 text-blue-400 border border-blue-900/50 shrink-0">
                <flux:icon name="photo" class="w-4 h-4" />
            </div>
            <div>
                <span class="font-bold text-white block">Rasio Landscape 16:9</span>
                <span class="text-zinc-400">Rekomendasi resolusi 1080 × 608 px (format JPG, PNG, WEBP).</span>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-indigo-950/60 text-indigo-400 border border-indigo-900/50 shrink-0">
                <flux:icon name="video-camera" class="w-4 h-4" />
            </div>
            <div>
                <span class="font-bold text-white block">Dukungan Video MP4</span>
                <span class="text-zinc-400">Video promo looping tanpa suara dengan batas maksimal 20 MB.</span>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-emerald-950/60 text-emerald-400 border border-emerald-900/50 shrink-0">
                <flux:icon name="cursor-arrow-rays" class="w-4 h-4" />
            </div>
            <div>
                <span class="font-bold text-white block">Direct URL / Marketplace</span>
                <span class="text-zinc-400">Dukungan klik langsung membuka link Tokopedia, Shopee, atau web sponsor.</span>
            </div>
        </div>
    </div>

    <!-- Tabel Pengajuan Hero Slide -->
    <div class="bg-zinc-900/90 rounded-2xl border border-zinc-800 p-5 shadow-sm">
        <h3 class="font-bold text-base text-white mb-4">Riwayat Pengajuan Hero Slide</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-800 text-xs font-semibold text-zinc-400 uppercase bg-zinc-950/40">
                        <th class="py-3 px-3" style="width: 120px;">Visual Media</th>
                        <th class="py-3 px-3">Judul Kampanye</th>
                        <th class="py-3 px-3">Periode Tayang</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-3 text-right">Tayang / Klik</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/80">
                    @forelse($campaigns as $camp)
                    <tr class="hover:bg-zinc-800/50 transition">
                        <td class="py-3 px-3" style="width: 120px; min-width: 120px; max-width: 120px;">
                            <div class="rounded-xl bg-zinc-950 overflow-hidden relative border border-zinc-800 flex items-center justify-center shadow-xs group" style="width: 104px; height: 58px; min-width: 104px;">
                                <img src="{{ $camp->thumbnail_url }}" style="width: 104px; height: 58px; object-fit: cover;" alt="{{ $camp->title }}" onerror="this.src='{{ asset('assets/images/banner_promo_diskon.png') }}'" />
                                @if($camp->media_type === 'video')
                                    <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 flex items-center justify-center text-white transition">
                                        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md">
                                            <flux:icon name="play" class="w-3.5 h-3.5 text-white fill-white ml-0.5" />
                                        </div>
                                    </div>
                                    <span class="absolute bottom-1 right-1 px-1 py-0.2 bg-black/80 rounded text-[9px] font-mono text-zinc-300">MP4</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-semibold text-white">{{ $camp->title }}</div>
                            <div class="text-xs text-blue-400 truncate max-w-xs mt-0.5">
                                <a href="{{ $camp->target_url }}" target="_blank" class="hover:underline flex items-center gap-1">
                                    <span>{{ $camp->target_url }}</span>
                                    <flux:icon name="arrow-top-right-on-square" class="w-3 h-3" />
                                </a>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-xs text-zinc-400 font-mono">
                            {{ $camp->start_date ? $camp->start_date->format('d M Y') : '-' }} s/d <br>
                            {{ $camp->end_date ? $camp->end_date->format('d M Y') : '-' }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($camp->status === 'approved')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/60 text-emerald-400 border border-emerald-800/50">Disetujui</span>
                            @elseif($camp->status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950/60 text-amber-400 border border-amber-800/50">Menunggu</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950/60 text-rose-400 border border-rose-800/50">Ditolak</span>
                                @if($camp->rejection_reason)
                                    <p class="text-[10px] text-rose-400 mt-1 max-w-[150px] mx-auto truncate" title="{{ $camp->rejection_reason }}">{{ $camp->rejection_reason }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="py-3 px-3 text-right font-mono text-xs">
                            <span class="text-white font-bold">{{ number_format($camp->views_count) }}</span> <span class="text-zinc-400">views</span><br>
                            <span class="text-emerald-400 font-bold">{{ number_format($camp->clicks_count) }}</span> <span class="text-zinc-400">clicks</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-zinc-400 text-sm">
                            Belum ada pengajuan banner Hero Slide. Klik "Ajukan Hero Slide Baru" di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Ajukan Hero Slide -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in">
        <div class="bg-zinc-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-zinc-800 space-y-4 max-h-[90vh] overflow-y-auto text-zinc-200">
            <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                <h3 class="font-bold text-lg text-white">Formulir Pengajuan Hero Slide</h3>
                <button type="button" wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-white">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit.prevent="submit" class="space-y-4">
                <flux:input label="Judul Kampanye Hero" wire:model="title" placeholder="Contoh: Diskon Akbar Sparepart Original 2026" required />

                <flux:input label="Target Link URL / Marketplace" wire:model="target_url" placeholder="https://tokopedia.com/toko-sponsor" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="date" label="Tanggal Mulai Tayang" wire:model="start_date" required />
                    <flux:input type="date" label="Tanggal Selesai Tayang" wire:model="end_date" required />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 mb-1">Tipe Media Visual</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="$set('media_type', 'image')" class="p-2.5 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 {{ $media_type === 'image' ? 'border-blue-500 bg-blue-950/50 text-blue-300' : 'border-zinc-700 bg-zinc-800 text-zinc-400' }}">
                            <flux:icon name="photo" class="w-4 h-4" /> Gambar Landscape (16:9)
                        </button>
                        <button type="button" wire:click="$set('media_type', 'video')" class="p-2.5 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 {{ $media_type === 'video' ? 'border-blue-500 bg-blue-950/50 text-blue-300' : 'border-zinc-700 bg-zinc-800 text-zinc-400' }}">
                            <flux:icon name="video-camera" class="w-4 h-4" /> Video MP4 (Maks 20MB)
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 mb-1">Upload File Banner Hero (Maks 20 MB)</label>
                    <input type="file" wire:model="mediaFile" class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700" />
                    @error('mediaFile') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror

                    <div wire:loading wire:target="mediaFile" class="text-xs text-blue-400 mt-1 flex items-center gap-1">
                        <flux:icon name="arrow-path" class="w-3.5 h-3.5 animate-spin" />
                        <span>Mengunggah visual media...</span>
                    </div>

                    @if($mediaFile)
                    <div class="mt-2 text-xs text-emerald-400 flex items-center gap-1">
                        <flux:icon name="check" class="w-3.5 h-3.5" />
                        <span>File terpilih: {{ $mediaFile->getClientOriginalName() }} ({{ round($mediaFile->getSize() / 1024 / 1024, 2) }} MB)</span>
                    </div>
                    @endif
                </div>

                @if($media_type === 'video')
                <div class="p-3 rounded-xl bg-zinc-950/60 border border-zinc-800 space-y-2">
                    <label class="block text-xs font-semibold text-zinc-200">
                        Custom Thumbnail Video <span class="text-zinc-500 font-normal">(Opsional - otomatis dibuat dari video jika dikosongkan)</span>
                    </label>
                    <input type="file" wire:model="thumbnailFile" accept="image/*" class="w-full text-xs text-zinc-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700" />
                    @error('thumbnailFile') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                </div>
                @endif

                <flux:textarea label="Catatan / Deskripsi Singkat" wire:model="description" placeholder="Informasi singkat atau promo khusus untuk teknisi..." rows="2" />

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-800">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-semibold transition">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition" wire:loading.attr="disabled">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
