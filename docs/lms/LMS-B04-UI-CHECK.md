# Pemeriksaan UI B04

Scope: CMS konten dan preview admin yang diubah B04. Mode antislop `during` dipilih pengguna untuk sesi ini; tidak disimpan sebagai preferensi global. Arah visual mengikuti panel VBAT/Flux existing, bukan redesign branding: ENERGY 1, RHYTHM 2, MOTION 1. Bukti visual berada di folder `screenshots`; bukti perilaku di `LmsAuthoringTest` dan laporan hasil B04. Pemeriksaan ini bukan sertifikasi aksesibilitas seluruh aplikasi atau jaminan semua kombinasi data/perangkat.

## Hard gate

- R-02 PASS: copy CMS/preview baru tidak memakai em dash.
- R-03 PASS: browser pada 390/820/1280 px menghasilkan lebar konten sama dengan viewport; navigasi membungkus, form dan daftar menumpuk pada layar sempit.
- R-17 PASS: tidak menambah statistik atau angka pelanggan.
- R-18 PASS: tidak menambah testimonial/identitas pelanggan; data browser ditandai Fixture B04.
- R-23 PASS: memakai logo/font/komponen/ikon buku existing untuk navigasi fitur CMS yang diminta; tidak membuat ilustrasi atau foto.
- R-24 PASS: tautan konten dan preview merujuk route Laravel yang terdaftar dan berhasil dibuka.
- R-25 PASS: teks label gelap #f5f5f5 di #262626 terukur 13,88:1; batas kontrol #737373 terukur 4,74:1 di putih dan 3,19:1 di gelap untuk batas non-teks. Warna text/error memakai palette Flux/Tailwind existing; tidak mengklaim audit seluruh tema aplikasi.
- R-26 PASS: form menuju layanan authoring nyata; kontrol publish menolak konten tidak valid. Reorder/arsip tercakup tes layanan/API; tombol batas urutan disabled. Import apply yang belum siap tidak ditawarkan CMS.
- R-27 PASS: daftar kosong mempunyai instruksi; wire:loading memberi status; error validation/konflik memberi ringkasan dan mempertahankan isian, dibuktikan tes CMS.
- R-28 PASS: tidak ada FAQ tambahan.
- R-32 PASS: form memakai elemen native/Flux; browser Tab berpindah dari judul ke slug dan outline solid terlihat. Dialog arsip memakai konfirmasi native.
- R-33 PASS: perubahan tersimpan pada source Blade/PHP; tidak ada patch runtime DOM atau CSS melalui browser.
- R-34 PASS: kedua tema diperiksa; label gelap yang sempat gagal dibetulkan dan diamati ulang.
- R-35 PASS: build dijalankan; browser menelusuri kelas/modul/materi dan publish bacaan/modul/kelas sampai sukses, lalu preview. Kontrak tulis, error, arsip, reorder, upload dan izin juga diuji lewat integrasi PHP/Livewire. Bukti screenshot dan hasil tes tercatat pada laporan.
- R-36 PASS: tidak ada klaim DRM, kepatuhan, performa, atau pemutaran perangkat yang belum dibuktikan.
- R-37 PASS: arah mempertahankan panel admin VBAT/Flux dan dials diumumkan sebelum menulis view.
- R-38 PASS: teks menjelaskan perilaku aktual; penanda preview secara eksplisit belum memberi akses publik dan konten contoh diberi label fixture.
- R-39 PASS: label native `for`/ID stabil; browser melaporkan 0 field CMS tanpa label setelah perpindahan tab. Fokus, cursor, loading dan disabled tersedia; tidak menambah animasi dekoratif.
- R-40 PASS: video preview memakai aspect ratio dan lazy iframe; tidak menambah script pemblokir render atau klaim performa. Tidak ada hero media.

## Purpose gate

- R-01 PASS: tidak menambah gradient/glow.
- R-04 PASS: ikon buku existing menandai pintu masuk pengelolaan belajar; bukan dekorasi.
- R-06 PASS: font Instrument Sans existing dipertahankan untuk konsistensi aplikasi.
- R-07 PASS: tidak menambah background grid.
- R-08 PASS: tombol Naik/Turun memakai kata kerja; tidak ada panah dekoratif berulang.
- R-09 PASS: status berupa teks aktual Draft/Terbit/Arsip, tanpa badge pemasaran.
- R-10 PASS: tidak menambah glassmorphism.
- R-12 PASS: tidak menambah bayangan kartu; panel mengikuti batas struktur form/daftar.
- R-13 PASS: tidak menambah glow.
- R-14 PASS: daftar mengikuti jumlah modul/materi aktual, bukan kartu fitur seragam.
- R-19 PASS: tidak menambah animasi template.
- R-22 PASS: tidak menambah ilustrasi generik.

## Liveliness dan craftsmanship

- Dials PASS: 1/2/1 konsisten dengan alat operasional, tidak ada animasi baru.
- Focal point PASS: judul konteks dan tahap aktif membedakan kelas/modul/materi.
- Whitespace PASS: jarak mengelompokkan input, aksi dan daftar, lalu reflow ke satu kolom saat sempit.
- Accent PASS: tahap aktif dan aksi simpan memakai kontras primer Flux, dengan identitas VBAT existing.
- Identity PASS: urutan kelas/modul/materi, kode unit dan konteks paket VBAT hadir pada konten sebenarnya.
- Design Read PASS: dideklarasikan sebelum penulisan view; tidak mengganti bahasa visual proyek.
- C-1 PASS: form/daftar berdampingan untuk edit dan pilih resource, menumpuk pada lebar sempit.
- C-2 PASS: layanan dan route konkret melayani kontrol yang ditawarkan; layanan gagal tidak dilaporkan sukses.
- C-3 PASS: semua bagian mengikuti pekerjaan admin, tanpa blok pemasaran tambahan.
- C-4 PASS: state kosong/loading/error, konflik revisi, tema dan reflow ditinjau dalam scope bukti di atas.
- C-5 PASS: hanya fixture dan hasil tes nyata yang dipakai sebagai bukti.
- R-05 PASS: komposisi mengikuti editor/daftar, bukan template landing.
- R-11 PASS: radius kecil komponen existing, tidak membuat seluruh elemen pill.
- R-15 PASS: aksi memakai Simpan kelas/modul/materi, Kelola, Arsipkan dan Pratinjau.
- R-16 PASS: tidak menambah kalimat promosi AI.
- R-20 PASS: tidak menambah emoji dekoratif.
- R-21 PASS: tidak menambah deskripsi produk abstrak; pesan gagal menyebut tindakan pemulihan.
- R-29 PASS: kontrol memakai komponen Flux dan pola field yang sama di tiga editor.
- R-30 PASS: tipe/status mengikuti nilai canonical backend, tanpa label paket yang tidak didukung schema.
- R-31 PASS: dark/light mengikuti aplikasi existing, dengan teks CMS eksplisit agar tidak hitam di tema gelap.

Bukti ini tidak menggantikan uji staging, zoom/perangkat tambahan, validasi konten/video nyata atau pemeriksaan aksesibilitas bagian aplikasi di luar CMS/preview. Batas rilis dan dependensi tetap tercatat pada laporan B04.
