<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Toast Container Component
 * 
 * Renders toast notifications from session flash data.
 * Place this component in your main layout to enable toasts globally.
 * 
 * Usage in layout: <livewire:toast-container />
 */
class ToastContainer extends Component
{
    public $toasts = [];

    public function mount()
    {
        $this->toasts = session()->get('toasts', []);
    }

    public function dismissToast($id)
    {
        $this->toasts = array_filter($this->toasts, fn($toast) => $toast['id'] !== $id);
    }

    public function render()
    {
        return view('livewire.toast-container');
    }
}
