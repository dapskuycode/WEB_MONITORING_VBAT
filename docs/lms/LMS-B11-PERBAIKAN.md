# Perbaikan B11 setelah verifikasi ulang

15 November 2026 WIB. Branch kedua repo `progress-lms`. Perbaikan ini belum commit/push/PR/deploy; migrasi utama belum dijalankan. Laporan B10/B11 sebelumnya adalah riwayat, bukan bukti seluruh UAT/perangkat sudah lulus.

## Yang diperbaiki

### Push dan kejujuran status

- Backend memakai `FcmPushTransport` dengan FCM HTTP v1 dan Google Auth service account. OAuth/FCM memakai timeout, token akses disimpan pada cache server, tidak dikirim ke mobile atau ditulis ke log.
- Receipt legacy berawalan PUSH-/INAPP- bukan bukti provider dan tidak otomatis dikirim ulang. Receipt push baru diambil dari `name` respons FCM yang sah; tidak ada lagi `PUSH-*` buatan. Inbox tanpa perangkat merupakan pengiriman `in_app` dengan receipt provider null. Perangkat aktif dengan konfigurasi/provider gagal menghasilkan `failed`, bukan sukses push.
- Migrasi `2026_11_15_100000_add_lms_push_delivery_receipts.php` menambahkan receipt/status per token-hash dan lease pemrosesan dispatch. Retry melewati perangkat yang sudah mendapat respons sukses; worker ganda menunggu lease, lease kedaluwarsa dapat dipulihkan.
- HTTP provider berlangsung di luar transaksi database. Job melempar kegagalan agar queue retry; backoff 30/120/300 detik, maksimum 3 percobaan. Dispatch baru dijadwalkan setelah commit. Jalankan queue database/asinkron pada lingkungan target.
- Token `UNREGISTERED` dinonaktifkan. Registrasi token yang sama pada akun baru mencabut pemilik sebelumnya; logout mencoba mencabut token backend sambil segera menghapus state lokal. Token tidak dicetak ke log.
- Mobile memakai SDK Firebase Core 4.x / Messaging 16.x yang menyediakan registration token untuk kontrak FCM HTTP v1, registrasi awal/token refresh, foreground refresh dan pembukaan inbox setelah tap notifikasi. Detail tujuan tetap melalui resolver akses existing.
- Integrasi Firebase bersifat opt-in lewat `FIREBASE_PUSH_ENABLED`. Tanpa konfigurasi, aplikasi tetap berjalan dan tombol notifikasi memberi status belum tersedia, tanpa token contoh/sukses palsu. Izin OS diminta melalui tindakan pengguna di halaman Notifikasi.

Status `sent` pada kanal push berarti FCM menerima pesan, bukan bukti HP menerima atau pengguna membaca. Jika provider menerima request tetapi respons hilang, retry tetap berpotensi duplikat; tidak ada klaim exactly-once di jaringan provider.

### Offline PDF yang dapat dipakai

