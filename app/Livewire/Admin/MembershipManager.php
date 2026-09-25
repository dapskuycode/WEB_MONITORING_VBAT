<?php

namespace App\Livewire\Admin;

use App\Models\Membership;
use App\Models\User;
use App\Services\MembershipService;
use Livewire\Component;
use Livewire\WithPagination;

class MembershipManager extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedUser;
    public $showIssueModal = false;
    public $tier = 'basic';

    public function mount()
    {
        //
    }

    public function searchUser()
    {
        $this->resetPage();
    }

    public function openIssueModal($userId)
    {
        $this->selectedUser = User::find($userId);
        $this->showIssueModal = true;
    }

    public function issueMembership()
    {
        if (!$this->selectedUser) {
            return;
        }

        MembershipService::createFromPurchase($this->selectedUser, ['tier' => $this->tier]);

        $this->showIssueModal = false;
        $this->selectedUser = null;
        session()->flash('message', 'KTA berhasil diterbitkan');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->with('membership')
            ->paginate(20);

        return view('livewire.admin.membership-manager', [
            'users' => $users,
        ]);
    }
}
