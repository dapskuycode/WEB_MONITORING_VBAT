# TODO implementasi LMS VBAT Ponsel

Dibuat: 9 November 2026; diperbarui: 12 November 2026, WIB. Status: implementasi bertahap; B02–B10 teruji lokal, belum rilis.

Tujuan: admin mengelola dan menerbitkan kelas melalui panel Laravel; siswa belajar melalui Flutter dengan hak akses, progres, kuis, dan hasil yang ditentukan server.

## Sumber dan cara menggunakan

- **FSD**: [FSD_LMS_VBatPonsel.md](</Users/mdaffaatstsaqif/Raphael/Raphael/40 - Projects/VBAT/FSD_LMS_VBatPonsel.md>).
- **WF**: [FSD_LMS_Mobile_Wireframes.md](</Users/mdaffaatstsaqif/Raphael/Raphael/40 - Projects/VBAT/FSD_LMS_Mobile_Wireframes.md>).
- **INV**: [01 - Inventaris Masalah.md](</Users/mdaffaatstsaqif/Raphael/Raphael/40 - Projects/VBAT/01 - Inventaris Masalah.md>); nomor INV mengikuti daftar temuan dokumen tersebut.
- **Konteks**: [00 - Gambaran Proyek.md](</Users/mdaffaatstsaqif/Raphael/Raphael/40 - Projects/VBAT/00 - Gambaran Proyek.md>).
- Acuan yang dipilih pengguna setelah fetch 9 November 2026: backend `VBAT-WEB/` dari `origin/main@5aebf5f`; mobile `vbat-ponsel-main/` dari `origin/haidarreview@02c5e0c`. Branch kerja masing-masing `codex/lms-foundation`. Periksa kembali remote sebelum implementasi berikutnya.
- FSD adalah target, inventaris adalah temuan inspeksi, dan source code adalah bukti implementasi. Keberadaan endpoint atau layar tidak membuktikan alur sudah bekerja.
- Kotak yang belum dicentang tetap pekerjaan terbuka; centang implementasi lokal bukan bukti deployment. Centang hanya setelah kriteria selesai terbukti; catat commit, tes, dan bukti alur pada item terkait.
- **P0**: fondasi akses/integritas atau penghalang rilis. **P1**: fungsi utama. **P2**: perluasan/operasional. Prioritas berbeda dari urutan milestone; P2 yang menjadi requirement tetap harus selesai sebelum milestone terkait dirilis.
- Tahapan di bawah adalah rekomendasi pengerjaan, bukan persetujuan menghapus atau mengurangi requirement. Keputusan yang belum disepakati tetap terbuka pada LMS-01/LMS-02.

## Pelaksanaan per batch

Rincian pembagian dan dependensi tersedia di [LMS-BATCH-PLAN.md](LMS-BATCH-PLAN.md). B00/LMS-01 sudah memiliki usulan keputusan; B01/LMS-02 sudah memiliki draft pemetaan, rancangan migrasi dan contoh kontrak. Keduanya belum final karena review keputusan terkait masih terbuka. B02 sudah memiliki implementasi lokal identitas/otorisasi beserta hasil tes di [LMS-B02-HASIL.md](LMS-B02-HASIL.md). B03 sudah memiliki fondasi lokal entitlement/media beserta tes di [LMS-B03-HASIL.md](LMS-B03-HASIL.md); LMS-05/LMS-06 tetap terbuka untuk kebijakan, enrollment, provider/publish, perangkat dan migrasi data. B04 sudah memiliki CMS/preview dan fondasi schema/revisi teruji di [LMS-B04-HASIL.md](LMS-B04-HASIL.md). B05 telah menyambungkan kelas/silabus melalui API dan Flutter; bukti di [LMS-B05-HASIL.md](LMS-B05-HASIL.md). B06–B10 telah dikerjakan lokal; laporan terbaru [LMS-B10-HASIL.md](LMS-B10-HASIL.md). Berikutnya B11: offline, notifikasi nyata, dan operasional; gate perangkat/staging tetap terbuka. Tidak ada kode aplikasi yang diubah pada B00/B01; perubahan kode dimulai di B02.

## Batas milestone

- **M0, fondasi**: keputusan produk, kontrak existing, identitas, otorisasi, dan model hak akses selaras.
- **M1, pilot terintegrasi**: satu kelas, satu modul, dua video, satu kuis pilihan ganda; admin membuat sampai menerbitkan, siswa belajar sampai mendapat hasil. Gunakan akun dan konten uji; ini belum rilis komersial.
- **M2, LMS inti**: pengelolaan konten lengkap, PDF/e-book, import Excel, tiga posisi kuis, jenis soal lanjutan, progres/riwayat, HS dan sertifikat sesuai kebijakan yang disepakati.
- **M3, tugas dan pengajar**: submission, penilaian manual, gradebook, revisi, dan role instructor bila diaktifkan.
- **M4, offline dan penguatan**: media offline yang sumbernya mendukung, sinkronisasi, notifikasi dan operasional lengkap.
- **R, gate rilis nyata**: pembayaran, akses konten, konfigurasi rilis, dan permukaan API bersama diperiksa untuk fitur yang benar-benar diaktifkan. Gate berlaku sebelum rilis terkait, bukan larangan mulai mengembangkan M1.

## M0: putuskan kontrak dan benahi fondasi

### LMS-01 | P0 | Keputusan produk dan batas rilis

**Status 9 November 2026:** usulan keputusan dan 18 skenario review tersedia di [LMS-01-KEPUTUSAN-PRODUK.md](LMS-01-KEPUTUSAN-PRODUK.md). Menunggu review K1–K6; checklist belum dicentang. Salinan identik tersedia di `docs/lms/LMS-01-KEPUTUSAN-PRODUK.md` pada kedua repo.

