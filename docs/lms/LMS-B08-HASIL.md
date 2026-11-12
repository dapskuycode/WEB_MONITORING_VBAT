# Hasil B08 — bacaan PDF dan import Excel

Tanggal: 11 November 2026 (WIB). Acuan web main dan mobile haidarreview sudah di-fetch sebelum pekerjaan; tidak ada pembaruan pada kedua acuan. Branch lokal tetap codex/lms-foundation dan perubahan batch sebelumnya dipertahankan. Belum commit/push/PR/deploy atau migrasi database utama.

LMS-18/19 terimplementasi lokal. LMS-17 sebagian: pipeline/reader lokal tersedia, sedangkan penyimpanan cloud, scanner nyata dan aplikasi target masih perlu verifikasi. Pilot e-book memakai PDF; EPUB belum didukung.

## Upload dan penyajian PDF

- Upload admin memeriksa role, purpose course_material, MIME aktual, header PDF dan ukuran maksimal 10 MB. Nama penyimpanan UUID, checksum SHA-256 dan pemilik disimpan; file berada pada disk LMS privat, awalnya quarantine.
- lms:scan-uploads memproses maksimal 25 file per putaran dan dijadwalkan setiap menit. ClamAV harus berhasil, lalu pdfinfo memverifikasi dokumen tidak terenkripsi dengan 1–1000 halaman. Ready baru ditulis setelah semua pemeriksaan berhasil. Malware/format invalid menjadi rejected; scanner/parser tidak tersedia tetap quarantine dengan alasan, bukan ready palsu. Lokasi executable melalui LMS_CLAMSCAN_BINARY dan LMS_PDFINFO_BINARY.
- ID upload dapat ditautkan oleh pemilik dengan purpose cocok setelah ready. Materi yang sudah terhubung dapat dikelola admin lain sesuai akses CMS, tanpa menjadikan ID upload asing sebagai grant bebas. API dan CMS tidak lagi langsung menaruh upload baru sebagai PDF siap terbit. CMS menyediakan unggah untuk diperiksa, status dan ID upload.
- Publish memakai sumber ready dan memeriksa checksum, path privat serta ketiadaan salinan pada disk publik. Penggantian file materi tetap melalui Draft/revisi. Signed media tetap terikat akun/masa berlaku dan memeriksa entitlement/prerequisite. Jalur media lama juga memerlukan upload ready; PDF legacy tanpa hasil pemeriksaan tidak lagi disajikan kepada siswa. Pemindahan file legacy dari public ke private saja belum merupakan bukti scan.
- Storage yang dipakai dan diuji masih adapter disk privat lokal existing. S3/object storage cloud belum dihubungkan atau diverifikasi. LmsFileService memakai path lokal; dukungan cloud memerlukan adapter streaming/scan yang sesuai sebelum rilis cloud. Tidak mengklaim cloud aktif.

## Reader dan completion

- API reading menyimpan sesi, source hash/revisi, urutan event, resume page, halaman maksimum, akhir yang pernah dicapai dan completed_at per akun/materi/sumber. Start/event idempotent; event konflik/urutan stale ditolak. Sesi berlaku 30 menit dan diperpanjang oleh event sah. Akses, kesiapan file, checksum, source/revisi dan expiry diperiksa sebelum menerima event maupun replay.
- Mencapai halaman terakhir belum menyelesaikan bacaan. Event halaman harus sudah diterima server, lalu siswa menekan konfirmasi selesai pada halaman terakhir. Baru acknowledgement server membuka aktivitas berikutnya dan menambah agregat kelas. Ini bukti navigasi/konfirmasi, bukan bukti membaca semua halaman atau memahami isi. Persentase navigasi bisa 100% sebelum konfirmasi, tetapi completed tetap false.
- Runner Flutter mengganti daftar/link PDF contoh pada jalur EbookPage dengan reader ber-ID dari silabus. Download memakai ApiClient/Bearer hanya untuk origin/path API yang sesuai, menolak redirect, membatasi ukuran dan memeriksa SHA-256. Signed URL bukan pengganti autentikasi. PDF diserahkan sebagai bytes ke pdfrx; jalur ini tidak menulis file offline aplikasi atau membuka PDF melalui URL eksternal.
- Posisi tersimpan dan status pending/retry terlihat. Event yang gagal mempertahankan identitas/payload yang sama; tidak ada completion lokal optimistis. Perubahan halaman saat save diproses berurutan. Error akses fatal membuang bytes dari state; logout/respons terlambat tidak memulihkan dokumen. Akses diperiksa lewat event dan heartbeat 60 detik; background menutup reader, resume membuka sesi/download baru. Kontrol tautan di dalam PDF tidak membuka browser eksternal. Secure mode mengikuti kemampuan platform existing, bukan DRM atau jaminan penghapusan seluruh cache native.
- Link ebook legacy tanpa ID valid hanya menampilkan petunjuk. PDF berstatus belum siap tidak dapat dibuka dari silabus. Kuis setelah PDF dapat memakai completion bacaan yang dikonfirmasi server melalui service prerequisite yang sama.

