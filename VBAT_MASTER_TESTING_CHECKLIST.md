# 📋 MASTER TESTING & QUALITY ASSURANCE CHECKLIST — VBAT PONSEL
**Target Ekosistem:** Flutter Mobile App (`vbat-ponsel-main`) & Web Admin Laravel (`VBAT-WEB`)  
**Lingkungan Uji:** Staging / Production Server Contabo (HTTPS) & Device Fisik Android / iOS

---

## 📱 BAGIAN 1: PENGUJIAN APLIKASI MOBILE (APK & UX)

| ID | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :--- | :--- | :--- | :---: |
| **MOB-01** | **Clean Install APK Release** | Install file `app-release.apk` pada HP Android fisik yang belum pernah terpasang VBAT. | Aplikasi terpasang sempurna, ikon & nama aplikasi muncul, dan saat dibuka langsung masuk ke Splash/Onboarding. | [ ] |
| **MOB-02** | **Upgrade APK (In-place Update)** | Install APK versi baru di atas APK versi sebelumnya tanpa melakukan uninstall data. | Database lokal & SharedPreferences tidak corrupt; session pengguna lama tidak ter-reset secara liar. | [ ] |
| **MOB-03** | **Onboarding Tour & Flow** | Buka aplikasi untuk pertama kali, lewati slide pengenalan tour aplikasi. | Transisi mulus, tombol "Mulai" / "Lewati" berfungsi, tidak ada force close/layar putih. | [ ] |
| **MOB-04** | **Mode Tamu (Guest Mode)** | Klik "Masuk sebagai Tamu" tanpa melakukan registrasi/login. | Masuk ke Beranda; banner hero, video promo, dan daftar produk toko tampil normal. | [ ] |
| **MOB-05** | **Auth Guard Tamu** | Dalam mode Guest, klik tab Akun, Wishlist, atau Pengaturan. | Aplikasi secara otomatis mengalihkan (*redirect*) pengguna ke halaman Login / Register. | [ ] |
| **MOB-06** | **Stabilitas Beranda** | Scroll halaman beranda dari atas ke bawah, tonton video promo, buka kategori. | Beranda stabil 60 FPS, tidak ada crash, pemutar video hero autoplay & mute otomatis berjalan lancar. | [ ] |
| **MOB-07** | **APP-01: Navigasi Tombol Best Deal di Beranda** | Di halaman Beranda, klik kartu Best Deal, tombol badge header "BEST DEAL", atau link "Lihat Semua". | Aplikasi langsung membuka halaman khusus `BestDealsPage` (route `/best-deals` / `/shop/best-deals`), BUKAN ke tab Shop katalog umum. | [ ] |
| **MOB-08** | **APP-02: Standardisasi Tampilan Nama & Ikon Tier** | Periksa kartu sponsor di slider beranda, kartu mitra shop, modal detail sponsor, dan kartu produk Best Deal. | Nama tier ditampilkan ringkas tanpa prefix panjang (contoh: "Platinum", bukan "Sponsor Platinum" / "SPONSOR • PLATINUM") lengkap dengan ikon visual resmi & warna tema tier. | [ ] |

---

## 🌐 BAGIAN 2: PENGUJIAN API SERVER CONTABO (HTTPS & SESSION)

