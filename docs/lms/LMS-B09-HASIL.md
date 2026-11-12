# Hasil B09: penilaian lanjutan dan hasil belajar

11 November 2026, WIB. Implementasi dan verifikasi lokal. Belum commit, push, PR, deploy atau migrasi database utama.

## Keputusan dan acuan

Pengguna menyetujui aturan FSD untuk pilot: HS kelas terkait membutuhkan seluruh aktivitas wajib selesai 100%, paket kelas aktif dan premium aktif. Studi kasus menunggu review manusia. Ini tidak menyelesaikan keputusan komersial, refund, migrasi kurikulum atau role instructor pada K3–K6.

Kedua remote sudah di-fetch sebelum B09. Web tetap `origin/main` pada `5aebf5f`, mobile `origin/haidarreview` pada `02c5e0c`, masing-masing 0/0 terhadap HEAD. Perubahan batch sebelumnya dipertahankan lokal pada `codex/lms-foundation`.

## Perubahan

- CMS/API mendukung MCQ, jawaban singkat berbobot dengan kelompok alias, dan studi kasus dengan rubrik. Normalisasi jawaban singkat memakai Unicode NFC, huruf kecil serta kata/frasa utuh; setiap kelompok hanya dihitung sekali. Studi kasus tidak diluluskan dengan keyword.
- Mode auto/manual/hybrid, acak soal/opsi, posisi setelah materi/akhir modul/akhir kelas, wajib/opsional dan nilai tertinggi/terakhir/rata-rata tersedia. Pengacakan dilakukan sekali pada snapshot attempt; perubahan soal tidak mengganti snapshot lama. Default testing B07 tetap 70, tanpa batas waktu/percobaan, nilai tertinggi.
- Admin memiliki antrean review `/admin/lms/reviews`, rubrik, feedback, alasan revisi, draft/publish, histori dan pemeriksaan versi. Draft revisi mempertahankan hasil published sebelumnya. Hanya nilai published dihitung untuk kelulusan; studi kasus baru tetap `pending_review`. Pengajar dan penilaian tugas dilanjutkan B10.
- Dashboard, riwayat, dokumen dan HS Flutter memakai API akun. Skor desimal dan pending review didukung; kunci/alias/rubrik privat tidak masuk DTO siswa. Halaman HS menampilkan kelas HS terbit yang dipetakan admin ke prasyarat Android/iPhone; daftar model ponsel contoh dihapus dari jalur ini.
- Sertifikat kelulusan diterbitkan satu kali per akun/kelas. Nomor unik, nama/kelas/tanggal serta completion disimpan sebagai snapshot. PDF nyata dibuat dengan Dompdf dan disimpan privat. Nama saat ini tidak mengganti identitas dokumen lama.
- Dokumen privat secara default. Pemilik dapat mengizinkan verifikasi publik dengan token acak; QR mengikuti URL verifikasi sebenarnya. PDF hanya diambil pemilik melalui endpoint autentikasi, tanpa mengirim bearer ke URL luar. Riwayat dan dokumen tetap tersedia setelah premium/paket expired. Revokasi admin beralasan tercatat; premium expired tidak mencabut sertifikat.
- Sertifikat kelulusan dibedakan dari KTA membership. Dokumen KTA existing dapat didaftar, tetapi penerbitan KTA/pembelian baru tetap menunggu LMS-30. Tidak membuat KTA contoh atau menyatakan premium sebagai bukti kelulusan.

## Endpoint baru

Semua path berikut relatif terhadap `/api/v1`; identitas berasal dari sesi/token.

- `GET /admin/lms/reviews`, `GET /admin/lms/reviews/{attempt}`, `POST .../{attempt}/draft`, `POST .../{attempt}/publish`: hanya admin. Mutasi memakai UUID request, expected grade version dan alasan; retry idempotent dan konflik 409.
- `GET /lms/dashboard`, `/lms/history`, `/lms/credentials`: pagination 20, scope akun sendiri.
- `GET /lms/courses/{course}/outcome`, `POST /lms/courses/{course}/certificate`.
- `POST /lms/credentials/{id}/visibility`, `GET /lms/credentials/{id}/document`: pemilik.
- `GET /lms/credentials/verify/{token}`: publik hanya jika pemilik mengizinkan.
- `POST /admin/lms/credentials/{id}/revoke`: admin dengan alasan.

