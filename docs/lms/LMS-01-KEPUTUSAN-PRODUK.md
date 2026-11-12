# LMS-01: usulan keputusan produk dan batas rilis

Tanggal: 9 November 2026, WIB. Versi: 0.1. Status: **siap ditinjau, belum disetujui sebagai aturan final**.

Dokumen ini menyelesaikan penyusunan usulan LMS-01. Checklist LMS-01 tetap terbuka sampai keputusan produk dikonfirmasi. Dokumen ini belum menyatakan fitur telah diimplementasikan.

## 1. Acuan dan lokasi kerja

- Backend: repo `WEB_MONITORING_VBAT`, acuan `origin/main@5aebf5f`.
- Mobile: repo `MOCKUP-VBAT`, acuan `origin/haidarreview@02c5e0c`.
- Keduanya telah di-fetch dari GitHub dan diverifikasi sesuai acuan pilihan pengguna pada 9 November 2026. Branch kerja masing-masing: `codex/lms-foundation`.
- Master dokumen: `PROJECT VBAT/LMS-01-KEPUTUSAN-PRODUK.md`. Salinan identik disimpan di `docs/lms/LMS-01-KEPUTUSAN-PRODUK.md` pada kedua repo agar dapat dibaca dari sesi/asisten yang berbeda. Perubahan keputusan harus disalin ke kedua repo.
- Sumber kebutuhan: `FSD_LMS_VBatPonsel.md` (FSD), `FSD_LMS_Mobile_Wireframes.md` (WF), dan `final_requirements_vbatponsel.md` (REQ).
- Sumber masalah: `01 - Inventaris Masalah.md` (INV). FSD/WF/INV berada di vault Obsidian `40 - Projects/VBAT/`; REQ berada di akar repo mobile.
- Aturan saat sumber bertentangan: catat konflik dalam dokumen ini. Jangan menganggap FSD, kode lama, atau usulan agen otomatis menggantikan keputusan pemilik produk.

## 2. Bentuk produk dan tahapan

### D01. Pengguna dan tempat pengelolaan

**Dasar kebutuhan:** FSD bagian 6; WF bagian 9–10.

Usulan: admin mengelola kelas di panel web Laravel; siswa mengikuti kelas melalui Flutter Android/iOS. Pratinjau admin menampilkan pengalaman siswa. Pengelolaan penuh dari aplikasi mobile tidak menjadi target awal.

Instructor adalah perluasan berikutnya. Sampai role itu diaktifkan, admin menangani authoring dan review manual; istilah pengajar pada layar tidak otomatis berarti perlu membuat role/portal instructor pada rilis pertama. Pemetaan role teknis existing seperti `super_admin` ditentukan pada LMS-02.

### D02. Pilot dan rilis pertama

**Usulan urutan, perlu konfirmasi cakupan rilis.**

- **Pilot internal:** satu kelas, satu modul, dua video dan satu kuis pilihan ganda. Akun/konten uji, grant admin yang diaudit, sesi dan hasil dari server. Pembayaran, publikasi ke toko aplikasi dan data siswa nyata bukan bagian pilot.
- **Rilis LMS inti yang diusulkan:** pengelolaan kelas/modul/materi, draft/preview/publish/archive, video online, PDF/e-book online, import Excel, urutan belajar, kuis dan penilaiannya, progres/riwayat, HS dan sertifikat/KTA sesuai ketentuan. Integrasi pembayaran serta gate keamanan wajib bila kelas berbayar diaktifkan.
- **Perluasan terpisah yang diusulkan:** tugas/lampiran dan gradebook lengkap, role instructor/co-instructor, download offline, serta notifikasi lanjutan. Penundaan ini belum merupakan penghapusan requirement dan perlu disetujui sebelum target rilis dikunci.
- Jawaban singkat/studi kasus tetap bagian target kuis inti; jika penilaian manualnya belum siap, konten yang memerlukannya tidak boleh diterbitkan sebagai auto-grade pura-pura.
- Setiap milestone dinilai berdasarkan fitur yang diaktifkan. Pilot berhasil tidak otomatis berarti seluruh LMS siap produksi.

