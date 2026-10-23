<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Support\Str;

new class extends Component
{
    use WithFileUploads;

    public $products;
    public $sponsors;
    public $showModal = false;
    public $editingId = null;

    // Filters
    public $search = '';
    public $sponsorFilter = '';

    // Form fields
    public $sponsor_id = '';
    public $name = '';
    public $description = '';
    public $price = '';
    public $discount_price = '';
    public $imageFile;
    public $image_url = '';
    public $existing_image = '';
    public $shopee_url = '';
    public $tokopedia_url = '';
    public $is_featured = false;
    public $is_active = true;
    public $order = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->sponsors = Sponsor::with('sponsorTier')
            ->orderByRaw("CASE WHEN LOWER(slug) = 'quantum' OR LOWER(name) = 'quantum' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $query = SponsorProduct::with(['sponsor.sponsorTier'])->latest();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->sponsorFilter)) {
            $query->where('sponsor_id', $this->sponsorFilter);
        }

        $this->products = $query->get();
    }

    public function updatedSearch()
    {
        $this->loadData();
    }

    public function updatedSponsorFilter()
    {
        $this->loadData();
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'name', 'description', 'price', 'discount_price', 'imageFile', 'image_url', 'existing_image', 'shopee_url', 'tokopedia_url']);
        $quantum = Sponsor::where('slug', 'quantum')->orWhere('name', 'Quantum')->first();
        $this->sponsor_id = $quantum ? $quantum->id : ($this->sponsors->first()?->id ?? '');
        $this->is_featured = false;
        $this->is_active = true;
        $this->order = 0;
        $this->showModal = true;
    }

    public function editProduct($id)
    {
        $p = SponsorProduct::findOrFail($id);
        $this->editingId = $p->id;
        $this->sponsor_id = $p->sponsor_id;
        $this->name = $p->name;
        $this->description = $p->description ?? '';
        $this->price = (int)$p->price;
        $this->discount_price = $p->discount_price ? (int)$p->discount_price : '';
        $this->existing_image = $p->image_path ?? '';
        $this->image_url = (str_starts_with($p->image_path ?? '', 'http')) ? $p->image_path : '';
        $this->imageFile = null;
        $this->shopee_url = $p->shopee_url ?? '';
        $this->tokopedia_url = $p->tokopedia_url ?? '';
        $this->is_featured = (bool)$p->is_featured;
        $this->is_active = (bool)$p->is_active;
        $this->order = $p->order ?? 0;
        $this->showModal = true;
    }

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

    public function updatedImageFile()
    {
        if ($this->imageFile && !$this->isValidUploadedFile($this->imageFile)) {
            $this->imageFile = null;
            $this->addError('imageFile', 'Berkas foto produk gagal diproses oleh server.');
        }
    }

    public function save()
    {
        if ($this->imageFile && !$this->isValidUploadedFile($this->imageFile)) {
            $this->imageFile = null;
            $this->addError('imageFile', 'Berkas foto produk tidak valid atau gagal terunggah ke server.');
            return;
        }

        $this->validate([
            'sponsor_id' => 'required|exists:sponsors,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'imageFile' => 'nullable|image|max:3072', // max 3MB
            'image_url' => 'nullable|string',
            'shopee_url' => 'nullable|url',
            'tokopedia_url' => 'nullable|url',
        ]);

        $imagePath = $this->existing_image ?: 'assets/images/product_1.png';

        if ($this->imageFile && $this->isValidUploadedFile($this->imageFile)) {
            $imagePath = $this->imageFile->store('products', 'public');
        } elseif (!empty($this->image_url)) {
            $imagePath = $this->image_url;
        }

        $slug = Str::slug($this->name);
        if (!$this->editingId) {
            $slug .= '-' . rand(100, 999);
        }

        $data = [
            'sponsor_id' => $this->sponsor_id,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => !empty($this->discount_price) ? $this->discount_price : null,
            'image_path' => $imagePath,
            'shopee_url' => $this->shopee_url,
            'tokopedia_url' => $this->tokopedia_url,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'order' => (int)$this->order,
        ];

        if ($this->editingId) {
            $product = SponsorProduct::findOrFail($this->editingId);
            $product->update($data);
        } else {
            SponsorProduct::create($data);
        }

        $this->showModal = false;
        $this->loadData();
    }

    public function toggleStatus($id)
    {
        $p = SponsorProduct::findOrFail($id);
        $p->is_active = !$p->is_active;
        $p->save();
        $this->loadData();
    }

    public function toggleFeatured($id)
    {
        $p = SponsorProduct::findOrFail($id);
        $p->is_featured = !$p->is_featured;
        $p->save();
        $this->loadData();
    }

    public function deleteProduct($id)
    {
        SponsorProduct::destroy($id);
        $this->loadData();
    }
};
?>

