<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Course;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\Certificate;
use App\Models\Entitlement;
use App\Models\PaymentTransaction;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboard extends Component
{
    use WithPagination;

    public $dateRange = '01 Oct 2026 - 31 Oct 2026';
    public $dateStart;
    public $dateEnd;

    public function mount()
    {
        $this->dateStart = request('start_date', '2026-10-01');
        $this->dateEnd = request('end_date', '2026-10-31');

        try {
            $formattedStart = Carbon::parse($this->dateStart)->format('d M Y');
            $formattedEnd = Carbon::parse($this->dateEnd)->format('d M Y');
            $this->dateRange = "{$formattedStart} - {$formattedEnd}";
        } catch (\Exception $e) {
            $this->dateRange = '01 Oct 2026 - 31 Oct 2026';
        }
    }

    public function exportReport()
    {
        return redirect()->route('admin.export.analytics', [
            'source' => 'analytics',
            'start_date' => $this->dateStart,
            'end_date' => $this->dateEnd,
        ]);
    }

    public function render()
    {
        // 1. KPI Cards Data - Data Aktual Database
        $totalRevenue = (float) PaymentTransaction::where('status', 'settlement')->sum('amount');
        $totalMarketplaceOrders = (int) SponsorProduct::sum('sold_count');
        
        $dbActiveStudents = Entitlement::active()->count();
        if ($dbActiveStudents === 0) {
            $dbActiveStudents = User::whereIn('role', ['user', 'technician', 'siswa', 'member'])->count();
        }
        $totalActiveStudents = (int) $dbActiveStudents;
        
        $totalCertificates = (int) Certificate::count();

        // 2. Trend Transaksi & Pembelajaran (7 bulan terakhir - Data Aktual)
        $last7Months = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $last7Months->push([
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'start' => $date->copy()->startOfMonth()->toDateTimeString(),
                'end' => $date->copy()->endOfMonth()->toDateTimeString(),
            ]);
        }

        $trendMonths = $last7Months->pluck('month')->toArray();
        $marketplaceTrend = [];
        $lmsTrend = [];
        
        foreach ($last7Months as $period) {
            // Marketplace: jumlah produk terjual yang dibuat pada periode ini
            $sold = (int) SponsorProduct::whereBetween('created_at', [$period['start'], $period['end']])->sum('sold_count');
            $marketplaceTrend[] = $sold;
            
            // LMS: pendaftaran materi / siswa aktif yang dibuat pada periode ini
            $entitlementsCount = (int) Entitlement::whereBetween('created_at', [$period['start'], $period['end']])->count();
            if ($entitlementsCount === 0) {
                $entitlementsCount = (int) User::whereIn('role', ['user', 'technician', 'siswa', 'member'])
                    ->whereBetween('created_at', [$period['start'], $period['end']])
                    ->count();
            }
            $lmsTrend[] = $entitlementsCount;
        }

        // Hitung batas maksimum dan skala SVG untuk Chart Tren
        $maxRaw = max(array_merge($marketplaceTrend, $lmsTrend));
        $chartMaxVal = $maxRaw > 0 ? (int) ceil($maxRaw * 1.25) : 10;
        if ($chartMaxVal < 10) {
            $chartMaxVal = 10;
        }

        $yLabels = [
            $chartMaxVal >= 1000 ? round($chartMaxVal / 1000, 1) . 'k' : $chartMaxVal,
            $chartMaxVal >= 1000 ? round(($chartMaxVal * 0.66) / 1000, 1) . 'k' : round($chartMaxVal * 0.66),
            $chartMaxVal >= 1000 ? round(($chartMaxVal * 0.33) / 1000, 1) . 'k' : round($chartMaxVal * 0.33),
            '0',
        ];

        // Hitung koordinat (x, y) garis SVG secara dinamis
        $marketplacePointsArr = [];
        $marketplaceDots = [];
        $lmsPointsArr = [];
        $lmsDots = [];

        foreach ($trendMonths as $idx => $mName) {
            $x = 60 + ($idx * 100);
            
            // Marketplace coordinate
            $mVal = $marketplaceTrend[$idx] ?? 0;
            $mY = 200 - (int) round(($mVal / $chartMaxVal) * 160);
            $marketplacePointsArr[] = "{$x},{$mY}";
            $marketplaceDots[] = ['x' => $x, 'y' => $mY, 'val' => $mVal, 'month' => $mName];

            // LMS coordinate
            $lVal = $lmsTrend[$idx] ?? 0;
            $lY = 200 - (int) round(($lVal / $chartMaxVal) * 160);
            $lmsPointsArr[] = "{$x},{$lY}";
            $lmsDots[] = ['x' => $x, 'y' => $lY, 'val' => $lVal, 'month' => $mName];
        }

        $marketplacePoints = implode(' ', $marketplacePointsArr);
        $marketplacePolygon = "60," . (200 - (int)round((($marketplaceTrend[0] ?? 0) / $chartMaxVal) * 160)) . " " . $marketplacePoints . " 660,200 60,200";

        $lmsPoints = implode(' ', $lmsPointsArr);
        $lmsPolygon = "60," . (200 - (int)round((($lmsTrend[0] ?? 0) / $chartMaxVal) * 160)) . " " . $lmsPoints . " 660,200 60,200";

        // 3. User Demographics (Data Aktual Pengguna)
        $techniciansCount = User::whereIn('role', ['user', 'technician', 'siswa', 'member'])->count();
        $sponsorsCount = User::where('role', 'sponsor')->count();
        $adminsCount = User::where('role', 'super_admin')->count();
        
        $totalDemoUsers = $techniciansCount + $sponsorsCount + $adminsCount;
        $circumference = 377; // 2 * pi * 60 approx 376.99

        $techPct = $totalDemoUsers > 0 ? round(($techniciansCount / $totalDemoUsers) * 100, 1) : 0;
        $sponPct = $totalDemoUsers > 0 ? round(($sponsorsCount / $totalDemoUsers) * 100, 1) : 0;
        $admPct  = $totalDemoUsers > 0 ? round(($adminsCount / $totalDemoUsers) * 100, 1) : 0;

        $techLen = $totalDemoUsers > 0 ? round(($techniciansCount / $totalDemoUsers) * $circumference, 1) : 0;
        $sponLen = $totalDemoUsers > 0 ? round(($sponsorsCount / $totalDemoUsers) * $circumference, 1) : 0;
        $admLen  = $totalDemoUsers > 0 ? round(($adminsCount / $totalDemoUsers) * $circumference, 1) : 0;

        $demographics = [
            'technicians' => [
                'label' => 'Teknisi / Siswa',
                'count' => $techniciansCount,
                'pct' => $techPct,
                'color' => '#1a68f0',
                'dasharray' => "{$techLen} {$circumference}",
                'dashoffset' => 0,
            ],
            'sponsors' => [
                'label' => 'Mitra Sponsor',
                'count' => $sponsorsCount,
                'pct' => $sponPct,
                'color' => '#0ea5e9',
                'dasharray' => "{$sponLen} {$circumference}",
                'dashoffset' => -$techLen,
            ],
            'admins' => [
                'label' => 'Administrator',
                'count' => $adminsCount,
                'pct' => $admPct,
                'color' => '#64748b',
                'dasharray' => "{$admLen} {$circumference}",
                'dashoffset' => -($techLen + $sponLen),
            ],
        ];

        // 4. Top 5 Kelas (LMS - Data Aktual)
        $topCourses = Course::withCount('lessons')->take(5)
            ->get()
            ->map(function ($course) {
                return [
                    'name' => $course->title,
                    'type' => ucfirst(str_replace('_', ' ', $course->type ?? 'Hardware')),
                    'instructor' => $course->instructor_name ?? $course->instructor ?? 'Tim VBAT Official',
                    'students' => $course->lessons_count ?? 0,
                    'rating' => 5.0,
                ];
            })
            ->toArray();

        // 5. Top 5 Produk (Marketplace - Data Aktual)
        $topProducts = SponsorProduct::with('sponsor')
            ->orderByDesc('sold_count')
            ->take(5)
            ->get()
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'sponsor' => $product->sponsor?->name ?? 'Mitra VBAT',
                    'price' => $product->price,
                    'sold' => (int) $product->sold_count,
                    'image' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                ];
            })
            ->toArray();

        return view('livewire.analytics-dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalMarketplaceOrders' => $totalMarketplaceOrders,
            'totalActiveStudents' => $totalActiveStudents,
            'totalCertificates' => $totalCertificates,
            'trendMonths' => $trendMonths,
            'marketplaceTrend' => $marketplaceTrend,
            'lmsTrend' => $lmsTrend,
            'yLabels' => $yLabels,
            'marketplacePoints' => $marketplacePoints,
            'marketplacePolygon' => $marketplacePolygon,
            'marketplaceDots' => $marketplaceDots,
            'lmsPoints' => $lmsPoints,
            'lmsPolygon' => $lmsPolygon,
            'lmsDots' => $lmsDots,
            'demographics' => $demographics,
            'totalDemoUsers' => $totalDemoUsers,
            'topCourses' => $topCourses,
            'topProducts' => $topProducts,
        ])->layout('layouts.admin', [
            'title' => 'Analitik Aplikasi - Overview',
            'pageTitle' => 'Analitik Aplikasi - Overview',
        ]);
    }
}