- [ ] Tetapkan admin web sebagai pengelola awal; putuskan kapan instructor dan tugas aktif, sumber video awal, serta requirement yang wajib pada rilis pertama.
- Kunci aturan akses kelas Android/iPhone/bundling, premium, preview, masa akses, syarat HS, sertifikat/KTA, dan akses setelah premium berakhir. Pisahkan hak belajar dari status kelulusan.
- Kunci ambang penyelesaian video, urutan wajib/opsional, nilai lulus, retake, pemilihan nilai tertinggi/terakhir/rata-rata, batas upload, retensi data, dan perilaku perubahan konten setelah siswa belajar.
- **Selesai bila:** tersedia keputusan tertulis beserta contoh siswa berhak/tidak berhak dan lulus/belum lulus; perbedaan ambang lokal 90% versus target kelulusan terselesaikan. Usulan default FSD tidak dianggap keputusan final.
- **Rujukan:** FSD bagian 6, 8, 9; WF bagian 6, 9, 10; INV 7, 17. **Dependensi:** tidak ada.

### LMS-02 | P0 | Petakan skema dan kontrak backend existing

**Status 10 November 2026:** draft teknis B01 tersedia di [LMS-02-KONTRAK-INTEGRASI.md](LMS-02-KONTRAK-INTEGRASI.md), dengan `lms-contract.examples.json` (28 route planned dan 9 contoh pertukaran fixture) serta `lms-existing-routes.json` (39 registrasi route existing). Usulan namespace dan keputusan terkait LMS-01 belum final; checklist belum dicentang. Salinan tersedia pada kedua repo.

- [ ] Tetapkan pemetaan `Course → kelas`, `Lesson → modul`, `LearningMaterial → materi`, termasuk posisi `Unit`, video, quiz, enrollment dan entitlement; telusuri model/field yang dirujuk CMS tetapi belum tersedia/selaras.
- Putuskan migrasi tambahan dan backfill yang mempertahankan ID/relasi/data existing. Jangan mengganti seluruh ID menjadi UUID hanya karena skema usulan FSD memakai UUID.
- Bekukan kontrak API LMS: endpoint, DTO, status akses, envelope, pagination, error, waktu UTC/WIB, request ID, idempotency dan concurrency. Tetapkan alias atau migrasi endpoint lama `/learning-materials` secara eksplisit.
- Selesaikan perbedaan FSD: admin versus instructor, nama/jenis materi, MCQ 2–6 versus 2–10 opsi, timer/attempt tanpa batas, serta import seluruhnya versus sebagian baris. Usulan awal import: seluruh perubahan batal jika ada error; keputusan akhir dicatat.
- **Selesai bila:** ada pemetaan field dan rencana migrasi, contoh request/response, strategi kompatibilitas, dan skenario tes admin → API → mobile yang menggunakan kontrak yang sama.
- **Rujukan:** FSD bagian 1, 3, 4, 7, 10; INV 13, 33. **Dependensi:** LMS-01.

### LMS-03 | P0 | Satukan autentikasi dan hilangkan sukses palsu

**Status B02, 10 November 2026:** implementasi lokal dan tes identitas/role/penyaringan respons tersedia; lihat [LMS-B02-HASIL.md](LMS-B02-HASIL.md). Backend terkait 48 tes/200 assertions dan mobile fungsional 30 tes lulus. Verifikasi perangkat/staging serta penuntasan akses media/entitlement lintas batch masih terbuka; checklist penuh belum dicentang.

- [ ] Gunakan identitas dari token server; satukan jalur login repository dan SessionManager, validasi sesi melalui `/auth/me`, serta gunakan penyimpanan token aman platform.
- Pisahkan demo dari build produksi; API gagal tidak boleh membuat token/sesi atau menyatakan berhasil. Verifikasi credential OAuth di server, atau nonaktifkan jalur OAuth sampai benar.
- Tangani 401, logout/revoke, token invalid, dan login ulang tanpa membocorkan draft atau cache antar akun. Benahi pembaruan profil/demografi yang bisa memilih akun dari request publik.
- **Selesai bila:** login gagal tetap gagal; token invalid tidak membuka LMS; akun A/B tidak bertukar cache; pengubahan email/user_id request tidak mengalihkan identitas operasi.
- **Rujukan:** FSD bagian 1.1, 2.3, 10; INV 2, 3, 4, 12. **Dependensi:** LMS-02.

### LMS-04 | P0 | Policies admin, student dan kepemilikan resource

**Status B02, 10 November 2026:** implementasi lokal dan tes identitas/role/penyaringan respons tersedia; lihat [LMS-B02-HASIL.md](LMS-B02-HASIL.md). Backend terkait 48 tes/200 assertions dan mobile fungsional 30 tes lulus. Verifikasi perangkat/staging serta penuntasan akses media/entitlement lintas batch masih terbuka; checklist penuh belum dicentang.

- [ ] Lindungi setiap operasi LMS di API maupun Livewire: mutasi/publikasi hanya pengelola berwenang; student hanya membaca/mengubah resource belajarnya sendiri.
- Pisahkan katalog/preview publik dari isi terlindungi. Gunakan serializer student yang tidak pernah menyertakan answer key, keyword/rubrik internal atau object key privat.
- **Selesai bila:** tes guest, student A, student B, admin, dan role lain menolak akses silang; menyembunyikan tombol di Flutter bukan satu-satunya pembatas.
- **Rujukan:** FSD bagian 1.3, 8; INV 8, 9. **Dependensi:** LMS-03.

### LMS-05 | P0 | Satu sumber enrollment dan entitlement

**Status B03:** layanan akses memakai schema existing dan snapshot server; aktivasi publik/grant lokal ditutup; waktu/status/paket teruji. Pembelian baru ditutup menunggu LMS-30. Enrollment dan keputusan premium/durasi tetap terbuka; belum dicentang. Lihat [LMS-B03-HASIL.md](LMS-B03-HASIL.md).

