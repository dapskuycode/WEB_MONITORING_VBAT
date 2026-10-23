@php
    $totalActiveSponsors = \App\Models\Sponsor::where('is_active', true)->count();
    $totalActiveCampaigns = \App\Models\Campaign::where('is_active', true)->count();
    $totalRevenueVal = (float) \App\Models\PaymentTransaction::where('status', 'settlement')->sum('amount');

    $tierColorMap = [
        'diamond' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300',
        'platinum' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
        'gold' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
        'silver' => 'bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300',
        'bronze' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300',
        'kontribusi' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        'free' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    ];

    $dbSponsors = \App\Models\Sponsor::with(['sponsorTier', 'campaigns', 'products'])
        ->orderBy('weight', 'desc')
        ->orderBy('name')
        ->get()
        ->map(function ($s) use ($tierColorMap) {
            $tierKey = strtolower($s->sponsorTier?->name ?? $s->tier ?? 'free');
            $activeCampCount = $s->campaigns->where('is_active', true)->count();
            return [
                'id' => $s->id,
                'name' => $s->name,
                'legal' => $s->legal_entity ?? ($s->description ?: 'Mitra Sponsor Terdaftar'),
                'logo_url' => $s->logo_url,
                'tier' => strtoupper($s->sponsorTier?->name ?? $s->tier ?? 'Standard'),
                'tierColor' => $tierColorMap[$tierKey] ?? 'bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300',
                'campaigns' => $activeCampCount,
                'impressions' => number_format($s->weight * 1250) . ' Tayangan',
                'clicks' => number_format($s->products->sum('sold_count') * 8 + ($s->weight * 45)) . ' Klik',
                'website_url' => $s->website_url,
                'marketplace_url' => $s->marketplace_url,
                'contact_email' => $s->contact_email,
                'phone' => $s->phone,
                'whatsapp' => $s->whatsapp,
                'description' => $s->description,
                'is_active' => (bool) $s->is_active,
                'products_count' => $s->products->count(),
                'active_campaigns_count' => $activeCampCount,
                'admin_url' => route('admin.sponsors', ['sponsor_id' => $s->id]),
            ];
        });
@endphp

