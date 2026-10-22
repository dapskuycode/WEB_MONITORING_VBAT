<div class="space-y-7 select-none">
    <!-- Alert Notifikasi Export -->
    @if (session()->has('message'))
        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-[#1a68f0] dark:text-blue-300 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2 font-medium">
                <svg class="w-5 h-5 text-[#1a68f0] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold text-[#1a68f0] hover:underline cursor-pointer">✕ Tutup</button>
        </div>
    @endif

    <!-- Key Performance Indicator (KPI) Cards: Top Row (4 Clean White Cards with soft drop shadow) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- KPI 1: Total Pendapatan (Revenue from Midtrans) -->
        <div class="p-6 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-[#828282] uppercase tracking-wider">Total Pendapatan</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1a68f0] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-[26px] font-extrabold text-[#212121] dark:text-white tracking-tight">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </p>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-zinc-800/80 text-xs">
                    <span class="text-slate-400 dark:text-zinc-500 font-medium">Gateway: Midtrans</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                        <span>+14.2%</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- KPI 2: Transaksi Marketplace -->
        <div class="p-6 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-[#828282] uppercase tracking-wider">Transaksi Marketplace</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1a68f0] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-[26px] font-extrabold text-[#212121] dark:text-white tracking-tight">
                    {{ number_format($totalMarketplaceOrders) }}
                </p>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-zinc-800/80 text-xs">
                    <span class="text-slate-400 dark:text-zinc-500 font-medium">Order Selesai</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                        <span>+8.6%</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- KPI 3: Peserta Kelas Aktif (LMS) -->
        <div class="p-6 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-[#828282] uppercase tracking-wider">Peserta Kelas Aktif (LMS)</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1a68f0] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-[26px] font-extrabold text-[#212121] dark:text-white tracking-tight">
                    {{ number_format($totalActiveStudents) }}
                </p>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-zinc-800/80 text-xs">
                    <span class="text-slate-400 dark:text-zinc-500 font-medium">Siswa & Teknisi</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                        <span>+19.4%</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- KPI 4: Sertifikat Diterbitkan -->
        <div class="p-6 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-[#828282] uppercase tracking-wider">Sertifikat Diterbitkan</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1a68f0] shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-[26px] font-extrabold text-[#212121] dark:text-white tracking-tight">
                    {{ number_format($totalCertificates) }}
                </p>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-zinc-800/80 text-xs">
                    <span class="text-slate-400 dark:text-zinc-500 font-medium">Digital Terverifikasi</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                        <span>+22.1%</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Analytics Charts: Middle Row (Two-Column Layout) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Chart (Wide, 2 Columns): 'Tren Transaksi & Pembelajaran' Line Chart -->
        <div class="lg:col-span-2 p-6 sm:p-7 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#212121] dark:text-white tracking-tight">
                        Tren Transaksi & Pembelajaran
                    </h2>
                    <p class="text-xs text-[#828282] mt-0.5">Pertumbuhan pesanan marketplace vs pendaftaran kelas LMS</p>
                </div>

                <!-- Clean Legend -->
                <div class="flex items-center gap-5 text-xs font-semibold">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#1a68f0]"></span>
                        <span class="text-[#212121] dark:text-zinc-300">Marketplace Orders</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#0ea5e9]"></span>
                        <span class="text-[#212121] dark:text-zinc-300">LMS Enrollments</span>
                    </div>
                </div>
            </div>

            <!-- Minimalist Line Chart SVG (No heavy grid lines, clear trend lines) -->
            <div class="relative w-full h-64 sm:h-72">
                <svg viewBox="0 0 700 240" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                    <defs>
                        <!-- Gradient Fill for Marketplace Line -->
                        <linearGradient id="blueGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#1a68f0" stop-opacity="0.12" />
                            <stop offset="100%" stop-color="#1a68f0" stop-opacity="0.0" />
                        </linearGradient>
                        <!-- Gradient Fill for LMS Line -->
                        <linearGradient id="tealGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.10" />
                            <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>

                    <!-- Subtle Horizontal Guidelines -->
                    <line x1="30" y1="40" x2="680" y2="40" stroke="#f1f5f9" stroke-width="1" class="dark:stroke-zinc-800" />
                    <line x1="30" y1="93" x2="680" y2="93" stroke="#f1f5f9" stroke-width="1" class="dark:stroke-zinc-800" />
                    <line x1="30" y1="146" x2="680" y2="146" stroke="#f1f5f9" stroke-width="1" class="dark:stroke-zinc-800" />
                    <line x1="30" y1="200" x2="680" y2="200" stroke="#e2e8f0" stroke-width="1" class="dark:stroke-zinc-700" />

                    <!-- Axis Value Labels -->
                    <text x="5" y="44" class="text-[10px] fill-[#828282]">{{ $yLabels[0] }}</text>
                    <text x="5" y="97" class="text-[10px] fill-[#828282]">{{ $yLabels[1] }}</text>
                    <text x="5" y="150" class="text-[10px] fill-[#828282]">{{ $yLabels[2] }}</text>
                    <text x="12" y="204" class="text-[10px] fill-[#828282]">{{ $yLabels[3] }}</text>

                    <!-- LMS Enrollments (Calm Sky Blue / Teal) Area & Line -->
                    <polygon points="{{ $lmsPolygon }}" fill="url(#tealGradient)" />
                    <polyline 
                        fill="none" 
                        stroke="#0ea5e9" 
                        stroke-width="3" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        points="{{ $lmsPoints }}" 
                    />

                    <!-- Marketplace Orders (Solid Blue) Area & Line -->
                    <polygon points="{{ $marketplacePolygon }}" fill="url(#blueGradient)" />
                    <polyline 
                        fill="none" 
                        stroke="#1a68f0" 
                        stroke-width="3" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        points="{{ $marketplacePoints }}" 
                    />

                    <!-- Interactive Data Points (Dots on lines) -->
                    <!-- LMS Dots -->
                    @foreach($lmsDots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="4" fill="#0ea5e9" class="stroke-white dark:stroke-zinc-900 transition-all hover:r-6 cursor-pointer" stroke-width="2">
                            <title>{{ $dot['month'] }}: {{ number_format($dot['val']) }} Pendaftaran LMS</title>
                        </circle>
                    @endforeach

                    <!-- Marketplace Dots -->
                    @foreach($marketplaceDots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="4" fill="#1a68f0" class="stroke-white dark:stroke-zinc-900 transition-all hover:r-6 cursor-pointer" stroke-width="2">
                            <title>{{ $dot['month'] }}: {{ number_format($dot['val']) }} Pesanan Marketplace</title>
                        </circle>
                    @endforeach
                </svg>

                <!-- Month Labels on Bottom Axis -->
                <div class="flex justify-between pl-8 pr-4 text-xs font-semibold text-[#828282] -mt-1">
                    @foreach ($trendMonths as $m)
                        <span>{{ $m }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Chart (Narrow, 1 Column): 'Demografi Pengguna' Donut Chart -->
        <div class="p-6 sm:p-7 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] flex flex-col justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#212121] dark:text-white tracking-tight">
                    Demografi Pengguna
                </h2>
                <p class="text-xs text-[#828282] mt-0.5">Komposisi peran pengguna dalam sistem</p>
            </div>

            <!-- Clean Donut Chart (SVG Circle with Dasharray) -->
            <div class="relative flex items-center justify-center py-4">
                <svg viewBox="0 0 160 160" class="w-44 h-44 -rotate-90 transform">
                    <!-- Background Circle -->
                    <circle cx="80" cy="80" r="60" stroke="#f1f5f9" stroke-width="18" fill="transparent" class="dark:stroke-zinc-800" />
                    
                    @foreach ($demographics as $d)
                        @if($d['count'] > 0)
                            <circle cx="80" cy="80" r="60" stroke="{{ $d['color'] }}" stroke-width="18" fill="transparent"
                                    stroke-dasharray="{{ $d['dasharray'] }}" stroke-dashoffset="{{ $d['dashoffset'] }}" stroke-linecap="round" />
                        @endif
                    @endforeach
                </svg>

                <!-- Center Metric Text -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-xl sm:text-2xl font-black text-[#212121] dark:text-white leading-none">
                        {{ number_format($totalDemoUsers) }}
                    </span>
                    <span class="text-[11px] font-medium text-[#828282] mt-1">Total Akun</span>
                </div>
            </div>

            <!-- Demographics Legend / Details List -->
            <div class="space-y-2.5 pt-4 border-t border-slate-100 dark:border-zinc-800/80">
                @foreach ($demographics as $d)
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $d['color'] }};"></span>
                            <span class="font-medium text-[#212121] dark:text-zinc-200 truncate">{{ $d['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="font-bold text-[#212121] dark:text-white">{{ number_format($d['count']) }}</span>
                            <span class="text-slate-400 dark:text-zinc-500 font-medium w-12 text-right">({{ $d['pct'] }}%)</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Performers Section: Bottom Row (Two Side-by-Side Lists/Tables in Clean White Cards) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Card: Top Kelas (LMS) Top 5 Courses -->
        <div class="p-6 sm:p-7 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)]">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-[#212121] dark:text-white tracking-tight flex items-center gap-2">
                        <span>Top Kelas (LMS)</span>
                    </h3>
                    <p class="text-xs text-[#828282] mt-0.5">5 kursus dengan jumlah pendaftaran dan siswa aktif tertinggi</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-[#1a68f0] dark:bg-blue-950/40 dark:text-blue-300 shrink-0">
                    Top 5 Course
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-zinc-800 text-[#828282] font-semibold">
                            <th class="py-3 px-2 text-start">Course Name</th>
                            <th class="py-3 px-3 text-start">Instructor / Sponsor</th>
                            <th class="py-3 px-2 text-right">Total Students</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60">
                        @foreach ($topCourses as $idx => $course)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="py-3.5 px-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center font-bold text-[11px] shrink-0 {{ $idx === 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-[#212121] dark:text-zinc-100 line-clamp-1 leading-snug" title="{{ $course['name'] }}">
                                                {{ $course['name'] }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-[#828282]">
                                                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-zinc-800 font-medium">{{ $course['type'] }}</span>
                                                <span>★ {{ $course['rating'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-medium text-[#212121] dark:text-zinc-200 line-clamp-1">
                                        {{ $course['instructor'] }}
                                    </p>
                                    <p class="text-[11px] text-[#828282]">Official Partner</p>
                                </td>
                                <td class="py-3.5 px-2 text-right">
                                    <span class="font-extrabold text-[#1a68f0] dark:text-blue-400 text-sm">
                                        {{ number_format($course['students']) }}
                                    </span>
                                    <p class="text-[10px] text-[#828282]">Siswa</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Card: Top Produk (Marketplace) Top 5 Products -->
        <div class="p-6 sm:p-7 rounded-[12px] bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)]">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-[#212121] dark:text-white tracking-tight flex items-center gap-2">
                        <span>Top Produk (Marketplace)</span>
                    </h3>
                    <p class="text-xs text-[#828282] mt-0.5">5 sparepart & tools terlaris dengan total pesanan terbanyak</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 shrink-0">
                    Best Sellers
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-zinc-800 text-[#828282] font-semibold">
                            <th class="py-3 px-2 text-start">Product Name</th>
                            <th class="py-3 px-3 text-start">Store / Sponsor Name</th>
                            <th class="py-3 px-2 text-right">Total Sold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60">
                        @foreach ($topProducts as $idx => $prod)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="py-3.5 px-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center font-bold text-[11px] shrink-0 {{ $idx === 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-[#212121] dark:text-zinc-100 line-clamp-1 leading-snug" title="{{ $prod['name'] }}">
                                                {{ $prod['name'] }}
                                            </p>
                                            <p class="text-[11px] text-[#828282] mt-0.5">
                                                Rp {{ number_format($prod['price'], 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-medium text-[#212121] dark:text-zinc-200 line-clamp-1">
                                        {{ $prod['sponsor'] }}
                                    </p>
                                    <p class="text-[11px] text-emerald-600 font-semibold">Verified Partner</p>
                                </td>
                                <td class="py-3.5 px-2 text-right">
                                    <span class="font-extrabold text-[#212121] dark:text-white text-sm">
                                        {{ number_format($prod['sold']) }}
                                    </span>
                                    <p class="text-[10px] text-[#828282]">Terjual</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
