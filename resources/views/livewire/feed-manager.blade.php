<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Feed & Campaign Manager</h1>
        <p class="text-gray-600 mt-1">Manage campaigns, best deals, placements, and feed configuration</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'campaigns')"
                class="@if($activeTab === 'campaigns') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Campaigns
            </button>
            <button wire:click="$set('activeTab', 'best-deals')"
                class="@if($activeTab === 'best-deals') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Best Deals
            </button>
            <button wire:click="$set('activeTab', 'placement')"
                class="@if($activeTab === 'placement') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Placement Config
            </button>
            <button wire:click="$set('activeTab', 'feed')"
                class="@if($activeTab === 'feed') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Feed Config
            </button>
        </nav>
    </div>

    {{-- Campaigns Tab --}}
    @if($activeTab === 'campaigns')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Campaign Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">{{ $selectedCampaignId ? 'Edit' : 'Create' }} Campaign</h2>
                <form wire:submit.prevent="saveCampaign" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title *</label>
                        <input type="text" wire:model="campaignTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('campaignTitle') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sponsor *</label>
                        <select wire:model="campaignSponsorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Select Sponsor --</option>
                            @foreach($sponsors as $sponsor)
                                <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                            @endforeach
                        </select>
                        @error('campaignSponsorId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Placement Type *</label>
                        <select wire:model="campaignPlacementType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="hero">Hero</option>
                            <option value="card">Card</option>
                            <option value="horizontal">Horizontal</option>
                            <option value="popup">Popup</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Media Type</label>
                        <select wire:model="campaignMediaType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Media File</label>
                        <input type="file" wire:model="campaignMediaFile" accept="image/*,video/*" class="mt-1 block w-full">
                        @error('campaignMediaFile') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Target URL</label>
                        <input type="url" wire:model="campaignTargetUrl" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="campaignDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Daily Limit</label>
                            <input type="number" wire:model="campaignDailyLimit" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Weight (Priority)</label>
                            <input type="number" wire:model="campaignWeight" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" wire:model="campaignStartDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" wire:model="campaignEndDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="campaignStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="draft">Draft</option>
                            <option value="pending_review">Pending Review</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full">
                        {{ $selectedCampaignId ? 'Update' : 'Create' }}
                    </button>
                    @if($selectedCampaignId)
                        <button type="button" wire:click="resetCampaignForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md w-full">Cancel</button>
                    @endif
                </form>
            </div>

            {{-- Campaign List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Campaigns</h2>
                <div class="mb-4 space-y-2">
                    <input type="text" wire:model.live="campaignSearch" placeholder="Search campaigns..." class="block w-full rounded-md border-gray-300 shadow-sm">
                    <select wire:model.live="sponsorFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Sponsors --</option>
                        @foreach($sponsors as $sponsor)
                            <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="placementFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Placements --</option>
                        <option value="hero">Hero</option>
                        <option value="card">Card</option>
                        <option value="horizontal">Horizontal</option>
                        <option value="popup">Popup</option>
                    </select>
                    <select wire:model.live="statusFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Status --</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($campaigns as $campaign)
                        <div class="border rounded-lg p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium">{{ $campaign->title }}</h3>
                                    <p class="text-xs text-gray-600">{{ $campaign->sponsor->name }} • {{ ucfirst($campaign->placement_type) }}</p>
                                    <p class="text-xs text-gray-600">{{ $campaign->start_date?->format('M d') }} - {{ $campaign->end_date?->format('M d') }}</p>
                                    <span class="inline-block px-2 py-1 text-xs rounded mt-1
                                        @if($campaign->status === 'active') bg-green-200 text-green-800
                                        @elseif($campaign->status === 'draft') bg-gray-200 text-gray-800
                                        @elseif($campaign->status === 'rejected') bg-red-200 text-red-800
                                        @else bg-yellow-200 text-yellow-800 @endif">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </div>
                                <div class="flex space-x-2 text-xs">
                                    <button wire:click="editCampaign({{ $campaign->id }})" class="text-blue-600 hover:text-blue-800">Edit</button>
                                    <button wire:click="deleteCampaign({{ $campaign->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No campaigns found.</p>
                    @endforelse
                </div>
                <div class="mt-4">
                    {{ $campaigns->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Best Deals Tab --}}
    @if($activeTab === 'best-deals')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Best Deal Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">{{ $selectedBestDealId ? 'Edit' : 'Create' }} Best Deal</h2>
                <form wire:submit.prevent="saveBestDeal" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title *</label>
                        <input type="text" wire:model="bestDealTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('bestDealTitle') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="bestDealDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Primary Product</label>
                        <select wire:model="bestDealSponsorProductId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Select Product --</option>
                            @foreach($sponsorProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sponsor->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Banner Image</label>
                        <input type="file" wire:model="bestDealBannerFile" accept="image/*" class="mt-1 block w-full">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start At</label>
                            <input type="datetime-local" wire:model="bestDealStartAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End At</label>
                            <input type="datetime-local" wire:model="bestDealEndAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="bestDealIsActive" class="rounded border-gray-300">
                        <span class="ml-2 text-sm">Active</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md w-full">
                        {{ $selectedBestDealId ? 'Update' : 'Create' }}
                    </button>
                    @if($selectedBestDealId)
                        <button type="button" wire:click="resetBestDealForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md w-full">Cancel</button>
                    @endif
                </form>
            </div>

            {{-- Best Deal List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Best Deals</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($bestDeals as $deal)
                        <div class="border rounded-lg p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium">{{ $deal->title }}</h3>
                                    <p class="text-xs text-gray-600">{{ $deal->start_at?->format('M d H:i') }} - {{ $deal->end_at?->format('M d H:i') }}</p>
                                    <p class="text-xs text-gray-600">{{ $deal->products_count ?? 0 }} product(s)</p>
                                    @if(!$deal->is_active)
                                        <span class="inline-block px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded mt-1">Inactive</span>
                                    @endif
                                </div>
                                <div class="flex space-x-2 text-xs">
                                    <button wire:click="editBestDeal({{ $deal->id }})" class="text-blue-600">Edit</button>
                                    <button wire:click="deleteBestDeal({{ $deal->id }})" onclick="return confirm('Delete?')" class="text-red-600">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No best deals found.</p>
                    @endforelse
                </div>
                <div class="mt-4">
                    {{ $bestDeals->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Placement Config Tab --}}
    @if($activeTab === 'placement')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Placement Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">{{ $selectedPlacementId ? 'Edit' : 'Create' }} Placement</h2>
                <form wire:submit.prevent="savePlacementConfig" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type *</label>
                        <select wire:model="placementType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Select Type --</option>
                            <option value="hero">Hero</option>
                            <option value="card">Card</option>
                            <option value="horizontal">Horizontal</option>
                            <option value="popup">Popup</option>
                        </select>
                        @error('placementType') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Probability (%) *</label>
                        <input type="range" wire:model="placementProbability" min="0" max="100" class="w-full">
                        <p class="text-sm text-gray-600 mt-1">{{ $placementProbability }}%</p>
                        @error('placementProbability') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Max Daily Impressions</label>
                        <input type="number" wire:model="placementMaxDaily" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="placementEnabled" class="rounded border-gray-300">
                        <span class="ml-2 text-sm">Enabled</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md w-full">Save</button>
                    @if($selectedPlacementId)
                        <button type="button" wire:click="resetPlacementForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md w-full">Cancel</button>
                    @endif
                </form>
            </div>

            {{-- Placement List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Placement Configurations</h2>
                <div class="space-y-2">
                    @forelse($placementConfigs as $config)
                        <div class="border rounded-lg p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium capitalize">{{ $config->type }}</h3>
                                    <p class="text-xs text-gray-600">Probability: {{ $config->probability }}%</p>
                                    @if($config->max_daily_impressions)
                                        <p class="text-xs text-gray-600">Max Daily: {{ $config->max_daily_impressions }}</p>
                                    @endif
                                    @if(!$config->enabled)
                                        <span class="inline-block px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded mt-1">Disabled</span>
                                    @endif
                                </div>
                                <div class="flex space-x-2 text-xs">
                                    <button wire:click="editPlacementConfig({{ $config->id }})" class="text-blue-600">Edit</button>
                                    <button wire:click="deletePlacementConfig({{ $config->id }})" onclick="return confirm('Delete?')" class="text-red-600">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No placement configs found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Feed Config Tab --}}
    @if($activeTab === 'feed')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Feed Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">{{ $selectedFeedId ? 'Edit' : 'Create' }} Feed Config</h2>
                <form wire:submit.prevent="saveFeedConfig" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Feed Type *</label>
                        <select wire:model="feedType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Select Type --</option>
                            <option value="partner_feed">Partner Feed</option>
                            <option value="best_deals">Best Deals</option>
                            <option value="informasi">Informasi</option>
                            <option value="lowongan_kerja">Lowongan Kerja</option>
                            <option value="magang">Magang</option>
                        </select>
                        @error('feedType') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Probability (%) *</label>
                        <input type="range" wire:model="feedProbability" min="0" max="100" class="w-full">
                        <p class="text-sm text-gray-600 mt-1">{{ $feedProbability }}%</p>
                        @error('feedProbability') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="feedEnabled" class="rounded border-gray-300">
                        <span class="ml-2 text-sm">Enabled</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md w-full">Save</button>
                    @if($selectedFeedId)
                        <button type="button" wire:click="resetFeedForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md w-full">Cancel</button>
                    @endif
                </form>
            </div>

            {{-- Feed List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Feed Configurations</h2>
                <div class="space-y-2">
                    @forelse($feedConfigs as $config)
                        <div class="border rounded-lg p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium">{{ str_replace('_', ' ', ucfirst($config->feed_type)) }}</h3>
                                    <p class="text-xs text-gray-600">Probability: {{ $config->probability }}%</p>
                                    @if(!$config->enabled)
                                        <span class="inline-block px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded mt-1">Disabled</span>
                                    @endif
                                </div>
                                <div class="flex space-x-2 text-xs">
                                    <button wire:click="editFeedConfig({{ $config->id }})" class="text-blue-600">Edit</button>
                                    <button wire:click="deleteFeedConfig({{ $config->id }})" onclick="return confirm('Delete?')" class="text-red-600">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No feed configs found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
