# LMS-02: pemetaan data dan kontrak integrasi web–mobile

Tanggal: 10 November 2026, WIB. Versi 0.1. Status: **draft teknis siap review; belum kontrak final atau implementasi**.

Batch B01. Cakupan: pemetaan struktur existing, usulan migrasi tambahan, batas API, contoh payload, kompatibilitas, dan acceptance admin → API → mobile. Keputusan K1–K6 pada LMS-01 belum disetujui; tidak dibekukan sepihak di sini.

## 1. Baseline dan bukti

- Web `WEB_MONITORING_VBAT`: `origin/main@5aebf5f`; mobile `MOCKUP-VBAT`: `origin/haidarreview@02c5e0c`. Fetch remote diulang 10 November 2026, HEAD kedua branch kerja `codex/lms-foundation` sama dengan acuan masing-masing (ahead/behind 0/0).
- Ada dokumen lokal yang belum di-commit dan output graph mobile. Dokumen pengguna dipertahankan; tidak ada merge branch lain, push, migrasi atau perubahan source aplikasi pada batch ini.
- Rute existing diperiksa melalui `php artisan route:list --path=api/v1 --json`; model/migrasi/controller/CMS/mobile diperiksa dari source. Keberadaan rute tidak membuktikan otorisasi atau perilakunya benar.
- Struktur database di bawah adalah yang dideklarasikan migrasi, bukan hasil inspeksi database staging/produksi. Tidak ada koneksi ke data produksi.
- Graph mobile dipakai untuk orientasi; karena dibuat sebelum pembaruan baseline mobile, detail dipastikan kembali dari source terkini. Tidak ada graph backend yang diasumsikan tersedia.
- Referensi produk: FSD bagian 1, 3–7, 8–10; WF bagian 1–10; INV 1, 7–9, 13–19, 23, 30, 32–33; LMS-01 D01–D16.

## 2. Pemetaan model dan field

### T01. Kelas memakai Course existing

Existing `courses`/`Course`: ID integer, title, slug, description, thumbnail_path, status, type, is_free_class, is_featured, published_at, archived_at. `type`: free_class/android/iphone/hardware_solution. `Course::lessons()` sudah tersedia.

Usulan: pertahankan ID/relasi. Tambahkan `code` unik dan `revision` bila dibutuhkan import dan concurrency; category produk dipetakan ke type existing. Bundling adalah hak gabungan paket, bukan langsung jenis kelas baru. Bedakan tipe kelas HS dari prasyarat kelas Android/iPhone dengan pemetaan eksplisit setelah LMS-01 K3/K4 disetujui.

Bukti web: `app/Models/Course.php`; migrasi `2026_09_12_060215_create_courses_lessons_videos_tables.php` dan `2026_09_25_210000_add_course_type_and_lms_domains.php`.

### T02. Modul memakai Lesson existing

Existing `lessons`/`Lesson`: id, course_id, title, sort_order, timestamps. Tidak ada status/description/code pada model dan migrasi modul tersebut.

Usulan: istilah API baru `module`, penyimpanan tetap `lessons`; tambah code, description, status dan revision melalui migrasi tambahan. Nama model tidak harus diubah massal. Existing course_id/lesson_id tidak dikonversi menjadi UUID.

Bukti web: `app/Models/Lesson.php`; `app/Http/Controllers/Api/LessonApiController.php`; migrasi struktur kelas.

### T03. Materi memakai LearningMaterial; jangan membuat Unit kedua

Existing `learning_materials`: lesson_id, unit_code, unit_title, material_type, title, description, youtube_url, youtube_video_id, pdf_path, thumbnail_path, content_text, external_url, sort_order, is_required, quiz_required, quiz_id, status, duration_seconds, view_count, soft delete.

Existing jenis materi: `youtube_video`, `pdf_document`, `text_content`, `external_link`. Target DTO memakai jenis netral `video`, `pdf`, `text`, `link` dan field provider; e-book EPUB memerlukan dukungan tipe/viewer baru dan tidak boleh dianggap sudah didukung. Untuk e-book berupa PDF gunakan jenis PDF.

