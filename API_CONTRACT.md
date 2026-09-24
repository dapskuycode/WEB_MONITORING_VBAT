# 📡 VBAT-WEBSITE API Contract v1.5

> **Project:** VBAT-PONSEL Backend
> **Maintainer:** Solkhan (mohamadsolkhannawawi)
> **Branch:** `dev/solkhan-room`
> **Last Updated:** 24 September 2026
> **Base URL:** `{APP_URL}/api/v1`

---

## 1. Global Conventions

### 1.1 Response Envelope

All API responses use a standard JSON envelope:

```json
{
  "success": true,
  "data": { },
  "meta": { },
  "message": null
}
```

- `success`: boolean
- `data`: payload object/array or `null`
- `meta`: pagination/cursor/insertion metadata or `null`
- `message`: human-readable message or `null`

### 1.2 Error Envelope

```json
{
  "success": false,
  "data": null,
  "errors": { "field": ["message"] },
  "message": "Validation failed"
}
```

HTTP status codes:

| Status | Meaning |
|--------|---------|
| 200    | Success |
| 201    | Created |
| 400    | Bad request / validation failed |
| 401    | Unauthenticated |
| 403    | Forbidden (role/ownership) |
| 404    | Not found |
| 422    | Unprocessable entity |
| 500    | Server error |
| 503    | Service unavailable (health check) |

### 1.3 Authentication

- Public endpoints: no token required
- Auth endpoints: `Authorization: Bearer <token>`
- Token mechanism: Laravel Sanctum (configured in Phase 4)
- Admin endpoints: require `role:super_admin` (via `EnsureUserHasRole` middleware)
- Ownership: server-side from `auth()->id()` — never from client body

### 1.4 Pagination

Cursor-based pagination for feed endpoints:

```json
{
  "meta": {
    "next_cursor": "base64_encoded_last_item_key",
    "has_more": true,
    "per_page": 20
  }
}
```

Request query: `?cursor=<cursor>&per_page=<number>`

---

## 2. Public Endpoints

### 2.1 Health Check

```
GET /api/v1/health
```

Response:

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "service": "vbat-website-api",
    "version": "1.0.0",
    "environment": "local",
    "timestamp": "2026-09-24T00:00:00+07:00",
    "checks": {
      "database": { "status": "ok", "driver": "sqlite" },
      "storage": { "status": "ok", "default_disk": "local", "public_disk_writable": true },
      "cache": { "status": "ok", "driver": "database" }
    }
  },
  "meta": null,
  "message": "Service healthy"
}
```

### 2.2 Banners & Sponsor Promotions

| Method | Endpoint | Description | Status |
|--------|----------|-------------|--------|
| GET    | `/api/v1/banners/hero` | Hero slider campaigns | Existing |
| GET    | `/api/v1/banners/shop-horizontal` | Horizontal banners for shop feed | Existing |
| GET    | `/api/v1/banners/cards` | Card sliders | Existing |
| GET    | `/api/v1/banners/card` | Alias of cards | Existing |
| GET    | `/api/v1/banners/popup` | Popup CTA campaigns | Existing |
| GET    | `/api/v1/sponsors/partners` | Brand partners list | Existing |
| GET    | `/api/v1/shop/products` | All active products | Existing |
| GET    | `/api/v1/shop/best-deals` | Best deal products | Existing |
| GET    | `/api/v1/shop/events/active` | Active global discount event | Existing |

### 2.3 Feed Endpoints (Phase 3 / REQ-SF-01)

| Method | Endpoint | Description | Status |
|--------|----------|-------------|--------|
| GET    | `/api/v1/feed/shop` | Product-only feed with cursor pagination | Planned |
| GET    | `/api/v1/feed/home` | Mixed feed (product + material + sponsor_card) | Planned |

### 2.4 Placement Endpoints (Phase 3 / REQ-SF-03)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET    | `/api/v1/placements/{type}` | Probabilistic campaign selection for placement type (`hero_slider`, `card`, `popup`, `horizontal`) | public |
| GET    | `/api/v1/placements/best-deal` | Best deal products (manual override + weighted selection) | public |
| POST   | `/api/v1/placements/track-click` | Track click on placement (creates campaign log) | public |

### 2.5 Tracking

| Method | Endpoint | Description | Status |
|--------|----------|-------------|--------|
| POST   | `/api/v1/track` | Log impression/click/wishlist event | Existing |
| POST   | `/api/v1/track/outbound` | Log outbound marketplace click | Phase 2 |
| POST   | `/api/v1/events` | Batch analytics event ingestion | Phase 5 |

### 2.6 Storage Proxy

```
GET /api/v1/storage/{path}
```

Serves file from storage with explicit CORS headers.

---

## 3. Auth-Required Endpoints

### 3.1 Demographics

| Method | Endpoint | Description | Status |
|--------|----------|-------------|--------|
| POST   | `/api/v1/user/demographics` | Update user demographics | Existing |
| GET    | `/api/v1/regions/provinces` | List provinces | Existing |
| GET    | `/api/v1/regions/cities/{provinceId}` | Cities by province | Existing |

### 3.2 Notifications (Phase 5 / REQ-ANA-02)

| Method | Endpoint | Description | Status |
|--------|----------|-------------|--------|
| GET    | `/api/v1/notifications` | List own notifications | Planned |
| POST   | `/api/v1/notifications/{id}/read` | Mark notification read | Planned |

### 3.3 Sponsor Portal (Phase 2–4)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET    | `/api/v1/sponsor/products` | List own products | sponsor |
| POST   | `/api/v1/sponsor/products` | Create product | sponsor |
| PUT    | `/api/v1/sponsor/products/{id}` | Edit own product | sponsor |
| DELETE | `/api/v1/sponsor/products/{id}` | Delete own product | sponsor |
| GET    | `/api/v1/sponsor/campaigns` | List own campaigns | sponsor |
| POST   | `/api/v1/sponsor/campaigns` | Create campaign | sponsor |
| PUT    | `/api/v1/sponsor/campaigns/{id}` | Edit own campaign | sponsor |
| DELETE | `/api/v1/sponsor/campaigns/{id}` | Delete own campaign | sponsor |

> Sponsor endpoints are scoped to `auth()->user()->sponsor`.

### 3.4 Learning Materials (Phase 2)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET    | `/api/v1/learning-materials` | List materials (published default) | optional |
| POST   | `/api/v1/learning-materials` | Create material | auth |
| GET    | `/api/v1/learning-materials/{id}` | Show material detail | optional |
| PUT    | `/api/v1/learning-materials/{id}` | Update material | auth |
| DELETE | `/api/v1/learning-materials/{id}` | Delete (soft) material | auth |
| POST   | `/api/v1/learning-materials/{id}/thumbnail` | Upload thumbnail | auth |
| POST   | `/api/v1/learning-materials/validate-youtube` | Validate YouTube URL + unlisted check | auth |
| POST   | `/api/v1/learning-materials/{id}/progress` | Submit view progress | auth |
| GET    | `/api/v1/learning-materials/{id}/analytics` | Get views & completion rate | admin |

**Filters**: `lesson_id`, `material_type`, `status`, `is_required`, `search`, `sort_by`, `sort_direction`.

### 3.5 Placements (Phase 3)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET    | `/api/v1/placements/{type}` | Probabilistic selection for placement slot (`hero_slider`, `card`, `popup`, `ho...[truncated]

