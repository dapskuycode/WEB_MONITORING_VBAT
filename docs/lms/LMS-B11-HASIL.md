# Hasil Batch B11: Offline Media, Notifikasi LMS Nyata, dan Operasional

Tanggal: 13 November 2026
Status: **Implementasi selesai lokal & teruji otomatis (Backend + Mobile); belum migrasi DB produksi atau uji perangkat fisik/staging**

---

## 1. Cakupan Batch B11

Batch B11 menyelesaikan seluruh item Milestone M4 (`LMS-27`, `LMS-28`, `LMS-29`):

1. **LMS-27 — Offline Media & Sinkronisasi (`K6` / `D15`):**
   - Lisensi unduhan offline khusus materi PDF/E-Book (`material_type === 'pdf_document'` yang memiliki `LmsUpload` berstatus `ready`) dengan batas maksimal **2 perangkat aktif** per akun (`offline_max_devices = 2`), masa berlaku lisensi **7 hari (`168 jam`)** atau dibatasi sisa masa aktif entitlement kelas (mana yang lebih cepat), serta kuota per pengguna (`offline_quota_bytes = 500 MB`).
   - Penolakan eksplisit untuk materi video YouTube (`422 offline_youtube_not_allowed`) serta larangan pengerjaan/pengumpulan kuis dan tugas saat offline (`offline_assessment_not_allowed`).
   - Unduhan biner PDF terautentikasi dengan verifikasi integritas **SHA-256** (`X-LMS-SHA256`), penyimpanan lokal terikat `(userId, deviceId, materialId)`, pemeriksaan kedaluwarsa, pencabutan lisensi/perangkat (`revoke`), serta deteksi manipulasi jam perangkat mundur melebihi toleransi **5 menit (`300s`)**.
   - Antrean progres baca offline (`queueReadingProgress`) dan sinkronisasi per-event (`POST /api/v1/lms/offline/sync`) dengan validasi usia event maks **24 jam (`86400s`)**, toleransi clock skew **5 menit (`300s`)**, kecocokan `content_revision`, urutan `sequence`, dan idempotensi `event_id`.
2. **LMS-28 — Notifikasi LMS Nyata (`D14`):**
   - Registrasi dan pencabutan token push perangkat (`lms_device_push_tokens`) per pengguna dan `device_id`.
   - Antrean dan log pengiriman notifikasi nyata (`lms_notification_dispatches` + `DispatchLmsNotificationJob`) dengan deduplikasi `event_key` dan status nyata (`queued`, `sent`, `failed`) untuk event:
     - `quiz_grade_published` & `quiz_grade_revised`
     - `assignment_grade_published`, `assignment_grade_revised`, & `assignment_returned`
     - `assignment_deadline_reminder` (pengingat H-1 / 24 jam sebelum tenggat tugas bagi siswa yang belum mengumpulkan).
   - Perlindungan privasi layar kunci: payload judul/isi notifikasi tidak pernah membocorkan nilai numerik (`score`), jawaban ujian/tugas, atau kunci jawaban.
   - Verifikasi deep-link di sisi server (`POST /api/v1/lms/notifications/resolve-deep-link`) dan guard di `NotificationPage` Flutter yang menolak akses bila belum login, salah akun, atau masa akses kelas sudah berakhir/dicabut.
3. **LMS-29 — Operasional, Korelasi Request, Redaksi Log, Dokumentasi, dan Kurasi Graph (`D16`):**
   - Middleware `LmsRequestCorrelation` pada `VBAT-WEB` dan interceptor `ApiClient` pada `vbat-ponsel-main` yang menyertakan serta mempropagasi header `X-Request-Id` di setiap request/response dan shared log context.
   - `LmsRedactionService` yang menyensor field sensitif (`password`, `token`, `authorization`, `push_token`, `answers`, `answer_text`, `selected_option_id`, `correct_answer`, `submission_text`, `signature`) serta query parameter bertanda tangan sebelum ditulis ke log/audit.
   - Perintah operasional terjadwal di `routes/console.php`:
     - `lms:send-assignment-reminders` (setiap jam)
     - `lms:cleanup-offline-licenses` (harian pukul 02:15)
     - `lms:ops-status` (ringkasan kesehatan antrean scan, gagal notifikasi, lisensi offline aktif, dan penolakan sync).

---

## 2. Artefak Kode yang Ditambahkan / Diperbarui

### Backend (`VBAT-WEB`)
- [config/lms.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/config/lms.php)
- [2026_11_13_100000_add_lms_offline_notifications_and_ops.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/database/migrations/2026_11_13_100000_add_lms_offline_notifications_and_ops.php)
- [LmsOfflineService.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Services/LmsOfflineService.php)
- [LmsNotificationService.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Services/LmsNotificationService.php)
- [DispatchLmsNotificationJob.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Jobs/DispatchLmsNotificationJob.php)
- [LmsRedactionService.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Services/LmsRedactionService.php)
- [LmsRequestCorrelation.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Http/Middleware/LmsRequestCorrelation.php)
- [LmsBatch11Controller.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Http/Controllers/Api/LmsBatch11Controller.php)
- [LmsGradingService.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Services/LmsGradingService.php) & [LmsAssignmentService.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/app/Services/LmsAssignmentService.php)
- [routes/api.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/routes/api.php) & [routes/console.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/routes/console.php)
- [LmsBatch11Test.php](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/VBAT-WEB/tests/Feature/Api/LmsBatch11Test.php)

