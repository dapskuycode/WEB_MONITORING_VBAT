<?php

use Livewire\Component;
use App\Models\Campaign;

new class extends Component
{
    public $campaigns;
    public $statusFilter = 'all'; // all, pending, approved, rejected
    public $placementFilter = 'all'; // all, hero_slider, shop_horizontal, card_slider, popup_modal
    public $editingLimitId = null;
    public $newLimit = 1000;
    public $newWeight = 1;

    public function mount()
    {
        $this->loadCampaigns();
    }

    public function loadCampaigns()
    {
        $query = Campaign::with('sponsor')->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->placementFilter !== 'all') {
            $query->where('placement_type', $this->placementFilter);
        }

        $this->campaigns = $query->get();
    }

    public function setStatusFilter($status)
    {
        $this->statusFilter = $status;
        $this->loadCampaigns();
    }

    public function setPlacementFilter($placement)
    {
        $this->placementFilter = $placement;
        $this->loadCampaigns();
    }

    public function approve($id)
    {
        $c = Campaign::findOrFail($id);
        $c->update(['status' => 'approved', 'rejection_reason' => null]);
        $this->loadCampaigns();
    }

    public function reject($id)
    {
        $c = Campaign::findOrFail($id);
        $c->update(['status' => 'rejected', 'rejection_reason' => 'Materi visual atau link promosi tidak memenuhi panduan komunitas.']);
        $this->loadCampaigns();
    }

    public function getActiveHeroCountProperty()
    {
        return Campaign::active()->where('placement_type', 'hero_slider')->count();
    }

    public function getActivePopupCountProperty()
    {
        return Campaign::active()->where('placement_type', 'popup_modal')->count();
    }

    public function editLimits($id)
    {
        $c = Campaign::findOrFail($id);
        $this->editingLimitId = $c->id;
        $this->newLimit = $c->daily_limit;
        $this->newWeight = $c->weight;
    }

    public function saveLimits()
    {
        if ($this->editingLimitId) {
            $c = Campaign::findOrFail($this->editingLimitId);
            $c->update([
                'daily_limit' => $this->newLimit,
                'weight' => $this->newWeight,
            ]);
            $this->editingLimitId = null;
            $this->loadCampaigns();
        }
    }
};
?>