- [ ] Selaraskan EntitlementService dengan model/migrasi yang memakai `package_type`, `status`, dan masa akses; tentukan relasi enrollment sesuai kontrak LMS-02.
- Tutup aktivasi membership publik yang dapat memberi akses dari email/user_id/fallback. Grant hanya melalui transaksi tervalidasi atau tindakan admin yang berotorisasi dan diaudit.
- Backend mengembalikan akses per kelas/materi serta alasan terkunci. Hapus hak akses produksi yang bergantung pada Dev controls dan flag lokal.
- **Selesai bila:** free/preview, Android, iPhone, bundling, premium, expired/revoked dan tanggal mulai di masa depan mengikuti keputusan LMS-01; semua jalur akses memberi keputusan yang sama.
- **Rujukan:** FSD bagian 1.1, 3.2, 8; INV 1, 7, 17. **Dependensi:** LMS-01, LMS-02, LMS-04.

### LMS-06 | P0 | Sumber video dan akses media

**Status B03:** upload privat, URL signed terikat akun, pemeriksaan ulang izin, proxy canonical dan perintah migrasi PDF tersedia/teruji pada fixture. Player beralih ke YouTube IFrame tanpa ekstraksi stream/fallback; offline URL palsu ditutup. Belum uji Android/iOS, gate publish embed, provider final atau migrasi data nyata; belum dicentang.

- [ ] Verifikasi pemutaran sumber video yang dipilih pada Android/iOS. Bedakan YouTube private dari unlisted; jangan menganggap video unlisted terlindungi oleh entitlement VBAT.
- Jika sumber awal YouTube: pakai player resmi, uji embed/error/progres, dokumentasikan keterbatasan tautan, branding, kontrol seek dan offline. Jangan menjanjikan proteksi penuh atau download YouTube sebagai fitur VBAT.
- Jika kontrol akses ketat/offline menjadi syarat rilis: pilih jalur media privat sebelum implementasi player final. PDF/file privat harus memakai otorisasi dan akses terbatas, bukan URL publik permanen.
- Batasi canonical path pada proxy berkas yang dipakai LMS; uji menggunakan fixture aman. Secure mode layar merupakan lapisan tambahan, bukan bukti konten mustahil disalin.
- **Selesai bila:** kemampuan sumber media sesuai requirement yang disepakati; pengguna tanpa akses tidak memperoleh file privat; video gagal embed menahan publish; batasan provider dicatat sebagai batas produk.
- **Rujukan:** FSD bagian 5.6, 8; WF bagian 3 dan Video YouTube; INV 6, 9, 19, 32. **Dependensi:** LMS-01, LMS-04, LMS-05.

## M1: satu kelas bekerja dari admin sampai hasil siswa

### LMS-07 | P1 | Struktur kelas, modul dan materi

**Status B04:** migrasi kompatibel code/revision/status modul/is_preview, layanan canonical, urutan per parent dan arsip yang mempertahankan riwayat tersedia. Audit/backfill data existing dan kebijakan snapshot/revisi belajar masih terbuka; checklist keseluruhan belum dicentang. Lihat [LMS-B04-HASIL.md](LMS-B04-HASIL.md).

- [ ] Implementasikan migrasi tambahan, relasi, kode unik, urutan, required/preview serta draft/published/archived sesuai LMS-02. Tetapkan validasi publikasi dan perilaku arsip tanpa menghapus riwayat belajar.
- **Selesai bila:** dua modul/materi tidak berbagi urutan yang dilarang; draft tidak muncul ke student; arsip dan perubahan konten mempertahankan data belajar sesuai kebijakan.
- **Rujukan:** FSD bagian 3.1–3.2, 4 Course/modul/materi, 5.1–5.4; INV 13. **Dependensi:** LMS-02, LMS-04, LMS-05.

### LMS-08 | P1 | Manajemen LMS pada panel web

**Status B04:** selesai implementasi lokal; layanan bersama API/CMS, publish gate, audit dan konflik revisi teruji. Pilot video diuji dengan HTTP fixture; browser menerbitkan bacaan/modul/kelas pada DB terpisah. Key/provider nyata dan staging belum diverifikasi.

- [x] Gunakan/perbaiki CMS existing untuk daftar kelas, filter/pencarian, cover, pembuatan/edit kelas/modul, penambahan video, pengurutan, penyimpanan Draft dan publish/archive. Tambahkan audit perubahan penting.
- **Selesai bila:** admin bisa menyiapkan kelas pilot dari UI; gagal simpan tidak menampilkan sukses; konten tidak valid tidak bisa diterbitkan; API dan CMS menyimpan struktur yang sama.
- **Rujukan:** FSD bagian 5.1–5.4; WF bagian 9–10; INV 13, 32. **Dependensi:** LMS-06, LMS-07.

### LMS-09 | P1 | Pratinjau sebagai siswa

**Status B04:** selesai implementasi lokal untuk preview admin baca saja dan simulasi siswa tanpa paket. Draft/media dapat ditinjau tanpa impersonasi, grant, progres atau attempt siswa; signature/role/expiry PDF teruji. Belum fitur preview publik atau simulasi akun siswa tertentu.

- [x] Buat preview yang terbatas untuk admin, menampilkan struktur, lock dan media dengan jelas; pisahkan dari sesi siswa nyata.
- **Selesai bila:** admin dapat menguji draft sebelum publish; preview tidak memberi hak ke akun lain, mencatat progres siswa, menghabiskan attempt siswa, atau menerbitkan konten secara tidak sengaja.
- **Rujukan:** FSD bagian 5.2, 6, 7; WF bagian 9. **Dependensi:** LMS-04, LMS-06, LMS-08.