## Import canonical

- Halaman admin bulk upload sebelumnya membuat kelas demo ketika dibuka dan menulis ke tabel Video legacy. Halaman aktif sekarang memakai BulkUpload dan BulkImportService canonical yang sama dengan API; komponen legacy menjadi wrapper. Membuka halaman tidak membuat kelas/data.
- Template XLSX memakai 15 kolom FSD 5.5 ditambah file_upload_id opsional. XLSX/CSV maksimal 5 MB, satu sheet, maksimal 1000 baris/16 kolom. Kelas dan modul harus sudah ada dengan kode, judul dan urutan yang cocok; import tidak membuat/mengubah modul secara implisit.
- Semua baris diperiksa, termasuk setelah baris 20. Header asing/duplikat, formula, macro/external links dan batas arsip/XML/worksheet yang tidak aman ditolak. Formula tidak dievaluasi. Boolean TRUE/FALSE dan 1/0 dipetakan eksplisit. Durasi input video hanya metadata Draft; playback B06 tetap memverifikasinya melalui provider.
- Kode materi dan urutan duplikat/terpakai ditolak. Mode hanya create, tanpa overwrite atau skip-invalid. PDF/ebook dirujuk lewat upload ready milik aktor; nama yang ambigu harus memakai UUID upload. ID dan nama yang diberikan bersama harus cocok. Path atau URL file dari spreadsheet tidak dipercaya.
- Kode kuis baru membuat placeholder kuis wajib, nonaktif dan tanpa soal, terkait materi baru. Posisi after_material atau end_of_module didukung melalui anchor materi; akhir modul harus berada pada materi terakhir. Kode kuis existing ditolak agar tidak dipindah/ditimpa. Admin wajib melengkapi soal, aturan dan aktivasi sebelum publish.
- Preview menyimpan normalized rows, error, aktor, hash berkas, fingerprint kelas/modul dan hash upload, dengan masa berlaku 30 menit. Apply menggunakan data server dari preview ID. Perubahan konten/file, preview expired, aktor berbeda atau input rows/skip-invalid dari klien ditolak. Mengganti file di CMS membatalkan preview sebelumnya.
- Apply berjalan atomik dan memakai authoring service untuk validasi/audit/revisi. Satu kegagalan membatalkan seluruh perubahan. Hasil selalu Draft, replay preview yang sudah applied mengembalikan hasil yang sama. Audit import mengacu kelas terkait dan menyimpan import ID/hash, sementara audit authoring mencatat materi per perubahan. Publish tetap manual melalui CMS/preview B04.
- Laporan kesalahan XLSX berisi nomor baris, nama materi, field, masalah dan saran. Sel ditulis sebagai string literal agar isi data tidak menjadi formula. UI membedakan belum diterapkan dan sukses; tidak menampilkan sukses rekaan.

## Endpoint aktual

Semua route LMS berikut memakai Sanctum; operasi upload/import hanya super_admin. Respons JSON sukses success=true/data dan private/no-store.

