# VBAT3 — BACKLOG PER FITUR (Developer & Sprint Ready)
**Tanggal:** 5 Oktober 2026  
**Status:** Sprint Ready / Ready for Development  
**Total Lingkup:** 4 Modul, 12 Fitur  

---

## 📌 DAFTAR ISI & STRUKTUR MODUL

| Modul | Kode Tiket | Nama Fitur | Komponen | Prioritas |
|---|---|---|---|---|
| **[SPONSOR] Manajemen Sponsor** | `SPONSOR-01` | Matriks Benefit per Tier (Single-Page Matrix) | Backend (Laravel / Livewire) | P0 (Critical) |
| | `SPONSOR-02` | Dashboard & Kelola Sponsor | Backend (Livewire Admin) | P1 (High) |
| | `SPONSOR-03` | Logo Sponsor (Admin & Portal Sponsor) | Backend & Portal Sponsor | P1 (High) |
| | `SPONSOR-04` | Bobot Tayang Otomatis dari Tier (Share of Voice) | Backend API & Database | P1 (High) |
| | `SPONSOR-05` | Batas Kuota & Aturan Unggah Bulanan | Backend & Portal Sponsor | P1 (High) |
| | `SPONSOR-06` | Alur Best Deal Mandiri & Rotasi Otomatis | Backend & Portal Sponsor | P1 (High) |
| **[EVENT] Event & Diskon** | `EVENT-01` | Penyederhanaan Alur Publish Event | Backend (Livewire / API) | P1 (High) |
| | `EVENT-02` | Carousel Slider Multi-Event di Beranda & Shop | Flutter App & Backend API | P2 (Medium) |
| **[APP] Perbaikan Aplikasi** | `APP-01` | Navigasi Tombol Best Deal di Beranda | Flutter Mobile App | P1 (High) |
| | `APP-02` | Standardisasi Tampilan Nama & Ikon Tier | Flutter Mobile App | P2 (Medium) |
| **[ADMIN] Perbaikan Tampilan Admin** | `ADMIN-01` | Perapian Visual & Hirarki Header Panel Admin | Blade / Admin View | P2 (Medium) |
| | `ADMIN-02` | Sanitasi Gender (Hanya Laki-laki & Perempuan) | Migration, Model & Form | P1 (High) |

---

# 🏢 MODUL 1: [SPONSOR] MANAJEMEN SPONSOR

> **Letak di Menu Admin:** Menu kiri **Manajemen Sponsor** (Satu menu terpadu).
> 
> **Struktur Navigasi:**
> ```
> Manajemen Sponsor
>    ├─ Daftar Sponsor ── klik sponsor ──┬─ Profil
>    │                                   ├─ Benefit (khusus sponsor ini)
>    │                                   └─ Produk
>    └─ Benefit per Tier (Pengaturan semua tier sekaligus)
> ```

---

### 🎫 SPONSOR-01 — MATRIKS BENEFIT PER TIER

* **Tujuan:** Admin dapat mengubah seluruh nilai benefit untuk keenam tier sponsor dalam satu tabel matriks terpadu tanpa membuka tier satu per satu.
* **Bentuk Hasil:** Halaman tabel matriks (Kolom = 6 Tier, Baris = 14 Benefit).

