<x-layouts::auth :title="__('Masuk ke Akun — VBAT Ponsel')">
    <div class="flex flex-col">
        <!-- Brand Header inside Card -->
        <div class="flex flex-col items-center justify-center text-center mb-7 sm:mb-8 select-none">
            <img 
                src="{{ asset('assets/images/vbat_logo_shadow.png') }}" 
                alt="VBAT Ponsel" 
                class="h-10 sm:h-12 w-auto object-contain mb-2.5" 
            />
            
            <p class="text-xs sm:text-[13px] text-slate-500 dark:text-zinc-400 font-normal tracking-tight">
                Portal Monitoring &amp; Kemitraan
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center text-sm mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login.store', [], false) }}" class="flex flex-col gap-4 sm:gap-5">
            @csrf

            <!-- Email Address -->
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-sm font-semibold text-[#212121] dark:text-zinc-200">
                    Alamat Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-zinc-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                    </div>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="contoh@domain.com"
                        class="w-full pl-11 pr-4 py-2.5 sm:py-3 rounded-xl bg-[#F8FAFC] dark:bg-zinc-800/80 border border-slate-200/90 dark:border-zinc-700 text-[#212121] dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-sm focus:outline-none focus:bg-white dark:focus:bg-zinc-800 focus:border-[#1a68f0] focus:ring-4 focus:ring-[#1a68f0]/10 transition-all duration-150"
                    />
                </div>
                @error('email')
                    <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5" x-data="{ show: false }">
                <label for="password" class="text-sm font-semibold text-[#212121] dark:text-zinc-200">
                    Kata Sandi
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-zinc-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </div>
                    <input
                        id="password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="w-full pl-11 pr-11 py-2.5 sm:py-3 rounded-xl bg-[#F8FAFC] dark:bg-zinc-800/80 border border-slate-200/90 dark:border-zinc-700 text-[#212121] dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-sm focus:outline-none focus:bg-white dark:focus:bg-zinc-800 focus:border-[#1a68f0] focus:ring-4 focus:ring-[#1a68f0]/10 transition-all duration-150"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors cursor-pointer"
                        tabindex="-1"
                        aria-label="Toggle keterlihatan kata sandi"
                    >
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                            <line x1="2" x2="22" y1="2" y2="22" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me & Forgot Password Row -->
            <div class="flex items-center justify-between pt-0.5">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        class="w-4 h-4 rounded border-slate-300 dark:border-zinc-700 text-[#1a68f0] focus:ring-0 focus:ring-offset-0 cursor-pointer accent-[#1a68f0]"
                        {{ old('remember') ? 'checked' : '' }}
                    />
                    <span class="text-sm text-slate-700 dark:text-zinc-300 font-normal">Ingat saya</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-[#1a68f0] hover:text-[#1253c4] hover:underline transition-colors" wire:navigate>
                        Lupa sandi?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    data-test="login-button"
                    class="w-full py-3 px-4 rounded-xl bg-[#1a68f0] hover:bg-[#1557cf] active:bg-[#1147a8] text-white font-semibold text-sm sm:text-base tracking-normal shadow-sm transition-all duration-150 cursor-pointer flex items-center justify-center gap-2 group"
                >
                    <span>Masuk ke Akun</span>
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>

        <!-- Footer Notice inside Card -->
        <div class="mt-6 sm:mt-7 text-center">
            <p class="text-xs text-slate-400 dark:text-zinc-400 leading-relaxed font-normal">
                Akses terbatas untuk Administrator dan Mitra Sponsor resmi VBAT Ponsel.
            </p>
        </div>
    </div>
</x-layouts::auth>

