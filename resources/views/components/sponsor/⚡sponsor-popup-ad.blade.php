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
    public $showPreviewModal = false;

    public $editingId = null;
    public $title = '';
    public $media_type = 'image';
    public $mediaFile;
    public $thumbnailFile;
    public $existing_media = null;
    public $target_url = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $successMessage = '';

    protected $listeners = [
        'trigger-create-popup' => 'openCreateModal'
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->sponsor = $user?->sponsor ?? Sponsor::where('name', 'like', '%Quantum%')->first() ?? Sponsor::first();

        if ($this->sponsor) {
            $this->campaigns = Campaign::where('sponsor_id', $this->sponsor->id)
                ->where('placement_type', 'popup_modal')
                ->latest()
                ->get();
        } else {
            $this->campaigns = collect();
        }
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'title', 'target_url', 'description', 'mediaFile', 'thumbnailFile', 'existing_media', 'successMessage']);
        $this->media_type = 'image';
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addDays(14)->format('Y-m-d');
        $this->showModal = true;
    }

    public function editCampaign($id)
    {
        $camp = Campaign::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $this->editingId = $camp->id;
        $this->title = $camp->title;
        $this->target_url = $camp->target_url;
        $this->description = $camp->description;
        $this->media_type = $camp->media_type;
        $this->existing_media = $camp->media_url;
        $this->start_date = $camp->start_date ? $camp->start_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->end_date = $camp->end_date ? $camp->end_date->format('Y-m-d') : now()->addDays(14)->format('Y-m-d');
        $this->mediaFile = null;
        $this->thumbnailFile = null;
        $this->showModal = true;
    }

    public function deleteCampaign($id)
    {
        $camp = Campaign::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $camp->delete();
        $this->successMessage = 'Pengajuan Pop Up Iklan berhasil dihapus.';
        $this->loadData();
    }

    public function submit()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'target_url' => 'required|url',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];

        if (!$this->editingId) {
            $rules['mediaFile'] = 'required|file|max:20480';
        } else {
            $rules['mediaFile'] = 'nullable|file|max:20480';
        }

        $rules['thumbnailFile'] = 'nullable|image|max:5120';

        $this->validate($rules, [
            'mediaFile.required' => 'Wajib memilih file visual Pop Up Iklan (gambar atau video)!',
            'mediaFile.max' => 'Ukuran file visual maksimal 20 MB!',
            'thumbnailFile.max' => 'Ukuran thumbnail maksimal 5 MB!',
            'start_date.required' => 'Rentang tanggal awal wajib diisi!',
            'end_date.required' => 'Rentang tanggal akhir wajib diisi!',
        ]);

        $mediaPath = null;
        if ($this->mediaFile) {
            $mediaPath = $this->mediaFile->store('campaigns/popup', 'public');
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
        } elseif ($this->media_type === 'video' && $mediaPath) {
            $thumbnailPath = \App\Services\VideoThumbnailService::generateThumbnail($mediaPath);
        }

        $data = [
            'sponsor_id' => $this->sponsor->id,
            'title' => $this->title,
            'placement_type' => 'popup_modal',
            'target_url' => $this->target_url,
            'description' => $this->description,
            'daily_limit' => 5000,
            'weight' => $this->sponsor->weight ?: 3,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        if ($mediaPath) {
            $data['media_path'] = $mediaPath;
            $data['media_type'] = $this->media_type;
        }

        if ($thumbnailPath) {
            $data['thumbnail_path'] = $thumbnailPath;
        }

        if ($this->editingId) {
            $camp = Campaign::where('sponsor_id', $this->sponsor->id)->findOrFail($this->editingId);
            $camp->update($data);
            $this->successMessage = 'Pengajuan Pop Up Iklan berhasil diperbarui!';
        } else {
            $data['status'] = 'pending';
            Campaign::create($data);
            $this->successMessage = 'Pengajuan Pop Up Iklan berhasil dikirim! Menunggu persetujuan Super Admin.';
        }

        $this->showModal = false;
        $this->loadData();
    }
};
?>

