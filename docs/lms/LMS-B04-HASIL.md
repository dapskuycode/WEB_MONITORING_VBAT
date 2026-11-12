# B04: pengelolaan konten dan preview admin

Tanggal: 10 November 2026, WIB. LMS-08/LMS-09 selesai implementasi lokal dan verifikasi scope; LMS-07 memiliki fondasi schema/urutan/revisi, dengan audit data existing serta kebijakan revisi belajar masih terbuka. Belum rilis dan belum menandakan pilot LMS siswa sudah utuh.

## Acuan

Kedua repo di-fetch sebelum pengerjaan. Acuan tetap web `origin/main@5aebf5f` dan mobile `origin/haidarreview@02c5e0c` (masing-masing 0/0 terhadap HEAD saat mulai), branch `codex/lms-foundation`. Perubahan B02/B03 dipertahankan. Kode aplikasi B04 berada di web; mobile menerima dokumentasi yang sama. Belum commit/push/deploy atau mengubah vault Obsidian.

## Hasil implementasi

- CMS `/admin/content` sekarang memakai Course → Lesson (modul) → LearningMaterial. Referensi `Unit`, `CourseCategory`, kategori/harga/periode paket rekaan dan field yang tidak ada di schema dihapus dari alur ini. Tidak membuat tabel palsu untuk menyesuaikan CMS lama.
- Admin dapat membuat/mengedit kelas, slug/kode, tipe canonical, cover, deskripsi, modul, kode unit, empat jenis materi, penanda wajib/kandidat preview, urutan, status Draft/Terbit/Arsip; mencari dan memfilter kelas, serta membuka pratinjau. Menu “Konten belajar LMS” ditambahkan untuk admin. Bundling/premium tetap hak akses, bukan jenis kelas.
- Form CMS dan endpoint tulis API memakai `LmsAuthoringService` yang sama. Resource di-update dengan `expected_revision`; konflik menghasilkan 409 dan tidak menimpa data terbaru. CMS mempertahankan isian saat gagal dan tidak menampilkan sukses palsu. Upload baru dibersihkan jika transaksi simpan gagal; file lama tetap disimpan.
- Authoring mencatat aktor, resource, nilai sebelum/sesudah, IP dan user agent pada `audit_logs`. Pembuatan/perubahan status/arsip tercatat; perubahan child juga menaikkan revisi parent, sehingga reorder berbasis struktur lama ditolak.
- Penomoran urutan aktif unik per parent diperiksa di dalam transaksi dengan lock parent. Item tidak dapat dipindahkan ke parent lain melalui edit. Reorder menerima seluruh child aktif tepat satu kali, memeriksa revisi parent dan child, lalu menyimpan atomik. Tombol Naik/Turun memakai jalur yang sama; tombol pada batas urutan dinonaktifkan.
- Tindakan DELETE course/lesson/material sekarang mengarsipkan, tidak melakukan cascade delete. ID dan riwayat belajar tetap ada. Arsip parent menyembunyikan turunannya tanpa menghapus data.
- Relasi `Course::learningMaterials()` diperbaiki menjadi `hasManyThrough` lewat lessons, bukan kolom `course_id` yang tidak ada pada learning_materials.

## Migrasi tambahan

`2026_11_10_100000_add_lms_authoring_revisions.php` menambahkan:

- Course: `code` nullable unik dan `revision` default 1.
- Lesson: `code` nullable unik, `description`, `status`, `revision` default 1.
- LearningMaterial: `is_preview` default false dan `revision` default 1.

Modul existing mendapat status `published` untuk mempertahankan visibilitas sebelumnya. Pembuatan melalui layanan/API/CMS secara eksplisit memakai Draft. Kode existing tetap nullable sampai diisi melalui edit atau backfill yang diaudit. Migrasi tidak mengubah urutan existing, menghapus riwayat, membuat enrollment atau mengganti ID.

Migrasi telah dijalankan pada SQLite in-memory untuk tes dan database SQLite fixture browser yang terpisah. **Belum dijalankan pada database utama/prod.** Sebelum deployment terkoordinasi, tinjau backup dan data existing lalu terapkan migration. Konflik urutan legacy ditolak saat publish dan dapat diperbaiki melalui reorder; audit orphan/duplikasi serta backfill kode tetap bagian pekerjaan data LMS-07. Belum menambah unique index urutan yang bisa gagal pada duplikasi legacy; konsistensi jalur tulis aplikasi memakai transaksi/lock, bukan jaminan atas SQL langsung di luar aplikasi. MySQL/staging belum diuji pada batch ini.