CMS `ContentCms` merujuk `App\Models\Unit`, `CourseCategory`, `unit_id`, `file_type`, `url`, `video_id`, `file_path` dan `is_archived`. Model Unit/CourseCategory tidak ditemukan pada checkout; field CMS tersebut berbeda dari LearningMaterial existing. Ini temuan inspeksi, belum reproduksi semua aksi CMS saat runtime.

Usulan: CMS dan API memakai layanan authoring yang sama di atas Course/Lesson/LearningMaterial. Map `youtube → youtube_video`, `pdf → pdf_document`, `url/video_id → youtube_url/youtube_video_id`, `file_path → pdf_path` hanya untuk PDF. Map `unit_id` ke modul/lesson memerlukan konteks dan audit; jangan menganggap semua ID Unit sama dengan Lesson. Tidak membuat tabel Unit hanya untuk mengakomodasi kode yang salah.

Tambahan yang dibutuhkan: is_preview/preview_seconds, referensi upload privat, revision dan metadata revisi. Audit duplikasi `unit_title`/title sebelum menjadikan salah satunya alias; jangan membuang informasi existing.

Bukti web: `app/Models/LearningMaterial.php`; migrasi `2026_09_24_160000_create_learning_materials_and_views_tables.php`; `app/Livewire/ContentCms.php::saveUnit/saveMaterial/render`.

### T04. Videos legacy dan penempatan kuis

Tabel `videos` sudah ada terpisah dari LearningMaterial. Usulan LearningMaterial menjadi sumber materi LMS baru. Audit pemakaian setiap baris videos sebelum backfill; sediakan pemetaan ID lama → materi baru dan idempotency backfill. Jangan menghapus videos sebelum seluruh konsumen dimigrasikan.

Existing Quiz berinduk `learning_material_id`, sedangkan LearningMaterial juga memiliki `quiz_id`. Target tiga posisi kuis tidak boleh memakai dua arah tersebut sebagai dua sumber kebenaran.

Usulan: pilih Quiz sebagai pemilik penempatan (`course_id`, `lesson_id` nullable untuk kuis akhir kelas, `placement`, `anchor_material_id` nullable). Aturan: after_material perlu anchor pada modul yang sama; end_of_module perlu modul; end_of_course tidak perlu anchor/modul. `learning_material_id` dipertahankan sebagai referensi legacy selama transisi; backfill setelah memvalidasi relasi berlawanan. Dua field lama baru dinonaktifkan setelah adapter/konsumen berpindah.

Bukti: model `Quiz`, `LearningMaterial`; migrasi domain LMS. Rancangan penempatan memerlukan tes cross-course dan konsistensi constraint saat LMS-07/LMS-14.

### T05. Hak paket berbeda dari enrollment dan membership

Existing Entitlement: user_id, package_type, starts_at, expires_at, status, source, transaction_reference. Membership terpisah; Certificate juga model/tabel terpisah.

EntitlementService masih memakai entitlement_type/entitlement_key/is_active; MediaAuthorizationService memakai source_type/metadata/is_active. Kolom tersebut tidak dideklarasikan skema entitlement existing. `EntitlementApiController::index` juga memfilter field lama. Mengganti satu service saja tidak cukup.

Usulan: satu layanan kebijakan akses membaca skema canonical existing dan membedakan grant paket dari masa akses konten, sesuai LMS-01 K3. Semua jalur media, kelas, kuis dan endpoint entitlement menggunakannya. Role admin untuk preview tidak boleh menyamar sebagai kelulusan student.

Usulan enrollment: tabel tambahan course_enrollments menyimpan user_id, course_id, revision kelas, status dan sumber penetapan. Grant/membership tidak disalin menjadi kelulusan. Riwayat renewal disimpan terpisah dari identitas enrollment agar tidak menghapus progres lama. Aturan satu enrollment aktif per user/course/revision dan pencatatan renewal diselesaikan setelah kebijakan K3/K5, bukan memaksakan unique FSD mentah.

Bukti: `app/Models/Entitlement.php`, `Membership.php`, `Certificate.php`; `app/Services/EntitlementService.php`, `MediaAuthorizationService.php`; `app/Http/Controllers/Api/EntitlementApiController.php`.

### T06. Progres existing belum bukti completion tepercaya

Existing learning_material_views menyimpan pasangan user/material, progress_percent dan completed_at. recordProgress menerima persentase client; belum ada sesi/event/cakupan interval tontonan.

