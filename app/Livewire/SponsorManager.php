<?php

namespace App\Livewire;

use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\SponsorProduct;
use App\Models\BenefitCategory;
use App\Models\TierBenefit;
use App\Models\SponsorBenefitOverride;
use App\Models\Province;
use App\Models\City;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class SponsorManager extends Component
{
    use WithFileUploads;

    public $activeTab = 'sponsors';
    
    // Sponsor properties
    public $selectedSponsorId = null;
    public $sponsorName = '';
    public $sponsorSlug = '';
    public $sponsorDescription = '';
    public $sponsorWebsiteUrl = '';
    public $sponsorTierId = null;
    public $sponsorWeight = 0;
    public $sponsorStartDate = '';
    public $sponsorEndDate = '';
    public $sponsorIsActive = true;
    public $sponsorContactEmail = '';
    public $sponsorPhone = '';
    public $sponsorWhatsapp = '';
    public $sponsorAddress = '';
    public $sponsorProvinceId = null;
    public $sponsorCityId = null;
    public $sponsorLogo = null;
    public $sponsorCoBrandingHeader = null;
    public $sponsorCoBrandingSplash = null;

    // Benefit override properties
    public $selectedBenefitCategoryId = null;
    public $benefitOverrideValue = '';

    // Product properties
    public $selectedProductId = null;
    public $productName = '';
    public $productSlug = '';
    public $productDescription = '';
    public $productPrice = 0;
    public $productDiscountPrice = 0;
    public $productRating = 0;
    public $productSoldCount = 0;
    public $productImage = null;
    public $productShopeeUrl = '';
    public $productTokopediaUrl = '';
    public $productIsFeatured = false;
    public $productIsActive = true;

    // Filters
    public $sponsorSearch = '';
    public $tierFilter = '';
    public $isActiveFilter = '';

    public function mount()
    {
        $this->resetSponsorForm();
        $this->resetProductForm();
    }

    public function render()
    {
        return view('livewire.sponsor-manager', [
            'sponsors' => $this->getSponsors(),
            'tiers' => SponsorTier::orderBy('sort_order')->get(),
            'benefitCategories' => BenefitCategory::all(),
            'provinces' => Province::orderBy('name')->get(),
            'cities' => City::when($this->sponsorProvinceId, fn($q) => $q->where('province_id', $this->sponsorProvinceId))->orderBy('name')->get(),
            'selectedSponsors' => $this->selectedSponsorId ? Sponsor::with(['sponsorTier', 'products', 'benefitOverrides'])->find($this->selectedSponsorId) : null,
            'products' => $this->selectedSponsorId ? SponsorProduct::where('sponsor_id', $this->selectedSponsorId)->get() : collect(),
            'tierBenefits' => $this->selectedSponsorId && $this->selectedSponsors ? TierBenefit::where('tier_id', $this->selectedSponsors->tier_id)->get() : collect(),
        ]);
    }

    private function getSponsors()
    {
        return Sponsor::with(['sponsorTier', 'user'])
            ->when($this->sponsorSearch, fn($q) => $q->where('name', 'like', "%{$this->sponsorSearch}%"))
            ->when($this->tierFilter, fn($q) => $q->where('tier_id', $this->tierFilter))
            ->when($this->isActiveFilter !== '', fn($q) => $q->where('is_active', (bool)$this->isActiveFilter))
            ->paginate(10);
    }

    public function saveSponsor()
    {
        $this->validate([
            'sponsorName' => 'required|string|max:255',
            'sponsorSlug' => 'required|string|unique:sponsors,slug' . ($this->selectedSponsorId ? ",$this->selectedSponsorId" : ''),
            'sponsorTierId' => 'required|exists:sponsor_tiers,id',
            'sponsorContactEmail' => 'required|email',
            'sponsorStartDate' => 'required|date',
            'sponsorEndDate' => 'required|date|after:sponsorStartDate',
        ]);

        $data = [
            'name' => $this->sponsorName,
            'slug' => $this->sponsorSlug,
            'description' => $this->sponsorDescription,
            'website_url' => $this->sponsorWebsiteUrl,
            'tier_id' => $this->sponsorTierId,
            'weight' => $this->sponsorWeight,
            'start_date' => $this->sponsorStartDate,
            'end_date' => $this->sponsorEndDate,
            'is_active' => $this->sponsorIsActive,
            'contact_email' => $this->sponsorContactEmail,
            'phone' => $this->sponsorPhone,
            'whatsapp' => $this->sponsorWhatsapp,
            'address' => $this->sponsorAddress,
            'province_id' => $this->sponsorProvinceId,
            'city_id' => $this->sponsorCityId,
        ];

        if ($this->sponsorLogo) {
            $data['logo_path'] = $this->sponsorLogo->store('sponsors/logos', 'public');
        }

        if ($this->sponsorCoBrandingHeader) {
            $data['co_branding_header_url'] = $this->sponsorCoBrandingHeader->store('sponsors/cobranding', 'public');
        }

        if ($this->sponsorCoBrandingSplash) {
            $data['co_branding_splash_url'] = $this->sponsorCoBrandingSplash->store('sponsors/cobranding', 'public');
        }

        if ($this->selectedSponsorId) {
            $sponsor = Sponsor::findOrFail($this->selectedSponsorId);
            $sponsor->update($data);
        } else {
            $sponsor = Sponsor::create($data);
        }

        session()->flash('message', $this->selectedSponsorId ? 'Sponsor updated.' : 'Sponsor created.');
        $this->resetSponsorForm();
    }

    public function editSponsor($id)
    {
        $sponsor = Sponsor::findOrFail($id);
        $this->selectedSponsorId = $sponsor->id;
        $this->sponsorName = $sponsor->name;
        $this->sponsorSlug = $sponsor->slug;
        $this->sponsorDescription = $sponsor->description;
        $this->sponsorWebsiteUrl = $sponsor->website_url;
        $this->sponsorTierId = $sponsor->tier_id;
        $this->sponsorWeight = $sponsor->weight;
        $this->sponsorStartDate = $sponsor->start_date?->format('Y-m-d');
        $this->sponsorEndDate = $sponsor->end_date?->format('Y-m-d');
        $this->sponsorIsActive = $sponsor->is_active;
        $this->sponsorContactEmail = $sponsor->contact_email;
        $this->sponsorPhone = $sponsor->phone;
        $this->sponsorWhatsapp = $sponsor->whatsapp;
        $this->sponsorAddress = $sponsor->address;
        $this->sponsorProvinceId = $sponsor->province_id;
        $this->sponsorCityId = $sponsor->city_id;
    }

    public function deleteSponsor($id)
    {
        Sponsor::findOrFail($id)->delete();
        session()->flash('message', 'Sponsor deleted.');
        $this->resetSponsorForm();
    }

    public function resetSponsorForm()
    {
        $this->selectedSponsorId = null;
        $this->sponsorName = '';
        $this->sponsorSlug = '';
        $this->sponsorDescription = '';
        $this->sponsorWebsiteUrl = '';
        $this->sponsorTierId = null;
        $this->sponsorWeight = 0;
        $this->sponsorStartDate = '';
        $this->sponsorEndDate = '';
        $this->sponsorIsActive = true;
        $this->sponsorContactEmail = '';
        $this->sponsorPhone = '';
        $this->sponsorWhatsapp = '';
        $this->sponsorAddress = '';
        $this->sponsorProvinceId = null;
        $this->sponsorCityId = null;
        $this->sponsorLogo = null;
        $this->sponsorCoBrandingHeader = null;
        $this->sponsorCoBrandingSplash = null;
    }

    public function saveBenefitOverride()
    {
        $this->validate([
            'selectedBenefitCategoryId' => 'required|exists:benefit_categories,id',
            'benefitOverrideValue' => 'nullable|string',
        ]);

        SponsorBenefitOverride::updateOrCreate(
            ['sponsor_id' => $this->selectedSponsorId, 'benefit_category_id' => $this->selectedBenefitCategoryId],
            ['override_value' => $this->benefitOverrideValue ?: null]
        );

        session()->flash('message', 'Benefit override updated.');
        $this->selectedBenefitCategoryId = null;
        $this->benefitOverrideValue = '';
    }

    // Product CRUD
    public function saveProduct()
    {
        $this->validate([
            'productName' => 'required|string|max:255',
            'productSlug' => 'required|string|unique:sponsor_products,slug' . ($this->selectedProductId ? ",$this->selectedProductId" : ''),
            'productPrice' => 'required|numeric|min:0',
        ]);

        $data = [
            'sponsor_id' => $this->selectedSponsorId,
            'name' => $this->productName,
            'slug' => $this->productSlug,
            'description' => $this->productDescription,
            'price' => $this->productPrice,
            'discount_price' => $this->productDiscountPrice,
            'rating' => $this->productRating,
            'sold_count' => $this->productSoldCount,
            'shopee_url' => $this->productShopeeUrl,
            'tokopedia_url' => $this->productTokopediaUrl,
            'is_featured' => $this->productIsFeatured,
            'is_active' => $this->productIsActive,
        ];

        if ($this->productImage) {
            $data['image_path'] = $this->productImage->store('sponsor-products', 'public');
        }

        $this->selectedProductId
            ? SponsorProduct::findOrFail($this->selectedProductId)->update($data)
            : SponsorProduct::create($data);

        session()->flash('message', $this->selectedProductId ? 'Product updated.' : 'Product created.');
        $this->resetProductForm();
    }

    public function editProduct($id)
    {
        $product = SponsorProduct::findOrFail($id);
        $this->selectedProductId = $product->id;
        $this->productName = $product->name;
        $this->productSlug = $product->slug;
        $this->productDescription = $product->description;
        $this->productPrice = $product->price;
        $this->productDiscountPrice = $product->discount_price;
        $this->productRating = $product->rating;
        $this->productSoldCount = $product->sold_count;
        $this->productShopeeUrl = $product->shopee_url;
        $this->productTokopediaUrl = $product->tokopedia_url;
        $this->productIsFeatured = $product->is_featured;
        $this->productIsActive = $product->is_active;
    }

    public function deleteProduct($id)
    {
        SponsorProduct::findOrFail($id)->delete();
        session()->flash('message', 'Product deleted.');
        $this->resetProductForm();
    }

    public function resetProductForm()
    {
        $this->selectedProductId = null;
        $this->productName = '';
        $this->productSlug = '';
        $this->productDescription = '';
        $this->productPrice = 0;
        $this->productDiscountPrice = 0;
        $this->productRating = 0;
        $this->productSoldCount = 0;
        $this->productImage = null;
        $this->productShopeeUrl = '';
        $this->productTokopediaUrl = '';
        $this->productIsFeatured = false;
        $this->productIsActive = true;
    }
}
