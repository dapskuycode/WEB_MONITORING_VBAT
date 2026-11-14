# Hasil Batch B10 — Tugas Praktik, Review/Return/Resubmit, Gradebook, dan Instructor Scope (`LMS-22` s/d `LMS-26`)

Tanggal: 12 November 2026, WIB.
Status: Implementasi lokal selesai dan teruji otomatis pada kedua repositori (`VBAT-WEB` & `vbat-ponsel-main`) di branch `progress-lms`. Belum dijalankan migrasi database produksi atau uji perangkat fisik/staging.

## 1. Keputusan Pengguna yang Diterapkan pada Batch B10

1. **Cakupan Instructor (`LMS-26`):** Role `instructor` diaktifkan penuh dengan pembatasan scope per kelas (`courses.instructor_id` dan tabel pivot co-instructor `lms_course_instructors`) menggunakan domain/service canonical yang sama dengan `super_admin` (`LMS-22` s/d `LMS-26` lengkap).
2. **Kebijakan Default Tugas & Penilaian (`K6` pilot/testing):**
   - Lampiran siswa (`purpose = assignment_submission`): maksimal 5 berkas per tugas (`PDF`, `JPG`, `PNG`), maksimal 20 MB (`20971520` byte) per berkas, wajib melewati pipeline `quarantine → scan → ready` (`LmsUploadService` + `LmsDocumentInspection`).
   - Penilaian tugas: skor maksimal default `100`, ambang lulus default `70` (persentase), `late_submission_allowed = false` secara default kecuali diaktifkan per tugas (dengan `late_until` opsional).
   - Pengiriman ulang (`resubmit`): versi yang sudah dikirim bersifat tetap (*immutable*) dengan kode bukti `SUB-YYYYMMDD-XXXXXXXX`; siswa hanya dapat mengirim versi baru (`version + 1`) bila reviewer mengembalikan tugas (`status = returned`, `allow_resubmit = true`, dan dalam batas `resubmit_deadline_at` bila ditetapkan).
   - Pengaruh ke progres kelas & *Hardware Solution* (HS): tugas dengan `is_required = true` menjadi bagian dari perhitungan unit wajib modul/kelas (`LmsProgressService`); pengiriman berstatus `pending_review` mengizinkan siswa membuka urutan aktivitas berikutnya di modul, tetapi kelulusan modul/kelas/HS tetap mensyaratkan nilai `published` yang memenuhi ambang lulus (`is_passed = true`).

## 2. Perubahan Backend (`VBAT-WEB`)

- **Migrasi:** `database/migrations/2026_11_12_100000_add_lms_assignments_gradebook_and_instructors.php`
  - Menambahkan `courses.instructor_id` dan `quizzes.weight`.
  - Membuat tabel `lms_course_instructors`, `lms_assignments`, `lms_assignment_drafts`, `lms_assignment_submissions`, `lms_submission_grade_revisions`, dan `lms_submission_requests`.
- **Model & Otorisasi:**
  - `App\Models\LmsAssignment`, `App\Models\LmsAssignmentDraft`, `App\Models\LmsAssignmentSubmission`.
  - `App\Models\User`: helper `isInstructor()`, `isLmsManager()`, `managedCourseIds()`, `canManageCourse()`.
  - `App\Providers\AppServiceProvider`: gate `lms-admin` mencakup `super_admin` dan `instructor` (dengan pembatasan per kelas di layanan canonical).
- **Layanan Canonical:**
  - `App\Services\LmsDocumentInspection` & `App\Services\LmsUploadService`: inspeksi header/magic byte `PDF`, `JPG`, `PNG`, kuota per berkas 20 MB, isolasi kepemilikan & `purpose = assignment_submission`.
  - `App\Services\LmsAssignmentService`: authoring tugas per modul, deteksi konflik revisi (`expected_revision`), penyimpanan draft ber-versi & idempoten (`request_id`), pengiriman tugas *immutable* dengan penentuan `is_late` berbasis waktu server, unduhan lampiran privat berotorisasi, review/penilaian draft & publikasi atomik (`expected_grade_version`), serta pengembalian tugas untuk revisi (`returnSubmission`).
  - `App\Services\LmsGradebookService`: rekapitulasi buku nilai kelas (`courseGradebook`), ringkasan nilai siswa (`studentGrades`) tanpa membocorkan `draft_score`/`rubric`, dan publikasi nilai massal atomik (`bulkPublish`).
  - `App\Services\LmsAuthoringService`, `App\Services\LmsGradingService`, `App\Services\LmsProgressService`: penegakan scope kelas untuk `instructor`, blokir publikasi modul bila masih ada tugas wajib berstatus draft, dan integrasi progres tugas ke kurikulum/silabus.
