<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class PushNotificationManager extends Component
{
    public $activeTab = 'pending'; // pending | history
    
    // ponytail: sample data for UI demo, replace with real DB query when FCM integration ready
    public function getPendingNotifications()
    {
        return [
            [
                'id' => 1,
                'title' => 'Promo Flash Sale - Diskon 50% Hari Ini!',
                'message' => 'Dapatkan diskon hingga 50% untuk semua produk elektronik. Buruan sebelum kehabisan!',
                'sponsor' => 'PT Samsung Electronics',
                'target' => 'Semua Teknisi',
                'status' => 'pending',
            ],
            [
                'id' => 2,
                'title' => 'Info: Perubahan Jam Operasional',
                'message' => 'Mulai tanggal 25 Oktober, jam operasional service center berubah menjadi 08:00 - 20:00.',
                'sponsor' => 'PT VIVO Mobile Indonesia',
                'target' => 'Teknisi Area Jakarta',
                'status' => 'pending',
            ],
            [
                'id' => 3,
                'title' => 'Workshop Gratis - Teknik Servis Layar OLED',
                'message' => 'Ikuti workshop eksklusif tentang repair layar OLED. Sertifikat dan doorprize menarik!',
                'sponsor' => 'PT Xiaomi Indonesia',
                'target' => 'Semua Teknisi',
                'status' => 'pending',
            ],
        ];
    }

    public function approve($notificationId)
    {
        // ponytail: implement FCM send + DB update
        session()->flash('message', 'Notifikasi berhasil disetujui dan dikirim!');
    }

    public function reject($notificationId)
    {
        // ponytail: implement DB update status to rejected
        session()->flash('message', 'Notifikasi ditolak.');
    }

    public function render()
    {
        return view('livewire.admin.push-notification-manager', [
            'pendingNotifications' => $this->getPendingNotifications(),
        ]);
    }
}
