# Pemeriksaan UI B09

11 November 2026. Scope: editor/review kuis dan layar hasil belajar yang berubah di B09. Antislop during adalah pilihan sesi pengguna; arah visual dan dials B04 tetap dipakai. Ini bukti verifikasi lokal, bukan audit aksesibilitas seluruh aplikasi atau uji Android/iOS/staging.

Bukti: `LmsAdvancedGradingTest`, `results_test.dart`, `quiz_test.dart`, build web, screenshot `B09-review-published.png`, empat pasang `B09-{dashboard,history,credentials,hardware}-{light,dark}.png` dan PDF/PNG fixture sertifikat. Browser memakai database SQLite fixture terpisah.

## Hard gate

- R-02 PASS: Copy pada view baru tidak memuat em dash; sumber diperiksa.
- R-03 PASS: Widget empat layar diuji 390/820 dengan teks 200%; tidak ada overflow Flutter. Grid CMS memakai satu kolom sebelum lg; screenshot browser ditinjau.
- R-17 PASS: Angka progres/nilai berasal dari respons server; konten bukti diberi nama fixture.
- R-18 PASS: Tidak ada testimonial pada layar yang ditambahkan.
- R-23 PASS: Tidak membuat logo, foto, ilustrasi, statistik atau identitas organisasi baru; branding aplikasi existing dipakai.
- R-24 PASS: Tautan review dibuka melalui route aktual; rute mobile hasil/riwayat/HS/dokumen dan silabus terdaftar. Duplikasi rute dashboard/sertifikat dihapus.
- R-25 PASS: Tema Flux/Material dipertahankan; teks primer pada screenshot terang/gelap terbaca. Token teks utama #18181b pada putih dan #e4e4e7 pada #18181b memenuhi 4.5:1; tidak menambahkan teks berwarna redup custom.
- R-26 PASS: Form CMS terhubung ke layanan; draft/publish dijalankan di browser. Aksi mobile memakai endpoint autentikasi; tes memeriksa rute, payload dan kegagalan, tanpa sukses palsu.
- R-27 PASS: State loading/error/kosong dan retry tersedia pada halaman hasil; antrean CMS memiliki pesan kosong, validasi dan feedback status.
- R-28 PASS: Tidak menambah FAQ.
- R-32 PASS: CMS memakai input/select/button/details native atau Flux dengan focus bawaan. Opsi dan alias diperbaiki dengan label nyata; radio memiliki accessible name.
- R-33 PASS: Perubahan berada di source repo dan dikompilasi lewat build normal; tidak menyuntik CSS atau perilaku ke browser.
- R-34 PASS: Widget terang/gelap diperiksa melalui tes screenshot, tanpa toggle tema baru.
- R-35 PASS: Build aset web lulus; browser menjalankan review → draft → publish. Form editor diuji Livewire. Layar hasil, privasi, logout dan layout diuji widget; bukti ini tidak dianggap uji native/staging.
- R-36 PASS: Tidak menambah klaim keamanan, sertifikasi, akreditasi atau angka performa.
- R-37 PASS: Arah mengikuti panel VBAT/Flux dan aplikasi Material existing; ENERGY 1, RHYTHM 2, MOTION 1 dari pilihan sesi during yang sudah berlaku.
- R-38 PASS: Semua screenshot baru memakai data fixture yang diberi nama; tidak menyamar sebagai hasil siswa nyata.
- R-39 PASS: Kontrol baru native/Flux/Material memakai label, disabled/loading state dan status; tanpa animasi dekoratif. UI memakai container layout existing.
- R-40 PASS: Tidak menambah hero/media jaringan atau skrip pihak ketiga baru; viewer PDF berada dalam area body dan loading state.

## Purpose gate

- R-01 PASS: Tidak menambah gradient atau glow.
- R-04 PASS: Ikon refresh, PDF dan chevron hanya menunjukkan tindakan/konten yang sesuai; set Material existing.
- R-06 PASS: Font aplikasi existing dipakai; screenshot widget memuat Roboto asli, PDF DejaVu Sans untuk Unicode.
- R-07 PASS: Tidak menambah pola latar.
- R-08 PASS: Tidak menambah panah dekoratif pada tombol.
- R-09 PASS: Status published/pending disajikan sebagai data penilaian, tanpa badge pemasaran.
- R-10 PASS: Tidak menambah glassmorphism.
- R-12 PASS: Panel memisahkan form dan record aktual; tanpa bayangan besar tambahan.
- R-13 PASS: Tidak menambah glow.
- R-14 PASS: Daftar memakai record kelas/attempt/dokumen aktual, bukan grid fitur pemasaran.
- R-19 PASS: Tidak menambah animasi template.
- R-22 PASS: Tidak menambah ilustrasi generik.

## Liveliness

- Dials PASS: ENERGY 1 / RHYTHM 2 / MOTION 1 konsisten dengan editor dan pembaca hasil.
- Focal point PASS: Judul kelas, soal atau dokumen menjadi fokus tiap record.
- Whitespace PASS: Jarak memisahkan identitas, status, feedback dan tindakan; Wrap/grid menata ulang pada lebar sempit.
- Accent PASS: Biru VBAT/primary dipakai untuk aksi, bukan seluruh teks.
- Identity PASS: Istilah kelas, HS, aktivitas wajib dan pemisahan sertifikat/KTA mengikuti konteks VBAT.
- Design Read PASS: Arah visual sesi B04 dipertahankan selama pengerjaan, tanpa redesign branding.

## Craftsmanship

- C-1 PASS: Komposisi editor/daftar sesuai pekerjaan admin; hasil mobile sesuai resource akun.
- C-2 PASS: Tidak menampilkan tombol unduh pada dokumen yang belum memiliki PDF; UI HS hanya menampilkan kelas yang diberikan server.
- C-3 PASS: Bagian baru mengikuti kebutuhan grading/hasil; tanpa section pengisi.
- C-4 PASS: State pending/error/kosong, logout, layout 390/820 dan tema diuji dalam cakupan fixture yang dinyatakan.
- C-5 PASS: Bukti hanya tes dan fixture; tidak mengklaim rilis atau uji perangkat nyata.
- R-05 PASS: Tidak menambah template landing hero/bento/pricing.
- R-11 PASS: Komponen memakai radius default sistem: field/panel CMS dan tombol Material tetap sesuai fungsinya.
- R-15 PASS: Aksi menyebut Simpan draft, Publikasikan, Buka silabus, Terbitkan sertifikat dan Muat ulang.
- R-16 PASS: Tidak menambah jargon pemasaran AI.
- R-20 PASS: Konten dan akses HS/kelas VBAT menentukan struktur, bukan branding kosmetik semata.
- R-21 PASS: Terang/gelap mengikuti tema aplikasi, tidak memaksa mode baru.
- R-29 PASS: Palet mengikuti warna primer serta warna semantik existing.
- R-30 PASS: Tidak meniru aplikasi lain atau mengganti sistem desain.
- R-31 PASS: Pilihan form/daftar dan spacing mengikuti hierarchy review dan hasil; tidak menambah elemen tanpa fungsi.