## Bukti verifikasi

- **124 tes backend / 1055 assertions lulus**, mencakup auth, boundary, akses/media, authoring, playback, silabus, attempt, dokumen/import serta grading/outcome. Tiga belas tes B09 mencakup batas skor/rubrik, draft tidak bocor, published-only, snapshot/shuffle, alias NFC/angka nol, average, posisi kuis, gate HS, sertifikat, ownership, riwayat dan form Livewire.
- **79 tes Flutter lulus**: regresi LMS, jawaban teks/pending, scope hasil akun, logout saat response tertunda, URL verifikasi asing, mutasi penerbitan/privasi, widget dan reflow. Screenshot empat layar pada terang/gelap dan ukuran 390; pemeriksaan layout juga 820 dengan teks 200%. Ini tes fixture, bukan aplikasi native yang terhubung ke staging.
- PHPStan scoped pada 10 layanan grading/outcome/placement/progress/authoring/attempt serta dua controller baru: 0 error. Bukan klaim PHPStan seluruh proyek bersih. Flutter analyzer: tanpa error/warning, empat info existing `analytics_tracker`.
- Pint, whitespace diff dan build aset web lulus. Browser lokal dengan SQLite fixture terpisah membuktikan buka review, simpan draft dan publish: versi 1 → 2 pending → 3 published. Editor soal diperiksa dan field opsi/alias diberi label.
- PDF fixture satu halaman dirender ke PNG dan diperiksa: nama beraksen, nomor, URL dan teks terbaca. Dokumen bertanda nama fixture, tanpa tanda tangan atau akreditasi rekaan. Bukti di `docs/lms/screenshots/B09-*` pada kedua repo; gate tampilan di `LMS-B09-UI-CHECK.md`.

## Batas dan langkah berikut

1. Migrasi `2026_11_11_100000_add_lms_grading_and_outcomes.php` baru diuji pada database tes/fixture. Migrasi juga membuat revisi published awal untuk attempt trusted lama. Lakukan backup dan rehearsal staging sebelum database nyata; record attempt legacy tetap tidak menjadi kelulusan tepercaya.
2. Mapping prasyarat HS wajib ditetapkan admin; HS tanpa mapping tertutup. Paket/premium bukan enrollment/pembayaran otomatis. Gate mengacu kelas yang dipetakan dan revisi/sumber aktif. Pengeditan kurikulum produksi serta perubahan aturan agregasi memerlukan kebijakan K5.
3. Sertifikat merekam kelulusan saat terbit. Revisi nilai berikutnya dapat menutup progres/HS, tetapi pencabutan dokumen memakai aksi admin tersendiri. Koreksi identitas/replacement dan refund belum dijadikan kebijakan otomatis.
4. Dompdf ditambah melalui Composer. Audit menemukan 14 advisory pada delapan paket existing (league/commonmark, Livewire dan enam komponen Symfony); tidak pada Dompdf. Pembenahan dependency menjadi gate sebelum rilis, bukan dinyatakan audit bersih. Jangan mengaktifkan remote asset/PHP/JavaScript pada renderer.
5. Konfigurasi URL aplikasi/proxy harus sesuai host API yang diakses mobile agar tautan verifikasi dapat digunakan. Domain produksi, antrean/scheduler dan private disk perlu diuji pada lingkungan target.
6. Uji terpadu Android/iOS, provider nyata dan staging belum dilakukan. Scanner ClamAV nyata, object storage cloud dan EPUB dari B08 masih terbuka. Graphify belum diperbarui sejak batch fondasi.
7. Batch berikut **B10**: tugas/submission, sisa review tugas, gradebook dan instructor bila disetujui. B09 bukan pernyataan seluruh LMS-21/24 atau milestone M2 siap rilis.
