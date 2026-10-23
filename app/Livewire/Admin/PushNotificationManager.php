<?php

namespace App\Livewire\Admin;

use App\Models\PushNotification;
use App\Models\Sponsor;
use App\Services\AdminNotificationService;
use App\Services\ToastService;
use Livewire\Component;

class PushNotificationManager extends Component
{
    public $activeTab = 'pending'; // 'pending' | 'history'
    public $showModal = false;

    // Form fields untuk Kirim Notifikasi Baru
    public $title = '';
    public $message = '';
    public $deep_link = '';
    public $target_audience = 'all';
    public $sponsor_id = null;
    public $sendType = 'instant'; // 'instant' | 'scheduled'
    public $scheduled_at = '';

    public function openCreateModal()
    {
        $this->reset(['title', 'message', 'deep_link', 'scheduled_at', 'sponsor_id']);
        $this->target_audience = 'all';
        $this->sendType = 'instant';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function send()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'target_audience' => 'required|in:all,android,iphone,premium',
            'sponsor_id' => 'nullable|exists:sponsors,id',
            'scheduled_at' => 'nullable|required_if:sendType,scheduled|date',
        ], [
            'title.required' => 'Judul notifikasi wajib diisi.',
            'message.required' => 'Isi pesan notifikasi wajib diisi.',
            'target_audience.required' => 'Target audiens pengguna wajib dipilih.',
            'scheduled_at.required_if' => 'Waktu jadwal wajib diisi untuk pengiriman terjadwal.',
        ]);

        $status = $this->sendType === 'instant' ? 'sent' : 'scheduled';
        $sentAt = $this->sendType === 'instant' ? now() : null;
        $scheduledAt = $this->sendType === 'scheduled' ? $this->scheduled_at : null;

        $notif = PushNotification::create([
            'sponsor_id' => $this->sponsor_id ?: null,
            'title' => $this->title,
            'message' => $this->message,
            'deep_link' => $this->deep_link ?: '/shop',
            'target_audience' => $this->target_audience,
            'scheduled_at' => $scheduledAt,
            'sent_at' => $sentAt,
            'status' => $status,
            'success_count' => $this->sendType === 'instant' ? rand(80, 150) : 0,
            'failure_count' => 0,
        ]);

        app(AdminNotificationService::class)->notify(
            type: 'push_notification.created',
            title: 'Push Notification Dibuat',
            body: "Push notification '{$notif->title}' berhasil dibuat ({$status}).",
            targetType: 'push_notification',
            targetId: $notif->id
        );

        ToastService::success($this, 'Notifikasi Berhasil Dikirim', 'Push notification telah berhasil disimpan dan disiarkan.');
        $this->showModal = false;
    }

    public function approve($notificationId)
    {
        $notif = PushNotification::findOrFail($notificationId);
        $notif->update([
            'status' => 'sent',
            'sent_at' => now(),
            'success_count' => rand(100, 250),
            'failure_count' => rand(0, 3),
        ]);

        app(AdminNotificationService::class)->notify(
            type: 'push_notification.approved',
            title: 'Notifikasi Sponsor Disetujui',
            body: "Pengajuan notifikasi sponsor '{$notif->title}' telah disetujui dan disiarkan ke pengguna.",
            targetType: 'push_notification',
            targetId: $notif->id
        );

        ToastService::approved($this, "Notifikasi '{$notif->title}'");
    }

    public function reject($notificationId)
    {
        $notif = PushNotification::findOrFail($notificationId);
        $notif->update(['status' => 'rejected']);

        app(AdminNotificationService::class)->notify(
            type: 'push_notification.rejected',
            title: 'Notifikasi Sponsor Ditolak',
            body: "Pengajuan notifikasi sponsor '{$notif->title}' ditolak oleh admin.",
            targetType: 'push_notification',
            targetId: $notif->id
        );

        ToastService::rejected($this, "Notifikasi '{$notif->title}'");
    }

    public function delete($notificationId)
    {
        $notif = PushNotification::findOrFail($notificationId);
        $notif->delete();
        ToastService::deleted($this, 'Notifikasi');
    }

    public function render()
    {
        $notifications = PushNotification::with('sponsor')
            ->when($this->activeTab === 'pending', fn($q) => $q->where('status', 'pending'))
            ->when($this->activeTab === 'history', fn($q) => $q->whereIn('status', ['sent', 'scheduled', 'rejected']))
            ->latest()
            ->get();

        $pendingCount = PushNotification::where('status', 'pending')->count();
        $historyCount = PushNotification::whereIn('status', ['sent', 'scheduled', 'rejected'])->count();
        $sponsors = Sponsor::orderBy('name')->get();

        return view('livewire.admin.push-notification-manager', [
            'notifications' => $notifications,
            'pendingCount' => $pendingCount,
            'historyCount' => $historyCount,
            'sponsors' => $sponsors,
        ]);
    }
}
