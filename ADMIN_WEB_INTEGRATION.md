# Admin Web Integration & Staging QA

## Route Map

| Route | Component | Role Required | Status |
|-------|-----------|---------------|--------|
| `/admin/dashboard` | AdminDashboard | Super Admin | ✅ |
| `/admin/sponsors` | SponsorManager | Super Admin | ✅ ADMIN-WEB-03 |
| `/admin/feed` | FeedManager | Super Admin | ✅ ADMIN-WEB-04 |
| `/admin/content` | ContentCms | Super Admin | ✅ ADMIN-WEB-05 |
| `/admin/bulk-upload` | BulkUpload | Super Admin | ✅ ADMIN-WEB-06 |
| `/admin/quizzes` | QuizManager | Super Admin | ✅ ADMIN-WEB-07 |
| `/admin/rules` | RuleConfigManager | Super Admin | ✅ ADMIN-WEB-07 |
| `/admin/analytics` | AnalyticsDashboard | Super Admin | ✅ ADMIN-WEB-08 |

## Database Setup

### Migrations
```bash
php artisan migrate
```

Total migrations: 37 (as of Phase F.9)

### Seeding
```bash
php artisan db:seed
```

Seeds included:
- Admin user (email: admin@vbat.local)
- 5 sponsors with tiers
- 3 courses (Android, iPhone, Free Class)
- Sample content, lessons, materials
- Default rule configs (70% threshold, 5 attempt limit, 90% hardware, prices)

## Test Accounts

| Email | Password | Role | Notes |
|-------|----------|------|-------|
| admin@vbat.local | password | Super Admin | Full access to all admin pages |
| sponsor@vbat.local | password | Sponsor | Limited to sponsor tools |
| user@vbat.local | password | User | Mobile app test account |

## Staging URLs

- Admin Dashboard: `https://staging.vbat.local/admin/dashboard`
- API Base: `https://api.staging.vbat.local/v1`
- Socket: `ws://staging.vbat.local:6001`

## QA Checklist - Happy Path

### Authentication & Authorization
- [ ] Login with admin@vbat.local succeeds
- [ ] Super Admin can access all admin routes
- [ ] Sponsor role blocked from admin routes
- [ ] Logout clears session

### Admin Web Pages
- [ ] Dashboard loads and shows summaries
- [ ] Sponsor Manager CRUD works
- [ ] Feed Manager CRUD works
- [ ] Content CMS CRUD works
- [ ] Bulk Upload: template download, upload, preview, commit
- [ ] Quiz Manager CRUD works
- [ ] Rule Config Manager CRUD & defaults seeded
- [ ] Analytics Dashboard shows data

### Data Integrity
- [ ] Sponsor create updates DB
- [ ] Feed create updates DB
- [ ] Content create updates DB
- [ ] Quiz create updates DB
- [ ] Bulk import commits transaction

### Error Cases
- [ ] Invalid file upload rejected
- [ ] Duplicate rule config key rejected
- [ ] Missing required field shows error
- [ ] 404 on missing resource
- [ ] 403 on unauthorized access

### Responsive Design
- [ ] Pages render on desktop (1920px)
- [ ] Pages render on tablet (768px)
- [ ] Pages render on mobile (375px)
- [ ] Tables scroll on mobile

### Browser Support
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)

### Performance
- [ ] Page load < 3s
- [ ] No console errors
- [ ] No memory leaks on repeated navigation

## Known Limitations

1. **Analytics & Audit**: Full event tracking requires TASK-ANA-BE-01 (backend analytics service). Current dashboard shows static aggregates.
2. **Notifications**: Notification inbox pending TASK-M1-BE-05 (event tracking).
3. **Bulk Import Dry-run**: Preview implemented, dry-run transaction rollback pending optimization.
4. **Export/Reports**: Export functionality pending policy definition and TASK-ANA-BE-01.

## Runbook

### Deploy to Staging
```bash
cd /path/to/vbat-website
git pull origin dev/solkhan-room
php artisan migrate
php artisan db:seed
php artisan cache:clear
```

### Rollback
```bash
php artisan migrate:rollback
git checkout <previous-commit>
php artisan migrate
```

### Troubleshooting

#### Route not found
- Run: `php artisan route:list | grep admin`
- Check routes/web.php for admin routes

#### Livewire component not found
- Run: `php artisan cache:clear`
- Verify component exists in app/Livewire/

#### Database errors
- Run: `php artisan migrate:refresh --seed`
- Check .env database credentials

## Evidence & Artifacts

- [x] Route map (this document)
- [x] Seed accounts defined
- [x] QA checklist
- [x] 215 tests passing
- [x] 37 migrations running
- [ ] Staging deployment tested (manual - requires infrastructure access)
- [ ] Browser screenshots (manual - requires QA on staging)

## Handoff Status

**Ready for Daffa (Mobile):**
- Admin API endpoints stable
- Rule configs accessible via API
- Quiz/entitlement rules frozen for integration

**Blocked:**
- Full analytics integration (awaits TASK-ANA-BE-01)
- Notification service (awaits TASK-M1-BE-05)
- Payment status on analytics (awaits TASK-PAY-BE-02)

**Next Steps:**
1. Deploy to staging environment
2. Run QA checklist (happy path + error cases)
3. Document any issues found
4. Request sign-off from project leads
5. Prepare for UAT with real sponsors

---
**Created:** 2026-09-25  
**Phase:** F (Admin Web Features)  
**Status:** READY FOR STAGING QA
