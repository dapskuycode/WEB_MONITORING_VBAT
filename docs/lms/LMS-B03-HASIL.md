# B03 — Entitlement dan akses media

Tanggal: 10 November 2026, WIB. Status: implementasi fondasi lokal teruji; **LMS-05 dan LMS-06 belum selesai penuh atau siap rilis**.

## Acuan dan batas kerja

Kedua remote sudah di-fetch sebelum implementasi. Acuan tetap web `origin/main@5aebf5f` dan mobile `origin/haidarreview@02c5e0c`; HEAD masing-masing tidak tertinggal/lebih maju dari acuan (0/0), pada branch lokal `codex/lms-foundation`. Perubahan B02 dipertahankan. Belum commit, push, deploy, menjalankan migrasi data nyata, atau mengubah vault Obsidian.

K2/K3/K4 pada usulan keputusan belum dianggap disetujui. Batch ini membaca grant existing, tidak menentukan harga, periode pembelian baru, tanggal akhir alumni, cakupan premium, atau ambang kelulusan baru. Belum ada tabel enrollment/migrasi skema bisnis baru; skema existing `entitlements` menjadi sumber izin sementara sampai kontrak LMS-02 disepakati.

## Perubahan backend

- `EntitlementService` dan `MediaAuthorizationService` memakai satu keputusan akses berdasarkan `package_type`, `status`, `starts_at`, `expires_at`. Grant berlaku saat `starts_at <= now < expires_at`; null start/expiry berarti tanpa batas pada sisi tersebut. Grant revoked/expired dan grant di masa depan tidak berlaku.
- Tipe kelas canonical adalah `courses.type`. Free Class published diizinkan; Android menerima grant Android atau bundling, iPhone menerima iPhone atau bundling. Flag legacy `is_free_class` tidak boleh membuka kelas bertipe berbayar. Cakupan premium belum disepakati: grant `premium` sendiri tidak memperluas akses ke seluruh kelas. Overlap grant aktif tetap sah sampai semua grant terkait berakhir/dicabut.
- Draft, archived, materi yang dihapus, dan parent yang tidak published tidak memperoleh izin media. Admin tidak otomatis melewati aturan playback; preview admin terpisah masuk B04.
- Hardware Solution ditolak dengan alasan `prerequisite_unavailable` sampai pemetaan kelas prasyarat dan kelulusan tepercaya tersedia (B06/B09). Angka lokal 90% tidak membuka akses. Ini status implementasi tertunda, bukan keputusan meniadakan fitur HS.
- `POST /api/v1/membership/activate` sekarang membutuhkan autentikasi dan selalu mengembalikan 409 `PURCHASE_VERIFICATION_REQUIRED`; tidak menerima email/user_id sebagai pemilih akun dan tidak membuat KTA/grant.
- Pembuatan transaksi baru `POST /api/v1/payment/transactions` sementara mengembalikan 503 `PURCHASES_UNAVAILABLE`, tanpa membuat transaksi atau menghubungi provider. Ini mencegah pembelian baru sebelum LMS-30 menyelaraskan webhook/grant/refund, konfigurasi kunci, verifikasi nominal/status dan idempotensi. Webhook existing masih memiliki kolom lama; jangan mengaktifkan pembayaran atau menganggap refund existing sudah benar.
- Endpoint legacy penerbitan KTA mengembalikan 503 `CERTIFICATE_ISSUANCE_UNAVAILABLE`; tidak lagi mencampur sertifikat dengan baris entitlement. KTA existing tidak dihapus.
- `GET /api/v1/entitlements/access` mengembalikan `server_time` dan keputusan per tipe beserta `reason`/`expires_at`. Endpoint akses per course memakai layanan sama. Daftar grant memakai filter schema nyata `package_type`/`status`, scope user dari sesi.

## Berkas dan kontrak media yang sudah diimplementasikan

Semua endpoint berikut memerlukan autentikasi Sanctum, termasuk media Free Class. Tamu masih dapat melihat metadata katalog; akses preview/tamu tanpa login belum diimplementasikan.

