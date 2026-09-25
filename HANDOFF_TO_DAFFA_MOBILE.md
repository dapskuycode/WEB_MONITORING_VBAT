# VBAT Backend → Mobile Handoff Document

**Date:** 2026-09-25  
**To:** Daffa (Mobile Lead)  
**From:** Solkhan (Backend Lead)  
**Status:** ✅ Ready for integration testing  

---

## Executive Summary

VBAT Backend v2.0 is feature-complete and API contract frozen. All Phase A–J requirements implemented and tested.

**What's Ready:**
- ✅ Sanctum auth (email/password + OAuth social login)
- ✅ Auto KTA generation on purchase (idempotent)
- ✅ Auto certificate generation + QR public verification
- ✅ Gamification (badges, streaks, achievements)
- ✅ Admin UI (5 Livewire components)
- ✅ Staging deployment runbook + health checks
- ✅ API contract frozen (no breaking changes)

**Testing Environment:**  
- Staging: `https://staging-vbat.quantumtele.io` (example)
- Base URL for local dev: `http://localhost:8000`

---

## 1. Integration Checklist for Mobile

- [ ] Clone repo & pull `dev/solkhan-room` branch
- [ ] Run migrations: `php artisan migrate:fresh --seed`
- [ ] Start server: `php artisan serve`
- [ ] Verify health: `curl http://localhost:8000/api/health`
- [ ] Test login (admin@vbat.local / password)
- [ ] Test membership read (GET /api/membership)
- [ ] Test certificate read (GET /api/certificates)
- [ ] Test badge read (GET /api/badges)
- [ ] Test info content (GET /api/info-contents)
- [ ] Test legal docs (GET /api/legal/terms)
- [ ] Run full API collection (see api-collection.md)

---

## 2. Key Endpoints (Mobile Must Implement)

### Auth Flow
1. POST `/api/auth/login` → get `access_token`
2. GET `/api/user` → verify logged-in state
3. PUT `/api/profile` → update profile (completeness check)
4. POST `/api/auth/logout` → cleanup

### Membership & KTA
1. GET `/api/membership` → current KTA status
2. GET `/api/membership/kta/{ktaNumber}` → public lookup (no auth)

### Certificate
1. GET `/api/certificates` → list (paginated)
2. GET `/api/certificates/{id}` → detail with QR
3. GET `/api/certificates/verify/{qrToken}` → public verification (no auth)

### Gamification (Optional for v1.0, required v1.1)
1. GET `/api/badges` → user badges
2. GET `/api/achievements` → achievement log
3. POST `/api/streak/increment` → daily login streak (idempotent)

### Info & Legal
1. GET `/api/info-contents` → list
2. GET `/api/info-contents/{slug}` → detail (premium gating)
3. GET `/api/legal/terms` → latest T&C
4. GET `/api/legal/privacy` → latest privacy
5. GET `/api/legal/about` → about Quantum Tele

---

## 3. Test Data (3 Accounts)

| Email | Password | Role | Tier | Purpose |
|-------|----------|------|------|---------|
| admin@vbat.local | password | super_admin | gold | Full feature test |
| training@vbat.local | password | admin | silver | Admin features |
| employee@vbat.local | password | user | basic | End-user simulation |

**Note:** Test users created via seeder. Reset with `php artisan migrate:fresh --seed`.

---

## 4. API Contract (Frozen)

See: `API_CONTRACT_FROZEN_V2.0.md`

Key guarantees:
- ✅ All response schemas fixed (no additions/removals until v3.0)
- ✅ All error codes standardized
- ✅ Pagination format stable
- ✅ Authentication flow unchanged
- ✅ Optional fields only for new features
- ✅ Backwards compatible with mobile app v1.2+

---

## 5. Known Quirks & Gotchas

### A. Streak Idempotency
**Endpoint:** `POST /api/streak/increment`

**Behavior:** Calling twice on same day = no increment (idempotent)

```bash
# Day 1, Call 1
POST /api/streak/increment → current_streak: 1

# Day 1, Call 2 (same day)
POST /api/streak/increment → current_streak: 1 (no change)

# Day 2, Call 1
POST /api/streak/increment → current_streak: 2
```

**Mobile Implementation:** Safe to call on app launch — no duplicate increments.

---

### B. QR Token Verification (Public)
**Endpoint:** `GET /api/certificates/verify/{qrToken}` (NO auth required)

**Use Case:** Camera scan → direct HTTP call (no token needed)

**Response (valid):**
```json
{
  "valid": true,
  "certificate_number": "CERT-000001-26",
  "holder_name": "User Name",
  "course": "Advanced Training",
  "issued_at": "2026-09-25T10:00:00Z",
  "status": "active"
}
```

**Response (revoked/invalid):**
```json
{
  "valid": false,
  "reason": "Certificate revoked",
  "revoked_at": "2026-09-26T10:00:00Z"
}
```

---

### C. Premium Content Gating
**Endpoint:** `GET /api/info-contents/{slug}` 

