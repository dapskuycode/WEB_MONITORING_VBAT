<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <!-- Quick Fill Kredensial Demo -->
        <div class="p-4 rounded-xl bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
            <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2 text-center">⚡ Masuk Cepat Akun Demo (1-Klik):</div>
            <div class="grid grid-cols-3 gap-2">
                <a href="{{ route('quick.login', 'admin') }}" class="px-2 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold text-center transition-all shadow-sm">
                    Super Admin
                </a>
                <a href="{{ route('quick.login', 'owner') }}" class="px-2 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold text-center transition-all shadow-sm">
                    Owner
                </a>
                <a href="{{ route('quick.login', 'sponsor') }}" class="px-2 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold text-center transition-all shadow-sm">
                    Sponsor
                </a>
            </div>
            <div class="mt-2 text-[11px] text-zinc-500 text-center">
                Password manual: <code class="font-mono text-zinc-700 dark:text-zinc-300">admin123 / owner123 / sponsor123</code>
            </div>
        </div>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
