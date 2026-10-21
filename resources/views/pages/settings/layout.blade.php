@props(['heading', 'subheading'])

<div class="flex items-start gap-8 max-lg:flex-col">
    <!-- Left Navigation Menu -->
    <div class="w-full lg:w-64 flex-shrink-0">
        <nav class="space-y-1" aria-label="{{ __('Settings') }}">
            <a 
                href="{{ route('profile.edit') }}" 
                wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.edit') ? 'bg-[#E6F0FA] text-[#1a73e8]' : 'text-[#212121] hover:bg-gray-50' }}"
            >
                <i class="fas fa-user w-5 text-center {{ request()->routeIs('profile.edit') ? 'text-[#1a73e8]' : 'text-[#828282]' }}"></i>
                <span>{{ __('Profile') }}</span>
            </a>
            
            <a 
                href="{{ route('security.edit') }}" 
                wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('security.edit') ? 'bg-[#E6F0FA] text-[#1a73e8]' : 'text-[#212121] hover:bg-gray-50' }}"
            >
                <i class="fas fa-lock w-5 text-center {{ request()->routeIs('security.edit') ? 'text-[#1a73e8]' : 'text-[#828282]' }}"></i>
                <span>{{ __('Security') }}</span>
            </a>
            
            <a 
                href="{{ route('appearance.edit') }}" 
                wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-[#E6F0FA] text-[#1a73e8]' : 'text-[#212121] hover:bg-gray-50' }}"
            >
                <i class="fas fa-palette w-5 text-center {{ request()->routeIs('appearance.edit') ? 'text-[#1a73e8]' : 'text-[#828282]' }}"></i>
                <span>{{ __('Appearance') }}</span>
            </a>
        </nav>
    </div>

    <!-- Right Content Area -->
    <div class="flex-1 min-w-0">
        <!-- Settings Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <!-- Card Header -->
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-[#212121]">{{ $heading ?? '' }}</h2>
                <p class="text-sm text-[#828282] mt-1">{{ $subheading ?? '' }}</p>
            </div>

            <!-- Card Content -->
            <div class="px-6 py-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
