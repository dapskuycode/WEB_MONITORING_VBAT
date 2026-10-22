<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\SponsorProduct;
use App\Models\User;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Sponsor;
use App\Models\Campaign;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    /**
     * Export Analytics or Dashboard data to formatted Excel (.xlsx).
     * Murni menggunakan data aktual database tanpa data dummy/sample fallback.
     */
    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $source = $request->query('source', 'analytics'); // 'analytics' or 'dashboard'

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('VBAT Monitoring System')
            ->setTitle('VBAT Analytics & Performance Report')
            ->setSubject('Laporan Analitik VBAT (Data Aktual)')
            ->setDescription('Laporan ekspor data kinerja, sponsor, dan metrik sistem VBAT berbasis database aktual.');

        // ==========================================
        // SHEET 1: Ringkasan Metrik (KPI) Aktual
        // ==========================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan KPI');

        // Header Dokumen
        $sheet->setCellValue('A1', 'LAPORAN KINERJA & ANALITIK SISTEM VBAT (DATA AKTUAL)');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1a68f0'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $periodeText = ($startDate && $endDate) ? "Filter Rentang Tanggal: {$startDate} s/d {$endDate}" : 'Filter Rentang Tanggal: Semua Waktu (Real-time Database)';
        $sheet->setCellValue('A2', $periodeText . ' | Waktu Cetak: ' . now()->translatedFormat('d F Y H:i:s'));
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('828282'));

        // KPI Headers
        $headers = ['No', 'Indikator Kinerja (KPI)', 'Nilai Aktual Database', 'Keterangan'];
        $row = 4;
        foreach ($headers as $colIdx => $header) {
            $colLetter = chr(65 + $colIdx);
            $sheet->setCellValue("{$colLetter}{$row}", $header);
        }
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1A68F0');
        $sheet->getStyle("A{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Fetch Real Database KPI Metrics (Tanpa fallback dummy)
        $totalRevenue = (float) PaymentTransaction::where('status', 'settlement')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']))
            ->sum('amount');

        $totalSettledTransactions = PaymentTransaction::where('status', 'settlement')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']))
            ->count();

        $activeSponsorsCount = Sponsor::where('is_active', true)->count();
        $totalSponsorsCount = Sponsor::count();

        $totalCampaigns = Campaign::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']))->count();
        $activeCampaigns = Campaign::where('is_active', true)->count();

        $totalProducts = SponsorProduct::count();
        $totalSoldProducts = (int) SponsorProduct::sum('sold_count');

        $totalStudents = User::whereIn('role', ['user', 'technician', 'siswa', 'member'])
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']))
            ->count();

        $totalAllUsers = User::count();
        $totalCertificates = Certificate::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']))->count();

        $kpiData = [
            [1, 'Total Pendapatan Terverifikasi', 'Rp ' . number_format($totalRevenue, 0, ',', '.'), 'Akumulasi transaksi settlement Midtrans di database'],
            [2, 'Total Transaksi Sukses', number_format($totalSettledTransactions) . ' Transaksi', 'Jumlah pembayaran status settlement'],
            [3, 'Total Mitra Sponsor Aktif', number_format($activeSponsorsCount) . ' Mitra (dari total ' . $totalSponsorsCount . ')', 'Mitra sponsor berstatus aktif di database'],
            [4, 'Total Kampanye Iklan', number_format($totalCampaigns) . ' Kampanye (' . $activeCampaigns . ' aktif)', 'Banner hero, slider, dan pop-up iklan sponsor'],
            [5, 'Total Produk Suku Cadang Terdaftar', number_format($totalProducts) . ' Produk', 'Total katalog produk suku cadang di etalase'],
            [6, 'Total Produk Terjual', number_format($totalSoldProducts) . ' Unit', 'Akumulasi total terjual suku cadang'],
            [7, 'Peserta / Teknisi Terdaftar', number_format($totalStudents) . ' Pengguna (total sistem: ' . $totalAllUsers . ')', 'Akun teknisi dan siswa pada sistem LMS'],
            [8, 'Sertifikat Kompetensi Diterbitkan', number_format($totalCertificates) . ' Lembar', 'Sertifikat kelulusan ujian/pelatihan yang tercatat'],
        ];

        $row = 5;
        foreach ($kpiData as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->setCellValue("B{$row}", $item[1]);
            $sheet->setCellValue("C{$row}", $item[2]);
            $sheet->setCellValue("D{$row}", $item[3]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        $sheet->getStyle('A4:D' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ==========================================
        // SHEET 2: Data Mitra Sponsor Aktual
        // ==========================================
        $sponsorSheet = $spreadsheet->createSheet();
        $sponsorSheet->setTitle('Mitra Sponsor');

        $sponsorSheet->setCellValue('A1', 'DAFTAR MITRA SPONSOR TERDAFTAR (DATABASE AKTUAL)');
        $sponsorSheet->mergeCells('A1:H1');
        $sponsorSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1a68f0'));

        $spHeaders = ['No', 'Nama Mitra Sponsor', 'Tier Kemitraan', 'Bobot Tayang', 'Produk', 'Kampanye', 'WhatsApp / Kontak', 'Status'];
        $row = 3;
        foreach ($spHeaders as $colIdx => $header) {
            $colLetter = chr(65 + $colIdx);
            $sponsorSheet->setCellValue("{$colLetter}{$row}", $header);
        }
        $sponsorSheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sponsorSheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1A68F0');

        $sponsors = Sponsor::with(['sponsorTier', 'products', 'campaigns'])
            ->orderBy('weight', 'desc')
            ->orderBy('name')
            ->get();

        $row = 4;
        $counter = 1;
        if ($sponsors->isNotEmpty()) {
            foreach ($sponsors as $sp) {
                $sponsorSheet->setCellValue("A{$row}", $counter++);
                $sponsorSheet->setCellValue("B{$row}", $sp->name);
                $sponsorSheet->setCellValue("C{$row}", strtoupper($sp->sponsorTier?->name ?? $sp->tier ?? 'STANDARD'));
                $sponsorSheet->setCellValue("D{$row}", $sp->weight ?? 0);
                $sponsorSheet->setCellValue("E{$row}", $sp->products->count());
                $sponsorSheet->setCellValue("F{$row}", $sp->campaigns->count());
                $sponsorSheet->setCellValue("G{$row}", $sp->whatsapp ?: ($sp->contact_email ?: '-'));
                $sponsorSheet->setCellValue("H{$row}", $sp->is_active ? 'AKTIF' : 'NONAKTIF');
                $sponsorSheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sponsorSheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sponsorSheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }
        } else {
            $sponsorSheet->setCellValue("A{$row}", 'Belum ada mitra sponsor yang terdaftar di database.');
            $sponsorSheet->mergeCells("A{$row}:H{$row}");
            $sponsorSheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $sponsorSheet->getStyle('A3:H' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'H') as $col) {
            $sponsorSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ==========================================
        // SHEET 3: Katalog Produk Sponsor Aktual
        // ==========================================
        $productSheet = $spreadsheet->createSheet();
        $productSheet->setTitle('Katalog Produk');
        $productSheet->setCellValue('A1', 'KATALOG PRODUK SUKU CADANG & ALAT SERVIS (DATABASE AKTUAL)');
        $productSheet->mergeCells('A1:G1');
        $productSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1a68f0'));

        $prHeaders = ['No', 'Nama Produk', 'Mitra Sponsor', 'Harga Normal', 'Harga Diskon', 'Terjual', 'Status'];
        $row = 3;
        foreach ($prHeaders as $colIdx => $header) {
            $colLetter = chr(65 + $colIdx);
            $productSheet->setCellValue("{$colLetter}{$row}", $header);
        }
        $productSheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $productSheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF10B981'); // Emerald green

        $products = SponsorProduct::with('sponsor')->orderBy('id', 'desc')->get();
        $row = 4;
        $pRank = 1;
        if ($products->isNotEmpty()) {
            foreach ($products as $pr) {
                $productSheet->setCellValue("A{$row}", $pRank++);
                $productSheet->setCellValue("B{$row}", $pr->name);
                $productSheet->setCellValue("C{$row}", $pr->sponsor?->name ?? 'Mitra VBAT');
                $productSheet->setCellValue("D{$row}", 'Rp ' . number_format($pr->price, 0, ',', '.'));
                $productSheet->setCellValue("E{$row}", $pr->discount_price ? ('Rp ' . number_format($pr->discount_price, 0, ',', '.')) : '-');
                $productSheet->setCellValue("F{$row}", number_format($pr->sold_count ?? 0) . ' Unit');
                $productSheet->setCellValue("G{$row}", $pr->is_active ? 'AKTIF' : 'NONAKTIF');
                $productSheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $productSheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $productSheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }
        } else {
            $productSheet->setCellValue("A{$row}", 'Belum ada produk yang terdaftar di database.');
            $productSheet->mergeCells("A{$row}:G{$row}");
            $productSheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $productSheet->getStyle('A3:G' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'G') as $col) {
            $productSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ==========================================
        // SHEET 4: Transaksi Pembayaran Aktual
        // ==========================================
        $txSheet = $spreadsheet->createSheet();
        $txSheet->setTitle('Riwayat Transaksi');
        $txSheet->setCellValue('A1', 'RIWAYAT TRANSAKSI PEMBAYARAN (DATABASE AKTUAL)');
        $txSheet->mergeCells('A1:F1');
        $txSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1a68f0'));

        $txHeaders = ['No', 'ID Transaksi / Order ID', 'Pembeli', 'Jumlah (Nominal)', 'Status', 'Waktu Transaksi'];
        $row = 3;
        foreach ($txHeaders as $colIdx => $header) {
            $colLetter = chr(65 + $colIdx);
            $txSheet->setCellValue("{$colLetter}{$row}", $header);
        }
        $txSheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $txSheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF6366F1'); // Indigo

        $transactions = PaymentTransaction::with('user')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']))
            ->latest()
            ->get();

        $row = 4;
        $tIdx = 1;
        if ($transactions->isNotEmpty()) {
            foreach ($transactions as $tx) {
                $txSheet->setCellValue("A{$row}", $tIdx++);
                $txSheet->setCellValue("B{$row}", $tx->order_id ?? $tx->id);
                $txSheet->setCellValue("C{$row}", $tx->user?->name ?? 'Pengguna Tamu');
                $txSheet->setCellValue("D{$row}", 'Rp ' . number_format($tx->amount ?? 0, 0, ',', '.'));
                $txSheet->setCellValue("E{$row}", strtoupper($tx->status ?? 'PENDING'));
                $txSheet->setCellValue("F{$row}", $tx->created_at ? $tx->created_at->format('d/m/Y H:i') : '-');
                $txSheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $txSheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $txSheet->getStyle("E{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }
        } else {
            $txSheet->setCellValue("A{$row}", 'Belum ada transaksi pembayaran tercatat pada periode ini.');
            $txSheet->mergeCells("A{$row}:F{$row}");
            $txSheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $txSheet->getStyle('A3:F' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'F') as $col) {
            $txSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set first sheet active
        $spreadsheet->setActiveSheetIndex(0);

        // Stream output response
        $filename = 'Laporan-VBAT-' . ($source === 'analytics' ? 'Analitik' : 'Dashboard') . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
