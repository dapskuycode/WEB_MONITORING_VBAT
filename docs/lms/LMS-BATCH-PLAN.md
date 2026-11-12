# Rencana batch LMS VBAT

Tanggal: 10 November 2026, WIB. Status: usulan pembagian kerja; setiap batch menghasilkan artefak atau perubahan yang dapat ditinjau dan bukti verifikasinya. ID mengacu `LMS-TODO.md`.

Satu batch tidak berarti seluruh milestone siap dirilis. Sebelum setiap batch: fetch dua repo, cek branch acuan dan perubahan lokal, periksa dependensi, lalu catat cakupan. Backend dan mobile memiliki branch/commit/tes masing-masing; kompatibilitas lintas repo diverifikasi sebelum merge/rilis.

## B00: keputusan produk

- LMS-01. Hasil: dokumen keputusan dan contoh akses/kelulusan.
- Status: usulan tersedia; review K1–K6 belum selesai. Tidak dianggap disetujui hanya karena batch teknis berikutnya dimulai.

## B01: data dan kontrak integrasi

- LMS-02. Hasil: pemetaan existing, rancangan migrasi, batas API, contoh request/response dan acceptance.
- Status: draft teknis tersedia. Belum ada source aplikasi atau migrasi yang diterapkan.
- Selesai final setelah konflik sumber dan keputusan produk untuk scope terpilih sudah diselesaikan serta kontrak diterima sebagai acuan.

## B02: identitas dan otorisasi

Status 10 November 2026: implementasi lokal dan tes scope batch tersedia. Laporan `LMS-B02-HASIL.md` / salinan repo `LMS-B02-HASIL.md`. Backend terkait 48 tes/200 assertions dan mobile fungsional 30 tes lulus; satu kegagalan sponsor backend dan dua golden sponsor mobile direproduksi di baseline. Belum diuji perangkat/staging atau dirilis. Akses media/entitlement dilanjutkan B03, bukan dianggap sudah selesai.

- LMS-03, LMS-04. Batasi pada login/sesi tepercaya, penghapusan sukses demo di jalur produksi, policy resource dan pemisahan serializer student/admin.
- Bukti: login gagal tetap gagal, token invalid ditolak, student tidak menulis konten, akses lintas akun ditolak, kunci soal tidak bocor dari route lama/baru.
- Prasyarat: kontrak identitas/role B01; aturan bisnis yang tidak terkait dapat tetap terbuka. Jika scope terlalu besar, B02a identitas dan B02b policy dirilis sebagai perubahan terpisah.

## B03: entitlement dan media

Status 10 November 2026: fondasi akses dan media sudah diimplementasikan lokal; laporan `LMS-B03-HASIL.md` / `LMS-B03-HASIL.md`. Backend terkait 65 tes/325 assertions dan mobile 35 tes lulus. LMS-05/06 belum selesai penuh: keputusan produk/enrollment, gate publish/provider, Android/iOS dan migrasi PDF nyata masih terbuka. Pembelian baru ditutup sampai LMS-30. Batch kode berikut B04 pada fixture lokal.

- LMS-05, LMS-06. Seragamkan hak akses dan masa berlaku; hentikan aktivasi publik; buktikan provider/player dan jalur file privat.
- Bukti: paket/expired/revoked/prerequisite konsisten; URI publik tidak menghindari policy. Prasyarat B02 serta K2/K3/K4 sesuai scope.

## B04: authoring admin dan preview

Status 10 November 2026: LMS-08/09 terimplementasi dan teruji lokal; fondasi LMS-07 tersedia, audit data/revisi belajar tetap terbuka. Laporan `LMS-B04-HASIL.md` / `LMS-B04-HASIL.md`. 83 tes terkait/471 assertions lulus; API luas 195/196 (sponsor baseline). CMS/preview diperiksa di browser 390/820/1280 px, terang/gelap. Migrasi baru belum diterapkan ke DB utama. Batch berikut B05 (LMS-10/11).

- LMS-07, LMS-08, LMS-09. Perbaiki CMS terhadap model canonical, urutan/status/publish/revision, preview siswa terpisah.
- Bukti: satu kelas disiapkan lewat UI, draft tersembunyi, validasi publish dan preview tidak mengubah progres siswa. Prasyarat B01–B03.

## B05: kelas dan silabus siswa

Status 10 November 2026: API daftar/detail dan Flutter repository/Cubit/route ber-ID teruji lokal. Laporan `LMS-B05-HASIL.md` / `LMS-B05-HASIL.md`. 88 tes backend/544 assertions dan 41 tes Flutter lulus. Progres terverifikasi/next_unit masih null menunggu B06; belum deploy atau uji native/staging.

- LMS-10, LMS-11. Sambungkan kelas milik akun, silabus, access state dan route ber-ID ke repository/Cubit mobile.
- Bukti: empty/error/locked nyata dan kelas sesuai akun; tidak memakai fallback contoh. Prasyarat B02–B04.

## B06: video dan progres

Status 10 November 2026: LMS-12/13 terimplementasi lokal. Ambang video ≥95% disetujui pengguna. Sesi/event idempotent, cakupan unik, resume, prerequisite dan player/pending sync teruji: 96 tes backend/705 assertions dan 49 tes Flutter lulus. Laporan `LMS-B06-HASIL.md` / `LMS-B06-HASIL.md`. Migrasi DB utama, API key/provider dan uji native/staging belum dilakukan. Batch berikut B07; kebijakan kuis tetap terbuka.

