# Pemeriksaan UI perbaikan B11

15 November 2026. Scope: layar library/reader offline dan entry download/opt-in push; bukti di `offline_persistence_test.dart` dan screenshot `B11-offline-light/dark.png`. Ini bukan sertifikasi aksesibilitas seluruh aplikasi atau bukti native/provider/staging.

## Hard gate

- R-02 PASS: Copy UI baru tanpa em dash.
- R-03 PASS: Tes reflow 390/820 dan teks 200% tanpa overflow; screenshot terang/gelap diperiksa.
- R-17 PASS: Jumlah pending berasal dari antrean aktual; identitas screenshot jelas fixture.
- R-18 PASS: Tidak menambah testimonial.
- R-23 PASS: Tidak membuat aset/branding baru; ikon Material existing sesuai download, sync dan notifikasi.
- R-24 PASS: Entry offline memakai route /offline-library yang terdaftar; reader hanya dari dokumen berlisensi.
- R-25 PASS: Tema Material existing dipakai, teks/aksi terang/gelap terlihat pada screenshot; warna utama e4e4e7/18181b dan 18181b/ffffff memiliki rasio 13.96/17.72 dari pemeriksaan pasangan token.
- R-26 PASS: Tes widget menjalankan halaman akhir, konfirmasi, retry lisensi gagal, hapus lokal dan opt-in push tanpa config. Tombol lain memanggil layanan canonical dengan tes kontrak.
- R-27 PASS: Loading, error, empty, pending dan rejected terlihat; kegagalan push tidak menjadi sukses.
- R-28 PASS: Tidak menambah FAQ.
- R-32 PASS: Button, chip dan icon button native Material berlabel/tooltip, dengan focus bawaan.
- R-33 PASS: Perubahan berada pada source, bukan injeksi ke browser.
- R-34 PASS: Screenshot dan reflow terang/gelap diuji.
- R-35 PASS: Aplikasi widget dijalankan pada 97 tes Flutter; build web lulus. Build Android dicoba dan terblokir SDK, tidak diklaim lulus/native UAT.
- R-36 PASS: Tidak mengklaim DRM atau exactly-once provider.
- R-37 PASS: Mengikuti arah existing VBAT/Material: ENERGY 1, RHYTHM 2, MOTION 1; mode during pilihan sesi.
- R-38 PASS: Bukti memakai file dan identitas fixture, bukan hasil siswa nyata.
- R-39 PASS: Kontrol memakai semantik/state Material; aksi jaringan dinonaktifkan saat sibuk/akun belum sah; tidak ada animasi baru.
- R-40 PASS: Viewer PDF mengambil ruang Expanded dan state loading, tanpa media hero/script baru.

## Purpose gate

- R-01 PASS: Tanpa gradient/glow tambahan.
- R-04 PASS: Ikon relevan dengan aksi download/sync/notifikasi.
- R-06 PASS: Font/tema aplikasi dipertahankan; screenshot memuat Roboto nyata.
- R-07 PASS: Tanpa pola latar baru.
- R-08 PASS: Tanpa panah dekoratif.
- R-09 PASS: Status hanya keadaan data yang nyata.
- R-10 PASS: Tanpa glassmorphism.
- R-12 PASS: Card hanya mengelompokkan metadata/aksi satu unduhan.
- R-13 PASS: Tanpa glow.
- R-14 PASS: Daftar mengikuti jumlah dokumen aktual.
- R-19 PASS: Tanpa animasi template.
- R-22 PASS: Tanpa ilustrasi generik.

## Liveliness

- Dials PASS: 1/2/1, alat baca operasional existing.
- Focal point PASS: Judul dokumen dan status lisensi menjadi fokus.
- Whitespace PASS: Pisahkan antrean, dokumen, dan aksi; Wrap pada layar sempit.
- Accent PASS: Primary untuk membuka dokumen, aksi lain sekunder.
- Identity PASS: Bahasa kelas, lisensi dan progres mengikuti konteks VBAT.
- Design Read PASS: Arah B04 dipertahankan, tanpa redesign atau aset baru.

## Craftsmanship

- C-1 PASS: Form mengikuti kebutuhan pembaca dan unduhan.
- C-2 PASS: Aksi nyata/tes tersedia, tidak ada kontrol dekoratif mati.
- C-3 PASS: Tidak menambah blok pengisi atau pemasaran.
- C-4 PASS: Kasus layout, error, logout, restart dan reader diuji dalam scope widget/kontrak yang disebut.
- C-5 PASS: Semua klaim bukti diberi scope; tidak menyatakan konfigurasi/SDK yang belum tersedia sebagai berhasil.
- R-05 PASS: Daftar resource, bukan template landing.
- R-11 PASS: Radius mengikuti Material, tanpa sistem pill baru.
- R-15 PASS: CTA menyebut Buka PDF, Perbarui lisensi, Hapus dari perangkat dan Sinkronkan progres.
- R-16 PASS: Tanpa jargon pemasaran AI.
- R-20 PASS: Alur lisensi/progres dan pemisahan online/offline menentukan konten.
- R-21 PASS: Tidak memaksa tema baru.
- R-29 PASS: Warna mengikuti tema primer/semantik existing.
- R-30 PASS: Tidak mengkloning desain aplikasi lain.
- R-31 PASS: Setiap kelompok UI sesuai dokumen/antrean/aksi, tanpa dekorasi tanpa fungsi.
