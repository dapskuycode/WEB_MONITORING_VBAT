<?php

use Livewire\Component;
use App\Models\User;
use Carbon\Carbon;

new class extends Component
{
    public $users;
    public $search = '';
    public $roleFilter = '';
    public $membershipFilter = '';

    // Modal state
    public $showDetailModal = false;
    public $selectedUser = null;

    // KPI Counters
    public $totalCount = 0;
    public $studentCount = 0;
    public $sponsorCount = 0;
    public $adminCount = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function updatedSearch()
    {
        $this->loadData();
    }

    public function updatedRoleFilter()
    {
        $this->loadData();
    }

    public function updatedMembershipFilter()
    {
        $this->loadData();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'roleFilter', 'membershipFilter']);
        $this->loadData();
    }

    public function loadData()
    {
        // Global KPI Counters
        $this->totalCount = User::count();
        $this->studentCount = User::where('role', 'student')->count();
        $this->sponsorCount = User::where('role', 'sponsor')->count();
        $this->adminCount = User::whereIn('role', ['super_admin', 'owner'])->count();

        // Query with eager loading
        $query = User::with(['province', 'city', 'membership', 'sponsor'])->latest();

        // Search Filter (Name, Email, Phone)
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        // Role Filter
        if (!empty($this->roleFilter)) {
            if ($this->roleFilter === 'admin_owner') {
                $query->whereIn('role', ['super_admin', 'owner']);
            } else {
                $query->where('role', $this->roleFilter);
            }
        }

        // Membership KTA Filter
        if (!empty($this->membershipFilter)) {
            if ($this->membershipFilter === 'active_kta') {
                $query->whereHas('membership', function ($q) {
                    $q->where('status', 'active');
                });
            } elseif ($this->membershipFilter === 'no_kta') {
                $query->doesntHave('membership');
            }
        }

        $this->users = $query->get();
    }

    public function viewUser($id)
    {
        $this->selectedUser = User::with(['province', 'city', 'membership', 'sponsor'])->find($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedUser = null;
    }
}; ?>