`revision` di sini adalah kendali konflik penyimpanan. Ini belum merupakan snapshot kurikulum/attempt immutable bagi siswa; kebijakan perubahan konten dan versi hasil belajar diteruskan B06/B07/B09 sesuai LMS-01/LMS-02.

## Aturan publish

- Kelas membutuhkan setidaknya satu modul terbit. Modul membutuhkan materi terbit; materi wajib non-arsip tidak boleh tertinggal Draft. Urutan legacy yang bentrok menahan publish.
- Materi teks tidak boleh kosong; tautan eksternal harus HTTPS. PDF harus tersedia pada disk privat `lms`, dan salinan pada path publik yang sama harus sudah dimigrasikan.
- Video membutuhkan ID YouTube valid, API key server, serta hasil YouTube Data API yang menunjukkan video `processed`, `public`/`unlisted` dan `embeddable=true`. Kegagalan provider/key kosong/status private/non-embeddable menahan publish. Pemeriksaan diulang saat menerbitkan material/modul/kelas, bukan mempercayai flag verifikasi dari klien. Dasar field provider: [dokumentasi YouTube Video status](https://developers.google.com/youtube/data/reference/rest/v3/videos#status).
- Kuis wajib harus aktif dan milik materi tersebut, memiliki soal/opsi/kunci valid, total poin positif serta konfigurasi skor/attempt yang masuk rentang. Ini validasi kelengkapan schema existing, belum mesin attempt/penilaian B07.
- Gate ini berlaku pada authoring baru/ubah/publish. Tidak membuktikan seluruh konten legacy yang sudah terbit telah diaudit atau provider tidak akan berubah setelah publish.

Konfigurasi `YOUTUBE_API_KEY` ditambahkan ke `.env.example`/`config/services.php`; nilainya tidak dikirim ke browser. Batch ini tidak membuat/mengisi key nyata dan tidak memanggil provider dengan video proyek nyata. Metadata API bukan bukti pemutaran Android/iOS atau persetujuan provider produksi. Video nyata tetap perlu ditinjau lewat preview dan perangkat; batas YouTube unlisted dari B03 tetap berlaku.

## Preview admin

- `/admin/lms/preview/{courseId}` hanya untuk super admin, memperlihatkan struktur/status materi serta simulasi **siswa tanpa paket**. Bukan impersonasi siswa tertentu; media dibuka dengan izin admin.
- Draft dapat dipratinjau tanpa dipublikasikan. Teks dirender escaped, video memakai iframe YouTube, PDF memakai URL bertanda tangan 5 menit yang terikat ke admin dan tetap memerlukan sesi/role admin.
- Preview tidak membuat enrollment/grant/progres, memulai attempt, mengirim jawaban, atau mengubah status publikasi. Reuse URL oleh siswa/admin lain, query yang dimodifikasi dan URL kedaluwarsa ditolak.
- `is_preview` hanya penanda kandidat untuk integrasi B05. Belum memberi akses publik atau menetapkan durasi preview siswa; keputusan produk terkait tetap terbuka.

## Perubahan kontrak admin

Endpoint existing dipertahankan, dengan perubahan berikut:

1. POST `/api/v1/courses`, `/lessons`, `/learning-materials`: schema canonical, default Draft, resource mengembalikan `revision`.
2. PUT resource existing: kirim `expected_revision`. Revision tidak cocok →409; tidak dikirim →422. ID parent edit tidak dapat diganti.
3. DELETE resource serta POST `/courses/{id}/archive`: kirim `expected_revision`; hasil berupa arsip, bukan penghapusan fisik.
4. PUT `/api/v1/lms-admin/{courses|lessons}/{id}/order`: admin saja. Payload `expected_revision` dan `items: [{id, revision}, ...]`, berisi seluruh child aktif dalam urutan tujuan. Course parent mengurutkan modul; Lesson parent mengurutkan materi.
5. Upload PDF/thumbnail materi memerlukan Draft dan `expected_revision`; ubah ke Draft lebih dahulu sebelum mengganti berkas published. Cover CMS memakai gambar JPG/PNG/WebP, maksimal 4 MB; PDF maksimal 20 MB dan tetap privat.
6. Listing/detail materi/kuis dan media siswa kini juga memeriksa status modul. Materi published di bawah modul Draft/Arsip tidak dapat menghindari batas parent.
7. Apply bulk import lama sementara ditutup (503) karena menulis langsung dan bisa melewati publish/revision. Preview/template masih tersedia; perbaikan parsing seluruh baris, apply terikat validasi dan transaksi import ada pada B08. Path file sementara preview dibetulkan memakai root disk local yang sebenarnya.

Consumer admin lama harus menyesuaikan revision dan semantik arsip. Deploy web/mobile tetap perlu koordinasi dengan integrasi siswa B05/B06; batch ini tidak menyatakan API contoh draft `/api/v1/lms` seluruhnya sudah tersedia.

## Bukti verifikasi

- **83 tes / 471 assertions lulus** pada filter `LmsAuthoringTest|LmsAccessMediaTest|LmsBoundaryTest|LearningMaterialApiTest|AuthApiTest|ReviewFindingsTest|AdminHierarchyAndGenderSanitationTest|SidebarRoleSeparationTest`.
- **16 tes baru B04**: pilot CMS video (HTTP fixture), validasi API/CMS yang sama, konflik revisi, publish child, provider/private video, urutan atomik/cross-parent, revisi agregat, Draft modul, arsip dan progres, preview escaped/read-only, signature PDF, isian saat gagal, cleanup upload stale, apply import/upload published ditolak, kelengkapan kuis dan relasi course-material, serta sumber PDF privat.
- Suite API luas **195/196 tes lulus**. Satu gagal `GapClosingFeatureTest::sponsors_index_includes_badge_metadata` karena URL logo relatif; kegagalan sama sudah dibuktikan pada baseline B02. Tes/fitur sponsor tidak diubah untuk menyembunyikan kegagalan.
- PHPStan layanan authoring/YouTube, ContentCms, controller order/preview dan model Course: **0 error**, memory limit 512 MB. Bukan klaim PHPStan seluruh repo bersih.
- `npm ci --ignore-scripts` sesuai lockfile, lalu `npm run build`: lulus. Kendala paket lokal dan timeout font saat percobaan awal selesai dipulihkan; tidak mengganti provider/font. Warning optional `fontaine` tidak menghentikan build. Build CSS/manifest disertakan karena repo melacak `public/build`.
- Pint dirty dan `git diff --check`: lulus. Tes material lama disesuaikan untuk revision, verifikasi provider fixture dan arsip yang mempertahankan riwayat. Fixture update dibuat deterministik agar validasi HTTPS tidak bergantung tipe/URL acak.
- Browser lokal menggunakan database fixture terpisah: login admin, navigasi CMS, pilih kelas/modul/materi, terbitkan bacaan → modul → kelas, serta preview baca saja. Ini data uji, bukan konten pengguna.
- Tampilan diperiksa pada 390, 820 dan 1280 px: `scrollWidth == innerWidth`. Label native diuji setelah pergantian tab (0 field CMS tanpa label); fokus Tab terlihat. Tema terang/gelap diperiksa dan perbaikan label gelap diuji ulang. Rasio teks label gelap 13,88:1; batas kontrol 4,74:1 di terang dan 3,19:1 di gelap (batas non-teks ≥3:1). Ini pemeriksaan scope, bukan sertifikasi WCAG seluruh aplikasi.
- Bukti gambar: `screenshots/B04-cms-desktop.jpg`, `B04-cms-mobile.jpg`, `B04-cms-dark.jpg`, `B04-preview.jpg`. Catatan pemeriksaan antislop pada `LMS-B04-UI-CHECK.md`.
- Server fixture sudah dihentikan, override viewport dan tema fixture dikembalikan. Tidak ada perubahan source Flutter pada B04, sehingga tes Flutter B03 tidak diklaim sebagai tes baru batch ini. Graphify belum dibangun ulang; source/test/laporan menjadi acuan terbaru.

## Kelanjutan

Batch berikut **B05: LMS-10/LMS-11**, kelas milik akun, silabus dan route mobile ber-ID memakai data server. K2/K3/K4, audit/migrasi data nyata LMS-07, pemilihan provider/perangkat B03, progres/attempt dan snapshot B06/B07, serta pembayaran LMS-30 tetap terbuka. Pembelian baru dan HS otomatis tetap tertutup sesuai B03. Tidak ada kewajiban menyelesaikan seluruh inventaris non-LMS sebelum mulai B05 pada fixture lokal.