### Mobile (`vbat-ponsel-main`)
- [api_client.dart](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/vbat-ponsel-main/lib/core/network/api_client.dart)
- [offline_media_manager.dart](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/vbat-ponsel-main/lib/core/utils/offline_media_manager.dart)
- [push_notification_service.dart](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/vbat-ponsel-main/lib/core/utils/push_notification_service.dart)
- [session_manager.dart](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/vbat-ponsel-main/lib/core/utils/session_manager.dart)
- [notification_page.dart](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/vbat-ponsel-main/lib/features/notifikasi/presentation/pages/notification_page.dart)
- [batch11_offline_notifications_test.dart](file:///Users/mdaffaatstsaqif/Dev/PROJECT%20VBAT/vbat-ponsel-main/test/batch11_offline_notifications_test.dart)

---

## 3. Hasil Verifikasi Otomatis (`T11-01` s/d `T11-05`)

### Backend (`VBAT-WEB`)
- `./vendor/bin/pint --dirty --test` → **PASS**
- `php artisan test --filter=Lms` → **71 passed (762 assertions) in 1.65s**
  - `T11-01` & `T11-03`: Penerbitan lisensi PDF, batas 2 perangkat aktif, unduhan terverifikasi SHA-256, penolakan YouTube offline (`422`), penolakan lintas akun/perangkat (`403`), serta pencabutan perangkat.
  - `T11-02`: Sinkronisasi progres baca offline per-event (`accepted`, `duplicate_event`, `revision_mismatch`, `event_too_old`, `clock_skew_exceeded`, `license_revoked`).
  - `T11-04`: Dispatch notifikasi nilai kuis/tugas, revisi nilai, pengembalian tugas, pengingat tenggat H-1, deduplikasi `event_key`, privasi teks tanpa bocor skor/jawaban, dan resolusi deep-link terverifikasi server.
  - `T11-05`: Propagasi `X-Request-Id`, redaksi payload sensitif (`LmsRedactionService`), dan perintah operasional (`lms:cleanup-offline-licenses`, `lms:ops-status`).

### Mobile (`vbat-ponsel-main`)
- `flutter --no-version-check analyze --no-fatal-infos` → **0 error, 0 warning**
- `flutter --no-version-check test test/batch11_offline_notifications_test.dart test/regression_flow_test.dart test/assignment_test.dart test/results_test.dart test/quiz_test.dart test/reading_test.dart test/student_courses_test.dart test/playback_test.dart test/lms_access_test.dart` → **74 passed**

---

## 4. Runbook Operasional & Pemeliharaan (`LMS-29`)

1. **Menjalankan Queue Worker & Scheduler:**
   - Worker antrean wajib berjalan untuk memproses `DispatchLmsNotificationJob` dan scan berkas:
     ```bash
     php artisan queue:work --tries=3 --timeout=60
     ```
   - Scheduler cron wajib menjalankan `php artisan schedule:run` setiap menit (atau `php artisan schedule:work` pada container worker), yang mengeksekusi:
     - `lms:expire-attempts` (setiap menit)
     - `lms:send-assignment-reminders` (setiap jam)
     - `lms:cleanup-offline-licenses` (setiap hari pukul 02:15)
2. **Pemeriksaan Kesehatan Operasional & Korelasi Insiden:**
   - Jalankan `php artisan lms:ops-status` untuk melihat jumlah berkas yang masih `pending` scan, notifikasi `failed`, lisensi offline `active`, dan event sync yang `rejected` dalam 24 jam terakhir.
   - Setiap respons API menyertakan header `X-Request-Id` (juga tersimpan di `ApiClient.lastRequestId` pada aplikasi mobile). Gunakan ID tersebut untuk menelusuri log aplikasi tanpa pernah mengekspos token, jawaban kuis/tugas, atau signed URL.
3. **Prosedur Backup & Rehearsal Pemulihan:**
   - Backup wajib mencakup basis data utama **serta** direktori storage privat (`storage/app/private/lms-uploads` dan `storage/app/private/lms-submissions`).
   - Saat rehearsal pemulihan di staging, verifikasi kecocokan nilai kolom `sha256` pada tabel `lms_uploads` dan `lms_assignment_submission_files` terhadap berkas fisik yang dipulihkan sebelum membuka trafik pengguna.
4. **Batas & Freshness Knowledge Graph:**
   - Artefak graph (`graphify-out/`) hanya mencakup kode sumber dan dokumentasi terkurasi; direktori generated/vendor (`vendor/`, `node_modules/`, `storage/`, `build/`, `.dart_tool/`, `.git/`) dikecualikan agar node arsitektur tetap bersih dan akurat.

---

## 5. Batas Verifikasi & Langkah Menuju Gate Rilis (`BR`)

- Seluruh fitur M0–M4 (`B00` s/d `B11`) telah selesai diimplementasikan dan lulus uji otomatis lokal.
- Sebelum rilis produksi penuh, jalankan **Batch BR (`LMS-30`, `LMS-31`, `LMS-32`)**: verifikasi webhook pembayaran sandbox/refund (bila pembelian dalam aplikasi diaktifkan), audit permukaan API non-LMS bersama, uji end-to-end di lingkungan staging, dan uji coba pada perangkat fisik Android/iOS.
