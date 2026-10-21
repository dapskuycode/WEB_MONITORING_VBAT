<?php

use Livewire\Component;
use App\Models\PushNotification;
use App\Models\Sponsor;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $notifications;
    public $sponsor;
    public $showModal = false;
    public $editingId = null;

    public $title = '';
    public $message = '';
    public $deep_link = '';
    public $target_audience = 'all';
    public $sendType = 'instant'; // instant / scheduled
    public $scheduled_at = '';
    public $successMessage = '';

    protected $listeners = [
        'trigger-create-notification' => 'openCreateModal'
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->sponsor = $user?->sponsor ?? Sponsor::where('name', 'like', '%TITAN%')->first() ?? Sponsor::first();

        if ($this->sponsor) {
            $this->notifications = PushNotification::where('sponsor_id', $this->sponsor->id)
                ->latest()
                ->get();
        } else {
            $this->notifications = collect();
        }
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'title', 'message', 'deep_link', 'scheduled_at', 'successMessage']);
        $this->target_audience = 'all';
        $this->sendType = 'instant';
        $this->deep_link = $this->sponsor?->website_url ?? 'https://shopee.co.id';
        $this->showModal = true;
    }

    public function editNotification($id)
    {
        $notif = PushNotification::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $this->editingId = $notif->id;
        $this->title = $notif->title;
        $this->message = $notif->message;
        $this->deep_link = $notif->deep_link;
        $this->target_audience = $notif->target_audience ?? 'all';
        $this->sendType = $notif->scheduled_at ? 'scheduled' : 'instant';
        $this->scheduled_at = $notif->scheduled_at ? $notif->scheduled_at->format('Y-m-d\TH:i') : '';
        $this->showModal = true;
    }

    public function deleteNotification($id)
    {
        $notif = PushNotification::where('sponsor_id', $this->sponsor->id)->findOrFail($id);
        $notif->delete();
        $this->successMessage = 'Pengajuan notifikasi berhasil dihapus.';
        $this->loadData();
    }

    public function submit()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'target_audience' => 'required|in:all,android,iphone,premium',
            'deep_link' => 'nullable|string|max:255',
        ], [
            'title.required' => 'Judul notifikasi wajib diisi!',
            'message.required' => 'Pesan notifikasi wajib diisi!',
        ]);

        $data = [
            'sponsor_id' => $this->sponsor?->id,
            'title' => $this->title,
            'message' => $this->message,
            'deep_link' => $this->deep_link ?: ($this->sponsor?->website_url ?? '/shop'),
            'target_audience' => $this->target_audience,
        ];

        if ($this->sendType === 'instant') {
            $data['sent_at'] = now();
            $data['scheduled_at'] = null;
            $data['status'] = 'pending'; // In approval workflow it waits or sent
        } else {
            $data['scheduled_at'] = $this->scheduled_at ? \Carbon\Carbon::parse($this->scheduled_at) : now()->addDay();
            $data['sent_at'] = null;
            $data['status'] = 'pending';
        }

        if ($this->editingId) {
            $notif = PushNotification::where('sponsor_id', $this->sponsor->id)->findOrFail($this->editingId);
            $notif->update($data);
            $this->successMessage = 'Pengajuan notifikasi berhasil diperbarui!';
        } else {
            PushNotification::create($data);
            $this->successMessage = 'Pengajuan notifikasi berhasil diajukan! Menunggu approval Super Admin.';
        }

        $this->showModal = false;
        $this->loadData();
    }
};
?>

