# Hasil B02: identitas dan otorisasi LMS

Tanggal: 10 November 2026, WIB. Status: **implementasi lokal dan tes scope B02 selesai; belum dirilis dan belum diuji pada perangkat native nyata**.

## Acuan dan cakupan

- Web: `origin/main@5aebf5f`; mobile: `origin/haidarreview@02c5e0c`. Fetch diulang sebelum implementasi; branch kerja masing-masing `codex/lms-foundation`, sama dengan acuan sebelum perubahan.
- Tidak ada merge branch lain, push, commit, migrasi database atau deployment pada batch ini. Dokumen lokal dan graph existing dipertahankan.
- Cakupan: LMS-03 dan bagian identitas/role/serializer LMS-04. Tidak menetapkan premium, masa akses alumni, ambang kelulusan, enrollment baru, atau kontrak /lms sebagai keputusan final.
- OAuth dinonaktifkan secara eksplisit, bukan dianggap sudah diintegrasikan. Implementasi provider terverifikasi merupakan pekerjaan lanjutan jika SSO diaktifkan lagi.

## Perubahan backend

1. Callback OAuth mengembalikan 503 `OAUTH_UNAVAILABLE`; identitas kiriman client tidak dipakai untuk membuat akun/link provider/token. Login email tetap tersedia; login API dibatasi 10 request per menit per IP melalui middleware throttle.
2. Mutasi profil/demografi wajib `auth:sanctum`; email/user_id sebagai pemilih akun ditolak. Partial update tidak menghapus gender ketika field tersebut tidak dikirim.
3. Mutasi kursus, modul, materi, kuis/soal, import, validasi YouTube, upload materi dan pembacaan analitik materi dibatasi ke `super_admin` existing. Siswa/sponsor tidak mendapat hak tersebut hanya dengan mengirim role pada payload.
4. Katalog legacy course/lesson/material/quiz hanya memuat resource terbit untuk non-admin. Respons nested disaring dari kunci jawaban, isi/sumber media privat, metadata sumber, dan thumbnail YouTube yang mengandung video ID. Bookmark tidak menjadi jalan memintas penyaringan tersebut.
5. Katalog adalah metadata; tidak menjadi grant playback. Admin tetap memakai respons authoring. Redaksi pada jalur legacy ini tidak menggantikan desain serializer/grant dan penyatuan entitlement di B03.
6. ContentCms/QuizManager memeriksa role saat boot komponen. Middleware role didaftarkan sebagai persistent middleware Livewire untuk request update berikutnya.
7. Logout API menghapus token personal pemanggil tanpa menghapus token akun lain; request melalui sesi stateful tidak mencoba memanggil delete pada transient token.

File utama backend: `routes/api.php`, `app/Http/Middleware/SanitizeLmsCatalog.php`, controller Auth/OAuth/Demographic/Course/Lesson/LearningMaterial/Quiz, komponen ContentCms/QuizManager, dan AppServiceProvider. Seluruh file PHP yang disentuh dirapikan dengan Pint.

## Perubahan mobile

1. AuthRepository memakai jalur SessionManager/API yang sama; mock user/token dan cache `cached_token` tidak lagi menjadi bukti login.
2. Login demo offline dan pemilih identitas SSO buatan dihapus. Tombol SSO login/registrasi menjelaskan bahwa fitur belum tersedia; tidak membuat sesi lokal ketika server gagal.
3. SessionManager memverifikasi `/auth/me` sebelum menandai login. ID/email/role dari profil server digunakan sebagai identitas. Pemulihan token tersimpan saat offline/gagal tetap berstatus belum terverifikasi.
4. Token menggunakan `flutter_secure_storage` 11.2.0. Token SharedPreferences legacy dipindahkan ke penyimpanan platform dan dibersihkan; token 401/403 dihapus. Token mock lama diabaikan. Error penyimpanan/verifikasi tidak dilaporkan sebagai login berhasil.
5. Versi sesi dan antrean operasi penyimpanan mencegah respons login/profil terlambat menghidupkan sesi setelah logout atau pergantian akun.
6. Pergantian akun/logout membersihkan profil, premium/entitlement tampilan, progres, wishlist dan cache belajar dalam memori. Bookmark dipisah per user ID; cache offline legacy dibersihkan saat pergantian sesi, bukan diwariskan ke akun baru. Data bookmark global legacy yang tidak punya owner tidak diimpor ke sembarang akun.
7. Membership aktif tidak otomatis memberi entitlement Android. isPremium awal false. Metode login lokal tanpa API yang tidak dipakai dihapus. Aturan paket/HS lokal lainnya masih ditangani B03/B06, bukan diubah tanpa keputusan produk.
8. Profil hanya diperbarui pada state session setelah konfirmasi server. Kegagalan simpan tidak menampilkan sukses atau menutup form seolah tersimpan; form pengguna tetap tersedia untuk dicoba kembali. Payload profil tidak memilih user lewat email/user_id.
9. Entitlement keychain iOS ditambahkan ke konfigurasi Debug/Release/Profile. Registrant plugin platform dan lockfile diperbarui oleh Flutter. Penandatanganan/provisioning dan kemampuan keychain pada perangkat nyata belum dibuktikan pada batch ini.

