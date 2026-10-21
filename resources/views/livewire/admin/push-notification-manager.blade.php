<div class="space-y-6">
    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <!-- Action Button (Right-aligned) -->
    <div class="flex justify-end">
        <button class="px-5 py-2.5 bg-[#1a73e8] text-white rounded-lg font-medium hover:bg-[#1557b0] transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Kirim Notifikasi Baru
        </button>
    </div>

    <!-- Tabbed Navigation -->
    <div class="border-b border-slate-200">
        <nav class="flex gap-8">
            <button 
                wire:click="$set('activeTab', 'pending')"
                class="pb-3 font-medium transition-colors relative"
                :class="@js($activeTab === 'pending') ? 'text-[#1a73e8]' : 'text-[#828282] hover:text-[#212121]'"
            >
                Menunggu Approval Sponsor
                @if($activeTab === 'pending')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1a73e8]"></span>
                @endif
            </button>
            <button 
                wire:click="$set('activeTab', 'history')"
                class="pb-3 font-medium transition-colors relative"
                :class="@js($activeTab === 'history') ? 'text-[#1a73e8]' : 'text-[#828282] hover:text-[#212121]'"
            >
                Riwayat Terkirim
                @if($activeTab === 'history')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1a73e8]"></span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Table: Pending Approvals -->
    @if($activeTab === 'pending')
        <div class="bg-white rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#F4F6F8] border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-[#212121]">Judul & Pesan</th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-[#212121]">Pengirim</th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-[#212121]">Target Pengguna</th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-[#212121]">Status</th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-[#212121]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pendingNotifications as $notification)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="max-w-md">
                                    <div class="font-semibold text-[#212121] mb-1">{{ $notification['title'] }}</div>
                                    <div class="text-sm text-[#828282] line-clamp-2">{{ $notification['message'] }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[#212121]">{{ $notification['sponsor'] }}</td>
                            <td class="px-6 py-4 text-[#828282]">{{ $notification['target'] }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1.5 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-sm font-medium">
                                    Menunggu Approval
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button 
                                        wire:click="approve({{ $notification['id'] }})"
                                        class="px-4 py-2 border-2 border-green-500 text-green-700 rounded-lg text-sm font-medium hover:bg-green-50 transition-colors"
                                    >
                                        Setujui
                                    </button>
                                    <button 
                                        wire:click="reject({{ $notification['id'] }})"
                                        class="px-4 py-2 border-2 border-red-500 text-red-700 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors"
                                    >
                                        Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-5xl text-slate-300 mb-3"></i>
                                <p class="text-[#828282]">Tidak ada notifikasi yang menunggu approval</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Table: History (placeholder) -->
    @if($activeTab === 'history')
        <div class="bg-white rounded-xl p-12 text-center">
            <i class="fas fa-history text-5xl text-slate-300 mb-3"></i>
            <p class="text-[#828282]">Riwayat notifikasi terkirim akan muncul di sini</p>
        </div>
    @endif
</div>
