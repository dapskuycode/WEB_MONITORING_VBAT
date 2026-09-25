# Staging Environment Contract — VBAT-WEBSITE (API v2)

## Overview
Staging environment untuk testing integrasi mobile (Daffa) dan QA.

**Base URL**: `https://staging-vbat.quantumtele.io` (example)
**Documentation**: `https://staging-vbat.quantumtele.io/api/docs`

## Authentication
- Sanctum token via `Authorization: Bearer {token}`
- Token lifetime: 24h
- Refresh via `/api/auth/refresh`
- Role-based access (super_admin, admin, user)

## Default Test Users

| Email | Password | Role | Tier | Use Case |
|-------|----------|------|------|----------|
| `admin@vbat.local` | `password` | `super_admin` | Gold | Full admin rights |
| `training@vbat.local` | `password` | `admin` | Silver | Training content management |
| `employee@vbat.local` | `password` | `user` | Basic | End‑user simulation |

## Key Endpoints

### Auth & Profile
- `POST /api/auth/login` — login with email/password
- `POST /api/auth/logout` — logout (requires token)
- `GET /api/user` — current user profile
- `PUT /api/profile` — update profile (completeness checked)

### Membership & KTA
- `GET /api/membership` — current membership
- `GET /api/membership/kta/{ktaNumber}` — lookup KTA (public)
- `POST /api/webhook/midtrans` — Midtrans webhook → auto‑KTA

### Certificate
- `GET /api/certificates` — list user certificates
- `GET /api/certificates/{id}` — certificate detail with QR
- `GET /api/certificates/verify/{qrToken}` — public verification (no auth)

### Gamification
- `GET /api/badges` — user badges
- `GET /api/achievements` — user achievement log
- `POST /api/streak/increment` — increment streak (idempotent)

### Info & Legal
- `GET /api/info-contents` — info contents list
- `GET /api/info-contents/{slug}` — by slug
- `GET /api/legal/terms` — latest terms
- `GET /api/legal/privacy` — latest privacy
- `GET /api/legal/about` — about Quantum Tele
- `GET /api/legal/consent` — consent version

### Admin (super_admin only)
- `GET /api/admin/memberships` — list all memberships
- `POST /api/admin/memberships/issue-kta` — issue KTA manually
- `GET /api/admin/certificates` — list all certificates
- `POST /api/admin/certificates/issue` — issue certificate
- `DELETE /api/admin/certificates/{id}` — revoke certificate
- `GET /api/admin/badges` — CRUD badges
- `GET /api/admin/streak-config` — streak grace/timezone settings
- `GET /api/admin/info-contents` — CRUD info content
- `GET /api/admin/legal-contents` — CRUD legal versions

## Data Seed
Seed yang reproducible:

```bash
php artisan migrate:fresh --seed
```

**Includes:**
- 5 demo badges (first_login, streak_7, training_complete, certificate_earned, premium_member)
- 3 test users (admin, training, employee)
- SponsorTier & TierBenefit records
- FeedConfig placeholder
- InfoContent & LegalContent demo entries

## Environment Variables (Redacted)
Variable kritis yang harus di-set di `.env`:

```env
APP_ENV=staging
APP_KEY=...
APP_URL=https://staging-vbat.quantumtele.io
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=...
DB_DATABASE=vbat_staging
DB_USERNAME=...
DB_PASSWORD=...

SANCTUM_STATEFUL_DOMAINS=staging-vbat.quantumtele.io
SESSION_DOMAIN=staging-vbat.quantumtele.io

MIDTRANS_SERVER_KEY=...
MIDTRANS_CLIENT_KEY=...
MIDTRANS_IS_PRODUCTION=false

QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis

WHATSAPP_API_URL=...
WHATSAPP_PHONE_NUMBER=...
WHATSAPP_PREMIUM_TIERS=gold,platinum,enterprise
```

## Health Check
Endpoint: `GET /api/health`

**Expected response:**
```json
{
  "status": "healthy",
  "timestamp": "2026-09-25T13:58:13Z",
  "database": true,
  "cache": true,
  "queue": true,
  "version": "v2.0.0"
}
```

## Error Codes
| HTTP | Code | Meaning |
|------|------|---------|
| 401 | `UNAUTHENTICATED` | Token missing/invalid |
| 403 | `UNAUTHORIZED` | Role insufficient |
| 404 | `NOT_FOUND` | Resource not found |
| 422 | `VALIDATION_ERROR` | Input validation failed |
| 429 | `RATE_LIMIT_EXCEEDED` | Too many requests |
| 500 | `INTERNAL_SERVER_ERROR` | Server error |

## Backwards Compatibility
API v2 compatible dengan mobile app versi ≥1.2.0.

Breaking changes akan diberi pemberitahuan 30 hari sebelumnya.

## Rollback Procedure
Jika deployment gagal:
1. `git checkout v2.0.0-stable`
2. `php artisan down`
3. `php artisan migrate:rollback --step=5`
4. `php artisan up`

## Contact
- **Backend (Solkhan)**: <dev-backend@quantumtele.io>
- **Mobile (Daffa)**: <dev-mobile@quantumtele.io>
- **Emergency**: <ops@quantumtele.io>