Usulan: tabel sesi playback dan event unik; progres canonical per enrollment/revisi materi berisi posisi terakhir/maksimum, cakupan diterima, status dan completed_at. Server menghitung hasil memakai kebijakan versi yang dipilih; client mengirim observasi, bukan keputusan completed.

Progres lama di-backfill sebagai `legacy_unverified`, bukan otomatis trusted completion. Bukti kelulusan existing yang sah dipertahankan melalui migrasi/audit terpisah; jangan mereset atau menaikkan semua siswa tanpa keputusan K5. Nilai persentase legacy tidak boleh menjadi bukti 95% tontonan baru.

Bukti: `app/Models/LearningMaterialView.php`; `LearningMaterialApiController::recordProgress`; mobile `video_player_page.dart` memakai fallback material ID 5 dan Dio terpisah.

### T07. Soal, attempt dan nilai

Existing Quiz: passing_score, max_attempts, time_limit_minutes, is_active; QuizQuestion: question_text, options, correct_answer, points; QuizAttempt: user_id, answers, score, is_passed, started_at, submitted_at.

CMS menyimpan opsi sebagai array string dengan correct_answer berupa indeks. API submit membandingkan secara strict dengan jawaban request, sedangkan kolom DB correct_answer adalah string. Normalisasi indeks/string harus eksplisit dan konsisten; jangan menebak ulang kunci dari teks pilihan.

Usulan opsi student berupa ID stabil `{id, body}` dan MCQ answer berupa option_id. Snapshot attempt mencatat revisi pertanyaan, opsi, urutan, batas waktu/percobaan, status, enrollment, deadline_at dan sequence autosave; kunci tetap hanya di server. Tambahan tipe soal/rubrik dan publikasi nilai dikerjakan sesuai tahapnya.

No-limit direpresentasikan null pada kontrak target; 0 bukan dua arti berbeda. Mode tertinggi/terakhir/rata-rata harus menggunakan attempt final sesuai keputusan produk. Nilai draft tidak mengubah prerequisite.

Bukti: `app/Livewire/QuizManager.php`; `app/Http/Controllers/Api/QuizApiController.php`; model/migrasi Quiz, QuizQuestion dan QuizAttempt; mobile `quiz_page.dart`.

### T08. Import sudah ada dan perlu diselaraskan

Ada BulkImportService dan dua rute legacy bulk-import/template. Jadi LMS-18/LMS-19 merupakan perbaikan/perluasan jalur existing.

Existing template memakai lesson_id/unit_code/material_type, berbeda dari template FSD yang memakai course/module/material code. preview default memeriksa paling banyak 20 baris; commit membaca seluruh file, memakai transaksi, dan dapat skip_invalid. Input status dapat berisi published. Belum ada preview ID immutable yang mengikat berkas tervalidasi ke apply.

Usulan: pakai kemampuan parser yang ada, validasi seluruh baris tanpa menulis, simpan preview terikat admin+checksum+versi resource, gunakan import_id saat apply, enforce Draft di server, lalu audit dan idempotency. Template/FSD kode diterjemahkan ke ID canonical dengan pemeriksaan kepemilikan/konteks. Semantik all-or-nothing menunggu K5; rancangan default mengikuti usulan LMS-01.

Bukti: `app/Services/BulkImportService.php`; `LearningMaterialApiController::bulkImport/bulkImportTemplate`; `routes/api.php`.

## 3. Batas API yang diusulkan

### T09. Tambah namespace LMS; jangan mematahkan konsumen lama

**Usulan teknis yang menyimpang dari path contoh FSD:** endpoint baru student berada pada `/api/v1/lms`, admin pada `/api/v1/admin/lms`. Alasannya: endpoint root existing mengembalikan bentuk dan perilaku berbeda, sementara client lama masih memakainya. Namespace adalah batas kontrak, bukan alasan membuat tabel LMS duplikat.

Pilihan ini belum final. Alternatif mempertahankan `/courses/my` dan root paths memerlukan negosiasi versi/adapter response; jangan mengubah bentuk `/courses/{id}` diam-diam. Daftar route target ada pada `lms-contract.examples.json`; seluruh route tersebut **planned**, belum tersedia.