#### Struktur Matriks & Input:
| Baris Benefit | Tipe Input | Aturan Tampilan |
|---|---|---|
| Kuota Produk | Angka / Checkbox `∞` | Checkbox "Tidak terbatas" (angka menjadi `∞` dan disabled saat dicentang). |
| Outbound Marketplace | Checkbox (Ya/Tidak) | Centang hijau jika aktif. |
| Storefront Khusus | Checkbox (Ya/Tidak) | Centang hijau jika aktif. |
| Best Deal Slot | Angka / Checkbox `∞` | Checkbox "Tidak terbatas". |
| Durasi Best Deal | Angka (Hari) | Disabled `—` jika slot = 0. |
| Prioritas Best Deal | Dropdown Pilihan | Standar, Top 5, Top 3, Slot #1. |
| Hero Slider | Angka (Slot) | Disabled `—` jika tidak berhak. |
| Posisi Hero | Dropdown Pilihan | Slide 5-6, Slide 3-4, Slide 1-2, Slide #1. |
| Share of Voice | Angka (0–100%) | Persentase probabilitas tayang. |
| Popup + CTA | Checkbox (Ya/Tidak) | Centang jika tier berhak modal pop-up. |
| In-Feed Banner | Angka (Slot) | Jumlah banner di sela katalog. |
| Jarak In-Feed | Angka (Produk) | Frekuensi kemunculan tiap N produk. |
| Push Broadcast | Angka / Checkbox `∞` | Checkbox "Tidak terbatas". |
| Badge | Teks | Label badge yang tampil di produk & profil. |

#### Aturan Khusus & Reset:
1. **Fitur Tidak Terbatas (∞):** Tersedia pada `Kuota Produk`, `Best Deal Slot`, dan `Push Broadcast`. Saat centang dilepas, nilai angka terakhir wajib muncul kembali (state remembered).
2. **Sel Tidak Berlaku (`—`):** Sel berwarna abu-abu dengan tanda `—` dan tidak dapat diubah (contoh: Hero Slider milik Kontribusi).
3. **Reset per Baris:** Tombol reset di ujung kiri baris untuk mengembalikan benefit tersebut ke nilai awal.
4. **Kembalikan Semua:** Tombol reset seluruh tabel dengan dialog konfirmasi diff perubahan sebelum dieksekusi.
5. **Persistensi:** Tombol *Simpan Semua* di bawah tabel menyimpan seluruh perubahan ke database.

* **Acceptance Criteria (DoD):**
  - [ ] Keenam tier (`Kontribusi`, `Bronze`, `Silver`, `Gold`, `Platinum`, `Diamond`) tampil berdampingan dalam 1 layar.
  - [ ] Perubahan nilai langsung tersimpan ke database dan mempengaruhi kuota serta aturan tayang secara real-time.
  - [ ] Tidak ada nilai benefit yang di-hardcode di kode program.

---

### 🎫 SPONSOR-02 — DASHBOARD & KELOLA SPONSOR

* **Tujuan:** Satu tempat terpadu untuk melihat, memfilter, dan mengelola kondisi akun seluruh sponsor.
* **Bentuk Hasil:** Halaman daftar sponsor berbasis kartu informatif berisi:
  - Logo sponsor
  - Nama sponsor
  - Badge Tier aktif
  - Status (Aktif / Nonaktif)
  - Indikator kuota produk bulan kalender berjalan (contoh: `Terpakai 12 dari 15`)
* **Fitur & Aksi Admin:**
  - Pencarian real-time & Filter berdasarkan Tier dan Status.
  - Tombol edit data profil.
  - Quick-switch Tier: Mengubah tier sponsor langsung dari dashboard; kuota otomatis menyesuaikan tier baru.
  - Toggle Aktif/Nonaktif: Menonaktifkan sponsor yang masa kontraknya habis tanpa menghapus riwayat datanya.
  - Hapus (Soft Delete) dengan konfirmasi keamanan.
* **Acceptance Criteria (DoD):**
  - [ ] Admin dapat mengganti tier dan status sponsor tanpa berpindah halaman.
  - [ ] Sponsor nonaktif otomatis hilang dari tampilan publik aplikasi mobile.
  - [ ] Tidak ada field tanggal kontrak otomatis (dikelola manual oleh admin).

---

### 🎫 SPONSOR-03 — LOGO SPONSOR

