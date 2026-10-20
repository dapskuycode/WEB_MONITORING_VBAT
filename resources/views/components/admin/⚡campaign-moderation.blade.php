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
    <!-- Filter Bar: Dropdowns + Slot Indicators -->
    <div class="p-3.5 sm:p-4 bg-white rounded-[12px] border border-slate-200/80 flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
        <!-- Left: Filter Dropdowns -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Placement Filter Dropdown -->
            <div class="relative">
                <select wire:model.live="placementFilter" class="appearance-none px-3.5 py-2 pr-8 rounded-lg border border-slate-200 bg-white text-xs sm:text-sm font-semibold text-[#212121] hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/20 focus:border-[#1a68f0] transition cursor-pointer">
                    <option value="all">Semua Penempatan</option>
                    <option value="hero_slider">Hero Slide (16:9)</option>
                    <option value="shop_horizontal">Horizontal Slider</option>
                    <option value="card_slider">Card Slider</option>
                    <option value="popup_modal">Pop Up Iklan</option>
                </select>
                <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>

            <!-- Status Filter Dropdown -->
            <div class="relative">
                <select wire:model.live="statusFilter" class="appearance-none px-3.5 py-2 pr-8 rounded-lg border border-slate-200 bg-white text-xs sm:text-sm font-semibold text-[#212121] hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/20 focus:border-[#1a68f0] transition cursor-pointer">
                    <option value="all">Semua Status</option>
                    <option value="pending">Menunggu (Pending)</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
                <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
        </div>

        <!-- Right: Slot Indicators -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Slot Hero -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Slot Hero: {{ $this->activeHeroCount }} dari 6</span>
            </div>

            <!-- Pop Up Aktif -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Pop Up Aktif: {{ $this->activePopupCount }} dari 1</span>
            </div>
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

                            <!-- Column 4: Status Icon -->
                            <td class="px-5 py-4">
                                @if ($camp->status === 'pending')
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs font-semibold text-amber-600">Pending</span>
                                    </div>
                                @elseif ($camp->status === 'approved')
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs font-semibold text-emerald-600">Disetujui</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l-2-2m0 0l-2-2m2 2l2-2m-2 2l-2 2m8-8a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs font-semibold text-rose-600">Ditolak</span>
                                    </div>
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