Jalur legacy tetap perlu diamankan; menambah API baru tidak menutup kebocoran sumber lama. Contoh: CourseApiController::show menserialisasi lessons.learningMaterials; LessonApiController::show menserialisasi learningMaterials/videos; QuizApiController::show dapat menserialisasi soal/kunci untuk guest. Terapkan kebijakan dan serializer terbatas pada seluruh jalur sebelum konten nyata diaktifkan.

Usulan pengelolaan Livewire: pakai layanan domain/policy yang sama; REST admin digunakan bila diperlukan konsumen. Endpoint admin tidak berarti membuat dua implementasi authoring yang berbeda.

### T10. Bentuk data dan identitas

- Existing resource ID tetap integer JSON positif. Mobile menggunakan int pada DTO baru, bukan ID mock c1/c2; request/route harus menyertakan ID dari API tanpa fallback.
- Request JSON UTF-8; Bearer token untuk student/admin; identitas hanya dari token. Field user_id/email tidak memilih owner dalam request student.
- Success `{success:true,data:...,request_id:...}`. List menambah meta `{current_page,per_page,total,last_page}` agar selaras pola existing; ini penyesuaian dari FSD `meta.page`. Per_page default 20, maksimum 100; contoh ID/jumlah adalah fixture.
- Error `{success:false,error:{code,message,fields},request_id}`; fields berupa object field → array pesan. Request ID juga pada header X-Request-ID.
- ISO-8601 UTC untuk timestamp; tampilan WIB di mobile. Selang akses: starts_at <= now < ends_at; null akhir hanya jika jenis grant memang mengizinkan menurut kebijakan yang disetujui.
- 401 sesi invalid, 403 tidak berhak, 404 resource tak ada/di luar scope yang disamarkan, 409 state/revisi/idempotency conflict, 410 akses/resource yang telah berakhir bila kontrak memilih status tersebut, 422 invalid, 429 throttled, 5xx gagal server. Client membaca kode, bukan membandingkan teks pesan.
- access state: accessible, preview, locked_entitlement, locked_prerequisite, expired, revoked. Hak baca hasil belajar dibedakan dari grant pembukaan media; course metadata/nilai tidak menyertakan URL sumber privat.
- `next_unit` memakai `{kind,id,module_id}` karena materi dan kuis dapat mempunyai ID integer sama. Null jika tidak ada aktivitas berikutnya; jangan menebak di widget.
- DTO kurikulum menggabungkan urutan aktivitas materi/kuis dengan kind dan position; tidak memasukkan kunci. Pengurutan setelah perubahan mendeteksi revision conflict dan menjaga parent yang sama.

### T11. Mutasi, retry dan media

- Mutasi memakai Idempotency-Key UUID untuk replay operasi yang sama; server mengikatnya pada aktor+operasi+resource dan hash payload. Kunci sama/payload berbeda →409; replay valid memberi resource dan status response yang sama. Aturan simpan minimal 24 jam; start/submit final tetap terlindungi constraint/transaksi setelah TTL lewat.
- Autosave jawaban memakai sequence monotonic, event playback memakai client_event_id unik. State final tidak dapat dibuka kembali oleh request telat/ulang.
- Edit admin/grade menggunakan expected_revision integer; server melakukan compare-and-update dalam transaksi. Mismatch →409. Revisi content dan attempt snapshot berbeda dari versi request.
- Materi diakses melalui grant pendek setelah otorisasi; metadata dan syllabus tidak membawa pdf_path/object key atau URL video penuh. Payload provider hanya dikirim saat akses sesuai policy. Grant server untuk YouTube tidak menjadikan video YouTube privat bagi akun VBAT.
- Request progress mengirim playback session, posisi, durasi observasi dan played_delta_seconds; tidak mengirim completed=true untuk video. Batas heartbeat, retensi event dan threshold mengacu LMS-01 K4/K6 dan diverifikasi di LMS-12.
- Batas upload, pemrosesan scanning dan deadline memakai field kebijakan server. Rancangan tidak menetapkan harga, cutoff alumni atau periode akses baru.

## 4. Urutan migrasi dan kompatibilitas