<div class="space-y-6">
    <!-- Alert Success / Info Toast -->
    @if($successMessage)
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs transition animate-fade-in dark:bg-emerald-950/40 dark:border-emerald-800/80 dark:text-emerald-300">
        <div class="flex items-center gap-2.5">
            <i class="fas fa-circle-check text-emerald-600 dark:text-emerald-400 text-base"></i>
            <span class="font-medium">{{ $successMessage }}</span>
        </div>
        <button type="button" wire:click="$set('successMessage', '')" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-200 cursor-pointer">
            Tutup
        </button>
    </div>
    @endif

    <!-- Guidelines Info Section (Horizontal Layout: Single clean white card with 3 divided sections) -->
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] border border-slate-100 dark:border-zinc-800 p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100 dark:divide-zinc-800 gap-4 md:gap-0">
            
            <!-- Section 1: Pengiriman Terjadwal -->
            <div class="flex items-start gap-3.5 md:pr-6">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1a68f0] flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40">
                    <i class="fas fa-bolt text-base"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Pengiriman Terjadwal</h4>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Pilih kirim langsung setelah di-approve atau jadwalkan pada jam aktif teknisi.</p>
                </div>
            </div>

            <!-- Section 2: Dukungan Deep Link -->
            <div class="flex items-start gap-3.5 pt-4 md:pt-0 md:px-6">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i class="fas fa-arrow-up-right-from-square text-base"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Dukungan Deep Link</h4>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Saat notifikasi ditekan, aplikasi langsung membuka katalog toko Anda.</p>
                </div>
            </div>

            <!-- Section 3: Target Segmentasi Audiens -->
            <div class="flex items-start gap-3.5 pt-4 md:pt-0 md:pl-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                    <i class="fas fa-users text-base"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-sm text-[#212121] dark:text-white leading-snug">Target Segmentasi Audiens</h4>
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 leading-relaxed">Targetkan siaran ke seluruh pengguna aktif atau spesifik OS (Android/iOS).</p>
                </div>
            </div>

        </div>
    </div>

    <!-- Data Table Card - SUBMISSION HISTORY MOCKUP (Riwayat Pengajuan Notifikasi: Sleek, spacious, borderless, NO ANALYTICS) -->
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] border border-slate-100 dark:border-zinc-800 overflow-hidden">
        <div class="px-6 py-4.5 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <h3 class="font-bold text-base text-[#212121] dark:text-white tracking-tight">Riwayat Pengajuan Notifikasi</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400">
                    {{ count($notifications) }}
                </span>
            </div>
            <p class="text-xs text-[#828282] dark:text-zinc-400 hidden sm:block">Status pengiriman pesan push broadcast ke ponsel</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950/40 text-[11px] font-bold text-[#828282] uppercase tracking-wider">
                        <th class="py-3.5 px-6">Judul & Pesan</th>
                        <th class="py-3.5 px-6 w-44">Target Pengguna</th>
                        <th class="py-3.5 px-6 w-52">Waktu Kirim / Jadwal</th>
                        <th class="py-3.5 px-6 w-48 text-center">Status (Approval)</th>
                        <th class="py-3.5 px-6 w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-sm">
                    @forelse($notifications as $notif)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition">
                        <!-- Column 1: Judul & Pesan -->
                        <td class="py-4 px-6 align-middle">
                            <div class="max-w-md">
                                <span class="font-bold text-[#212121] dark:text-white text-sm block">
                                    {{ $notif->title }}
                                </span>
                                <p class="text-xs text-[#828282] dark:text-zinc-400 mt-1 line-clamp-2 leading-relaxed">
                                    {{ $notif->message }}
                                </p>
                                @if($notif->deep_link)
                                <div class="inline-flex items-center gap-1.5 mt-1.5 text-[11px] text-[#1a68f0] hover:underline font-medium">
                                    <i class="fas fa-link text-[10px]"></i>
                                    <span class="truncate max-w-[280px]">{{ $notif->deep_link }}</span>
                                </div>
                                @endif
                            </div>
                        </td>

                        <!-- Column 2: Target Pengguna -->
                        <td class="py-4 px-6 align-middle">
                            @php
                                $audienceMap = [
                                    'all' => 'Semua Teknisi',
                                    'android' => 'Khusus Android',
                                    'iphone' => 'Khusus iPhone',
                                    'premium' => 'Member Premium'
                                ];
                                $labelAudience = $audienceMap[$notif->target_audience] ?? ucfirst($notif->target_audience);
                            @endphp
                            <div class="flex items-center gap-1.5 text-xs text-slate-700 dark:text-zinc-300 font-medium">
                                <i class="fas fa-users text-slate-400 text-xs"></i>
                                <span>{{ $labelAudience }}</span>
                            </div>
                        </td>

                        <!-- Column 3: Waktu Kirim / Jadwal -->
                        <td class="py-4 px-6 align-middle text-xs">
                            @if($notif->sent_at)
                                <div class="text-slate-700 dark:text-zinc-300 font-medium">
                                    {{ $notif->sent_at->translatedFormat('d M Y, H:i') }} WIB
                                </div>
                                <span class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-check-circle text-[9px]"></i> Langsung Terkirim
                                </span>
                            @elseif($notif->scheduled_at)
                                <div class="text-slate-700 dark:text-zinc-300 font-medium">
                                    {{ $notif->scheduled_at->translatedFormat('d M Y, H:i') }} WIB
                                </div>
                                <span class="text-[10px] text-amber-600 font-semibold flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-clock text-[9px]"></i> Terjadwal
                                </span>
                            @else
                                <span class="text-[#828282]">-</span>
                            @endif
                        </td>

                        <!-- Column 4: Status (Approval): Pastel Yellow / Pastel Green / Pastel Red -->
                        <td class="py-4 px-6 align-middle text-center">
                            @if($notif->status === 'sent')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Disetujui & Terkirim
                                </span>
                            @elseif($notif->status === 'pending' || $notif->status === 'scheduled')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Menunggu Approval
                                </span>
                            @elseif($notif->status === 'rejected')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/60 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ ucfirst($notif->status) }}
                                </span>
                            @endif
                        </td>

                        <!-- Column 5: Aksi (Clean, minimal icon-only buttons for Edit and Delete in subtle gray) -->
                        <td class="py-4 px-6 align-middle text-right">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" 
                                        wire:click="editNotification({{ $notif->id }})" 
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-[#1a68f0] hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                                        title="Edit Pengajuan">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                                <button type="button" 
                                        wire:click="deleteNotification({{ $notif->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus pengajuan notifikasi ini?"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
                                        title="Hapus Pengajuan">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-[#828282] dark:text-zinc-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mb-3 text-slate-400 dark:text-zinc-500">
                                    <i class="fas fa-bell text-xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-[#212121] dark:text-white">Belum Ada Pengajuan Notifikasi</span>
                                <p class="text-xs text-[#828282] dark:text-zinc-400 mt-1 max-w-sm">
                                    Kirim broadcast pesan ke perangkat ponsel teknisi dengan menekan tombol "+ Ajukan Notifikasi Baru".
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Pengajuan Push Notification (Strict Shopee Enterprise Light Mode) -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-fade-in">
        <div class="bg-white dark:bg-zinc-900 rounded-[16px] max-w-lg w-full p-6 shadow-2xl border border-slate-100 dark:border-zinc-800 space-y-5 max-h-[90vh] overflow-y-auto">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-[#1a68f0] flex items-center justify-center">
                        <i class="fas fa-bell text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-[#212121] dark:text-white">
                            {{ $editingId ? 'Edit Pengajuan Notifikasi' : 'Ajukan Push Notification Baru' }}
                        </h3>
                        <p class="text-xs text-[#828282] dark:text-zinc-400">Pesan akan dikirim setelah disetujui Super Admin</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('showModal', false)" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form wire:submit.prevent="submit" class="space-y-4">
                
                <!-- Judul Notifikasi -->
                <div>
                    <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                        Judul Notifikasi <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model="title" 
                           placeholder="Contoh: Flash Sale Solder 50%!" 
                           required 
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/30 focus:border-[#1a68f0] transition shadow-2xs">
                    @error('title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Isi Pesan Notifikasi -->
                <div>
                    <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                        Isi Pesan Notifikasi <span class="text-rose-500">*</span>
                    </label>
                    <textarea wire:model="message" 
                              rows="3" 
                              placeholder="Klaim voucher sekarang sebelum kehabisan untuk pembelian perkakas servis..." 
                              required 
                              class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/30 focus:border-[#1a68f0] transition shadow-2xs"></textarea>
                    @error('message') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Deep Link URL -->
                <div>
                    <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                        Target Link URL / Deep Link
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                            <i class="fas fa-link"></i>
                        </span>
                        <input type="text" 
                               wire:model="deep_link" 
                               placeholder="https://tokopedia.com/toko-anda atau /katalog" 
                               class="w-full pl-8 pr-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/30 focus:border-[#1a68f0] transition shadow-2xs">
                    </div>
                    <span class="text-[11px] text-[#828282] dark:text-zinc-400 mt-1 block">Arahkan pengguna langsung ke link toko atau halaman produk.</span>
                </div>

                <!-- Target Audience & Send Type -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                            Target Pengguna <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model="target_audience" 
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/30 focus:border-[#1a68f0] transition shadow-2xs">
                            <option value="all">Semua Teknisi</option>
                            <option value="android">Khusus Android</option>
                            <option value="iphone">Khusus iPhone</option>
                            <option value="premium">Member Premium</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                            Metode Pengiriman <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model="sendType" 
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/30 focus:border-[#1a68f0] transition shadow-2xs">
                            <option value="instant">Kirim Langsung Setelah Approve</option>
                            <option value="scheduled">Jadwalkan Waktu Tertentu</option>
                        </select>
                    </div>
                </div>

                @if($sendType === 'scheduled')
                <div class="animate-fade-in">
                    <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                        Jadwal Waktu Kirim <span class="text-rose-500">*</span>
                    </label>
                    <input type="datetime-local" 
                           wire:model="scheduled_at" 
                           required 
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/30 focus:border-[#1a68f0] transition shadow-2xs">
                </div>
                @endif

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <button type="button" 
                            wire:click="$set('showModal', false)" 
                            class="px-4 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800 text-xs sm:text-sm font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition cursor-pointer disabled:opacity-50">
                        <span wire:loading.remove>{{ $editingId ? 'Simpan Perubahan' : 'Ajukan Notifikasi' }}</span>
                        <span wire:loading class="inline-flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                            <span>Memproses...</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