- Reader online memiliki aksi **Simpan offline**. Menu **Bacaan offline** tersedia dari Belajar, hasil belajar dan login, sehingga unduhan masih dapat dibuka ketika startup tidak dapat memverifikasi API.
- Nama field, request UUID, platform, URL `/file` dan payload event kini sesuai API backend: `source_hash`, `material_revision`, `license_id`, `event_type`, `page`, `confirm_read`, sequence per lisensi dan event UUID. Fixture lama yang memakai kontrak berbeda diperbaiki.
- File disimpan sungguhan di application-support directory. PDF dan manifest/lisensi/antrean memakai AES-256-GCM; kunci serta device ID tersimpan pada secure storage. Manifest terikat akun, fingerprint token sesi dan origin API. File yang diubah gagal autentikasi/checksum.
- Startup tidak lagi menghapus seluruh unduhan. Restart memulihkan lisensi dan antrean yang sesuai, tetapi tidak mengubah sesi API menjadi logged-in hanya dari cache. Logout/pergantian akun menghapus state dan vault; response download/sync yang terlambat tidak mengisi kembali akun lama.
- Reader offline menampilkan halaman, menyimpan event dan konfirmasi eksplisit, lalu menandai masih menunggu sinkronisasi. Prasyarat/kelulusan tidak dibuka lokal. Hasil sync accepted/rejected ditampilkan; batch maksimal 100 event per request.
- Unduhan memeriksa akun/perangkat/materi/status/ukuran/hash, header PDF dan redirect. File dibatasi 10 MiB sesuai pipeline materi PDF. Video YouTube, kuis dan tugas tetap online.
- Daftar menyediakan buka, perbarui lisensi, hapus lokal, sinkronkan dan periksa sesi online. Lisensi ditampilkan dalam WIB. Reader memeriksa masa berlaku saat dibuka, kembali foreground dan berkala; session change membuang bytes dari layar. Secure-mode native dipakai sesuai kemampuan platform, bukan DRM.
- Batas konfigurasi existing dipertahankan: dua perangkat aktif, TTL 168 jam atau sisa akses kelas yang lebih pendek, kuota 104857600 byte (100 MiB) per akun/perangkat pada service. Ini mengoreksi laporan lama yang menyebut 500 MB.

### Scanner dan operasional

- Scanner default kembali `clamscan`; binary kosong ditolak. Bypass hanya bila `LMS_ALLOW_UNSCANNED_UPLOADS=true` dan environment `local/testing`. Production tetap membutuhkan scanner. Record ready hasil bypass lama tidak otomatis dipindai ulang; audit/re-scan dataset existing sebelum rilis. Bypass format/parser bukan bukti scan virus.
- `lms:ops-status` menampilkan apakah konfigurasi project/credential push dan antivirus tersedia, tanpa menampilkan rahasia. Status ini tidak menguji koneksi provider atau membuktikan binary scanner dapat dijalankan.
- Command timeout kuis aktual `lms:expire-quiz-attempts`; cleanup lisensi setiap 15 menit; reminder tugas setiap jam. Gunakan source `routes/console.php` untuk schedule aktual.
- Backup mencakup database dan seluruh disk `lms` aktual (`storage/app/private/lms` menurut `config/filesystems.php`), bukan nama direktori/tabel rekaan pada runbook lama. Rehearsal restore masih harus dilakukan pada lingkungan target.

## Menyiapkan Firebase saat proyek VBAT tersedia

Konfigurasi berikut belum tersedia/diberikan pada sesi ini. Tidak membuat proyek Google, mengaktifkan billing, memasang credential orang lain atau mengirim notifikasi nyata tanpa konfigurasi akun VBAT.

1. Gunakan/buat proyek Firebase milik VBAT, daftarkan aplikasi Android/iOS dengan application ID/bundle ID yang cocok source. Aktifkan FCM HTTP v1. Untuk iOS, siapkan APNs serta capability Push Notifications melalui proses signing proyek.
2. Simpan service-account backend di luar repository/public directory. Beri izin yang diperlukan untuk mengirim FCM pada project target. Isi environment backend:

```dotenv
LMS_FCM_PROJECT_ID=ID_PROJECT_FIREBASE
LMS_FCM_CREDENTIALS=/lokasi/privat/service-account-vbat.json
QUEUE_CONNECTION=database
LMS_CLAMSCAN_BINARY=clamscan
LMS_ALLOW_UNSCANNED_UPLOADS=false
```

3. Pada QA yang terisolasi, jalankan migrasi terbaru, clear/rebuild config sesuai lingkungan, lalu worker/scheduler. Database utama/produksi memerlukan rehearsal/backup dan persetujuan deployment tersendiri.

```sh
php artisan migrate
php artisan config:clear
php artisan queue:work --tries=3 --timeout=120
```

Terminal terpisah:

```sh
php artisan schedule:work
php artisan lms:ops-status
```