### LMS-10 | P1 | API Kelas Saya, detail dan silabus

**Status B07:** daftar/detail/silabus, progres video dan kuis, agregat modul/kelas dan next_unit tersedia lokal. View/attempt legacy tidak dihitung sebagai completion. B08 menambahkan bacaan PDF dengan konfirmasi server. Uji aplikasi target/staging belum dilakukan.

- [x] Sajikan kelas milik akun, akses/masa berlaku, daftar modul/materi terbit, status per unit, ringkasan progres dan aktivitas berikutnya. Definisikan katalog versus kelas milik akun.
- **Selesai bila:** respons kosong tetap kosong; kelas akun lain tidak ikut muncul; materi terkunci punya alasan; data modul mengikuti urutan dan status server.
- **Rujukan:** FSD bagian 2.1, 4 Course/modul/materi, 7; WF bagian 1–2; INV 7, 14. **Dependensi:** LMS-05, LMS-07.

### LMS-11 | P1 | Integrasi kelas di Flutter

**Status B05:** alur utama terimplementasi lokal dengan DTO/repository/Cubit, pagination, state gagal/kosong/terkunci, isolasi sesi dan route ber-ID. Legacy link tanpa ID ditolak, bukan ditebak. Uji perangkat/staging dan migrasi consumer home/bookmark lama belum diklaim selesai; lihat laporan B05.

- [x] Sambungkan repository/Cubit daftar kelas, detail, silabus dan route ber-ID ke API melalui ApiClient. Pisahkan state dan request dari widget; pakai ulang fitur belajar yang relevan sebelum membuat struktur paralel `features/lms`.
- Hapus fallback contoh di jalur produksi; sediakan loading, empty, error/retry, locked, expired dan revoked. Satu kegagalan API tidak boleh membuka kelas atau menggantinya dengan demo.
- **Selesai bila:** akun berbeda menampilkan kelas berbeda; restart aplikasi tetap menampilkan data server; state gagal/kosong tidak menyerupai data belajar nyata.
- **Rujukan:** FSD bagian 1.2, 2.3, 10; WF bagian 1–2; INV 12, 14. **Dependensi:** LMS-03, LMS-10.

### LMS-12 | P0 | Progres belajar dan prerequisite di server

**Status B06:** implementasi lokal dan tes lulus; ambang video ≥95% disetujui pengguna. Video terverifikasi tersedia; B07 menambahkan completion kuis yang dinilai server. B08 menambahkan progres bacaan PDF; file wajib berstatus ready setelah scan. Migrasi utama dan uji provider/native/staging tetap terbuka. Lihat `LMS-B06-HASIL.md`.

- [x] Implementasikan sesi playback, heartbeat, event ID unik, resume position dan perhitungan completion sesuai LMS-01. Validasi akses, durasi, selang waktu dan event replay; seek ke akhir tidak otomatis menuntaskan video.
- Hitung progres materi/modul/kelas dan `next_unit`; kunci unit berikutnya berdasarkan hasil server. Telemetri menonton bukan jaminan perhatian atau pemahaman siswa.
- **Selesai bila:** event duplikat tidak menggandakan progres; lompatan waktu tidak membuka unit; pindah perangkat memuat progres akun; request siswa lain/akses berakhir ditolak.
- **Rujukan:** FSD bagian 4 Pelacakan progres, 8–9; WF bagian 2–3; INV 16, 17. **Dependensi:** LMS-05, LMS-06, LMS-07.

### LMS-13 | P1 | Player Flutter dan resume

**Status B06:** integrasi sesi/progres/resume dan retry identitas event tersedia; tes repository/tracker/widget lulus. Uji player YouTube di Android/iOS dengan video dan API key nyata belum dilakukan, sehingga gate perangkat belum selesai.

- [x] Hubungkan player dengan ID materi aktual, token ApiClient, event progres, posisi tersimpan, secure mode sesuai platform, serta konfirmasi server untuk selesai/lanjut.
- Tampilkan pending/retry ketika jaringan gagal; jangan diam-diam menganggap sinkronisasi berhasil. Tangani akses dicabut dan lifecycle aplikasi sesuai kemampuan media.
- **Selesai bila:** membuka ulang materi melanjutkan posisi yang benar; gagal kirim terlihat; materi tanpa akses tidak terus diputar dari cache grant yang tidak valid.
- **Rujukan:** FSD bagian 2.1, 2.3, 5.6; WF bagian 3; INV 9, 16. **Dependensi:** LMS-11, LMS-12.

### LMS-14 | P0 | Backend kuis dan attempt pilihan ganda

**Status B07:** terimplementasi lokal: format CMS/API canonical, snapshot, batas opsional, deadline server, autosave/version, idempotensi start/submit dan finalisasi scheduler. Default testing 70/tanpa batas waktu dan percobaan/nilai tertinggi; hasil legacy diabaikan. Lihat `LMS-B07-HASIL.md`.

- [x] Selaraskan editor admin dengan penilaian server untuk format opsi/jawaban benar. Tambahkan posisi kuis, required/pass score, timer/attempt limit, snapshot soal, deadline server, autosave, submit idempotent dan aturan nilai.
- Terapkan transaksi/concurrency untuk start dan submit; finalisasi attempt kedaluwarsa termasuk saat aplikasi ditutup; perubahan soal tidak mengubah attempt yang sudah berjalan.
- **Selesai bila:** siswa tidak menerima kunci; mengubah jam perangkat tidak memperpanjang waktu; start/submit ganda tidak membuat nilai ganda; satu soal MCQ dari CMS dinilai benar sesuai opsi yang dipilih.
- **Rujukan:** FSD bagian 4 Kuis/ujian, 5.7–5.9, 8; WF bagian 5 dan Pengaturan kuis; INV 8, 13, 15. **Dependensi:** LMS-04, LMS-05, LMS-07, LMS-12.

