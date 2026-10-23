<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component {}; ?>

<div>
    <!-- Danger Zone Card -->
    <div class="mt-8 bg-white dark:bg-zinc-900 rounded-xl shadow-sm overflow-hidden border border-red-100 dark:border-red-900/50">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-red-100 dark:border-red-900/40 bg-red-50/30 dark:bg-red-950/20">
            <h3 class="text-lg font-semibold text-red-700 dark:text-red-400">Hapus akun</h3>
            <p class="text-sm text-red-600 dark:text-red-300 mt-1">Akun dan semua data Anda akan dihapus secara permanen dan tidak dapat dipulihkan</p>
        </div>

        <!-- Card Content -->
        <div class="px-6 py-6">
            <flux:modal.trigger name="confirm-user-deletion">
                <button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-red-300 dark:border-red-800 text-red-600 dark:text-red-400 font-medium text-sm transition-all hover:bg-red-50 dark:hover:bg-red-950/40 hover:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" data-test="delete-user-button">
                    <i class="fas fa-trash-alt"></i>
                    Hapus akun
                </button>
            </flux:modal.trigger>
        </div>
    </div>

    <livewire:pages::settings.delete-user-modal />
</div>
