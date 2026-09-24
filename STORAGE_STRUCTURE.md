# 📦 VBAT-WEBSITE — Storage Structure

> **Maintainer:** Solkhan (mohamadsolkhannawawi)
> **Last Updated:** 24 September 2026
> **Related:** DEVELOPMENT_PLANNING.md §3.1 (Storage & Media), §TASK-M1-BE-01

---

## 1. Filesystem Disks

| Disk    | Driver | Root                            | Visibility | Use                                |
| ------- | ------ | ---------------- | ---------- | --------------------------------- |
| `local` | local  | `storage/app/private` | private   | Private app files (not exposed)   |
| `public`| local  | `storage/app/public`   | public    | Sponsor logos, products, campaign banners, learning materials |
| `s3`    | s3     | (S3 bucket)               | configurable | Object storage production/staging (configurable via env) |

Active disk dipilih oleh `FILESYSTEM_DISK` di `.env`. Default: `local` (dev), `public` (server with symlink), `s3` (production).

**Public URL untuk disk `public`:** `{APP_URL}/storage/{path}`. Wajib jalankan `php artisan storage:link` agar `public/storage` symlink aktif.

---

## 2. Directory Layout — `storage/app/public/`

```
storage/app/public/
├── sponsor/
│   ├── logos/                       # Logo akun sponsor (untuk hero/card sponsor)
│   ├── products/                    # Gambar produk sponsor
│   └── campaigns/
│       ├── hero/                    # Banner hero slider (max 6 slide)
│       ├── card/                    # Banner card in-feed (mosaic home)
│       ├── horizontal/              # Banner horizontal shop feed
│       └── popup/                   # Banner popup + CTA
├── learning/
│   ├── thumbnails/                  # Thumbnail course/lesson/unit
│   ├── pdfs/                        # PDF materi (downloadable)
│   └── offline-encrypted/           # Placeholder untuk konten offline (D-005 encrypted)
└── health-check.txt                 # tmp file dibuat oleh HealthController
```

Folder kosong hanya sebagai marker; file aktual akan ditulis oleh:
- `SponsorController` upload → `sponsor/logos/`, `sponsor/products/`
- `CampaignController` upload → `sponsor/campaigns/{hero|card|horizontal|popup}/`
- `LearningMaterialController` upload → `learning/thumbnails/`, `learning/pdfs/`
- `HealthController` test write → `health-check.txt` (di root, auto-deleted)

---

## 3. S3 (Object Storage) Configuration

Untuk pindah ke S3-compatible (IDCloudHost S3, MinIO, AWS, dll.), set di `.env`:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key_id
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=ap-sortheast-1
AWS_BUCKET=vbat-assets
AWS_ENDPOINT=https://s3.idcloudhost.com           # contoh IDCloudHost
AWS_URL=https://vbat-assets.s3.idcloudhost.com    # public URL pattern
AWS_USE_PATH_STYLE_ENDPOINT=false                  # true untuk MinIO self-hosted
```

Catatan:

- `AWS_URL` adalah base URL publik untuk aset. Mobile clients (Flutter) akan menerima URL absolut via API response.
- Folder structure di S3 mengikuti struktur `sponsor/...` dan `learning/...` yang sama (Laravel Flysystem S3 driver otomatis menaruh file di path yang Anda tulis).
- Permission file di S3 harus `public-read` agar bisa diakses tanpa signed URL.

---

## 4. Storage Proxy & CORS

Terdapat route khusus untuk proxy file storage dengan header CORS eksplisit:

```
GET /api/v1/storage/{path}
```

Route ini membaca file dari `storage/app/public/{path}` (atau fallback ke `public/{path}`) dan me-return dengan header:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Content-Type: <mime dari file>
```

Untuk S3, route ini tidak relevan karena URL sudah publik. Tetap disediakan sebagai fallback untuk file private.

---

## 5. Retention & Cleanup

- File orphan (tidak direferensi oleh row manapun) WAJIB dibersihkan via scheduled command (TODO Phase 5).
- Sponsor delete / admin hard-delete → hapus file dari storage mengikuti `Sponsor` model `deleting` event.
- Backup object storage (S3 versioning atau snapshot) sesuai retention policy yang akan didefinisikan di Phase 5.

---

## 6. Verifikasi Storage (Smoke Test)

```bash
# 1. Cek symlink
ls -la public/storage

# 2. Cek writable
php artisan tinker
>>> Storage::disk(config('filesystems.default'))->put('test.txt', 'hello');
>>> Storage::disk(config('filesystems.default'))->get('test.txt');
>>> Storage::disk(config('filesystems.default'))->delete('test.txt');

# 3. Health endpoint
curl http://localhost:8000/api/v1/health | jq .data.checks.storage
```

Expected: status `ok`, `public_disk_writable: true`.