### LMS-15 | P1 | Quiz runner Flutter

**Status B07:** runner MCQ server, resume, timer, autosave, nomor soal, tanda ragu-ragu tersimpan, konfirmasi, hasil dan retry teruji lokal. Uji perangkat/staging tetap terbuka. Route legacy tanpa ID tidak menjalankan kuis contoh.

- [x] Ganti soal/kunci/penilaian lokal dengan instruksi dan attempt server; implementasikan mulai/lanjut attempt, timer, autosave, nomor soal, tandai ragu-ragu, konfirmasi submit dan hasil.
- Flush jawaban sebelum submit; tampilkan jawaban belum tersimpan, konflik, timeout dan retry. Hasil ditentukan server; jawaban tidak boleh tercampur antarakun/attempt.
- **Selesai bila:** restart tidak mereset deadline atau membuat attempt baru; hasil sama dengan server; jawaban tersimpan sebelum finalisasi dan kondisi gagal tidak ditampilkan sebagai lulus.
- **Rujukan:** FSD bagian 2.1, 4 Kuis/ujian; WF bagian 5–6; INV 15. **Dependensi:** LMS-11, LMS-14.

### LMS-16 | P0 | Bukti pilot end-to-end

**Status B07: sebagian.** Tes backend dua video melalui sesi playback → MCQ → kelas 100% dan kontrak/widget Flutter lulus. Soal buatan CMS juga dinilai melalui API. Pembuktian alur terpadu pada aplikasi target dengan provider nyata, admin menerbitkan lewat UI, login ulang/perangkat dan staging belum dilakukan; gate M1 tetap terbuka.

- [ ] Siapkan fixture satu kelas, satu modul, dua video dan satu MCQ; admin menerbitkan, siswa berhak menonton/menuntaskan/menjawab, siswa tanpa akses ditolak. Grant pilot melalui fixture/admin, bukan endpoint aktivasi publik.
- **Selesai bila:** tes backend dan kontrak mobile lulus; alur dibuktikan pada aplikasi target; login ulang mempertahankan progres/hasil; tidak ada fallback demo; hasil dan keterbatasan pengujian dicatat. Ini gate M1, bukan klaim siap produksi.
- **Rujukan:** FSD bagian 8; INV 7, 8, 13–17. **Dependensi:** LMS-08, LMS-09, LMS-13, LMS-15.

## M2: pengelolaan konten dan pembelajaran inti lengkap

### LMS-17 | P1 | Unggah privat dan viewer PDF/e-book

**Status B08: sebagian.** Pipeline karantina/scan/ready, owner/purpose/checksum, publish gate, reader PDF dan completion server terimplementasi serta teruji lokal. E-book pilot berbentuk PDF; EPUB belum didukung. Penyimpanan pilot masih disk privat lokal, bukan object storage cloud yang sudah dikonfigurasi. Scanner nyata, cloud dan uji Android/iOS/staging tetap terbuka. PDF legacy tanpa hasil scan diblokir sampai didaftarkan ulang melalui pipeline. Lihat `LMS-B08-HASIL.md`.

- [ ] Buat upload yang memeriksa pemilik, purpose, ukuran, MIME aktual, nama dan status scanning/quarantine/ready/rejected. Pakai object storage privat dan unduhan berotorisasi dengan masa berlaku terbatas.
- Hubungkan penambahan materi di admin dan viewer mobile ke berkas nyata; definisikan penyelesaian bacaan sesuai LMS-01; hilangkan URL dummy.
- **Selesai bila:** file belum siap tidak bisa dipublikasikan; file akun lain ditolak; akses langsung tanpa grant gagal; bacaan dan progres dapat dibuka ulang dengan benar.
- **Rujukan:** FSD bagian 4 Course/modul/materi, Alur unggah file; INV 9, 19. **Dependensi:** LMS-06, LMS-07, LMS-11, LMS-12.

### LMS-18 | P1 | Template dan pemeriksaan import Excel

**Status B08:** template XLSX/CSV memakai kode FSD; seluruh baris (maksimal 1000) diperiksa. Preview tersimpan terikat aktor/hash berkas/revisi kelas-modul/upload dan memiliki umur 30 menit. Mode hanya create untuk kelas/modul existing; duplikat ditolak, tanpa overwrite. Formula/macro/tautan eksternal dan berkas melewati batas ditolak. Laporan kesalahan XLSX tersedia.

- [x] Sediakan template kolom FSD 5.5, batas ukuran/sheet/header, kode/urutan unik, required/preview, referensi kuis, URL dan file yang sudah diunggah. Gunakan referensi file yang tidak ambigu saat nama file sama.
- Validasi seluruh berkas tanpa menulis konten; tampilkan nomor baris, field, masalah dan saran, serta unduhan laporan error. Periksa juga otorisasi dan file yang tidak tepercaya.
- **Selesai bila:** template valid menghasilkan preview yang benar; file invalid tidak mengubah database; referensi lintas kelas/sumber media invalid ditandai.
- **Rujukan:** FSD bagian 5.5, 7; WF bagian Import Excel. **Dependensi:** LMS-02, LMS-08, LMS-14, LMS-17.

### LMS-19 | P1 | Penerapan import dan publikasi

**Status B08:** apply memakai preview ID server dengan transaksi semua-atau-batal, freshness check, audit dan replay idempotent. Semua materi Draft; kuis baru nonaktif tanpa soal wajib dilengkapi manual. Halaman bulk upload/demo dan API lama diarahkan ke layanan canonical; tidak ada lagi auto-create kelas demo.