File utama mobile: `session_manager.dart`, `auth_repository_impl.dart`, halaman login/registrasi/edit profil, helper bookmark/offline, pubspec/lockfile dan konfigurasi plugin/keychain.

## Verifikasi yang lulus

- **Backend:** `php artisan test --compact --filter='LmsBoundaryTest|AuthApiTest|LearningMaterialApiTest|ReviewFindingsTest'`: **48 tes, 200 assertions**, lulus.
- **Tes keamanan baru:** 10 metode pada `tests/Feature/Api/LmsBoundaryTest.php`, termasuk matriks 22 operasi admin, katalog/nested/bookmark tanpa kunci/sumber privat, draft, pemalsuan OAuth, pemilihan akun profil, Livewire, attempt akun lain dan pencabutan token.
- **Mobile:** `flutter test --no-pub test/auth_session_test.dart test/widget_test.dart test/regression_flow_test.dart test/sponsor_tier_badge_test.dart`: **30 tes fungsional lulus**, termasuk **13 tes autentikasi baru**.
- Tes mobile mencakup kredensial demo ditolak, identitas `/auth/me`, token aman/expired, jaringan gagal, respons terlambat setelah logout, pergantian akun, pemisahan bookmark, kegagalan profil, tombol SSO dan widget login.
- `flutter analyze --no-pub --no-fatal-infos`: tidak ada error/warning; empat info lint pada `analytics_tracker.dart` existing tetap ada. File tersebut tidak diubah B02.
- `vendor/bin/phpstan analyse ... app/Http/Middleware/SanitizeLmsCatalog.php`: lulus, 0 error. Ini pemeriksaan middleware baru, bukan klaim seluruh proyek PHPStan lulus.
- `vendor/bin/pint --dirty --test` dan `git diff --check` pada kedua repo: lulus.
- `plutil -lint` untuk Runner.entitlements dan project.pbxproj: lulus. Ini validasi sintaks, bukan uji keychain nyata.

## Kegagalan suite luas yang sudah direproduksi pada baseline

- Backend API suite: **168/169 tes lulus**, satu gagal `GapClosingFeatureTest::sponsors_index_includes_badge_metadata` karena URL logo `/storage/...` tidak memuat `http`.
- Kegagalan backend yang sama direproduksi pada source HEAD asli yang diekstrak ke `/tmp/vbat-b02-baseline-web-dhysbevm`, dengan pemetaan autoload diarahkan ke source baseline. Tidak diperbaiki dengan mengubah tes atau fitur sponsor dalam batch ini.
- Dua golden mobile sponsor (tema terang/gelap) berbeda **1,04% / 7.480 piksel**. Keduanya direproduksi dengan selisih sama pada source `02c5e0c` di `/tmp/vbat-b02-baseline-mobile-u7dfsse9` memakai SDK lingkungan yang sama.
- Golden tidak ditimpa agar suite terlihat hijau. Tes fungsional sponsor dan pemeriksaan layout sempit tetap lulus; regresi visual sponsor perlu ditriase terpisah.
- Uji widget profil yang bergantung pada avatar jaringan existing tidak dipakai sebagai bukti visual. Bukti kegagalan simpan profil pada batch ini berasal dari tes layanan dan pemeriksaan perubahan handler form; walkthrough perangkat tetap terbuka.

## Batas dan pekerjaan berikutnya

- B02 belum membuat aplikasi siap produksi. Aktivasi membership publik, kesesuaian skema entitlement, proxy/storage/media dan otorisasi playback masih harus dibereskan pada **B03: LMS-05/LMS-06**. Jangan mengaktifkan konten/data/transaksi nyata hanya berdasarkan tes B02.
- Karena katalog non-admin kini tidak membawa sumber konten, consumer legacy yang sebelumnya mengandalkan URL/isi dari katalog harus bermigrasi ke jalur akses materi yang benar. Perubahan web/mobile perlu dirilis secara terkoordinasi setelah B03/B05/B06 menyediakan dan memverifikasi jalurnya.
- Tidak ada smoke test perangkat Android/iOS, build release native, verifikasi provisioning/keychain nyata, atau walkthrough staging end-to-end pada batch ini. Backend route test dan mobile adapter test berjalan terpisah dengan fixture; tidak dianggap pengujian lintas jaringan nyata.
- Logout offline membersihkan sesi lokal; jika request revoke tidak mencapai backend, token server tetap perlu mekanisme expiry/revoke operasional. Jangan mengklaim server sudah menerima logout saat offline.
- CMS admin masih memiliki ketidaksesuaian model/field yang dicatat B01. Guard komponen tidak menyelesaikan struktur authoring tersebut; dikerjakan pada B04.
- Checklist LMS-03/LMS-04 penuh tetap terbuka sampai verifikasi dan dependensi lintas batch yang relevan terpenuhi. Implementasi scope B02 sudah tersedia untuk review lokal.

## Ringkasan handoff

Branch kerja kedua repo `codex/lms-foundation`; perubahan belum di-commit/push. Lanjutkan B03 dari baseline ini tanpa membuang perubahan lokal. Periksa remote dan diff sebelum bekerja. Jangan memakai graph mobile lama sebagai bukti status source setelah B02; pembaruan baseline graph mengikuti LMS-29.
