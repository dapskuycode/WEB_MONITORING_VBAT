# Hasil B07 — kuis pilihan ganda

Status: implementasi lokal LMS-14/15; LMS-16 sebagian. Kedua remote sudah di-fetch sebelum pekerjaan, tanpa perubahan pada acuan web `main` dan mobile `haidarreview`. Branch lokal tetap `codex/lms-foundation`; perubahan batch sebelumnya dipertahankan. Belum commit/push/PR/deploy atau migrasi database utama.

## Aturan testing

MD FSD menyebut default lulus 70 dan pengaturan waktu/percobaan/pemilihan nilai oleh admin. Pengguna meminta mengikuti MD, lalu memilih yang mudah untuk testing bila belum ditentukan. Default kuis baru pada kode/CMS/API sekarang **lulus 70, tanpa batas percobaan (0), tanpa batas waktu (null), nilai tertinggi**. Kuis existing tidak diubah massal. Batas 1–10 percobaan dan 1–240 menit tetap tersedia serta diuji melalui fixture. Pilihan ini tidak memfinalkan aturan produksi, gate HS atau kebijakan revisi seluruh kurikulum.

## Implementasi

- CMS dan API memakai normalisasi MCQ yang sama: 2–6 opsi label/value unik, satu kunci valid dan bobot positif. Format legacy CMS berupa label+indeks serta API label/value dikenali; format ambigu/invalid ditolak. Publish kuis wajib dan pembukaan attempt memvalidasi definisi. Pilot memakai relasi kuis ke materi canonical yang sudah ada, bukan mesin penempatan kuis baru.
- Attempt menyimpan snapshot soal, opsi, kunci internal, bobot dan aturan saat mulai. Mengubah soal/aturan tidak mengubah penilaian attempt tersebut. Student menerima serializer eksplisit tanpa kunci, termasuk setelah lulus; field snapshot juga disembunyikan dari serialisasi model.
- Start memakai request UUID dengan pemetaan persisten ke attempt, termasuk start yang sebenarnya melanjutkan attempt aktif. Replay setelah attempt selesai tetap mengembalikan attempt yang sama. Lock akun/kuis menyerialkan start/save/submit. Pengguna lain mendapat 404; identitas/score dari klien ditolak.
- Autosave menyimpan jawaban dan tanda ragu-ragu bersama version. Stale version atau request UUID dengan payload berbeda ditolak 409. Submit hanya menilai jawaban yang sudah tersimpan, bersifat final dan aman diulang. Skor berbobot dalam bilangan bulat memakai floor agar 69,6 tidak dibulatkan menjadi lulus 70. Nilai tertinggi/hasil lulus berasal dari attempt server, bukan flag klien.
- Deadline disimpan server; jawaban setelah deadline tidak mengubah nilai. Attempt kedaluwarsa difinalisasi dari jawaban tersimpan ketika dibaca/disimpan maupun melalui `lms:expire-quiz-attempts`, dijadwalkan setiap menit. Hasil yang sudah final tetap sama.
- Gate kuis memeriksa paket, status published/aktif, relasi materi dan completion video serta prerequisite server. Hasil terikat hash sumber materi; legacy attempt tidak membuka unit. Mengganti sumber menandai attempt aktif lama invalidated dengan alasan source_changed dan tidak memakai hasilnya pada sumber baru. Attempt tersebut tidak menghabiskan kuota baru; hasil final lama tetap tercatat pada sumbernya. Kebijakan migrasi/revisi produksi yang lebih luas tetap LMS-07/K5.
- Kuis dengan riwayat attempt tidak dihapus melalui API; CMS menonaktifkannya. CMS menolak memindahkan kuis ber-attempt ke materi lain. Snapshot melindungi hasil dari perubahan/penghapusan soal; ini belum merupakan audit revisi admin yang lengkap.
- Progres modul/kelas dan next_unit kini menghitung kuis wajib yang lulus. Dua video wajib dan satu kuis wajib baru membuat kelas 100% setelah ketiganya selesai. Materi PDF/reader masih menunggu B08; HS/sertifikat tetap belum dibuka oleh batch ini.
- Endpoint start/submit kuis legacy kini 410 TRUSTED_ATTEMPT_REQUIRED. Jalur public metadata tetap disanitasi; meta kelulusan legacy tidak dipakai lagi oleh controller public.
- Runner Flutter memakai ApiClient/repository/controller. Mendukung mulai/lanjut, nomor soal, navigasi, pilihan tunggal, tanda ragu-ragu yang tersimpan, autosave setelah 800 ms, simpan manual, flush sebelum submit, konfirmasi dan hasil server. Countdown memakai waktu server dan Stopwatch, bukan jam kalender perangkat. Tanda ragu-ragu tidak memengaruhi nilai.
- Satu mutasi dikirim pada satu waktu. Perubahan selama save diproses setelah acknowledgement sebelumnya. Network/429/5xx menampilkan belum tersimpan; retry memakai UUID/version/payload yang sama. Konflik meminta reload untuk meninjau jawaban. Respons terlambat setelah logout dibuang. Akses diperiksa ulang saat operasi, berkala 30 detik ketika idle dan saat kembali ke foreground. Perubahan yang belum diakui server hanya ada di memori; keluar menampilkan peringatan dan tidak menjanjikan pemulihan draft offline.
- Route canonical `/courses/{courseId}/modules/{moduleId}/quizzes/{quizId}` dicocokkan terhadap silabus, terpisah dari ID materi yang bisa bernilai sama. Route `/quiz` tanpa ID tidak lagi menjalankan soal/penilaian contoh.