If `metadata.premium_only = true` and user tier < gold:

```json
{
  "error": "UNAUTHORIZED",
  "message": "This content is only for premium members"
}
```

**Mobile Implementation:** Check tier before displaying premium content, or let API reject.

---

### D. Midtrans Webhook → Auto KTA
**Backend:** Listens on `POST /api/webhook/midtrans` (signature-validated)

**Mobile:** No action needed — automatic KTA generation happens server-side.

**Verify:** After purchase, call `GET /api/membership` — KTA should appear.

---

### E. Empty Lists
**All GET endpoints** with no data return:

```json
{
  "data": [],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 0,
    "last_page": 1
  }
}
```

**Mobile:** Render empty state gracefully.

---

## 6. Error Handling Strategy

**Standard error structure:**
```json
{
  "error": "ERROR_CODE",
  "message": "Human-readable message",
  "errors": {
    "field": ["validation message"]  // Only for 422
  }
}
```

**Common errors:**
- `401 UNAUTHENTICATED` — Token missing/expired → force re-login
- `403 UNAUTHORIZED` — Role insufficient → show "not authorized" UI
- `404 NOT_FOUND` — Resource missing → show "not found" UI
- `422 VALIDATION_ERROR` — Input invalid → show field errors to user
- `429 RATE_LIMIT_EXCEEDED` — Too many requests → wait & retry
- `500 INTERNAL_SERVER_ERROR` — Server down → show error + "contact support"

---

## 7. Pagination

All list endpoints support:
- `page` (default: 1)
- `limit` (default: 10, max: 100)
- `sort` (e.g., `created_at:desc`)

**Example:**
```bash
GET /api/certificates?page=2&limit=20&sort=issued_at:desc
```

**Response includes:**
```json
{
  "pagination": {
    "current_page": 2,
    "per_page": 20,
    "total": 45,
    "last_page": 3,
    "next_page_url": "?page=3",
    "prev_page_url": "?page=1"
  }
}
```

---

## 8. Rate Limiting

**Limit:** 60 requests/minute per IP

**Headers (in response):**
- `X-RateLimit-Limit: 60`
- `X-RateLimit-Remaining: 45`
- `X-RateLimit-Reset: 1695667200`

**When exceeded (429):**
```json
{
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Too many requests. Try again in 60 seconds",
  "retry_after": 60
}
```

**Mobile:** Respect `retry_after` header.

---

## 9. CORS Configuration

**Allowed origins:**
- `http://localhost:*` (dev)
- `https://vbat-mobile.app` (production)
- `capacitor://localhost` (Capacitor dev)

**Allowed methods:** GET, POST, PUT, DELETE, OPTIONS

**Allowed headers:** Content-Type, Authorization, X-Requested-With

**Mobile:** Ensure requests include `Origin` header (Capacitor does this automatically).

---

## 10. Deployment Pipeline

**Dev → Staging → Production**

1. **Local dev:** `php artisan serve` (http://localhost:8000)
2. **Staging:** `git push origin dev/solkhan-room` → auto-deploy to staging-vbat.quantumtele.io
3. **Production:** Merge PR to main → manual deployment via runbook

**Staging Health:** `curl https://staging-vbat.quantumtele.io/api/health`

---

## 11. Database Seed Version

**Current production seed:**
- Migrations: `2026_09_25_*` (all up-to-date)
- Seeded data: GamificationSeeder (5 demo badges)
- Test users: 3 accounts (see section 3)

**To reset staging:**
```bash
php artisan migrate:fresh --seed
```

**Timeline:**
- Created: 2026-09-25
- Last updated: 2026-09-25 14:00 WIB
- Version: v2.0

---

## 12. Fixture vs API Discrepancies

**None identified as of 2026-09-25.**

If mobile team finds mismatches:
1. Document in GitHub issue
2. Tag @solkhan for backend fix
3. Include: endpoint, expected vs actual response
4. Will be fixed in patch release (v2.0.1)

---

## 13. Next Steps for Mobile

1. **Week 1:** Integration testing (endpoints + error cases)
2. **Week 2:** UI implementation (login, membership, cert, badges)
3. **Week 3:** Staging QA (full feature test)
4. **Week 4:** Production release (Phase K)

**Blockers:** None known

---

## 14. Contact & Support

**Backend:** Solkhan  
- Slack: @solkhan
- Email: solkhan@quantumtele.io
- Emergency: +62 8xx-xxxx-xxxx

**Mobile:** Daffa  
- Slack: @daffa
- Email: daffa@quantumtele.io

**DevOps:** riset-01 admin  
- Slack: @devops
- Email: ops@quantumtele.io

---

## 15. Sign-Off

**Backend Ready:** ✅ Solkhan (2026-09-25 14:00 WIB)
**Mobile Review:** ⏳ Awaiting Daffa feedback
**Production Deployment:** ⏳ After mobile integration complete

---

**Generated:** 2026-09-25 14:00 WIB  
**Valid Until:** End of Phase K (production release)