- [x] Implementasikan apply berotorisasi, batas umur preview, deteksi perubahan sejak preview, idempotency, transaksi, audit dan pilihan duplikat sesuai keputusan LMS-02. Semua hasil tetap Draft.
- **Selesai bila:** apply dua kali tidak menggandakan item; konflik tidak menimpa konten tanpa keputusan admin; gagal menerapkan tidak meninggalkan perubahan sebagian yang bertentangan dengan kebijakan; preview student dan publikasi manual tetap wajib.
- **Rujukan:** FSD bagian 5.5–6; WF bagian Import Excel. **Dependensi:** LMS-09, LMS-18.

### LMS-20 | P1 | Jawaban singkat, studi kasus dan aturan kuis lengkap

- [x] Implementasi lokal: tambahkan model/editor/DTO tiga jenis soal, alias jawaban, rubric/bobot, grading auto/manual/hybrid, pengacakan soal/opsi, tiga posisi kuis dan aturan pengambilan nilai.
- Jangan memakai keyword sebagai satu-satunya bukti kualitas studi kasus; sediakan review/override berotorisasi sesuai mode dan audit. Pisahkan hasil sementara, menunggu penilaian, dan nilai published.
- **Selesai bila:** semua jenis soal bekerja dari admin sampai siswa; revisi tidak mengubah snapshot lama; nilai draft/kunci tidak bocor; hasil manual tidak membuka prerequisite sebelum diterbitkan.
**Status B09:** tiga jenis soal, editor alias/rubrik, mode penilaian, snapshot/pengacakan, tiga posisi dan agregasi nilai terimplementasi serta teruji lokal. Studi kasus wajib review manusia; draft tidak membuka prerequisite. Uji native/staging tetap terbuka. Lihat `LMS-B09-HASIL.md`.

- **Rujukan:** FSD bagian 5.7–5.9, 4 Kuis/ujian; WF bagian Pengaturan kuis; INV 13, 15. **Dependensi:** LMS-14, LMS-15, LMS-24.

### LMS-21 | P1 | Dashboard, riwayat, HS, sertifikat dan KTA

- [ ] Ganti dashboard/riwayat belajar dan sertifikat contoh dengan data akun server. Bedakan sertifikat kelulusan, kartu membership/KTA dan hak akses premium sesuai LMS-01.
- Satukan keputusan HS/kelulusan di backend, nomor sertifikat unik dan endpoint verifikasi yang sesuai kebutuhan privasi; penerbitan ulang tidak menghasilkan dokumen ganda.
- **Selesai bila:** belum lulus tetap terkunci; kelulusan/akses sesuai kebijakan; nomor tetap/dummy tidak dianggap valid; perubahan premium tidak menghapus riwayat atau sertifikat secara keliru.
**Status B09: sebagian.** Dashboard, riwayat, HS sesuai FSD pilot dan sertifikat kelulusan memakai data server. Dokumen privat secara default, PDF nyata, nomor unik, snapshot identitas dan revokasi beralasan tersedia. KTA existing hanya ditampilkan; penerbitan membership baru, pembayaran dan kebijakan komersial tetap menunggu LMS-30/K3. Uji target/staging terbuka.

- **Rujukan:** FSD bagian 2.1, Pelacakan progres, 9; WF bagian 6; INV 17, 18, 30. **Dependensi:** LMS-01, LMS-12, LMS-14, LMS-15.

## M3: tugas, penilaian dan instructor

### LMS-22 | P1 | Tugas dan submission server

**Status B10:** terimplementasi dan teruji lokal (`LmsAssignmentService`, pipeline upload `assignment_submission` maksimal 5 berkas PDF/JPG/PNG @ 20 MB, draft ber-versi & idempoten, pengiriman immutable dengan bukti `SUB-YYYYMMDD-...`, deadline/late server, dan return/resubmit). Lihat [LMS-B10-HASIL.md](LMS-B10-HASIL.md).

- [x] Tambahkan tugas per modul, due/late policy, max score, file limits, draft dan submit versi immutable, ownership, idempotency dan return/resubmit.
- **Selesai bila:** submit kosong/file belum bersih ditolak; waktu server menetapkan late; request ulang tidak menggandakan submission; versi lama tetap tersedia; siswa lain tidak dapat mengakses jawaban/lampiran.
- **Rujukan:** FSD bagian 4 Submission tugas, 3.1–3.2; WF bagian 4. **Dependensi:** LMS-04, LMS-05, LMS-07, LMS-17.

### LMS-23 | P1 | Composer tugas dan receipt Flutter

**Status B10:** terimplementasi dan teruji lokal (`AssignmentRepository`, `AssignmentController`, `AssignmentPage`, integrasi silabus & route `/assignment`, isolasi sesi saat logout, serta uji widget pada tema terang/gelap & skala teks 200%). Lihat [LMS-B10-HASIL.md](LMS-B10-HASIL.md).

- [x] Buat instruksi, deadline WIB, draft/autosave, unggah/hapus lampiran, retry, konfirmasi kirim, receipt dan revisi. Jangan mencampur draft lokal antarakun.
- **Selesai bila:** keluar/masuk layar tidak kehilangan draft tersimpan; submit terkonfirmasi server mengunci versi; gagal upload/submit jelas; siswa melihat status terlambat dan revisi yang benar.
- **Rujukan:** FSD bagian 2.1, 4 Submission tugas; WF bagian 4; **Dependensi:** LMS-11, LMS-22.

### LMS-24 | P0 | Penilaian, publikasi nilai dan audit

- [x] Sediakan penilaian jawaban kuis/tugas oleh admin: rentang skor, rubric, feedback, draft/publish, expected version, revisi dengan alasan, return untuk perbaikan dan histori aktor/waktu.
- **Selesai bila:** siswa hanya menerima nilai/feedback yang boleh dilihat; draft tersembunyi; konflik editor terdeteksi; revisi mempertahankan versi lama dan memperbarui kelulusan secara atomik.
**Status B10:** selesai implementasi lokal untuk kuis (B09) maupun tugas (B10: review draft/publish, return untuk revisi, histori revisi nilai, sinkronisasi kelulusan/HS, serta dukungan reviewer `instructor` sesuai scope kelas). Lihat [LMS-B10-HASIL.md](LMS-B10-HASIL.md).