## 3. Konten dan penerbitan

### D03. Sumber video

**Dasar kebutuhan:** FSD 5.6, 6; WF Video YouTube; REQ constraint wajib menonton.

Usulan untuk pilot: video YouTube **unlisted**, online, melalui player resmi, dengan konten uji yang memang boleh digunakan. Private dan unlisted tidak diperlakukan sebagai hal yang sama. Setiap video diuji pada aplikasi target sebelum bisa dipublikasikan.

Batas produk yang harus diterima atau mengubah pilihan sumber media:

- Unlisted membatasi penemuan melalui pencarian, bukan kepemilikan tautan. Tautan yang diketahui dapat dibagikan; entitlement VBAT tidak menjadi otorisasi di YouTube.
- Tidak menjanjikan hilangnya seluruh branding/tautan/kontrol YouTube atau pencegahan penyalinan absolut.
- Offline video YouTube tidak diimplementasikan sebagai download/cache VBAT. Offline memerlukan sumber media yang mendukung penggunaan tersebut.
- Requirement melarang skip/fast-forward ke bagian belum ditonton perlu dibuktikan pada player yang dipilih. Progres server tidak sama dengan kendali penuh atas player.
- Jika proteksi akses video, kendali player atau offline menjadi syarat wajib rilis pertama, pilih sumber media privat sebelum kontrak media dan rilis komersial dikunci. Pemilihan penyedia/biaya dan kelayakan penggunaan layanan dilakukan di LMS-06.

