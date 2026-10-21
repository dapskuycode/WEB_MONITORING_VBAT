<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\BestDeal;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

new class extends Component
{
    use WithFileUploads;

    public $products;
    public $sponsor;
    public $showModal = false;
    public $editingId = null;
    public $flashMessage = '';

    protected $listeners = ['trigger-create-product' => 'openCreateModal'];

    // Filter
    public $search = '';

    // Form fields
    public $name = '';
    public $description = '';
    public $price = '';
    public $discount_price = '';
    public $imageFile;
    public $image_url = '';
    public $existing_image = '';
    public $shopee_url = '';
    public $tokopedia_url = '';
    public $is_active = true;

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
            $query = SponsorProduct::with('bestDeals')->where('sponsor_id', $this->sponsor->id)->latest();

            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            }

            $this->products = $query->get();
        } else {
            $this->products = collect();
        }
    }

    public function updatedSearch()
    {
        $this->loadData();
    }

    public function openCreateModal()
    {
        if ($this->sponsor && $this->sponsor->hasReachedProductQuota()) {
            $max = $this->sponsor->getMonthlyProductQuota();
            $this->flashMessage = "Batas kuota produk bulanan Anda ({$max} produk) telah tercapai. Kuota akan di-reset pada tanggal 1 bulan depan.";
            return;
        }

        $this->reset(['editingId', 'name', 'description', 'price', 'discount_price', 'imageFile', 'image_url', 'existing_image', 'shopee_url', 'tokopedia_url']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function editProduct($id)
    {
        if (!$this->sponsor) return;

        $p = SponsorProduct::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $this->editingId = $p->id;
        $this->name = $p->name;
        $this->description = $p->description ?? '';
        $this->price = (int)$p->price;
        $this->discount_price = $p->discount_price ? (int)$p->discount_price : '';
        $this->existing_image = $p->image_path ?? '';
        $this->image_url = (str_starts_with($p->image_path ?? '', 'http')) ? $p->image_path : '';
        $this->imageFile = null;
        $this->shopee_url = $p->shopee_url ?? '';
        $this->tokopedia_url = $p->tokopedia_url ?? '';
        $this->is_active = (bool)$p->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        if (!$this->sponsor) return;

        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'imageFile' => 'nullable|image|max:3072', // max 3MB
            'image_url' => 'nullable|string',
            'shopee_url' => 'nullable|url',
            'tokopedia_url' => 'nullable|url',
        ], [
            'name.required' => 'Nama produk suku cadang/alat wajib diisi.',
            'price.required' => 'Harga produk wajib diisi.',
            'imageFile.image' => 'File harus berupa gambar (jpg, png, webp).',
            'imageFile.max' => 'Ukuran foto maksimal 3MB.',
            'shopee_url.url' => 'Format link Shopee tidak valid.',
            'tokopedia_url.url' => 'Format link Tokopedia tidak valid.',
        ]);

        $imagePath = $this->existing_image ?: 'assets/images/product_1.png';

        if ($this->imageFile) {
            $imagePath = $this->imageFile->store('products', 'public');
        } elseif (!empty($this->image_url)) {
            $imagePath = $this->image_url;
        }

        $slug = Str::slug($this->name);
        if (!$this->editingId) {
            $slug .= '-' . rand(100, 999);
        }

        $data = [
            'sponsor_id' => $this->sponsor->id,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => !empty($this->discount_price) ? $this->discount_price : null,
            'image_path' => $imagePath,
            'shopee_url' => $this->shopee_url,
            'tokopedia_url' => $this->tokopedia_url,
            'is_active' => $this->is_active,
        ];

        if (!$this->editingId && $this->sponsor && $this->sponsor->hasReachedProductQuota()) {
            $max = $this->sponsor->getMonthlyProductQuota();
            $this->addError('name', "Batas kuota produk bulanan ({$max} produk) untuk tier ini telah tercapai.");
            return;
        }

        if ($this->editingId) {
            $product = SponsorProduct::where('sponsor_id', $this->sponsor->id)->findOrFail($this->editingId);
            $product->update($data);
        } else {
            $data['order'] = SponsorProduct::where('sponsor_id', $this->sponsor->id)->max('order') + 1;
            SponsorProduct::create($data);
        }

        $this->showModal = false;
        $this->loadData();
    }

    public function submitToBestDeal($productId)
    {
        if (!$this->sponsor) return;

        if (strtolower($this->sponsor->tier ?? '') === 'kontribusi') {
            $this->flashMessage = 'Tier Kontribusi tidak memiliki slot Best Deal. Silakan hubungi admin untuk upgrade tier.';
            return;
        }

        $product = SponsorProduct::where('sponsor_id', $this->sponsor->id)->findOrFail($productId);

        $activeBestDeal = BestDeal::active()->first();
        if (!$activeBestDeal) {
            $activeBestDeal = BestDeal::create([
                'title' => 'BEST DEALS VBAT',
                'description' => 'Program promo produk pilihan terbaik dari mitra resmi.',
                'is_active' => true,
                'selection_type' => 'auto',
            ]);
        }

        $tierRank = match(strtolower($this->sponsor->tier ?? '')) {
            'diamond' => 1,
            'platinum' => 2,
            'gold' => 3,
            'silver' => 4,
            'bronze' => 5,
            default => 6,
        };

        $activeBestDeal->products()->syncWithoutDetaching([
            $product->id => [
                'badge_text' => 'BEST DEAL ' . strtoupper($this->sponsor->tier ?? ''),
                'order' => $tierRank,
            ]
        ]);

        $this->loadData();
        $this->flashMessage = "Produk '{$product->name}' berhasil diajukan dan langsung tayang di Best Deal!";
    }

    public function removeFromBestDeal($productId)
    {
        if (!$this->sponsor) return;

        $product = SponsorProduct::where('sponsor_id', $this->sponsor->id)->findOrFail($productId);
        $activeBestDeal = BestDeal::active()->first();
        if ($activeBestDeal) {
            $activeBestDeal->products()->detach($product->id);
        }

        $this->loadData();
        $this->flashMessage = "Produk '{$product->name}' telah ditarik dari Best Deal.";
    }

    public function toggleStatus($id)
    {
        if (!$this->sponsor) return;

        $p = SponsorProduct::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $p->is_active = !$p->is_active;
        $p->save();
        $this->loadData();
    }

    public function deleteProduct($id)
    {
        if (!$this->sponsor) return;

        $p = SponsorProduct::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $p->delete();
        $this->loadData();
    }
};
?>

