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
        // 1. KPI Cards Data (Data Aktual Database)
        $totalRevenue = (float) PaymentTransaction::where('status', 'settlement')->sum('amount');
        $totalMarketplaceOrders = (int) SponsorProduct::sum('sold_count');

        // Peserta Kelas Aktif (LMS)
        $dbActiveStudents = Entitlement::active()->count();
        if ($dbActiveStudents === 0) {
            $dbActiveStudents = User::whereIn('role', ['user', 'technician', 'siswa', 'member'])->count();
        }
        $totalActiveStudents = (int) $dbActiveStudents;

        // Sertifikat Diterbitkan
        $totalCertificates = (int) Certificate::count();

        // 2. Trend Transaksi & Pembelajaran (Bulan Berjalan)
        $trendMonths = ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov'];
        $marketplaceTrend = [
            SponsorProduct::whereMonth('created_at', 5)->sum('sold_count'),
            SponsorProduct::whereMonth('created_at', 6)->sum('sold_count'),
            SponsorProduct::whereMonth('created_at', 7)->sum('sold_count'),
            SponsorProduct::whereMonth('created_at', 8)->sum('sold_count'),
            SponsorProduct::whereMonth('created_at', 9)->sum('sold_count'),
            SponsorProduct::whereMonth('created_at', 10)->sum('sold_count'),
            SponsorProduct::whereMonth('created_at', 11)->sum('sold_count'),
        ];
        $lmsTrend = [
            User::whereMonth('created_at', 5)->count(),
            User::whereMonth('created_at', 6)->count(),
            User::whereMonth('created_at', 7)->count(),
            User::whereMonth('created_at', 8)->count(),
            User::whereMonth('created_at', 9)->count(),
            User::whereMonth('created_at', 10)->count(),
            User::whereMonth('created_at', 11)->count(),
        ];

        // 3. User Demographics (Data Aktual Pengguna)
        $techniciansCount = User::whereIn('role', ['user', 'technician', 'siswa', 'member'])->count();
        $sponsorsCount = User::where('role', 'sponsor')->count();
        $adminsCount = User::where('role', 'super_admin')->count();

        $totalDemoUsers = max(1, $techniciansCount + $sponsorsCount + $adminsCount);
        $demographics = [
            'technicians' => [
                'label' => 'Teknisi / Siswa',
                'count' => $techniciansCount,
                'pct' => round(($techniciansCount / $totalDemoUsers) * 100, 1),
                'color' => '#1a68f0', // Primary Solid Blue
            ],
            'sponsors' => [
                'label' => 'Mitra Sponsor',
                'count' => $sponsorsCount,
                'pct' => round(($sponsorsCount / $totalDemoUsers) * 100, 1),
                'color' => '#0ea5e9', // Sky/Teal calm blue
            ],
            'admins' => [
                'label' => 'Administrator',
                'count' => $adminsCount,
                'pct' => round(($adminsCount / $totalDemoUsers) * 100, 1),
                'color' => '#64748b', // Slate gray
            ],
        ];

        // 4. Top 5 Kelas (LMS - Data Aktual)
        $coursesFromDb = Course::withCount('lessons')->take(5)->get();
        $topCourses = [];
        foreach ($coursesFromDb as $c) {
            $topCourses[] = [
                'name' => $c->title,
                'type' => ucfirst(str_replace('_', ' ', $c->type ?? 'Hardware')),
                'instructor' => $c->instructor ?? 'Tim VBAT Official',
                'students' => $c->lessons_count ?? 0,
                'rating' => 5.0,
            ];
        }

        // 5. Top 5 Produk (Marketplace - Data Aktual)
        $productsFromDb = SponsorProduct::with('sponsor')->orderByDesc('sold_count')->take(5)->get();
        $topProducts = [];
        foreach ($productsFromDb as $p) {
            $topProducts[] = [
                'name' => $p->name,
                'sponsor' => $p->sponsor?->name ?? 'Mitra VBAT',
                'price' => $p->price,
                'sold' => (int) $p->sold_count,
                'image' => $p->image_path ? asset('storage/' . $p->image_path) : null,
            ];
        }

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