1. `POST /api/v1/media/access-check`, payload `material_ids` berisi 1–50 ID unik: hasil keyed per ID berisi `access`, `reason`, `media_url`. Alasan penolakan materi dibuat umum agar tidak mengungkap metadata draft/akun lain.
2. URL media memakai signature Laravel, masa berlaku 5 menit, material ID, jenis media, dan user ID. Token bearer tidak berada di URL. Saat URL dibuka, server memeriksa signature, akun, serta hak akses/status publikasi **lagi**, sehingga pencabutan grant setelah penerbitan URL tetap menolak akses.
3. `GET /api/v1/media/serve/pdf/{id}` hanya membaca disk `lms` di `storage/app/private/lms`, tanpa fallback ke public. Path traversal dan symlink yang keluar root ditolak. Respons PDF memakai `private, no-store`.
4. `GET /api/v1/media/serve/video/{id}` mengembalikan ID/embed YouTube setelah izin lolos. `grant_expires_at` adalah expiry izin VBAT; `provider_url_expires: false` menjelaskan bahwa URL YouTube itu sendiri tidak kedaluwarsa. Dummy access token dihapus.
5. Manifest course memakai kolom `material_type=pdf_document`, hanya materi published, dan URL bertanda tangan. Tidak membagikan object key/path publik. Ini kontrak akses online, belum lisensi offline.
6. Upload PDF API dan penempatan upload PDF pada CMS diarahkan ke disk privat. Respons upload tidak lagi mengembalikan URL publik. Perbaikan field/form CMS secara menyeluruh tetap B04.
7. Proxy publik `/media-file` dan `/storage/{path}` hanya melayani MIME gambar yang diizinkan, dengan canonical path di root yang sesuai; menolak PDF, path PDF yang direferensikan materi, dan direktori `learning/pdfs`. Ini sengaja memperketat consumer lama yang memakai proxy publik untuk dokumen.

## Migrasi PDF lama — belum dijalankan pada data nyata

Menambahkan middleware API tidak menutup akses langsung webserver melalui `public/storage`, CDN, atau salinan lama. Sebelum memakai PDF berbayar nyata:

```sh
php artisan lms:privatize-pdfs
php artisan lms:privatize-pdfs --apply
```

Perintah pertama hanya dry run. Perintah `--apply` menyalin berkas yang direferensikan materi (termasuk soft-deleted) dan berkas di direktori publik `learning/pdfs`, memverifikasi SHA-256 sumber/tujuan, baru menghapus original publik. DB path tetap sama, disk pembacaan berubah. Konflik isi tidak menimpa target atau menghapus original; exit code gagal perlu ditangani. Menjalankan ulang aman untuk berkas yang sudah dipindahkan. Berkas hilang/path di luar root perlu audit manual.

Operator wajib meninjau hasil dry run, backup, symlink/CDN/cache, direktori/salinan di luar lokasi yang dipindai dan tautan publik lama. Tes fixture bukan bukti data produksi telah dipindahkan. Jangan buka konten berbayar sebelum migrasi dan audit ini terbukti.

## Perubahan mobile

- Izin kelas berasal dari snapshot server, dengan cache tampilan maksimal 5 menit atau sisa expiry grant (mana yang lebih pendek). Hanya tipe persis yang dikenali; bundling/flag lokal tidak lagi memberi akses otomatis. Operasi media selalu meminta izin server baru, sehingga cache tampilan bukan otorisasi konten.
- Snapshot gagal/expired mengosongkan izin berbayar. Versi sesi dan urutan request menahan respons terlambat setelah logout/pergantian akun atau setelah keputusan server yang lebih baru.
- `grantEntitlement` lokal dan menu Dev pengubah akses pada layar belajar dihapus. `learningProgress` lokal tidak membuka HS. Identitas KTA tidak lagi diturunkan dari kepemilikan paket.
- Aktivasi lokal/pembelian simulasi tidak dapat membuat grant atau KTA. Layar paket menyatakan pembelian belum tersedia dan tidak menampilkan pembayaran berhasil dari tombol konfirmasi lokal.
- Player memakai `youtube_player_flutter` yang membungkus YouTube IFrame, menggantikan ekstraksi stream `YoutubeExplode` dan pemutar raw stream. Harus ada ID materi backend dan izin server; URL dari parameter route, flag preview/free, playlist contoh, atau video fallback tidak dapat mengotorisasi playback. Respons media hanya diambil dari origin API yang sama, bearer di header, redirect otomatis dimatikan.
- Error izin/koneksi/embed mempunyai pesan gagal dan tombol periksa ulang. Logout, pindah latar belakang dan izin yang gagal saat pemeriksaan periodik menghentikan player; saat kembali foreground izin diminta ulang. Pemeriksaan periodik saat player terbuka: 60 detik.
- Sumber YouTube unlisted yang sudah diketahui tetap dapat dibuka di luar VBAT. Tidak ada klaim DRM, expiry URL provider, larangan seek yang sudah tepercaya, atau proteksi penuh dari screenshot. Provider final belum diputuskan; jalur ini kandidat online untuk pilot, bukan persetujuan provider produksi.
- Player lama yang memakai progres persentase/fallback material ID dihilangkan; batch ini belum menulis progres, belum memberi completion, belum menyediakan kurikulum/kuis terintegrasi. Pengendalian seek, resume/progres server dan integrasi playlist aktual diteruskan B05/B06.
- “Offline” lama hanya menyamarkan URL, tidak menyimpan video atau lisensi. Jalur itu dinonaktifkan dan manifest legacy dibersihkan, tanpa menghapus file unduhan nyata. UI tidak dapat melaporkan manifest tersebut sebagai unduhan yang bisa dimainkan. Offline nyata tetap LMS-27/B11.

