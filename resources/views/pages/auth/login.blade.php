<x-layouts::auth :title="__('Masuk ke Akun — VBAT Ponsel')">
    <div class="flex flex-col gap-6">
        <!-- Logo & Header -->
        <div class="flex flex-col items-center text-center">
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 border border-zinc-700/70 flex items-center justify-center text-white font-black text-xl shadow-inner mb-3">
                V
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-1.5">
                <span>VBAT</span><span class="text-amber-500">Ponsel</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Portal Monitoring & Kemitraan</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store', [], false) }}" class="flex flex-col gap-4">
            @csrf

            <!-- Email Address -->
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-xs font-medium text-zinc-300">
                    Alamat Email
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="nama@vbatponsel.com"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950/80 border border-zinc-800 text-white placeholder-zinc-500 text-sm focus:outline-none focus:border-zinc-600 focus:ring-1 focus:ring-zinc-600 transition-colors"
                />
                @error('email')
                    <p class="text-xs text-red-400 mt-0.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5" x-data="{ show: false }">
                <label for="password" class="text-xs font-medium text-zinc-300">
                    Kata Sandi
                </label>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950/80 border border-zinc-800 text-white placeholder-zinc-500 text-sm pe-10 focus:outline-none focus:border-zinc-600 focus:ring-1 focus:ring-zinc-600 transition-colors"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 end-0 pe-3 flex items-center text-zinc-400 hover:text-zinc-200 transition-colors focus:outline-none cursor-pointer"
                        tabindex="-1"
                        aria-label="Tampilkan sandi"
                    >
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-red-400 mt-0.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me & Forgot Password Row -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        class="w-4 h-4 rounded border-zinc-700 bg-zinc-950 text-amber-500 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        {{ old('remember') ? 'checked' : '' }}
                    />
                    <span class="text-xs text-zinc-300">Ingat saya</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-zinc-400 hover:text-amber-400 transition-colors" wire:navigate>
                        Lupa sandi?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    data-test="login-button"
                    class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-zinc-950 font-bold text-sm tracking-wide shadow-md shadow-amber-500/10 transition-all cursor-pointer flex items-center justify-center gap-2"
                >
                    <span>Masuk ke Akun</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </form>

        <!-- Footer Notice -->
        <div class="pt-4 border-t border-zinc-800 text-center">
            <p class="text-[11px] text-zinc-500 leading-relaxed">
                Akses terbatas untuk Administrator dan Mitra Sponsor resmi VBAT Ponsel.
            </p>
        </div>
    </div>
</x-layouts::auth>
