<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component {}; ?>

<div>
    <!-- Danger Zone Card -->
    <div class="mt-8 bg-white rounded-xl shadow-sm overflow-hidden border border-red-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-red-100 bg-red-50/30">
            <h3 class="text-lg font-semibold text-red-700">Hapus akun</h3>
            <p class="text-sm text-red-600 mt-1">Hapus akun Anda dan semua datanya</p>
        </div>

        <!-- Card Content -->
        <div class="px-6 py-6">
            <flux:modal.trigger name="confirm-user-deletion">
                <button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-red-300 text-red-600 font-medium text-sm transition-all hover:bg-red-50 hover:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" data-test="delete-user-button">
                    <i class="fas fa-trash-alt"></i>
                    Hapus akun
                </button>
            </flux:modal.trigger>
        </div>
    </div>

    <livewire:pages::settings.delete-user-modal />
</div>