- POST /api/v1/lms/uploads multipart file+purpose=course_material → 202 upload metadata tanpa path. GET /api/v1/lms/uploads/{uuid} hanya pemilik.
- POST /api/v1/lms/materials/{id}/reading body request_id UUID → session_id, sequence, source_hash, page_count, resume_page, progress, size_bytes dan signed media_url.
- POST /api/v1/lms/reading/{uuid}/events body event_id,sequence,page,confirm_read → snapshot server. Angka progress_percent/identitas klien tidak diterima. Completion menuntut event akhir sebelumnya dan konfirmasi eksplisit.
- GET /api/v1/lms/imports/template → template XLSX. POST /api/v1/lms/imports multipart file → preview ID dan seluruh hasil pemeriksaan. GET /api/v1/lms/imports/{uuid} → hasil milik aktor. POST /api/v1/lms/imports/{uuid}/apply → hasil Draft atau replay. GET /api/v1/lms/imports/{uuid}/errors → laporan XLSX.
- Endpoint bulk-import legacy hanya mendelegasikan preview; preview=false ditutup 410 dan tidak menulis data. Legacy material PDF upload menerima karantina 202, kemudian ID ready harus ditautkan melalui authoring dengan revision; tidak otomatis mengganti materi.
- Upload/import preview dibatasi 10 request/menit/akun, reading start/event 60/menit/akun. Backend juga membatasi ukuran/row/page; rate limit tidak menggantikan pemeriksaan role/sumber.

## Verifikasi

- Backend: **119 tes / 971 assertions lulus**, mencakup 12 tes baru dokumen/import dan suite regresi B02–B07. Kasus upload private/quarantine/scan failure/malware/MIME/purpose/owner, end-page+confirm, replay/order, resume, source/revision/expiry, signed media legacy, semua 25 baris, atomic apply, actor/tamper/stale/expiry, file source, formula/multi-sheet/header, template/error download, Livewire preview/apply dan larangan kelas demo diuji. Scanner/parser pada tes API diganti fixture inspection; ini bukan bukti ClamAV nyata sudah bekerja.
- Flutter: **71 tes lulus**, termasuk sembilan tes reader baru dan regresi kuis/player/sesi. Token/origin/redirect/checksum, respons terlambat/401, konfirmasi/retry, perubahan halaman in-flight, tautan legacy dan layout 390/820 px, terang/gelap, teks 200% diuji. Tes layout menginjeksi viewer fixture, sehingga tidak dinyatakan sebagai uji WebView/native Android/iOS.
- Harness terpisah dart run tool/verify_pdf_fixture.dart memakai engine PDFium nyata pada host: fixture PDF valid dua halaman dibuka dan halaman pertama berhasil dirender. Native asset diunduh melalui build hooks resmi. Ini membuktikan engine host, bukan seluruh runner/perangkat target.
- PHPStan layanan upload/inspection/reading/import/progress/entitlement, model upload/import dan dua controller baru: 0 error. Flutter analyzer exit 0 tanpa error/warning, empat info existing analytics_tracker. Pint, diff whitespace dan build aset web lulus. PHPStan seluruh CMS legacy tidak diklaim bersih, sebagaimana batas B07.
- Dependency pdfrx 2.4.8 dipilih resolver sesuai SDK proyek, crypto dipakai untuk checksum; pdfrx_engine 0.4.7 merupakan dev dependency untuk harness. Acuan API: [dokumentasi resmi PdfViewer](https://pub.dev/documentation/pdfrx/latest/pdfrx/PdfViewer-class.html); kode diuji terhadap versi terpasang/lockfile, bukan diasumsikan sama dengan latest.

## Batas dan langkah operasional

Migrasi 2026_11_11_080000_create_lms_documents_and_imports.php hanya berjalan di database tes. Tidak mengubah DB utama. Sebelum staging/rilis: backup, terapkan migrasi B04/B06/B07/B08 yang diperlukan, siapkan ClamAV dengan database signature terbaru serta pdfinfo, jalankan scheduler Laravel, dan verifikasi scanner/parser pada sampel nyata. Pada host sesi ini ClamAV belum tersedia; upload nyata akan tetap quarantine sesuai desain. Jangan mengubah status ready secara manual sebagai jalan pintas.

PDF legacy harus didaftarkan melalui upload, diperiksa, lalu ditautkan sebagai ID ready lewat Draft/revision sebelum kembali disajikan. Rencanakan migrasi ini sebelum mengaktifkan kode pada data produksi; perintah privatize-pdfs hanya memindahkan/verifikasi salinan, tidak melakukan malware scan. Object storage cloud, EPUB, native Android/iOS, HTTPS/deployment serta uji pilot M1/staging tetap terbuka. Kebijakan revisi/enrollment dan K3–K6 belum otomatis disetujui oleh mode teknis B08.

Tidak membuat graph baru; graphify masih lebih tua dari implementasi. Batch kode berikut B09: tipe soal/penilaian lanjutan dan hasil belajar, mengikuti keputusan grading/HS yang belum final.