1. **Inventaris/backfill dry-run:** hitung record/relasi orphan, konflik kode/urutan, referensi quiz ganda, penggunaan videos, nilai kunci jawaban dan isi legacy. Catat data yang tidak dapat dipetakan. Belum menjalankan migrasi produksi.
2. **Tambahan kompatibel:** nullable field/revision/preview/code pada tabel yang dipertahankan; tabel enrollment/session/event/snapshot/grade/import sesuai tahap. Unique baru diterapkan setelah data dinormalisasi. Pakai FK integer sesuai existing.
3. **Normalisasi authoring:** CMS/API/BulkImport memakai field canonical dan layanan bersama. Role student/teknisi dan super_admin existing dipetakan ke kapabilitas, bukan mengubah role semua akun saat login.
4. **Pusatkan akses:** seragamkan entitlement/media/controller/legacy reads; hentikan aktivasi hak publik. Tentukan status legacy grant yang tidak memiliki bukti; tidak mengubahnya menjadi pembayaran sah secara otomatis.
5. **Isi resource tambahan:** buat enrollment eligible secara idempotent dari sumber tepercaya; pertahankan asal grant dan revisi. Migrasikan data progres/attempt dengan provenance legacy; tidak memalsukan event tontonan.
6. **API target + client:** backend lebih dulu menerbitkan kontrak yang sudah dites; mobile memakai adapter baru. Client lama tetap mendapat kontrak lama yang telah diamankan atau error/upgrade yang jelas; tidak menerima bentuk setengah lama/setengah baru.
7. **Hentikan writer legacy:** setelah konsumen terverifikasi berpindah, writer progres berbasis persentase harus ditolak/dibatasi. Alias hanya memanggil layanan canonical dengan bukti lengkap; jangan menerjemahkan percent=100 menjadi trusted completion.
8. **Observasi dan cleanup:** catat versi kontrak, status rollout, error/latency dan kesesuaian hasil; hapus kolom/route legacy hanya pada migrasi terpisah setelah audit konsumen.

Rollback: matikan writer/fitur baru melalui konfigurasi rollout yang dikerjakan pada batch implementasi; pulihkan layanan kompatibel yang tetap menerapkan policy. Jangan menghapus attempt/nilai baru atau membuka endpoint raw lama sebagai cara rollback. Backup dan restore diuji sebelum migrasi nyata.

## 5. Perubahan yang diperlukan di Flutter

- CourseEntity saat ini menyimpan id string, duration string dan rating wajib. DTO target membawa id integer, duration_seconds nullable, cover_url nullable, counts, access, revision dan progress. Rating tidak boleh diisi angka rekaan bila tidak tersedia.
- CourseRepositoryImpl yang mengembalikan mock diganti adapter ApiClient. Gunakan repository/Cubit yang ada; pisahkan status gagal/kosong/terkunci. Struktur folder baru hanya bila mengurangi duplikasi.
- CourseSyllabusPage tidak memilih kelas berdasarkan tebakan nama dari katalog. Route membawa course_id/module_id/material_id/quiz_id/attempt_id yang sesuai jenisnya.
- CourseDetailPage tidak menentukan `_completedStep` sendiri; VideoPlayerPage tidak memakai fallback ID materi 5; request progres membawa token dan sesi server.
- QuizPage menerima snapshot attempt, bukan soal/kunci/penilaian lokal; timer mengikuti deadline server. ApiClient perlu metode PUT/PATCH dan dukungan header idempotency/revision saat fiturnya diimplementasikan.
- SessionManager menyimpan tampilan state server, bukan otorisasi; membership aktif tidak otomatis memberi entitlement Android. Cache/draft/progres/grant dipisahkan per akun dan dibersihkan saat pergantian akun.

## 6. Acceptance kontrak sebelum dianggap final

Semua skenario berikut masih rencana tes. Tidak ditandai lulus hanya karena contoh JSON bisa diparse.

