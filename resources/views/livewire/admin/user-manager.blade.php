<div class="space-y-6 text-[#212121] dark:text-[#F3F4F6]">
    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl flex items-center justify-between text-xs sm:text-sm font-semibold shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 dark:text-emerald-400"></i>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau email..."
                class="w-full px-4 py-2.5 bg-white dark:bg-[#242424] border border-slate-200 dark:border-[#3F3F46] rounded-xl text-sm focus:ring-2 focus:ring-[#1a68f0] focus:border-transparent outline-none"
            />
        </div>
        <div class="w-full sm:w-48">
            <select
                wire:model.live="roleFilter"
                class="w-full px-4 py-2.5 bg-white dark:bg-[#242424] border border-slate-200 dark:border-[#3F3F46] rounded-xl text-sm focus:ring-2 focus:ring-[#1a68f0] focus:border-transparent outline-none cursor-pointer"
            >
                <option value="">Semua Role</option>
                <option value="siswa">Siswa</option>
                <option value="sponsor">Sponsor</option>
                <option value="super_admin">Super Admin</option>
                <option value="owner">Owner</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-[#242424] rounded-2xl border border-slate-200 dark:border-[#3F3F46] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm border-collapse">
                <thead class="bg-slate-50 dark:bg-[#18181B] border-b border-slate-200 dark:border-[#3F3F46] text-[#828282] dark:text-[#9CA3AF] font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Nama</th>
                        <th class="px-5 py-3.5">Email</th>
                        <th class="px-5 py-3.5">Role</th>
                        <th class="px-5 py-3.5">Telepon</th>
                        <th class="px-5 py-3.5">Terdaftar</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#3F3F46]/60">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-[#333338]/40 transition-colors">
                            <td class="px-5 py-4 font-semibold text-[#212121] dark:text-[#F3F4F6]">
                                {{ $user->name }}
                            </td>
                            <td class="px-5 py-4 text-[#828282] dark:text-[#9CA3AF]">
                                {{ $user->email }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{
                                    $user->role === 'super_admin' ? 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300' :
                                    ($user->role === 'sponsor' ? 'bg-purple-100 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300' :
                                    ($user->role === 'owner' ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300' :
                                    'bg-slate-100 dark:bg-[#333338] text-slate-700 dark:text-zinc-300'))
                                }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-[#828282] dark:text-[#9CA3AF]">
                                {{ $user->phone ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-xs text-[#828282] dark:text-[#9CA3AF]">
                                {{ $user->created_at->diffForHumans() }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $user->id }})"
                                        class="px-3 py-1.5 text-xs font-semibold bg-[#1a68f0] hover:bg-blue-600 text-white rounded-lg transition-colors cursor-pointer"
                                    >
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="Yakin hapus user ini?"
                                        class="px-3 py-1.5 text-xs font-semibold bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors cursor-pointer"
                                    >
                                        <i class="fas fa-trash mr-1"></i>Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-[#828282] dark:text-[#9CA3AF]">
                                <i class="fas fa-users text-4xl mb-3 opacity-40"></i>
                                <p class="font-semibold">Tidak ada user ditemukan</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-5 py-4 border-t border-slate-200 dark:border-[#3F3F46]">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="closeModal">
            <div class="bg-white dark:bg-[#242424] rounded-2xl shadow-2xl w-full max-w-md border border-slate-200 dark:border-[#3F3F46]" wire:click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-[#3F3F46]">
                    <h3 class="text-lg font-bold text-[#212121] dark:text-[#F3F4F6]">Edit User</h3>
                    <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#828282] dark:text-[#9CA3AF] mb-1.5">Nama</label>
                        <input
                            type="text"
                            wire:model="name"
                            class="w-full px-4 py-2.5 bg-white dark:bg-[#18181B] border border-slate-200 dark:border-[#3F3F46] rounded-xl text-sm focus:ring-2 focus:ring-[#1a68f0] focus:border-transparent outline-none"
                        />
                        @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#828282] dark:text-[#9CA3AF] mb-1.5">Email</label>
                        <input
                            type="email"
                            wire:model="email"
                            class="w-full px-4 py-2.5 bg-white dark:bg-[#18181B] border border-slate-200 dark:border-[#3F3F46] rounded-xl text-sm focus:ring-2 focus:ring-[#1a68f0] focus:border-transparent outline-none"
                        />
                        @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#828282] dark:text-[#9CA3AF] mb-1.5">Role</label>
                        <select
                            wire:model="role"
                            class="w-full px-4 py-2.5 bg-white dark:bg-[#18181B] border border-slate-200 dark:border-[#3F3F46] rounded-xl text-sm focus:ring-2 focus:ring-[#1a68f0] focus:border-transparent outline-none cursor-pointer"
                        >
                            <option value="siswa">Siswa</option>
                            <option value="sponsor">Sponsor</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="owner">Owner</option>
                        </select>
                        @error('role') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#828282] dark:text-[#9CA3AF] mb-1.5">Telepon</label>
                        <input
                            type="text"
                            wire:model="phone"
                            class="w-full px-4 py-2.5 bg-white dark:bg-[#18181B] border border-slate-200 dark:border-[#3F3F46] rounded-xl text-sm focus:ring-2 focus:ring-[#1a68f0] focus:border-transparent outline-none"
                        />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-[#333338] text-slate-700 dark:text-zinc-300 rounded-xl text-sm font-semibold hover:bg-slate-200 dark:hover:bg-[#3F3F46] transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="flex-1 px-4 py-2.5 bg-[#1a68f0] hover:bg-blue-600 text-white rounded-xl text-sm font-semibold transition-colors cursor-pointer"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