- LMS-12, LMS-13. Backend event/session/completion, player terautentikasi, resume dan pending sync.
- Bukti: seek tidak menuntaskan, replay aman, progres lintas login/perangkat dan prerequisite server. Prasyarat B03–B05 dan aturan K4.

## B07: kuis pilihan ganda dan pilot utuh

Status B07: LMS-14/15 terimplementasi lokal; LMS-16 baru terbukti melalui fixture backend dan kontrak/widget Flutter, belum pada perangkat/staging. 107 tes backend/864 assertions dan 62 tes Flutter lulus. Laporan `LMS-B07-HASIL.md` / `LMS-B07-HASIL.md`. Default testing mengikuti arahan pengguna: lulus 70, tanpa batas waktu/percobaan, nilai tertinggi; aturan produksi belum difinalkan. Migrasi DB utama dan scheduler deployment belum diterapkan. Batch kode berikut B08; gate pilot M1 tetap terbuka.

- LMS-14, LMS-15, LMS-16. Snapshot attempt, autosave/deadline, grading server dan alur mobile.
- Bukti: satu kelas → dua video → satu kuis → hasil/progres tersimpan, termasuk kasus gagal/akun tanpa akses. Prasyarat B04–B06.

## B08: bacaan dan import Excel

Status 11 November 2026: LMS-18/19 terimplementasi lokal; LMS-17 sebagian (reader/pipeline lokal siap diuji, object storage cloud, scanner nyata dan aplikasi target belum diverifikasi). 119 tes backend/971 assertions dan 71 tes Flutter lulus. PDFium host berhasil merender fixture dua halaman melalui harness Dart. Laporan `LMS-B08-HASIL.md` / `LMS-B08-HASIL.md`. Migrasi utama belum diterapkan; PDF legacy perlu hasil scan sebelum disajikan. Batch kode berikut B09 dengan keputusan grading/HS yang masih terbuka.

- LMS-17, LMS-18, LMS-19. Unggah privat/viewer, template/preview seluruh baris, apply aman dan Draft.
- Bukti: error baris akhir terdeteksi, apply idempotent dan file privat tidak bocor. Prasyarat B03–B07 serta K5.

## B09: penilaian kuis lanjutan dan hasil belajar

Status 11 November 2026: implementasi lokal LMS-20, bagian hasil/HS/sertifikat LMS-21 dan review kuis LMS-24 tersedia. Aturan HS FSD disetujui pengguna untuk pilot. 124 tes backend/1055 assertions dan 79 tes Flutter lulus; bukti browser CMS dan PDF fixture tersedia. Laporan `LMS-B09-HASIL.md` / `LMS-B09-HASIL.md`. Belum migrasi utama/push/rilis; uji target/staging dan keputusan komersial tetap terbuka. Berikutnya B10.

- LMS-24 bagian review kuis lebih dahulu; kemudian LMS-20 dan LMS-21. Tiga jenis soal, finalisasi nilai manual/hybrid, dashboard, HS dan sertifikat.
- Bukti: nilai draft belum membuka prerequisite; kelulusan/HS/dokumen sesuai kelas/paket. Prasyarat B07–B08 dan K3–K5. Review manual tidak menunggu fitur tugas selesai.

## B10: tugas, gradebook dan instructor

- LMS-22, LMS-23, sisa LMS-24 untuk tugas, LMS-25, LMS-26 bila diaktifkan.
- Bukti: draft/submit/receipt/revisi/versioning, nilai published, scope pengajar dan lintas akun. Prasyarat B08–B09 serta K1/K6.

## B11: offline, notifikasi dan operasional

- LMS-27, LMS-28, LMS-29. Provider offline yang mendukung, lisensi/sync, notifikasi nyata, audit/monitoring/dokumentasi.
- Bukti: file benar-benar diunduh, akses tidak meluas, reject sync jelas, dispatch nyata, queue/scheduler teruji. Prasyarat fitur yang dipakai dari B03–B10 dan K6.
- Log request dan audit dasar dikerjakan sejak batch mutasi terkait; B11 bukan alasan menunda audit sampai akhir.

## BR: gate rilis, dijalankan sesuai scope dan berulang

- LMS-30 sebelum transaksi kelas nyata; LMS-31 sebelum rilis aplikasi dengan endpoint terkait; LMS-32 pada setiap milestone yang hendak dirilis.
- Mulai persiapan paralel saat kontrak relevan tersedia. Gate ini tidak menunggu semua B00–B11 selesai jika rilis hanya mencakup sebagian fitur.
- Bukti: transaksi sandbox/refund bila pembayaran aktif, konfigurasi release/TLS, route lama ikut aman, tes kontrak dan walkthrough staging/perangkat.

## Aturan menutup batch

- Bedakan: draft siap review, implementasi selesai lokal, tes lulus, diterima reviewer, dan dirilis.
- Catat keputusan/commit backend dan mobile, pemeriksaan yang dijalankan, hasil, batas verifikasi dan item tersisa.
- Jangan centang item TODO penuh jika hanya satu subbagian selesai. Jangan mengganti keputusan terbuka dengan default tersembunyi.
- Setelah hasil satu batch ditinjau, lanjut ke batch berikut yang dependensinya terpenuhi. Hindari memperluas perubahan ke toko/sponsor kecuali menjadi dependensi atau gate keamanan scope tersebut.
