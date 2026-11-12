# B05: Kelas Saya dan silabus Flutter

Tanggal: 10 November 2026, WIB. Integrasi alur utama LMS-11 terimplementasi lokal; LMS-10 selesai untuk daftar/detail/akses/silabus, tetapi progres terverifikasi dan `next_unit` masih menunggu B06. Belum commit, push, PR atau deploy.

## Perubahan

- Kedua repo sudah di-fetch sebelum implementasi; acuan tetap web `origin/main@5aebf5f` dan mobile `origin/haidarreview@02c5e0c`, branch lokal `codex/lms-foundation`. Perubahan B02–B04 dipertahankan.
- API baru, wajib Sanctum: `GET /api/v1/lms/courses?scope=mine|catalog&page=1&per_page=20` dan `GET /api/v1/lms/courses/{id}`. `per_page` maksimal 50; selector `user_id`/`email` tidak dapat mengganti akun autentikasi. Respons memakai `private, no-store`.
- Katalog berisi kelas canonical yang published dan tidak diarsipkan, termasuk metadata kelas terkunci. Kelas Saya diturunkan dari riwayat grant paket akun (termasuk expired/revoked/future) serta partisipasi akun pada Free Class melalui view existing. Free Class tanpa riwayat partisipasi tersedia di Katalog. Ini bukan implementasi tabel enrollment baru dan tidak memutuskan kebijakan premium/durasi yang masih terbuka.
- Keputusan akses tetap satu layanan B03, kini membedakan `accessible`, `locked_entitlement`, `locked_prerequisite`, `expired`, `revoked`, `not_started`, `unavailable`. Grant aktif lain yang overlap tetap dapat memberi akses; status membership/premium lokal tidak membuka paket.
- Silabus hanya berisi modul/materi published sesuai urutan server dan ID sebenarnya. Aktivitas membawa `kind` (`material` atau `quiz`), ID, course ID dan module ID; ID kuis/materi yang sama tidak tertukar. Quiz hanya disertakan bila aktif dan benar-benar terkait materi itu.
- DTO eksplisit tidak membawa YouTube ID/URL, path PDF, isi bacaan atau kunci kuis. Cover hanya URL folder cover publik yang sesuai. Video tetap meminta grant media B03 ketika dibuka; metadata bukan otorisasi playback.
- Progres existing berasal dari endpoint persentase klien yang belum tepercaya. Karena itu respons memakai `progress.available=false`, persentase/completion null dan `next_unit=null`, bukan menganggap view 100% sebagai kelulusan. `first_activity` hanya referensi aktivitas pertama jika dapat dibuka, bukan bukti resume/kelulusan. Perhitungan progres/urutan prasyarat masuk LMS-12/13 pada B06.
- Repository mock diganti adapter `ApiClient`; DTO bertipe dan Cubit memisahkan request/state dari widget. Empty tetap empty; kegagalan/malformed response tidak diganti demo. Pagination eksplisit, retry, pergantian scope dan versi sesi menolak respons lama. HTTP 401 membersihkan sesi lokal tanpa mencoba mencabut token yang sudah ditolak server.
- Layar Belajar, silabus/detail modul dan dashboard belajar memakai alur data ini. Rating, durasi, silabus, progres dan dashboard contoh pada alur tersebut dihapus. Jalur utama: `/courses/{courseId}`, `/courses/{courseId}/modules/{moduleId}`, dan `/courses/{courseId}/modules/{moduleId}/materials/{materialId}`. Parent/material dicocokkan terhadap silabus sebelum player dibuka.
- Route legacy tanpa ID valid menampilkan pesan keterbatasan; tidak menebak berdasarkan nama kategori. Tautan lama dari home/bookmark yang belum memiliki ID backend perlu dimigrasikan saat integrasi consumer tersebut; tidak diarahkan ke konten contoh.
- Kuis dan materi non-video tampil sebagai metadata dengan pesan belum tersedia; tidak mengarah ke layar kuis simulasi atau membuka URL PDF publik. Mesin attempt masuk B07, pembaca bacaan/PDF B08. Halaman legacy lain di luar empat layar tersebut belum dinyatakan bebas mock.

## Verifikasi

- **Backend: 88 tes / 544 assertions lulus**, filter StudentCourseTest, LmsAccessMediaTest, LmsAuthoringTest, LmsBoundaryTest, LearningMaterialApiTest, AuthApiTest, ReviewFindingsTest, AdminHierarchyAndGenderSanitationTest dan SidebarRoleSeparationTest. B05 menambah 5 tes untuk scope akun/empty, status/waktu grant, urutan/serializer, partisipasi tanpa completion palsu, dan pagination/metadata terkunci.
- **Flutter: 41 tes lulus** pada `student_courses_test.dart`, `auth_session_test.dart`, `lms_access_test.dart`, `regression_flow_test.dart`, `widget_test.dart`. B05 menambah 13 tes untuk parsing/header, ID/parent, scope/page, 401, race logout/pergantian akun, error/retry/empty, akses terkunci dan layout.
- `flutter analyze --no-pub --no-fatal-infos`: exit 0, tanpa error/warning; hanya empat info existing pada analytics_tracker. PHPStan controller StudentCourse dan EntitlementService: 0 error. Pint dan diff whitespace checks lulus.
- UI fixture Flutter diuji pada 390/820 px, tema terang/gelap dan skala teks 200%; overflow pagination yang ditemukan sudah diperbaiki. Render fixture memakai font SDK Flutter, bukan screenshot perangkat/staging. Bukti: `screenshots/B05-courses-light.png` dan `B05-courses-dark.png` pada kedua repo.
- Tidak mengulang suite sponsor/golden yang tidak berubah; kegagalan baseline sponsor yang tercatat B02/B04 tidak dianggap selesai. Tidak ada klaim build Android/iOS atau uji player/provider nyata pada batch ini.

## Pemeriksaan UI scope baru

Arah mengikuti aplikasi VBAT dan komponen Material existing, ENERGY 1 / RHYTHM 2 / MOTION 1; mode antislop `during` sesuai pilihan sesi sebelumnya.

- PASS data/claims: label berasal dari DTO; tanpa rating, testimoni, progres atau durasi rekaan. Fixture diberi nama Fixture B05.
- PASS purpose/identity: daftar kelas dan hierarki modul/aktivitas melayani navigasi belajar; warna/theme existing dipakai, tanpa aset dekoratif baru.
- PASS state/control: widget test menekan scope, pagination dan retry; empty/error/signed-out/locked berbeda. Kuis/reader belum siap tidak ditampilkan sebagai tombol yang berhasil membuka simulasi.
- PASS layout/theme: tes reflow dua lebar dan dua tema; skala teks 200% tanpa exception overflow setelah perbaikan. Chip/tombol/ListTile memakai kontrol Material berlabel.
- PASS source/runtime: perubahan berada pada source Flutter/PHP, tanpa patch DOM. Widget berjalan pada test harness dan bukti render diperiksa. Ini bukan sertifikasi aksesibilitas seluruh aplikasi atau bukti native/staging; pemeriksaan rilis tetap wajib.

## Kelanjutan

Batch berikut **B06: LMS-12/LMS-13, sesi playback dan progres server/resume**. Tidak ada migrasi baru B05; database tujuan tetap harus mempunyai migrasi B04 sebelum deployment. K2/K3/K4, audit data existing, uji perangkat/provider, enrollment final, pembayaran LMS-30 dan HS/sertifikat masih terbuka. Graphify belum dibangun ulang setelah batch implementasi; source/test/laporan ini adalah acuan terbaru.