- **Endpoint API & Komponen Web Admin:**
  - `App\Http\Controllers\Api\LmsAssignmentController`, `LmsGradeController`, `LmsDocumentController`, `StudentCourseController`, `QuizApiController`.
  - Livewire `App\Livewire\GradeReview`, `ContentCms`, `QuizManager` beserta blade view terkait.

## 3. Perubahan Mobile (`vbat-ponsel-main`)

- **Jaringan & Entitas Silabus:**
  - `lib/core/network/api_client.dart`: penambahan metode `put()`.
  - `lib/features/belajar/domain/entities/course_entity.dart`, `course_repository_impl.dart`, `course_syllabus_page.dart`, `app_router.dart`: dukungan aktivitas `kind: 'assignment'` pada silabus dan navigasi `/assignment` ber-ID server.
- **Repositori, Controller & UI Tugas (`LMS-23` & `LMS-25`):**
  - `lib/features/belajar/data/repositories/assignment_repository.dart`: pemetaan ketat detail tugas, draft, riwayat pengiriman, buku nilai siswa (`courseGrades`), unggah lampiran, dan pemeriksaan status pindai; menolak payload yang membocorkan field internal (`rubric`, `draft_score`, `draft_feedback`, `storage_path`).
  - `lib/features/belajar/presentation/bloc/assignment_controller.dart`: pengelolaan state draft, lampiran `quarantine/ready/rejected`, idempotensi `request_id` saat retry jaringan, dan pembersihan state seketika saat sesi/akun berubah (`logout`).
  - `lib/features/belajar/presentation/pages/assignment_page.dart`: tampilan instruksi, tenggat WIB, editor jawaban teks & lampiran, konfirmasi pengiriman *immutable*, kartu bukti pengiriman (`receiptCode`), catatan revisi instruktur (`returned`), dan riwayat versi pengiriman; mendukung tema terang/gelap dan skala teks 200%.

## 4. Bukti Verifikasi Otomatis

### Backend (`VBAT-WEB`)
- `./vendor/bin/pint --dirty --test` → **PASS**
- `php artisan test --filter=Lms` (termasuk `tests/Feature/Api/LmsBatch10Test.php` yang menguji `T10-01` s/d `T10-07`) → **68 tes lulus (683 assertions) dalam 1,51 detik**.
  - `test_t10_01_assignment_authoring_validation_and_publish_gate`: PASS
  - `test_t10_02_and_t10_03_student_draft_upload_immutable_submit_and_idempotency`: PASS
  - `test_t10_04_deadline_late_policy_and_entitlement_revocation`: PASS
  - `test_t10_05_review_draft_publish_return_resubmit_and_hs_gate`: PASS
  - `test_t10_06_gradebook_aggregation_and_bulk_publish`: PASS
  - `test_t10_07_instructor_course_scoping_and_immediate_revocation`: PASS

### Mobile (`vbat-ponsel-main`)
- `flutter --no-version-check analyze --no-fatal-infos` → **PASS (0 error, 0 warning; 4 info baseline di `analytics_tracker.dart`)**.
- `flutter --no-version-check test test/assignment_test.dart test/results_test.dart test/quiz_test.dart test/reading_test.dart test/student_courses_test.dart test/playback_test.dart test/lms_access_test.dart` → **62 tes lulus dalam 3 detik** (termasuk 4 suite baru di `test/assignment_test.dart`).

## 5. Batas Verifikasi & Pekerjaan Tersisa

- Penyimpanan berkas masih menggunakan disk privat lokal (`storage/app/private`) dan pemindaian lokal (`LmsDocumentInspection`); integrasi object storage cloud, daemon ClamAV produksi, dan uji end-to-end pada perangkat fisik Android/iOS serta staging tetap menjadi bagian dari gate operasional/rilis (`B11` / `BR`).
- Batch berikutnya: **B11** (`LMS-27` Offline media & sinkronisasi, `LMS-28` Notifikasi LMS nyata, `LMS-29` Operasional, dokumentasi, dan kurasi graph) serta gate rilis **BR** (`LMS-30` s/d `LMS-32`).

