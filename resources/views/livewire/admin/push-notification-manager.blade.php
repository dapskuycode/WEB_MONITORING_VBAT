<div class="space-y-6 text-[#212121] dark:text-[#F3F4F6]">
    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl flex items-center justify-between text-xs sm:text-sm font-semibold shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 dark:text-emerald-400"></i>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <!-- Tabbed Navigation & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-[#3F3F46] pb-2 sm:pb-0">
        <nav class="flex gap-6 sm:gap-8">
            <button 
                type="button" 
                wire:click="$set('activeTab', 'pending')"
                class="pb-3 text-xs sm:text-sm font-semibold transition-colors relative cursor-pointer {{ $activeTab === 'pending' ? 'text-[#1a68f0] dark:text-blue-400' : 'text-[#828282] dark:text-[#9CA3AF] hover:text-[#212121] dark:hover:text-white' }}"
            >
                <span>Menunggu Approval Sponsor</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'pending' ? 'bg-blue-100 dark:bg-blue-950 text-[#1a68f0] dark:text-blue-300' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400' }}">
                    {{ $pendingCount }}
                </span>
                @if($activeTab === 'pending')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1a68f0] dark:bg-blue-400 rounded-full"></span>
                @endif
            </button>
            <button 
                type="button" 
                wire:click="$set('activeTab', 'history')"
                class="pb-3 text-xs sm:text-sm font-semibold transition-colors relative cursor-pointer {{ $activeTab === 'history' ? 'text-[#1a68f0] dark:text-blue-400' : 'text-[#828282] dark:text-[#9CA3AF] hover:text-[#212121] dark:hover:text-white' }}"
            >
                <span>Riwayat Terkirim & Terjadwal</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'history' ? 'bg-blue-100 dark:bg-blue-950 text-[#1a68f0] dark:text-blue-300' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400' }}">
                    {{ $historyCount }}
                </span>
                @if($activeTab === 'history')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1a68f0] dark:bg-blue-400 rounded-full"></span>
                @endif
            </button>
        </nav>

        <button 
            type="button" 
            wire:click="openCreateModal" 
            class="mb-2 sm:mb-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-600 text-white rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-colors flex items-center gap-2 self-start sm:self-auto cursor-pointer shrink-0"
        >
            <i class="fas fa-plus"></i>
            <span>Kirim Notifikasi Baru</span>
        </button>
    </div>

    <!-- Table: Pending Approvals -->
    @if($activeTab === 'pending')
        <div class="bg-white dark:bg-[#242424] rounded-2xl border border-slate-200 dark:border-[#3F3F46] shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm border-collapse">
                    <thead class="bg-slate-50 dark:bg-[#18181B] border-b border-slate-200 dark:border-[#3F3F46] text-[#828282] dark:text-[#9CA3AF] font-bold text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5">Judul & Pesan</th>
                            <th class="px-5 py-3.5">Pengirim</th>
                            <th class="px-5 py-3.5">Target Audiens</th>
                            <th class="px-5 py-3.5">Diajukan</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-[#3F3F46]/60">
                        @forelse($notifications as $notif)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-[#333338]/40 transition-colors">
                                <td class="px-5 py-4 max-w-sm">
                                    <div class="font-bold text-[#212121] dark:text-[#F3F4F6] mb-0.5">{{ $notif->title }}</div>
                                    <div class="text-xs text-[#828282] dark:text-[#9CA3AF] line-clamp-2 leading-relaxed">{{ $notif->message }}</div>
                                    @if($notif->deep_link)
                                        <div class="text-[10px] text-[#1a68f0] dark:text-blue-400 mt-1 truncate">
                                            <i class="fas fa-link mr-1"></i>{{ $notif->deep_link }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-semibold text-[#212121] dark:text-[#F3F4F6]">
                                    {{ $notif->sponsor?->name ?? 'Admin VBAT' }}
                                </td>
                                <td class="px-5 py-4 text-xs font-medium text-[#828282] dark:text-[#9CA3AF]">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-[#333338] text-slate-700 dark:text-zinc-300">
                                        <i class="fas fa-users text-[10px]"></i>
                                        <span>{{ ucfirst($notif->target_audience ?? 'all') }}</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-[#828282] dark:text-[#9CA3AF]">
                                    {{ $notif->created_at ? $notif->created_at->diffForHumans() : '-' }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <i class="fas fa-clock text-[10px]"></i>
                                        <span>Menunggu Approval</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            type="button"
                                            wire:click="approve({{ $notif->id }})"
                                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs cursor-pointer flex items-center gap-1"
                                            title="Setujui dan siarkan notifikasi"
                                        >
                                            <i class="fas fa-check"></i>
                                            <span>Setujui</span>
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="reject({{ $notif->id }})"
                                            class="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-400 hover:bg-rose-100 text-xs font-bold transition cursor-pointer flex items-center gap-1"
                                            title="Tolak pengajuan notifikasi"
                                        >
                                            <i class="fas fa-times"></i>
                                            <span>Tolak</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-[#333338] text-slate-400 dark:text-zinc-500 flex items-center justify-center mx-auto mb-2.5">
                                        <i class="fas fa-inbox text-lg"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Tidak ada pengajuan notifikasi yang menunggu persetujuan</p>
                                    <p class="text-[11px] text-[#828282] dark:text-[#9CA3AF] mt-0.5">Semua notifikasi promosi sponsor telah ditinjau.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Table: History (Riwayat Terkirim, Terjadwal, Ditolak) -->
    @if($activeTab === 'history')
        <div class="bg-white dark:bg-[#242424] rounded-2xl border border-slate-200 dark:border-[#3F3F46] shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm border-collapse">
                    <thead class="bg-slate-50 dark:bg-[#18181B] border-b border-slate-200 dark:border-[#3F3F46] text-[#828282] dark:text-[#9CA3AF] font-bold text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5">Judul & Pesan</th>
                            <th class="px-5 py-3.5">Pengirim</th>
                            <th class="px-5 py-3.5">Audiens</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5">Waktu Eksekusi</th>
                            <th class="px-5 py-3.5 text-center">Terkirim</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-[#3F3F46]/60">
                        @forelse($notifications as $notif)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-[#333338]/40 transition-colors">
                                <td class="px-5 py-4 max-w-sm">
                                    <div class="font-bold text-[#212121] dark:text-[#F3F4F6] mb-0.5">{{ $notif->title }}</div>
                                    <div class="text-xs text-[#828282] dark:text-[#9CA3AF] line-clamp-2 leading-relaxed">{{ $notif->message }}</div>
                                </td>
                                <td class="px-5 py-4 font-semibold text-[#212121] dark:text-[#F3F4F6]">
                                    {{ $notif->sponsor?->name ?? 'Admin VBAT' }}
                                </td>
                                <td class="px-5 py-4 text-xs font-medium text-[#828282] dark:text-[#9CA3AF]">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-[#333338] text-slate-700 dark:text-zinc-300">
                                        <i class="fas fa-users text-[10px]"></i>
                                        <span>{{ ucfirst($notif->target_audience ?? 'all') }}</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if($notif->status === 'sent')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <i class="fas fa-check-circle text-[10px]"></i>
                                            <span>Terkirim</span>
                                        </span>
                                    @elseif($notif->status === 'scheduled')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                            <i class="fas fa-calendar-alt text-[10px]"></i>
                                            <span>Terjadwal</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <i class="fas fa-ban text-[10px]"></i>
                                            <span>Ditolak</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-[#828282] dark:text-[#9CA3AF]">
                                    {{ $notif->sent_at ? $notif->sent_at->format('d/m/Y H:i') : ($notif->scheduled_at ? ('Jadwal: ' . $notif->scheduled_at->format('d/m/Y H:i')) : '-') }}
                                </td>
                                <td class="px-5 py-4 text-center font-mono font-bold text-xs text-[#212121] dark:text-[#F3F4F6]">
                                    {{ number_format($notif->success_count ?? 0) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <button 
                                        type="button"
                                        wire:click="delete({{ $notif->id }})"
                                        wire:confirm="Yakin ingin menghapus riwayat notifikasi ini dari database?"
                                        class="p-2 text-slate-400 hover:text-rose-600 transition cursor-pointer"
                                        title="Hapus riwayat"
                                    >
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-[#333338] text-slate-400 dark:text-zinc-500 flex items-center justify-center mx-auto mb-2.5">
                                        <i class="fas fa-history text-lg"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Belum ada riwayat notifikasi</p>
                                    <p class="text-[11px] text-[#828282] dark:text-[#9CA3AF] mt-0.5">Notifikasi yang telah dikirim, dijadwalkan, atau ditolak akan tersimpan di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Modal Form: Kirim Notifikasi Baru -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-[#242424] border border-slate-200 dark:border-[#3F3F46] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col text-[#212121] dark:text-[#F3F4F6]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-[#3F3F46] bg-slate-50/70 dark:bg-[#18181B]">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-paper-plane text-[#1a68f0] dark:text-blue-400"></i>
                        <h3 class="text-base font-bold text-[#212121] dark:text-[#F3F4F6]">Buat Push Notification Baru</h3>
                    </div>
                    <button type="button" wire:click="closeModal" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white transition cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-[#F3F4F6] mb-1.5">
                            Mitra Sponsor Pengirim <span class="text-[11px] font-normal text-slate-400">(Opsional, default Admin)</span>
                        </label>
                        <select wire:model="sponsor_id" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                            <option value="">Admin Resmi VBAT (Broadcast Utama)</option>
                            @foreach($sponsors as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->name }} ({{ strtoupper($sp->tier ?? 'MITRA') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-[#F3F4F6] mb-1.5">
                            Judul Notifikasi <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="title" placeholder="Contoh: Diskon Khusus Alat Servis Ponsel!" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                        @error('title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-[#F3F4F6] mb-1.5">
                            Pesan Notifikasi <span class="text-rose-500">*</span>
                        </label>
                        <textarea wire:model="message" rows="3" placeholder="Tuliskan isi pesan notifikasi yang akan tampil di ponsel teknisi/pengguna..." class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]"></textarea>
                        @error('message') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Target Audiens</label>
                            <select wire:model="target_audience" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                                <option value="all">Semua Pengguna</option>
                                <option value="android">Khusus Pengguna Android</option>
                                <option value="iphone">Khusus Pengguna iPhone/iOS</option>
                                <option value="premium">Teknisi / Siswa Premium</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#212121] dark:text-[#F3F4F6] mb-1.5">Metode Pengiriman</label>
                            <select wire:model.live="sendType" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                                <option value="instant">Kirim Langsung Sekarang</option>
                                <option value="scheduled">Jadwalkan Pengiriman</option>
                            </select>
                        </div>
                    </div>

                    @if($sendType === 'scheduled')
                        <div>
                            <label class="block text-xs font-bold text-[#212121] dark:text-[#F3F4F6] mb-1.5">
                                Tanggal & Jam Jadwal <span class="text-rose-500">*</span>
                            </label>
                            <input type="datetime-local" wire:model="scheduled_at" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                            @error('scheduled_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-[#212121] dark:text-[#F3F4F6] mb-1.5">
                            Deep Link / URL Tujuan <span class="text-[11px] font-normal text-slate-400">(Opsional)</span>
                        </label>
                        <input type="text" wire:model="deep_link" placeholder="Contoh: /shop atau https://tokopedia.com/..." class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-[#3F3F46] bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-2.5 p-4 border-t border-slate-200 dark:border-[#3F3F46] bg-slate-50/50 dark:bg-[#18181B]">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-[#333338] border border-slate-200 dark:border-[#3F3F46] text-[#212121] dark:text-[#F3F4F6] hover:bg-slate-100 dark:hover:bg-[#3F3F46] transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" wire:click="send" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#1a68f0] hover:bg-blue-600 text-white shadow-xs transition disabled:opacity-50 cursor-pointer flex items-center gap-1.5">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>{{ $sendType === 'instant' ? 'Kirim Sekarang' : 'Simpan Jadwal' }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
