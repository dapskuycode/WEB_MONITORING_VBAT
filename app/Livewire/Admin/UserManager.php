<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\ToastService;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $showModal = false;
    public $selectedUserId = null;
    public $name = '';
    public $email = '';
    public $role = 'siswa';
    public $phone = '';

    protected $queryString = ['search', 'roleFilter'];

    public function openEditModal($id)
    {
        $user = User::findOrFail($id);
        $this->selectedUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->phone = $user->phone ?? '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['selectedUserId', 'name', 'email', 'role', 'phone']);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->selectedUserId,
            'role' => 'required|in:siswa,sponsor,super_admin,owner',
        ]);

        $user = User::findOrFail($this->selectedUserId);
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
        ]);

        ToastService::updated($this, 'User');
        $this->closeModal();
    }

    public function delete($id)
    {
        User::findOrFail($id)->delete();
        ToastService::deleted($this, 'User');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn($q) => $q->where('role', $this->roleFilter))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.user-manager', ['users' => $users]);
    }
}