<div class="space-y-6 text-[#212121]">
    {{-- Toast Flash Message --}}
    @if ($flashMessage)
        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-200 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <i class="fas fa-info-circle text-[#1a68f0] text-sm"></i>
                <span class="font-medium">{{ $flashMessage }}</span>
            </div>
            <button wire:click="$set('flashMessage', '')" class="text-blue-500 hover:text-blue-700 font-bold cursor-pointer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Top Info Cards (Horizontal Layout - Two Side-by-Side Cards) --}}
    @php
        $quota = $sponsor?->getMonthlyProductQuota() ?? 50;
        $isUnlimited = is_null($quota);
        $usedCount = $sponsor ? $sponsor->getCurrentMonthProductsCount() : $products->count();
        if ($usedCount === 0 && $products->count() > 0) {
            $usedCount = $products->count();
        }
        $tierName = ucfirst($sponsor?->tier ?? 'Gold');
        $quotaReached = $sponsor ? $sponsor->hasReachedProductQuota() : false;
        $percentage = (!$isUnlimited && $quota > 0) ? min(100, round(($usedCount / $quota) * 100)) : 0;
    @endphp

    {{-- Top Info Cards (Horizontal Layout - Two Side-by-Side Cards) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Left Card: Quota --}}
        <div class="bg-white dark:bg-zinc-900 rounded-[12px] p-5 border border-slate-200/80 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#828282] uppercase tracking-wider">Kuota Produk Bulanan</span>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $quotaReached ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40' : 'bg-blue-50 text-[#1a68f0] dark:bg-blue-950/40' }}">
                    {{ $tierName }} Tier
                </span>
            </div>
            <div>
                <p class="text-base sm:text-lg font-bold text-[#212121] dark:text-white">
                    Terpakai <span class="text-[#1a68f0]">{{ $usedCount }}</span> dari {{ $quota }} ({{ $tierName }} Tier)
                </p>
                <div class="w-full bg-slate-100 dark:bg-zinc-800 rounded-full h-2 mt-3 overflow-hidden">
                    <div class="h-2 rounded-full bg-[#1a68f0] transition-all duration-500 ease-out" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
            <p class="text-[11px] text-[#828282] dark:text-zinc-500">
                Kuota produk di-reset otomatis setiap tanggal 1 bulan kalender.
            </p>
        </div>

        {{-- Right Card: Visibility Info --}}
        <div class="bg-white dark:bg-zinc-900 rounded-[12px] p-5 border border-slate-200/80 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#828282] uppercase tracking-wider">Informasi Visibilitas</span>
                <div class="w-6 h-6 rounded-full bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center">
                    <i class="fas fa-sparkles text-amber-500 text-xs"></i>
                </div>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-[#212121] dark:text-zinc-200 leading-relaxed font-medium">
                    Produk aktif akan tampil di katalog Shop. Admin dapat memilih produk Anda untuk program Best Deal.
                </p>
            </div>
            <p class="text-[11px] text-[#828282] dark:text-zinc-500">
                Tingkatkan penjualan dengan menyertakan link toko resmi marketplace (Shopee / Tokopedia).
            </p>
        </div>
    </div>

    {{-- Data Table Container - Sleek, Spacious, Clean Mockup --}}
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] overflow-hidden">
        {{-- Top of Table: Search input & Total count --}}
        <div class="p-4 border-b border-slate-100 dark:border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-80">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fas fa-search text-xs"></i>
                </div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nama produk..." 
                    class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm text-[#212121] dark:text-zinc-100 placeholder-slate-400 bg-slate-50/70 dark:bg-zinc-800/60 rounded-xl border border-slate-200 dark:border-zinc-700 focus:outline-none focus:bg-white dark:focus:bg-zinc-800 focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all"
                >
            </div>
            <div class="text-xs sm:text-sm font-semibold text-[#828282] dark:text-zinc-400 self-end sm:self-center">
                Total: <span class="text-[#212121] dark:text-white font-bold">{{ $products->count() }} Produk</span>
            </div>
        </div>

        {{-- Borderless Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#F8F9FA] dark:bg-zinc-800/40 text-[11px] font-bold text-[#828282] uppercase tracking-wider border-b border-slate-100 dark:border-zinc-800">
                        <th class="px-5 py-3.5">Nama Produk</th>
                        <th class="px-5 py-3.5">Harga</th>
                        <th class="px-5 py-3.5">Marketplace</th>
                        <th class="px-5 py-3.5 text-center">Program</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                    @forelse($products as $prod)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                            {{-- Column 1: Nama Produk (Thumbnail + Bold Title ONLY) --}}
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-zinc-800 flex items-center justify-center shrink-0 border border-slate-200/80 dark:border-zinc-700 overflow-hidden">
                                        @php
                                            $img = $prod->image_path;
                                            if ($img && !str_starts_with($img, 'http') && !str_starts_with($img, 'assets/')) {
                                                $img = asset('storage/' . $img);
                                            } elseif (!$img) {
                                                $img = null;
                                            }
                                        @endphp
                                        @if($img)
                                            <img src="{{ $img }}" alt="{{ $prod->name }}" class="w-full h-full object-cover">
                                        @else
                                            <i class="fas fa-image text-slate-400 text-sm"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0 pr-2">
                                        <p class="font-bold text-[#212121] dark:text-white truncate text-xs sm:text-sm" title="{{ $prod->name }}">
                                            {{ $prod->name }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Column 2: Harga (Final price bold + crossed out + discount badge) --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($prod->discount_price && $prod->discount_price < $prod->price)
                                    @php
                                        $percent = round((($prod->price - $prod->discount_price) / $prod->price) * 100);
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-[#212121] dark:text-white text-xs sm:text-sm font-mono">
                                            Rp {{ number_format($prod->discount_price, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[11px] text-[#828282] line-through font-mono">
                                            Rp {{ number_format($prod->price, 0, ',', '.') }}
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-600 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800">
                                            -{{ $percent }}%
                                        </span>
                                    </div>
                                @else
                                    <div class="font-bold text-[#212121] dark:text-white text-xs sm:text-sm font-mono">
                                        Rp {{ number_format($prod->price, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>

                            {{-- Column 3: Marketplace (Minimal pill badges) --}}
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($prod->shopee_url)
                                        <a 
                                            href="{{ $prod->shopee_url }}" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border border-orange-300 text-orange-600 bg-orange-50/50 hover:bg-orange-100/70 dark:bg-orange-950/30 dark:border-orange-700 dark:text-orange-400 transition-colors"
                                            title="Buka Toko Shopee"
                                        >
                                            <i class="fas fa-shopping-bag text-[10px]"></i>
                                            <span>Shopee</span>
                                        </a>
                                    @endif
                                    @if($prod->tokopedia_url)
                                        <a 
                                            href="{{ $prod->tokopedia_url }}" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border border-emerald-300 text-emerald-600 bg-emerald-50/50 hover:bg-emerald-100/70 dark:bg-emerald-950/30 dark:border-emerald-700 dark:text-emerald-400 transition-colors"
                                            title="Buka Toko Tokopedia"
                                        >
                                            <i class="fas fa-store text-[10px]"></i>
                                            <span>Tokopedia</span>
                                        </a>
                                    @endif
                                    @if(!$prod->shopee_url && !$prod->tokopedia_url)
                                        <span class="text-[#828282] dark:text-zinc-500 text-xs">-</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Column 4: Program (Yellow badge for Best Deal or gray -) --}}
                            <td class="px-5 py-3.5 text-center">
                                @php
                                    $inBestDeal = $prod->bestDeals()->where('is_active', true)->exists();
                                @endphp
                                @if($inBestDeal)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-700">
                                        <i class="fas fa-star text-[9px] text-amber-500"></i>
                                        <span>Best Deal</span>
                                    </span>
                                @else
                                    <span class="text-[#828282] dark:text-zinc-500 font-semibold">-</span>
                                @endif
                            </td>

                            {{-- Column 5: Status (Pastel green badge) --}}
                            <td class="px-5 py-3.5 text-center">
                                <button 
                                    type="button" 
                                    wire:click="toggleStatus({{ $prod->id }})" 
                                    class="cursor-pointer focus:outline-none"
                                    title="Klik untuk ubah status"
                                >
                                    @if($prod->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F5E9] text-[#2E7D32] dark:bg-emerald-950/60 dark:text-emerald-300">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400">
                                            Nonaktif
                                        </span>
                                    @endif
                                </button>
                            </td>

                            {{-- Column 6: Aksi (Icon-only buttons in subtle gray) --}}
                            <td class="px-5 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button 
                                        type="button"
                                        wire:click="editProduct({{ $prod->id }})" 
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#1a68f0] hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer" 
                                        title="Edit Produk"
                                    >
                                        <i class="fas fa-pencil-alt text-xs"></i>
                                    </button>
                                    <button 
                                        type="button"
                                        wire:click="deleteProduct({{ $prod->id }})" 
                                        wire:confirm="Yakin ingin menghapus produk ini dari katalog Anda?" 
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[#828282] hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer" 
                                        title="Hapus Produk"
                                    >
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-[#828282] dark:text-zinc-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <i class="fas fa-cube text-xl"></i>
                                </div>
                                <p class="font-bold text-sm text-[#212121] dark:text-white">Belum ada produk di katalog Anda.</p>
                                <p class="text-xs text-[#828282] mt-1">Klik tombol "+ Tambah Produk Baru" untuk menambahkan suku cadang resmi ke katalog.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Clean Modal Form Tambah / Edit Produk (Shopee Light Enterprise) --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl p-6 space-y-5">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-[#212121] dark:text-white">
                        {{ $editingId ? 'Edit Produk Suku Cadang' : 'Tambah Produk Baru' }}
                    </h3>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">
                        Brand Mitra: <span class="font-semibold text-[#1a68f0]">{{ $sponsor->name ?? 'Mitra Sponsor' }}</span>
                    </p>
                </div>
                <button 
                    type="button" 
                    wire:click="$set('showModal', false)" 
                    class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form wire:submit="save" class="space-y-4">
                {{-- Nama Produk --}}
                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-zinc-300 mb-1">Nama Produk *</label>
                    <input 
                        wire:model="name" 
                        type="text" 
                        placeholder="Contoh: Baterai Infinix Hot 9/10/11 Play BL-58BX Original" 
                        class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-xl focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 focus:outline-none dark:text-white"
                    >
                    @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Harga & Harga Diskon --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-zinc-300 mb-1">Harga Normal (Rp) *</label>
                        <input 
                            wire:model="price" 
                            type="number" 
                            placeholder="Contoh: 145000" 
                            class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-xl focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 focus:outline-none dark:text-white font-mono"
                        >
                        @error('price') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-zinc-300 mb-1">Harga Diskon / Promo (Rp) <span class="text-[#828282] font-normal">(Opsional)</span></label>
                        <input 
                            wire:model="discount_price" 
                            type="number" 
                            placeholder="Contoh: 123250" 
                            class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-xl focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 focus:outline-none dark:text-white font-mono"
                        >
                        @error('discount_price') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Foto Produk --}}
                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-zinc-300 mb-1">Foto Thumbnail Produk</label>
                    <div class="flex items-center gap-3">
                        @if($imageFile)
                            <div class="w-12 h-12 rounded-xl border border-slate-200 dark:border-zinc-700 overflow-hidden shrink-0">
                                <img src="{{ $imageFile->temporaryUrl() }}" class="w-full h-full object-cover">
                            </div>
                        @elseif($existing_image)
                            <div class="w-12 h-12 rounded-xl border border-slate-200 dark:border-zinc-700 overflow-hidden shrink-0">
                                @php
                                    $prevImg = $existing_image;
                                    if (!str_starts_with($prevImg, 'http') && !str_starts_with($prevImg, 'assets/')) {
                                        $prevImg = asset('storage/' . $prevImg);
                                    } elseif (str_starts_with($prevImg, 'assets/')) {
                                        $prevImg = asset($prevImg);
                                    }
                                @endphp
                                <img src="{{ $prevImg }}" class="w-full h-full object-cover">
                            </div>
                        @endif
                        <input 
                            wire:model="imageFile" 
                            type="file" 
                            accept="image/*" 
                            class="w-full text-xs text-[#828282] file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#1a68f0] hover:file:bg-blue-100 dark:file:bg-blue-950 dark:file:text-blue-300 cursor-pointer"
                        >
                    </div>
                    @error('imageFile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Marketplace URLs --}}
                <div class="space-y-3 p-3.5 bg-slate-50 dark:bg-zinc-800/40 rounded-xl border border-slate-200/80 dark:border-zinc-700/60">
                    <span class="text-xs font-bold text-[#212121] dark:text-zinc-200 block">Toko Marketplace Resmi:</span>
                    <div>
                        <label class="block text-[11px] font-semibold text-orange-600 dark:text-orange-400 mb-1">
                            <i class="fas fa-shopping-bag mr-1"></i> Tautan Shopee:
                        </label>
                        <input 
                            wire:model="shopee_url" 
                            type="url" 
                            placeholder="https://shopee.co.id/nama-toko/produk-xyz" 
                            class="w-full px-3 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-lg focus:border-orange-500 focus:outline-none dark:text-white"
                        >
                        @error('shopee_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 mb-1">
                            <i class="fas fa-store mr-1"></i> Tautan Tokopedia:
                        </label>
                        <input 
                            wire:model="tokopedia_url" 
                            type="url" 
                            placeholder="https://tokopedia.com/nama-toko/produk-xyz" 
                            class="w-full px-3 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-lg focus:border-emerald-500 focus:outline-none dark:text-white"
                        >
                        @error('tokopedia_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Deskripsi (Optional for editing/detail, but kept clean) --}}
                <div>
                    <label class="block text-xs font-bold text-[#212121] dark:text-zinc-300 mb-1">Spesifikasi Tambahan <span class="text-[#828282] font-normal">(Opsional)</span></label>
                    <textarea 
                        wire:model="description" 
                        rows="2" 
                        placeholder="Garansi, kecocokan model smartphone, atau petunjuk pemakaian..." 
                        class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-xl focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 focus:outline-none dark:text-white"
                    ></textarea>
                    @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Checkbox Aktif --}}
                <div class="flex items-center gap-2 pt-1">
                    <input 
                        wire:model="is_active" 
                        type="checkbox" 
                        id="is_active" 
                        class="rounded border-slate-300 text-[#1a68f0] focus:ring-[#1a68f0] w-4 h-4 cursor-pointer"
                    >
                    <label for="is_active" class="text-xs font-medium text-[#212121] dark:text-zinc-300 cursor-pointer">
                        Tampilkan produk ini di katalog Shop aplikasi (Status Aktif)
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <button 
                        type="button" 
                        wire:click="$set('showModal', false)" 
                        class="px-4 py-2 text-xs font-semibold text-[#828282] hover:text-[#212121] dark:text-zinc-400 dark:hover:text-white transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 bg-[#1a68f0] hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition cursor-pointer"
                    >
                        Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

