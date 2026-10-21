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

    public $title = '';
    public $media_type = 'image';
    public $mediaFile;
    public $thumbnailFile;
    public $target_url = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $successMessage = '';

    public $editingId = null;
    public $existing_media = '';

    protected $listeners = ['trigger-create-hero' => 'openCreateModal'];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        if ($user && $user->isSuperAdmin()) {
            $this->sponsor = Sponsor::where('slug', 'quantum')->orWhere('name', 'Quantum')->first() ?? $user->sponsor ?? Sponsor::first();
        } else {
            $this->sponsor = $user?->sponsor ?? Sponsor::first();
        }

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
        $this->reset(['editingId', 'existing_media', 'title', 'target_url', 'description', 'mediaFile', 'thumbnailFile', 'successMessage']);
        $this->media_type = 'image';
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addMonths(1)->format('Y-m-d');
        $this->showModal = true;
    }

    public function editCampaign($id)
    {
        if (!$this->sponsor) return;

        $c = Campaign::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $this->editingId = $c->id;
        $this->title = $c->title;
        $this->target_url = $c->target_url;
        $this->description = $c->description ?? '';
        $this->media_type = $c->media_type ?? 'image';
        $this->start_date = $c->start_date ? $c->start_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->end_date = $c->end_date ? $c->end_date->format('Y-m-d') : now()->addMonths(1)->format('Y-m-d');
        $this->existing_media = $c->media_path;
        $this->mediaFile = null;
        $this->thumbnailFile = null;
        $this->showModal = true;
    }

    public function deleteCampaign($id)
    {
        if (!$this->sponsor) return;

        $c = Campaign::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $c->delete();
        $this->successMessage = 'Pengajuan banner berhasil dihapus.';
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
            'mediaFile.required' => 'Wajib memilih file visual banner (gambar atau video)!',
            'mediaFile.max' => 'Ukuran file visual maksimal 20 MB!',
            'thumbnailFile.max' => 'Ukuran thumbnail maksimal 5 MB!',
        ]);

        $mediaPath = $this->existing_media ?: 'assets/images/banner_promo_diskon.png';
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

        $data = [
            'sponsor_id' => $this->sponsor->id,
            'title' => $this->title,
            'placement_type' => 'hero_slider',
            'media_path' => $mediaPath,
            'media_type' => $this->media_type,
            'target_url' => $this->target_url,
            'description' => $this->description,
            'daily_limit' => 3000,
            'weight' => $this->sponsor->weight ?: 3,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        if ($thumbnailPath) {
            $data['thumbnail_path'] = $thumbnailPath;
        }

        if ($this->editingId) {
            $camp = Campaign::where('sponsor_id', $this->sponsor->id)->findOrFail($this->editingId);
            $camp->update($data);
            $this->successMessage = 'Pengajuan banner Hero Slide berhasil diperbarui!';
        } else {
            $data['status'] = 'pending';
            Campaign::create($data);
            $this->successMessage = 'Pengajuan banner Hero Slide berhasil dikirim! Menunggu moderasi Super Admin.';
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
            
            <!-- Section 1: Landscape 16:9 -->
            <div class="flex items-center justify-between gap-3 md:pr-6">
                <div class="flex items-start gap-3.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1a68f0] flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40">
                        <i class="fas fa-image text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Rasio Landscape 16:9</h4>
                        <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Resolusi 1080 x 608 px (JPG/PNG/WEBP).</p>
                    </div>
                </div>
                <button type="button" 
                    wire:click="$set('showPreviewModal', true)" 
                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 hover:border-[#1a68f0] text-slate-700 dark:text-zinc-300 hover:text-[#1a68f0] bg-white dark:bg-zinc-800 hover:bg-blue-50/50 dark:hover:bg-blue-950/30 text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs">
                    <i class="fas fa-eye text-xs"></i>
                    <span>Preview</span>
                </button>
            </div>

            <!-- Section 2: Video MP4 Support -->
            <div class="flex items-start gap-3.5 pt-4 md:pt-0 md:px-6">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i class="fas fa-video text-base"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Dukungan Video MP4</h4>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Video promo looping tanpa suara (maks 20 MB).</p>
                </div>
            </div>

            <!-- Section 3: Direct URL / Marketplace -->
            <div class="flex items-start gap-3.5 pt-4 md:pt-0 md:pl-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                    <i class="fas fa-link text-base"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Direct URL / Marketplace</h4>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Klik langsung membuka tautan toko atau website.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- Data Table Card - SUBMISSION HISTORY (Riwayat Pengajuan: Sleek, spacious, borderless, NO ANALYTICS) -->
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] border border-slate-100 dark:border-zinc-800 overflow-hidden">
        <div class="px-6 py-4.5 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <h3 class="font-bold text-base text-[#212121] dark:text-white tracking-tight">Riwayat Pengajuan</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400">
                    {{ count($campaigns) }}
                </span>
            </div>
            <p class="text-xs text-[#828282] dark:text-zinc-400 hidden sm:block">Status moderasi banner oleh tim verifikator VBAT</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950/40 text-[11px] font-bold text-[#828282] uppercase tracking-wider">
                        <th class="py-3.5 px-6 w-36">Visual Media</th>
                        <th class="py-3.5 px-6">Judul Kampanye</th>
                        <th class="py-3.5 px-6 w-52">Periode Tayang</th>
                        <th class="py-3.5 px-6 w-44 text-center">Status</th>
                        <th class="py-3.5 px-6 w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-sm">
                    @forelse($campaigns as $camp)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition">
                        <!-- Column 1: Visual Media (16:9 Thumbnail) -->
                        <td class="py-4 px-6 align-middle">
                            <div class="w-24 h-[54px] rounded-lg bg-slate-100 dark:bg-zinc-800 overflow-hidden relative border border-slate-200/80 dark:border-zinc-700 shrink-0 shadow-2xs group">
                                <img src="{{ $camp->thumbnail_url }}" 
                                     alt="{{ $camp->title }}" 
                                     class="w-full h-full object-cover transition duration-300 group-hover:scale-105"
                                     onerror="this.src='{{ asset('assets/images/banner_promo_diskon.png') }}'" />
                                
                                @if($camp->media_type === 'video')
                                    <div class="absolute inset-0 bg-black/35 flex items-center justify-center text-white">
                                        <div class="w-6 h-6 rounded-full bg-[#1a68f0] text-white flex items-center justify-center shadow-xs">
                                            <i class="fas fa-play text-[9px] ml-0.5"></i>
                                        </div>
                                    </div>
                                    <span class="absolute bottom-1 right-1 px-1 py-0.2 bg-black/75 rounded text-[8px] font-mono text-white font-medium">MP4</span>
                                @endif
                            </div>
                        </td>

                        <!-- Column 2: Judul Kampanye -->
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

                        <!-- Column 3: Periode Tayang -->
                        <td class="py-4 px-6 align-middle">
                            <div class="text-xs font-medium text-slate-700 dark:text-zinc-300 flex items-center gap-1.5">
                                <i class="far fa-calendar text-slate-400 text-xs"></i>
                                <span>
                                    {{ $camp->start_date ? $camp->start_date->translatedFormat('d M Y') : '-' }} – 
                                    {{ $camp->end_date ? $camp->end_date->translatedFormat('d M Y') : '-' }}
                                </span>
                            </div>
                        </td>

                        <!-- Column 4: Status (Approval Badges: pastel yellow, pastel green, pastel red) -->
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
                                    wire:confirm="Yakin ingin menghapus pengajuan banner ini?"
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
                                    <i class="fas fa-images text-lg"></i>
                                </div>
                                <span class="font-medium text-sm text-[#212121] dark:text-white">Belum ada riwayat pengajuan banner</span>
                                <p class="text-xs text-[#828282] max-w-xs">Klik tombol "+ Ajukan Hero Slide Baru" di bagian atas untuk mulai mempromosikan produk Anda.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Ajukan / Edit Hero Slide (Light Mode Shopee Enterprise) -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in"
         wire:keydown.escape="$set('showModal', false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 dark:border-zinc-800 space-y-5 max-h-[90vh] overflow-y-auto">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-4">
                <div>
                    <h3 class="font-bold text-lg text-[#212121] dark:text-white">
                        {{ $editingId ? 'Edit Pengajuan Hero Slide' : 'Formulir Pengajuan Hero Slide Baru' }}
                    </h3>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Isi spesifikasi materi promosi landscape rasio 16:9.</p>
                </div>
                <button type="button" wire:click="$set('showModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                    <i class="fas fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form wire:submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-white mb-1.5">
                        Judul Kampanye <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                        wire:model="title" 
                        placeholder="Contoh: Promo Solder T12 Akhir Tahun" 
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
                            placeholder="https://tokopedia.com/toko-sponsor/promo" 
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
                            <span>Gambar (16:9)</span>
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
                            File Banner Hero {{ $editingId ? '(Biarkan kosong jika tidak diganti)' : '*' }}
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
                        placeholder="Informasi promosi atau keterangan tambahan untuk admin..."
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
                        {{ $editingId ? 'Simpan Perubahan' : 'Kirim Pengajuan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal Preview Mockup Tampilan Hero Slider -->
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
                        <h3 class="font-bold text-sm text-[#212121] dark:text-white">Preview Tampilan Hero Slider</h3>
                        <p class="text-[11px] text-[#828282] dark:text-zinc-400">Rasio Landscape 16:9 pada Halaman Beranda</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('showPreviewModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                    <i class="fas fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto pr-1 space-y-3.5 text-center">
                <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 shadow-sm bg-slate-50 dark:bg-zinc-950 inline-block max-w-full">
                    <img src="{{ asset('assets/images/previews/preview-hero-slider.jpg') }}" 
                         alt="Preview Hero Slider di Aplikasi" 
                         class="max-h-[58vh] w-auto mx-auto object-contain">
                </div>
                <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 dark:bg-blue-950/30 dark:border-blue-900/40 text-left text-xs text-blue-900 dark:text-blue-200 space-y-1">
                    <div class="font-bold flex items-center gap-1.5 text-[#1a68f0]">
                        <i class="fas fa-circle-info text-xs"></i>
                        <span>Spesifikasi Optimal (1080 × 608 px)</span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-zinc-300 leading-relaxed">
                        Banner Anda akan tampil pada carousel bagian paling atas di Beranda aplikasi VBAT Ponsel dan dapat diklik oleh pengguna untuk langsung menuju katalog atau toko Anda.
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
