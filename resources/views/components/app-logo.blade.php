@props([
    'sidebar' => false,
])

@php
    $defaultHref = auth()->user()?->isSponsor() ? route('sponsor.dashboard') : route('dashboard');
@endphp

<a href="{{ $attributes->get('href', $defaultHref) }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5 select-none focus:outline-none']) }}>
    <img src="{{ asset('assets/images/vbat_logo_simple.png') }}" alt="VBAT" class="h-8 w-auto object-contain shrink-0" />
    <div class="flex items-center gap-1 leading-none">
        <span class="text-xl font-extrabold text-[#1a68f0] tracking-tight">VBAT</span>
        <span class="text-xl font-extrabold text-[#212121] dark:text-white tracking-tight">Ponsel</span>
    </div>
</a>