Referensi batas layanan: [privasi video YouTube](https://support.google.com/youtube/answer/157177?hl=en) dan [kebijakan developer YouTube](https://developers.google.com/youtube/terms/developer-policies). Referensi ini tidak menggantikan uji player dan penilaian kelayakan penggunaan untuk produk VBAT.

### D04. Struktur, status dan import

**Mode teknis pilot B08:** create-only pada kelas/modul existing, duplikat ditolak, apply seluruhnya-batal dan hasil Draft. Ini batas implementasi testing, bukan persetujuan overwrite/migrasi kurikulum atau seluruh K5.

**Dasar kebutuhan:** FSD 5.1–5.5, 5.7; WF 9–10.

Usulan struktur produk: `Kelas → Modul → Aktivitas`, dengan aktivitas video, bacaan atau kuis. Kuis dapat ditempatkan setelah materi, di akhir modul atau di akhir kelas; tugas menjadi jenis aktivitas tambahan pada tahapnya. Pemetaan tabel/model existing dikerjakan di LMS-02.

Semua konten baru berstatus Draft. Admin wajib mempratinjau, memperbaiki validasi, lalu menerbitkan secara eksplisit. Siswa hanya melihat konten terbit yang boleh diakses. Arsip mempertahankan riwayat tetapi menutup pembukaan aktivitas baru sesuai kebijakan perubahan konten.

Usulan import Excel: preview tidak mengubah data; **satu baris invalid membatalkan penerapan seluruh import**. Konflik kode default membatalkan import; pilihan update/skip harus eksplisit. Apply berulang tidak menggandakan isi. Semua hasil tetap Draft.

Ini memilih satu kebijakan dari dua versi yang bertentangan: contoh “Terapkan 20 baris” belum menjadi acuan bila kebijakan seluruhnya-batal disetujui. Excel merujuk berkas PDF/e-book yang telah diunggah dan teridentifikasi unik, bukan sekadar nama yang dapat ambigu.

## 4. Pembelian, premium dan akses

### D05. Pisahkan empat status

**Dasar kebutuhan:** REQ bagian membership, paket dan premium; FSD enrollment/entitlement; INV 1, 7, 17, 23.

- **Hak paket:** akun memiliki kelas Android, iPhone atau keduanya melalui bundling. Usulan: bundling adalah gabungan hak Android+iPhone, bukan izin untuk setiap konten baru tanpa batas.
- **Masa akses konten:** periode yang mengizinkan pembukaan materi/HS; dapat berasal dari masa awal pembelian, program alumni, perpanjangan atau grant admin yang sah.
- **Status belajar:** progres dan kelulusan yang tersimpan per akun dan kelas. Premium aktif tidak membuat siswa otomatis lulus.
- **Dokumen identitas/pencapaian:** KTA membership dan sertifikat kelulusan memiliki syarat penerbitan berbeda; akses permanen terhadap dokumen tidak berarti akses materi permanen.

Usulan aturan akses: kelas terbit + hak paket sesuai + masa akses konten aktif + prerequisite terpenuhi. Preview/free class mempunyai kebijakan terpisah. Server mengevaluasi semuanya; flag lokal bukan bukti hak.

**Belum diputuskan:** apakah premium/perpanjangan hanya mengaktifkan kembali paket yang dimiliki, atau membuka seluruh kelas. Rekomendasi awal: hanya paket yang dimiliki; perubahan ke akses semua kelas harus diputuskan sebagai perubahan produk.

### D06. Masa akses dan alumni

**Dasar kebutuhan:** REQ menyebut free premium alumni hingga awal Januari 2027, kemudian langganan tahunan; KTA/sertifikat tetap valid setelah premium habis.

Usulan: premium tahunan berakhir pada timestamp server, dengan tanggal/jam berakhir ditampilkan dalam WIB. Simpan waktu dalam UTC. Periode perpanjangan aktif ditambahkan dari akhir periode aktif; perpanjangan setelah expired dimulai dari waktu pembayaran/grant tervalidasi. Aturan ini masih usulan.

Angka harga dari REQ adalah catatan kebutuhan lama dan tidak dikunci sebagai katalog harga dalam dokumen ini.

**Wajib dikonfirmasi sebelum rilis berbayar/alumni:**

- Durasi akses yang termasuk pembelian kelas baru, serta kapan mulai dihitung.
- Tanggal **dan jam** berakhir program alumni, siapa yang termasuk alumni, dan paket apa yang diberikan.
- Aturan menumpuk perpanjangan dan akibat refund terhadap membership/hak paket/dokumen.

Jangan menerjemahkan “awal Januari” menjadi 1 Januari atau tanggal lain tanpa keputusan pemilik produk. Jangan mengisi expiry kosong sebagai akses permanen secara diam-diam.

### D07. Free class dan preview

**Dasar kebutuhan:** REQ preview 5–10 detik; FSD `is_preview` dan hak preview.

Usulan: katalog/metadata boleh terlihat publik; hanya materi yang sengaja ditandai Free/Preview dapat diputar tanpa hak paket penuh. Default preview diusulkan **10 detik**, dapat diatur admin dalam batas yang disepakati.

Preview tidak menyelesaikan materi berbayar, membuka kuis/HS atau menerbitkan sertifikat. Pratinjau admin terpisah dari preview pemasaran dan tidak menulis progres/attempt siswa nyata. Dukungan batas preview wajib diuji pada sumber media, bukan dijanjikan hanya dari UI.

## 5. Progres, evaluasi dan kelulusan

### D08. Materi selesai versus kelas selesai

**Dasar kebutuhan:** FSD bagian 4 Pelacakan progres dan 9; REQ kelas terkait tuntas 100%; INV 16–17.

Ambang video selesai yang disetujui pengguna pada 10 November 2026: cakupan tontonan unik minimal **95%**, posisi maksimum minimal **95% durasi**, dan bagian belum ditonton maksimal **5%**. Server memvalidasi sesi/event/selang waktu; seek atau payload `progress_percent:100` saja tidak menuntaskan materi. Putar ulang bagian yang sama tidak menggandakan cakupan.

**Kelas selesai 100% berarti semua aktivitas wajib dinyatakan selesai**, bukan setiap video harus memiliki angka tampilan tepat 100%. Untuk kuis wajib, selesai berarti lulus dengan nilai final yang sudah dipublikasikan. Tugas menghitung kelulusan hanya bila ditandai wajib setelah fitur tugas tersedia.

Progres kelas: jumlah aktivitas wajib selesai dibagi total aktivitas wajib. Aktivitas opsional tidak menghambat kelulusan. Kelas tanpa aktivitas wajib yang valid tidak otomatis lulus dan tidak boleh dipublikasikan sebagai kelas bersertifikat.

Contoh: dua video wajib dan satu kuis wajib adalah tiga aktivitas. Kedua video telah mencapai ambang teknis, tetapi kuis gagal: progres kelas 2/3 dan belum lulus. Kuis lulus: 3/3 atau 100%.

**Keputusan pengguna, 10 November 2026:** “Minimal 95% durasi (usulan FSD)”. Bagian ambang video K4 disetujui; aturan kuis, grading dan gate HS lain tetap menunggu keputusan tersendiri. Telemetri tidak membuktikan perhatian/pemahaman siswa secara absolut.

### D09. Urutan belajar dan bacaan

**Dasar kebutuhan:** FSD 2.1, 4 Course/modul/materi; WF 2–3.

Usulan default: aktivitas wajib berjalan berurutan; materi/kuis berikutnya terkunci sampai prerequisite selesai. Siswa boleh kembali ke materi yang sudah dibuka selama hak akses aktif. Aktivitas opsional tidak memblokir jalur wajib; prasyarat khusus harus terlihat jelas pada editor admin.

PDF/e-book dinyatakan selesai setelah mencapai akhir viewer dan konfirmasi siswa diterima server. B08 menerapkan pola ini untuk bacaan berformat PDF dengan jumlah halaman hasil parser server; EPUB belum didukung. Ini bukti interaksi/konfirmasi, bukan jaminan dokumen dipahami. Tombol Lanjutkan memakai aktivitas berikutnya dari server.

### D10. Aturan kuis

**Dasar kebutuhan:** FSD 5.7–5.9, 9; WF 5–6.

**Konfigurasi testing B07:** pengguna meminta mengikuti MD dan, bila belum ditentukan, memilih yang paling mudah untuk testing. FSD menetapkan default nilai lulus **70/100**, sedangkan batas waktu, percobaan dan pemilihan nilai dapat diatur admin. Implementasi pilot memakai **70/100, tanpa batas percobaan (`max_attempts=0`), tanpa batas waktu (`time_limit_minutes=null`), nilai tertinggi**. Nilai 3 percobaan/20 menit sebelumnya hanya usulan dan tidak diterapkan sebagai default. Kuis existing tidak diubah massal. Ini keputusan untuk testing, bukan pengesahan kebijakan produksi.

Admin dapat mengatur nilai 0–100, waktu tanpa batas atau 1–240 menit, percobaan 1–10 atau tanpa batas, nilai tertinggi/terakhir/rata-rata, pengacakan dan wajib lulus. Aturan yang sudah dipakai attempt tidak berubah retroaktif.

Siswa melanjutkan attempt aktif yang sama, termasuk setelah aplikasi ditutup. Deadline dihitung server; expired attempt difinalisasi sesuai jawaban tersimpan. Nilai/attempt draft atau menunggu review tidak dianggap lulus. Penjelasan/jawaban benar mengikuti aturan review; answer key internal tetap tidak dikirim sebagai data soal student.

Untuk pilot gunakan MCQ dengan 2–6 opsi dan satu jawaban benar. Jawaban singkat memakai jawaban/alias yang didefinisikan admin. Studi kasus menyediakan rubric dan review manusia untuk nilai final bila menggunakan mode manual/hybrid; keyword tidak otomatis membuktikan kualitas analisis.

### D11. Hardware Solution per paket

**Dasar kebutuhan:** REQ HS; FSD 2.1, Pelacakan progres; INV 17.

**Keputusan pengguna, 11 November 2026:** “Pakai aturan FSD untuk pilot”. Disetujui untuk B09: seluruh aktivitas wajib materi/kuis pada kelas terkait tuntas 100%, paket kelas aktif dan premium aktif. Studi kasus menunggu nilai manusia yang sudah dipublikasikan. Persetujuan ini tidak menetapkan kebijakan komersial/refund/migrasi kurikulum atau seluruh K3–K6.

Usulan: HS Android terbuka bila hak paket Android aktif, masa akses premium aktif, dan **kelas Android terkait telah lulus 100% aktivitas wajib**. HS iPhone mengikuti kelas iPhone. Membeli bundling tidak langsung membuka HS; menyelesaikan Android tidak otomatis membuka HS iPhone.

Flag lokal progres 90% bukan aturan produk yang diusulkan. Pembelian ataupun premium aktif saja tidak menggantikan syarat belajar. Daftar kelas mana yang menjadi prerequisite HS harus ditetapkan admin secara eksplisit; jangan memilih sembarang kelas berdasarkan nama.

### D12. KTA dan sertifikat

**Dasar kebutuhan:** REQ KTA/sertifikat permanen; FSD 2.1, 8; INV 18.

Usulan: KTA diterbitkan setelah pembelian/grant membership tervalidasi dan profil wajib lengkap. Sertifikat kelulusan diterbitkan setelah kelas terkait lulus serta identitas wajib valid. **KTA bukan sertifikat kelulusan.** Tidak memakai nomor sertifikat tetap atau sukses lokal sebagai bukti.

KTA/sertifikat yang sudah diterbitkan dapat dilihat dan diverifikasi setelah masa premium habis. Masa berlaku dokumen tidak dihapus hanya karena langganan expired. Siswa tetap dapat melihat hasil/riwayat yang sudah diperolehnya; file materi terlindungi tetap mengikuti akses konten.

Nama/identitas dokumen disimpan sebagai snapshot saat terbit. Koreksi identitas atau pembatalan karena kesalahan/fraud menjadi tindakan admin beralasan dengan audit; tidak mengubah dokumen lama tanpa jejak. Kebijakan refund terhadap dokumen masih perlu keputusan produk, terpisah dari expired premium biasa.

## 6. Tugas, perubahan konten dan data

### D13. Tugas dan review manual

**Dasar kebutuhan:** FSD Submission tugas, Penilaian/gradebook, 9; WF 4, 8.

Jika tahap tugas diaktifkan: draft dapat diedit, submit membuat versi tetap dan receipt. Usulan batas awal **5 file, 20 MiB per file**, tipe PDF/JPEG/PNG sesuai konfigurasi tugas. Deadline mengikuti server, tampil WIB. Default telat ditolak kecuali admin membuka jendela pengumpulan terlambat.

Resubmission hanya jika admin mengizinkan/return untuk revisi, dengan versi baru dan deadline jelas. Nilai draft tidak terlihat siswa; nilai published dan revisinya memiliki riwayat. Aktivitas yang memerlukan tugas tidak boleh diterbitkan sebelum alur tersebut tersedia.

### D14. Perubahan kelas setelah digunakan

**Usulan kebijakan untuk melindungi progres existing; detail revisi ditentukan LMS-02.**

- Koreksi judul/deskripsi tidak mereset progres.
- Mengganti video, kunci/soal, bobot atau aturan lulus membuat revisi; attempt yang berjalan memakai snapshot awal.
- Usulan: angkatan/enrollment yang sudah berjalan tetap pada versi persyaratan kelasnya. Materi wajib baru berlaku untuk enrollment baru atau migrasi admin yang dijelaskan dan diaudit; tidak diam-diam mengunci ulang siswa yang telah lulus.
- Arsip menghentikan pembukaan materi/attempt baru sesuai pemberitahuan. Attempt aktif ditangani eksplisit (dibiarkan finalisasi atau dibatalkan dengan alasan); tidak hilang begitu saja.
- Keputusan kompensasi/penggantian kelas ketika kelas berbayar diarsipkan perlu disetujui sebelum fitur arsip dipakai pada siswa nyata.

### D15. Offline dan koneksi gagal

**Dasar kebutuhan:** FSD 2.3, 8–9; INV 19.

Usulan rilis awal online. Saat jaringan gagal, tampilkan status gagal/belum tersinkron; jangan mengganti data dengan kelas, hasil atau sertifikat contoh. Optimistic UI tidak membuka prerequisite sebelum server mengonfirmasi.

Pada tahap offline: hanya media dengan izin/provider yang mendukung; license terkait akun/perangkat/masa akses, event unik dapat diterima/ditolak server. Batas usulan FSD: antrean maksimal 24 jam dan toleransi selisih jam 5 menit, tetap perlu dikonfirmasi. Kuis/tugas tidak memperoleh jalur submit yang menghindari server.

### D16. Retensi dan penghapusan akun

**Dasar kebutuhan:** FSD 8, audit grade/attempt dan versi submission.

Usulan: pisahkan profil, bukti transaksi, progres, attempt/grade, submission/lampiran, dokumen terbit dan log audit. Tidak menetapkan satu durasi untuk semua jenis data atau menyimpan semua data tanpa batas.

Penghapusan akun segera mencabut token/akses dan melepas cache perangkat. Data/dokumen yang masih perlu dipertahankan mengikuti kebijakan retensi dan pseudonimisasi yang disetujui; akses verifikasi publik juga perlu aturan setelah penghapusan.

**Belum diputuskan:** jangka simpan per kategori, dasar kebutuhan bisnis/legal, akses sertifikat setelah akun dihapus, dan masa simpan lampiran setelah penilaian. Tetapkan sebelum menerima submission/data siswa nyata yang terdampak. Durasi tidak ditentukan sepihak oleh developer.

## 7. Contoh perilaku untuk uji produk

Semua contoh berikut adalah skenario uji usulan berdasarkan D01–D16; bukan laporan tes yang telah dijalankan. Gunakan akun fixture, bukan identitas siswa nyata.

1. **Guest membuka preview Android:** hanya preview yang ditandai tersedia; tidak mendapat completion berbayar, kuis atau HS.
2. **Akun tanpa pembelian tetapi mengubah `isPremium` lokal:** tetap tidak memperoleh materi berbayar dari API.
3. **Pemilik Android dengan masa akses aktif:** bisa membuka materi Android yang prerequisite-nya terpenuhi; iPhone tetap terkunci.
4. **Pemilik Android tetapi premium/periode akses expired:** materi dan HS terkunci; progres/nilai tersimpan, KTA/sertifikat yang sudah terbit tetap dapat dibuka.
5. **Pemilik Android dengan akses aktif tetapi satu kuis wajib gagal:** kelas belum lulus dan HS Android terkunci, walaupun semua video sudah selesai.
6. **Dua video memenuhi ambang D08 dan satu kuis wajib lulus:** tiga aktivitas wajib selesai, kelas 100%; HS kelas terkait tersedia bila hak paket dan premium masih aktif.
7. **Pemilik bundling baru selesai Android:** HS Android dapat tersedia; HS iPhone tetap terkunci sampai kelas iPhone terkait lulus.
8. **Membeli premium saja tanpa hak paket:** menurut usulan D05 tidak membuka kelas berbayar baru. Hasil ini berubah jika pemilik produk memilih premium membuka semua kelas.
9. **Alumni mendapat grant yang masih aktif:** hanya paket yang tercantum di grant terbuka. Setelah cutoff tepat yang disetujui, konten kembali terkunci; dokumen permanen tidak otomatis hilang.
10. **Siswa seek langsung ke akhir video:** belum selesai hanya karena posisi akhir; server mengevaluasi cakupan yang diterima.
11. **Aplikasi ditutup saat kuis:** attempt dan deadline tetap sama; membuka ulang tidak memberikan waktu/percobaan baru.
12. **Studi kasus masih menunggu review:** tampil Menunggu penilaian; tidak membuka prerequisite yang memerlukan lulus.
13. **Admin preview draft:** tidak mengubah progres/attempt siswa dan tidak membuat draft terlihat publik.
14. **Import 24 baris dengan 4 baris invalid:** menurut usulan D04 tidak ada perubahan konten; admin memperbaiki berkas lalu mengulang preview.
15. **Materi wajib baru ditambahkan setelah kelas lulus:** kelulusan versi lama tidak otomatis dicabut; perubahan mengikuti kebijakan revisi D14.
16. **Siswa A mencoba membuka grade/submission siswa B:** ditolak walaupun keduanya memiliki paket yang sama.
17. **Dua request submit kuis yang sama:** satu hasil final; tidak menggandakan nilai atau pemakaian attempt.
18. **Pembayaran pending/refund:** pending tidak memberi hak; refund mengikuti keputusan yang disetujui dan tidak diperlakukan sama dengan sekadar premium expired.

## 8. Keputusan yang masih membutuhkan review pemilik produk

- [ ] **K1, cakupan rilis (D01–D02):** setuju admin web + siswa mobile; tugas, instructor dan offline menyusul? Tentukan fitur target yang benar-benar wajib di rilis pertama.
- [ ] **K2, media (D03, D07):** setuju unlisted online untuk pilot? Untuk rilis nyata, apakah batas tautan/player diterima atau video privat wajib sejak awal? Preview 10 detik masih usulan.
- [ ] **K3, paket dan masa akses (D05–D06):** premium hanya memperpanjang paket yang dimiliki atau membuka semuanya; durasi awal pembelian; cutoff alumni yang tepat; renewal/refund.
- [ ] **K4, progres dan kuis (D08–D11), sebagian:** video ≥95% disetujui 10 November 2026; bagian lain belum final. Testing kuis B07 memakai 70/tanpa batas waktu dan percobaan/nilai tertinggi sesuai delegasi pengguna. Kebijakan produksi kuis, kelas wajib 100% dan HS per kelas terkait masih perlu ditetapkan.
- [ ] **K5, konten dan dokumen (D04, D12–D14):** setuju import seluruhnya-batal, pemisahan KTA versus sertifikat, dan revisi kelas tidak mereset kelulusan lama secara diam-diam?
- [ ] **K6, data dan tugas (D13, D15–D16):** batas upload, late/resubmit, batas offline dan periode retensi per jenis data; penanggung jawab keputusan retensi sebelum rilis fitur terdampak.

Pemilik produk dapat menyetujui per kelompok dan mengubah nilai tertentu. Persetujuan pilot bukan persetujuan seluruh kebijakan komersial/retensi. LMS-02 dapat memetakan struktur existing, tetapi tidak boleh membekukan aturan bisnis yang masih terbuka seolah sudah disetujui.

## 9. Hasil LMS-01 dan langkah berikutnya

- [x] Kebutuhan dan konflik sumber dicatat.
- [x] Usulan aturan serta batas pilot/rilis ditulis.
- [x] Contoh akses dan kelulusan tersedia sebagai dasar acceptance.
- [ ] Kelompok K1–K6 ditinjau; keputusan akhir dicatat beserta tanggal/aktor.
- [ ] LMS-01 dinyatakan selesai setelah keputusan untuk milestone yang dipilih sudah cukup jelas; kelompok yang ditunda harus memiliki batas milestone dan tidak dianggap disetujui.

Setelah review, LMS-02 memetakan aturan terpilih ke model/migrasi/API existing. Implementasi auth, entitlement, media dan alur kelas mengikuti backlog sesudahnya.