## Kontrak aktual

Semua route baru memakai Sanctum dan `success=true,data={...}` pada sukses, dengan Cache-Control private/no-store. Identitas berasal dari akun autentikasi. Request idempotensi dikirim pada body `request_id`, mengikuti pola aktual playback B06; contoh kontrak FSD yang memakai header belum menjadi seluruh kontrak aplikasi.

- GET `/api/v1/lms/quizzes/{id}`: metadata, batas, remaining_attempts (null berarti tanpa batas), can_start, passed, best_score dan attempt aktif atau hasil tertinggi milik akun pada sumber saat ini.
- POST `/api/v1/lms/quizzes/{id}/attempts`: `{request_id: UUID}` → attempt baru, lanjutan aktif atau replay start yang sama.
- GET `/api/v1/lms/attempts/{id}`: snapshot student, jawaban, flagged_question_ids, status/version, deadline_at/server_time dan hasil final bila ada.
- POST `/api/v1/lms/attempts/{id}/answers`: `{request_id,expected_version,answers:[{question_id,value:string|null}],flagged_question_ids:[id]}`. Answers dan flags adalah kumpulan lengkap, bukan patch. Soal/opsi/flag asing ditolak. Respons adalah attempt versi server.
- POST `/api/v1/lms/attempts/{id}/submit`: `{request_id,expected_version}`. Tidak menerima jawaban atau skor; klien menyimpan dahulu. State akhir submitted/expired/invalidated bersifat readonly.
- Rate limit baru: read 120/menit/akun, start+submit 30/menit/akun, autosave 60/menit/akun/attempt. Status error 401/403/404/409/422/429 mengikuti identitas, akses dan validasi.

## Bukti verifikasi

- **Backend: 107 tes / 864 assertions lulus.** Sebelas tes StudentQuiz baru meliputi prerequisite/legacy pass, resume/start alias, autosave replay/version/ownership, flags, snapshot grading, deadline/late answers, scheduler, batas vs unlimited, opsi/identitas/revocation, CMS → API grading, pergantian source dan pilot dua video lewat API playback → MCQ → kelas 100%. Suite juga mencakup playback, daftar/silabus, media, authoring, boundary, materi, auth dan hierarki/sidebar.
- **Flutter: 62 tes lulus**, termasuk 13 tes quiz repository/controller/widget. Kasus lost acknowledgement, perubahan saat in-flight, flush+submit retry, late/expired result, respons konflik/asing, logout/401, collision ID material/quiz, tautan legacy, runner interaktif dan layout diuji bersama suite belajar/player/auth/regression/smoke.
- PHPStan untuk QuizAttemptService, QuizDefinitionService, LmsProgressService, StudentQuizController, QuizApiController, StudentCourseController dan QuizAttempt: **0 error**. Pemeriksaan yang diperluas ke CMS/authoring belum bersih, antara lain properti/metode CMS tanpa tipe; lint seluruh proyek tidak diklaim lulus. Tidak menambah suppress/baseline untuk menyembunyikannya.
- Flutter analyzer exit 0 tanpa error/warning; empat info existing analytics_tracker. Pint dan diff whitespace checks lulus. Build aset web lulus; peringatan package fontaine opsional tidak menghalangi build.
- Render fixture `screenshots/B07-quiz-light.png` dan `B07-quiz-dark.png` diperiksa. Reflow 390/820 px, terang/gelap dan teks 200% tanpa overflow. Radio, checkbox, navigasi dan tombol tetap kontrol Material berlabel; tombol yang berada di bawah layar dapat dijangkau melalui scroll.

## Pemeriksaan UI

Antislop during mengikuti pilihan sesi. Arah meneruskan VBAT/Material, ENERGY 1 / RHYTHM 2 / MOTION 1. Warna biru dari tema membantu menandai pilihan/tindakan; layout satu kolom menjaga urutan soal–opsi–simpan; tipografi tema dan jarak antarkontrol menjaga keterbacaan/tap target. Navigasi bernomor membantu berpindah soal dan label ragu-ragu menjelaskan statusnya. Tidak ada aset dekoratif baru, animasi atau klaim hasil rekaan. Loading/pending/error/expired/hasil final berbeda dan dapat diuji. Ini pemeriksaan scope baru melalui widget harness, bukan sertifikasi aksesibilitas seluruh aplikasi atau screenshot perangkat nyata.

## Batas dan kelanjutan

Migrasi `2026_11_10_140000_add_trusted_quiz_attempts.php` hanya dijalankan pada database tes. Sebelum staging/produksi: backup, terapkan migrasi yang diperlukan B04/B06/B07 melalui prosedur deployment, pastikan scheduler Laravel berjalan dan siapkan API key/video YouTube untuk playback nyata.

**LMS-16 / gate M1 masih terbuka:** belum ada pembuktian terpadu pada Android/iOS/staging dengan admin menerbitkan lewat UI, provider nyata, login ulang/perangkat dan kegagalan jaringan nyata. Test backend dan widget tidak menggantikan bukti tersebut. Soal non-MCQ, review manual, cara memilih nilai lain, grading/HS/sertifikat dan audit revisi lengkap mengikuti batch berikutnya. Graphify belum dibangun ulang setelah implementasi; source/test/laporan ini lebih mutakhir.

Batch kode berikut B08: reader PDF/e-book dan import Excel. Tidak ada PR atau deployment yang dibuat pada B07.
