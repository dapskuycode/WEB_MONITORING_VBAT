<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F8F9FA] dark:bg-zinc-950 text-slate-800 dark:text-zinc-100 antialiased font-sans selection:bg-blue-600/15 selection:text-blue-600 relative overflow-x-hidden flex flex-col justify-between p-4 sm:p-6 transition-colors duration-200">
        
        <!-- Decorative Ambient Background Capsules (Enterprise Design) -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10 select-none" aria-hidden="true">
            <!-- Top Right Capsule Accents -->
            <div class="absolute -top-12 -right-16 w-80 h-28 sm:w-96 sm:h-36 rounded-full bg-[#E2EDFB]/80 dark:bg-blue-950/20 rotate-[-40deg] transform origin-center transition-colors"></div>
            <div class="absolute -top-28 right-44 w-60 h-20 sm:w-72 sm:h-24 rounded-full bg-[#ECF4FE]/70 dark:bg-blue-950/15 rotate-[-40deg] transform origin-center transition-colors"></div>
            
            <!-- Bottom Left Capsule Accents -->
            <div class="absolute -bottom-12 -left-16 w-80 h-28 sm:w-96 sm:h-36 rounded-full bg-[#E2EDFB]/80 dark:bg-blue-950/20 rotate-[-40deg] transform origin-center transition-colors"></div>
            <div class="absolute -bottom-28 left-44 w-60 h-20 sm:w-72 sm:h-24 rounded-full bg-[#ECF4FE]/70 dark:bg-blue-950/15 rotate-[-40deg] transform origin-center transition-colors"></div>
        </div>

        <!-- Top Right Mode Switcher Header -->
        <header class="w-full max-w-6xl mx-auto flex justify-end items-center py-2 px-2 sm:px-4">
            <div 
                x-data="{
                    isDark: document.documentElement.classList.contains('dark'),
                    init() {
                        this.isDark = document.documentElement.classList.contains('dark');
                    },
                    toggle() {
                        this.isDark = !this.isDark;
                        if (this.isDark) {
                            document.documentElement.classList.add('dark');
                            try {
                                localStorage.setItem('theme', 'dark');
                                localStorage.setItem('flux.appearance', 'dark');
                            } catch (e) {}
                        } else {
                            document.documentElement.classList.remove('dark');
                            try {
                                localStorage.setItem('theme', 'light');
                                localStorage.setItem('flux.appearance', 'light');
                            } catch (e) {}
                        }
                    }
                }"
                @click="toggle()"
                role="button"
                tabindex="0"
                @keydown.space.prevent="toggle()"
                @keydown.enter.prevent="toggle()"
                title="Toggle Mode Terang / Gelap"
                class="relative inline-flex items-center w-[68px] h-8 p-1 rounded-full bg-white dark:bg-zinc-800 border border-slate-200/90 dark:border-zinc-700/80 shadow-xs cursor-pointer select-none transition-all hover:border-slate-300 dark:hover:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                aria-label="Toggle tema terang atau gelap"
            >
                <!-- Animated Active Knob -->
                <span 
                    class="absolute top-1 bottom-1 w-6 h-6 rounded-full bg-slate-100 dark:bg-zinc-700 shadow-xs transition-transform duration-200 ease-out"
                    :class="isDark ? 'translate-x-[36px]' : 'translate-x-0'"
                ></span>

                <!-- Sun Icon (left) -->
                <span 
                    class="relative z-10 flex items-center justify-center w-6 h-6 transition-colors duration-200"
                    :class="!isDark ? 'text-blue-600' : 'text-slate-400 dark:text-zinc-500'"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                    </svg>
                </span>

                <!-- Moon Icon (right) -->
                <span 
                    class="relative z-10 flex items-center justify-center w-6 h-6 transition-colors duration-200 ml-auto"
                    :class="isDark ? 'text-blue-400' : 'text-slate-400 dark:text-zinc-500'"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
                    </svg>
                </span>
            </div>
        </header>

        <!-- Main Card Content -->
        <main class="w-full max-w-[480px] mx-auto my-auto px-4 py-4 sm:py-6">
            <div class="rounded-2xl border border-slate-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-[0_12px_45px_-10px_rgba(20,40,90,0.06)] dark:shadow-2xl dark:shadow-black/70 p-7 sm:p-10 transition-all duration-200">
                {{ $slot }}
            </div>
            
            <!-- Page Bottom Copyright -->
            <p class="mt-8 text-center text-xs text-slate-500 dark:text-zinc-500 font-normal select-none">
                &copy; 2026 VBAT Ponsel &bull; Hak cipta dilindungi.
            </p>
        </main>

        <!-- Balanced Footer Spacing -->
        <footer class="w-full py-2"></footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