| ID | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :--- | :--- | :--- | :---: |
| **API-01** | **Koneksi HTTPS Contabo** | Buka endpoint API via browser/Postman: `https://api.domain-contabo.com/api/v1/health` (atau `/feed/home`). | Mengembalikan respon HTTP `200 OK`, sertifikat SSL valid (Let's Encrypt / Cloudflare), gembok hijau aktif. | [ ] |
| **API-02** | **Autentikasi Login API** | Kirim request `POST /api/v1/auth/login` dengan email dan password terdaftar. | Menerima respon JSON `200 OK` berisi token Bearer Sanctum dan objek profil user lengkap. | [ ] |
| **API-03** | **Session Persistence** | Login di aplikasi mobile, tutup aplikasi secara paksa (*kill app* dari task manager), lalu buka kembali. | Pengguna tetap dalam kondisi login (tidak perlu login ulang). Token tersimpan aman di secure storage. | [ ] |
| **API-04** | **Logout & Token Invalidation** | Klik tombol Logout di tab Akun Mobile atau Web Admin. | Respon `200 OK`; token di tabel `personal_access_tokens` dihapus/di-revoke; kembali ke halaman login. | [ ] |

---

## 👥 BAGIAN 3: PENGUJIAN HAK AKSES 4 ROLE (AUTHORIZATION GUARD)

| Role | Akun Uji | Hak Akses yang Diizinkan | Pembatasan / Larangan (Must Be Denied) | Status |
| :--- | :--- | :--- | :--- | :---: |
| **1. Super Admin VBAT** | `admin@vbatponsel.com` | Akses 100% rute Web Admin: Manajemen Pengguna (`/admin/users`), Sponsor, Kampanye, Keuangan, Rules, & Analytics. | Tidak ada batasan. Memiliki akses root. | [ ] |
| **2. Admin VBAT (Owner/Staff)** | `owner@vbatponsel.com` | Akses Dashboard Admin, Kurasi Konten (`/admin/content`), Approval Sponsor, Feed Manager, dan Quiz Manager. | Dilarang mengubah kredensial root Super Admin atau konfigurasi environment server. | [ ] |
| **3. Mitra Sponsor** | `sponsor@braderparts.com` | Hanya bisa membuka halaman Sponsor (`/sponsor/dashboard`, `/sponsor/products`, `/sponsor/campaigns/*`). | **Ditolak HTTP 403 Forbidden** jika mencoba membuka rute `/admin/*` atau melihat data milik sponsor lain. | [ ] |
| **4. User / Siswa** | Siswa terdaftar di Mobile App | Mengakses seluruh fitur Mobile App (Belajar, Toko, KTA, Quiz) dan API publik. | **Ditolak masuk ke Web Admin Dashboard** (`/dashboard` atau `/admin/*`). | [ ] |

---

## 🛠️ BAGIAN 4: PENGUJIAN CRUD ADMIN (KELAS, MATERI, QUIZ, SPONSOR, FEED, ANALYTICS)

| ID | Modul Admin | Aksi Uji | Validasi Hasil | Status |
| :--- | :--- | :--- | :--- | :---: |
| **ADM-01** | **Manajemen Kelas & Modul** | Buat kelas baru (misal: "Kelas Master IC"), edit deskripsi & harga, arsipkan kelas. | Data tersimpan di database dan langsung muncul pada tab Belajar di Mobile App. | [ ] |
| **ADM-02** | **Upload Materi & Video** | Masukkan materi video baru, tandai video sebagai "Materi Berbayar" vs "Preview Gratis". | Video ter-link dengan benar; durasi dan deskripsi materi tampil di mobile player. | [ ] |
| **ADM-03** | **Bank Soal & Kuis** | Buat kuis evaluasi baru dengan kunci jawaban kata kunci (misal: kata kunci: `short`, `ground`, `vbat`). | Jawaban siswa di mobile dievaluasi otomatis sesuai kata kunci yang dibuat admin. | [ ] |
| **ADM-04** | **Moderasi Sponsor** | Setujui (*Approve*) atau Tolak (*Reject*) pengajuan kampanye banner dari sponsor di `/admin/campaigns`. | Kampanye yang di-*Approve* langsung tayang di mobile; yang di-*Reject* tidak tampil di feed. | [ ] |
| **ADM-05** | **Feed & Pengumuman** | Publikasikan artikel informasi baru di `/admin/feed`. | Artikel langsung muncul pada daftar Feed di Mobile App secara real-time. | [ ] |
| **ADM-06** | **Analytics Dashboard** | Buka `/admin/analytics`, verifikasi grafik impresi, rasio klik (CTR), dan sebaran demografi teknisi. | Angka statistik bertambah dinamis seiring aktivitas klik dan interaksi di aplikasi mobile. | [ ] |
| **ADM-07** | **EVENT-01: Publish Mode Eksklusif** | Di menu Event & Diskon, uji buat event mode "Aktifkan Sekarang" (hanya isi end_at) vs "Pakai Jadwal Tanggal" (start_at masa depan). | Mode "Aktifkan Sekarang" langsung berstatus "Sedang Berjalan" (hijau). Mode "Pakai Jadwal Tanggal" berstatus "Terjadwal" (biru) dan aktif otomatis saat waktu server tiba. | [ ] |
| **ADM-08** | **EVENT-02: Multi-Event Carousel Slider** | Aktifkan 2 atau lebih event diskon sekaligus, lalu buka Beranda dan Shop di aplikasi Flutter. | Banner event tampil dalam bentuk Carousel Slider yang otomatis bergeser tiap 4.5 detik, mendukung swipe manual, memiliki dot indicator, dan tidak bertumpuk/error. | [ ] |
| **ADM-09** | **ADMIN-01: Hirarki Header Tunggal Admin** | Buka rute `/dashboard`, `/admin/sponsors`, `/admin/products`, `/admin/campaigns`, `/admin/events`, `/admin/best-deals`. | Setiap halaman memiliki satu judul tunggal yang bersih (tidak ada judul ganda bertumpuk), whitespace lega, dan KPI cards rapi di posisi teratas. | [ ] |
| **ADM-10** | **ADMIN-02: Sanitasi Gender (Laki-laki & Perempuan)** | Buka dashboard analitik (grafik rasio gender) dan uji endpoint API `/api/v1/user/demographics`. | Panel rasio gender hanya menampilkan 2 baris (Laki-laki dan Perempuan, baris "Lainnya" hilang total); API menolak input selain `male`, `female`, `Laki-laki`, `Perempuan`. | [ ] |

---

## 🏢 BAGIAN 5: PENGUJIAN MODUL SPONSOR LENGKAP (SPONSOR-01 S/D SPONSOR-06)

### 📊 5.1 MATRIKS BENEFIT TIER SPONSOR (SPONSOR-01 — 84 SEL: 6 TIER × 14 KOLOM BENEFIT)
> **Halaman Uji:** `/admin/sponsors/tiers` (Tab "Matriks Benefit Tier")  
> **Tujuan:** Memastikan 14 kolom benefit pada 6 tingkatan tier (Diamond, Platinum, Gold, Silver, Bronze, Kontribusi) dapat diatur, ditampilkan, dan berfungsi akurat.

| No | Kolom Benefit | Tipe Data | Nilai Baseline Default | Prosedur / Cara Menguji Kolom Ini | Hasil yang Diharapkan (Expected Result) | Status |
| :---: | :--- | :---: | :--- | :--- | :--- | :---: |
| **01** | **Kuota Produk** | Integer / ∞ | Kontribusi: 5<br>Bronze: 15<br>Silver: 35<br>Gold: 75<br>Platinum: 150<br>Diamond: ∞ | 1. Klik sel angka kuota.<br>2. Ubah angka (misal Silver dari 35 ke 40).<br>3. Klik ikon centang / blur input.<br>4. Untuk Diamond, pastikan muncul badge `∞ Unlimited`. | Nilai baru langsung tersimpan (AJAX/Livewire tanpa reload). Diamond terkunci sebagai unlimited dan tidak membatasi katalog produk. | [ ] |
| **02** | **Outbound Marketplace** | Boolean (Toggle) | Semua Tier: **Aktif (ON)** | 1. Klik switch toggle pada tier Kontribusi menjadi OFF.<br>2. Buka portal sponsor Kontribusi dan coba simpan link Shopee/Tokopedia.<br>3. Kembalikan toggle ke ON. | Saat OFF, form produk menonaktifkan input marketplace. Saat ON, link Shopee & Tokopedia wajib diisi salah satu. | [ ] |
| **03** | **Storefront Khusus** | Boolean (Toggle) | Kontribusi: OFF<br>Bronze: OFF<br>Silver - Diamond: **ON** | 1. Buka halaman aplikasi mobile pada tab toko mitra.<br>2. Verifikasi profil sponsor Silver, Gold, Platinum, Diamond memiliki tab profil storefront khusus.<br>3. Periksa profil Bronze/Kontribusi. | Sponsor Silver ke atas memiliki halaman etalase merek khusus. Kontribusi & Bronze hanya tampil di list produk umum. | [ ] |
| **04** | **Best Deal (Slot)** | Integer / 0 / ∞ | Kontribusi: 0<br>Bronze: 1<br>Silver: 3<br>Gold: 8<br>Platinum: 15<br>Diamond: ∞ | 1. Cek nilai Kontribusi: harus `0` dan berstatus disabled.<br>2. Cek Bronze (1 slot), Silver (3 slot), dst.<br>3. Ubah angka slot Silver menjadi 5. | Kontribusi tidak memiliki jatah slot (tombol terkunci). Diamond memiliki slot bebas tanpa batasan kuota. | [ ] |
| **05** | **Durasi Best Deal** | Integer (Hari) / — | Kontribusi: —<br>Bronze: 3 hari<br>Silver: 7 hari<br>Gold: 14 hari<br>Platinum: 21 hari<br>Diamond: 30 hari | 1. Pastikan Kontribusi menampilkan badge strip `—` (tidak aktif).<br>2. Ubah durasi tayang Gold dari 14 hari menjadi 10 hari.<br>3. Ajukan produk Best Deal pada sponsor Gold. | Tanggal kedaluwarsa (`end_at`) promosi Best Deal otomatis terhitung sesuai durasi hari tier sponsor. | [ ] |
| **06** | **Prioritas Best Deal** | String Dropdown | Kontribusi: —<br>Bronze/Silver: Standar<br>Gold: Top 5<br>Platinum: Top 3<br>Diamond: Slot #1 | 1. Verifikasi label prioritas masing-masing tier pada tabel.<br>2. Cek urutan produk pada `/api/v1/shop/best-deals`. | Produk dari sponsor Diamond selalu berada di posisi terdepan (#1), disusul Platinum (Top 3), Gold (Top 5), lalu Standar. | [ ] |
| **07** | **Hero Slider (Slot)** | Integer / — | Kontribusi & Bronze: —<br>Silver: 1 slot<br>Gold: 1 slot<br>Platinum: 2 slot<br>Diamond: 1 slot | 1. Verifikasi Kontribusi & Bronze menampilkan strip `—`.<br>2. Buka form pengajuan kampanye Hero pada sponsor Bronze. | Sponsor Bronze/Kontribusi tidak memiliki hak membuat banner Hero Slider (opsi placement Hero diblokir). | [ ] |
| **08** | **Posisi Hero Slider** | String | Kontribusi/Bronze: —<br>Silver: Slide 5-6<br>Gold: Slide 3-4<br>Platinum: Slide 1-2<br>Diamond: Slide #1 | 1. Pasang banner hero aktif untuk masing-masing tier.<br>2. Panggil API `/api/v1/banners/hero` atau buka Beranda mobile. | Banner Diamond selalu tayang di slide pertama (`order: 1`), Platinum di slide 1-2, Gold di slide 3-4, dan Silver di slide akhir. | [ ] |
| **09** | **Share of Voice (%)** | Persentase (0-100) | Kontribusi/Bronze: 0%<br>Silver: 20%<br>Gold: 35%<br>Platinum: 50%<br>Diamond: 100% | 1. Ubah nilai Share of Voice Platinum menjadi 60%.<br>2. Cek apakah field `weight` pada sponsor Platinum otomatis berubah menjadi 60. | Nilai SOV langsung tersinkronisasi ke bobot tayang kampanye tanpa perlu input manual. | [ ] |
| **10** | **Popup + CTA** | Boolean (Toggle) | Kontribusi & Bronze: OFF<br>Silver - Diamond: **ON** | 1. Login sebagai sponsor Bronze, coba buat banner Popup.<br>2. Login sebagai sponsor Gold, buat banner Popup. | Sponsor Bronze ditolak membuat popup banner. Sponsor Gold berhasil mempublikasikan banner popup dengan tombol CTA. | [ ] |
| **11** | **In-Feed Banner (Slot)** | Integer / — | Kontribusi: —<br>Bronze: 1<br>Silver: 2<br>Gold: 3<br>Platinum: 4<br>Diamond: 5 | 1. Uji batas pembuatan banner in-feed di portal sponsor.<br>2. Pastikan sponsor tidak bisa membuat banner infeed melebihi jumlah slot. | Jumlah kampanye in-feed aktif dibatasi sesuai kuota slot tier. | [ ] |
| **12** | **Jarak In-Feed (Produk)** | Integer (Interval) | Kontribusi: —<br>Bronze: 24<br>Silver: 12<br>Gold: 8<br>Platinum: 6<br>Diamond: 4 | 1. Scroll feed produk di aplikasi mobile.<br>2. Hitung jarak kemunculan kartu banner sponsor. | Banner sponsor Diamond disisipkan setiap interval 4 item produk, sedangkan Platinum setiap 6 item, dst. | [ ] |
| **13** | **Push Broadcast/bulan** | Integer / 0 | Kontribusi, Bronze, Silver: 0<br>Gold: 1<br>Platinum: 3<br>Diamond: 5 | 1. Buka menu Broadcast Notifikasi Sponsor.<br>2. Verifikasi kuota kirim pesan broadcast bulanan. | Sponsor Silver ke bawah tidak memiliki akses broadcast (0/bulan). Gold dapat kirim 1x, Platinum 3x, Diamond 5x. | [ ] |
| **14** | **Badge Lencana** | String Text | Diamond, Platinum, Gold, Silver, Bronze, Kontribusi | 1. Ubah teks badge (misal: "Official Diamond Partner").<br>2. Periksa tampilan kartu sponsor di mobile app. | Teks lencana resmi sponsor langsung terbarui pada detail sponsor dan kartu Best Deal di aplikasi. | [ ] |

---

### ⚙️ 5.2 FITUR SISTEM MATRIKS BENEFIT
- [ ] **Inline Editing Real-time:** Setiap perubahan nilai pada sel tabel otomatis tersimpan ke tabel `tier_benefits` via AJAX/Livewire tanpa perlu refresh halaman web.
- [ ] **Reset ke Default Awal:** Mengklik tombol *"Reset ke Default Awal"* mengembalikan ke-84 nilai sel matriks persis seperti konfigurasi awal seeder tanpa menghapus relasi sponsor.
- [ ] **Override Benefit Khusus Sponsor:** Jika admin memberikan jatah khusus pada sponsor tertentu (misal: sponsor Silver diberi kuota produk 50), nilai override tersimpan di `sponsor_benefit_overrides` tanpa mengubah master tier Silver.

---

### 🏢 5.3 PENGUJIAN OPERASIONAL SPONSOR (SPONSOR-02 S/D SPONSOR-06)

| ID | Fitur Sponsor | Skenario & Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :--- | :--- | :--- | :---: |
| **SPO-01** | **SPONSOR-02: Quick-switch Tier** | Buka `/admin/sponsors`, ubah dropdown tier dari Silver ke Platinum pada kartu sponsor Braderparts. | Tier langsung berganti ke Platinum, badge warna berubah, dan bobot Share of Voice otomatis naik ke 50%. | [ ] |
| **SPO-02** | **SPONSOR-02: Soft Delete & Status** | Klik toggle nonaktif pada sponsor; lalu uji tombol Hapus (soft delete). | Sponsor nonaktif/terhapus otomatis hilang dari API mobile publik namun data transaksi tetap utuh di database. | [ ] |
| **SPO-03** | **SPONSOR-03: Validasi Upload Logo** | 1. Upload berkas non-gambar (PDF/TXT) ke logo sponsor.<br>2. Upload gambar JPG/PNG > 2MB.<br>3. Upload gambar PNG valid 500KB. | Berkas non-gambar dan ukuran > 2MB ditolak dengan pesan jelas. Logo valid berhasil diunggah dan menghasilkan URL CORS absolut. | [ ] |
| **SPO-04** | **SPONSOR-04: Rotasi Share of Voice** | Panggil endpoint `/api/v1/banners/hero` berulang kali (10x request). | Banner dari sponsor Platinum (SOV 50%) muncul jauh lebih sering dibandingkan sponsor Silver (SOV 20%). | [ ] |
| **SPO-05** | **SPONSOR-05: Kuota Unggah Bulanan** | 1. Login sebagai sponsor Kontribusi (kuota 5 produk).<br>2. Tambah 5 produk.<br>3. Coba tambah produk ke-6. | Muncul indikator *"Terpakai 5 dari 5 produk"*, tombol tambah terkunci, dan API merespon HTTP 422 Kuota Penuh. | [ ] |
| **SPO-06** | **SPONSOR-05: Reset Kuota Kalender** | Ubah `created_at` produk menjadi bulan lalu di database. | Kuota bulan ini kembali menjadi `0` terpakai dan sponsor dapat mengunggah produk baru kembali. | [ ] |
| **SPO-07** | **SPONSOR-06: Best Deal Mandiri** | Di portal sponsor, klik tombol **"+ Ajukan Best Deal"** pada salah satu produk aktif. | Status produk seketika berubah menjadi *"Tayang di Best Deal"* dan langsung muncul di katalog Best Deals mobile tanpa persetujuan manual admin. | [ ] |
| **SPO-08** | **SPONSOR-06: Blokir Best Deal Kontribusi** | Buka katalog produk sponsor tier Kontribusi. | Tombol Best Deal disabled dengan tulisan *"Slot Best Deal (0)"*. Pengajuan via API ditolak HTTP 422. | [ ] |
| **SPO-09** | **SPONSOR-06: Penarikan Best Deal** | Klik tombol **"Tarik"** pada produk yang sedang tayang di Best Deal. | Produk seketika keluar dari program Best Deal dan tombol kembali menjadi *"+ Ajukan Best Deal"*. | [ ] |
| **SPO-10** | **Isolasi Multi-Tenancy (Anti-IDOR)** | Sponsor A mencoba mengubah produk atau kampanye milik Sponsor B via Postman API. | Server merespon dengan **HTTP 403 Forbidden** (hanya pemilik sah dan Super Admin yang diizinkan). | [ ] |

---

### 🎟️ 5.4 PENGUJIAN MODUL 2: EVENT & DISKON (EVENT-01 S/D EVENT-02)
> **Halaman Uji Web:** `/admin/events` (Livewire: `discount-event-manager`)  
> **Halaman Uji Mobile:** Tab Beranda & Tab Shop (`home_page.dart` & `shop_page.dart`)  
> **API Terkait:** `GET /api/v1/shop/events/active`

| ID | Fitur | Skenario & Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :--- | :--- | :--- | :---: |
| **EVT-01** | **EVENT-01: Publish Mode "Aktifkan Sekarang"** | 1. Buka `/admin/events`, klik *"Buat Event Baru"*.<br>2. Pilih radio mode: **"Aktifkan Sekarang"**.<br>3. Perhatikan input Tanggal Mulai: otomatis terkunci/terisi waktu sekarang.<br>4. Masukkan Tanggal Selesai (misal 3 hari ke depan).<br>5. Simpan event. | 1. Event langsung dibuat dengan status badge **"Sedang Berjalan" (Hijau)**.<br>2. Tidak ada jeda tunda; event langsung masuk ke daftar event aktif saat ini. | [ ] |
| **EVT-02** | **EVENT-01: Publish Mode "Pakai Jadwal Tanggal"** | 1. Klik *"Buat Event Baru"*.<br>2. Pilih radio mode: **"Pakai Jadwal Tanggal"**.<br>3. Input Tanggal Mulai menjadi aktif dan dapat diedit.<br>4. Masukkan Tanggal Mulai = besok, Tanggal Selesai = 7 hari lagi.<br>5. Simpan event. | 1. Event berstatus badge **"Terjadwal" (Biru)**.<br>2. Event belum tayang di aplikasi sampai tanggal/jam server mencapai waktu mulai.<br>3. Begitu waktu server tiba, status otomatis beralih menjadi aktif. | [ ] |
| **EVT-03** | **EVENT-01: Validasi Tanggal Tidak Valid** | 1. Pilih mode "Pakai Jadwal Tanggal".<br>2. Masukkan Tanggal Selesai yang lebih awal dari Tanggal Mulai (misal Mulai 10 Okt, Selesai 8 Okt).<br>3. Klik Simpan. | Sistem menolak dengan validasi error: *"Tanggal selesai harus setelah tanggal mulai"*; data tidak tersimpan ke database. | [ ] |
| **EVT-04** | **EVENT-01: Kurasi Produk Promo Event** | 1. Pada event yang aktif, klik tombol ikon box produk (*"Pilih Produk"*).<br>2. Centang beberapa produk sponsor.<br>3. Cek jumlah produk terpilih pada kolom tabel. | Produk terpilih otomatis tersimpan di tabel pivot `discount_event_products` dan mendapatkan potongan harga diskon event. | [ ] |
| **EVT-05** | **EVENT-02: Kondisi 0 Event Aktif (Mobile)** | 1. Nonaktifkan seluruh event di web admin (atau biarkan kosong).<br>2. Buka Beranda dan Shop di aplikasi Flutter. | Komponen banner diskon event otomatis tersembunyi (*zero height*), tidak meninggalkan ruang kosong putih (*blank space*) atau error layout. | [ ] |
| **EVT-06** | **EVENT-02: Kondisi 1 Event Aktif (Mobile)** | 1. Aktifkan tepat 1 event di web admin.<br>2. Buka Beranda & Shop. | Banner event tampil statis dan elegan dengan judul, teks promo, dan badge diskon tanpa bergeser berlebih. | [ ] |
| **EVT-07** | **EVENT-02: Kondisi Multi-Event (2+ Event)** | 1. Aktifkan 2 atau 3 event diskon sekaligus di admin.<br>2. Buka Beranda & Shop di aplikasi. | Banner event otomatis bertransformasi menjadi **Carousel Slider Multi-Event** dengan animasi perpindahan otomatis setiap 4.5 detik. | [ ] |
| **EVT-08** | **EVENT-02: Navigasi Swipe Manual & Dots Indicator** | 1. Lakukan gesture swipe jari ke kiri dan kanan pada carousel event.<br>2. Perhatikan indikator titik (*dot indicator*) di bagian bawah carousel. | 1. Banner merespon swipe manual dengan mulus.<br>2. Titik aktif (*active dot*) bergerak sinkron mengikuti slide event yang sedang ditampilkan. | [ ] |
| **EVT-09** | **EVENT-02: Navigasi Klik Banner Event** | Klik salah satu banner event pada carousel di Beranda atau Shop. | Aplikasi langsung membuka halaman khusus `DiscountEventPage` yang memuat seluruh katalog produk khusus event tersebut beserta harga diskon resminya. | [ ] |

---

### 📱 5.5 PENGUJIAN MODUL 3: PERBAIKAN APLIKASI MOBILE (APP-01 S/D APP-02)
> **Target Aplikasi:** Flutter Mobile App (`vbat-ponsel-main`)  
> **Halaman Uji:** `home_page.dart`, `shop_page.dart`, `best_deals_page.dart`, `global_search_page.dart`

| ID | Fitur | Skenario & Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :--- | :--- | :--- | :---: |
| **APP-01** | **APP-01: Klik Kartu Produk Best Deal di Beranda** | Di halaman Beranda, cari seksi *"BEST DEAL"*, lalu tap salah satu kartu produk Best Deal. | Aplikasi **langsung membuka halaman `BestDealsPage`** (`/best-deals`), BUKAN berpindah ke tab Shop katalog umum. | [ ] |
| **APP-02** | **APP-01: Klik Tombol "Lihat Semua" Best Deal** | Di header seksi Best Deal pada Beranda, klik tombol teks *"Lihat Semua"*. | Aplikasi mengarahkan navigasi ke `BestDealsPage` secara instan. | [ ] |
| **APP-03** | **APP-01: Klik Badge Header "BEST DEAL"** | Klik chip badge berlatar oranye-merah bertuliskan *"BEST DEAL"* di Beranda. | Navigasi membuka halaman `BestDealsPage`. | [ ] |
| **APP-04** | **APP-01: Konsistensi Deep Link & Back Navigation** | 1. Buka rute `/shop/best-deals` atau `/best-deals` via GoRouter.<br>2. Tekan tombol Back (kembali) di AppBar. | Pengguna kembali ke halaman sebelumnya (Beranda) secara mulus tanpa merusak state tab bawah (*Bottom Navigation Bar*). | [ ] |
| **APP-05** | **APP-02: Eliminasi String Panjang "Sponsor Platinum"** | Periksa seluruh tampilan nama tier sponsor di slider promo, kartu mitra toko, dan badge kartu produk. | Teks panjang seperti `"Sponsor Platinum"` atau `"SPONSOR • PLATINUM"` **bersih total**; hanya menampilkan nama tier ringkas: **"Platinum"**, **"Gold"**, dst. | [ ] |
| **APP-06** | **APP-02: Standardisasi Ikon & Warna 6 Tier** | Verifikasi ikon dan warna pada masing-masing tingkatan sponsor:<br>- 💎 Diamond: Cyan Blue (`#0096C7`), icon `diamond_rounded`<br>- 👑 Platinum: Royal Purple (`#6C5CE7`), icon `workspace_premium_rounded`<br>- 🥇 Gold: Deep Amber (`#E65100`), icon `military_tech_rounded`<br>- 🥈 Silver: Slate Silver (`#5A6B82`), icon `shield_rounded`<br>- 🥉 Bronze: Bronze Brown (`#8D6E63`), icon `verified_rounded`<br>- 🤝 Kontribusi: Sky Blue (`#0284C7`), icon `handshake_rounded` | Seluruh kartu mitra dan chip lencana memuat kombinasi warna serta ikon vektor resmi yang sesuai hierarki brand. | [ ] |
| **APP-07** | **APP-02: Slider Promo Beranda (`HorizontalSponsorSlider`)** | Periksa banner slider promo di Beranda. | Badge di sudut kiri atas banner menampilkan chip solid dengan ikon tier resmi dan nama tier ringkas. | [ ] |
| **APP-08** | **APP-02: Kartu Mitra Toko (`ShopPage`)** | Buka tab Shop, periksa deretan horizontal kartu mitra sponsor. | Masing-masing kartu mitra memuat badge chip tier terstandarisasi dengan ikon visual di samping nama tier. | [ ] |
| **APP-09** | **APP-02: Dialog Detail Mitra Sponsor** | Klik salah satu logo/kartu sponsor di Beranda untuk membuka dialog detail. | Dialog menampilkan nama sponsor diikuti badge tier resmi berikon di bawahnya secara rapi. | [ ] |
| **APP-10** | **APP-02: Hasil Pencarian Global (`GlobalSearchPage`)** | Buka halaman Pencarian Global, cari kata kunci sponsor/produk. | Kartu sponsor pada hasil pencarian menampilkan badge tier terstandarisasi. | [ ] |

---

### 🖥️ 5.6 PENGUJIAN MODUL 4: PERBAIKAN PANEL ADMIN (ADMIN-01 S/D ADMIN-02)
> **Target Aplikasi:** Web Admin Laravel (`VBAT-WEB`)  
> **Halaman Uji:** `/dashboard`, `/admin/sponsors`, `/admin/products`, `/admin/events`, `/admin/campaigns`, `/admin/best-deals`, `/admin/notifications`, `/admin/bulk-upload`, `/admin/users`

| ID | Fitur | Skenario & Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :--- | :--- | :--- | :---: |
| **ADM-11** | **ADMIN-01: Satu Judul Tunggal di Super Dashboard** | Login sebagai Super Admin, buka `/dashboard`. | 1. Hanya terdapat **satu judul tunggal**: *"Super Dashboard — Analitik & Metrik"* dengan ikon dan badge pulse *"Real-time Analytics"*.<br>2. Judul ganda lama (*"Dashboard Analitik Super Admin & Owner"*) telah hilang total. | [ ] |
| **ADM-12** | **ADMIN-01: Hirarki Tata Letak KPI Cards Teratas** | Periksa susunan komponen di `/dashboard`. | 4 Kartu Metrik Utama (*Total Tayangan Iklan*, *Total Klik Sponsor*, *Rata-rata CTR*, *Interaksi Wishlist*) tersusun rapi langsung tepat di bawah header dengan whitespace lega. | [ ] |
| **ADM-13** | **ADMIN-01: Eliminasi Judul Ganda di Semua Modul Admin** | Telusuri menu-menu admin:<br>- `/admin/sponsors`<br>- `/admin/products`<br>- `/admin/campaigns`<br>- `/admin/events`<br>- `/admin/best-deals`<br>- `/admin/notifications`<br>- `/admin/bulk-upload`<br>- `/admin/users` | Tidak ada halaman yang menampilkan judul ganda bertumpuk; setiap halaman memiliki satu header bersih yang memuat judul, deskripsi, dan tombol aksi (*action buttons*). | [ ] |
| **ADM-14** | **ADMIN-02: Rasio Gender Dashboard Bersih dari "Lainnya"** | Di `/dashboard`, scroll ke seksi **"Demografi Pengguna" -> "Rasio Jenis Kelamin"**. | Panel rasio gender **hanya memuat 2 baris kategori**: **Laki-laki** (biru) dan **Perempuan** (merah muda). Baris *"Lainnya 0 (0%)"* **hilang total**. | [ ] |
| **ADM-15** | **ADMIN-02: Form Profil Hanya 2 Pilihan Gender** | Di aplikasi Flutter, buka Akun -> Edit Profil (`edit_profile_page.dart`). | Dropdown *"Jenis Kelamin"* hanya menyediakan 2 pilihan: **Laki-laki** dan **Perempuan**. Tidak ada opsi "Lainnya" atau "Other". | [ ] |
| **ADM-16** | **ADMIN-02: API Menolak Gender "other" (HTTP 422)** | Kirim request via Postman/cURL:<br>`POST /api/v1/user/demographics`<br>Payload: `{"gender": "other"}`. | Server merespon dengan **HTTP 422 Unprocessable Content** dan pesan validasi error pada kolom `gender`. | [ ] |
| **ADM-17** | **ADMIN-02: API Normalisasi Gender Valid (HTTP 200)** | Kirim request:<br>1. `POST /api/v1/user/demographics` dengan `{"gender": "Laki-laki"}`.<br>2. Request kedua dengan `{"gender": "Perempuan"}`. | 1. Server merespon **HTTP 200 OK**.<br>2. Database menyimpan nilai normalisasi: `'male'` untuk Laki-laki dan `'female'` untuk Perempuan. | [ ] |
| **ADM-18** | **ADMIN-02: Integritas Database Sanitasi** | Jalankan query database:<br>`SELECT COUNT(*) FROM users WHERE gender NOT IN ('male', 'female') AND gender IS NOT NULL;` | Menghasilkan nilai **0** (tidak ada baris pengguna dengan gender kotor atau "other"). | [ ] |

---

## 🎓 BAGIAN 6: PENGUJIAN USER MOBILE (HOME, SHOP, LMS, QUIZ, ENTITLEMENT)

| ID | Fitur | Skenario Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **USR-01** | **Home & Hero Slider** | Putar banner video promosi di beranda, lalu klik tombol "Beli / Detail". | Video terputar mulus; klik tercatat di server (`POST /api/v1/track`) dan browser membuka link marketplace. | [ ] |
| **USR-02** | **Shop & Wishlist Per Akun** | Login Akun A → Wishlist produk X → Logout. Login Akun B. | Wishlist Akun B kosong (terisolasi per akun). Produk X hanya ada di Akun A. | [ ] |
| **USR-03** | **Free Class (Kelas Gratis)** | Tonton materi di kategori kelas gratis. | Siswa dapat menonton video dari awal hingga akhir 100% tanpa terpotong. | [ ] |
| **USR-04** | **Preview Video Berbayar (5-10 Detik)** | Tonton materi berbayar tanpa membeli paket kelas. | Tepat di detik ke-10, video otomatis dijeda (*pause*) dan muncul dialog ajakan berlangganan/beli kelas. | [ ] |
| **USR-05** | **Constraint Player (Anti-Skip)** | Coba geser (*seek*) slider video materi berbayar ke durasi menit yang belum ditonton. | Slider menolak melompat (*locked*); siswa diwajibkan menonton materi secara tuntas. | [ ] |
| **USR-06** | **Pembelian Kelas & Entitlement** | Beli paket kelas (Android / iPhone / Bundling). | Hak akses kelas langsung terbuka 100%; video materi tidak lagi terkunci. | [ ] |
| **USR-07** | **Penerbitan KTA Digital** | Periksa tab Profil setelah membeli paket kelas. | Kartu KTA Digital muncul dengan nomor resmi `VBAT-YYYYMM-XXXXXX` dan berstatus aktif hijau di Web Admin. | [ ] |
| **USR-08** | **Gating Hardware Solution (HS)** | Buka tab Hardware Solution sebelum menyelesaikan kuis kelas. | Menu HS terkunci (*Locked*). Setelah progress belajar 100% dan lulus kuis, menu HS otomatis terbuka (*Unlocked*). | [ ] |
| **USR-09** | **Super Secret Mode** | Buka materi eksklusif di HP Android, coba lakukan Screenshot atau Screen Recording. | Layar hasil screenshot/rekaman menjadi hitam pekat (`FLAG_SECURE` aktif melindungi materi). | [ ] |

---

## ⚙️ BAGIAN 7: PENGUJIAN INFRASTRUKTUR, BACKUP & JARINGAN

| ID | Item Uji | Perintah / Prosedur Uji | Kriteria Kelulusan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **SYS-01** | **Migration & Database Seeder** | Jalankan `php artisan migrate:fresh --seed` pada database staging. | Migrasi 100% sukses tanpa error foreign key; seeder mengisi data awal admin, sponsor, dan kategori. | [ ] |
| **SYS-02** | **Storage Link & Asset** | Jalankan `php artisan storage:link`, unggah gambar sponsor, buka URL gambar di browser. | File asset gambar terbuka sempurna (`HTTP 200 OK`), tidak ada `404 Not Found`. | [ ] |
| **SYS-03** | **Backup & Rollback Database** | Jalankan dump database: `mysqldump -u root -p vbat_db > backup.sql`, lalu uji restore. | File backup terbuat utuh dan data berhasil dipulihkan tanpa ada tabel yang hilang. | [ ] |
| **SYS-04** | **Simulasi Jaringan Buruk (Low Network)** | Ubah koneksi HP ke 2G / Slow 3G di Developer Options atau gunakan Airplane mode. | Aplikasi menampilkan banner "Koneksi tidak stabil / Coba lagi"; tidak terjadi force close (crash). | [ ] |
| **SYS-05** | **Mode Offline Terenkripsi** | Unduh materi di HP, matikan seluruh koneksi internet (Airplane mode), lalu putar materi unduhan. | Video unduhan terputar mulus secara offline dari storage lokal terenkripsi. | [ ] |
| **SYS-06** | **Pemeriksaan Logcat Android** | Pantau error runtime via terminal: `adb logcat \| grep -E "flutter\|FATAL\|AndroidRuntime"`. | Tidak ada unhandled exceptions, memory leak kritis, atau loop error berulang. | [ ] |

---

## 🔒 BAGIAN 8: AUDIT KEAMANAN KODE & SANITASI RILIS (ZERO HARDCODE)

- [ ] **Bebas dari IP Lokal (`127.0.0.1` / `localhost`)**:
  Seluruh konfigurasi API di mobile (`lib/core/constants/api_constants.dart` atau file `.env`) telah menggunakan domain HTTPS server Contabo (contoh: `https://api.vbatponsel.com/api/v1`).
- [ ] **Bebas dari Mock / Dummy Token**:
  Tidak ada token palsu atau hardcoded Bearer token di interceptor HTTP `dio` / `http client`.
- [ ] **Bebas dari User ID Hardcoded**:
  Penyimpanan data profil, KTA, dan wishlist 100% menggunakan session dinamis hasil login pengguna (`user.id` / `user.email`).
- [ ] **Bebas dari Kebocoran Secret Key**:
  Kunci rahasia seperti `APP_KEY`, Midtrans Server Key, dan password database tersimpan aman di `.env` server dan tidak pernah di-commit ke repositori publik.
