<?php

use Livewire\Component;
use App\Models\PushNotification;

new class extends Component
{
    public $notifications;
    public $showModal = false;

    public $title = '';
    public $message = '';
    public $deep_link = '';
    public $target_audience = 'all';
    public $sendType = 'instant'; // instant / scheduled
    public $scheduled_at = '';

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $this->notifications = PushNotification::latest()->get();
    }

    public function openCreateModal()
    {
        $this->reset(['title', 'message', 'deep_link', 'scheduled_at']);
        $this->target_audience = 'all';
        $this->sendType = 'instant';
        $this->showModal = true;
    }

    public function send()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_audience' => 'required|in:all,android,iphone,premium',
        ]);

        if ($this->sendType === 'instant') {
            // Simulasi pengiriman broadcast FCM
            PushNotification::create([
                'title' => $this->title,
                'message' => $this->message,
                'deep_link' => $this->deep_link ?: '/shop',
                'target_audience' => $this->target_audience,
                'sent_at' => now(),
                'status' => 'sent',
                'success_count' => rand(85, 120),
                'failure_count' => rand(0, 3),
            ]);
        } else {
            PushNotification::create([
                'title' => $this->title,
                'message' => $this->message,
                'deep_link' => $this->deep_link ?: '/shop',
                'target_audience' => $this->target_audience,
                'scheduled_at' => $this->scheduled_at ?: now()->addDay(),
                'status' => 'scheduled',
            ]);
        }

        $this->showModal = false;
        $this->loadNotifications();
    }

    public function delete($id)
    {
        PushNotification::destroy($id);
        $this->loadNotifications();
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                Push Notification Manager (FCM)
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kirim pesan promosi sponsor & pengumuman penting langsung ke perangkat ponsel pengguna (Kirim Langsung atau Jadwalkan).
            </p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            Kirim Notifikasi Baru
        </button>
    </div>

    <!-- Tabel Riwayat Notifikasi -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-xs font-semibold text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-800">
                <tr>
                    <th class="px-6 py-4">Judul & Pesan</th>
                    <th class="px-6 py-4">Target Pengguna</th>
                    <th class="px-6 py-4">Jadwal / Waktu Kirim</th>
                    <th class="px-6 py-4">Status & Pengiriman</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($notifications as $notif)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-zinc-900 dark:text-white text-base">{{ $notif->title }}</div>
                            <p class="text-xs text-zinc-500 mt-0.5 line-clamp-2">{{ $notif->message }}</p>
                            @if ($notif->deep_link)
                                <div class="text-[11px] text-sky-600 font-medium mt-1">🔗 Deep-link: {{ $notif->deep_link }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                {{ strtoupper($notif->target_audience) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-zinc-600 dark:text-zinc-300">
                            @if ($notif->sent_at)
                                Terkirim: {{ $notif->sent_at->translatedFormat('d M Y, H:i') }}
                            @elseif ($notif->scheduled_at)
                                Dijadwalkan: {{ $notif->scheduled_at->translatedFormat('d M Y, H:i') }}
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($notif->status === 'sent')
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    ✓ Terkirim ({{ $notif->success_count }} sukses)
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    🕒 Terjadwal
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="delete({{ $notif->id }})" wire:confirm="Hapus log notifikasi ini?" class="text-xs text-rose-600 hover:underline font-semibold">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-zinc-500">Belum ada riwayat pesan notifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Form Kirim Notifikasi -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Susun Pesan Push Notification</h2>
                    <button wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Judul Notifikasi *</label>
                        <input type="text" wire:model="title" placeholder="Contoh: Flash Promo BraderParts Hari Ini!" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Isi Pesan Notifikasi *</label>
                        <textarea wire:model="message" rows="3" placeholder="Tuliskan pesan promosi sponsor yang memikat..." class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Target Pengguna</label>
                            <select wire:model="target_audience" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="all">Semua Pengguna (Broadcast)</option>
                                <option value="android">Khusus Kelas Android</option>
                                <option value="iphone">Khusus Kelas iPhone</option>
                                <option value="premium">Pengguna Premium / Alumni</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Tujuan Deep-link Aplikasi</label>
                            <input type="text" wire:model="deep_link" placeholder="/shop atau /product-detail" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 space-y-2">
                        <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">Metode Pengiriman:</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                                <input type="radio" wire:model.live="sendType" value="instant" class="text-sky-600">
                                Kirim Langsung Sekarang
                            </label>
                            <label class="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                                <input type="radio" wire:model.live="sendType" value="scheduled" class="text-sky-600">
                                Jadwalkan Waktu Kirim
                            </label>
                        </div>

                        @if ($sendType === 'scheduled')
                            <div class="pt-2">
                                <label class="block text-[11px] text-zinc-500 mb-1">Tanggal & Jam Pengiriman</label>
                                <input type="datetime-local" wire:model="scheduled_at" class="w-full px-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t dark:border-zinc-800">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">Batal</button>
                    <button wire:click="send" class="px-4 py-2 text-sm font-semibold rounded-xl bg-sky-600 hover:bg-sky-700 text-white shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        {{ $sendType === 'instant' ? 'Kirim Broadcast Sekarang' : 'Simpan Jadwal' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>