---

## 4. Admin Endpoints

All admin endpoints require `Authorization: Bearer <token>` + `role:super_admin`.

### 4.1 Notifications (Phase 4 / REQ-ADM-02)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/api/v1/admin/notifications` | List notifications (filter: `?unread_only=1`, `?type=`, `?per_page=`) |
| GET    | `/api/v1/admin/notifications/unread-count` | Get unread count for badge |
| POST   | `/api/v1/admin/notifications/{id}/read` | Mark single notification as read |
| POST   | `/api/v1/admin/notifications/{id}/read-all` | Mark all unread as read |

Response envelope includes `meta.unread_count`.

### 4.2 Sponsor Override (Phase 4 / REQ-ADM-02)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | `/api/v1/admin/sponsors/{sponsor}/benefit-overrides` | Override sponsor benefit value |
| PUT    | `/api/v1/admin/sponsors/{sponsor}/tier` | Change sponsor tier |

**Benefit override request body:**
```json
{
  "benefit_category_id": 1,
  "value": "custom_value",
  "label": "Custom Label",
  "reason": "Override reason"
}
```

**Tier change request body:**
```json
{
  "tier_id": 2,
  "reason": "Upgrade to gold"
}
```

### 4.3 Campaign & Product Override (Phase 4 / REQ-ADM-02)

| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT    | `/api/v1/admin/campaigns/{campaign}` | Override campaign status (active/paused/deleted) |
| DELETE | `/api/v1/admin/products/{product}` | Admin soft-delete any product |

### 4.4 Best Deal Management (Phase 4 / REQ-ADM-02)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/api/v1/admin/best-deals` | List best deals (with sponsor, products) |
| POST   | `/api/v1/admin/best-deals` | Manually select products for best deal |
| DELETE | `/api/v1/admin/best-deals/{bestDeal}` | Remove from best deal |

### 4.5 Audit Log (Phase 4 / REQ-ADM-02)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/api/v1/admin/audit-logs` | List audit logs (filter: `?action=`, `?target_type=`, `?target_id=`, `?per_page=`) |

Every admin override action is automatically logged to `admin_audit_logs`.

### 4.6 Analytics (Phase 5 / REQ-ANA-01)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/api/v1/admin/analytics/dashboard` | Aggregated dashboard metrics |
| GET    | `/api/v1/admin/analytics/export` | CSV/XLSX export |

> Sponsor role is **blocked** from all analytics endpoints (D-004).

---

## 5. Changelog

| Version | Date | Endpoint / Change | Description |
|---------|------|-------------------|-------------|
| v1.0    | 2026-09-24 | Baseline | Existing endpoints documented |
| v1.1    | 2026-09-24 | `GET /api/v1/health` | Added health check endpoint |
| v1.2    | 2026-09-24 | `/api/v1/sponsors`, `/api/v1/products` | Sponsor & Product CRUD + tier system |
| v1.3    | 2026-09-24 | `/api/v1/learning-materials` | Learning Material CRUD + YouTube validation + progress + analytics |
| v1.4    | 2026-09-24 | `/api/v1/placements/{type}`, `/api/v1/placements/best-deal` | Probabilistic placement selection + best_deal override (REQ-SF-03) |
| v1.5    | 2026-09-24 | `/api/v1/admin/notifications`, `/api/v1/admin/sponsors/{id}/benefit-overrides`, `/api/v1/admin/sponsors/{id}/tier`, `/api/v1/admin/campaigns/{id}`, `/api/v1/admin/products/{id}`, `/api/v1/admin/best-deals`, `/api/v1/admin/audit-logs` | Admin Override & Notification System (REQ-ADM-02) |
| v1.6    | TBD | Analytics & notifications | Event ingestion & user notifications (REQ-ANA-01/02) |

---

## 6. Handoff Notes

- This contract is shared with Mobile team (Daffa). Any API change requires a version bump and changelog update.
- Ownership and role enforcement are server-side; mobile clients must only attach Bearer token.
- See `CORS_CONFIG.md` for cross-origin details.
- See `STORAGE_STRUCTURE.md` for file URL conventions.