<div class="space-y-6 text-[#212121] dark:text-zinc-100">
    <!-- Search & Filter Bar with Upload Button -->
    <div class="p-3.5 sm:p-4 bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3 w-full">
        <!-- Search Input with Magnifying Glass -->
        <div class="relative flex-1 w-full min-w-0 sm:min-w-[280px] md:min-w-[360px] lg:min-w-[420px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-zinc-500">
                <i class="fas fa-search text-sm"></i>
            </div>
            <input 
                type="text" 
                wire:model.live.debounce.250ms="search" 
                placeholder="Cari nama part, SKU, atau deskripsi produk..." 
                class="w-full pl-10 pr-9 py-2 sm:py-2.5 text-xs sm:text-sm text-[#212121] dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 bg-slate-50/70 dark:bg-zinc-800/80 rounded-xl border border-slate-200 dark:border-zinc-700 focus:outline-none focus:bg-white dark:focus:bg-zinc-800 focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/15 transition-all shadow-2xs"
            >
            @if(!empty($search))
                <button 
                    type="button" 
                    wire:click="$set('search', '')" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 cursor-pointer"
                    title="Hapus pencarian"
                >
                    <i class="fas fa-times-circle text-xs"></i>
                </button>
            @endif
        </div>

        <!-- Filter Sponsor + Upload Button -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0 w-full md:w-auto">
            <select 
                wire:model.live="sponsorFilter" 
                class="w-full sm:w-auto px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-medium rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-[#212121] dark:text-zinc-200 focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/15 cursor-pointer shadow-2xs transition"
            >
                <option value="">Filter: Semua Sponsor</option>
                @foreach($sponsors as $sp)
                    <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                @endforeach
            </select>

            <button 
                type="button"
                wire:click="openCreateModal" 
                class="inline-flex items-center justify-center gap-2 px-4 py-2 sm:py-2.5 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors cursor-pointer shrink-0"
            >
                <i class="fas fa-plus text-xs"></i>
                <span>Upload Produk Baru</span>
            </button>
        </div>
    </div>

    <!-- Data Table - Clean Enterprise Content Card (border-radius: 12px, soft enterprise shadow) -->
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[650px]">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-zinc-800 bg-[#F8F9FA] dark:bg-zinc-800/40 text-[11px] font-bold text-[#828282] uppercase tracking-wider">
                        <th class="px-5 py-3.5">Produk</th>
                        <th class="px-5 py-3.5">Mitra Sponsor</th>
                        <th class="px-5 py-3.5">Harga & Diskon</th>
                        <th class="px-5 py-3.5">Marketplace</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                    @forelse($products as $prod)
                    @php
                        $displayImg = $prod->image_path;
                        if ($displayImg && !str_starts_with($displayImg, 'http') && !str_starts_with($displayImg, 'assets/')) {
                            $displayImg = asset('storage/' . $displayImg);
                        }
                        $tierName = strtolower($prod->sponsor?->sponsorTier?->slug ?? $prod->sponsor?->tier ?? 'kontribusi');
                        $tierLabel = strtoupper($prod->sponsor?->sponsorTier?->name ?? $prod->sponsor?->tier ?? 'KONTRIBUSI');
                    @endphp
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/30 transition-colors">
                        <!-- Column 1: Produk (Thumbnail + ONLY Product Name) -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[8px] overflow-hidden bg-slate-50 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 shrink-0 flex items-center justify-center">
                                    @if($displayImg)
                                        <img src="{{ $displayImg }}" alt="{{ $prod->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <span class="font-semibold text-[#212121] dark:text-zinc-100 line-clamp-1" title="{{ $prod->name }}">
                                    {{ $prod->name }}
                                </span>
                            </div>
                        </td>

                        <!-- Column 2: Mitra Sponsor (Sponsor Name + Pastel Badge for Tier) -->
                        <td class="px-5 py-3.5">
                            <div>
                                <span class="font-medium text-[#212121] dark:text-zinc-200 block truncate max-w-[180px]">
                                    {{ $prod->sponsor?->name ?? 'Tanpa Sponsor' }}
                                </span>
                                <span class="inline-block mt-0.5 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-wider
                                    @if($tierName === 'diamond' || $tierName === 'platinum') bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300
                                    @elseif($tierName === 'gold') bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300
                                    @elseif($tierName === 'silver') bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300
                                    @elseif($tierName === 'bronze') bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300
                                    @else bg-blue-50 text-[#1a68f0] dark:bg-blue-950/40 dark:text-blue-300 @endif">
                                    {{ $tierLabel }}
                                </span>
                            </div>
                        </td>

                        <!-- Column 3: Harga & Diskon (Promo price bold, strikethrough original + clean discount badge) -->
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @if($prod->discount_price && $prod->discount_price < $prod->price)
                                @php
                                    $pct = round((($prod->price - $prod->discount_price) / $prod->price) * 100);
                                @endphp
                                <div class="font-bold text-[#212121] dark:text-zinc-100">
                                    Rp {{ number_format($prod->discount_price, 0, ',', '.') }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5 text-[11px]">
                                    <span class="line-through text-[#828282]">
                                        Rp {{ number_format($prod->price, 0, ',', '.') }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded-md bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 font-bold text-[10px]">
                                        -{{ $pct }}%
                                    </span>
                                </div>
                            @else
                                <div class="font-bold text-[#212121] dark:text-zinc-100">
                                    Rp {{ number_format($prod->price, 0, ',', '.') }}
                                </div>
                            @endif
                        </td>

                        <!-- Column 4: Marketplace (Subtle pill-shaped badges) -->
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                @if($prod->shopee_url)
                                    <a href="{{ $prod->shopee_url }}" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center px-2.5 py-0.5 rounded-full border border-orange-200 bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-800 text-[11px] font-medium hover:bg-orange-100 transition-colors"
                                       title="Buka Toko Shopee">
                                        Shopee
                                    </a>
                                @endif

                                @if($prod->tokopedia_url)
                                    <a href="{{ $prod->tokopedia_url }}" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center px-2.5 py-0.5 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-[11px] font-medium hover:bg-emerald-100 transition-colors"
                                       title="Buka Toko Tokopedia">
                                        Tokopedia
                                    </a>
                                @endif

                                @if(!$prod->shopee_url && !$prod->tokopedia_url)
                                    <span class="text-[11px] text-[#828282] italic">-</span>
                                @endif
                            </div>
                        </td>

                        <!-- Column 5: Status (Pastel green badge, dark green text for Aktif, identical to Sponsor Manager) -->
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <button wire:click="toggleStatus({{ $prod->id }})" 
                                    type="button"
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-colors inline-flex items-center gap-1.5 cursor-pointer {{ $prod->is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700' }}"
                                    title="Klik untuk ubah status">
                                <i class="fas fa-circle {{ $prod->is_active ? 'text-emerald-600' : 'text-slate-400' }}"></i>
                                <span>{{ $prod->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </button>
                        </td>

                        <!-- Column 6: Aksi (Clean, consistent minimal action buttons identical to Sponsor Manager) -->
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Edit Button -->
                                <button wire:click="editProduct({{ $prod->id }})" 
                                        type="button"
                                        class="p-1.5 text-slate-400 hover:text-[#1a68f0] hover:bg-blue-50 dark:hover:bg-zinc-800 rounded-lg transition-colors cursor-pointer" 
                                        title="Edit Produk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/>
                                    </svg>
                                </button>
                                <!-- Delete Button -->
                                <button wire:confirm="Hapus produk ini dari katalog part?" 
                                        wire:click="deleteProduct({{ $prod->id }})" 
                                        type="button"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-zinc-800 rounded-lg transition-colors cursor-pointer" 
                                        title="Hapus Produk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center text-slate-400">
                            <div class="max-w-xs mx-auto space-y-2">
                                <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-zinc-800 flex items-center justify-center mx-auto text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-[#212121] dark:text-zinc-100">Belum ada produk part</p>
                                <p class="text-xs text-slate-400">Data katalog part tidak ditemukan atau belum ditambahkan oleh mitra sponsor.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Create / Edit Produk (Enterprise Light/Dark Mode Dialog) -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 dark:bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 transition-all"
         x-data
         @keydown.escape.window="$wire.set('showModal', false)">
        <div class="bg-white dark:bg-[#242424] rounded-[16px] max-w-xl w-full border border-[#E0E0E0] dark:border-[#3F3F46] shadow-2xl flex flex-col max-h-[90vh] overflow-hidden text-[#212121] dark:text-[#F3F4F6] animate-in fade-in zoom-in-95 duration-150">
            
            <!-- 1. Sticky Header -->
            <div class="sticky top-0 z-10 bg-white dark:bg-[#242424] px-6 py-4 flex items-center justify-between border-b border-[#E0E0E0] dark:border-[#3F3F46]">
                <div>
                    <h2 class="text-base font-bold text-[#212121] dark:text-[#F3F4F6]">
                        {{ $editingId ? 'Edit Data Produk' : 'Upload Produk Baru' }}
                    </h2>
                    <p class="text-xs text-[#828282] dark:text-[#9CA3AF] mt-0.5">Lengkapi spesifikasi, harga promo, dan link marketplace resmi</p>
                </div>
                <button type="button" 
                        wire:click="$set('showModal', false)" 
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] dark:text-[#9CA3AF] hover:text-[#212121] dark:hover:text-[#F3F4F6] hover:bg-zinc-100 dark:hover:bg-[#333338] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- 2. Scrollable Body -->
            <form wire:submit.prevent="save" id="productForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Mitra Sponsor <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select wire:model="sponsor_id" 
                                    class="w-full appearance-none pl-3.5 pr-9 py-2.5 bg-white dark:bg-[#333338] border border-[#E0E0E0] dark:border-[#3F3F46] rounded-xl text-xs text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all cursor-pointer">
                                <option value="">-- Pilih Mitra Sponsor --</option>
                                @foreach($sponsors as $sp)
                                    <option value="{{ $sp->id }}">
                                        {{ $sp->name }} @if(strtolower($sp->slug) === 'quantum' || strtolower($sp->name) === 'quantum') ★ (Toko Resmi Quantum) @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#828282] dark:text-[#9CA3AF]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </div>
                        </div>
                        @error('sponsor_id') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Nama Produk <span class="text-rose-500">*</span></label>
                        <input wire:model="name" 
                               type="text" 
                               placeholder="Contoh: Solder Listrik T12 Digital" 
                               class="w-full px-3.5 py-2.5 bg-white dark:bg-[#333338] border border-[#E0E0E0] dark:border-[#3F3F46] rounded-xl text-xs text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        @error('name') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Deskripsi Produk</label>
                    <textarea wire:model="description" 
                              rows="2" 
                              placeholder="Spesifikasi atau keunggulan part..." 
                              class="w-full px-3.5 py-2.5 bg-white dark:bg-[#333338] border border-[#E0E0E0] dark:border-[#3F3F46] rounded-xl text-xs text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all resize-none"></textarea>
                    @error('description') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Harga Normal (Rp) <span class="text-rose-500">*</span></label>
                        <input wire:model="price" 
                               type="number" 
                               placeholder="389000" 
                               class="w-full px-3.5 py-2.5 bg-white dark:bg-[#333338] border border-[#E0E0E0] dark:border-[#3F3F46] rounded-xl text-xs text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        @error('price') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Harga Promo / Diskon (Opsional)</label>
                        <input wire:model="discount_price" 
                               type="number" 
                               placeholder="330000" 
                               class="w-full px-3.5 py-2.5 bg-white dark:bg-[#333338] border border-[#E0E0E0] dark:border-[#3F3F46] rounded-xl text-xs text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        @error('discount_price') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Foto Produk: Modern Drag-and-Drop Upload Zone -->
                <div>
                    <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Foto Produk</label>
                    <div class="relative rounded-xl border-2 border-dashed border-[#E0E0E0] dark:border-[#3F3F46] hover:border-[#1a68f0]/60 dark:hover:border-blue-500/60 bg-[#F8F9FA] dark:bg-[#18181B] hover:bg-[#EBF3FE]/30 dark:hover:bg-[#333338]/50 p-4 transition-all">
                        <input wire:model="imageFile" 
                               type="file" 
                               accept="image/png,image/jpeg,image/jpg,image/webp" 
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-lg bg-white dark:bg-[#27272A] border border-[#E0E0E0] dark:border-[#3F3F46] flex items-center justify-center overflow-hidden shrink-0 shadow-xs relative">
                                @php
                                    $isProdImgPreviewable = false;
                                    $prodImgPreviewUrl = null;
                                    if ($imageFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                        try {
                                            if (is_file($imageFile->getRealPath()) && !is_dir($imageFile->getRealPath()) && $imageFile->isPreviewable()) {
                                                $prodImgPreviewUrl = $imageFile->temporaryUrl();
                                                $isProdImgPreviewable = true;
                                            }
                                        } catch (\Throwable $e) {
                                            $isProdImgPreviewable = false;
                                        }
                                    }
                                @endphp
                                @if($isProdImgPreviewable && $prodImgPreviewUrl)
                                    <img src="{{ $prodImgPreviewUrl }}" class="w-full h-full object-cover">
                                @elseif($existing_image)
                                    <img src="{{ str_starts_with($existing_image, 'http') ? $existing_image : asset('storage/' . $existing_image) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-[#1a68f0] dark:text-blue-400 bg-[#EBF3FE]/60 dark:bg-blue-950/40">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-[#1a68f0] dark:text-blue-400">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 0 1-.88-7.903A5 5 0 1 1 15.9 6L16 6a5 5 0 0 1 1 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <span>Klik atau seret file foto part ke sini</span>
                                </div>
                                <p class="text-[11px] text-[#828282] dark:text-[#9CA3AF] mt-0.5">JPG, PNG, atau WEBP (maks. 3MB)</p>
                                <div wire:loading wire:target="imageFile" class="text-[11px] text-[#1a68f0] dark:text-blue-400 font-medium mt-1">Mengunggah foto...</div>
                            </div>
                        </div>
                    </div>
                    @error('imageFile') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Tautan Shopee Produk</label>
                        <input wire:model="shopee_url" 
                               type="url" 
                               placeholder="https://shopee.co.id/..." 
                               class="w-full px-3.5 py-2.5 bg-white dark:bg-[#333338] border border-[#E0E0E0] dark:border-[#3F3F46] rounded-xl text-xs text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        @error('shopee_url') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Tautan Tokopedia Produk</label>
                        <input wire:model="tokopedia_url" 
                               type="url" 
                               placeholder="https://tokopedia.com/..." 
                               class="w-full px-3.5 py-2.5 bg-white dark:bg-[#333338] border border-[#E0E0E0] dark:border-[#3F3F46] rounded-xl text-xs text-[#212121] dark:text-[#F3F4F6] placeholder-[#BDBDBD] dark:placeholder-[#9CA3AF] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        @error('tokopedia_url') <span class="text-[11px] text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-3.5 rounded-xl border border-[#E0E0E0] dark:border-[#3F3F46] bg-[#F8F9FA] dark:bg-[#18181B] flex items-center justify-between">
                    <div>
                        <label for="prod-is-active" class="text-xs font-semibold text-[#212121] dark:text-[#F3F4F6] cursor-pointer block">
                            Status Aktif Katalog
                        </label>
                        <span class="text-[11px] text-[#828282] dark:text-[#9CA3AF] block mt-0.5">Produk dapat ditemukan dan dibeli teknisi di aplikasi mobile</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="prod-is-active" wire:model="is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-300 dark:bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1a68f0]"></div>
                    </label>
                </div>
            </form>

            <!-- 3. Sticky Footer -->
            <div class="sticky bottom-0 z-10 bg-white dark:bg-[#242424] px-6 py-4 flex items-center justify-end gap-3 border-t border-[#E0E0E0] dark:border-[#3F3F46]">
                <button type="button" 
                        wire:click="$set('showModal', false)" 
                        class="px-5 py-2 text-xs font-semibold rounded-xl border border-[#E0E0E0] dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] hover:bg-zinc-50 dark:hover:bg-[#3F3F46] transition-colors">
                    Batal
                </button>
                <button type="submit" 
                        form="productForm"
                        wire:loading.attr="disabled"
                        class="px-5 py-2 text-xs font-semibold rounded-xl bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white shadow-xs transition-colors flex items-center gap-1.5 disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Simpan Perubahan' : 'Upload Produk' }}</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                        <svg class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
