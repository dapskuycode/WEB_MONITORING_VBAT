# Hasil B06 — video dan progres

Tanggal: 10 November 2026, WIB. Implementasi lokal LMS-12/13, melengkapi progres/next_unit LMS-10. Web/mobile tetap repo terpisah; sebelum batch kedua remote berhasil di-fetch. Acuan pengguna tetap web `main` dan mobile `haidarreview`; perubahan lokal batch sebelumnya dipertahankan. Belum commit/push/PR/deploy atau migrasi database utama.

## Hasil

- Pengguna menyetujui video selesai pada cakupan unik ≥95% durasi (usulan FSD). Server juga memeriksa posisi maksimum ≥95%. Ini hanya menyelesaikan bagian video K4; aturan kuis/grading/HS lain belum disetujui.
- Durasi diperiksa melalui YouTube Data API `status,contentDetails` saat membuka sesi. API key tidak dikirim ke aplikasi. Key hilang, video private/tidak embeddable, durasi nol/live/tidak valid dan provider gagal menolak pembukaan sesi. Durasi authoring dan persentase klien tidak dipercaya.
- Sumber terverifikasi disimpan terpisah dari authoring. Progres dipisah menurut akun, materi dan hash video+durasi; source baru tidak mewarisi completion lama. Revisi materi mengakhiri sesi aktif; judul baru dengan sumber sama tetap memakai cakupan sumber yang sama. Ini batas teknis pilot, bukan keputusan final pembekuan revisi kurikulum/grade.
- Start memakai request UUID; heartbeat memakai event UUID dan sequence berurutan. Retry persis sama mengembalikan acknowledgement tersimpan, payload konflik/out-of-order ditolak. Lock akun menyerialkan mutasi; sesi baru menggantikan playback lama di akun tersebut. Sesi kedaluwarsa setelah 90 detik tanpa heartbeat.
- Cakupan dihitung sebagai gabungan interval unik. Kredit hanya untuk kemajuan yang masuk akal saat status sebelumnya playing, dibatasi waktu server dan gap heartbeat 15 detik. Seek ke depan/laporan ended tidak otomatis selesai; rewind tidak menggandakan cakupan. Posisi yang ditolak mengembalikan anchor server dan meminta resync. Telemetri ini tidak membuktikan perhatian atau mencegah skrip yang meniru tontonan dalam waktu nyata.
- Akses, status terbit, revision dan source dicek sebelum heartbeat maupun replay. Sesi akun lain 404; sesi digantikan/kedaluwarsa/akses berubah 409. Endpoint persentase lama kini 410 `PLAYBACK_SESSION_REQUIRED`, tanpa menulis view.
- API silabus dan daftar mengembalikan progres materi, agregat modul/kelas dan next_unit. Materi wajib berurutan, aktivitas opsional tidak memblokir, materi yang sudah dibuka pada sumber saat ini dapat dikunjungi ulang selama hak akses aktif. Completion quiz/PDF belum tersedia: quiz wajib tetap memblokir sampai B07, bacaan sampai B08. Kelas tanpa aktivitas wajib tidak otomatis 100%. Media serve/access-check/manifest juga memeriksa prerequisite; gate bukan sekadar flag UI.
- Player Flutter memakai sesi playback melalui ApiClient, resume server dan heartbeat 5 detik termasuk saat jeda. Kontrol putar/jeda/ulang 10 detik; seek maju/rate control tidak ditampilkan. Selesai hanya mengikuti acknowledgement server. Silabus dimuat ulang ketika kembali dari materi dan tombol Lanjutkan memakai referensi server.
- Koneksi gagal menjeda player dan menampilkan pending/retry. Tracker mempertahankan event dan sequence yang sama sampai acknowledgement; tidak ada completion optimistis. Respons terlambat setelah logout/pergantian akun dibuang. Background menutup player; resume membuka sesi baru. Keluar/background sebelum heartbeat bisa kehilangan bagian terakhir ≤interval sinkronisasi normal dan perlu ditonton ulang; jaringan/lifecycle tertunda dapat kehilangan lebih banyak bagian yang belum diakui.

## Kontrak aktual

Semua endpoint membutuhkan Sanctum dan menggunakan akun autentikasi; respons sukses `success=true,data={...}` serta `Cache-Control: private, no-store`.

- `POST /api/v1/lms/materials/{id}/playback`: `{request_id: UUID}`. Data: session_id, video_id, sequence, duration_seconds, resume_position dan progress (available, percent, covered_seconds, completed).
- `POST /api/v1/lms/playback/{session}/events`: `{event_id: UUID, sequence: integer, position_seconds: number, state: playing|paused|ended}`. Data acknowledgement: session_id, event_id, sequence, duration_seconds, resume_position, progress dan needs_resync; video_id hanya pada start.
- `GET /api/v1/lms/courses` dan detail: `progress.available=true` untuk agregat, persentase berdasarkan aktivitas wajib selesai. Silabus material video memiliki percent/completed/resume_position; modul memiliki agregat sendiri. next_unit adalah referensi kind/id/course_id/module_id, bukan grant. Baca can_open/reason pada aktivitas yang cocok. Kuis tidak dapat dibuka sebelum implementasi attempt B07.

## Verifikasi

- Backend: **96 tes / 705 assertions lulus**, meliputi playback, student courses, akses media, authoring, boundary, materi, auth, review findings dan hierarki/sidebar. Delapan tes playback baru mencakup threshold 95%, prerequisite, replay/sequence, seek/flood, rewatch/resume, penggantian sesi, ownership/expiry/revoke/revision, provider/gap, perubahan durasi, quiz barrier dan penolakan progres legacy. Ini fixture HTTP/database, bukan uji beban atau provider nyata.
- Flutter: **49 tes lulus** pada playback, student courses, auth, LMS access, regression flow dan smoke. Delapan tes baru memeriksa token/resume, lost acknowledgement dengan event identik, single in-flight, resync, sesi digantikan, logout terlambat, 401/malformed acknowledgement, parent next_unit dan UI gagal pada teks 200%.
- PHPStan layanan/controller/model terkait: **0 error**. Flutter analyzer exit 0 tanpa error/warning; empat info existing di analytics_tracker. Pint dan diff whitespace checks lulus. UI tetap mengikuti Material/theme VBAT dan antislop during yang dipilih pengguna; tombol berlabel, status gagal terpisah, Wrap untuk kontrol. Fixture silabus memakai tema terang/gelap, lebar 390/820 dan teks 200%. Tidak ada klaim audit aksesibilitas menyeluruh, performa terukur, atau playback WebView/native yang belum diuji.

## Belum dijalankan

Migrasi baru `2026_11_10_120000_create_lms_playback_tables.php` baru berjalan di database tes. Sebelum staging/produksi, siapkan backup dan jalankan migrasi B04/B06 melalui prosedur deployment, isi `YOUTUBE_API_KEY` backend dengan izin yang sesuai, lalu uji video terbit pada Android/iOS: resume, buffering, seek, background, kehilangan jaringan, pergantian perangkat dan revocation. URL unlisted yang telah diketahui tidak bisa dicabut oleh aplikasi; native secure mode mengikuti kemampuan platform, bukan DRM.

Graphify belum diperbarui setelah implementasi. B07 berikutnya: LMS-14/15/16, attempt kuis dan pilot dua video + satu MCQ. Keputusan default kuis perlu dituntaskan sebelum mengunci kebijakan attempt; persetujuan ambang video tidak sekaligus menyetujui 70/3 percobaan/20 menit/nilai tertinggi.