- **C01:** admin membuat Course/Lesson/LearningMaterial Draft dengan ID integer; CMS dan API membaca field yang sama.
- **C02:** draft dan URL sumber privat tidak terlihat dari API student maupun legacy public course/lesson/material; guest tidak menerima kunci quiz.
- **C03:** admin preview tidak membuat enrollment/progres/attempt siswa nyata dan tidak mengubah status Draft.
- **C04:** publish gagal jika sumber tidak valid, kuis tidak lengkap, urutan konflik atau parent lintas course; revision stale menghasilkan 409.
- **C05:** dua akun dengan paket berbeda menerima kelas/hak sesuai kebijakan; query user_id/email tidak mengganti identitas.
- **C06:** siswa berhak membuka media melalui grant; expired/revoked/prerequisite mengembalikan kode yang sama di web/API/mobile.
- **C07:** seek/progress_percent palsu tidak membuka aktivitas berikut; duplicate event tidak menggandakan cakupan; completion lama tidak otomatis dianggap trusted.
- **C08:** start attempt concurrent menghasilkan satu attempt aktif; restart mempertahankan deadline; soal student tidak memiliki correct_answer/answer_key/keywords.
- **C09:** autosave sequence stale ditolak; submit double mengembalikan satu hasil; pilihan yang disimpan CMS dinilai benar sesuai pemetaan option ID.
- **C10:** preview Excel memeriksa error di baris >20; apply terkait berkas/aktor/revisi yang sama; retry tidak menggandakan materi; semua hasil Draft.
- **C11:** renewal/refund/grant tidak menghapus riwayat akun; membership, certificate dan entitlement tidak diperlakukan sebagai model yang sama.
- **C12:** mobile menampilkan empty/error dari server, tidak menghasilkan mock success, dan memakai `next_unit.kind` untuk membuka resource yang benar.
- **C13:** backfill diulang pada fixture menghasilkan pemetaan yang sama; ID legacy yang tidak dapat dipetakan ditolak untuk review, bukan dibuang diam-diam.

## 7. Status dan syarat melanjutkan batch

- [x] Acuan kedua repo di-fetch dan diverifikasi.
- [x] Model, migrasi, CMS, endpoint, mobile dan import existing dipetakan.
- [x] Rancangan migrasi/kompatibilitas dan contoh kontrak tersedia.
- [x] Batas batch serta acceptance lintas repo ditulis.
- [ ] K1–K6 LMS-01 ditinjau untuk milestone yang dipilih.
- [ ] Namespace T09, representasi enrollment/revisi dan strategi adapter disepakati sebelum kontrak dibekukan.
- [ ] Implementasi, migrasi fixture dan contract test membuktikan perilaku target.

LMS-02 berstatus **draft teknis selesai disusun dan diselaraskan hingga Batch B11**.

### Tambahan Kontrak Aktual B10 & B11 (`routes/api.php`)

- **Tugas, Gradebook & Instructor (`B10`):**
  - `GET /api/v1/lms/courses/{course}/assignments` & `GET /api/v1/lms/assignments/{assignment}`
  - `POST /api/v1/lms/assignments/{assignment}/draft`
  - `POST /api/v1/lms/assignments/{assignment}/files` & `DELETE /api/v1/lms/assignments/{assignment}/files/{file}`
  - `GET /api/v1/lms/assignment-files/{file}/download`
  - `POST /api/v1/lms/assignments/{assignment}/submit`
  - `GET /api/v1/lms/assignments/{assignment}/result`
  - `GET /api/v1/lms/courses/{course}/grades` (siswa)
  - `GET /api/v1/lms/courses/{course}/gradebook` & `POST /api/v1/lms/gradebook/bulk-publish` (admin/instructor sesuai scope)
  - `POST /api/v1/lms/assignment-submissions/{submission}/grade` & `POST /api/v1/lms/assignment-submissions/{submission}/return`
- **Offline Media, Notifikasi LMS & Operasional (`B11`):**
  - `GET /api/v1/lms/offline/licenses`
  - `POST /api/v1/lms/materials/{material}/offline-license`
  - `GET /api/v1/lms/offline/licenses/{licenseId}/download` (menyertakan header `X-LMS-SHA256`)
  - `POST /api/v1/lms/offline/licenses/{licenseId}/renew`
  - `POST /api/v1/lms/offline/licenses/{licenseId}/revoke`
  - `POST /api/v1/lms/offline/devices/{deviceId}/revoke`
  - `POST /api/v1/lms/offline/sync` (respons per-event `accepted` / `rejected` beserta `reason_code`)
  - `GET /api/v1/user/notifications`
  - `POST /api/v1/lms/notifications/push-token` & `POST /api/v1/lms/notifications/push-token/revoke`
  - `POST /api/v1/lms/notifications/resolve-deep-link`
  - Seluruh request dan response API LMS mempropagasi header korelasi `X-Request-Id`.
