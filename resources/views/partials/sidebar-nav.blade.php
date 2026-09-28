<flux:sidebar.nav>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isOwner())
        {{-- Menu Khusus Admin & Owner --}}
        <flux:sidebar.group :heading="__('Analitik & Metrik')" class="grid">
            <flux:sidebar.item icon="chart-bar" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Super Dashboard') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group :heading="__('Admin & Owner VBAT')" class="grid">
            <flux:sidebar.item icon="building-office-2" :href="route('admin.sponsors')" :current="request()->routeIs('admin.sponsors')" wire:navigate>
                {{ __('Manajemen Sponsor') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="shopping-bag" :href="route('admin.products')" :current="request()->routeIs('admin.products')" wire:navigate>
                {{ __('Manajemen Produk Part') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="check-badge" :href="route('admin.campaigns')" :current="request()->routeIs('admin.campaigns')" wire:navigate>
                {{ __('Moderasi Kampanye') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="tag" :href="route('admin.events')" :current="request()->routeIs('admin.events')" wire:navigate>
                {{ __('Event & Diskon Shop') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="sparkles" :href="route('admin.best-deals')" :current="request()->routeIs('admin.best-deals')" wire:navigate>
                {{ __('Program Best Deal') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="arrow-up-tray" :href="route('admin.bulk-upload')" :current="request()->routeIs('admin.bulk-upload')" wire:navigate>
                {{ __('Bulk Upload Kelas') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="bell-alert" :href="route('admin.notifications')" :current="request()->routeIs('admin.notifications')" wire:navigate>
                {{ __('Push Notification') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                {{ __('Daftar Akun User') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
    @elseif(auth()->user()->isSponsor())
        {{-- Menu Khusus Mitra Sponsor --}}
        <flux:sidebar.group :heading="__('Portal Mitra Sponsor')" class="grid">
            <flux:sidebar.item icon="presentation-chart-line" :href="route('sponsor.dashboard')" :current="request()->routeIs('sponsor.dashboard')" wire:navigate>
                {{ __('Dashboard Sponsor') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="shopping-bag" :href="route('sponsor.products')" :current="request()->routeIs('sponsor.products')" wire:navigate>
                {{ __('Katalog Produk Saya') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="photo" :href="route('sponsor.campaigns.hero')" :current="request()->routeIs('sponsor.campaigns.hero')" wire:navigate>
                {{ __('Ajukan Hero Slide') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="rectangle-stack" :href="route('sponsor.campaigns.horizontal')" :current="request()->routeIs('sponsor.campaigns.horizontal')" wire:navigate>
                {{ __('Ajukan Horizontal Slider') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="squares-2x2" :href="route('sponsor.campaigns.card')" :current="request()->routeIs('sponsor.campaigns.card')" wire:navigate>
                {{ __('Ajukan Card Slider') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="megaphone" :href="route('sponsor.campaigns.popup')" :current="request()->routeIs('sponsor.campaigns.popup')" wire:navigate>
                {{ __('Ajukan Pop Up Iklan') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="bell-alert" :href="route('sponsor.notifications')" :current="request()->routeIs('sponsor.notifications')" wire:navigate>
                {{ __('Push Notification') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
    @endif
</flux:sidebar.nav>
