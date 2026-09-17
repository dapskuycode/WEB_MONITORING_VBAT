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
    public $placement_type = 'hero_slider'; // hero_slider, shop_horizontal
    public $media_type = 'image';
    public $mediaFile;
    public $target_url = '';
    public $description = '';
    public $successMessage = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        // Temukan sponsor terkait user atau ambil sponsor pertama jika admin mencoba portal sponsor
        $this->sponsor = $user?->sponsor ?? Sponsor::first();

        if ($this->sponsor) {
            $this->campaigns = Campaign::where('sponsor_id', $this->sponsor->id)->latest()->get();
        } else {
            $this->campaigns = collect();
        }
    }

    public function openCreateModal()
    {
        $this->reset(['title', 'target_url', 'description', 'mediaFile', 'successMessage']);
        $this->placement_type = 'hero_slider';
        $this->media_type = 'image';
        $this->showModal = true;
    }

    public function submitCampaign()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'placement_type' => 'required|in:hero_slider,shop_horizontal',
            'target_url' => 'required|url',
            'description' => 'required|string',
            'mediaFile' => 'required|file|max:20480',
        ], [
            'mediaFile.required' => 'Wajib memilih file banner visual (gambar atau video)!',
            'mediaFile.max' => 'Ukuran file media melebihi batas maksimal 20 MB!',
        ]);

        $mediaPath = 'assets/images/banner_promo_diskon.png'; // Fallback mockup asset
        if ($this->mediaFile) {
            $mediaPath = $this->mediaFile->store('campaigns', 'public');
            $ext = strtolower($this->mediaFile->getClientOriginalExtension());
            if (in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'avi']) || $this->media_type === 'video') {
                $this->media_type = 'video';
            } else {
                $this->media_type = 'image';
            }
        }

        Campaign::create([
            'sponsor_id' => $this->sponsor->id,
            'title' => $this->title,
            'placement_type' => $this->placement_type,
            'media_path' => $mediaPath,
            'media_type' => $this->media_type,
            'target_url' => $this->target_url,
            'description' => $this->description,
            'daily_limit' => 2000,
            'weight' => $this->sponsor->weight ?: 3,
            'status' => 'pending', // Menunggu moderasi admin
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $this->successMessage = 'Pengajuan kampanye promosi berhasil dikirim! Menunggu persetujuan Admin Utama Vbat.';
        $this->showModal = false;
        $this->loadData();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Portal Mitra Sponsor: {{ $sponsor?->name ?? 'Mitra Sponsor' }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase bg-purple-100 text-purple-700 border border-purple-200">
                    Tier: {{ $sponsor?->tier ?? 'PARTNER' }}
                </span>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola materi promosi, ajukan banner hero slider atau banner shop baru untuk ditayangkan di aplikasi VbatPonsel.
            </p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajukan Kampanye Baru
        </button>
    </div>

    @if ($successMessage)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <span>✓ {{ $successMessage }}</span>
        </div>
    @endif

    <!-- Daftar Kampanye Sponsor -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($campaigns as $camp)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-sm flex flex-col justify-between">
                <div>
                    <!-- Banner -->
                    <div class="aspect-[2.5/1] bg-zinc-100 dark:bg-zinc-800 relative flex items-center justify-center overflow-hidden border-b dark:border-zinc-800">
                        @php
                            $mediaUrl = $camp->media_path;
                            if ($mediaUrl && !str_starts_with($mediaUrl, 'http')) {
                                $mediaUrl = str_starts_with($mediaUrl, 'assets/') ? asset($mediaUrl) : asset('storage/' . $mediaUrl);
                            }
                            $isVideo = $camp->media_type === 'video' || str_ends_with(strtolower($mediaUrl), '.mp4') || str_ends_with(strtolower($mediaUrl), '.webm') || str_ends_with(strtolower($mediaUrl), '.mov');
                        @endphp
                        @if ($isVideo)
                            <video src="{{ $mediaUrl }}" class="w-full h-full object-cover" autoplay muted loop playsinline controls></video>
                        @elseif ($mediaUrl)
                            <img src="{{ $mediaUrl }}" alt="{{ $camp->title }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-xs text-zinc-400">Tidak ada media visual</div>
                        @endif
                        <span class="absolute top-2 left-2 px-2.5 py-0.8 text-[10px] font-bold rounded-full uppercase shadow-sm
                            {{ $camp->status === 'approved' ? 'bg-emerald-500 text-white' : '' }}
                            {{ $camp->status === 'pending' ? 'bg-amber-500 text-white' : '' }}
                            {{ $camp->status === 'rejected' ? 'bg-rose-500 text-white' : '' }}
                        ">
                            {{ $camp->status === 'approved' ? '✓ Tayang Aktif' : ($camp->status === 'pending' ? '🕒 Menunggu Approval' : '✕ Ditolak') }}
                        </span>
                        <span class="absolute top-2 right-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-black/60 text-white backdrop-blur-xs flex items-center gap-1">
                            @if ($isVideo)
                                🎥 VIDEO
                            @else
                                🖼️ GAMBAR
                            @endif
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="p-5 space-y-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400">
                                {{ $camp->placement_type === 'hero_slider' ? '📌 Hero Slider Beranda' : '🛒 Banner Katalog Shop' }}
                            </span>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white mt-0.5">{{ $camp->title }}</h3>
                            <p class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $camp->description }}</p>
                        </div>

                        <div class="p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-100 dark:border-zinc-800 text-xs">
                            <span class="text-zinc-400 block text-[10px] uppercase font-bold">Link Toko Shopee/Tokopedia:</span>
                            <a href="{{ $camp->target_url }}" target="_blank" class="text-blue-600 hover:underline truncate block font-medium mt-0.5">
                                {{ $camp->target_url }}
                            </a>
                        </div>

                        <!-- Metrik Performa Ringkas -->
                        <div class="grid grid-cols-3 gap-2 pt-1 border-t dark:border-zinc-800 text-center text-xs">
                            <div class="p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                                <div class="text-[10px] text-zinc-400 uppercase font-bold">Views</div>
                                <div class="font-black text-zinc-800 dark:text-zinc-200 mt-0.5">{{ $camp->views_count }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                                <div class="text-[10px] text-zinc-400 uppercase font-bold">Clicks</div>
                                <div class="font-black text-zinc-800 dark:text-zinc-200 mt-0.5">{{ $camp->clicks_count }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                                <div class="text-[10px] text-zinc-400 uppercase font-bold">CTR</div>
                                <div class="font-black text-emerald-600 mt-0.5">{{ $camp->ctr }}%</div>
                            </div>
                        </div>

                        @if ($camp->rejection_reason)
                            <div class="p-2.5 rounded-lg bg-rose-50 text-rose-700 text-xs">
                                <strong>Catatan Admin:</strong> {{ $camp->rejection_reason }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-zinc-500 bg-white dark:bg-zinc-900 rounded-2xl border border-dashed border-zinc-300 dark:border-zinc-800">
                Anda belum mengajukan materi promosi. Klik "Ajukan Kampanye Baru" untuk mulai beriklan di VbatPonsel.
            </div>
        @endforelse
    </div>

    <!-- Modal Form Ajukan Kampanye -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Form Pengajuan Kampanye Promosi</h2>
                    <button wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Judul Promosi *</label>
                        <input type="text" wire:model="title" placeholder="Contoh: Diskon Suku Cadang Original" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Posisi Penayangan *</label>
                            <select wire:model="placement_type" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="hero_slider">Hero Slider (Beranda Atas)</option>
                                <option value="shop_horizontal">Banner Horizontal (Shop / 12 Item)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Tipe Media *</label>
                            <select wire:model="media_type" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="image">Gambar Banner (JPEG/PNG/WebP)</option>
                                <option value="video">Video Singkat (MP4)</option>
                            </select>
                        </div>
                    </div>

                    <div x-data="{ localFileName: '', localFileSize: '', localFileMB: 0, isTooLarge: false }">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                Unggah Banner Visual (Gambar / Video) *
                            </label>
                            <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 px-2 py-0.5 rounded-full">
                                Batas Maksimal: 20 MB
                            </span>
                        </div>

                        <input type="file"
                            wire:model="mediaFile"
                            accept="image/*,video/*"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    localFileName = file.name;
                                    const mb = (file.size / (1024 * 1024));
                                    localFileMB = mb;
                                    localFileSize = mb >= 1 ? mb.toFixed(2) + ' MB' : (file.size / 1024).toFixed(1) + ' KB';
                                    isTooLarge = mb > 20;
                                } else {
                                    localFileName = '';
                                    localFileSize = '';
                                    isTooLarge = false;
                                }
                            "
                            class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

                        <!-- Client-side Immediate File Size Display -->
                        <div x-show="localFileName" class="mt-2 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 text-xs flex items-center justify-between">
                            <div class="flex items-center gap-2 truncate">
                                <span class="font-bold text-zinc-500">File:</span>
                                <span x-text="localFileName" class="truncate font-semibold text-zinc-800 dark:text-zinc-200"></span>
                            </div>
                            <div class="shrink-0 font-bold px-2 py-0.5 rounded text-[11px]"
                                 :class="isTooLarge ? 'bg-rose-100 text-rose-700 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'">
                                <span x-text="'Ukuran: ' + localFileSize"></span>
                                <span x-show="!isTooLarge"> / Maks. 20 MB</span>
                            </div>
                        </div>

                        <div x-show="isTooLarge" class="mt-1 text-xs text-rose-600 font-bold">
                            ⚠️ Ukuran file melebihi batas 20 MB! Silakan pilih file video/gambar yang lebih kecil.
                        </div>

                        <!-- Upload Loading State -->
                        <div wire:loading wire:target="mediaFile" class="text-xs text-blue-600 dark:text-blue-400 font-bold flex items-center gap-1.5 mt-1.5 animate-pulse">
                            <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Sedang memproses & mengunggah file ke server, mohon tunggu...
                        </div>

                        <!-- Server Verified Confirmation -->
                        @if ($mediaFile)
                            @php
                                $sizeInMB = round($mediaFile->getSize() / (1024 * 1024), 2);
                                $displaySize = $sizeInMB >= 1 ? $sizeInMB . ' MB' : round($mediaFile->getSize() / 1024, 1) . ' KB';
                            @endphp
                            <div class="text-xs text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 mt-1.5">
                                ✓ Terverifikasi di server: {{ $mediaFile->getClientOriginalName() }} ({{ $displaySize }})
                            </div>
                        @endif
                        @error('mediaFile')
                            <span class="text-xs text-rose-600 mt-1 block font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Link Tujuan Toko (Shopee / Tokopedia / Web) *</label>
                        <input type="url" wire:model="target_url" placeholder="https://shopee.co.id/toko_resmi" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Deskripsi Promosi (Muncul pada Pop-up) *</label>
                        <textarea wire:model="description" rows="3" placeholder="Jelaskan detail promo atau keunggulan produk Anda..." class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t dark:border-zinc-800">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">Batal</button>
                    <button wire:click="submitCampaign" class="px-4 py-2 text-sm font-bold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-sm">Kirim Pengajuan</button>
                </div>
            </div>
        </div>
    @endif
</div>