- **Rujukan:** FSD bagian 4 Penilaian/gradebook dan review attempt, 8; WF bagian 8. **Dependensi:** LMS-04, LMS-14. Penilaian tugas juga memerlukan LMS-22.

### LMS-25 | P1 | Gradebook dan nilai siswa

**Status B10:** terimplementasi dan teruji lokal (`LmsGradebookService`, endpoint `/lms/courses/{course}/gradebook`, `/lms/gradebook/bulk-publish`, dan `/lms/courses/{course}/grades`, serta integrasi tab Buku Nilai di `GradeReview` Livewire dan `AssignmentRepository.courseGrades` di Flutter). Lihat [LMS-B10-HASIL.md](LMS-B10-HASIL.md).

- [x] Buat antrean penilaian/filter/pagination, gradebook, hasil siswa, agregasi nilai sesuai bobot, detail feedback dan publikasi batch yang memiliki audit/concurrency.
- **Selesai bila:** nilai kosong berbeda dari nol/draft; agregasi sesuai nilai yang eligible; publish batch tidak bocor ke siswa/kelas lain; pending review terlihat di mobile.
- **Rujukan:** FSD bagian 4 Penilaian/gradebook, 4.6, 7; WF bagian 6, 8. **Dependensi:** LMS-20, LMS-23, LMS-24.

### LMS-26 | P1 | Instructor dan scope kursus, bila diaktifkan

**Status B10:** diaktifkan atas keputusan pengguna dan terimplementasi/teruji lokal (`courses.instructor_id`, pivot co-instructor `lms_course_instructors`, pembatasan per kelas di `LmsAuthoringService`, `LmsAssignmentService`, `LmsGradingService`, `LmsGradebookService`, dan Livewire CMS/Quiz/GradeReview). Lihat [LMS-B10-HASIL.md](LMS-B10-HASIL.md).

- [x] Tambahkan role instructor, kepemilikan/co-instructor ACL, authoring dan grading dalam scope kursus; gunakan layanan/domain yang sama dengan admin.
- **Selesai bila:** instructor kelas X tidak bisa mengubah/menilai kelas Y; akses di luar scope ditolak server; pencabutan assignment instructor berlaku pada request berikutnya.
- **Rujukan:** FSD bagian 1.3, 2.2; WF bagian 7–8. **Dependensi:** LMS-01, LMS-04, LMS-08, LMS-24.
- Jika belum diaktifkan pada rilis awal, item tetap terbuka; jangan menampilkan kontrol pengajar sebagai fitur yang sudah tersedia.

## M4: offline, notifikasi dan operasional

> **Status perbaikan B11, 15 November 2026:** baca `LMS-B11-PERBAIKAN.md`. Jalur offline/transport push diperbaiki dan teruji lokal; penerimaan FCM/perangkat, SDK Android, staging, audit data lama dan graph tetap belum dibuktikan.

### LMS-27 | P1 | Offline media dan sinkronisasi

**Status B11:** terimplementasi dan teruji lokal (`LmsOfflineService`, `LmsBatch11Controller`, dan `OfflineMediaManager` Flutter) sesuai keputusan kebijakan `K6`/`D15` (khusus materi PDF/E-Book berlisensi, maks 2 perangkat aktif per pengguna, TTL 7 hari atau sisa masa aktif kelas, verifikasi SHA-256, batas usia event sync 24 jam, toleransi clock skew 5 menit, penolakan YouTube/kuis/tugas offline, dan pembersihan instan saat logout). Lihat [LMS-B11-HASIL.md](LMS-B11-HASIL.md).

- [x] Implementasikan download file nyata, penyimpanan terlindungi, license/grant perangkat, antrean event unik, expiry, batas usia/clock skew dan hasil sync per event. Hanya aktif untuk provider/media yang memang mendukung.
- **Selesai bila:** bukan sekadar menyimpan URL terobfusikasi; offline tidak membuka hak baru; event replay/ditolak ditangani; cache akun lain tidak terbaca; tidak ada submit kuis/tugas offline yang melewati aturan server.
- **Rujukan:** FSD bagian 4 Pelacakan progres, 8–9; WF bagian 2–3; INV 19. **Dependensi:** LMS-06, LMS-12, LMS-13, LMS-17.

### LMS-28 | P2 | Notifikasi LMS nyata

**Status B11:** terimplementasi dan teruji lokal (`LmsNotificationService`, `DispatchLmsNotificationJob`, pengingat H-1 `lms:send-assignment-reminders`, inbox `/api/v1/user/notifications`, privasi layar kunci tanpa bocor skor/jawaban, serta verifikasi deep-link server `/api/v1/lms/notifications/resolve-deep-link` di `NotificationPage` Flutter). Lihat [LMS-B11-HASIL.md](LMS-B11-HASIL.md).

- [x] Hubungkan grade published/revised, tugas dikembalikan dan pengingat yang disepakati ke queue/push/in-app; deep link memeriksa identitas dan akses.
- **Selesai bila:** token/perangkat nyata menerima event uji; status sukses/gagal berasal dari dispatch, bukan angka acak; retry tidak membuat notifikasi ganda dan target akun benar.
- **Rujukan:** FSD bagian 1.2, 4, 9; INV 22. **Dependensi:** LMS-03, LMS-24, LMS-25.

### LMS-29 | P2 | Operasional, dokumentasi dan graph

