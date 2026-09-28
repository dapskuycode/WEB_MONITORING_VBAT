<x-layouts::auth :title="__('Masuk ke Akun — VBAT Ponsel')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-center text-center gap-1.5">
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Masuk ke Akun</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Portal Monitoring & Kemitraan VBAT Ponsel</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Alamat Email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="nama@vbatponsel.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Kata Sandi')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('••••••••')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-xs end-0 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" :href="route('password.request')" wire:navigate>
                        {{ __('Lupa sandi?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <flux:checkbox name="remember" :label="__('Ingat saya')" :checked="old('remember')" />
            </div>

            <div class="pt-2">
                <flux:button variant="primary" type="submit" class="w-full font-medium" data-test="login-button">
                    {{ __('Masuk') }}
                </flux:button>
            </div>
        </form>

        <div class="pt-4 border-t border-zinc-200/60 dark:border-zinc-800 text-center">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Akses terbatas untuk Administrator dan Mitra Sponsor resmi.
            </p>
        </div>
    </div>
</x-layouts::auth>
