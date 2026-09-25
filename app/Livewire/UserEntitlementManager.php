<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Course;
use App\Models\UserEntitlement;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class UserEntitlementManager extends Component
{
    use WithPagination;

    // ===== PUBLIC PROPERTIES =====
    public $activeTab = 'grant'; // 'grant', 'entitlements', 'batch'
    public $grantUserId = null;
    public $grantCourseId = null;
    public $grantExpiresAt = null;
    public $grantNotes = '';

    public $selectedEntitlementId = null;
    public $editExpiresAt = null;
    public $editStatus = 'active'; // active, expired, revoked

    public $batchUserId = null;
    public $batchCourseIds = [];
    public $batchExpiresAt = null;
    public $batchNotes = '';

    public $searchTerm = '';
    public $userFilter = '';
    public $courseFilter = '';

    // ===== COMPUTED =====
    public function getUsersProperty()
    {
        return User::select('id', 'name', 'email')
            ->where('role', 'user')
            ->orderBy('name')
            ->get();
    }

    public function getCoursesProperty()
    {
        return Course::select('id', 'title', 'slug')
            ->orderBy('title')
            ->get();
    }

    public function getEntitlementsProperty(): LengthAwarePaginator
    {
        $query = UserEntitlement::with(['user', 'course']);

        if ($this->searchTerm) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', "%{$this->searchTerm}%")
                  ->orWhere('email', 'like', "%{$this->searchTerm}%");
            });
        }

        if ($this->userFilter) {
            $query->where('user_id', $this->userFilter);
        }

        if ($this->courseFilter) {
            $query->where('course_id', $this->courseFilter);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    // ===== ACTIONS =====
    public function saveGrant()
    {
        $this->validate([
            'grantUserId' => 'required|exists:users,id',
            'grantCourseId' => 'required|exists:courses,id',
            'grantExpiresAt' => 'nullable|date|after:now',
            'grantNotes' => 'nullable|string|max:500',
        ]);

        $entitlement = UserEntitlement::create([
            'user_id' => $this->grantUserId,
            'course_id' => $this->grantCourseId,
            'expires_at' => $this->grantExpiresAt,
            'notes' => $this->grantNotes,
            'status' => 'active',
            'granted_by' => auth()->id(),
        ]);

        session()->flash('message', "Course access granted successfully.");
        $this->resetGrantForm();
    }

    public function resetGrantForm()
    {
        $this->grantUserId = null;
        $this->grantCourseId = null;
        $this->grantExpiresAt = null;
        $this->grantNotes = '';
    }

    public function editEntitlement($id)
    {
        $entitlement = UserEntitlement::findOrFail($id);
        $this->selectedEntitlementId = $entitlement->id;
        $this->editExpiresAt = $entitlement->expires_at?->format('Y-m-d');
        $this->editStatus = $entitlement->status;
    }

    public function saveEntitlement()
    {
        $this->validate([
            'editExpiresAt' => 'nullable|date',
            'editStatus' => 'required|in:active,expired,revoked',
        ]);

        $entitlement = UserEntitlement::findOrFail($this->selectedEntitlementId);
        $entitlement->update([
            'expires_at' => $this->editExpiresAt,
            'status' => $this->editStatus,
        ]);

        session()->flash('message', "Entitlement updated.");
        $this->resetEntitlementForm();
    }

    public function deleteEntitlement($id)
    {
        $entitlement = UserEntitlement::findOrFail($id);
        $entitlement->delete();
        session()->flash('message', "Entitlement deleted.");
    }

    public function resetEntitlementForm()
    {
        $this->selectedEntitlementId = null;
        $this->editExpiresAt = null;
        $this->editStatus = 'active';
    }

    public function saveBatch()
    {
        $this->validate([
            'batchUserId' => 'required|exists:users,id',
            'batchCourseIds' => 'required|array|min:1',
            'batchCourseIds.*' => 'exists:courses,id',
            'batchExpiresAt' => 'nullable|date|after:now',
            'batchNotes' => 'nullable|string|max:500',
        ]);

        $count = 0;
        foreach ($this->batchCourseIds as $courseId) {
            UserEntitlement::create([
                'user_id' => $this->batchUserId,
                'course_id' => $courseId,
                'expires_at' => $this->batchExpiresAt,
                'notes' => $this->batchNotes,
                'status' => 'active',
                'granted_by' => auth()->id(),
            ]);
            $count++;
        }

        session()->flash('message', "{$count} course(s) granted successfully.");
        $this->resetBatchForm();
    }

    public function resetBatchForm()
    {
        $this->batchUserId = null;
        $this->batchCourseIds = [];
        $this->batchExpiresAt = null;
        $this->batchNotes = '';
    }

    // ===== RENDER =====
    public function render()
    {
        return view('livewire.user-entitlement-manager', [
            'users' => $this->users,
            'courses' => $this->courses,
            'entitlements' => $this->entitlements,
        ]);
    }
}