**Status B11:** terimplementasi dan teruji lokal (`LmsRequestCorrelation` middleware + `ApiClient` header `X-Request-Id`, `LmsRedactionService`, perintah terjadwal `lms:send-assignment-reminders`, `lms:cleanup-offline-licenses`, dan `lms:ops-status`, pembaruan kontrak `LMS-02-KONTRAK-INTEGRASI.md`, serta runbook operasional di [LMS-B11-HASIL.md](LMS-B11-HASIL.md)).

- [x] Sediakan request ID, log teredaksi, audit yang dapat ditelusuri, monitoring queue/scan/attempt timeout/sync, scheduler, serta prosedur pemulihan/migrasi.
- Perbarui kontrak API, panduan admin dan catatan implementasi. Perbarui baseline graph backend/mobile serta jalankan ulang kurasi setelah perubahan arsitektur; pertahankan jejak requirement → implementasi → tes.
- **Selesai bila:** error dapat ditelusuri tanpa menyimpan token/jawaban ujian/signed URL penuh; worker/scheduler terbukti memproses fixture; dokumen dan graph menyebut batas/freshness yang benar.
- **Rujukan:** FSD bagian 8, 10; INV 33 dan catatan deployment. **Dependensi:** kontrak LMS-02; validasi akhir mengikuti fitur milestone yang dirilis.

## R: gate sebelum dipakai dengan akun, konten dan transaksi nyata

### LMS-30 | P0 | Pembayaran, renewal dan refund

- [ ] Validasi signature, identitas transaksi, nominal, produk, status dan replay webhook; selaraskan grant/expiry/revoke dengan settlement, renewal dan refund. Status pembayaran mobile bukan bukti pemberian hak.
- **Selesai bila:** transaksi sandbox sukses/gagal/pending/refund memberi hak sesuai LMS-01; callback ganda/nominal salah tidak memberi hak; riwayat transaksi memakai data akun sebenarnya.
- **Rujukan:** FSD bagian akses/enrollment; INV 1, 23, 25 bagian riwayat transaksi. **Dependensi:** LMS-05. Wajib sebelum pembelian kelas diaktifkan; tidak menghalangi pilot fixture M1.

### LMS-31 | P0 | Permukaan API bersama dan konfigurasi rilis

- [ ] Periksa ulang temuan keamanan lintas aplikasi pada route yang tetap terbuka: OAuth/demografi, sponsor ownership, proxy file, ekspor analitik dan akses akun. Perbaiki atau tutup kemampuan yang belum aman sebelum deployment terkait.
- Verifikasi HTTPS/TLS, URL API per environment, cleartext/arbitrary loads, signing release, debug, CORS, storage privat, worker/scheduler serta backup/pemulihan di lingkungan rilis.
- **Selesai bila:** tidak ada jalur aktif yang menghindari policy LMS atau memberi akses akun lain; build release target dan konfigurasi lingkungan diverifikasi. Pengujian memakai fixture/disposable atau sandbox, bukan data produksi.
- **Rujukan:** INV 2, 4, 5, 6, 9, 10, 24 dan tambahan deployment. **Dependensi:** LMS-03–LMS-06 untuk jalur LMS; cakupan akhir mengikuti fitur yang diaktifkan.

### LMS-32 | P0 | Acceptance per milestone dan rilis

- [ ] Buat tes kontrak dan integrasi untuk setiap fitur yang dirilis: author → publish → read → progress → attempt → grade → hasil; tambah tugas/import/offline hanya pada milestone yang memuatnya.
- Uji lintas role/akun, status expired/revoked, batas deadline, retry/duplikasi/concurrency, draft tersembunyi, kegagalan API, migrasi/backfill dan kesesuaian hasil antarperangkat.
- **Selesai bila:** bukti tes otomatis dan walkthrough staging/perangkat tersedia; tidak ada data contoh/sukses palsu dalam jalur produksi; temuan tersisa diberi scope, dampak dan keputusan rilis tertulis.
- **Rujukan:** FSD bagian 8; seluruh INV terkait LMS. **Dependensi:** semua item milestone yang hendak dirilis; LMS-30 bila pembayaran aktif; LMS-31 sebelum rilis nyata.

## Hubungan dengan inventaris masalah di luar LMS

Tidak perlu menuntaskan seluruh inventaris sebelum mulai mengembangkan LMS. Semua temuan tetap terbuka sampai ada bukti penyelesaian.

- **Masuk langsung ke backlog ini:** INV 1–4, 6–10, 12–19, 22–25 bagian terkait LMS, 30, 32–33; batas masing-masing dijelaskan pada item terkait. INV 5 dan 24 juga diperiksa di gate API bersama.
- **Tidak menjadi prasyarat alur belajar bila tidak dipakai:** INV 11 (wishlist), 20–21 (feed/promo/diskon), 26–29 (sponsor/produk/tier/analitik), 31 (placeholder UI di luar jalur LMS), serta tambahan audit toko/sponsor. Perbaikan fungsionalnya dijadwalkan terpisah.
- **Tidak boleh hilang hanya karena scope LMS:** bagian reset password/OTP/forum pada INV 25, konfigurasi profil pada INV 32, dan semua endpoint non-LMS yang masih terbuka. Nonaktifkan atau batasi fitur yang belum berfungsi/aman sampai siap; nilai dampaknya terhadap rilis aplikasi secara keseluruhan.

## Pekerjaan pertama yang diambil

1. LMS-01: catat keputusan produk dan batas sumber video/rilis.
2. LMS-02: petakan skema CMS/API/mobile dan tetapkan kontrak migrasinya.
3. LMS-03, LMS-04, LMS-05, LMS-06: benahi fondasi identitas, hak akses dan media.
4. LMS-07–LMS-16: buktikan satu kelas pilot berjalan utuh.

Jangan mulai dari import massal atau seluruh layar sekaligus. Gunakan pilot untuk membuktikan kontrak sebelum memperluas ke konten, penilaian, dan offline.