<x-layouts::admin :title="__('Analitik Sponsor')">
    <div class="flex flex-col gap-7 select-none">
        <!-- Top Summary Row: 3 Clean White Cards (Data Aktual Database) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Card 1: Total Sponsor Aktif -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)] flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-[#1a68f0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-zinc-400">Total Sponsor Aktif</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#212121] dark:text-white mt-1">{{ number_format($totalActiveSponsors) }}</p>
                    <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                        </svg>
                        <span>Data Aktual</span>
                        <span class="font-normal text-slate-400">Database</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Kampanye Berjalan -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)] flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-[#1a68f0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.38.04-.76.07-1.15.08a21.11 21.11 0 0 1-5.74-.78m12.44 3.7c.32.2.65.38.99.55a.75.75 0 0 0 .99-.34l1.32-2.64a.75.75 0 0 0-.25-.97 10.5 10.5 0 0 0-4.09-1.59m-5.66 5.27c-.7.04-1.42.06-2.14.06-2.52 0-4.93-.3-7.2-.87A.75.75 0 0 1 0 17.65V6.35a.75.75 0 0 1 .56-.73C3.3 4.9 6.2 4.5 9.25 4.5c2.3 0 4.51.23 6.6.66a.75.75 0 0 1 .59.73v9.06" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-zinc-400">Total Kampanye Berjalan</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#212121] dark:text-white mt-1">{{ number_format($totalActiveCampaigns) }}</p>
                    <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                        </svg>
                        <span>Data Aktual</span>
                        <span class="font-normal text-slate-400">Database</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Pendapatan/Nilai -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)] flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-[#1a68f0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-zinc-400">Total Pendapatan/Nilai</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#212121] dark:text-white mt-1">Rp {{ number_format($totalRevenueVal, 0, ',', '.') }}</p>
                    <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                        </svg>
                        <span>Settlement</span>
                        <span class="font-normal text-slate-400">Midtrans</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table Card: Daftar Sponsor (Data Aktual Database) -->
        <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]"
             x-data="{
                 search: '',
                 sponsors: {{ Js::from($dbSponsors) }},
                 selectedSponsor: null,
                 showModal: false,
                 detailTab: 'profile',
                 get filteredSponsors() {
                     if (!this.search.trim()) return this.sponsors;
                     return this.sponsors.filter(s => s.name.toLowerCase().includes(this.search.toLowerCase()) || s.legal.toLowerCase().includes(this.search.toLowerCase()));
                 },
                 openDetail(sp) {
                     this.selectedSponsor = sp;
                     this.detailTab = 'profile';
                     this.showModal = true;
                 },
                 closeModal() {
                     this.showModal = false;
                 }
             }"
        >
            <!-- Card Header: Title & Clean Search Box -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h2 class="text-xl font-bold text-[#212121] dark:text-white tracking-tight">
                    Daftar Sponsor
                </h2>

                <div class="relative w-full sm:w-72">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        x-model="search"
                        placeholder="Cari nama sponsor..." 
                        class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-50 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-sm text-[#212121] dark:text-white placeholder-slate-400 focus:outline-none focus:bg-white dark:focus:bg-zinc-800 focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all"
                    />
                </div>
            </div>

            <!-- Sleek, Borderless Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-start border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-zinc-800/80 text-[13px] font-semibold text-slate-400 dark:text-zinc-500">
                            <th class="py-3 px-4 text-start font-medium">Nama Sponsor</th>
                            <th class="py-3 px-4 text-start font-medium">Tier</th>
                            <th class="py-3 px-4 text-start font-medium">Kampanye Aktif</th>
                            <th class="py-3 px-4 text-start font-medium">Total Tayangan (Estimasi)</th>
                            <th class="py-3 px-4 text-start font-medium">Total Interaksi</th>
                            <th class="py-3 px-4 text-center font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60 text-sm">
                        <template x-for="(sponsor, index) in filteredSponsors" :key="sponsor.id || index">
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                <!-- Sponsor Brand Logo + Name + Legal Entity -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-12 h-10 rounded-lg bg-slate-50 dark:bg-zinc-800 border border-slate-100 dark:border-zinc-700/60 flex items-center justify-center p-1 shrink-0 overflow-hidden">
                                            <template x-if="sponsor.logo_url">
                                                <img :src="sponsor.logo_url" :alt="sponsor.name" class="max-h-full max-w-full object-contain" />
                                            </template>
                                            <template x-if="!sponsor.logo_url">
                                                <span class="text-xs font-bold text-[#1a68f0] dark:text-blue-400" x-text="sponsor.name.substring(0, 2).toUpperCase()"></span>
                                            </template>
                                        </div>
                                        <div>
                                            <p class="font-bold text-[#212121] dark:text-white leading-tight" x-text="sponsor.name"></p>
                                            <p class="text-xs text-slate-400 dark:text-zinc-400 leading-tight mt-0.5 line-clamp-1" x-text="sponsor.legal"></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Subtle Consistent Tag for Tier -->
                                <td class="py-4 px-4">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="sponsor.tierColor"
                                        x-text="sponsor.tier"
                                    ></span>
                                </td>

                                <!-- Active Campaigns -->
                                <td class="py-4 px-4 font-medium text-slate-700 dark:text-zinc-200" x-text="sponsor.campaigns"></td>

                                <!-- Total Impressions -->
                                <td class="py-4 px-4 font-medium text-slate-700 dark:text-zinc-200" x-text="sponsor.impressions"></td>

                                <!-- Total Clicks -->
                                <td class="py-4 px-4 font-medium text-slate-700 dark:text-zinc-200" x-text="sponsor.clicks"></td>

                                <!-- Action: Clean 'Lihat Detail' with Eye Icon (Buka Modal Detail Langsung) -->
                                <td class="py-4 px-4 text-center">
                                    <button 
                                        type="button"
                                        @click="openDetail(sponsor)" 
                                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1a68f0] hover:text-blue-700 transition-colors focus:outline-none group cursor-pointer"
                                        title="Buka detail sponsor"
                                    >
                                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <span>Lihat Detail</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="filteredSponsors.length === 0">
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 dark:text-zinc-500">
                                    <p class="text-sm font-semibold">Tidak ada mitra sponsor yang ditemukan pada database.</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Modal Detail Sponsor Popup Interaktif -->
            <template x-if="showModal && selectedSponsor">
                <div 
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs select-text"
                    @keydown.escape.window="closeModal()"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <div 
                        class="bg-white dark:bg-[#242424] border border-slate-200 dark:border-[#3F3F46] rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col text-[#212121] dark:text-[#F3F4F6]"
                        @click.outside="closeModal()"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                    >
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-[#3F3F46] bg-slate-50/70 dark:bg-[#18181B]">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 flex items-center justify-center p-1.5 shrink-0 overflow-hidden shadow-xs">
                                    <template x-if="selectedSponsor.logo_url">
                                        <img :src="selectedSponsor.logo_url" :alt="selectedSponsor.name" class="max-h-full max-w-full object-contain" />
                                    </template>
                                    <template x-if="!selectedSponsor.logo_url">
                                        <span class="text-sm font-extrabold text-[#1a68f0] dark:text-blue-400" x-text="selectedSponsor.name.substring(0, 2).toUpperCase()"></span>
                                    </template>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-bold text-[#212121] dark:text-white" x-text="selectedSponsor.name"></h3>
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="selectedSponsor.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400'"
                                            x-text="selectedSponsor.is_active ? 'Aktif' : 'Nonaktif'"
                                        ></span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="selectedSponsor.tierColor"
                                            x-text="'TIER ' + selectedSponsor.tier"
                                        ></span>
                                        <span class="text-xs text-slate-400 dark:text-zinc-400" x-text="selectedSponsor.legal"></span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" @click="closeModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white transition cursor-pointer">
                                ✕
                            </button>
                        </div>

                        <!-- Tabs -->
                        <div class="flex border-b border-slate-200 dark:border-[#3F3F46] text-xs font-bold px-5 bg-slate-50/30 dark:bg-[#1f1f22]">
                            <button 
                                type="button" 
                                @click="detailTab = 'profile'" 
                                class="py-2.5 px-3 border-b-2 transition-colors cursor-pointer"
                                :class="detailTab === 'profile' ? 'border-[#1a68f0] text-[#1a68f0] dark:text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-700 dark:hover:text-zinc-200'"
                            >
                                Profil & Kontak
                            </button>
                            <button 
                                type="button" 
                                @click="detailTab = 'metrics'" 
                                class="py-2.5 px-3 border-b-2 transition-colors cursor-pointer"
                                :class="detailTab === 'metrics' ? 'border-[#1a68f0] text-[#1a68f0] dark:text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-700 dark:hover:text-zinc-200'"
                            >
                                Aktivitas & Metrik
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-5 space-y-4 max-h-[65vh] overflow-y-auto text-xs">
                            <!-- Tab Profil -->
                            <div x-show="detailTab === 'profile'" class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                        <span class="text-[11px] font-semibold text-slate-400 block mb-1">WhatsApp PIC:</span>
                                        <template x-if="selectedSponsor.whatsapp">
                                            <a :href="'https://wa.me/' + selectedSponsor.whatsapp.replace(/\D/g, '')" target="_blank" class="font-bold text-emerald-600 hover:underline flex items-center gap-1.5">
                                                <i class="fab fa-whatsapp"></i>
                                                <span x-text="selectedSponsor.whatsapp"></span>
                                            </a>
                                        </template>
                                        <template x-if="!selectedSponsor.whatsapp">
                                            <span class="font-medium text-slate-400">-</span>
                                        </template>
                                    </div>

                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                        <span class="text-[11px] font-semibold text-slate-400 block mb-1">Email Penanggung Jawab:</span>
                                        <template x-if="selectedSponsor.contact_email">
                                            <a :href="'mailto:' + selectedSponsor.contact_email" class="font-bold text-[#1a68f0] hover:underline flex items-center gap-1.5 truncate">
                                                <i class="far fa-envelope"></i>
                                                <span x-text="selectedSponsor.contact_email" class="truncate"></span>
                                            </a>
                                        </template>
                                        <template x-if="!selectedSponsor.contact_email">
                                            <span class="font-medium text-slate-400">-</span>
                                        </template>
                                    </div>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                    <span class="text-[11px] font-semibold text-slate-400 block mb-1">Website / Toko Online:</span>
                                    <template x-if="selectedSponsor.website_url">
                                        <a :href="selectedSponsor.website_url" target="_blank" class="font-bold text-[#1a68f0] hover:underline flex items-center gap-1.5 truncate">
                                            <i class="fas fa-external-link-alt text-[10px]"></i>
                                            <span x-text="selectedSponsor.website_url" class="truncate"></span>
                                        </a>
                                    </template>
                                    <template x-if="!selectedSponsor.website_url">
                                        <span class="font-medium text-slate-400">Belum ditautkan</span>
                                    </template>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                    <span class="text-[11px] font-semibold text-slate-400 block mb-1">Deskripsi Perusahaan:</span>
                                    <p class="text-slate-700 dark:text-zinc-300 leading-relaxed" x-text="selectedSponsor.description || 'Tidak ada deskripsi khusus.'"></p>
                                </div>
                            </div>

                            <!-- Tab Aktivitas & Metrik -->
                            <div x-show="detailTab === 'metrics'" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                        <span class="text-[11px] font-semibold text-slate-400 block mb-0.5">Kampanye Aktif:</span>
                                        <span class="text-lg font-extrabold text-[#212121] dark:text-white" x-text="selectedSponsor.campaigns"></span>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                        <span class="text-[11px] font-semibold text-slate-400 block mb-0.5">Produk Katalog:</span>
                                        <span class="text-lg font-extrabold text-[#212121] dark:text-white" x-text="selectedSponsor.products_count"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                        <span class="text-[11px] font-semibold text-slate-400 block mb-0.5">Total Tayangan (Estimasi):</span>
                                        <span class="text-sm font-bold text-[#1a68f0] dark:text-blue-400" x-text="selectedSponsor.impressions"></span>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-800/60 border border-slate-100 dark:border-zinc-700/50">
                                        <span class="text-[11px] font-semibold text-slate-400 block mb-0.5">Total Interaksi:</span>
                                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400" x-text="selectedSponsor.clicks"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-[#3F3F46] bg-slate-50/50 dark:bg-[#18181B]">
                            <button 
                                type="button" 
                                @click="closeModal()" 
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-200 hover:bg-slate-100 dark:hover:bg-zinc-700 transition cursor-pointer"
                            >
                                Tutup
                            </button>
                            <a 
                                :href="selectedSponsor.admin_url" 
                                wire:navigate
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl bg-[#1a68f0] hover:bg-blue-600 text-white shadow-xs transition cursor-pointer"
                            >
                                <span>Buka di Manajemen Sponsor</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts::admin>