<div class="space-y-6">
    <!-- Alert Success / Info Toast -->
    @if($successMessage)
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs transition animate-fade-in dark:bg-emerald-950/40 dark:border-emerald-800/80 dark:text-emerald-300">
        <div class="flex items-center gap-2.5">
            <i class="fas fa-circle-check text-emerald-600 dark:text-emerald-400 text-base"></i>
            <span class="font-medium">{{ $successMessage }}</span>
        </div>
        <button type="button" wire:click="$set('successMessage', '')" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-200">
            Tutup
        </button>
    </div>
    @endif

    <!-- Guidelines Info Section (Horizontal Layout: Single clean white card with 3 divided sections) -->
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] border border-slate-100 dark:border-zinc-800 p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100 dark:divide-zinc-800 gap-4 md:gap-0">
            
            <!-- Section 1: Rentang Waktu Terjadwal -->
            <div class="flex items-start gap-3.5 md:pr-6">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1a68f0] flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40">
                    <i class="fas fa-clock text-base"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Rentang Waktu Terjadwal</h4>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Wajib menentukan tanggal awal dan akhir tayang otomatis.</p>
                </div>
            </div>

            <!-- Section 2: Multi-Sponsor Carousel Slider -->
            <div class="flex items-start gap-3.5 pt-4 md:pt-0 md:px-6">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i class="fas fa-layer-group text-base"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Multi-Sponsor Carousel Slider</h4>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Jika lebih dari 1 sponsor aktif pada periode yang sama, pop up berbentuk slider.</p>
                </div>
            </div>

            <!-- Section 3: Format Visual & Ukuran (Preview) -->
            <div class="flex items-center justify-between gap-3 pt-4 md:pt-0 md:pl-6">
                <div class="flex items-start gap-3.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                        <i class="fas fa-image-portrait text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Format Visual (Maks 20 MB)</h4>
                        <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Rasio portrait modal (4:5 / 3:4 / 1:1), JPG/PNG/MP4.</p>
                    </div>
                </div>
                <button type="button" 
                    wire:click="$set('showPreviewModal', true)" 
                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 hover:border-[#1a68f0] text-slate-700 dark:text-zinc-300 hover:text-[#1a68f0] bg-white dark:bg-zinc-800 hover:bg-blue-50/50 dark:hover:bg-blue-950/30 text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs">
                    <i class="fas fa-eye text-xs"></i>
                    <span>Preview</span>
                </button>
            </div>

        </div>
    </div>

    <!-- Data Table Card - SUBMISSION HISTORY (Riwayat Pengajuan Pop Up Iklan: Sleek, spacious, borderless, NO ANALYTICS) -->
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] border border-slate-100 dark:border-zinc-800 overflow-hidden">
        <div class="px-6 py-4.5 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <h3 class="font-bold text-base text-[#212121] dark:text-white tracking-tight">Riwayat Pengajuan Pop Up Iklan</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400">
                    {{ count($campaigns) }}
                </span>
            </div>
            <p class="text-xs text-[#828282] dark:text-zinc-400 hidden sm:block">Status penayangan pop-up dialog pembuka aplikasi</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950/40 text-[11px] font-bold text-[#828282] uppercase tracking-wider">
                        <th class="py-3.5 px-6 w-32">Visual Media</th>
                        <th class="py-3.5 px-6">Judul Pop Up</th>
                        <th class="py-3.5 px-6 w-56">Rentang Jadwal Tayang</th>
                        <th class="py-3.5 px-6 w-44 text-center">Status</th>
                        <th class="py-3.5 px-6 w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-sm">
                    @forelse($campaigns as $camp)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition">
                        <!-- Column 1: Visual Media (Portrait/Vertical 4:5 Thumbnail) -->
                        <td class="py-4 px-6 align-middle">
                            <div class="w-14 h-[68px] rounded-lg bg-slate-100 dark:bg-zinc-800 overflow-hidden relative border border-slate-200/80 dark:border-zinc-700 shrink-0 shadow-2xs group">
                                <img src="{{ $camp->thumbnail_url }}" 
                                     alt="{{ $camp->title }}" 
                                     class="w-full h-full object-cover transition duration-300 group-hover:scale-105"
                                     onerror="this.src='{{ asset('assets/images/PHOTO-2026-07-22-20-21-55.jpg') }}'" />
                                
                                @if($camp->media_type === 'video')
                                    <div class="absolute inset-0 bg-black/35 flex items-center justify-center text-white">
                                        <div class="w-5 h-5 rounded-full bg-[#1a68f0] text-white flex items-center justify-center shadow-xs">
                                            <i class="fas fa-play text-[8px] ml-0.5"></i>
                                        </div>
                                    </div>
                                    <span class="absolute bottom-1 right-1 px-1 py-0.2 bg-black/75 rounded text-[8px] font-mono text-white font-medium">MP4</span>
                                @endif
                            </div>
                        </td>

                        <!-- Column 2: Judul Pop Up -->
                        <td class="py-4 px-6 align-middle">
                            <div class="font-bold text-[#212121] dark:text-white text-sm line-clamp-1">
                                {{ $camp->title }}
                            </div>
                            @if($camp->target_url)
                            <a href="{{ $camp->target_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-[#1a68f0] hover:underline mt-1 max-w-md truncate">
                                <span class="truncate">{{ $camp->target_url }}</span>
                                <i class="fas fa-arrow-up-right-from-square text-[10px] shrink-0"></i>
                            </a>
                            @endif
                        </td>

                        <!-- Column 3: Rentang Jadwal Tayang -->
                        <td class="py-4 px-6 align-middle">
                            <div class="text-xs font-medium text-slate-700 dark:text-zinc-300 flex items-center gap-1.5">
                                <i class="far fa-calendar text-slate-400 text-xs"></i>
                                <span>
                                    {{ $camp->start_date ? $camp->start_date->translatedFormat('d M') : '-' }} – 
                                    {{ $camp->end_date ? $camp->end_date->translatedFormat('d M Y') : '-' }}
                                </span>
                            </div>
                        </td>

                        <!-- Column 4: Status (Approval Badges: pastel yellow for "Menunggu Approval", pastel green for "Disetujui") -->
                        <td class="py-4 px-6 align-middle text-center">
                            @if($camp->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#E8F5E9] text-[#2E7D32] border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#2E7D32] dark:bg-emerald-400"></span>
                                    <span>Disetujui</span>
                                </span>
                            @elseif($camp->status === 'pending')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>Menunggu Approval</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>Ditolak</span>
                                </span>
                            @endif
                        </td>

                        <!-- Column 5: Aksi (Clean, minimal icon-only buttons for Edit & Delete in subtle gray) -->
                        <td class="py-4 px-6 align-middle text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button" 
                                    wire:click="editCampaign({{ $camp->id }})" 
                                    title="Edit Pengajuan"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#1a68f0] hover:bg-blue-50 dark:hover:bg-blue-950/50 transition cursor-pointer active:scale-95">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button type="button" 
                                    wire:click="deleteCampaign({{ $camp->id }})" 
                                    wire:confirm="Yakin ingin menghapus pengajuan pop up iklan ini?"
                                    title="Hapus Pengajuan"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer active:scale-95">
                                    <i class="fas fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 px-6 text-center text-[#828282] dark:text-zinc-400">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-slate-400">
                                    <i class="fas fa-bullhorn text-lg"></i>
                                </div>
                                <span class="font-medium text-sm text-[#212121] dark:text-white">Belum ada pengajuan Pop Up Iklan</span>
                                <p class="text-xs text-[#828282] max-w-xs">Klik tombol "+ Ajukan Pop Up Baru" di bagian atas untuk mulai mengajukan modal interaktif startup.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Ajukan / Edit Pop Up Iklan (Light Mode Shopee Enterprise) -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in"
         wire:keydown.escape="$set('showModal', false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 dark:border-zinc-800 space-y-5 max-h-[90vh] overflow-y-auto">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-4">
                <div>
                    <h3 class="font-bold text-lg text-[#212121] dark:text-white">
                        {{ $editingId ? 'Edit Pop Up Iklan Startup' : 'Formulir Pengajuan Pop Up Iklan' }}
                    </h3>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Spesifikasi modal pembuka aplikasi ponsel format portrait (4:5 / 1:1).</p>
                </div>
                <button type="button" wire:click="$set('showModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                    <i class="fas fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form wire:submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-white mb-1.5">
                        Judul Pop Up <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                        wire:model="title" 
                        placeholder="Contoh: Mega Promo Suku Cadang" 
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-[#212121] dark:text-white focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/40 transition placeholder:text-slate-400" />
                    @error('title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-white mb-1.5">
                        Target Link URL / Marketplace <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fas fa-link text-xs"></i>
                        </span>
                        <input type="url" 
                            wire:model="target_url" 
                            placeholder="https://tokopedia.com/megapromo-part" 
                            required
                            class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-[#212121] dark:text-white focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/40 transition placeholder:text-slate-400" />
                    </div>
                    @error('target_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-white mb-1.5">
                            Tanggal Mulai Tayang <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                            wire:model="start_date" 
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-[#212121] dark:text-white focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-blue-100 transition" />
                        @error('start_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-white mb-1.5">
                            Tanggal Selesai Tayang <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                            wire:model="end_date" 
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-[#212121] dark:text-white focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-blue-100 transition" />
                        @error('end_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-white mb-1.5">Tipe Format Media</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" 
                            wire:click="$set('media_type', 'image')" 
                            class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer {{ $media_type === 'image' ? 'border-[#1a68f0] bg-blue-50/70 text-[#1a68f0] dark:bg-blue-950/50' : 'border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-[#828282]' }}">
                            <i class="fas fa-image text-sm"></i>
                            <span>Gambar (4:5 / 1:1)</span>
                        </button>
                        <button type="button" 
                            wire:click="$set('media_type', 'video')" 
                            class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer {{ $media_type === 'video' ? 'border-[#1a68f0] bg-blue-50/70 text-[#1a68f0] dark:bg-blue-950/50' : 'border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-[#828282]' }}">
                            <i class="fas fa-video text-sm"></i>
                            <span>Video MP4 (Maks 20MB)</span>
                        </button>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50/50 dark:bg-zinc-800/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-[#212121] dark:text-white">
                            File Pop Up Iklan {{ $editingId ? '(Biarkan kosong jika tidak diganti)' : '*' }}
                        </label>
                        <button type="button" wire:click="$set('showPreviewModal', true)" class="text-xs text-[#1a68f0] hover:underline flex items-center gap-1 font-semibold cursor-pointer">
                            <i class="fas fa-eye text-[11px]"></i>
                            <span>Lihat Preview Mockup</span>
                        </button>
                    </div>
                    <input type="file" 
                        wire:model="mediaFile" 
                        class="w-full text-xs text-[#828282] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#1a68f0] file:text-white hover:file:bg-blue-700 cursor-pointer" />
                    @error('mediaFile') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror

                    <div wire:loading wire:target="mediaFile" class="text-xs text-[#1a68f0] mt-1.5 flex items-center gap-1.5 font-medium">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span>Mengunggah visual media...</span>
                    </div>

                    @if($mediaFile)
                    <div class="mt-2 text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 font-medium">
                        <i class="fas fa-check"></i>
                        <span>File terpilih: {{ $mediaFile->getClientOriginalName() }} ({{ round($mediaFile->getSize() / 1024 / 1024, 2) }} MB)</span>
                    </div>
                    @endif
                </div>

                @if($media_type === 'video')
                <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50/50 dark:bg-zinc-800/40 space-y-2">
                    <label class="block text-xs font-bold text-[#212121] dark:text-white">
                        Custom Thumbnail Video <span class="text-[#828282] font-normal">(Opsional)</span>
                    </label>
                    <input type="file" 
                        wire:model="thumbnailFile" 
                        accept="image/*" 
                        class="w-full text-xs text-[#828282] file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 cursor-pointer" />
                    @error('thumbnailFile') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
                </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-white mb-1.5">
                        Catatan / Deskripsi Singkat <span class="text-[#828282] font-normal">(Opsional)</span>
                    </label>
                    <textarea 
                        wire:model="description" 
                        rows="2" 
                        placeholder="Keterangan singkat promo atau pesan khusus..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-[#212121] dark:text-white focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400"></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <button type="button" 
                        wire:click="$set('showModal', false)" 
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300 text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                        wire:loading.attr="disabled"
                        class="px-5 py-2.5 rounded-xl bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                        {{ $editingId ? 'Simpan Perubahan' : 'Kirim Pengajuan Pop Up' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal Preview Mockup Tampilan Pop Up Iklan -->
    @if($showPreviewModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-fade-in"
         wire:keydown.escape="$set('showPreviewModal', false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 dark:border-zinc-800 space-y-4 max-h-[92vh] flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-3 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1a68f0] flex items-center justify-center border border-blue-100 dark:border-blue-900/40">
                        <i class="fas fa-mobile-screen-button text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#212121] dark:text-white">Preview Pop Up Iklan Startup</h3>
                        <p class="text-[11px] text-[#828282] dark:text-zinc-400">Dialog Modal Pembuka Saat Buka Aplikasi (4:5 / 1:1)</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('showPreviewModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                    <i class="fas fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto pr-1 space-y-3.5 text-center">
                <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 shadow-sm bg-slate-50 dark:bg-zinc-950 inline-block max-w-full">
                    <img src="{{ asset('assets/images/previews/preview-popup-ad.jpg') }}" 
                         alt="Preview Pop Up Iklan di Aplikasi" 
                         class="max-h-[58vh] w-auto mx-auto object-contain">
                </div>
                <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 dark:bg-blue-950/30 dark:border-blue-900/40 text-left text-xs text-blue-900 dark:text-blue-200 space-y-1">
                    <div class="font-bold flex items-center gap-1.5 text-[#1a68f0]">
                        <i class="fas fa-circle-info text-xs"></i>
                        <span>Panduan Rasio Modal Pop Up (4:5 / 3:4 / 1:1)</span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-zinc-300 leading-relaxed">
                        Area yang dikelilingi <strong class="text-rose-500 font-semibold">kotak merah</strong> pada preview menunjukkan letak penayangan dialog modal pop-up yang muncul saat pengguna membuka aplikasi ponsel VBAT.
                    </p>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-zinc-800 flex justify-end shrink-0">
                <button type="button" wire:click="$set('showPreviewModal', false)" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs font-semibold transition cursor-pointer">
                    Tutup Preview
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
