<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Sponsor Manager</h1>
        <p class="text-gray-600 mt-1">Manage sponsors, tiers, benefits, and marketplace products</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'sponsors')" 
                class="@if($activeTab === 'sponsors') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Sponsors
            </button>
            <button wire:click="$set('activeTab', 'benefits')" 
                class="@if($activeTab === 'benefits') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Benefits
            </button>
            <button wire:click="$set('activeTab', 'products')" 
                class="@if($activeTab === 'products') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Products
            </button>
        </nav>
    </div>

    {{-- Sponsors Tab --}}
    @if($activeTab === 'sponsors')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Sponsor Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">{{ $selectedSponsorId ? 'Edit' : 'Create' }} Sponsor</h2>
                <form wire:submit.prevent="saveSponsor" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name *</label>
                        <input type="text" wire:model="sponsorName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('sponsorName') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Slug *</label>
                        <input type="text" wire:model="sponsorSlug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('sponsorSlug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tier *</label>
                        <select wire:model="sponsorTierId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Select Tier --</option>
                            @foreach($tiers as $tier)
                                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                            @endforeach
                        </select>
                        @error('sponsorTierId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date *</label>
                            <input type="date" wire:model="sponsorStartDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('sponsorStartDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date *</label>
                            <input type="date" wire:model="sponsorEndDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('sponsorEndDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Email *</label>
                        <input type="email" wire:model="sponsorContactEmail" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('sponsorContactEmail') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="tel" wire:model="sponsorPhone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                            <input type="tel" wire:model="sponsorWhatsapp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Website URL</label>
                        <input type="url" wire:model="sponsorWebsiteUrl" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="sponsorDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" wire:model="sponsorAddress" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Province</label>
                            <select wire:model="sponsorProvinceId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">-- Select Province --</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">City</label>
                            <select wire:model="sponsorCityId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">-- Select City --</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Weight (Display Order)</label>
                        <input type="number" wire:model="sponsorWeight" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Logo</label>
                        <input type="file" wire:model="sponsorLogo" accept="image/*" class="mt-1 block w-full">
                        @error('sponsorLogo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Co-Branding Header</label>
                        <input type="file" wire:model="sponsorCoBrandingHeader" accept="image/*" class="mt-1 block w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Co-Branding Splash</label>
                        <input type="file" wire:model="sponsorCoBrandingSplash" accept="image/*" class="mt-1 block w-full">
                    </div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="sponsorIsActive" class="rounded border-gray-300">
                        <span class="ml-2 text-sm">Active</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ $selectedSponsorId ? 'Update' : 'Create' }}
                    </button>
                    @if($selectedSponsorId)
                        <button type="button" wire:click="resetSponsorForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                    @endif
                </form>
            </div>

            {{-- Sponsor List & Filter --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Sponsors</h2>
                <div class="mb-4 space-y-2">
                    <input type="text" wire:model.live="sponsorSearch" placeholder="Search..." class="block w-full rounded-md border-gray-300 shadow-sm">
                    <select wire:model.live="tierFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Tiers --</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="isActiveFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Status --</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="space-y-2">
                    @forelse($sponsors as $sponsor)
                        <div class="border rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium">{{ $sponsor->name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $sponsor->sponsorTier->name ?? 'No Tier' }}</p>
                                    <p class="text-sm text-gray-500">{{ $sponsor->start_date?->format('M d') }} - {{ $sponsor->end_date?->format('M d, Y') }}</p>
                                    @if(!$sponsor->is_active)
                                        <span class="inline-block px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded">Inactive</span>
                                    @endif
                                </div>
                                <div class="flex space-x-2">
                                    <button wire:click="editSponsor({{ $sponsor->id }})" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                    <button wire:click="deleteSponsor({{ $sponsor->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No sponsors found.</p>
                    @endforelse
                </div>
                <div class="mt-4">
                    {{ $sponsors->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Benefits Tab --}}
    @if($activeTab === 'benefits')
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Benefit Overrides</h2>
            
            @if($selectedSponsorId && $selectedSponsors)
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                    <p class="text-sm text-blue-800">Sponsor: <strong>{{ $selectedSponsors->name }}</strong> ({{ $selectedSponsors->sponsorTier->name }})</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium mb-3">Set Override</h3>
                        <form wire:submit.prevent="saveBenefitOverride" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Benefit Category *</label>
                                <select wire:model="selectedBenefitCategoryId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">-- Select Category --</option>
                                    @foreach($benefitCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Override Value</label>
                                <input type="text" wire:model="benefitOverrideValue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="e.g., unlimited, 500, etc.">
                            </div>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Save Override</button>
                        </form>
                    </div>

                    <div>
                        <h3 class="font-medium mb-3">Tier Benefits ({{ $selectedSponsors->sponsorTier->name }})</h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($tierBenefits as $benefit)
                                <div class="border rounded p-3">
                                    <h4 class="font-medium text-sm">{{ $benefit->benefitCategory->name ?? 'Unknown' }}</h4>
                                    <p class="text-sm text-gray-600">Value: {{ $benefit->value ?? 'N/A' }}</p>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No benefits configured for this tier.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
                    Select a sponsor first to manage benefits.
                </div>
            @endif
        </div>
    @endif

    {{-- Products Tab --}}
    @if($activeTab === 'products')
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Marketplace Products</h2>

            @if($selectedSponsorId && $selectedSponsors)
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                    <p class="text-sm text-blue-800">Sponsor: <strong>{{ $selectedSponsors->name }}</strong></p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium mb-3">{{ $selectedProductId ? 'Edit' : 'Create' }} Product</h3>
                        <form wire:submit.prevent="saveProduct" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" wire:model="productName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Slug *</label>
                                <input type="text" wire:model="productSlug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea wire:model="productDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Price *</label>
                                    <input type="number" wire:model="productPrice" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Discount</label>
                                    <input type="number" wire:model="productDiscountPrice" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Rating</label>
                                    <input type="number" wire:model="productRating" step="0.1" min="0" max="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sold Count</label>
                                    <input type="number" wire:model="productSoldCount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Shopee URL</label>
                                <input type="url" wire:model="productShopeeUrl" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tokopedia URL</label>
                                <input type="url" wire:model="productTokopediaUrl" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Product Image</label>
                                <input type="file" wire:model="productImage" accept="image/*" class="mt-1 block w-full">
                            </div>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="productIsFeatured" class="rounded border-gray-300">
                                    <span class="ml-2 text-sm">Featured</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="productIsActive" class="rounded border-gray-300">
                                    <span class="ml-2 text-sm">Active</span>
                                </label>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">{{ $selectedProductId ? 'Update' : 'Create' }}</button>
                            @if($selectedProductId)
                                <button type="button" wire:click="resetProductForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                            @endif
                        </form>
                    </div>

                    <div>
                        <h3 class="font-medium mb-3">Products</h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($products as $product)
                                <div class="border rounded p-3 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-sm">{{ $product->name }}</h4>
                                            <p class="text-xs text-gray-600">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                            @if($product->is_featured)
                                                <span class="inline-block px-2 py-1 text-xs bg-yellow-200 text-yellow-800 rounded mt-1">Featured</span>
                                            @endif
                                        </div>
                                        <div class="flex space-x-2">
                                            <button wire:click="editProduct({{ $product->id }})" class="text-blue-600 text-xs">Edit</button>
                                            <button wire:click="deleteProduct({{ $product->id }})" onclick="return confirm('Delete?')" class="text-red-600 text-xs">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No products yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
                    Select a sponsor first to manage products.
                </div>
            @endif
        </div>
    @endif
</div>
