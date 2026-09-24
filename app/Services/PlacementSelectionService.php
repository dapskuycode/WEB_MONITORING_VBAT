<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\PlacementConfig;
use App\Models\Sponsor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlacementSelectionService
{
    /**
     * Select campaigns for a given placement type using weighted probabilistic selection.
     *
     * @param string $placementType e.g. 'hero_slider', 'horizontal_infeed', 'popup', 'card_infeed'
     * @param int|null $tierId Filter by tier (optional — if null, use all active configs)
     * @param int $slots How many slots to fill (overrides config max_slots if provided)
     * @return Collection<Campaign> Selected campaigns
     */
    public function select(string $placementType, ?int $tierId = null, int $slots = 0): Collection
    {
        // 1. Get active config for this placement type
        $configQuery = PlacementConfig::active()
            ->where('placement_type', $placementType)
            ->orderBy('sort_order');

        if ($tierId !== null) {
            $configQuery->where('tier_id', $tierId);
        }

        $config = $configQuery->first();

        if (!$config) {
            return new Collection();
        }

        $maxSlots = $slots > 0 ? $slots : $config->max_slots;

        // 2. Get eligible campaigns (weighted by share_of_voice per tier/config)
        $eligibleCampaigns = $this->getEligibleCampaigns($placementType, $config, $maxSlots);

        if ($eligibleCampaigns->isEmpty()) {
            // Fallback behavior
            return $config->fallback_behavior === 'show_default'
                ? $this->getDefaultCampaigns($placementType, $maxSlots)
                : new Collection();
        }

        // 3. Apply Share of Voice (SoV) weighting to candidates.
        //    Each candidate's base weight is boosted by its placement config's SoV.
        $sovWeightedCandidates = $this->applyShareOfVoiceWeighting($eligibleCampaigns, $placementType);

        // 4. Weighted probabilistic selection
        $selected = $this->weightedRandomSelect($sovWeightedCandidates, $maxSlots, $config->target_probability);

        // 4. Log selections for analytics
        $this->logSelections($selected, $placementType);

        return $selected;
    }

    /**
     * Get campaigns eligible for display.
     *
     * @return Collection<Campaign>
     */
    protected function getEligibleCampaigns(string $placementType, PlacementConfig $config, int $maxSlots): Collection
    {
        $now = now();

        return Campaign::where('placement_type', $placementType)
            ->where('status', 'active')
            ->where('start_date', '<=', $now->toDateString())
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now->toDateString());
            })
            ->when($config->tier_id, function ($q) use ($config) {
                // Filter by sponsor's tier
                $q->whereHas('sponsor', function ($sq) use ($config) {
                    $sq->where('tier_id', $config->tier_id);
                });
            })
            ->whereNull('deleted_at')
            ->with(['sponsor:id,name,slug,tier_id', 'sponsor.tier:id,slug,name'])
            ->limit($maxSlots * 3) // Get 3x candidates for better probability distribution
            ->get()
            ->filter(fn (Campaign $c) => $this->checkDailyLimit($c)); // Enforce daily limit
    }

    /**
     * Check if campaign hasn't exceeded its daily limit.
     */
    protected function checkDailyLimit(Campaign $campaign): bool
    {
        if ($campaign->daily_limit <= 0) {
            return true; // 0 = unlimited
        }

        $todayImpressions = CampaignLog::where('campaign_id', $campaign->id)
            ->where('event_type', 'impression')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $todayImpressions < $campaign->daily_limit;
    }

    /**
     * Weighted random selection using target probability.
     *
     * Higher weight campaigns get higher probability, but adjusted by target_probability.
     *
     * @param Collection<Campaign> $candidates
     * @return Collection<Campaign>
     */
    protected function weightedRandomSelect(Collection $candidates, int $maxSlots, float $targetProbability): Collection
    {
        if ($candidates->count() <= $maxSlots) {
            return $candidates;
        }

        // Calculate weights adjusted by target probability
        $totalWeight = $candidates->sum('weight');
        if ($totalWeight <= 0) {
            // Equal probability if all weights are 0
            return $candidates->random(min($maxSlots, $candidates->count()));
        }

        $selected = new Collection();
        $remaining = $candidates->keyBy('id');

        for ($i = 0; $i < $maxSlots && $remaining->isNotEmpty(); $i++) {
            // Calculate adjusted probabilities
            $remainingCount = $remaining->count();
            $adjustedWeights = $remaining->mapWithKeys(function (Campaign $c) use ($targetProbability, $totalWeight, $remainingCount) {
                $baseProb = ($c->weight / $totalWeight);
                // Blend with target probability: higher tiers get closer to target_probability
                $adjusted = $baseProb * $targetProbability + (1 - $targetProbability) / $remainingCount;
                return [$c->id => $adjusted];
            });

            // Normalize
            $weightSum = $adjustedWeights->sum();
            $normalized = $adjustedWeights->map(fn ($w) => $w / $weightSum);

            // Probabilistic pick
            $pickedId = $this->pickByProbability($normalized);

            if ($pickedId !== null) {
                $picked = $remaining->get($pickedId);
                $selected->push($picked);
                $remaining->forget($pickedId);
                // Recalculate total weight
                $totalWeight = $remaining->sum('weight');
            }
        }

        return $selected;
    }

    /**
     * Pick an item ID based on probability distribution.
     *
     * @param \Illuminate\Support\Collection<int, float> $probabilities Key=ID, Value=probability
     */
    protected function pickByProbability(Collection $probabilities): ?int
    {
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0.0;

        foreach ($probabilities as $id => $prob) {
            $cumulative += $prob;
            if ($rand <= $cumulative) {
                return $id;
            }
        }

        // Fallback to last item (floating point safety)
        return $probabilities->keys()->last();
    }

    /**
     * Log selection events for analytics.
     *
     * @param Collection<Campaign> $selected
     */
    protected function logSelections(Collection $selected, string $placementType): void
    {
        $now = now();
        $logs = $selected->map(function (Campaign $campaign) use ($placementType, $now) {
            return [
                'campaign_id' => $campaign->id,
                'sponsor_product_id' => null,
                'user_id' => null, // Anonymous — will be filled by impression event
                'event_type' => 'selection',
                'placement_context' => $placementType,
                'created_at' => $now,
            ];
        })->values()->toArray();

        if (!empty($logs)) {
            DB::table('campaign_logs')->insert($logs);
        }
    }

    /**
     * Apply Share of Voice (SoV) weighting to campaigns.
     *
     * Each campaign's base weight is multiplied by (1 + share_of_voice)
     * from its sponsor's placement config. Higher SoV = higher chance.
     *
     * @param Collection<Campaign> $candidates
     * @return Collection<Campaign> (with modified weight attribute)
     */
    protected function applyShareOfVoiceWeighting(Collection $candidates, string $placementType): Collection
    {
        // Fetch SoV values for all relevant tier configs in one query
        $sovMap = PlacementConfig::active()
            ->where('placement_type', $placementType)
            ->whereNotNull('tier_id')
            ->pluck('share_of_voice', 'tier_id')
            ->toArray();

        return $candidates->map(function (Campaign $campaign) use ($sovMap) {
            $tierId = $campaign->sponsor?->tier_id;
            $sov = ($tierId !== null && isset($sovMap[$tierId]))
                ? (float) $sovMap[$tierId]
                : 0.0;

            // Boost weight: base_weight * (1 + SoV)
            // SoV 0.000 = no boost, SoV 0.500 = 1.5x boost, SoV 1.000 = 2x boost
            $campaign->weight = (float) $campaign->weight * (1.0 + $sov);

            return $campaign;
        });
    }

    /**
     * Get default/fallback campaigns when no active campaigns exist.
     *
     * @return Collection<Campaign>
     */
    protected function getDefaultCampaigns(string $placementType, int $maxSlots): Collection
    {
        // Return paused campaigns as fallback (admin can designate these)
        return Campaign::where('placement_type', $placementType)
            ->where('status', 'paused') // Paused = designated as fallback
            ->whereNull('deleted_at')
            ->with(['sponsor:id,name,slug,tier_id', 'sponsor.tier:id,slug,name'])
            ->limit($maxSlots)
            ->get();
    }

    /**
     * Get all placement configs grouped by placement_type.
     *
     * @return Collection<PlacementConfig>
     */
    public function getConfigs(): Collection
    {
        return PlacementConfig::active()
            ->orderBy('placement_type')
            ->orderBy('sort_order')
            ->with('tier:id,slug,name')
            ->get()
            ->groupBy('placement_type');
    }
}
