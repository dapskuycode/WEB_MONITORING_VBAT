# 📋 VBAT-WEBSITE Handoff Checklist

> **Project:** VBAT-PONSEL Backend
> **Maintainer:** Solkhan (mohamadsolkhannawawi)
> **Branch:** `dev/solkhan-room`
> **Last Updated:** 24 September 2026

---

## Usage

Copy this template for each handoff to Mobile team (Daffa) or other stakeholders.

---

## Handoff Template

### Metadata

- **Handoff Date:** 2026-09-24
- **From:** Solkhan (Backend)
- **To:** Daffa (Mobile) / QA / Stakeholder
- **Scope:** Phase 1 (Sponsor CRUD API): 6-tier sponsor system, benefit matrix, Sponsor & Product CRUD APIs, Campaign status migration
- **Commit Range:** `010daf5..8c9b15c` (3 commits on `dev/solkhan-room`)
- **API Contract Version:** v1.1

### Changes Summary

| # | File / Endpoint | Change Type | Description |
|---|-----------------|-------------|-------------|
| 1 | `app/Http/Controllers/Api/SponsorApiController.php` | NEW | Sponsor CRUD + tiers listing + logo upload |
| 2 | `app/Http/Controllers/Api/SponsorProductApiController.php` | NEW | Product CRUD + image upload + quota validation |
| 3 | `routes/api.php` | MODIFY | Added `sponsors` & `products` API resources |
| 4 | `app/Models/SponsorTier.php` | NEW | 6-tier model with benefit relations |
| 5 | `app/Models/BenefitCategory.php` | NEW | 11 benefit categories (katalog, best deal, hero, etc.) |
| 6 | `app/Models/TierBenefit.php` | NEW | Pivot: default benefit value per tier |
| 7 | `app/Models/SponsorBenefitOverride.php` | NEW | Per-sponsor benefit overrides |
| 8 | `app/Models/Sponsor.php` | MODIFY | Added `tier_id` FK, `resolveBenefit()`, relations |
| 9 | `app/Models/Campaign.php` | MODIFY | `scopeApproved` → `scopePublished`, lifecycle statuses |
| 10 | `database/seeders/SponsorTierSeeder.php` | NEW | Seeds 6 tiers × 11 benefits = 66 tier_benefits |
| 11 | `tests/Feature/Api/SponsorApiTest.php` | NEW | 10 sponsor CRUD tests |
| 12 | `tests/Feature/Api/SponsorProductApiTest.php` | NEW | 8 product CRUD tests |

### Database Changes

| # | Migration | Table | Change |
|---|-----------|-------|--------|
| 1 | `2026_09_24_150000_create_sponsor_tiers_table` | `sponsor_tiers` | NEW — 6-tier system (kontribusi→diamond) |
| 2 | `2026_09_24_150001_create_benefit_categories_table` | `benefit_categories` | NEW — 11 benefit types |
| 3 | `2026_09_24_150002_create_tier_benefits_table` | `tier_benefits` | NEW — default value per tier×benefit |
| 4 | `2026_09_24_150003_create_sponsor_benefit_overrides_table` | `sponsor_benefit_overrides` | NEW — per-sponsor overrides |
| 5 | `2026_09_24_150004_add_tier_id_to_sponsors_table` | `sponsors` | ADD `tier_id` FK (legacy `tier` string kept in sync) |
| 6 | `2026_09_24_150005_update_campaigns_status_to_lifecycle` | `campaigns` | status: `approved` → `active`/`paused`/`deleted` |

### Environment Variables Added

| Variable | Default | Description |
|----------|---------|-------------|
| `CORS_ALLOWED_ORIGINS` | `*` | Comma-separated allowed origins for CORS |
| `API_VERSION` | `v1` | API version prefix |
| `API_BASE_URL` | `http://localhost:8000/api/v1` | Base URL for mobile clients |
| `MOBILE_MIN_VERSION` | `1.0.0` | Minimum mobile app version |
| `FEATURE_BULK_UPLOAD_XLSX` | `true` | Feature flag for XLSX bulk upload |
| `FEATURE_YOUTUBE_UNLISTED_ONLY` | `true` | Feature flag for YouTube unlisted restriction |

### API Contract Changes

- [ ] New endpoint(s) documented in `API_CONTRACT.md`
- [ ] Modified endpoint(s) documented with version bump
- [ ] Deprecated endpoint(s) marked with removal date
- [ ] Response format unchanged (backward-compatible)
- [ ] Breaking change ↔ mobile team notified

### Tests

- [x] `php artisan test` — all 63 passing (201 assertions, 6748ms)
- [x] Feature tests for new endpoints (10 sponsor + 8 product = 18 new tests)
- [ ] Migration rollback tested (or limitation noted)
- [x] Seeder idempotency verified (SponsorTierSeeder re-runnable)

### Evidence

| # | Evidence | Format | Status |
|---|----------|--------|--------|
| 1 | Sponsor API CRUD endpoints | `route:list --path=api/v1/sponsors` | ✅ 6 routes |
| 2 | Product API CRUD endpoints | `route:list --path=api/v1/products` | ✅ 7 routes |
| 3 | Tier system seeded | `SponsorTier::count() = 6` | ✅ 6 tiers |
| 4 | Benefit matrix seeded | `TierBenefit::count() = 66` | ✅ 66 benefits |
| 5 | `php artisan migrate:status` output | Terminal | ✅ 24/24 Ran |
| 6 | `php artisan test` output | Terminal | ✅ 63/63 Passed |
| 7 | Campaign status migration | `Campaign::whereStatus('active')` | ✅ Lifecycle |

### Known Blockers / Limitations

1. `.gitignore` and `package-lock.json` have incidental changes (prompt docs exclusion, name fix) — not committed yet.
2. Composer install timed out (exit 124) but vendor is intact and all tests pass.
3. No Sanctum/auth integration yet — health endpoint is intentionally public.

### Next Actions

1. Phase 1: Implement Sponsor CRUD API (REQ-SF-01, REQ-SF-02)
2. Phase 2: Implement Learning Material API (REQ-LM-01)
3. Phase 3: Admin Panel Integration
4. Mobile team (Daffa) to verify health endpoint from Flutter app

### Sign-off

- [ ] Backend (Solkhan): reviewed and tested
- [ ] Mobile (Daffa): acknowledged and accepted
- [ ] QA: verified on staging

---

## Completed Handoffs

| # | Date | Scope | Commit Range | Status |
|---|------|-------|--------------|--------|
| 1 | 2026-09-24 | Phase 0 (Foundation) | `5f7810d..4878464` | ✅ Signed off |
| 2 | 2026-09-24 | Phase 1 (Sponsor CRUD) | `010daf5..8c9b15c` | ✅ Signed off |

---

## Quick Reference

- **API Base URL (Staging):** TBD
- **Health Check:** `{APP_URL}/api/v1/health`
- **Storage Proxy:** `{APP_URL}/api/v1/storage/{path}`
- **CORS Config:** `CORS_CONFIG.md`
- **Storage Structure:** `STORAGE_STRUCTURE.md`
- **API Contract:** `API_CONTRACT.md`
- **Development Plan:** `../DEVELOPMENT_PLANNING.md`