4. Mobile dapat memakai konfigurasi native Firebase yang benar atau FirebaseOptions dari build defines. Buat file konfigurasi QA berbeda untuk Android dan iOS sesuai app ID masing-masing; berikut hanya placeholder, bukan credential siap pakai:

```json
{
  "API_BASE_URL": "https://HOST_QA/api/v1",
  "FIREBASE_PUSH_ENABLED": "true",
  "FIREBASE_PROJECT_ID": "ID_PROJECT_FIREBASE",
  "FIREBASE_API_KEY": "API_KEY_APLIKASI",
  "FIREBASE_APP_ID": "APP_ID_PLATFORM",
  "FIREBASE_MESSAGING_SENDER_ID": "PROJECT_NUMBER",
  "FIREBASE_IOS_BUNDLE_ID": "BUNDLE_ID_IOS_JIKA_DIPAKAI"
}
```

```sh
flutter run --dart-define-from-file=/lokasi/konfigurasi/firebase-qa.json
```

Service-account private key backend tidak boleh dimasukkan ke file build mobile. Pastikan override SharedPreferences API URL tidak menunjuk server lama.

5. Login akun QA di perangkat, buka Notifikasi dan tekan **Aktifkan notifikasi push**. Publikasikan nilai fixture, jalankan worker dan cek receipt FCM nyata, inbox dan penerimaan foreground/background/tap. Uji token invalid, logout/ganti akun dan retry. Status tabel saja belum membuktikan penerimaan perangkat.

Acuan API resmi: [FCM HTTP v1](https://firebase.google.com/docs/cloud-messaging/send/v1-api), [setup Flutter](https://firebase.google.com/docs/cloud-messaging/flutter/get-started), [receive Flutter](https://firebase.google.com/docs/cloud-messaging/flutter/receive-messages). Kontrak SDK dipastikan terhadap versi di lockfile; jangan menaikkan major tanpa memeriksa API token yang dipakai.

## Bukti dan batas verifikasi

- Tes regresi backend dan mobile dicatat pada bagian hasil akhir di bawah; tes FCM memakai HTTP fake terkontrol, bukan akun Firebase nyata.
- Tes baru mencakup partial delivery/retry, worker lease, no-config, invalid token, pemindahan pemilik token, inbox-only, scanner fail-closed; mobile mencakup kontrak canonical, ciphertext/manifest, restart tanpa login API, antrean, pergantian token, respons setelah logout, konfirmasi reader, retry/delete dan opt-in push tanpa config.
- Screenshot UI fixture `B11-offline-light.png` / `B11-offline-dark.png`: layout 390/820, teks 200%, terang/gelap; native PDF viewer diganti builder fixture dalam widget test. Engine PDF yang dipakai tetap pdfrx.
- Build aset web lulus. Percobaan build bundle Flutter dengan jalur Firebase aktif **terhalang Android SDK tidak tersedia**. Ini tidak dinyatakan berhasil; native Android/iOS dan pengiriman provider sungguhan tetap harus diuji.
- Audit Composer saat pengerjaan menemukan 15 advisory pada sembilan package existing (Laravel, commonmark, Livewire dan komponen Symfony); tidak pada Google Auth. Pembenahan dependency dan acceptance rilis tetap BR, tidak dinyatakan audit bersih.
- Graph existing masih 129 node dan belum dibangun ulang. Gunakan source dan laporan ini untuk freshness perubahan B11; tidak mengklaim pekerjaan graph selesai.

## Hasil akhir

**139 tes backend / 1276 assertions dan 97 tes Flutter lulus** pada suite regresi terpilih B02–B11. PHPStan kelima layanan/job yang diubah: 0 error; Pint, diff whitespace dan build aset web lulus. Analyzer Flutter: 0 error/warning, empat info existing analytics_tracker. Uji build Android terblokir ketiadaan SDK. Migrasi utama, commit/push/deploy dan uji perangkat/provider nyata tidak dilakukan pada perbaikan ini.
