<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VBAT Ponsel — Web Monitoring & Sponsor Center</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col antialiased selection:bg-amber-500 selection:text-white">

    <!-- Top Navigation -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/20 text-white font-black text-lg">
                    V
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-lg tracking-tight text-white">VBAT<span class="text-amber-500">Ponsel</span></span>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">Web Monitoring</span>
                    </div>
                    <p class="text-xs text-slate-400 hidden sm:block">Sponsor & Admin Management Platform</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition-all shadow-md shadow-blue-600/20">
                        <span>Buka Dashboard</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-sm font-medium border border-slate-700 transition-all">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <span>Login Biasa</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 bg-grid-pattern relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-blue-900/10 via-transparent to-slate-950 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-16 relative z-10">
            <!-- Badge -->
            <div class="text-center mb-6">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold tracking-wide shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Sistem Monitoring & Sponsor Siap Digunakan
                </span>
            </div>

            <!-- Main Heading -->
            <div class="text-center max-w-3xl mx-auto mb-12">
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight mb-4">
                    Pusat Kendali <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-indigo-300 to-amber-400">Monitoring & Kemitraan</span> VbatPonsel
                </h1>
                <p class="text-slate-400 text-base sm:text-lg leading-relaxed">
                    Kelola kampanye promosi sponsor berjenjang, moderasi iklan in-app, event diskon katalog, analitik demografi teknisi, serta impor massal materi kelas secara mandiri.
                </p>
            </div>

            <!-- Quick Login Cards Section (Solusi Cepat Akses Web) -->
            <div class="mb-16">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Pilih Akses Cepat (1-Klik Login)
                        </h2>
                        <p class="text-xs text-slate-400">Klik tombol akun di bawah ini untuk langsung masuk tanpa perlu mengetik manual</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 1: Super Admin -->
                    <div class="rounded-2xl bg-gradient-to-b from-slate-900 to-slate-900/90 border border-slate-800 p-6 flex flex-col justify-between hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/5 transition-all group">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20 text-xs font-bold uppercase tracking-wider">
                                    Role: Super Admin
                                </span>
                                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2 group-hover:text-blue-400 transition-colors">Super Admin VBAT</h3>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                                Memiliki akses penuh ke seluruh modul: moderasi kampanye sponsor, event diskon, bulk upload kelas, dan broadcast push notification.
                            </p>
                            <div class="bg-slate-950/60 rounded-xl p-3 border border-slate-800 text-xs font-mono mb-4 text-slate-300 space-y-1">
                                <div><span class="text-slate-500">Email:</span> admin@vbatponsel.com</div>
                                <div><span class="text-slate-500">Pass:</span> admin123</div>
                            </div>
                        </div>
                        <a href="{{ route('quick.login', 'admin') }}" class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm text-center transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-600/20">
                            <span>Masuk sbg Super Admin</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    </div>

                    <!-- Card 2: Owner -->
                    <div class="rounded-2xl bg-gradient-to-b from-slate-900 to-slate-900/90 border border-slate-800 p-6 flex flex-col justify-between hover:border-amber-500/50 hover:shadow-xl hover:shadow-amber-500/5 transition-all group">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-bold uppercase tracking-wider">
                                    Role: Owner
                                </span>
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2 group-hover:text-amber-400 transition-colors">Owner Eksekutif</h3>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                                Fokus pada analitik performa bisnis: metrik demografi usia teknisi, rasio gender, domisili kota, CTR kampanye sponsor, dan Heat Index produk.
                            </p>
                            <div class="bg-slate-950/60 rounded-xl p-3 border border-slate-800 text-xs font-mono mb-4 text-slate-300 space-y-1">
                                <div><span class="text-slate-500">Email:</span> owner@vbatponsel.com</div>
                                <div><span class="text-slate-500">Pass:</span> owner123</div>
                            </div>
                        </div>
                        <a href="{{ route('quick.login', 'owner') }}" class="w-full py-2.5 px-4 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-semibold text-sm text-center transition-all flex items-center justify-center gap-2 shadow-lg shadow-amber-600/20">
                            <span>Masuk sbg Owner</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    </div>

                    <!-- Card 3: Sponsor -->
                    <div class="rounded-2xl bg-gradient-to-b from-slate-900 to-slate-900/90 border border-slate-800 p-6 flex flex-col justify-between hover:border-emerald-500/50 hover:shadow-xl hover:shadow-emerald-500/5 transition-all group">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold uppercase tracking-wider">
                                    Role: Sponsor Mitra
                                </span>
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2 group-hover:text-emerald-400 transition-colors">Mitra BraderParts</h3>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                                Portal mandiri untuk mitra sponsor: ajukan banner promo Hero / Shop, tentukan tautan Shopee/Tokopedia, dan lihat analitik CTR sendiri.
                            </p>
                            <div class="bg-slate-950/60 rounded-xl p-3 border border-slate-800 text-xs font-mono mb-4 text-slate-300 space-y-1">
                                <div><span class="text-slate-500">Email:</span> sponsor@braderparts.com</div>
                                <div><span class="text-slate-500">Pass:</span> sponsor123</div>
                            </div>
                        </div>
                        <a href="{{ route('quick.login', 'sponsor') }}" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm text-center transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20">
                            <span>Masuk Portal Sponsor</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modules Showcase -->
            <div class="border-t border-slate-800/80 pt-12">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    Modul Terintegrasi Sesuai Product Backlog
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 text-center">
                        <div class="text-xl mb-1">📊</div>
                        <div class="text-xs font-semibold text-white">Super Analytics</div>
                        <div class="text-[10px] text-slate-400">Demografi & Heat Index</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 text-center">
                        <div class="text-xl mb-1">🏢</div>
                        <div class="text-xs font-semibold text-white">Sponsor Tiering</div>
                        <div class="text-[10px] text-slate-400">Platinum, Gold, Silver</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 text-center">
                        <div class="text-xl mb-1">🛡️</div>
                        <div class="text-xs font-semibold text-white">Moderasi Iklan</div>
                        <div class="text-[10px] text-slate-400">Approve/Reject & Limit</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 text-center">
                        <div class="text-xl mb-1">🏷️</div>
                        <div class="text-xs font-semibold text-white">Event Diskon</div>
                        <div class="text-[10px] text-slate-400">% & Potongan Rp</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 text-center">
                        <div class="text-xl mb-1">📥</div>
                        <div class="text-xs font-semibold text-white">Bulk Upload</div>
                        <div class="text-[10px] text-slate-400">Impor CSV Video Kelas</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 text-center">
                        <div class="text-xl mb-1">🔔</div>
                        <div class="text-xs font-semibold text-white">Push Notifikasi</div>
                        <div class="text-[10px] text-slate-400">FCM Siaran Sponsor</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-900/40 py-6 text-center text-xs text-slate-500">
        <p>PROJECT VBAT &bull; Laravel 12 & Livewire Volt &bull; Master Wilayah dari <code class="text-slate-400">hasil_backup.sql</code></p>
    </footer>

</body>
</html>
