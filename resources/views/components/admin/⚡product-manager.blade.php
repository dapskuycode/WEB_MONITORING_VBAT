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
        $this->sponsors = Sponsor::orderBy('name')->get();

        $query = SponsorProduct::with('sponsor')->latest();

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
        $this->sponsor_id = $this->sponsors->first()?->id ?? '';
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

    public function save()
    {
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

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Manajemen & Upload Produk Part Mitra
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola katalog suku cadang & peralatan teknisi ponsel, upload foto produk, atur diskon, dan hubungkan tautan marketplace resmi mitra sponsor.
            </p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Upload Produk Baru
        </button>
    </div>

    <!-- Filter & Pencarian -->
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="relative w-full sm:w-80">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama part, deskripsi..." class="w-full pl-10 pr-4 py-2 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
            <svg class="w-4 h-4 text-zinc-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400 whitespace-nowrap">Filter Sponsor:</label>
            <select wire:model.live="sponsorFilter" class="w-full sm:w-56 px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                <option value="">Semua Mitra Sponsor</option>
                @foreach($sponsors as $sp)
                    <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Tabel Produk -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm table-fixed">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="px-5 py-3.5 w-5/12">Produk Part</th>
                        <th class="px-5 py-3.5 w-2/12">Mitra Sponsor</th>
                        <th class="px-5 py-3.5 w-2/12">Harga & Diskon</th>
                        <th class="px-5 py-3.5 w-2/12">Marketplace</th>
                        <th class="px-5 py-3.5 w-20 text-center">Status</th>
                        <th class="px-5 py-3.5 w-20 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($products as $prod)
                    @php
                        $displayImg = $prod->image_path;
                        if ($displayImg && !str_starts_with($displayImg, 'http') && !str_starts_with($displayImg, 'assets/')) {
                            $displayImg = asset('storage/' . $displayImg);
                        }
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition">
                        <td class="px-5 py-4 w-5/12">
                            <div class="flex items-center gap-3">
                                <div style="width: 52px; height: 52px; min-width: 52px; max-width: 52px; min-height: 52px; max-height: 52px;" class="rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shrink-0 flex items-center justify-center">
                                    @if($displayImg && str_starts_with($displayImg, 'http'))
                                        <img src="{{ $displayImg }}" alt="{{ $prod->name }}" style="width: 52px; height: 52px; min-width: 52px; max-width: 52px; min-height: 52px; max-height: 52px; object-fit: cover; display: block;" class="rounded-lg">
                                    @else
                                        <div class="flex flex-col items-center justify-center text-zinc-400 text-xs p-1 text-center">
                                            <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-zinc-900 dark:text-white truncate" title="{{ $prod->name }}">{{ $prod->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate mt-0.5" title="{{ $prod->description }}">{{ $prod->description ?: 'Tanpa deskripsi' }}</div>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="text-[10px] text-zinc-400 font-mono truncate max-w-[120px]">Slug: {{ $prod->slug }}</span>
                                        @if($prod->is_featured)
                                            <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-[10px] font-bold">★ Unggulan</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 w-2/12">
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block truncate" title="{{ $prod->sponsor?->name }}">{{ $prod->sponsor?->name ?? 'Tanpa Sponsor' }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider inline-block mt-1
                                @if(($prod->sponsor?->tier ?? '') === 'platinum') bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300
                                @elseif(($prod->sponsor?->tier ?? '') === 'gold') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @else bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 @endif">
                                {{ $prod->sponsor?->tier ?? 'PARTNER' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 w-2/12">
                            <div class="font-semibold text-zinc-900 dark:text-white whitespace-nowrap">
                                Rp{{ number_format($prod->price, 0, ',', '.') }}
                            </div>
                            @if($prod->discount_price)
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1 mt-0.5 whitespace-nowrap">
                                    <span>Promo: Rp{{ number_format($prod->discount_price, 0, ',', '.') }}</span>
                                    <span class="px-1 rounded bg-emerald-100 dark:bg-emerald-900/40 text-[10px] font-bold">
                                        -{{ round((($prod->price - $prod->discount_price) / $prod->price) * 100) }}%
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 w-2/12">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($prod->shopee_url)
                                    <a href="{{ $prod->shopee_url }}" target="_blank" class="px-2 py-1 rounded-md bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 text-xs font-semibold hover:underline flex items-center gap-1 shrink-0" title="Buka Shopee">
                                        Shopee
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif
                                @if($prod->tokopedia_url)
                                    <a href="{{ $prod->tokopedia_url }}" target="_blank" class="px-2 py-1 rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 text-xs font-semibold hover:underline flex items-center gap-1 shrink-0" title="Buka Tokopedia">
                                        Tokopedia
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif
                                @if(!$prod->shopee_url && !$prod->tokopedia_url)
                                    <span class="text-xs text-zinc-400">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 w-20 text-center whitespace-nowrap">
                            <button wire:click="toggleStatus({{ $prod->id }})" class="cursor-pointer">
                                @if($prod->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">Nonaktif</span>
                                @endif
                            </button>
                        </td>
                        <td class="px-5 py-4 w-20 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="editProduct({{ $prod->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition cursor-pointer" title="Edit Produk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:confirm="Hapus produk ini dari katalog?" wire:click="deleteProduct({{ $prod->id }})" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition cursor-pointer" title="Hapus Produk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            Belum ada produk part yang diupload. Klik "Upload Produk Baru" di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Create / Edit Produk -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl max-w-2xl w-full border border-zinc-200 dark:border-zinc-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    {{ $editingId ? 'Edit Data Produk Part' : 'Upload Produk Part Baru' }}
                </h3>
                <button wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Mitra Sponsor *</label>
                        <select wire:model="sponsor_id" class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                            <option value="">-- Pilih Mitra Sponsor --</option>
                            @foreach($sponsors as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->name }} ({{ strtoupper($sp->tier) }})</option>
                            @endforeach
                        </select>
                        @error('sponsor_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Nama Produk Part *</label>
                        <input wire:model="name" type="text" placeholder="Contoh: Solder Listrik T12 Digital Auto Sleep" class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                        @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Deskripsi Produk</label>
                    <textarea wire:model="description" rows="2" placeholder="Spesifikasi atau keunggulan part..." class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white"></textarea>
                    @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Harga Normal (Rp) *</label>
                        <input wire:model="price" type="number" placeholder="Contoh: 389000" class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                        @error('price') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Harga Promo / Diskon (Opsional)</label>
                        <input wire:model="discount_price" type="number" placeholder="Contoh: 330000" class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                        @error('discount_price') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Bagian Foto Produk (Bisa Upload File atau Input URL) -->
                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 space-y-3">
                    <label class="block text-xs font-bold text-zinc-900 dark:text-white">Foto Produk Part</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-zinc-500 block mb-1">Opsi A: Upload File (PNG/JPG/WebP max 3MB)</span>
                            <input wire:model="imageFile" type="file" accept="image/*" class="w-full text-xs text-zinc-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @error('imageFile') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <span class="text-xs text-zinc-500 block mb-1">Opsi B: Atau Input Link URL Foto Langsung</span>
                            <input wire:model="image_url" type="text" placeholder="https://images.unsplash.com/..." class="w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                            @error('image_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Image Preview -->
                    @if($imageFile)
                        <div class="flex items-center gap-2 pt-2">
                            <span class="text-xs text-emerald-600 font-medium">Preview File yang akan diupload:</span>
                            <img src="{{ $imageFile->temporaryUrl() }}" class="w-12 h-12 object-cover rounded-lg border border-zinc-200">
                        </div>
                    @elseif($image_url)
                        <div class="flex items-center gap-2 pt-2">
                            <span class="text-xs text-blue-600 font-medium">Preview URL:</span>
                            <img src="{{ $image_url }}" class="w-12 h-12 object-cover rounded-lg border border-zinc-200">
                        </div>
                    @elseif($existing_image)
                        <div class="flex items-center gap-2 pt-2">
                            <span class="text-xs text-zinc-500">Gambar Saat Ini:</span>
                            <img src="{{ str_starts_with($existing_image, 'http') ? $existing_image : asset('storage/' . $existing_image) }}" class="w-12 h-12 object-cover rounded-lg border border-zinc-200">
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Tautan Shopee Produk</label>
                        <input wire:model="shopee_url" type="url" placeholder="https://shopee.co.id/..." class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                        @error('shopee_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Tautan Tokopedia Produk</label>
                        <input wire:model="tokopedia_url" type="url" placeholder="https://tokopedia.com/..." class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-white">
                        @error('tokopedia_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-2">
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                        <input wire:model="is_active" type="checkbox" class="w-4 h-4 text-blue-600 rounded">
                        <span>Aktifkan di Katalog Mobile</span>
                    </label>

                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                        <input wire:model="is_featured" type="checkbox" class="w-4 h-4 text-amber-500 rounded">
                        <span>Tandai Sebagai Produk Unggulan</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition">
                        {{ $editingId ? 'Simpan Perubahan' : 'Upload Produk' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
