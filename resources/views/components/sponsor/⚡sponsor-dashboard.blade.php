<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Campaign;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithFileUploads;

    public $sponsor;
    public $logo;
    public $showLogoModal = false;
    public $logoSuccessMessage = '';

    // WM-02: profil sponsor dapat diubah oleh sponsor untuk akunnya sendiri.
    public $description = '';
    public $website_url = '';
    public $marketplace_url = '';
    public $showProfileModal = false;

    // WR-02: gambar header halaman sponsor.
    public $headerImage;
    public $showHeaderModal = false;
    public $headerSuccessMessage = '';

    public function mount()
    {
        $user = Auth::user();
        $this->sponsor = $user?->sponsor ?? Sponsor::first();

        if ($this->sponsor) {
            $this->description = $this->sponsor->description ?? '';
            $this->website_url = $this->sponsor->website_url ?? '';
            $this->marketplace_url = $this->sponsor->marketplace_url ?? '';
        }
    }

    /** WM-02: simpan profil sponsor (deskripsi, website, marketplace). */
    public function saveProfile()
    {
        if (! $this->sponsor) {
            return;
        }

        $this->validate([
            'description' => 'nullable|string|max:1000',
            'website_url' => 'nullable|url',
            'marketplace_url' => 'nullable|url',
        ], [
            'website_url.url' => 'Format tautan website tidak valid.',
            'marketplace_url.url' => 'Format tautan marketplace tidak valid.',
        ]);

        $this->sponsor->update([
            'description' => $this->description,
            'website_url' => $this->website_url,
            'marketplace_url' => $this->marketplace_url,
        ]);
        $this->sponsor->refresh();

        $this->showProfileModal = false;
        $this->logoSuccessMessage = 'Profil sponsor berhasil diperbarui!';
    }

    /** WR-02: unggah gambar header halaman sponsor. */
    protected function isValidUploadedFile($file): bool
    {
        if (!$file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            return false;
        }
        try {
            $realPath = $file->getRealPath();
            return $file->exists() && !empty($realPath) && is_file($realPath) && !is_dir($realPath);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function updatedHeaderImage()
    {
        if ($this->headerImage && !$this->isValidUploadedFile($this->headerImage)) {
            $this->headerImage = null;
            $this->addError('headerImage', 'Berkas gambar header gagal diproses oleh server.');
        }
    }

    /** WR-02: unggah gambar header halaman sponsor. */
    public function saveHeaderImage()
    {
        if (!$this->headerImage || !$this->isValidUploadedFile($this->headerImage)) {
            $this->headerImage = null;
            $this->addError('headerImage', 'Berkas gambar header tidak valid atau gagal terunggah ke server.');
            return;
        }

        $this->validate([
            'headerImage' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ], [
            'headerImage.image' => 'Berkas harus berupa gambar valid (JPG, PNG, atau WEBP).',
            'headerImage.mimes' => 'Format gambar header hanya JPG, PNG, atau WEBP.',
            'headerImage.max' => 'Ukuran gambar header maksimal 4MB.',
        ]);

        if (! $this->sponsor) {
            return;
        }

        $path = $this->headerImage->store('sponsors/headers', 'public');
        $this->sponsor->update(['header_image_url' => $path]);
        $this->sponsor->refresh();

        $this->reset('headerImage');
        $this->showHeaderModal = false;
        $this->headerSuccessMessage = 'Gambar header berhasil diperbarui!';
    }

    public function updatedLogo()
    {
        if ($this->logo && !$this->isValidUploadedFile($this->logo)) {
            $this->logo = null;
            $this->addError('logo', 'Berkas logo gagal diproses oleh server.');
            return;
        }

        $this->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'logo.image' => 'Berkas harus berupa gambar valid (JPG, PNG, atau WEBP). Dokumen non-gambar seperti PDF tidak diizinkan.',
            'logo.mimes' => 'Format file yang diperbolehkan hanya JPG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran berkas logo maksimal 2MB.',
        ]);
    }

    public function saveLogo()
    {
        if (!$this->logo || !$this->isValidUploadedFile($this->logo)) {
            $this->logo = null;
            $this->addError('logo', 'Berkas logo tidak valid atau gagal terunggah ke server.');
            return;
        }

        $this->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'logo.image' => 'Berkas harus berupa gambar valid (JPG, PNG, atau WEBP). Dokumen non-gambar seperti PDF tidak diizinkan.',
            'logo.mimes' => 'Format file yang diperbolehkan hanya JPG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran berkas logo maksimal 2MB.',
        ]);

        if (!$this->sponsor) return;

        $path = $this->logo->store('sponsors/logos', 'public');
        $this->sponsor->update(['logo_path' => $path]);
        $this->sponsor->refresh();

        $this->reset('logo');
        $this->showLogoModal = false;
        $this->logoSuccessMessage = 'Logo resmi sponsor Anda berhasil diperbarui!';
    }

    public function with(): array
    {
        if (!$this->sponsor) {
            return [
                'hasSponsor' => false,
                'quotas' => [
                    'hero_slider' => ['label' => 'Hero Slider (16:9)', 'used' => 0, 'max' => 5, 'remaining' => 5, 'percent' => 100, 'icon' => 'fa-images', 'route' => route('sponsor.campaigns.hero')],
                    'horizontal_slider' => ['label' => 'Horizontal Slider', 'used' => 0, 'max' => 2, 'remaining' => 2, 'percent' => 100, 'icon' => 'fa-sliders-h', 'route' => route('sponsor.campaigns.horizontal')],
                    'card_slider' => ['label' => 'Card Slider', 'used' => 0, 'max' => 10, 'remaining' => 10, 'percent' => 100, 'icon' => 'fa-th-large', 'route' => route('sponsor.campaigns.card')],
                    'popup_ad' => ['label' => 'Pop Up Iklan', 'used' => 0, 'max' => 1, 'remaining' => 1, 'percent' => 100, 'icon' => 'fa-bullhorn', 'route' => route('sponsor.campaigns.popup')],
                ],
                'campaigns' => collect(),
                'products' => collect(),
            ];
        }

        $sponsorId = $this->sponsor->id;

        // Hitung kuota real-time untuk masing-masing penempatan iklan
        $countHero = Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'hero_slider')->count();
        $countHorizontal = Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'shop_horizontal')->count();
        $countCard = Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'card_slider')->count();
        $countPopup = Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'popup_modal')->count();

        // Resolusi batas kuota dari benefit tier sponsor (atau fallback rasional sesuai brief: 5, 2, 10, 1)
        $benefitHero = $this->sponsor->resolveBenefit('hero_slider');
        $benefitPopup = $this->sponsor->resolveBenefit('popup_cta');
        $benefitInfeed = $this->sponsor->resolveBenefit('infeed_banner');

        $maxHero = is_numeric($benefitHero) ? (int)$benefitHero : 5;
        $maxHorizontal = is_numeric($benefitInfeed) ? (int)$benefitInfeed : 2;
        $maxCard = 10;
        $maxPopup = is_numeric($benefitPopup) ? (int)$benefitPopup : 1;

        // Pastikan kalkulasi sisa dan persentase tidak bernilai negatif
        $remHero = max(0, $maxHero - $countHero);
        $remHorizontal = max(0, $maxHorizontal - $countHorizontal);
        $remCard = max(0, $maxCard - $countCard);
        $remPopup = max(0, $maxPopup - $countPopup);

        $quotas = [
            'hero_slider' => [
                'label' => 'Hero Slider (16:9)',
                'used' => $countHero,
                'max' => $maxHero,
                'remaining' => $remHero,
                'percent' => $maxHero > 0 ? min(100, round(($remHero / $maxHero) * 100)) : 0,
                'icon' => 'fa-images',
                'route' => route('sponsor.campaigns.hero'),
            ],
            'horizontal_slider' => [
                'label' => 'Horizontal Slider',
                'used' => $countHorizontal,
                'max' => $maxHorizontal,
                'remaining' => $remHorizontal,
                'percent' => $maxHorizontal > 0 ? min(100, round(($remHorizontal / $maxHorizontal) * 100)) : 0,
                'icon' => 'fa-sliders-h',
                'route' => route('sponsor.campaigns.horizontal'),
            ],
            'card_slider' => [
                'label' => 'Card Slider',
                'used' => $countCard,
                'max' => $maxCard,
                'remaining' => $remCard,
                'percent' => $maxCard > 0 ? min(100, round(($remCard / $maxCard) * 100)) : 0,
                'icon' => 'fa-th-large',
                'route' => route('sponsor.campaigns.card'),
            ],
            'popup_ad' => [
                'label' => 'Pop Up Iklan',
                'used' => $countPopup,
                'max' => $maxPopup,
                'remaining' => $remPopup,
                'percent' => $maxPopup > 0 ? min(100, round(($remPopup / $maxPopup) * 100)) : 0,
                'icon' => 'fa-bullhorn',
                'route' => route('sponsor.campaigns.popup'),
            ],
        ];

        // Daftar kampanye promosi (tanpa CTR / Clicks, hanya Kampanye, Penempatan, Status)
        $campaigns = Campaign::where('sponsor_id', $sponsorId)
            ->latest()
            ->take(6)
            ->get();

        // Katalog produk suku cadang milik sponsor
        $products = SponsorProduct::where('sponsor_id', $sponsorId)
            ->latest()
            ->take(6)
            ->get();

        return [
            'hasSponsor' => true,
            'quotas' => $quotas,
            'campaigns' => $campaigns,
            'products' => $products,
        ];
    }
};
?>

