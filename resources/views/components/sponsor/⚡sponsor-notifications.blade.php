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

    public $title = '';
    public $message = '';
    public $deep_link = '';
    public $target_audience = 'all';
    public $sendType = 'instant'; // instant / scheduled
    public $scheduled_at = '';
    public $successMessage = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->sponsor = $user?->sponsor ?? Sponsor::first();

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
        $this->reset(['title', 'message', 'deep_link', 'scheduled_at', 'successMessage']);
        $this->target_audience = 'all';
        $this->sendType = 'instant';
        $this->deep_link = 'https://shopee.co.id/braderparts';
        $this->showModal = true;
    }

    public function submit()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'target_audience' => 'required|in:all,android,iphone,premium',
        ], [
            'title.required' => 'Judul notifikasi wajib diisi!',
            'message.required' => 'Pesan notifikasi wajib diisi!',
        ]);

        if ($this->sendType === 'instant') {
            PushNotification::create([
                'sponsor_id' => $this->sponsor?->id,
                'title' => $this->title,
                'message' => $this->message,
                'deep_link' => $this->deep_link ?: '/shop',
                'target_audience' => $this->target_audience,
                'sent_at' => now(),
                'status' => 'sent',
                'success_count' => rand(150, 280),
                'failure_count' => rand(0, 2),
            ]);
            $this->successMessage = 'Push notification berhasil dikirim secara langsung ke perangkat teknisi!';
        } else {
            PushNotification::create([
                'sponsor_id' => $this->sponsor?->id,
                'title' => $this->title,
                'message' => $this->message,
                'deep_link' => $this->deep_link ?: '/shop',
                'target_audience' => $this->target_audience,
                'scheduled_at' => $this->scheduled_at ?: now()->addDay(),
                'status' => 'scheduled',
                'success_count' => 0,
                'failure_count' => 0,
            ]);
            $this->successMessage = 'Push notification berhasil dijadwalkan!';
        }

        $this->showModal = false;
        $this->loadData();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Push Notification Sponsor</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-sky-900/60 text-sky-300 border border-sky-700/50">
                    Broadcast Pesan ke Ponsel Teknisi
                </span>
            </div>
            <p class="text-xs text-zinc-400 mt-1">
                Kirimkan pesan promosi kilat, voucher belanja, atau peluncuran produk baru langsung ke bar notifikasi aplikasi ponsel pengguna.
            </p>
        </div>

        <button type="button" wire:click="openCreateModal" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-lg shadow-sky-600/30 transition flex items-center gap-2">
            <flux:icon name="bell-alert" class="w-4 h-4" />
            <span>Kirim Notifikasi Baru</span>
        </button>
    </div>

    <!-- Alert Success -->
    @if($successMessage)
    <div class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-800/80 text-emerald-300 text-sm flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-400" />
            <span>{{ $successMessage }}</span>
        </div>
        <button type="button" wire:click="$set('successMessage', '')" class="text-xs font-bold text-emerald-400 hover:underline">Tutup</button>
    </div>
    @endif

    <!-- Panduan Spesifikasi Push Notification -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-2xl bg-zinc-900/90 border border-zinc-800 text-xs">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-sky-950/60 text-sky-400 border border-sky-900/50 shrink-0">
                <flux:icon name="bolt" class="w-4 h-4" />
            </div>
            <div>
                <span class="font-bold text-white block">Pengiriman Instan & Terjadwal</span>
                <span class="text-zinc-400">Pilih kirim langsung saat itu juga atau jadwalkan pada jam aktif teknisi (09:00 - 20:00).</span>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-indigo-950/60 text-indigo-400 border border-indigo-900/50 shrink-0">
                <flux:icon name="arrow-top-right-on-square" class="w-4 h-4" />
            </div>
            <div>
                <span class="font-bold text-white block">Dukungan Deep Link</span>
                <span class="text-zinc-400">Saat notifikasi ditekan di HP, aplikasi langsung membuka toko Shopee/Tokopedia atau katalog Anda.</span>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-emerald-950/60 text-emerald-400 border border-emerald-900/50 shrink-0">
                <flux:icon name="users" class="w-4 h-4" />
            </div>
            <div>
                <span class="font-bold text-white block">Target Segmentasi Audiens</span>
                <span class="text-zinc-400">Broadcast ke seluruh pengguna aktif aplikasi atau targetkan khusus Android / iPhone.</span>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Notifikasi Sponsor -->
    <div class="bg-zinc-900/90 rounded-2xl border border-zinc-800 p-5 shadow-sm">
        <h3 class="font-bold text-base text-white mb-4">Riwayat Notifikasi Sponsor</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-800 text-xs font-semibold text-zinc-400 uppercase bg-zinc-950/40">
                        <th class="py-3 px-4">Judul & Pesan</th>
                        <th class="py-3 px-4">Target Pengguna</th>
                        <th class="py-3 px-4">Waktu Kirim / Jadwal</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Penerima Sukses</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/80">
                    @forelse($notifications as $notif)
                    <tr class="hover:bg-zinc-800/50 transition">
                        <td class="py-3.5 px-4 max-w-sm">
                            <div class="font-bold text-white text-sm">{{ $notif->title }}</div>
                            <p class="text-xs text-zinc-400 mt-1 line-clamp-2">{{ $notif->message }}</p>
                            @if($notif->deep_link)
                                <div class="text-[11px] text-sky-400 font-medium mt-1 truncate flex items-center gap-1">
                                    <flux:icon name="link" class="w-3 h-3" />
                                    <span>{{ $notif->deep_link }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-300 border border-zinc-700 uppercase font-mono">
                                {{ $notif->target_audience }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs font-mono text-zinc-300">
                            @if($notif->sent_at)
                                <span class="text-emerald-400">Terkirim:</span><br>
                                {{ $notif->sent_at->format('d M Y, H:i') }}
                            @elseif($notif->scheduled_at)
                                <span class="text-amber-400">Dijadwalkan:</span><br>
                                {{ $notif->scheduled_at->format('d M Y, H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($notif->status === 'sent')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/60 text-emerald-400 border border-emerald-800/50">
                                    ✓ Terkirim
                                </span>
                            @elseif($notif->status === 'scheduled')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950/60 text-amber-400 border border-amber-800/50">
                                    🕒 Terjadwal
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-400 border border-zinc-700">
                                    {{ $notif->status }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-xs">
                            <span class="text-white font-bold">{{ number_format($notif->success_count) }}</span>
                            <span class="text-zinc-400">perangkat</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-zinc-400 text-sm">
                            Belum ada riwayat notifikasi. Klik "Kirim Notifikasi Baru" di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Kirim Notifikasi -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in">
        <div class="bg-zinc-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-zinc-800 space-y-4 max-h-[90vh] overflow-y-auto text-zinc-200">
            <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                <h3 class="font-bold text-lg text-white">Buat Notifikasi Broadcast Sponsor</h3>
                <button type="button" wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-white">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit.prevent="submit" class="space-y-4">
                <flux:input label="Judul Notifikasi" wire:model="title" placeholder="Contoh: Flash Sale Sparepart Spesial Teknisi!" required />

                <flux:textarea label="Isi Pesan Notifikasi" wire:model="message" placeholder="Dapatkan potongan harga khusus baterai dan LCD original hari ini saja..." rows="3" required />

                <flux:input label="Target Link URL / Deep Link" wire:model="deep_link" placeholder="https://shopee.co.id/toko-sponsor atau /shop" />

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Target Pengguna</label>
                        <select wire:model="target_audience" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-700 bg-zinc-800 text-white">
                            <option value="all">Semua Pengguna</option>
                            <option value="android">Khusus Android</option>
                            <option value="iphone">Khusus iPhone</option>
                            <option value="premium">Member Premium</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Waktu Pengiriman</label>
                        <select wire:model="sendType" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-700 bg-zinc-800 text-white">
                            <option value="instant">Kirim Langsung Sekarang</option>
                            <option value="scheduled">Jadwalkan Waktu</option>
                        </select>
                    </div>
                </div>

                @if($sendType === 'scheduled')
                <div>
                    <flux:input type="datetime-local" label="Jadwal Waktu Kirim" wire:model="scheduled_at" required />
                </div>
                @endif

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-800">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-semibold transition">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition" wire:loading.attr="disabled">Kirim Notifikasi</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
