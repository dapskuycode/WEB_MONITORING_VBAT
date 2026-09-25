<?php

namespace App\Livewire\Admin;

use App\Models\Badge;
use App\Models\Achievement;
use App\Models\Streak;
use Livewire\Component;
use Livewire\WithPagination;

class GamificationManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showBadgeModal = false;
    public $showAchievementModal = false;
    public $showStreakConfigModal = false;

    // Badge form
    public $badgeCode;
    public $badgeName;
    public $badgeDescription;
    public $badgeIconUrl;
    public $badgeXpReward;

    // Achievement filter
    public $userId;

    // Streak config
    public $graceDays = 2;
    public $timezone = 'Asia/Jakarta';

    public function mount()
    {
        $this->graceDays = config('gamification.grace_days', 2);
        $this->timezone = config('app.timezone', 'Asia/Jakarta');
    }

    public function openBadgeModal($id = null)
    {
        if ($id) {
            $badge = Badge::find($id);
            $this->badgeCode = $badge->code;
            $this->badgeName = $badge->name;
            $this->badgeDescription = $badge->description;
            $this->badgeIconUrl = $badge->icon_url;
            $this->badgeXpReward = $badge->xp_reward;
        } else {
            $this->resetBadgeForm();
        }
        $this->showBadgeModal = true;
    }

    public function resetBadgeForm()
    {
        $this->badgeCode = null;
        $this->badgeName = null;
        $this->badgeDescription = null;
        $this->badgeIconUrl = null;
        $this->badgeXpReward = 0;
    }

    public function saveBadge()
    {
        Badge::updateOrCreate(
            ['code' => $this->badgeCode],
            [
                'name' => $this->badgeName,
                'description' => $this->badgeDescription,
                'icon_url' => $this->badgeIconUrl,
                'xp_reward' => $this->badgeXpReward,
            ]
        );

        $this->showBadgeModal = false;
        session()->flash('message', 'Badge disimpan');
    }

    public function deleteBadge($id)
    {
        Badge::find($id)->delete();
        session()->flash('message', 'Badge dihapus');
    }

    public function render()
    {
        $badges = Badge::query()
            ->when($this->search, fn($q) => $q->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%"))
            ->paginate(10);

        $achievements = Achievement::query()
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->with('badge', 'user')
            ->latest()
            ->paginate(20);

        return view('livewire.admin.gamification-manager', [
            'badges' => $badges,
            'achievements' => $achievements,
        ]);
    }
}
