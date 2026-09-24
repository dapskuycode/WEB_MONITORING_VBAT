# 🔗 CORS Configuration — VBAT-PONSEL Backend

> **Phase:** 0.6 (TASK-M1-BE-01)  
> **Branch:** `dev/solkhan-room`  
> **Dokumen ini menjelaskan konfigurasi CORS untuk integrasi Flutter Web (CanvasKit) & Mobile.**

---

## 1. Ringkasan

File konfigurasi CORS berada di:

```
config/cors.php
```

Konfigurasi ini dikelola melalui environment variable `CORS_ALLOWED_ORIGINS` di `.env`.

---

## 2. Environment Variable

| Variable | Default | Keterangan |
|----------|---------|------------|
| `CORS_ALLOWED_ORIGINS` | `*` | Daftar origin yang diizinkan, dipisahkan koma. Contoh: `http://localhost:3000,https://web.vbat.id` |

---

## 3. Konfigurasi per Environment

### Development (Local)

```env
# Mengizinkan semua origin (untuk development Flutter Web & Mobile emulator)
CORS_ALLOWED_ORIGINS=*
```

### Staging

```env
# Batasi ke domain staging Flutter Web & Admin Panel
CORS_ALLOWED_ORIGINS=https://staging.vbat.id,https://admin-staging.vbat.id
```

### Production

```env
# Hanya domain production yang diizinkan
CORS_ALLOWED_ORIGINS=https://vbat.id,https://web.vbat.id,https://admin.vbat.id
```

---

## 4. Detail Konfigurasi (`config/cors.php`)

| Key | Nilai | Penjelasan |
|-----|-------|------------|
| `paths` | `['api/*']` | CORS hanya diterapkan pada route API (`/api/*`) |
| `allowed_methods` | `['*']` | Mengizinkan semua HTTP method (GET, POST, PUT, DELETE, OPTIONS) |
| `allowed_origins` | Dari `env('CORS_ALLOWED_ORIGINS')` | Origin yang diizinkan, dipisahkan koma |
| `allowed_origins_patterns` | `[]` | Tidak menggunakan regex pattern |
| `allowed_headers` | `['*']` | Mengizinkan semua header |
| `exposed_headers` | `[]` | Tidak ada header tambahan yang di-expose |
| `max_age` | `0` | Tidak meng-cache preflight |
| `supports_credentials` | `false` | Tidak mengirim cookie/credentials (sesuaikan jika pakai Sanctum dengan credentials) |

> **Catatan:** Jika menggunakan Laravel Sanctum dengan `withCredentials: true` di Flutter/Dio, ubah `supports_credentials` menjadi `true` dan pastikan `allowed_origins` tidak menggunakan wildcard `*`.

---

## 5. Integrasi dengan Flutter

### Flutter Web (CanvasKit)

Flutter Web dengan renderer CanvasKit memerlukan CORS untuk:
- Mengakses gambar/video dari URL API
- Melakukan HTTP request ke backend API

Konfigurasi CORS ini memastikan:
- Preflight `OPTIONS` request berhasil
- Header `Access-Control-Allow-Origin` dikirim pada response API

### Flutter Mobile (Android/iOS)

Meskipun CORS tidak diterapkan secara ketat pada mobile app native, konfigurasi ini tetap berguna untuk:
- WebView dalam app
- Testing dengan Flutter Web emulator
- Konsistensi konfigurasi

---

## 6. Verifikasi CORS

### 6.1 Preflight Request Test

```bash
curl -X OPTIONS http://localhost:8000/api/v1/health \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v
```

Response yang diharapkan:
```
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

### 6.2 Actual Request Test

```bash
curl http://localhost:8000/api/v1/health \
  -H "Origin: http://localhost:3000" \
  -v
```

Response yang diharapkan:
```
HTTP/1.1 200 OK
Access-Control-Allow-Origin: *
Content-Type: application/json
```

---

## 7. Perubahan File

File yang dibuat/diubah pada Phase 0.6:

| File | Aksi | Keterangan |
|------|------|------------|
| `config/cors.php` | ✅ Dibuat | Konfigurasi CORS middleware |
| `.env.example` | ✅ Diupdate | Tambah `CORS_ALLOWED_ORIGINS` |
| `docs/CORS_CONFIG.md` | ✅ Dibuat | Dokumentasi ini |

---

## 8. Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| `No 'Access-Control-Allow-Origin' header` | CORS middleware tidak aktif atau origin tidak cocok | Periksa `config/cors.php` dan `CORS_ALLOWED_ORIGINS` |
| `CORS policy: Credentials flag is true, but Access-Control-Allow-Origin is '*'` | `supports_credentials=true` tapi origin menggunakan wildcard | Ubah `allowed_origins` ke domain spesifik, bukan `*` |
| Preflight request gagal (405/403) | Method atau header tidak diizinkan | Pastikan `allowed_methods` dan `allowed_headers` mencakup yang dibutuhkan |
| Gambar/video CORS error di Flutter Web | Storage proxy belum dikonfigurasi | Gunakan `/api/v1/storage/{path}` proxy yang sudah ada |

---

## 9. Referensi

- [Laravel CORS Documentation](https://laravel.com/docs/12.x/routing#cors)
- [MDN Web Docs: CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- `DEVELOPMENT_PLANNING.md` — TASK-M1-BE-01 Server Setup
- `HERMES_PROMPT_SOLKHAN.md` — Phase 0.6

---

> **Last Updated:** 24 September 2026  
> **Maintained by:** Solkhan (mohamadsolkhannawawi)
