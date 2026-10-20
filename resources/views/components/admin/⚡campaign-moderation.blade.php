<?php

use Livewire\Component;
use App\Models\Campaign;

new class extends Component
{
    public $campaigns;
    public $statusFilter = 'all';
    public $placementFilter = 'all';
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
        $payload = ['status' => 'approved'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('campaigns', 'rejection_reason')) {
            $payload['rejection_reason'] = null;
        }
        $c->update($payload);
        $this->loadCampaigns();
    }

    public function reject($id)
    {
        $c = Campaign::findOrFail($id);
        $payload = ['status' => 'rejected'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('campaigns', 'rejection_reason')) {
            $payload['rejection_reason'] = 'Materi visual atau link promosi tidak memenuhi panduan komunitas.';
        }
        $c->update($payload);
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

<div class="space-y-6 text-[#212121]">
    <!-- Page Header: Title + Subtitle + Slot Indicators -->
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#212121] flex items-center gap-2.5">
                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Moderasi Kampanye Promosi Sponsor
            </h1>
            <p class="text-xs sm:text-sm text-[#828282] mt-1.5 leading-relaxed">
                Tinjau materi banner promosi yang diajukan oleh sponsor, berikan persetujuan (Approve/Reject), dan atur bobot rotasi serta limit penayangan harian.
            </p>
        </div>

        <!-- Top Right: Slot Indicators -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- Slot Hero Indicator -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700">
                <span class="w-2 h-2 rounded-full {{ $this->activeHeroCount >= 6 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                <span>Slot Hero: {{ $this->activeHeroCount }}/6</span>
            </div>

            <!-- Pop Up Aktif Indicator -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
                <span class="w-2 h-2 rounded-full {{ $this->activePopupCount > 0 ? 'bg-rose-500' : 'bg-slate-400' }}"></span>
                <span>Pop Up Aktif: {{ $this->activePopupCount }}</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar: Placement & Status -->
    <div class="p-3.5 sm:p-4 bg-white rounded-[12px] border border-slate-200/80 flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
        <!-- Left: Placement Filter Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="setPlacementFilter('all')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $placementFilter === 'all' ? 'bg-[#1a68f0] text-white shadow-sm' : 'text-[#828282] hover:text-[#212121] hover:bg-slate-50' }}">
                Semua Penempatan
            </button>
            <button wire:click="setPlacementFilter('hero_slider')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $placementFilter === 'hero_slider' ? 'bg-[#1a68f0] text-white shadow-sm' : 'text-[#828282] hover:text-[#212121] hover:bg-slate-50' }}">
                Hero Slide (16:9)
            </button>
            <button wire:click="setPlacementFilter('shop_horizontal')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $placementFilter === 'shop_horizontal' ? 'bg-[#1a68f0] text-white shadow-sm' : 'text-[#828282] hover:text-[#212121] hover:bg-slate-50' }}">
                Horizontal Slider
            </button>
            <button wire:click="setPlacementFilter('card_slider')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $placementFilter === 'card_slider' ? 'bg-[#1a68f0] text-white shadow-sm' : 'text-[#828282] hover:text-[#212121] hover:bg-slate-50' }}">
                Card Slider
            </button>
            <button wire:click="setPlacementFilter('popup_modal')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $placementFilter === 'popup_modal' ? 'bg-[#1a68f0] text-white shadow-sm' : 'text-[#828282] hover:text-[#212121] hover:bg-slate-50' }}">
                Pop Up Iklan
            </button>
        </div>

        <!-- Right: Status Filter Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="setStatusFilter('all')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $statusFilter === 'all' ? 'border-2 border-[#1a68f0] text-[#1a68f0] bg-white' : 'border border-slate-200 text-[#828282] hover:text-[#212121] hover:border-slate-300' }}">
                Semua Status
            </button>
            <button wire:click="setStatusFilter('pending')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $statusFilter === 'pending' ? 'border-2 border-[#1a68f0] text-[#1a68f0] bg-white' : 'border border-slate-200 text-[#828282] hover:text-[#212121] hover:border-slate-300' }}">
                Menunggu (Pending)
            </button>
            <button wire:click="setStatusFilter('approved')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $statusFilter === 'approved' ? 'border-2 border-[#1a68f0] text-[#1a68f0] bg-white' : 'border border-slate-200 text-[#828282] hover:text-[#212121] hover:border-slate-300' }}">
                Disetujui
            </button>
            <button wire:click="setStatusFilter('rejected')" class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $statusFilter === 'rejected' ? 'border-2 border-[#1a68f0] text-[#1a68f0] bg-white' : 'border border-slate-200 text-[#828282] hover:text-[#212121] hover:border-slate-300' }}">
                Ditolak
            </button>
        </div>
    </div>

    <!-- Data Table: Clean Borderless -->
    <div class="bg-white rounded-[12px] border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#F4F6F8] border-b border-slate-200/80">
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wider">Banner Preview</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wider">Detail Kampanye</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wider">Penempatan</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3.5 text-center text-xs font-bold text-[#212121] uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($campaigns as $camp)
                        @php
                            $mediaUrl = $camp->media_path;
                            if ($mediaUrl && !str_starts_with($mediaUrl, 'http')) {
                                $mediaUrl = str_starts_with($mediaUrl, 'assets/') ? asset($mediaUrl) : asset('storage/' . $mediaUrl);
                            }
                            $isVideo = $camp->media_type === 'video' || str_ends_with(strtolower($mediaUrl ?? ''), '.mp4');
                            
                            $placementLabel = match($camp->placement_type) {
                                'hero_slider' => 'Hero Slide (16:9)',
                                'shop_horizontal' => 'Horizontal Slider',
                                'card_slider' => 'Card Slider',
                                'popup_modal' => 'Pop Up Iklan',
                                default => $camp->placement_type,
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <!-- Column 1: Banner Preview -->
                            <td class="px-5 py-4">
                                <div class="w-24 h-14 rounded-lg overflow-hidden bg-slate-100 flex items-center justify-center">
                                    @if ($isVideo)
                                        <video src="{{ $mediaUrl }}" class="w-full h-full object-cover" muted></video>
                                    @elseif ($mediaUrl)
                                        <img src="{{ $mediaUrl }}" alt="{{ $camp->title }}" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    @endif
                                </div>
                            </td>

                            <!-- Column 2: Detail Kampanye -->
                            <td class="px-5 py-4">
                                <div class="max-w-xs">
                                    <p class="text-sm font-bold text-[#212121] leading-snug">{{ $camp->title }}</p>
                                    <p class="text-xs text-[#828282] mt-0.5">{{ $camp->sponsor?->name ?? 'N/A' }}</p>
                                </div>
                            </td>

                            <!-- Column 3: Penempatan -->
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-[#212121]">{{ $placementLabel }}</p>
                                <p class="text-xs text-[#828282] mt-0.5">{{ $camp->created_at->format('d/m/Y') }}</p>
                            </td>

                            <!-- Column 4: Status Badge -->
                            <td class="px-5 py-4">
                                @if ($camp->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Pending
                                    </span>
                                @elseif ($camp->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Ditolak
                                    </span>
                                @endif
                            </td>

                            <!-- Column 5: Action Buttons -->
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($camp->status !== 'approved')
                                        <button wire:click="approve({{ $camp->id }})" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                            Setujui
                                        </button>
                                    @endif
                                    @if ($camp->status !== 'rejected')
                                        <button wire:click="reject({{ $camp->id }})" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition">
                                            Tolak
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-[#828282] text-sm">
                                Belum ada kampanye dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit Limit & Bobot -->
    @if ($editingLimitId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-[#212121]">Atur Kuota & Frekuensi Tayang</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-[#212121] mb-1">Limit Tayangan Per Hari</label>
                        <input type="number" wire:model="newLimit" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#212121] mb-1">Bobot Rotasi Iklan (1 - 10)</label>
                        <input type="number" wire:model="newWeight" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button wire:click="$set('editingLimitId', null)" class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-[#828282]">Batal</button>
                    <button wire:click="saveLimits" class="px-4 py-1.5 text-xs font-bold rounded-lg bg-[#1a68f0] text-white shadow-sm">Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>
