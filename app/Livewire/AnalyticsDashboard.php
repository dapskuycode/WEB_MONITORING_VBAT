<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserEntitlement;
use App\Models\LearningMaterial;
use App\Models\QuizAttempt;
use App\Models\Course;
use App\Models\Sponsor;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class AnalyticsDashboard extends Component
{
    use WithPagination;

    public $dateStart;
    public $dateEnd;
    public $sponsorFilter = '';
    public $placementFilter = '';
    public $activeTab = 'overview';

    public function mount()
    {
        $this->dateStart = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateEnd = Carbon::now()->format('Y-m-d');
    }

    public function getTotalUsersProperty()
    {
        return User::count();
    }

    public function getTotalSponsorsProperty()
    {
        return Sponsor::where('status', 'active')->count();
    }

    public function getTotalMaterialsProperty()
    {
        return LearningMaterial::where('status', 'published')->count();
    }

    public function getTotalAttemptsProperty()
    {
        return QuizAttempt::count();
    }

    public function getTopMaterialsProperty()
    {
        return LearningMaterial::withCount('views')
            ->orderBy('views_count', 'desc')
            ->limit(5)
            ->get();
    }

    public function getRecentUsersProperty()
    {
        return User::latest()
            ->with('sponsor')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.analytics-dashboard', [
            'totalUsers' => $this->totalUsers,
            'totalSponsors' => $this->totalSponsors,
            'totalMaterials' => $this->totalMaterials,
            'totalAttempts' => $this->totalAttempts,
            'topMaterials' => $this->topMaterials,
            'recentUsers' => $this->recentUsers,
        ]);
    }
}
