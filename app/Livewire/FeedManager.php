<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Models\BestDeal;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\PlacementConfig;
use App\Models\FeedConfig;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class FeedManager extends Component
{
    use WithFileUploads, WithPagination;

    public $activeTab = 'campaigns';

    // Campaign properties
    public $selectedCampaignId = null;
    public $campaignTitle = '';
    public $campaignSponsorId = null;
    public $campaignPlacementType = 'hero';
    public $campaignMediaFile = null;
    public $campaignMediaType = 'image';
    public $campaignTargetUrl = '';
    public $campaignDescription = '';
    public $campaignDailyLimit = null;
    public $campaignWeight = 0;
    public $campaignStartDate = '';
    public $campaignEndDate = '';
    public $campaignStatus = 'draft';

    // BestDeal properties
    public $selectedBestDealId = null;
    public $bestDealTitle = '';
    public $bestDealDescription = '';
    public $bestDealBannerFile = null;
    public $bestDealStartAt = '';
    public $bestDealEndAt = '';
    public $bestDealIsActive = true;
    public $bestDealSponsorProductId = null;
    public $bestDealProducts = [];

    // PlacementConfig properties
    public $selectedPlacementId = null;
    public $placementType = '';
    public $placementEnabled = true;
    public $placementProbability = 50;
    public $placementMaxDaily = null;

    // FeedConfig properties
    public $selectedFeedId = null;
    public $feedType = '';
    public $feedEnabled = true;
    public $feedProbability = 50;

    // Filters
    public $campaignSearch = '';
    public $sponsorFilter = '';
    public $placementFilter = '';
    public $statusFilter = '';

    public function mount()
    {
        $this->resetCampaignForm();
        $this->resetBestDealForm();
        $this->resetPlacementForm();
        $this->resetFeedForm();
    }

    public function render()
    {
        return view('livewire.feed-manager', [
            'campaigns' => $this->getCampaigns(),
            'sponsors' => Sponsor::where('is_active', true)->orderBy('name')->get(),
            'bestDeals' => BestDeal::with('products')->paginate(10),
            'sponsorProducts' => SponsorProduct::where('is_active', true)->get(),
            'placementConfigs' => PlacementConfig::all(),
            'feedConfigs' => FeedConfig::all(),
        ]);
    }

    private function getCampaigns()
    {
        return Campaign::with('sponsor')
            ->when($this->campaignSearch, fn($q) => $q->where('title', 'like', "%{$this->campaignSearch}%"))
            ->when($this->sponsorFilter, fn($q) => $q->where('sponsor_id', $this->sponsorFilter))
            ->when($this->placementFilter, fn($q) => $q->where('placement_type', $this->placementFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    // ===== CAMPAIGN CRUD =====
    public function saveCampaign()
    {
        $this->validate([
            'campaignTitle' => 'required|string|max:255',
            'campaignSponsorId' => 'required|exists:sponsors,id',
            'campaignPlacementType' => 'required|in:hero,card,horizontal,popup',
            'campaignTargetUrl' => 'nullable|url',
        ]);

        $data = [
            'title' => $this->campaignTitle,
            'sponsor_id' => $this->campaignSponsorId,
            'placement_type' => $this->campaignPlacementType,
            'media_type' => $this->campaignMediaType,
            'target_url' => $this->campaignTargetUrl,
            'description' => $this->campaignDescription,
            'daily_limit' => $this->campaignDailyLimit,
            'weight' => $this->campaignWeight,
            'start_date' => $this->campaignStartDate ?: null,
            'end_date' => $this->campaignEndDate ?: null,
            'status' => $this->campaignStatus,
        ];

        if ($this->campaignMediaFile) {
            $data['media_path'] = $this->campaignMediaFile->store('campaigns', 'public');
        }

        if ($this->selectedCampaignId) {
            $campaign = Campaign::findOrFail($this->selectedCampaignId);
            $campaign->update($data);
        } else {
            $campaign = Campaign::create($data);
        }

        session()->flash('message', $this->selectedCampaignId ? 'Campaign updated.' : 'Campaign created.');
        $this->resetCampaignForm();
    }

    public function editCampaign($id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->selectedCampaignId = $campaign->id;
        $this->campaignTitle = $campaign->title;
        $this->campaignSponsorId = $campaign->sponsor_id;
        $this->campaignPlacementType = $campaign->placement_type;
        $this->campaignMediaType = $campaign->media_type;
        $this->campaignTargetUrl = $campaign->target_url;
        $this->campaignDescription = $campaign->description;
        $this->campaignDailyLimit = $campaign->daily_limit;
        $this->campaignWeight = $campaign->weight;
        $this->campaignStartDate = $campaign->start_date?->format('Y-m-d');
        $this->campaignEndDate = $campaign->end_date?->format('Y-m-d');
        $this->campaignStatus = $campaign->status;
    }

    public function deleteCampaign($id)
    {
        Campaign::findOrFail($id)->delete();
        session()->flash('message', 'Campaign deleted.');
        $this->resetCampaignForm();
    }

    public function resetCampaignForm()
    {
        $this->selectedCampaignId = null;
        $this->campaignTitle = '';
        $this->campaignSponsorId = null;
        $this->campaignPlacementType = 'hero';
        $this->campaignMediaFile = null;
        $this->campaignMediaType = 'image';
        $this->campaignTargetUrl = '';
        $this->campaignDescription = '';
        $this->campaignDailyLimit = null;
        $this->campaignWeight = 0;
        $this->campaignStartDate = '';
        $this->campaignEndDate = '';
        $this->campaignStatus = 'draft';
    }

    // ===== BEST DEAL CRUD =====
    public function saveBestDeal()
    {
        $this->validate([
            'bestDealTitle' => 'required|string|max:255',
            'bestDealSponsorProductId' => 'nullable|exists:sponsor_products,id',
        ]);

        $data = [
            'title' => $this->bestDealTitle,
            'description' => $this->bestDealDescription,
            'start_at' => $this->bestDealStartAt ?: null,
            'end_at' => $this->bestDealEndAt ?: null,
            'is_active' => $this->bestDealIsActive,
            'sponsor_product_id' => $this->bestDealSponsorProductId,
            'selected_by' => auth()->id(),
            'selection_type' => 'admin',
        ];

        if ($this->bestDealBannerFile) {
            $data['banner_path'] = $this->bestDealBannerFile->store('best-deals', 'public');
        }

        if ($this->selectedBestDealId) {
            $bestDeal = BestDeal::findOrFail($this->selectedBestDealId);
            $bestDeal->update($data);
        } else {
            $bestDeal = BestDeal::create($data);
        }

        // Sync products
        if (!empty($this->bestDealProducts)) {
            $bestDeal->products()->sync($this->bestDealProducts);
        }

        session()->flash('message', $this->selectedBestDealId ? 'Best Deal updated.' : 'Best Deal created.');
        $this->resetBestDealForm();
    }

    public function editBestDeal($id)
    {
        $deal = BestDeal::with('products')->findOrFail($id);
        $this->selectedBestDealId = $deal->id;
        $this->bestDealTitle = $deal->title;
        $this->bestDealDescription = $deal->description;
        $this->bestDealStartAt = $deal->start_at?->format('Y-m-d H:i');
        $this->bestDealEndAt = $deal->end_at?->format('Y-m-d H:i');
        $this->bestDealIsActive = $deal->is_active;
        $this->bestDealSponsorProductId = $deal->sponsor_product_id;
        $this->bestDealProducts = $deal->products->pluck('id')->toArray();
    }

    public function deleteBestDeal($id)
    {
        BestDeal::findOrFail($id)->delete();
        session()->flash('message', 'Best Deal deleted.');
        $this->resetBestDealForm();
    }

    public function resetBestDealForm()
    {
        $this->selectedBestDealId = null;
        $this->bestDealTitle = '';
        $this->bestDealDescription = '';
        $this->bestDealBannerFile = null;
        $this->bestDealStartAt = '';
        $this->bestDealEndAt = '';
        $this->bestDealIsActive = true;
        $this->bestDealSponsorProductId = null;
        $this->bestDealProducts = [];
    }

    // ===== PLACEMENT CONFIG CRUD =====
    public function savePlacementConfig()
    {
        $this->validate([
            'placementType' => 'required|string|in:hero,card,horizontal,popup',
            'placementProbability' => 'required|integer|min:0|max:100',
        ]);

        PlacementConfig::updateOrCreate(
            ['type' => $this->placementType],
            [
                'enabled' => $this->placementEnabled,
                'probability' => $this->placementProbability,
                'max_daily_impressions' => $this->placementMaxDaily,
            ]
        );

        session()->flash('message', 'Placement config saved.');
        $this->resetPlacementForm();
    }

    public function editPlacementConfig($id)
    {
        $config = PlacementConfig::findOrFail($id);
        $this->selectedPlacementId = $config->id;
        $this->placementType = $config->type;
        $this->placementEnabled = $config->enabled;
        $this->placementProbability = $config->probability;
        $this->placementMaxDaily = $config->max_daily_impressions;
    }

    public function deletePlacementConfig($id)
    {
        PlacementConfig::findOrFail($id)->delete();
        session()->flash('message', 'Placement config deleted.');
        $this->resetPlacementForm();
    }

    public function resetPlacementForm()
    {
        $this->selectedPlacementId = null;
        $this->placementType = '';
        $this->placementEnabled = true;
        $this->placementProbability = 50;
        $this->placementMaxDaily = null;
    }

    // ===== FEED CONFIG CRUD =====
    public function saveFeedConfig()
    {
        $this->validate([
            'feedType' => 'required|string|in:partner_feed,best_deals,informasi,lowongan_kerja,magang',
            'feedProbability' => 'required|integer|min:0|max:100',
        ]);

        FeedConfig::updateOrCreate(
            ['feed_type' => $this->feedType],
            [
                'enabled' => $this->feedEnabled,
                'probability' => $this->feedProbability,
            ]
        );

        session()->flash('message', 'Feed config saved.');
        $this->resetFeedForm();
    }

    public function editFeedConfig($id)
    {
        $config = FeedConfig::findOrFail($id);
        $this->selectedFeedId = $config->id;
        $this->feedType = $config->feed_type;
        $this->feedEnabled = $config->enabled;
        $this->feedProbability = $config->probability;
    }

    public function deleteFeedConfig($id)
    {
        FeedConfig::findOrFail($id)->delete();
        session()->flash('message', 'Feed config deleted.');
        $this->resetFeedForm();
    }

    public function resetFeedForm()
    {
        $this->selectedFeedId = null;
        $this->feedType = '';
        $this->feedEnabled = true;
        $this->feedProbability = 50;
    }
}