* **Tujuan:** Setiap sponsor memiliki logo resmi yang ditampilkan secara konsisten di aplikasi dan admin.
* **Hak Akses Unggah:**
  - **Admin VBAT:** Dapat mengunggah/mengganti logo sponsor mana pun dari form admin.
  - **Mitra Sponsor:** Dapat mengunggah logo miliknya sendiri dari menu profil di Portal Sponsor.
* **Spesifikasi Teknis:**
  - Validasi format berkas: JPG / PNG, maksimal 2MB. Berkas non-gambar (misal PDF) wajib ditolak dengan pesan jelas.
  - Disimpan ke public storage dan dapat diakses dengan header CORS.
  - Tampil di kartu mitra, Best Deal, dan halaman sponsor pada aplikasi mobile.
  - Field form co-branding header dan splash **resmi dihapus**.
* **Acceptance Criteria (DoD):**
  - [ ] Logo berhasil diunggah oleh admin maupun sponsor.
  - [ ] Logo tampil pada aplikasi Flutter tanpa kendala CORS / 404.

---

### 🎫 SPONSOR-04 — BOBOT TAYANG OTOMATIS DARI TIER

* **Tujuan:** Frekuensi kemunculan banner sponsor ditentukan secara adil dari Share of Voice tier, bukan angka manual.
* **Kondisi Lama:** Form sponsor memiliki field input manual "Bobot Frekuensi Tayang".
* **Bentuk Hasil:**
  - Field input bobot manual **dihapus** dari form sponsor.
  - Sistem otomatis membaca nilai `share_of_voice` dari tier sponsor terkait (hasil pengaturan SPONSOR-01).
  - Algoritma rotasi banner di API `/banners/hero`, `/banners/cards`, dan `/banners/popup` menggunakan pembobotan otomatis ini.
* **Acceptance Criteria (DoD):**
  - [ ] Form sponsor bersih dari input bobot manual.
  - [ ] Banner dari tier ber-Share of Voice lebih tinggi (misal Platinum 50% vs Silver 20%) tampil lebih sering secara proporsional.

---

### 🎫 SPONSOR-05 — BATAS KUOTA & ATURAN UNGGAH BULANAN

* **Tujuan:** Menegakkan batasan kuota produk sponsor per bulan kalender sesuai tier aktifnya.
* **Bentuk Hasil:**
  - Portal sponsor menampilkan sisa kuota: `"Terpakai X dari Y produk bulan ini"`.
  - Upaya unggah produk melebihi kuota otomatis ditolak dengan pesan informatif.
  - Kuota dihitung per bulan kalender dan di-reset setiap tanggal 1 pukul 00:00 WIB.
  - Jika admin menaikkan tier sponsor di tengah bulan, batas kuota langsung bertambah secara instan.
* **Acceptance Criteria (DoD):**
  - [ ] Sponsor dengan kuota terpenuhi tidak dapat menambah produk baru sampai kuota ditambah atau masuk bulan baru.
  - [ ] Tier Diamond dengan kuota `∞` tidak pernah terhalang batas kuota.

---

### 🎫 SPONSOR-06 — ALUR BEST DEAL MANDIRI & ROTASI OTOMATIS

