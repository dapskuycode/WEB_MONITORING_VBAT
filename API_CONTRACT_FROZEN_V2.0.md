# VBAT-WEBSITE API Contract v2.0 — FROZEN

**Date:** 2026-09-25  
**Status:** ✅ FROZEN — No breaking changes after this point  
**Version:** 2.0.0  
**Backend Lead:** Solkhan  
**Mobile Lead:** Daffa  

---

## 1. Schema Definitions

### User Profile
```json
{
  "id": 1,
  "name": "Admin Super",
  "email": "admin@vbat.local",
  "phone": "+6281234567890",
  "address": "Jl. Test No. 123",
  "tier_id": 1,
  "email_verified_at": "2026-09-25T10:00:00Z",
  "created_at": "2026-09-25T10:00:00Z",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

### Membership
```json
{
  "id": 1,
  "user_id": 1,
  "kta_number": "KTA-000001-2026",
  "tier": "gold",
  "status": "active",
  "valid_from": "2026-09-25",
  "valid_until": "2027-09-25",
  "transaction_id": "TXN-20260925-00001",
  "created_at": "2026-09-25T10:00:00Z",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

### Certificate
```json
{
  "id": 1,
  "user_id": 1,
  "certificate_number": "CERT-000001-26",
  "course_id": 1,
  "course_name": "Advanced Training",
  "issued_at": "2026-09-25T10:00:00Z",
  "qr_token": "abc123xyz789",
  "qr_url": "https://api.vbat.io/api/certificates/verify/abc123xyz789",
  "status": "active",
  "revoked_at": null,
  "revoke_reason": null,
  "created_at": "2026-09-25T10:00:00Z",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

### Badge
```json
{
  "id": 1,
  "name": "First Login",
  "description": "Logged in for the first time",
  "icon_url": "https://cdn.vbat.io/badges/first-login.png",
  "criteria": "{}",
  "created_at": "2026-09-25T10:00:00Z",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

### UserBadge (awarded to user)
```json
{
  "id": 1,
  "user_id": 1,
  "badge_id": 1,
  "awarded_at": "2026-09-25T10:00:00Z",
  "created_at": "2026-09-25T10:00:00Z"
}
```

### Streak
```json
{
  "id": 1,
  "user_id": 1,
  "current_streak": 5,
  "longest_streak": 10,
  "last_activity_date": "2026-09-25",
  "created_at": "2026-09-25T10:00:00Z",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

### InfoContent
```json
{
  "id": 1,
  "slug": "whatsapp-contact",
  "title": "WhatsApp Kontak",
  "content": "Hub kami via WhatsApp...",
  "type": "support",
  "is_active": true,
  "metadata": {
    "whatsapp_number": "+6281234567890",
    "premium_only": true
  },
  "created_at": "2026-09-25T10:00:00Z",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

### LegalContent
```json
{
  "id": 1,
  "type": "terms",
  "version": "2.0",
  "title": "Terms of Service v2.0",
  "content": "Syarat dan ketentuan...",
  "effective_date": "2026-10-01",
  "is_current": true,
  "created_at": "2026-09-25T10:00:00Z",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

---

## 2. Endpoint Contract — Auth & Profile

### POST /api/auth/login
**Authentication:** None  
**Request:**
```json
{
  "email": "admin@vbat.local",
  "password": "password"
}
```

**Response 200 (OK):**
```json
{
  "access_token": "1|abc...",
  "token_type": "Bearer",
  "user": { /* User schema */ }
}
```

**Response 401 (Invalid credentials):**
```json
{
  "error": "INVALID_CREDENTIALS",
  "message": "Email or password incorrect"
}
```

**Response 422 (Validation error):**
```json
{
  "error": "VALIDATION_ERROR",
  "message": "Validation failed",
  "errors": {
    "email": ["Email field is required"],
    "password": ["Password must be at least 6 characters"]
  }
}
```

---

### GET /api/user
**Authentication:** Sanctum (Bearer token)  
**Response 200 (OK):**
```json
{ /* User schema */ }
```

**Response 401 (No token):**
```json
{
  "error": "UNAUTHENTICATED",
  "message": "Token missing or expired"
}
```

---

### PUT /api/profile
**Authentication:** Sanctum  
**Request:**
```json
{
  "name": "Updated Name",
  "phone": "+6281234567890",
  "address": "Jl. Updated No. 123"
}
```

**Response 200 (OK):**
```json
{ /* Updated User schema */ }
```

**Response 422 (Validation):**
```json
{
  "error": "VALIDATION_ERROR",
  "errors": {
    "phone": ["Phone format invalid"]
  }
}
```

---

### POST /api/auth/logout
**Authentication:** Sanctum  
**Response 204 (No Content)**

---

## 3. Endpoint Contract — Membership & KTA

### GET /api/membership
**Authentication:** Sanctum  
**Response 200 (OK):**
```json
{ /* Membership schema */ }
```

**Response 404 (No membership yet):**
```json
{
  "error": "NOT_FOUND",
  "message": "Membership not found"
}
```

---

### GET /api/membership/kta/:ktaNumber
**Authentication:** None (Public)  
**Response 200 (OK):**
```json
{
  "kta_number": "KTA-000001-2026",
  "holder_name": "Admin Super",
  "tier": "gold",
  "valid_from": "2026-09-25",
  "valid_until": "2027-09-25",
  "status": "active"
}
```

**Response 404 (KTA not found):**
```json
{
  "error": "NOT_FOUND",
  "message": "KTA number not found"
}
```

---

## 4. Endpoint Contract — Certificate

### GET /api/certificates
**Authentication:** Sanctum  
**Query:** `?page=1&limit=10`  
**Response 200 (OK):**
```json
{
  "data": [
    { /* Certificate schema */ }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 5,
    "last_page": 1
  }
}
```

**Response 200 (Empty list):**
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

---

### GET /api/certificates/:id
**Authentication:** Sanctum  
**Response 200 (OK):**
```json
{ /* Certificate schema */ }
```

**Response 403 (User doesn't own cert):**
```json
{
  "error": "UNAUTHORIZED",
  "message": "You cannot access this certificate"
}
```

**Response 404 (Not found):**
```json
{
  "error": "NOT_FOUND",
  "message": "Certificate not found"
}
```

---

### GET /api/certificates/verify/:qrToken
**Authentication:** None (Public)  
**Response 200 (Valid):**
```json
{
  "valid": true,
  "certificate_number": "CERT-000001-26",
  "holder_name": "Admin Super",
  "course": "Advanced Training",
  "issued_at": "2026-09-25T10:00:00Z",
  "status": "active"
}
```

**Response 200 (Invalid/Revoked):**
```json
{
  "valid": false,
  "reason": "Certificate revoked",
  "revoked_at": "2026-09-26T10:00:00Z"
}
```

**Response 404 (Token not found):**
```json
{
  "error": "NOT_FOUND",
  "message": "Certificate verification token not found"
}
```

---

## 5. Endpoint Contract — Gamification

### GET /api/badges
**Authentication:** Sanctum  
**Response 200 (OK):**
```json
{
  "badges": [
    { /* UserBadge with Badge schema */ }
  ],
  "total_badges": 3
}
```

---

### GET /api/achievements
**Authentication:** Sanctum  
**Response 200 (OK):**
```json
{
  "achievements": [
    {
      "id": 1,
      "type": "certificate_earned",
      "description": "Earned Advanced Training certificate",
      "points": 100,
      "achieved_at": "2026-09-25T10:00:00Z"
    }
  ],
  "total_points": 250
}
```

---

### POST /api/streak/increment
**Authentication:** Sanctum  
**Request:**
```json
{
  "activity_type": "daily_login"
}
```

**Response 200 (OK):**
```json
{
  "current_streak": 5,
  "longest_streak": 10,
  "last_activity_date": "2026-09-25"
}
```

**Response 200 (Already incremented today — Idempotent):**
```json
{
  "current_streak": 5,
  "longest_streak": 10,
  "last_activity_date": "2026-09-25",
  "message": "Streak already incremented today"
}
```

---

## 6. Endpoint Contract — Info & Legal

### GET /api/info-contents
**Authentication:** None  
**Response 200 (OK):**
```json
{
  "data": [
    { /* InfoContent schema */ }
  ]
}
```

---

### GET /api/info-contents/:slug
**Authentication:** Sanctum (if premium-gated)  
**Response 200 (OK):**
```json
{ /* InfoContent schema */ }
```

**Response 403 (Premium content, user not premium):**
```json
{
  "error": "UNAUTHORIZED",
  "message": "This content is only for premium members"
}
```

**Response 404 (Not found):**
```json
{
  "error": "NOT_FOUND",
  "message": "Content not found"
}
```

---

### GET /api/legal/terms
**Authentication:** None  
**Response 200 (OK):**
```json
{ /* LegalContent (latest) schema */ }
```

---

### GET /api/legal/privacy
**Authentication:** None  
**Response 200 (OK):**
```json
{ /* LegalContent (latest privacy) schema */ }
```

---

### GET /api/legal/about
**Authentication:** None  
**Response 200 (OK):**
```json
{
  "id": 5,
  "title": "Tentang Kami",
  "content": "Quantum Tele adalah...",
  "version": "1.0",
  "effective_date": "2026-09-01",
  "updated_at": "2026-09-25T10:00:00Z"
}
```

---

### GET /api/legal/consent
**Authentication:** None  
**Response 200 (OK):**
```json
{
  "id": 6,
  "type": "consent",
  "version": "1.0",
  "title": "Data Consent Form v1.0",
  "content": "Anda setuju dengan...",
  "effective_date": "2026-09-01"
}
```

---

## 7. Admin Endpoints Contract

### POST /api/admin/memberships/issue-kta
**Authentication:** Sanctum (super_admin)  
**Request:**
```json
{
  "user_id": 2,
  "tier_code": "silver",
  "valid_months": 12
}
```

**Response 201 (Created):**
```json
{ /* Membership schema */ }
```

**Response 403 (Not admin):**
```json
{
  "error": "UNAUTHORIZED",
  "message": "Only administrators can issue KTA"
}
```

---

### POST /api/admin/certificates/issue
**Authentication:** Sanctum (super_admin)  
**Request:**
```json
{
  "user_id": 3,
  "course_id": 1,
  "course_name": "Advanced Training",
  "completion_date": "2026-09-20"
}
```

**Response 201 (Created):**
```json
{ /* Certificate schema */ }
```

---

### DELETE /api/admin/certificates/:id
**Authentication:** Sanctum (super_admin)  
**Request:**
```json
{
  "reason": "Certificate reissue requested"
}
```

**Response 204 (No Content)**

**Response 403 (Not admin):**
```json
{
  "error": "UNAUTHORIZED",
  "message": "You cannot revoke this certificate"
}
```

---

## 8. Error Codes Reference

| HTTP | Code | Meaning | Example |
|------|------|---------|---------|
| 200 | OK | Success | Login successful |
| 201 | CREATED | Resource created | Certificate issued |
| 204 | NO_CONTENT | Success (no body) | Logout success |
| 400 | BAD_REQUEST | Invalid format | Malformed JSON |
| 401 | UNAUTHENTICATED | No/expired token | Token missing |
| 403 | UNAUTHORIZED | Insufficient permissions | Not admin |
| 404 | NOT_FOUND | Resource missing | Certificate not found |
| 422 | VALIDATION_ERROR | Input invalid | Email format wrong |
| 429 | RATE_LIMIT_EXCEEDED | Too many requests | > 60 req/min |
| 500 | INTERNAL_SERVER_ERROR | Server error | Database down |

---

## 9. Pagination Standard

**All list endpoints use:**
```json
{
  "data": [ /* items */ ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 25,
    "last_page": 3,
    "next_page_url": "?page=2",
    "prev_page_url": null
  }
}
```

**Query params:**
- `page` (default: 1)
- `limit` (default: 10, max: 100)
- `sort` (e.g., `created_at:desc`)

---

## 10. Events & Webhooks (For Future)

### Midtrans Webhook Event
**Endpoint:** `POST /api/webhook/midtrans`  
**Headers:** `X-Midtrans-Signature: <HMAC-SHA256>`  
**Payload:**
```json
{
  "transaction_id": "TXN-20260925-00001",
  "status": "settlement",
  "order_id": "ORDER-00001",
  "gross_amount": "150000.00",
  "customer_details": {
    "email": "user@vbat.local"
  }
}
```

**Behavior:**
- Verify HMAC signature with `MIDTRANS_SERVER_KEY`
- Create membership if not exists (idempotent via transaction_id)
- Issue KTA automatically
- Return 200 OK (idempotent even if already processed)

---

## 11. Backwards Compatibility

### No Breaking Changes Planned
- API v2.0 supports mobile app ≥ 1.2.0
- All deprecated endpoints removed in v1.9
- New fields are OPTIONAL (backward compatible)
- Schema additions ONLY (no removals)

### Future Versioning
- New major version (v3.0) requires 30-day notice
- Sunset period: 60 days overlap (v2 + v3 both active)
- Mobile team (Daffa) must approve deprecation

---

## 12. Migration & Seed Version

**Current Production State:**
- Database migrations: `2026_09_25` (latest)
- Seeded data: GamificationSeeder (5 demo badges)
- Test users: admin@vbat.local, training@vbat.local, employee@vbat.local

**To Reset Staging:**
```bash
php artisan migrate:fresh --seed
```

---

## 13. Contract Verification Checklist

✅ **All endpoints have:**
- [ ] Request/response schema
- [ ] Auth requirement (Sanctum / None / super_admin)
- [ ] Success case (200/201/204)
- [ ] Error cases (401/403/404/422)
- [ ] Empty list case (for GET all)
- [ ] Idempotency note (for POST)

✅ **Error responses standardized:**
- [ ] All errors include `error` code + `message`
- [ ] Validation errors include `errors` object
- [ ] Consistent HTTP status codes

✅ **Admin write → DB → API → mobile read:**
- [ ] Admin issues KTA → stored in DB → API returns membership
- [ ] Admin issues cert → stored in DB → mobile reads certificate
- [ ] Streak increment → stored in DB → API returns current streak
- [ ] Admin creates badge → stored in DB → mobile displays badge

✅ **No silent breaking changes:**
- [ ] New fields are optional
- [ ] Old fields not removed
- [ ] Pagination format unchanged
- [ ] Error codes stable
- [ ] Authentication flow unchanged

---

## 14. Handoff Checklist (to Daffa — Mobile Team)

- [ ] API contract frozen (this document)
- [ ] Staging environment live (staging-vbat.quantumtele.io)
- [ ] API collection & curl examples (api-collection.md)
- [ ] Test users created (3 accounts)
- [ ] Deployment runbook ready (deployment-runbook.md)
- [ ] Changelog provided (see below)
- [ ] Backwards compatibility guaranteed
- [ ] Health check endpoint working
- [ ] All tests passing (215/215)

---

## 15. Changelog v2.0 (since v1.9)

**New Endpoints:**
- `GET /api/membership/kta/:ktaNumber` (public KTA lookup)
- `GET /api/certificates/verify/:qrToken` (public cert verification)
- `POST /api/streak/increment` (gamification)
- `GET /api/badges` (user badges)
- `GET /api/achievements` (achievement log)
- `GET /api/info-contents/:slug` (info by slug, premium-gated)
- `GET /api/legal/consent` (consent version)
- Admin CRUD endpoints (H.1-H.4)

**Changed Endpoints:**
- `GET /api/certificates` now paginated (pagination schema added)
- `POST /api/admin/certificates/issue` — new `completion_date` parameter

**Removed Endpoints:**
- None (v2.0 fully backwards compatible with v1.9 mobile app)

**New Features:**
- KTA auto-generation on purchase (idempotent)
- Certificate QR code public verification
- Gamification (badges, achievements, streaks)
- Info content with premium tier gating
- Legal content versioning
- Admin UI for all features (Livewire components)

**Security Improvements:**
- Signed QR tokens with HMAC verification
- Webhook signature validation (Midtrans)
- Role-based access control (super_admin)
- CSRF protection on web routes
- Rate limiting (60 req/min per IP)

---

## Sign-Off

**Backend Lead (Solkhan):**  
✅ Contract verified, tests passing, ready for production handoff

**Mobile Lead (Daffa):**  
⏳ Review contract, run integration tests, confirm compatibility

**DevOps / Deployment:**  
⏳ Ready to deploy staging → production

---

**Generated:** 2026-09-25 14:00 WIB  
**Valid Until:** Next major version (v3.0) — 60 day deprecation notice required
