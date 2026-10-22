<?php

use Livewire\Component;
use App\Models\AdminNotification;
use App\Services\AdminNotificationService;

new class extends Component
{
    public $notifications = [];
    public $unreadCount = 0;
    public $filter = 'all'; // 'all' or 'unread'

    protected $listeners = [
        'refreshNotifications' => 'loadNotifications',
        'notificationCreated' => 'loadNotifications',
    ];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $service = app(AdminNotificationService::class);
        $res = $service->listForAdmin(
            adminId: auth()->id(),
            unreadOnly: $this->filter === 'unread',
            perPage: 15
        );

        $this->notifications = $res['data']->toArray();
        $this->unreadCount = $res['unread_count'];
    }

    public function setFilter($type)
    {
        $this->filter = $type;
        $this->loadNotifications();
    }

    public function openNotification($id)
    {
        $notif = AdminNotification::find($id);
        if ($notif) {
            $notif->markAsRead();
            $this->loadNotifications();

            if (!empty($notif->deep_link)) {
                return $this->redirect($notif->deep_link, navigate: true);
            }
        }
    }

    public function deleteNotification($id)
    {
        AdminNotification::where('id', $id)->delete();
        $this->loadNotifications();
    }
};
?>

<div class="relative" x-data="{ open: false }" wire:poll.15s="loadNotifications">
    <!-- Bell Button -->
    <button 
        type="button" 
        @click="open = !open; if(open) $wire.loadNotifications()"
        class="w-9 h-9 rounded-full bg-white dark:bg-zinc-900 border border-slate-200/80 dark:border-zinc-800 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors shadow-xs cursor-pointer focus:outline-none relative"
        aria-label="Pusat Notifikasi"
        title="Pusat Notifikasi Sistem"
    >
        <i class="fas fa-bell text-sm"></i>
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white dark:ring-zinc-900 animate-pulse">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Notification Dropdown Panel -->
    <div 
        x-show="open" 
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
        class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-[#242424] border border-slate-200 dark:border-[#3F3F46] shadow-2xl z-50 overflow-hidden flex flex-col text-[#212121] dark:text-[#F3F4F6]"
    >
        <!-- Header -->
        <div class="p-3.5 px-4 border-b border-slate-100 dark:border-[#3F3F46] flex items-center justify-between bg-slate-50/70 dark:bg-[#18181B]">
            <div class="flex items-center gap-2">
                <i class="fas fa-bell text-[#1a68f0] dark:text-blue-400 text-sm"></i>
                <h3 class="text-sm font-bold text-[#212121] dark:text-[#F3F4F6]">Notifikasi Sistem</h3>
                @if($unreadCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-950 text-[#1a68f0] dark:text-blue-300">
                        {{ $unreadCount }} baru
                    </span>
                @endif
            </div>

            @if($unreadCount > 0)
                <button 
                    type="button" 
                    wire:click="markAllAsRead" 
                    class="text-[11px] font-semibold text-[#1a68f0] dark:text-blue-400 hover:underline cursor-pointer"
                >
                    Tandai semua dibaca
                </button>
            @endif
        </div>

        <!-- Filter tabs -->
        <div class="flex border-b border-slate-100 dark:border-[#3F3F46] text-xs font-semibold px-3 bg-white dark:bg-[#242424]">
            <button 
                type="button" 
                wire:click="setFilter('all')" 
                class="py-2 px-3 border-b-2 transition-colors cursor-pointer {{ $filter === 'all' ? 'border-[#1a68f0] text-[#1a68f0] dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-zinc-400' }}"
            >
                Semua
            </button>
            <button 
                type="button" 
                wire:click="setFilter('unread')" 
                class="py-2 px-3 border-b-2 transition-colors cursor-pointer {{ $filter === 'unread' ? 'border-[#1a68f0] text-[#1a68f0] dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-zinc-400' }}"
            >
                Belum Dibaca ({{ $unreadCount }})
            </button>
        </div>

        <!-- Notification List -->
        <div class="max-h-[360px] overflow-y-auto divide-y divide-slate-100 dark:divide-[#3F3F46]/60">
            @forelse($notifications as $notif)
                <div 
                    class="p-3.5 hover:bg-slate-50/80 dark:hover:bg-[#333338]/60 transition-colors flex items-start gap-3 cursor-pointer group {{ !$notif['is_read'] ? 'bg-blue-50/30 dark:bg-blue-950/20' : '' }}"
                    wire:click="openNotification({{ $notif['id'] }})"
                >
                    <!-- Icon based on type -->
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs {{ !$notif['is_read'] ? 'bg-blue-100 text-[#1a68f0] dark:bg-blue-950 dark:text-blue-300' : 'bg-slate-100 text-slate-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                        @if(str_contains($notif['type'], 'product'))
                            <i class="fas fa-cube"></i>
                        @elseif(str_contains($notif['type'], 'campaign'))
                            <i class="fas fa-bullhorn"></i>
                        @elseif(str_contains($notif['type'], 'payment'))
                            <i class="fas fa-wallet"></i>
                        @elseif(str_contains($notif['type'], 'sponsor'))
                            <i class="fas fa-handshake"></i>
                        @elseif(str_contains($notif['type'], 'push_notification'))
                            <i class="fas fa-paper-plane"></i>
                        @else
                            <i class="fas fa-bell"></i>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-1">
                            <p class="text-xs font-bold text-[#212121] dark:text-[#F3F4F6] line-clamp-1 leading-snug">
                                {{ $notif['title'] }}
                            </p>
                            <div class="flex items-center gap-1 shrink-0">
                                @if(!$notif['is_read'])
                                    <span class="w-2 h-2 rounded-full bg-[#1a68f0]"></span>
                                @endif
                                <button 
                                    type="button" 
                                    wire:click.stop="deleteNotification({{ $notif['id'] }})"
                                    class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-500 transition-opacity p-0.5 cursor-pointer"
                                    title="Hapus notifikasi"
                                >
                                    <i class="fas fa-trash text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                        @if(!empty($notif['body']))
                            <p class="text-[11px] text-slate-500 dark:text-[#9CA3AF] mt-0.5 line-clamp-2 leading-relaxed">
                                {{ $notif['body'] }}
                            </p>
                        @endif
                        <span class="text-[10px] text-slate-400 dark:text-zinc-500 mt-1 block">
                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="py-10 px-4 text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-[#333338] text-slate-400 dark:text-zinc-500 flex items-center justify-center mx-auto mb-2.5">
                        <i class="fas fa-bell-slash text-base"></i>
                    </div>
                    <p class="text-xs font-semibold text-slate-600 dark:text-zinc-400">Tidak ada notifikasi</p>
                    <p class="text-[11px] text-slate-400 dark:text-zinc-500 mt-0.5">Semua pembaruan sistem akan muncul di sini.</p>
                </div>
            @endforelse
        </div>

        <!-- Footer -->
        <div class="p-2.5 border-t border-slate-100 dark:border-[#3F3F46] bg-slate-50/50 dark:bg-[#18181B] text-center">
            <a 
                href="{{ route('admin.notifications') }}" 
                wire:navigate 
                class="text-xs font-semibold text-[#1a68f0] dark:text-blue-400 hover:underline block"
                @click="open = false"
            >
                Buka Push Notification Manager →
            </a>
        </div>
    </div>
</div>