<div class="space-y-6">
    <!-- 1. KPI Statistic Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Users -->
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium tracking-wider text-zinc-500 uppercase dark:text-zinc-400">Total Akun</p>
                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $totalCount }}</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Terdaftar di ekosistem</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500"></div>
        </div>

        <!-- Siswa / Teknisi -->
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium tracking-wider text-emerald-600 uppercase dark:text-emerald-400">Siswa / Teknisi</p>
                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $studentCount }}</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Pengguna aplikasi mobile</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-emerald-500"></div>
        </div>

        <!-- Mitra Sponsor -->
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium tracking-wider text-purple-600 uppercase dark:text-purple-400">Mitra Sponsor</p>
                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $sponsorCount }}</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Brand part & tools resmi</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-purple-500"></div>
        </div>

        <!-- Admin & Owner -->
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium tracking-wider text-amber-600 uppercase dark:text-amber-400">Admin & Owner</p>
                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $adminCount }}</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Pengelola sistem VBAT</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-amber-500"></div>
        </div>
    </div>

    <!-- 2. Filter & Search Controls -->
    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <!-- Search Field -->
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama, email, atau nomor HP pengguna..."
                    class="w-full rounded-xl border border-zinc-300 bg-zinc-50/50 py-2.5 pr-4 pl-10 text-sm text-zinc-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-white dark:focus:border-blue-400 dark:focus:bg-zinc-800"
                />
            </div>

            <!-- Filters Group -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Role Filter -->
                <select
                    wire:model.live="roleFilter"
                    class="rounded-xl border border-zinc-300 bg-zinc-50/50 px-3.5 py-2.5 text-sm font-medium text-zinc-700 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-200"
                >
                    <option value="">Semua Role</option>
                    <option value="student">Siswa / Teknisi</option>
                    <option value="sponsor">Mitra Sponsor</option>
                    <option value="admin_owner">Admin & Owner</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="owner">Owner</option>
                </select>

                <!-- Membership Filter -->
                <select
                    wire:model.live="membershipFilter"
                    class="rounded-xl border border-zinc-300 bg-zinc-50/50 px-3.5 py-2.5 text-sm font-medium text-zinc-700 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-200"
                >
                    <option value="">Status KTA</option>
                    <option value="active_kta">Memiliki KTA Aktif</option>
                    <option value="no_kta">Belum Ada KTA</option>
                </select>

                <!-- Reset Button -->
                @if(!empty($search) || !empty($roleFilter) || !empty($membershipFilter))
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-xs font-semibold text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reset
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- 3. Users Table Container -->
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/75 text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="py-3.5 pr-3 pl-5">Pengguna</th>
                        <th scope="col" class="px-3 py-3.5">Peran (Role)</th>
                        <th scope="col" class="px-3 py-3.5">Demografi</th>
                        <th scope="col" class="px-3 py-3.5">Domisili (Kota)</th>
                        <th scope="col" class="px-3 py-3.5">Status KTA</th>
                        <th scope="col" class="px-3 py-3.5">Terdaftar</th>
                        <th scope="col" class="py-3.5 pr-5 pl-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($users as $user)
                    <tr class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40">
                        <!-- User Info -->
                        <td class="py-4 pr-3 pl-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 font-bold text-white shadow-xs">
                                    {{ $user->initials() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</p>
                                        @if($user->profile_completed)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400" title="Profil Lengkap">
                                                ✓
                                            </span>
                                        @endif
                                    </div>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                    @if($user->phone)
                                        <p class="truncate text-[11px] text-zinc-400 dark:text-zinc-500">{{ $user->phone }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Role Badge -->
                        <td class="px-3 py-4 whitespace-nowrap">
                            @if($user->role === 'student')
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-blue-500/20 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                    Siswa / Teknisi
                                </span>
                            @elseif($user->role === 'sponsor')
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-purple-500/20 bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-purple-600"></span>
                                    Mitra Sponsor
                                </span>
                            @elseif($user->role === 'super_admin')
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-red-500/20 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950/50 dark:text-red-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
                                    Super Admin
                                </span>
                            @elseif($user->role === 'owner')
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-500/20 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                    Owner
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ ucfirst($user->role) }}
                                </span>
                            @endif
                        </td>

                        <!-- Demographics (Gender & Age) -->
                        <td class="px-3 py-4 whitespace-nowrap">
                            <div class="text-xs text-zinc-700 dark:text-zinc-300">
                                @if($user->gender)
                                    <span class="inline-block font-medium">
                                        {{ $user->gender === 'male' || $user->gender === 'Laki-laki' ? 'Laki-laki' : 'Perempuan' }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-600">-</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                @if($user->age)
                                    {{ $user->age }} Tahun
                                @elseif($user->birth_date)
                                    {{ $user->birth_date->format('d/m/Y') }}
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-600">Usia belum diisi</span>
                                @endif
                            </div>
                        </td>

                        <!-- Domisili Kota -->
                        <td class="px-3 py-4">
                            <div class="text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $user->city?->name ?? 'Belum ditentukan' }}
                            </div>
                            @if($user->province)
                                <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ $user->province->name }}
                                </div>
                            @endif
                        </td>

                        <!-- Membership KTA -->
                        <td class="px-3 py-4 whitespace-nowrap">
                            @if($user->membership && $user->membership->status === 'active')
                                <div class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/20 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $user->membership->membership_number }}
                                </div>
                            @else
                                <span class="inline-flex items-center rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                                    Free Member
                                </span>
                            @endif
                        </td>

                        <!-- Tanggal Daftar -->
                        <td class="px-3 py-4 whitespace-nowrap text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                        </td>

                        <!-- Action Button -->
                        <td class="py-4 pr-5 pl-3 text-right whitespace-nowrap">
                            <button
                                type="button"
                                wire:click="viewUser({{ $user->id }})"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-2xs transition hover:border-blue-500 hover:text-blue-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-blue-400 dark:hover:text-blue-400"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                                <h4 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">Tidak ada data pengguna</h4>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Tidak ditemukan akun yang cocok dengan kata kunci atau filter pencarian Anda.</p>
                                <button
                                    type="button"
                                    wire:click="resetFilters"
                                    class="mt-4 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                >
                                    Bersihkan Filter
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Table Footer / Count info -->
        <div class="border-t border-zinc-200 bg-zinc-50/50 px-5 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/40 dark:text-zinc-400">
            Menampilkan <span class="font-semibold text-zinc-900 dark:text-white">{{ $users->count() }}</span> dari <span class="font-semibold text-zinc-900 dark:text-white">{{ $totalCount }}</span> akun terdaftar
        </div>
    </div>

    <!-- 4. Detail User Modal -->
    @if($showDetailModal && $selectedUser)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-xs">
        <div class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-2xl transition-all dark:border-zinc-800 dark:bg-zinc-900">
            <!-- Modal Header -->
            <div class="relative border-b border-zinc-200 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6 text-white dark:border-zinc-800">
                <button
                    type="button"
                    wire:click="closeDetailModal"
                    class="absolute top-5 right-5 flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-white/30"
                >
                    ✕
                </button>
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-2xl font-bold text-white shadow-inner backdrop-blur-sm">
                        {{ $selectedUser->initials() }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-bold text-white">{{ $selectedUser->name }}</h3>
                            @if($selectedUser->isProfileComplete())
                                <span class="rounded-full bg-emerald-400/20 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-200 border border-emerald-400/30">
                                    ✓ Profil Lengkap
                                </span>
                            @else
                                <span class="rounded-full bg-amber-400/20 px-2.5 py-0.5 text-[10px] font-semibold text-amber-200 border border-amber-400/30">
                                    ⚠ Profil Belum Lengkap
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-blue-100">{{ $selectedUser->email }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-lg bg-white/20 px-2.5 py-0.5 text-xs font-semibold text-white uppercase backdrop-blur-sm">
                                Role: {{ $selectedUser->role }}
                            </span>
                            @if($selectedUser->membership)
                                <span class="inline-flex items-center rounded-lg bg-emerald-400/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-100 backdrop-blur-sm">
                                    KTA: {{ $selectedUser->membership->membership_number }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Body Details -->
            <div class="max-h-[70vh] space-y-6 overflow-y-auto p-6">
                <!-- Kontak & Akun -->
                <div>
                    <h4 class="text-xs font-bold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">1. Informasi Kontak & Akun</h4>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">ID Pengguna (Database)</p>
                            <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">#{{ $selectedUser->id }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Alamat Email</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedUser->email }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Nomor Telepon</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedUser->phone ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">WhatsApp</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedUser->whatsapp ?: ($selectedUser->phone ?: '-') }}</p>
                        </div>
                        <div class="col-span-1 sm:col-span-2 rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Alamat Lengkap</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedUser->address ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Demografi & Wilayah -->
                <div>
                    <h4 class="text-xs font-bold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">2. Data Demografi & Wilayah Domisili</h4>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Jenis Kelamin</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $selectedUser->gender === 'male' || $selectedUser->gender === 'Laki-laki' ? 'Laki-laki' : ($selectedUser->gender ? 'Perempuan' : '-') }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Tanggal Lahir & Usia</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $selectedUser->birth_date ? $selectedUser->birth_date->format('d F Y') . ' (' . $selectedUser->age . ' Thn)' : '-' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Provinsi</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedUser->province?->name ?? 'Belum diisi' }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Kota / Kabupaten</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedUser->city?->name ?? 'Belum diisi' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Media Sosial -->
                <div>
                    <h4 class="text-xs font-bold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">3. Media Sosial</h4>
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Instagram</p>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">{{ $selectedUser->instagram ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Facebook</p>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">{{ $selectedUser->facebook ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">TikTok</p>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">{{ $selectedUser->tiktok ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">YouTube</p>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">{{ $selectedUser->youtube ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Membership KTA -->
                <div>
                    <h4 class="text-xs font-bold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">3. Status Keanggotaan & KTA</h4>
                    <div class="mt-3 rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-800/60">
                        @if($selectedUser->membership)
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                        STATUS: {{ strtoupper($selectedUser->membership->status) }}
                                    </span>
                                    <h5 class="mt-2 text-lg font-bold tracking-wider text-zinc-900 dark:text-white">
                                        {{ $selectedUser->membership->membership_number }}
                                    </h5>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        Tipe: {{ ucfirst($selectedUser->membership->purchase_type ?? 'Permanent VIP') }} • Diterbitkan: {{ $selectedUser->membership->issued_at ? Carbon::parse($selectedUser->membership->issued_at)->format('d M Y') : '-' }}
                                    </p>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-zinc-800 dark:text-zinc-200">Belum Memiliki Kartu Tanda Anggota (KTA)</p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Pengguna berstatus Free Member dan belum membeli paket kelas permanen.</p>
                                </div>
                                <span class="rounded-lg bg-zinc-200/60 px-2.5 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                    Non-KTA
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="flex flex-wrap items-center justify-between border-t border-zinc-200 pt-4 text-xs text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                    <div>
                        Terdaftar: {{ $selectedUser->created_at ? $selectedUser->created_at->format('d M Y, H:i') : '-' }} WIB
                    </div>
                    <div>
                        Pembaruan Terakhir: {{ $selectedUser->updated_at ? $selectedUser->updated_at->format('d M Y, H:i') : '-' }} WIB
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-800/50">
                <button
                    type="button"
                    wire:click="closeDetailModal"
                    class="rounded-xl border border-zinc-300 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 shadow-2xs transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
