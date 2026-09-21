<?php

use Livewire\Component;
use Livewire\WithFileUploads;
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
        if ($user && ($user->isSuperAdmin() || $user->isOwner())) {
            $this->sponsor = Sponsor::where('slug', 'quantum')->orWhere('name', 'Quantum')->first() ?? $user->sponsor ?? Sponsor::first();
        } else {
            $this->sponsor = $user?->sponsor ?? Sponsor::first();
        }

        if ($this->sponsor) {
            $query = SponsorProduct::where('sponsor_id', $this->sponsor->id)->latest();

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

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                    <flux:icon name="shopping-bag" class="w-7 h-7 text-indigo-600" />
                    Katalog Produk & Suku Cadang Saya
                </h1>
                @if($sponsor)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    {{ $sponsor->name }} ({{ strtoupper($sponsor->tier ?? 'PARTNER') }})
                </span>
                @endif
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola daftar produk suku cadang, alat servis, dan perlengkapan ponsel brand Anda yang ditampilkan di katalog Shop aplikasi VBAT Ponsel.
            </p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition cursor-pointer">
            <flux:icon name="plus" class="w-4 h-4" />
            Tambah Produk Baru
        </button>
    </div>

    <!-- Alert Edukasi -->
    <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 flex items-start gap-3">
        <flux:icon name="sparkles" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div class="text-xs text-amber-800 dark:text-amber-300 space-y-1">
            <span class="font-bold">Informasi Penjualan & Promosi:</span>
            <p>
                Produk yang Anda tambahkan di sini akan langsung tampil di katalog toko resmi mitra aplikasi ponsel. Selain itu, Admin VBAT dapat memilih produk Anda untuk diikutsertakan ke dalam <strong>Program Best Deal</strong> dan <strong>Event Diskon Toko</strong> untuk jangkauan pembeli yang lebih luas!
            </p>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="relative w-full sm:w-80">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama produk, sparepart..." class="w-full pl-10 pr-4 py-2 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white">
            <flux:icon name="magnifying-glass" class="w-4 h-4 text-zinc-400 absolute left-3.5 top-3" />
        </div>

        <div class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">
            Total: <span class="font-bold text-zinc-900 dark:text-white">{{ $products->count() }} Produk</span>
        </div>
    </div>

    <!-- Tabel Produk -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm table-fixed">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="px-5 py-3.5 w-5/12">Nama & Deskripsi Produk</th>
                        <th class="px-5 py-3.5 w-3/12">Harga Normal / Diskon</th>
                        <th class="px-5 py-3.5 w-2/12">Marketplace</th>
                        <th class="px-5 py-3.5 w-1/12 text-center">Status</th>
                        <th class="px-5 py-3.5 w-1/12 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 text-zinc-700 dark:text-zinc-300">
                    @forelse($products as $prod)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                        <!-- Kolom Produk Part -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0 border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    @php
                                        $img = $prod->image_path;
                                        if ($img && !str_starts_with($img, 'http') && !str_starts_with($img, 'assets/')) {
                                            $img = asset('storage/' . $img);
                                        } elseif (!$img) {
                                            $img = asset('assets/images/product_1.png');
                                        }
                                    @endphp
                                    <img src="{{ $img }}" alt="{{ $prod->name }}" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0 pr-2">
                                    <p class="font-bold text-zinc-900 dark:text-white truncate text-sm" title="{{ $prod->name }}">
                                        {{ $prod->name }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1 mt-0.5">
                                        {{ $prod->description ?: 'Tidak ada deskripsi.' }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Kolom Harga -->
                        <td class="px-5 py-3.5">
                            <div class="font-mono text-sm font-bold text-zinc-900 dark:text-white">
                                Rp {{ number_format($prod->price, 0, ',', '.') }}
                            </div>
                            @if($prod->discount_price && $prod->discount_price < $prod->price)
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold font-mono flex items-center gap-1 mt-0.5">
                                    <span>Promo: Rp {{ number_format($prod->discount_price, 0, ',', '.') }}</span>
                                    <span class="text-[10px] bg-emerald-100 dark:bg-emerald-950 px-1 py-0.2 rounded font-bold">
                                        HEMAT {{ round((($prod->price - $prod->discount_price) / $prod->price) * 100) }}%
                                    </span>
                                </div>
                            @endif
                        </td>

                        <!-- Kolom Marketplace Links -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($prod->shopee_url)
                                    <a href="{{ $prod->shopee_url }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-orange-50 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300 border border-orange-200 dark:border-orange-800 hover:opacity-80 transition" title="Buka Toko Shopee">
                                        <span>Shopee</span>
                                        <flux:icon name="arrow-up-right" class="w-3 h-3" />
                                    </a>
                                @endif
                                @if($prod->tokopedia_url)
                                    <a href="{{ $prod->tokopedia_url }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:opacity-80 transition" title="Buka Toko Tokopedia">
                                        <span>Tokopedia</span>
                                        <flux:icon name="arrow-up-right" class="w-3 h-3" />
                                    </a>
                                @endif
                                @if(!$prod->shopee_url && !$prod->tokopedia_url)
                                    <span class="text-xs text-zinc-400 italic">Belum ada link</span>
                                @endif
                            </div>
                        </td>

                        <!-- Kolom Status -->
                        <td class="px-5 py-3.5 text-center">
                            <button wire:click="toggleStatus({{ $prod->id }})" class="cursor-pointer">
                                @if($prod->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        Nonaktif
                                    </span>
                                @endif
                            </button>
                        </td>

                        <!-- Kolom Aksi -->
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="editProduct({{ $prod->id }})" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 hover:text-indigo-600 rounded-lg transition" title="Edit Produk">
                                    <flux:icon name="pencil-square" class="w-4 h-4" />
                                </button>
                                <button wire:click="deleteProduct({{ $prod->id }})" wire:confirm="Yakin ingin menghapus produk ini dari katalog Anda?" class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-zinc-500 hover:text-rose-600 rounded-lg transition" title="Hapus Produk">
                                    <flux:icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="shopping-bag" class="w-10 h-10 mx-auto text-zinc-300 dark:text-zinc-700 mb-2" />
                            <p class="font-medium text-sm">Belum ada produk di katalog Anda.</p>
                            <p class="text-xs text-zinc-400 mt-1">Klik tombol "Tambah Produk Baru" untuk mulai memasukkan suku cadang & alat servis toko Anda.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Tambah / Edit Produk -->
    @if($showModal)
    <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $editingId ? 'Edit Produk Suku Cadang' : 'Tambah Produk Baru' }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Brand: <span class="font-semibold text-indigo-600">{{ $sponsor->name ?? 'Mitra Sponsor' }}</span>
                    </p>
                </div>
                <button wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit="save" class="space-y-4">
                <!-- Nama Produk -->
                <div>
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Nama Produk Part / Alat *</label>
                    <input wire:model="name" type="text" placeholder="Contoh: LCD iPhone 11 Pro Max Original Quality" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white">
                    @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Harga & Harga Promo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Harga Normal (Rp) *</label>
                        <input wire:model="price" type="number" placeholder="Contoh: 1250000" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white font-mono">
                        @error('price') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Harga Diskon / Promo (Rp) <span class="text-zinc-400 font-normal">(Opsional)</span></label>
                        <input wire:model="discount_price" type="number" placeholder="Contoh: 1062500" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white font-mono">
                        @error('discount_price') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Foto Produk -->
                <div>
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Foto Produk (Maks 3MB)</label>
                    <div class="flex items-center gap-3">
                        @if($imageFile)
                            <div class="w-14 h-14 rounded-xl border border-zinc-300 dark:border-zinc-700 overflow-hidden shrink-0">
                                <img src="{{ $imageFile->temporaryUrl() }}" class="w-full h-full object-cover">
                            </div>
                        @elseif($existing_image)
                            <div class="w-14 h-14 rounded-xl border border-zinc-300 dark:border-zinc-700 overflow-hidden shrink-0">
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
                        <input wire:model="imageFile" type="file" accept="image/*" class="w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300">
                    </div>
                    @error('imageFile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Link Shopee & Tokopedia -->
                <div class="space-y-3 p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60">
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 block">Link Toko Marketplace Resmi:</span>
                    <div>
                        <label class="block text-[11px] font-semibold text-orange-700 dark:text-orange-400 mb-0.5">Tautan Shopee:</label>
                        <input wire:model="shopee_url" type="url" placeholder="https://shopee.co.id/nama-toko/produk-xyz" class="w-full px-3 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none dark:text-white">
                        @error('shopee_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 mb-0.5">Tautan Tokopedia:</label>
                        <input wire:model="tokopedia_url" type="url" placeholder="https://tokopedia.com/nama-toko/produk-xyz" class="w-full px-3 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:text-white">
                        @error('tokopedia_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Deskripsi & Spesifikasi Produk</label>
                    <textarea wire:model="description" rows="3" placeholder="Jelaskan kualitas, garansi, atau kecocokan tipe smartphone..." class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"></textarea>
                    @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Checkbox Aktif -->
                <div class="flex items-center gap-2">
                    <input wire:model="is_active" type="checkbox" id="is_active" class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                    <label for="is_active" class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Tampilkan produk ini di katalog toko aplikasi (Status Aktif)</label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-semibold text-zinc-600 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
