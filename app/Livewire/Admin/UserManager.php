<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class UserManager extends Component
{
    public $search = '';
    public $roleFilter = 'all';
    public $ktaStatusFilter = 'all';
    
    public function render()
    {
        // ponytail: backend filter logic ketika ada real data
        return view('livewire.admin.user-manager');
    }
}
