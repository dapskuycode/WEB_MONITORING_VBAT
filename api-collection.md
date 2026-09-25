# VBAT API Collection — curl Examples

## Setup
```bash
BASE_URL="http://localhost:8000"
TOKEN=""  # Set after login
```

## 1. Auth & Profile

### Login
```bash
curl -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@vbat.local",
    "password": "password"
  }'
```

**Expected:**
```json
{
  "access_token": "1|abc...",
  "token_type": "Bearer",
  "user": {...}
}
```

### Get Current User
```bash
curl -X GET "$BASE_URL/api/user" \
  -H "Authorization: Bearer $TOKEN"
```

### Update Profile
```bash
curl -X PUT "$BASE_URL/api/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "phone": "+6281234567890",
    "address": "Jl. Test No. 123"
  }'
```

### Logout
```bash
curl -X POST "$BASE_URL/api/auth/logout" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 2. Membership & KTA

### Get Current Membership
```bash
curl -X GET "$BASE_URL/api/membership" \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
```json
{
  "id": 1,
  "user_id": 1,
  "kta_number": "KTA-000001-2026",
  "tier": "gold",
  "status": "active",
  "valid_from": "2026-09-25",
  "valid_until": "2027-09-25"
}
```

### Lookup KTA (Public)
```bash
curl -X GET "$BASE_URL/api/membership/kta/KTA-000001-2026"
```

---

## 3. Certificate

### List My Certificates
```bash
curl -X GET "$BASE_URL/api/certificates" \
  -H "Authorization: Bearer $TOKEN"
```

### Get Certificate Detail
```bash
curl -X GET "$BASE_URL/api/certificates/1" \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
```json
{
  "id": 1,
  "user_id": 1,
  "certificate_number": "CERT-000001-26",
  "course_name": "Advanced Training",
  "issued_at": "2026-09-25T10:00:00Z",
  "qr_token": "abc123...",
  "qr_url": "http://localhost:8000/api/certificates/verify/abc123...",
  "status": "active"
}
```

### Verify Certificate (Public)
```bash
curl -X GET "$BASE_URL/api/certificates/verify/abc123..."
```

**Expected:**
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

---

## 4. Gamification

### Get My Badges
```bash
curl -X GET "$BASE_URL/api/badges" \
  -H "Authorization: Bearer $TOKEN"
```

### Get Achievements
```bash
curl -X GET "$BASE_URL/api/achievements" \
  -H "Authorization: Bearer $TOKEN"
```

### Increment Streak (Idempotent)
```bash
curl -X POST "$BASE_URL/api/streak/increment" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "activity_type": "daily_login"
  }'
```

**Expected:**
```json
{
  "current_streak": 5,
  "longest_streak": 10,
  "last_activity_date": "2026-09-25"
}
```

---

## 5. Info & Legal

### List Info Contents
```bash
curl -X GET "$BASE_URL/api/info-contents"
```

### Get by Slug
```bash
curl -X GET "$BASE_URL/api/info-contents/whatsapp-contact"
```

### Latest Terms
```bash
curl -X GET "$BASE_URL/api/legal/terms"
```

### Latest Privacy
```bash
curl -X GET "$BASE_URL/api/legal/privacy"
```

### About (Quantum Tele)
```bash
curl -X GET "$BASE_URL/api/legal/about"
```

### Consent Version
```bash
curl -X GET "$BASE_URL/api/legal/consent"
```

---

## 6. Admin Endpoints (super_admin only)

### List All Memberships
```bash
curl -X GET "$BASE_URL/api/admin/memberships" \
  -H "Authorization: Bearer $TOKEN"
```

### Issue KTA Manually
```bash
curl -X POST "$BASE_URL/api/admin/memberships/issue-kta" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "tier_code": "silver",
    "valid_months": 12
  }'
```

### List All Certificates
```bash
curl -X GET "$BASE_URL/api/admin/certificates" \
  -H "Authorization: Bearer $TOKEN"
```

### Issue Certificate
```bash
curl -X POST "$BASE_URL/api/admin/certificates/issue" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 3,
    "course_id": 1,
    "course_name": "Basic Training",
    "completion_date": "2026-09-20"
  }'
```

### Revoke Certificate
```bash
curl -X DELETE "$BASE_URL/api/admin/certificates/5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Certificate reissue requested"
  }'
```

### List Badges
```bash
curl -X GET "$BASE_URL/api/admin/badges" \
  -H "Authorization: Bearer $TOKEN"
```

### Create Badge
```bash
curl -X POST "$BASE_URL/api/admin/badges" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Veteran Member",
    "description": "1 year active membership",
    "icon_url": "https://cdn.vbat.id/badges/veteran.png",
    "criteria": "{\"years\": 1}"
  }'
```

### Update Streak Config
```bash
curl -X PUT "$BASE_URL/api/admin/streak-config" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "grace_hours": 6,
    "timezone": "Asia/Jakarta"
  }'
```

### Create Info Content
```bash
curl -X POST "$BASE_URL/api/admin/info-contents" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "slug": "help-center",
    "title": "Pusat Bantuan",
    "content": "Contact us at...",
    "type": "support",
    "is_active": true
  }'
```

### Create Legal Version
```bash
curl -X POST "$BASE_URL/api/admin/legal-contents" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "terms",
    "version": "2.0",
    "title": "Terms of Service v2.0",
    "content": "Updated terms...",
    "effective_date": "2026-10-01",
    "is_current": false
  }'
```

---

## 7. Error Cases

### 401 Unauthorized
```bash
curl -X GET "$BASE_URL/api/user"
# No token → 401
```

### 403 Forbidden
```bash
# Regular user tries admin endpoint
curl -X GET "$BASE_URL/api/admin/memberships" \
  -H "Authorization: Bearer $USER_TOKEN"
```

### 404 Not Found
```bash
curl -X GET "$BASE_URL/api/certificates/999999" \
  -H "Authorization: Bearer $TOKEN"
```

### 422 Validation Error
```bash
curl -X PUT "$BASE_URL/api/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "invalid"
  }'
```

---

## 8. Health Check

```bash
curl -X GET "$BASE_URL/api/health"
```

**Expected:**
```json
{
  "status": "healthy",
  "timestamp": "2026-09-25T13:58:13Z",
  "database": true,
  "cache": true,
  "queue": true
}
```

---

## Quick Test Script
```bash
#!/bin/bash
BASE_URL="http://localhost:8000"

# Login
RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@vbat.local","password":"password"}')

TOKEN=$(echo $RESPONSE | jq -r '.access_token')

# Test profile
curl -X GET "$BASE_URL/api/user" \
  -H "Authorization: Bearer $TOKEN"

# Test membership
curl -X GET "$BASE_URL/api/membership" \
  -H "Authorization: Bearer $TOKEN"

# Test certificates
curl -X GET "$BASE_URL/api/certificates" \
  -H "Authorization: Bearer $TOKEN"

echo "✅ API test complete"
```