<div class="space-y-6 select-none">
    {{-- Toast Notifikasi Sukses --}}
    @if ($logoSuccessMessage || $headerSuccessMessage)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                <span class="font-medium">{{ $logoSuccessMessage ?: $headerSuccessMessage }}</span>
            </div>
            <button wire:click="$set('logoSuccessMessage', ''); $set('headerSuccessMessage', '')" class="text-emerald-700 hover:text-emerald-900 font-bold cursor-pointer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- 1. HEADER SECTION (Profile & Actions - Crisp White Container) --}}
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 p-6 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <!-- Left Side: Partner Profile Area -->
        <div class="flex items-center gap-4 min-w-0">
            <!-- Large Avatar: SP with small camera icon for upload -->
            <div class="relative shrink-0">
                <div class="w-16 h-16 rounded-[12px] bg-slate-100 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 flex items-center justify-center overflow-hidden shadow-xs">
                    @if ($sponsor?->logo_url)
                        <img src="{{ $sponsor->logo_url }}" alt="{{ $sponsor->name }}" class="w-full h-full object-contain p-1" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                        <span class="hidden text-xl font-extrabold text-[#1a68f0] tracking-wider uppercase">
                            {{ strtoupper(substr($sponsor?->name ?? 'SP', 0, 2)) }}
                        </span>
                    @else
                        <span class="text-xl font-extrabold text-[#1a68f0] tracking-wider uppercase">
                            {{ strtoupper(substr($sponsor?->name ?? 'SP', 0, 2)) }}
                        </span>
                    @endif
                </div>
                <button 
                    type="button" 
                    wire:click="$set('showLogoModal', true)" 
                    title="Upload Logo Resmi" 
                    class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-[#1a68f0] hover:bg-blue-600 text-white shadow-xs border-2 border-white dark:border-zinc-900 flex items-center justify-center cursor-pointer transition-transform hover:scale-105"
                >
                    <i class="fas fa-camera text-[10px]"></i>
                </button>
            </div>

            <!-- Partner Info & Quick Links -->
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl sm:text-2xl font-bold text-[#212121] dark:text-zinc-100 tracking-tight truncate">
                        {{ $sponsor?->name ?? 'Mitra Sponsor' }}
                    </h2>
                    <!-- Clean Pastel Blue Badge for TIER PARTNER -->
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#EBF3FE] text-[#1a68f0] border border-blue-200/60 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/60">
                        TIER {{ strtoupper($sponsor?->tier ?? 'PARTNER') }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3.5 mt-1.5 text-xs text-[#828282] dark:text-zinc-400">
                    <button type="button" wire:click="$set('showProfileModal', true)" class="text-[#1a68f0] hover:underline font-semibold flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-pen-to-square text-[11px]"></i>
                        <span>Ubah Profil</span>
                    </button>
                    <span class="text-slate-300 dark:text-zinc-700">•</span>
                    <button type="button" wire:click="$set('showHeaderModal', true)" class="text-[#1a68f0] hover:underline font-semibold flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-panorama text-[11px]"></i>
                        <span>Gambar Header</span>
                    </button>
                    <span class="text-slate-300 dark:text-zinc-700">•</span>
                    <button type="button" wire:click="$set('showLogoModal', true)" class="text-[#1a68f0] hover:underline font-semibold flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-upload text-[11px]"></i>
                        <span>Upload Logo</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Side: Action Buttons -->
        <div class="flex items-center gap-3 shrink-0">
            <!-- Secondary Outline Button: Segarkan Data -->
            <button 
                type="button" 
                wire:click="$refresh" 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-xs sm:text-sm font-semibold text-[#212121] dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-700/60 transition-colors shadow-xs cursor-pointer"
            >
                <i class="fas fa-arrows-rotate text-xs text-[#828282] dark:text-zinc-400"></i>
                <span>Segarkan Data</span>
            </button>

            <!-- Primary VBAT Solid Blue Button: + Ajukan Kampanye -->
            <a 
                href="{{ route('sponsor.campaigns.hero') }}" 
                wire:navigate 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#1a68f0] hover:bg-blue-600 text-white text-xs sm:text-sm font-bold shadow-xs transition-colors cursor-pointer"
            >
                <i class="fas fa-plus text-xs"></i>
                <span>+ Ajukan Kampanye</span>
            </a>
        </div>
    </div>

    {{-- 2. CRITICAL FIX - QUOTA SUMMARY CARDS (NO ANALYTICS) --}}
    {{-- Row of 4 clean, white cards with very soft drop shadows. Each card displays a quota type, relevant minimal icon, and visual progress bar --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($quotas as $key => $quota)
            @php
                $isFull = $quota['remaining'] <= 0;
            @endphp
            <a 
                href="{{ $quota['route'] ?? '#' }}" 
                wire:navigate 
                class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 p-5 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] hover:shadow-[0_8px_24px_-6px_rgba(20,40,90,0.08)] hover:border-[#1a68f0]/40 transition-all group flex flex-col justify-between"
            >
                <div>
                    <!-- Header Card: Minimal Icon + Title -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-[#828282] dark:text-zinc-400 group-hover:text-[#212121] dark:group-hover:text-white transition-colors">
                            {{ $quota['label'] }}
                        </span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-[#1a68f0] dark:text-blue-400 flex items-center justify-center">
                            <i class="fas {{ $quota['icon'] }} text-xs"></i>
                        </div>
                    </div>

                    <!-- Quota Text: "Sisa Kuota: X dari Y" -->
                    <div class="mt-3.5">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xs text-[#828282] dark:text-zinc-400">Sisa Kuota:</span>
                            <span class="text-base font-extrabold text-[#212121] dark:text-white">
                                {{ $quota['remaining'] }} <span class="text-xs font-semibold text-[#828282] dark:text-zinc-400">dari {{ $quota['max'] }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Visual Progress Bar: Thin blue progress bar (thin gray when 0) -->
                <div class="mt-4 pt-1">
                    <div class="w-full bg-slate-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                        <div 
                            class="h-1.5 rounded-full transition-all duration-300 {{ $isFull ? 'bg-slate-300 dark:bg-zinc-700' : 'bg-[#1a68f0]' }}" 
                            style="width: {{ $quota['percent'] }}%"
                        ></div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- 3. BOTTOM SECTION - QUICK OVERVIEW TABLES (Crisp White Borderless Table Cards) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Left Card: Daftar Kampanye Promosi -->
        <div class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3.5 mb-1 border-b border-slate-100 dark:border-zinc-800">
                    <div>
                        <h3 class="font-bold text-base text-[#212121] dark:text-white">Daftar Kampanye Promosi</h3>
                        <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Daftar pengajuan kampanye iklan terbaru Anda</p>
                    </div>
                    <a href="{{ route('sponsor.campaigns.hero') }}" wire:navigate class="text-xs font-semibold text-[#1a68f0] hover:underline flex items-center gap-1">
                        <span>Semua Kampanye</span>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/70 dark:border-zinc-800 text-[11px] font-bold text-[#828282] dark:text-zinc-400 uppercase tracking-wider">
                                <th class="py-3 pr-3">Kampanye</th>
                                <th class="py-3 px-3">Penempatan</th>
                                <th class="py-3 pl-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80">
                            @forelse($campaigns as $camp)
                                @php
                                    $placementName = match($camp->placement_type) {
                                        'hero_slider' => 'Hero Slider',
                                        'shop_horizontal' => 'Horizontal Slider',
                                        'card_slider' => 'Card Slider',
                                        'popup_modal' => 'Pop Up Iklan',
                                        default => ucwords(str_replace('_', ' ', $camp->placement_type)),
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                    <td class="py-3 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-8 rounded-lg bg-slate-100 dark:bg-zinc-800 overflow-hidden border border-slate-200 dark:border-zinc-700 shrink-0 flex items-center justify-center">
                                                <img 
                                                    src="{{ $camp->thumbnail_url }}" 
                                                    alt="{{ $camp->title }}" 
                                                    class="w-full h-full object-cover" 
                                                    onerror="this.src='{{ asset('assets/images/banner_promo_diskon.png') }}'" 
                                                />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-[#212121] dark:text-zinc-100 truncate max-w-[200px]" title="{{ $camp->title }}">
                                                    {{ $camp->title }}
                                                </p>
                                                <p class="text-[11px] text-[#828282] dark:text-zinc-400 capitalize">
                                                    {{ $camp->media_type ?? 'Gambar' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-[#212121] dark:text-zinc-300 font-medium">
                                        {{ $placementName }}
                                    </td>
                                    <td class="py-3 pl-3 text-right">
                                        @if($camp->status === 'approved' || $camp->status === 'active')
                                            <!-- Green badge for Disetujui -->
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                                <i class="fas fa-circle text-[6px]"></i>
                                                <span>Disetujui</span>
                                            </span>
                                        @elseif($camp->status === 'pending')
                                            <!-- Yellow badge for Menunggu Approval -->
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/70 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60">
                                                <i class="fas fa-circle text-[6px]"></i>
                                                <span>Menunggu Approval</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/70 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60">
                                                <i class="fas fa-circle text-[6px]"></i>
                                                <span>Ditolak</span>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-[#828282] dark:text-zinc-500 text-xs">
                                        <i class="fas fa-inbox text-2xl mb-2 text-slate-300 dark:text-zinc-700 block"></i>
                                        Belum ada kampanye iklan yang diajukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Card: Katalog Produk & Suku Cadang Saya -->
        <div class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3.5 mb-2 border-b border-slate-100 dark:border-zinc-800">
                    <div>
                        <h3 class="font-bold text-base text-[#212121] dark:text-white">Katalog Produk & Suku Cadang Saya</h3>
                        <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Daftar item suku cadang yang aktif di marketplace</p>
                    </div>
                    <!-- Subtle blue link: Lihat Semua Produk -->
                    <a href="{{ route('sponsor.products') }}" wire:navigate class="text-xs font-semibold text-[#1a68f0] hover:underline flex items-center gap-1">
                        <span>Lihat Semua Produk</span>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </a>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-zinc-800/80">
                    @forelse($products as $prod)
                        <div class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 px-2 rounded-lg transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Small Product Thumbnail -->
                                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-zinc-800 border border-slate-200/80 dark:border-zinc-700 flex items-center justify-center overflow-hidden shrink-0">
                                    @if($prod->image_path)
                                        <img 
                                            src="{{ str_starts_with($prod->image_path, 'http') ? $prod->image_path : asset($prod->image_path) }}" 
                                            class="w-full h-full object-cover" 
                                            alt="{{ $prod->name }}" 
                                            onerror="this.src='{{ asset('assets/images/product_1.png') }}'" 
                                        />
                                    @else
                                        <i class="fas fa-cube text-[#828282] text-sm"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <!-- Product Name -->
                                    <p class="text-xs font-semibold text-[#212121] dark:text-zinc-100 truncate max-w-[220px]" title="{{ $prod->name }}">
                                        {{ $prod->name }}
                                    </p>
                                    <p class="text-[11px] text-[#828282] dark:text-zinc-400 mt-0.5">
                                        {{ $prod->category ?? 'Sparepart' }}
                                    </p>
                                </div>
                            </div>
                            <!-- Price -->
                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-[#1a68f0] font-mono">
                                    Rp {{ number_format($prod->price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-[#828282] dark:text-zinc-500 text-xs">
                            <i class="fas fa-box-open text-2xl mb-2 text-slate-300 dark:text-zinc-700 block"></i>
                            Belum ada produk aktif di katalog suku cadang Anda.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL GANTI LOGO RESMI SPONSOR (Light/Dark Mode Shopee Style) --}}
    @if ($showLogoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/70 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-white dark:bg-[#242424] border border-slate-200/90 dark:border-[#3F3F46] rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col text-[#212121] dark:text-[#F3F4F6]">
                <!-- Sticky Header -->
                <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-[#3F3F46]">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-image text-[#1a68f0] dark:text-blue-400 text-base"></i>
                        <h2 class="text-base font-bold text-[#212121] dark:text-[#F3F4F6]">Unggah Logo Resmi Sponsor</h2>
                    </div>
                    <button type="button" wire:click="$set('showLogoModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] dark:text-[#9CA3AF] hover:text-[#212121] dark:hover:text-[#F3F4F6] hover:bg-slate-100 dark:hover:bg-[#333338] transition cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <p class="text-xs text-[#828282] dark:text-[#9CA3AF]">
                        Logo ini akan ditampilkan di Beranda, katalog produk, dan kartu identitas sponsor pada aplikasi VBAT.
                    </p>

                    <div class="flex flex-col items-center justify-center p-6 rounded-xl border-2 border-dashed border-slate-300 dark:border-[#3F3F46] bg-slate-50/60 dark:bg-[#18181B]">
                        <div class="w-24 h-24 rounded-xl bg-white dark:bg-[#27272A] border border-slate-200 dark:border-[#3F3F46] flex items-center justify-center overflow-hidden mb-3 shadow-xs">
                            @php
                                $isDashLogoPreviewable = false;
                                $dashLogoPreviewUrl = null;
                                if ($logo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                    try {
                                        if (is_file($logo->getRealPath()) && !is_dir($logo->getRealPath()) && $logo->isPreviewable()) {
                                            $dashLogoPreviewUrl = $logo->temporaryUrl();
                                            $isDashLogoPreviewable = true;
                                        }
                                    } catch (\Throwable $e) {
                                        $isDashLogoPreviewable = false;
                                    }
                                }
                            @endphp
                            @if ($isDashLogoPreviewable && $dashLogoPreviewUrl)
                                <img src="{{ $dashLogoPreviewUrl }}" alt="Preview Baru" class="w-full h-full object-contain p-2">
                            @elseif ($sponsor?->logo_url)
                                <img src="{{ $sponsor->logo_url }}" alt="Logo Saat Ini" class="w-full h-full object-contain p-2" onerror="this.style.display='none';">
                            @else
                                <span class="text-lg font-bold text-[#1a68f0] dark:text-blue-400 uppercase">{{ substr($sponsor?->name ?? 'SP', 0, 2) }}</span>
                            @endif
                        </div>

                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/jpg,image/webp" class="text-xs text-[#828282] dark:text-[#9CA3AF] file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#1a68f0] file:text-white hover:file:bg-blue-600 cursor-pointer">
                        <span class="text-[11px] text-[#828282] dark:text-[#9CA3AF] mt-2">Format: JPG, PNG, atau WEBP (Maksimal 2MB)</span>
                        <div wire:loading wire:target="logo" class="text-xs text-[#1a68f0] dark:text-blue-400 font-semibold mt-1">Mengunggah berkas...</div>
                        @error('logo')
                            <span class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-2.5 p-4 border-t border-slate-200 dark:border-[#3F3F46] bg-slate-50/50 dark:bg-[#242424]">
                    <button type="button" wire:click="$set('showLogoModal', false)" class="px-4 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-[#333338] border border-slate-200 dark:border-[#3F3F46] text-[#212121] dark:text-[#F3F4F6] hover:bg-slate-100 dark:hover:bg-[#3F3F46] transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" wire:click="saveLogo" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#1a68f0] hover:bg-blue-600 text-white shadow-xs transition disabled:opacity-50 cursor-pointer">
                        Simpan Logo
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL UBAH PROFIL SPONSOR (Light/Dark Mode Shopee Style) --}}
    @if ($showProfileModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/70 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-white dark:bg-[#242424] border border-slate-200/90 dark:border-[#3F3F46] rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col text-[#212121] dark:text-[#F3F4F6]">
                <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-[#3F3F46]">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-store text-[#1a68f0] dark:text-blue-400 text-base"></i>
                        <h2 class="text-base font-bold text-[#212121] dark:text-[#F3F4F6]">Ubah Profil Sponsor</h2>
                    </div>
                    <button type="button" wire:click="$set('showProfileModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] dark:text-[#9CA3AF] hover:text-[#212121] dark:hover:text-[#F3F4F6] hover:bg-slate-100 dark:hover:bg-[#333338] transition cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1">Deskripsi Singkat</label>
                        <textarea wire:model="description" rows="3" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0]" placeholder="Ceritakan singkat tentang toko atau produk Anda"></textarea>
                        @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1">Website Resmi</label>
                        <input type="url" wire:model="website_url" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0]" placeholder="https://website-toko.com">
                        @error('website_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1">Tautan Marketplace (Shopee / Tokopedia)</label>
                        <input type="url" wire:model="marketplace_url" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0]" placeholder="https://shopee.co.id/nama-toko">
                        @error('marketplace_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        <span class="text-[11px] text-[#828282] dark:text-[#9CA3AF] mt-1 block">Tombol di aplikasi hanya muncul untuk tautan yang terisi.</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 p-4 border-t border-slate-200 dark:border-[#3F3F46] bg-slate-50/50 dark:bg-[#242424]">
                    <button type="button" wire:click="$set('showProfileModal', false)" class="px-4 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-[#333338] border border-slate-200 dark:border-[#3F3F46] text-[#212121] dark:text-[#F3F4F6] hover:bg-slate-100 dark:hover:bg-[#3F3F46] transition cursor-pointer">Batal</button>
                    <button type="button" wire:click="saveProfile" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#1a68f0] hover:bg-blue-600 text-white shadow-xs transition disabled:opacity-50 cursor-pointer">Simpan Profil</button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL UNGGAH GAMBAR HEADER (Light/Dark Mode Shopee Style) --}}
    @if ($showHeaderModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/70 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-white dark:bg-[#242424] border border-slate-200/90 dark:border-[#3F3F46] rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col text-[#212121] dark:text-[#F3F4F6]">
                <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-[#3F3F46]">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-panorama text-[#1a68f0] dark:text-blue-400 text-base"></i>
                        <h2 class="text-base font-bold text-[#212121] dark:text-[#F3F4F6]">Gambar Header Halaman Sponsor</h2>
                    </div>
                    <button type="button" wire:click="$set('showHeaderModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] dark:text-[#9CA3AF] hover:text-[#212121] dark:hover:text-[#F3F4F6] hover:bg-slate-100 dark:hover:bg-[#333338] transition cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-xs text-[#828282] dark:text-[#9CA3AF]">
                        Gambar ini menjadi banner latar atas halaman toko sponsor Anda. Bila dikosongkan, halaman memakai warna standar.
                    </p>

                    <div class="flex flex-col items-center justify-center p-6 rounded-xl border-2 border-dashed border-slate-300 dark:border-[#3F3F46] bg-slate-50/60 dark:bg-[#18181B]">
                        <div class="w-full h-24 rounded-xl bg-white dark:bg-[#27272A] border border-slate-200 dark:border-[#3F3F46] flex items-center justify-center overflow-hidden mb-3 shadow-xs">
                            @php
                                $isDashHeaderPreviewable = false;
                                $dashHeaderPreviewUrl = null;
                                if ($headerImage instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                    try {
                                        if (is_file($headerImage->getRealPath()) && !is_dir($headerImage->getRealPath()) && $headerImage->isPreviewable()) {
                                            $dashHeaderPreviewUrl = $headerImage->temporaryUrl();
                                            $isDashHeaderPreviewable = true;
                                        }
                                    } catch (\Throwable $e) {
                                        $isDashHeaderPreviewable = false;
                                    }
                                }
                            @endphp
                            @if ($isDashHeaderPreviewable && $dashHeaderPreviewUrl)
                                <img src="{{ $dashHeaderPreviewUrl }}" alt="Preview Header" class="w-full h-full object-cover">
                            @elseif ($sponsor?->header_image_url_full)
                                <img src="{{ $sponsor->header_image_url_full }}" alt="Header Saat Ini" class="w-full h-full object-cover" onerror="this.style.display='none';">
                            @else
                                <span class="text-xs font-semibold text-[#828282] dark:text-[#9CA3AF]">BELUM ADA GAMBAR HEADER</span>
                            @endif
                        </div>
                        <input type="file" wire:model="headerImage" accept="image/png,image/jpeg,image/jpg,image/webp" class="text-xs text-[#828282] dark:text-[#9CA3AF] file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#1a68f0] file:text-white hover:file:bg-blue-600 cursor-pointer">
                        <span class="text-[11px] text-[#828282] dark:text-[#9CA3AF] mt-2">Format: JPG, PNG, atau WEBP (Maksimal 4MB)</span>
                        @error('headerImage') <span class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 p-4 border-t border-slate-200 dark:border-[#3F3F46] bg-slate-50/50 dark:bg-[#242424]">
                    <button type="button" wire:click="$set('showHeaderModal', false)" class="px-4 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-[#333338] border border-slate-200 dark:border-[#3F3F46] text-[#212121] dark:text-[#F3F4F6] hover:bg-slate-100 dark:hover:bg-[#3F3F46] transition cursor-pointer">Batal</button>
                    <button type="button" wire:click="saveHeaderImage" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#1a68f0] hover:bg-blue-600 text-white shadow-xs transition disabled:opacity-50 cursor-pointer">Simpan Gambar Header</button>
                </div>
            </div>
        </div>
    @endif
</div>