## Validasi YouTube dan pekerjaan publish yang masih terbuka

Parser membatasi host/form URL YouTube serta video ID 11 karakter. Validasi memakai request oEmbed dengan timeout 5 detik, bukan selalu sukses/mock atau `file_get_contents` tanpa timeout. Response gagal tidak dianggap accessible. oEmbed hanya membuktikan metadata dapat diakses; `unlisted: null`, `embed_verified: false` tetap eksplisit. Tidak dapat membedakan private/unlisted atau membuktikan embed di Android/iOS melalui metadata saja.

Belum ada bukti pemutaran konten proyek pada perangkat nyata. Gate publish untuk video yang gagal embed dan rekam hasil verifikasi provider masih harus disambungkan saat authoring B04, lalu diuji bersama player. Jangan menganggap `is_valid` dari oEmbed sebagai izin publish produksi. Tidak ada panggilan YouTube nyata dalam tes; HTTP memakai fixture.

## Hasil tes

- Backend terkait: **65 tes / 325 assertions lulus**, filter `LmsAccessMediaTest|LmsBoundaryTest|AuthApiTest|LearningMaterialApiTest|ReviewFindingsTest|AdminHierarchyAndGenderSanitationTest`.
- B03 menambah **11 tes backend** pada `LmsAccessMediaTest`: matriks paket/status/waktu, status konten, isolasi akun, aktivasi/checkout ditolak, signature/tamper/expiry/revoke/wrong-user, manifest, upload privat, traversal/symlink, migrasi dry run/apply/repeat/conflict, serta metadata video/validasi YouTube.
- Dua tes normalisasi gender existing diperbarui agar memakai identitas autentikasi sesuai B02, tanpa payload `user_id`. Penolakan pemilih akun tetap dicakup `LmsBoundaryTest`.
- Mobile fungsional: **35 tes lulus**, termasuk **7 tes baru** `lms_access_test.dart` (server grant, kegagalan/expired, race logout/urutan request, checkout tanpa grant, same-origin/header, dan widget tanpa fallback). Tes lama pengharapan grant lokal/HS 90%/offline URL disesuaikan terhadap perilaku yang benar.
- `flutter analyze --no-pub --no-fatal-infos`: exit 0, tanpa warning/error; hanya 4 info lama pada `analytics_tracker.dart`. Tidak ada lint baru.
- PHPStan pada 5 layanan dan 2 controller media baru/diubah: 0 error, memory limit 512 MB. Ini bukan klaim seluruh proyek PHPStan bersih.
- Pint dirty dan `git diff --check` kedua repo lulus.
- Suite API luas yang dijalankan sebelum penambahan tes video terakhir: **178/179 lulus**, satu gagal sponsor URL relatif versus absolut (`GapClosingFeatureTest::sponsors_index_includes_badge_metadata`), sama dengan baseline yang sudah dibuktikan pada B02. Tes sponsor tidak diubah. Dua golden sponsor yang sudah gagal di baseline B02 tidak diulang karena scope B03 tidak mengubah sponsor.
- Tidak ada build native, walkthrough Android/iOS, staging, payment provider nyata atau uji embedded video langsung. Graphify belum dibangun ulang setelah perubahan source; source/test dan laporan ini menjadi acuan status terbaru.

## Kelanjutan

LMS-05/06 tetap terbuka pada checklist: keputusan premium/durasi, enrollment canonical, provider/publish gate, uji Android/iOS dan migrasi data nyata belum selesai. **Batch kode berikut B04: LMS-07/08/09 (authoring admin dan preview)** dapat dikerjakan pada fixture lokal dengan gate di atas tetap tercatat. Pilot utuh baru dinilai sesudah B05–B07. Pembayaran tetap tertutup sampai LMS-30 selesai; HS/sertifikat menunggu B06/B09 dan kebijakan terkait.
