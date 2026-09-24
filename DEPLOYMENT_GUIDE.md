# 🚀 VBAT-WEBSITE Deployment Guide

> **Project:** VBAT-PONSEL Backend
> **Branch:** `dev/solkhan-room` (staging) / `main` (production)
> **Last Updated:** 24 September 2026

---

## 1. Environment Variables

Copy `.env.example` to `.env` and adjust values:

| Variable | Staging | Production | Notes |
|----------|---------|------------|-------|
| `APP_ENV` | `staging` | `production` | |
| `APP_DEBUG` | `true` | `false` | Never `true` in production |
| `APP_URL` | `https://staging.vbat.id` | `https://vbat.id` | Used for asset URLs |
| `DB_CONNECTION` | `mysql` | `mysql` | SQLite only for local dev |
| `FILESYSTEM_DISK` | `public` / `s3` | `s3` | Use S3 for production assets |
| `QUEUE_CONNECTION` | `redis` | `redis` | Use queue worker for heavy jobs |
| `CACHE_STORE` | `redis` | `redis` | Redis recommended |
| `CORS_ALLOWED_ORIGINS` | `https://staging.vbat.id,https://admin-staging.vbat.id` | `https://vbat.id,https://admin.vbat.id` | Comma-separated origins |
| `API_BASE_URL` | `https://staging.vbat.id/api/v1` | `https://vbat.id/api/v1` | Mobile team reference |
| `SANCTUM_STATEFUL_DOMAINS` | `staging.vbat.id` | `vbat.id` | For cookie-based auth if needed |

### Required Secrets

Generate application key:

```bash
php artisan key:generate
```

Sanctum uses existing app encryption key; no extra secret required.

---

## 2. Deploy Steps

### Staging (`dev/solkhan-room`)

```bash
# 1. Fetch latest code
git fetch origin
git checkout dev/solkhan-room
git pull origin dev/solkhan-room

# 2. Install dependencies
php "C:/ProgramData/ComposerSetup/bin/composer.phar" install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Seed if needed (idempotent)
php artisan db:seed --force

# 5. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Storage link
php artisan storage:link

# 7. Restart queue workers / PHP-FPM
```

### Production (`main`)

Same as staging, but checkout `main` and ensure `APP_DEBUG=false`.

---

## 3. Rollback Procedure

### Code Rollback

```bash
git log --oneline -5
# Identify last known good commit
git reset --hard <COMMIT_HASH>
# Or if using tag-based deployment:
git checkout tags/<TAG>
```

### Database Rollback

```bash
# Rollback last N migrations
php artisan migrate:rollback --step=N
```

> **Warning:** Only rollback if the migrations have not caused data loss. Always backup before rollback.

---

## 4. Health Checks

After deployment, verify:

```bash
curl -s https://staging.vbat.id/api/v1/health | jq
```

Expected response:

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "service": "vbat-website-api",
    "version": "1.0.0"
  }
}
```

---

## 5. Smoke Test Commands

```bash
# Login as admin and export token
TOKEN=$(curl -s -X POST https://staging.vbat.id/api/v1/auth/login \
  -H "Accept: application/json" \
  -d "email=admin@example.com&password=secret" | jq -r '.data.token')

# Test admin analytics
curl -s "https://staging.vbat.id/api/v1/admin/analytics/dashboard" \
  -H "Authorization: Bearer $TOKEN" | jq

# Test health
curl -s https://staging.vbat.id/api/v1/health | jq
```

---

## 6. Backup Procedure

1. **Database:** scheduled `mysqldump` or provider snapshot
2. **Storage:** provider object-storage versioning
3. **Environment:** keep `.env` in encrypted vault (never commit secrets)

---

## 7. Monitoring Checklist

- [ ] Health endpoint returns 200
- [ ] `php artisan migrate --force` completed without errors
- [ ] Queue worker running for `QUEUE_CONNECTION=redis`
- [ ] Storage read/write verified from external network
- [ ] CORS preflight (`OPTIONS`) succeeds from mobile origin
- [ ] Log rotation configured
- [ ] Credentials redacted from logs