* **Tujuan:** Sponsor dapat mengajukan produknya sendiri ke Best Deal; sistem mengatur penempatan dan rotasi tanpa campur tangan manual admin.
* **Bentuk Hasil:**
  - Tersedia tombol *"Ajukan ke Best Deal"* pada setiap produk di Portal Sponsor.
  - **Tier Kontribusi:** Tombol tidak aktif (disabled) karena memiliki kuota 0 slot.
  - **Jika kuota slot tier sponsor masih ada:** Produk langsung tayang di Best Deal.
  - **Jika slot penuh:** Produk masuk ke antrean rotasi otomatis.
  - **Prioritas Tampilan:** Mengikuti hierarki tier (Diamond Slot #1 > Platinum Top 3 > Gold Top 5 > Silver/Bronze Standar).
  - Sponsor dapat melihat status produk: *Menunggu*, *Tayang*, atau *Selesai*.
* **Acceptance Criteria (DoD):**
  - [ ] Penayangan Best Deal berjalan otomatis tanpa approval admin.
  - [ ] Kontribusi tidak dapat mengajukan produk ke Best Deal.

---

# 🎟️ MODUL 2: [EVENT] EVENT & DISKON

> **Letak di Menu Admin:** Menu kiri **Event & Diskon Shop**.

---

### 🎫 EVENT-01 — PENYEDERHANAAN ALUR PUBLISH EVENT

* **Tujuan:** Mengeliminasi konflik logika publish event agar jadwal aktif berjalan akurat dan transparan.
* **Kondisi Lama:** Pilihan jadwal dan status aktif saling bertabrakan, menyebabkan event berjadwal tidak aktif saat disimpan.
* **Bentuk Hasil:** Antarmuka formulir menyediakan dua mode eksklusif:
  1. **Mode "Aktifkan Sekarang":**
     - Field tanggal & jam mulai disembunyikan.
     - Hanya mengisi masa berakhir event.
     - Setelah disimpan, status event langsung aktif (`is_active = true`) dan tayang di aplikasi.
  2. **Mode "Pakai Jadwal Tanggal":**
     - Field tanggal mulai dan berakhir diisi lengkap.
     - Event tersimpan dalam status terjadwal dan otomatis aktif saat waktu server menyentuh waktu mulai.
* **Catatan Teknis:** Pastikan sinkronisasi zona waktu server dan panel admin menggunakan `Asia/Jakarta` (WIB).
* **Acceptance Criteria (DoD):**
  - [ ] Mode "Aktifkan Sekarang" langsung memunculkan event di aplikasi tanpa menunggu jadwal.
  - [ ] Mode "Pakai Jadwal Tanggal" otomatis aktif pada waktu yang ditentukan tanpa intervensi manual.

---

### 🎫 EVENT-02 — CAROUSEL SLIDER MULTI-EVENT DI APLIKASI

* **Tujuan:** Mendukung penayangan beberapa event promo aktif secara bergantian pada banner aplikasi.
* **Kondisi Lama:** Banner hanya mampu menampilkan 1 event tunggal.
* **Bentuk Hasil:**
  - Banner event di Beranda dan Shop memuat seluruh event yang sedang aktif dalam bentuk slider carousel.
  - Bergeser otomatis (auto-play interval 4–5 detik).
  - Mendukung geser manual (swipe gesture).
  - Dilengkapi indikator titik (*dot indicators*) aktif.
* **Acceptance Criteria (DoD):**
  - [ ] Jika terdapat ≥ 2 event aktif, seluruhnya tampil bergantian di banner aplikasi.
  - [ ] Banner tidak saling menimpa atau error saat dimuat.

---

# 📱 MODUL 3: [APP] PERBAIKAN APLIKASI FLUTTER

---

### 🎫 APP-01 — NAVIGASI TOMBOL BEST DEAL DI BERANDA

* **Tujuan:** Mengarahkan klik kartu Best Deal di Beranda langsung ke halaman khusus Best Deal.
* **Kondisi Lama:** Klik Best Deal mengarahkan pengguna ke halaman Shop umum.
* **Bentuk Hasil:**
  - Event `onTap` pada kartu Best Deal di Beranda mengarahkan navigasi ke `BestDealsPage` (route `/shop/best-deals`).
* **Acceptance Criteria (DoD):**
  - [ ] Menekan kartu Best Deal di Beranda membuka halaman Best Deals, bukan Shop.

---

### 🎫 APP-02 — STANDARDISASI TAMPILAN NAMA & IKON TIER

* **Tujuan:** Menampilkan tingkatan tier sponsor dengan ringkas, bersih, dan dilengkapi ikon penanda.
* **Kondisi Lama:** Tertulis teks panjang seperti `"Sponsor Platinum"`.
* **Bentuk Hasil:**
  - Format teks diubah menjadi nama tier saja: `Kontribusi`, `Bronze`, `Silver`, `Gold`, `Platinum`, `Diamond`.
  - Menampilkan ikon penanda visual di samping nama tier sesuai skema warna resminya.
* **Acceptance Criteria (DoD):**
  - [ ] Seluruh kartu mitra dan badge menampilkan nama tier ringkas disertai ikon.

---

# 🖥️ MODUL 4: [ADMIN] PERBAIKAN TAMPILAN ADMIN

---

### 🎫 ADMIN-01 — KERAPIAN VISUAL & HIRARKI HEADER PANEL ADMIN

* **Tujuan:** Menghadirkan antarmuka panel admin yang bersih, lega, dan nyaman dipindai.
* **Kondisi Lama:** Terdapat judul ganda (contoh: *"Super Dashboard — Analitik & Metrik"* dan *"Dashboard Analitik Super Admin & Owner"* pada satu halaman yang sama) serta margin komponen terlalu padat.
* **Bentuk Hasil:**
  - Menggunakan satu judul tunggal yang jelas per halaman.
  - Menata ulang whitespace, margin, dan padding antar-komponen card.
  - Menempatkan kartu metrik performa utama (KPI cards) rapi di posisi teratas.
* **Acceptance Criteria (DoD):**
  - [ ] Tidak ada judul berulang di seluruh halaman admin.
  - [ ] Tata letak visual nyaman digunakan sesuai persetujuan Product Owner.

---

### 🎫 ADMIN-02 — SANITASI GENDER (HANYA LAKI-LAKI & PEREMPUAN)

* **Tujuan:** Menyederhanakan pilihan gender hanya menjadi Laki-laki dan Perempuan di seluruh sistem.
* **Kondisi Lama:** Tersedia opsi "Lainnya" / "Other" di form, migration, dan dashboard analitik.
* **Bentuk Hasil:**
  - Form profil/pengguna hanya menampilkan 2 opsi: `Laki-laki` dan `Perempuan`.
  - Panel analitik demografi dashboard hanya memuat 2 kategori gender (baris "Lainnya 0 (0%)" dihapus total).
  - Validasi backend diubah: `in:male,female,laki-laki,perempuan`.
* **Acceptance Criteria (DoD):**
  - [ ] Pilihan dan statistik "Lainnya" bersih dari form dan dasbor analitik.

---

# 📖 LAMPIRAN: MATRIKS ATURAN BENEFIT AWAL

> **Prinsip Utama:** Dokumen ini merupakan **nilai awal (default baseline)** yang disimpan di database. Admin VBAT dapat mengubah seluruh nilai ini kapan saja via menu **Benefit per Tier** tanpa deploy ulang kode.

```text
┌──────────────────────┬────────┬────────┬────────┬────────┬──────────┬─────────┐
│ Benefit              │Kontri. │Bronze  │Silver  │Gold    │Platinum  │Diamond  │
├──────────────────────┼────────┼────────┼────────┼────────┼──────────┼─────────┤
│ Kuota Produk         │ [ 5  ] │ [ 15 ] │ [ 35 ] │ [ 75 ] │ [ 150  ] │ [∞] ☑   │
│ Outbound Marketplace │  [✓]   │  [✓]   │  [✓]   │  [✓]   │   [✓]    │  [✓]    │
│ Storefront Khusus    │  [ ]   │  [ ]   │  [✓]   │  [✓]   │   [✓]    │  [✓]    │
│ Best Deal Slot       │ [ 0  ] │ [ 1  ] │ [ 3  ] │ [ 8  ] │ [ 15   ] │ [∞] ☑   │
│ Durasi Best Deal     │   —    │ [ 3 ]  │ [ 7 ]  │ [ 14 ] │ [ 21   ] │ [ 30 ]  │
│ Prioritas Best Deal  │   —    │Standar │Standar │ Top 5  │  Top 3   │ Slot #1 │
│ Hero Slider (slot)   │   —    │   —    │ [ 1 ]  │ [ 1 ]  │ [ 2    ] │ [ 1 ]   │
│ Posisi Hero          │   —    │   —    │ Slide  │ Slide  │  Slide   │ Slide   │
│                      │        │        │  5-6   │  3-4   │   1-2    │   #1    │
│ Share of Voice %     │ [ 0 ]  │ [ 0 ]  │ [ 20 ] │ [ 35 ] │ [ 50   ] │ [100 ]  │
│ Popup + CTA          │  [ ]   │  [ ]   │  [✓]   │  [✓]   │   [✓]    │  [✓]    │
│ In-Feed Banner       │   —    │ [ 1 ]  │ [ 2 ]  │ [ 3 ]  │ [ 4    ] │ [ 5 ]   │
│ Jarak In-Feed (produk)│  —    │ [ 24 ] │ [ 12 ] │ [ 8 ]  │ [ 6    ] │ [ 4 ]   │
│ Push Broadcast/bulan │ [ 0 ]  │ [ 0 ]  │ [ 0 ]  │ [ 1 ]  │ [ 3    ] │ [∞] ☑   │
│ Badge                │Kontri- │ Bronze │ Silver │ Gold   │ Platinum │ Diamond │
│                      │butor   │Partner │Official│ Brand  │ Premier  │  Title  │
└──────────────────────┴────────┴────────┴────────┴────────┴──────────┴─────────┘
```

---

# 🛑 LARANGAN & ATURAN PENGEMBANGAN (DO NOTS)

1. **Dilarang keras melakukan hardcode** nilai benefit, batas kuota, maupun daftar 6 tier di kode Flutter atau Laravel. Semua wajib dibaca dinamis dari database.
2. **Dilarang** menyimpan nilai benefit di berkas konfigurasi statis (seperti `.env` atau `config/*.php`).
3. **Dilarang** menyisakan field input bobot frekuensi manual pada form sponsor — bobot wajib diturunkan otomatis dari tier Share of Voice.
4. **Dilarang** memunculkan opsi gender "Lainnya" pada form registrasi, edit profil, dan panel analitik.
5. **Dilarang** membangun antarmuka untuk fitur co-branding header dan splash (fitur ini resmi dibatalkan).

---

# 📋 ATURAN MODERASI BANNER & KAMPANYE

| Tahap | Aksi Sponsor / Admin | Status Sistem | Dampak Aplikasi |
|---|---|---|---|
| **1. Diajukan** | Sponsor mengunggah materi banner/produk | `draft` / `pending` | Belum tampil di aplikasi |
| **2. Ditinjau** | Admin memeriksa kesesuaian materi iklan | `under_review` | Belum tampil di aplikasi |
| **3. Disetujui** | Admin menekan tombol Approve | `active` / `approved` | Langsung tampil sesuai jadwal & tier |
| **4. Ditolak** | Admin menekan tombol Reject | `rejected` | Tidak tampil; sponsor melihat status Ditolak |

*Catatan:* Pada penolakan banner, sistem tidak mewajibkan pengisian teks alasan penolakan pada admin.

---

# ✅ DEFINITION OF DONE (DOD) CHECKLIST

- [ ] Seluruh migrasi database dan seeder data awal (`TierBenefitSeeder`) berjalan sukses.
- [ ] Matriks benefit di admin dapat diedit, di-reset per baris, di-reset all, dan tersimpan permanen.
- [ ] Perubahan benefit di admin langsung tercermin pada kuota dan perilaku tayang aplikasi mobile.
- [ ] Semua endpoint gambar & video (`/storage/` dan `/assets/`) menyertakan header `Access-Control-Allow-Origin: *`.
- [ ] Hasil pengujian `flutter analyze` dan unit/feature test backend berstatus **0 error**.