<div class="space-y-6">
    <!-- Header Moderasi -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Moderasi Kampanye Promosi Sponsor
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Tinjau materi banner promosi yang diajukan oleh sponsor, berikan persetujuan (Approve/Reject), dan atur bobot rotasi serta limit penayangan harian.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Indikator Kuota Slot Hero Slider -->
            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-xs font-bold text-blue-700 dark:text-blue-300 shadow-sm">
                <span class="w-2 h-2 rounded-full {{ $this->activeHeroCount >= 6 ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse' }}"></span>
                <span>Slot Hero: {{ $this->activeHeroCount }} / 6</span>
            </div>

            <!-- Indikator Kuota Pop Up Iklan -->
            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-xs font-bold text-rose-700 dark:text-rose-300 shadow-sm">
                <span class="w-2 h-2 rounded-full {{ $this->activePopupCount > 0 ? 'bg-rose-500 animate-pulse' : 'bg-zinc-400' }}"></span>
                <span>Pop Up Aktif: {{ $this->activePopupCount }}</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar: Placement Types & Status -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <!-- Tabs Penempatan -->
        <div class="flex flex-wrap items-center gap-1.5">
            <button wire:click="setPlacementFilter('all')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $placementFilter === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Semua Penempatan
            </button>
            <button wire:click="setPlacementFilter('hero_slider')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $placementFilter === 'hero_slider' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Hero Slide (16:9)
            </button>
            <button wire:click="setPlacementFilter('shop_horizontal')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $placementFilter === 'shop_horizontal' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Horizontal Slider
            </button>
            <button wire:click="setPlacementFilter('card_slider')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $placementFilter === 'card_slider' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Card Slider
            </button>
            <button wire:click="setPlacementFilter('popup_modal')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $placementFilter === 'popup_modal' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Pop Up Iklan
            </button>
        </div>

        <!-- Filter Status Buttons -->
        <div class="flex rounded-xl bg-zinc-100 dark:bg-zinc-700/50 p-1 text-xs font-semibold self-start md:self-auto">
            <button wire:click="setStatusFilter('all')" class="px-3 py-1.5 rounded-lg {{ $statusFilter === 'all' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400' }}">Semua Status</button>
            <button wire:click="setStatusFilter('pending')" class="px-3 py-1.5 rounded-lg {{ $statusFilter === 'pending' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400' }}">Menunggu (Pending)</button>
            <button wire:click="setStatusFilter('approved')" class="px-3 py-1.5 rounded-lg {{ $statusFilter === 'approved' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400' }}">Disetujui</button>
            <button wire:click="setStatusFilter('rejected')" class="px-3 py-1.5 rounded-lg {{ $statusFilter === 'rejected' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400' }}">Ditolak</button>
        </div>
    </div>

    <!-- Grid List Kampanye -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($campaigns as $camp)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-sm flex flex-col justify-between group hover:border-zinc-300 dark:hover:border-zinc-700 transition">
                <div>
                    <!-- Media Preview -->
                    <div class="aspect-[2.2/1] bg-zinc-100 dark:bg-zinc-800 relative flex items-center justify-center overflow-hidden border-b dark:border-zinc-800">
                        @php
                            $mediaUrl = $camp->media_path;
                            if ($mediaUrl && !str_starts_with($mediaUrl, 'http')) {
                                $mediaUrl = str_starts_with($mediaUrl, 'assets/') ? asset($mediaUrl) : asset('storage/' . $mediaUrl);
                            }
                            $isVideo = $camp->media_type === 'video' || str_ends_with(strtolower($mediaUrl), '.mp4') || str_ends_with(strtolower($mediaUrl), '.webm') || str_ends_with(strtolower($mediaUrl), '.mov');
                            
                            $placementBadge = match($camp->placement_type) {
                                'hero_slider' => ['label' => 'Hero Slide', 'color' => 'bg-blue-600'],
                                'shop_horizontal' => ['label' => 'Horizontal', 'color' => 'bg-indigo-600'],
                                'card_slider' => ['label' => 'Card Slider', 'color' => 'bg-teal-600'],
                                'popup_modal' => ['label' => 'Pop Up Dialog', 'color' => 'bg-rose-600'],
                                default => ['label' => $camp->placement_type, 'color' => 'bg-zinc-600'],
                            };
                        @endphp

                        @if ($isVideo)
                            <video src="{{ $mediaUrl }}" class="w-full h-full object-cover" autoplay muted loop playsinline controls></video>
                        @elseif ($mediaUrl)
                            <img src="{{ $mediaUrl }}" alt="{{ $camp->title }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-xs text-zinc-400 font-semibold">Tidak ada preview visual</div>
                        @endif

                        <span class="absolute top-2 left-2 px-2.5 py-1 text-[10px] font-bold rounded-full uppercase shadow-sm
                            {{ $camp->status === 'approved' ? 'bg-emerald-500 text-white' : '' }}
                            {{ $camp->status === 'pending' ? 'bg-amber-500 text-white' : '' }}
                            {{ $camp->status === 'rejected' ? 'bg-rose-500 text-white' : '' }}
                        ">
                            {{ $camp->status }}
                        </span>

                        <div class="absolute top-2 right-2 flex items-center gap-1.5">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-black/60 text-white backdrop-blur-xs flex items-center gap-1">
                                {{ $isVideo ? '🎥 VIDEO' : '🖼️ GAMBAR' }}
                            </span>
                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full text-white {{ $placementBadge['color'] }} shadow-xs">
                                {{ $placementBadge['label'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Detail & Spesifikasi -->
                    <div class="p-5 space-y-3">
                        <div>
                            <div class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                                {{ $camp->sponsor?->name }} (TIER {{ strtoupper($camp->sponsor?->tier ?? 'PARTNER') }})
                            </div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white mt-0.5 leading-snug">{{ $camp->title }}</h3>
                            <p class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $camp->description ?: 'Tidak ada deskripsi.' }}</p>
                        </div>

                        <!-- Target Link Promosi -->
                        <div class="p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-100 dark:border-zinc-800 text-xs">
                            <span class="text-zinc-400 block text-[10px] uppercase font-bold">Target Link Promosi:</span>
                            <a href="{{ $camp->target_url }}" target="_blank" class="text-blue-600 hover:underline truncate block font-medium mt-0.5">
                                {{ $camp->target_url }}
                            </a>
                        </div>

                        <!-- Jadwal Rentang Waktu (Khusus Pop Up & Kampanye Terjadwal) -->
                        <div class="flex items-center justify-between text-xs p-2 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400 text-[11px]">Jadwal Tayang:</span>
                            <span class="font-mono text-zinc-700 dark:text-zinc-300 font-semibold text-[11px]">
                                {{ $camp->start_date ? $camp->start_date->format('d/m/Y') : '-' }} s/d {{ $camp->end_date ? $camp->end_date->format('d/m/Y') : '-' }}
                            </span>
                        </div>

                        <!-- Limit & Bobot Rotasi -->
                        <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400 pt-1">
                            <div>
                                <span class="text-zinc-400">Limit:</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ number_format($camp->daily_limit) }}/hr</span>
                            </div>
                            <div>
                                <span class="text-zinc-400">Bobot:</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $camp->weight }}</span>
                            </div>
                            <button wire:click="editLimits({{ $camp->id }})" class="text-blue-600 hover:underline font-semibold text-[11px]">
                                Atur Kuota
                            </button>
                        </div>

                        @if ($camp->rejection_reason)
                            <div class="p-2.5 rounded-lg bg-rose-50 text-rose-700 text-xs">
                                <strong>Alasan Penolakan:</strong> {{ $camp->rejection_reason }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Moderasi -->
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30 flex items-center justify-end gap-2">
                    @if ($camp->status !== 'approved')
                        <button wire:click="approve({{ $camp->id }})" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                            ✓ Approve (Tayangkan)
                        </button>
                    @endif
                    @if ($camp->status !== 'rejected')
                        <button wire:click="reject({{ $camp->id }})" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition">
                            ✕ Reject (Tolak)
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-zinc-500 bg-white dark:bg-zinc-900 rounded-2xl border border-dashed border-zinc-300 dark:border-zinc-800">
                Belum ada kampanye dengan filter yang dipilih.
            </div>
        @endforelse
    </div>

    <!-- Modal Edit Limit & Bobot -->
    @if ($editingLimitId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-sm p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-zinc-900 dark:text-white">Atur Kuota & Frekuensi Tayang</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Limit Tayangan Per Hari</label>
                        <input type="number" wire:model="newLimit" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Bobot Rotasi Iklan (1 - 10)</label>
                        <input type="number" wire:model="newWeight" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t dark:border-zinc-800">
                    <button wire:click="$set('editingLimitId', null)" class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">Batal</button>
                    <button wire:click="saveLimits" class="px-4 py-1.5 text-xs font-bold rounded-lg bg-blue-600 text-white shadow-sm">Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>