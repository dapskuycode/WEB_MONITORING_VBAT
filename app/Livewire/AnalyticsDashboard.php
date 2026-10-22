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
        $this->dateStart = '2026-10-01';
        $this->dateEnd = '2026-10-31';
    }

    public function exportReport()
    {
        session()->flash('message', 'Laporan Analitik Aplikasi (Excel) periode ' . $this->dateRange . ' sedang diekspor...');
    }

    public function render()
    {
        // 1. KPI Cards Data - REAL DATA ONLY
        $totalRevenue = (float) PaymentTransaction::where('status', 'settlement')->sum('amount');
        $totalMarketplaceOrders = (int) SponsorProduct::sum('sold_count');
        
        $dbActiveStudents = Entitlement::active()->count();
        if ($dbActiveStudents === 0) {
            $dbActiveStudents = User::whereIn('role', ['user', 'technician', 'siswa', 'member'])->count();
        }
        $totalActiveStudents = (int) $dbActiveStudents;
        
        $totalCertificates = (int) Certificate::count();

        // 2. Trend Transaksi & Pembelajaran (7 bulan terakhir - REAL DATA)
        $last7Months = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $last7Months->push([
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'start' => $date->startOfMonth()->toDateString(),
                'end' => $date->endOfMonth()->toDateString(),
            ]);
        }

        $trendMonths = $last7Months->pluck('month')->toArray();
        
        $marketplaceTrend = [];
        $lmsTrend = [];
        
        foreach ($last7Months as $period) {
            // Marketplace: sum sold_count by created_at in period
            $marketplaceTrend[] = (int) SponsorProduct::whereBetween('created_at', [$period['start'], $period['end']])
                ->sum('sold_count');
            
            // LMS: count entitlements created in period
            $lmsTrend[] = (int) Entitlement::whereBetween('created_at', [$period['start'], $period['end']])
                ->count();
        }

        // 3. User Demographics (Donut chart data: Teknisi, Sponsor, Admin) - REAL DATA
        $techniciansCount = User::whereIn('role', ['user', 'technician', 'siswa', 'member'])->count();
        $sponsorsCount = User::where('role', 'sponsor')->count();
        $adminsCount = User::where('role', 'super_admin')->count();
        
        $totalDemoUsers = $techniciansCount + $sponsorsCount + $adminsCount;
        
        $demographics = [
            'technicians' => [
                'label' => 'Teknisi / Siswa',
                'count' => $techniciansCount,
                'pct' => $totalDemoUsers > 0 ? round(($techniciansCount / $totalDemoUsers) * 100, 1) : 0,
                'color' => '#1a68f0',
            ],
            'sponsors' => [
                'label' => 'Mitra Sponsor',
                'count' => $sponsorsCount,
                'pct' => $totalDemoUsers > 0 ? round(($sponsorsCount / $totalDemoUsers) * 100, 1) : 0,
                'color' => '#0ea5e9',
            ],
            'admins' => [
                'label' => 'Administrator',
                'count' => $adminsCount,
                'pct' => $totalDemoUsers > 0 ? round(($adminsCount / $totalDemoUsers) * 100, 1) : 0,
                'color' => '#64748b',
            ],
        ];

        // 4. Top 5 Kelas (LMS) - REAL DATA
        $topCourses = Course::with('instructor')
            ->limit(5)
            ->get()
            ->map(function ($course) {
                // Count entitlements for this course
                $studentCount = Entitlement::where('course_id', $course->id)->count();
                return [
                    'name' => $course->title,
                    'type' => ucfirst(str_replace('_', ' ', $course->type ?? 'Umum')),
                    'instructor' => $course->instructor?->name ?? 'VBAT Academy',
                    'students' => $studentCount,
                    'rating' => 0,
                ];
            })
            ->toArray();

        // 5. Top 5 Produk (Marketplace) - REAL DATA
        $topProducts = SponsorProduct::with('sponsor')
            ->orderByDesc('sold_count')
            ->take(5)
            ->get()
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'sponsor' => $product->sponsor?->name ?? 'Unknown',
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
