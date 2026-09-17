<?php

use Livewire\Component;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $sponsors;
    public $showModal = false;
    public $editingId = null;

    // Form fields
    public $name = '';
    public $tier = 'gold';
    public $weight = 3;
    public $website_url = '';
    public $contact_email = '';
    public $phone = '';
    public $whatsapp = '';
    public $description = '';
    public $is_active = true;

    // Account creation fields
    public $createUserAccount = true;
    public $userEmail = '';
    public $userPassword = '';

    public function mount()
    {
        $this->loadSponsors();
    }

    public function loadSponsors()
    {
        $this->sponsors = Sponsor::with('user')->withCount('products', 'campaigns')->orderByDesc('weight')->get();
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'name', 'tier', 'weight', 'website_url', 'contact_email', 'phone', 'whatsapp', 'description', 'userEmail', 'userPassword']);
        $this->tier = 'gold';
        $this->weight = 3;
        $this->is_active = true;
        $this->createUserAccount = true;
        $this->showModal = true;
    }

    public function editSponsor($id)
    {
        $sponsor = Sponsor::findOrFail($id);
        $this->editingId = $sponsor->id;
        $this->name = $sponsor->name;
        $this->tier = $sponsor->tier;
        $this->weight = $sponsor->weight;
        $this->website_url = $sponsor->website_url;
        $this->contact_email = $sponsor->contact_email;
        $this->phone = $sponsor->phone;
        $this->whatsapp = $sponsor->whatsapp;
        $this->description = $sponsor->description;
        $this->is_active = (bool)$sponsor->is_active;
        $this->createUserAccount = false;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'tier' => 'required|in:platinum,gold,silver,partner',
            'weight' => 'required|integer|min:0|max:100',
        ]);

        $userId = null;
        if ($this->createUserAccount && !empty($this->userEmail) && !empty($this->userPassword)) {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->userEmail,
                'password' => Hash::make($this->userPassword),
                'role' => 'sponsor',
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
            ]);
            $userId = $user->id;
        }

        if ($this->editingId) {
            $sponsor = Sponsor::findOrFail($this->editingId);
            $sponsor->update([
                'name' => $this->name,
                'tier' => $this->tier,
                'weight' => $this->weight,
                'website_url' => $this->website_url,
                'contact_email' => $this->contact_email,
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
        } else {
            Sponsor::create([
                'user_id' => $userId,
                'name' => $this->name,
                'slug' => Str::slug($this->name) . '-' . rand(100, 999),
                'tier' => $this->tier,
                'weight' => $this->weight,
                'website_url' => $this->website_url,
                'contact_email' => $this->contact_email,
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ]);
        }

        $this->showModal = false;
        $this->loadSponsors();
    }

    public function toggleStatus($id)
    {
        $s = Sponsor::findOrFail($id);
        $s->is_active = !$s->is_active;
        $s->save();
        $this->loadSponsors();
    }

    public function deleteSponsor($id)
    {
        Sponsor::destroy($id);
        $this->loadSponsors();
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Manajemen Akun Sponsor & Tiering
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola data mitra sponsor, terbitkan akun login portal sponsor, dan atur prioritas tier (Platinum, Gold, Silver).
            </p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Sponsor Baru
        </button>
    </div>

    <!-- Tabel Daftar Sponsor -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-xs font-semibold text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="px-6 py-4">Nama Sponsor</th>
                        <th class="px-6 py-4">Tier & Bobot</th>
                        <th class="px-6 py-4">Akun Login</th>
                        <th class="px-6 py-4">Kontak & Website</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($sponsors as $sponsor)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-zinc-900 dark:text-white text-base">{{ $sponsor->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $sponsor->products_count }} Produk • {{ $sponsor->campaigns_count }} Kampanye</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase
                                    {{ $sponsor->tier === 'platinum' ? 'bg-purple-100 text-purple-700 border border-purple-200' : '' }}
                                    {{ $sponsor->tier === 'gold' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' }}
                                    {{ $sponsor->tier === 'silver' ? 'bg-slate-100 text-slate-700 border border-slate-200' : '' }}
                                    {{ $sponsor->tier === 'partner' ? 'bg-blue-100 text-blue-700 border border-blue-200' : '' }}
                                ">
                                    ⭐ {{ $sponsor->tier }}
                                </span>
                                <div class="text-xs text-zinc-400 mt-1 font-medium">Bobot Rotasi: {{ $sponsor->weight }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                @if ($sponsor->user)
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $sponsor->user->email }}</div>
                                    <span class="text-emerald-600 font-medium">✓ Akun Aktif</span>
                                @else
                                    <span class="text-zinc-400 italic">Belum ada akun login</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-zinc-600 dark:text-zinc-300">
                                <div>WA: {{ $sponsor->whatsapp ?? '-' }}</div>
                                <a href="{{ $sponsor->website_url }}" target="_blank" class="text-blue-600 hover:underline truncate block max-w-[150px]">
                                    {{ $sponsor->website_url ?? '-' }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleStatus({{ $sponsor->id }})" class="px-3 py-1 text-xs rounded-full font-bold {{ $sponsor->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $sponsor->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button wire:click="editSponsor({{ $sponsor->id }})" class="px-3 py-1.5 text-xs bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-800 dark:text-zinc-200 rounded-lg font-semibold">
                                    Edit
                                </button>
                                <button wire:click="deleteSponsor({{ $sponsor->id }})" wire:confirm="Hapus sponsor ini?" class="px-3 py-1.5 text-xs bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg font-semibold">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-500">Belum ada mitra sponsor.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Tambah / Edit Sponsor -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $editingId ? 'Edit Sponsor' : 'Tambah Mitra Sponsor Baru' }}
                    </h2>
                    <button wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Nama Perusahaan / Sponsor *</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white focus:outline-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Tier Sponsor *</label>
                            <select wire:model="tier" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                                <option value="platinum">Platinum (Prioritas Tertinggi)</option>
                                <option value="gold">Gold</option>
                                <option value="silver">Silver</option>
                                <option value="partner">Partner</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Bobot Frekuensi Tayang (Weight) *</label>
                            <input type="number" wire:model="weight" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">WhatsApp Order</label>
                            <input type="text" wire:model="whatsapp" placeholder="62812345678" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Website / Marketplace URL</label>
                            <input type="url" wire:model="website_url" placeholder="https://shopee.co.id/..." class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Deskripsi Singkat</label>
                        <textarea wire:model="description" rows="2" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white"></textarea>
                    </div>

                    @if (!$editingId)
                        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 space-y-2">
                            <label class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-white cursor-pointer">
                                <input type="checkbox" wire:model.live="createUserAccount" class="rounded text-blue-600">
                                Sekaligus Terbitkan Akun Login Portal Sponsor
                            </label>
                            @if ($createUserAccount)
                                <div class="grid grid-cols-2 gap-2 pt-2">
                                    <div>
                                        <label class="block text-[11px] text-zinc-500 mb-0.5">Email Login</label>
                                        <input type="email" wire:model="userEmail" placeholder="sponsor@toko.com" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-zinc-500 mb-0.5">Password</label>
                                        <input type="password" wire:model="userPassword" placeholder="******" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t dark:border-zinc-800">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                        Batal
                    </button>
                    <button wire:click="save" class="px-4 py-2 text-sm font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                        Simpan Data
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>