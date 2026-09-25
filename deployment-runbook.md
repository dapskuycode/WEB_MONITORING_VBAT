# VBAT-WEBSITE Deployment Runbook

## Overview
Production deployment procedure untuk VBAT-WEBSITE backend API.

**Target Server:** Ubuntu 22.04, PHP 8.2, MySQL 8.0, Nginx, Redis
**Process Manager:** Supervisor (queue workers)
**Deployment Method:** Git pull + artisan commands

---

## Pre-Deployment Checklist

- [ ] All tests pass (`php artisan test`)
- [ ] Migrations reviewed and reversible
- [ ] `.env.production` configured
- [ ] Database backup completed
- [ ] Rollback plan ready
- [ ] Maintenance window scheduled (if needed)
- [ ] Stakeholders notified (Daffa for mobile compatibility)

---

## Staging Deployment (staging-vbat.quantumtele.io)

### 1. SSH into Staging Server
```bash
ssh bmkg@152.118.31.54
cd ~/VBAT/vbat-website-staging
```

### 2. Pull Latest Code
```bash
git fetch origin
git checkout dev/solkhan-room
git pull origin dev/solkhan-room
```

### 3. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 4. Environment Setup
```bash
cp .env.staging .env
php artisan key:generate  # Only first time
```

### 5. Run Migrations
```bash
php artisan down --message="Updating database" --retry=60

php artisan migrate --force

php artisan up
```

### 6. Clear Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 7. Restart Queue Workers
```bash
php artisan queue:restart
# Supervisor auto-restarts workers
```

### 8. Verify Deployment
```bash
curl https://staging-vbat.quantumtele.io/api/health

# Expected: {"status":"healthy","database":true,"cache":true}
```

### 9. Smoke Tests
```bash
# Login test
curl -X POST https://staging-vbat.quantumtele.io/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@vbat.local","password":"password"}'

# Profile test
curl https://staging-vbat.quantumtele.io/api/user \
  -H "Authorization: Bearer {token}"
```

---

## Production Deployment (vbat.quantumtele.io)

### 1. Backup Database
```bash
ssh bmkg@152.118.31.54

# Create timestamped backup
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u vbat_user -p vbat_production > ~/backups/vbat_$DATE.sql

# Compress
gzip ~/backups/vbat_$DATE.sql

# Verify
ls -lh ~/backups/vbat_$DATE.sql.gz
```

### 2. Enable Maintenance Mode
```bash
cd ~/VBAT/vbat-website-production
php artisan down --message="System maintenance in progress" --retry=60
```

### 3. Pull Production Code
```bash
git fetch origin
git checkout main  # Production branch
git pull origin main
```

### 4. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### 5. Run Migrations
```bash
php artisan migrate --force

# Check status
php artisan migrate:status
```

### 6. Seed Critical Data (if needed)
```bash
php artisan db:seed --class=GamificationSeeder --force
```

### 7. Clear & Rebuild Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Restart Services
```bash
# Queue workers
php artisan queue:restart

# Supervisor (if needed)
sudo supervisorctl restart vbat-worker:*

# PHP-FPM (if needed)
sudo systemctl reload php8.2-fpm

# Nginx (if needed)
sudo nginx -t && sudo systemctl reload nginx
```

### 9. Disable Maintenance Mode
```bash
php artisan up
```

### 10. Verify Production
```bash
# Health check
curl https://vbat.quantumtele.io/api/health

# API version
curl https://vbat.quantumtele.io/api/version

# Login smoke test
curl -X POST https://vbat.quantumtele.io/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@vbat.local","password":"password"}'
```

### 11. Monitor Logs
```bash
# Laravel logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Nginx access
sudo tail -f /var/log/nginx/vbat_access.log

# Nginx error
sudo tail -f /var/log/nginx/vbat_error.log
```

---

## Rollback Procedure

### Option A: Git Rollback (Code Only)
```bash
cd ~/VBAT/vbat-website-production

# Find last stable commit
git log --oneline -10

# Rollback
git checkout <commit-hash>

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart
php artisan queue:restart
```

### Option B: Database Rollback
```bash
# Rollback last migration batch
php artisan down
php artisan migrate:rollback --step=1
php artisan up

# Rollback specific migration
php artisan migrate:rollback --path=database/migrations/2026_09_25_xxx.php
```

### Option C: Full Restore from Backup
```bash
# Stop services
php artisan down
php artisan queue:restart

# Restore database
gunzip ~/backups/vbat_20260925_140000.sql.gz
mysql -u vbat_user -p vbat_production < ~/backups/vbat_20260925_140000.sql

# Restart
php artisan up
```

---

## Monitoring & Health Checks

### Automated Health Checks (Cron)
```bash
# Add to crontab
*/5 * * * * curl -f https://vbat.quantumtele.io/api/health || echo "Health check failed" | mail -s "VBAT Health Alert" ops@quantumtele.io
```

### Key Metrics to Monitor
- **Response time**: `/api/health` should respond < 200ms
- **Database connections**: Monitor active connections
- **Queue depth**: `php artisan queue:work --once` should process < 10s
- **Storage**: Check disk usage `df -h`
- **Logs**: Watch for errors in `storage/logs/`

### Laravel Horizon (Optional)
```bash
# Install
composer require laravel/horizon

# Publish
php artisan horizon:install

# Run
php artisan horizon
```

---

## Common Issues & Fixes

### Issue: 500 Internal Server Error
**Fix:**
```bash
# Check logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
```

### Issue: Queue Not Processing
**Fix:**
```bash
# Check supervisor status
sudo supervisorctl status vbat-worker:*

# Restart workers
php artisan queue:restart
sudo supervisorctl restart vbat-worker:*

# Check failed jobs
php artisan queue:failed
php artisan queue:retry all
```

### Issue: Migration Fails
**Fix:**
```bash
# Check migration status
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback --step=1
php artisan migrate --force
```

### Issue: CORS Errors (Mobile App)
**Fix:**
```bash
# config/cors.php
'allowed_origins' => ['https://vbat-mobile.app', 'capacitor://localhost'],

# Clear config
php artisan config:cache
```

---

## Security Checklist

- [ ] `.env` file permissions: `chmod 600 .env`
- [ ] `.env` NOT in git: check `.gitignore`
- [ ] APP_DEBUG=false in production
- [ ] HTTPS enforced (check Nginx config)
- [ ] Database password rotated quarterly
- [ ] Sanctum tokens expire after 24h
- [ ] Rate limiting enabled (60 req/min)
- [ ] CSRF protection enabled for web routes
- [ ] Signed URLs for media with expiry
- [ ] Midtrans webhook signature validation active

---

## Environment Variables (Production)

Critical `.env` values (redacted):

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://vbat.quantumtele.io

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vbat_production
DB_USERNAME=vbat_user
DB_PASSWORD=***

SANCTUM_STATEFUL_DOMAINS=vbat.quantumtele.io
SESSION_DOMAIN=.quantumtele.io

MIDTRANS_SERVER_KEY=***
MIDTRANS_CLIENT_KEY=***
MIDTRANS_IS_PRODUCTION=true

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=***
REDIS_PORT=6379

WHATSAPP_API_URL=***
WHATSAPP_PHONE_NUMBER=+628***
```

---

## Post-Deployment

1. **Notify Mobile Team (Daffa):** "Backend v2.1 deployed to production. Health check: OK."
2. **Update Documentation:** API changelog, breaking changes
3. **Monitor for 1 hour:** Watch logs, error rates, response times
4. **Backup new state:** Create post-deployment backup

---

## Emergency Contacts

- **Backend Lead (Solkhan)**: +62 8xx-xxxx-xxxx
- **Mobile Lead (Daffa)**: +62 8xx-xxxx-xxxx
- **DevOps**: ops@quantumtele.io
- **On-Call**: +62 8xx-xxxx-xxxx (24/7)

---

## Deployment Log Template

```markdown
# Deployment: 2026-09-25 14:00 WIB

**Deployer:** Solkhan
**Branch:** main
**Commit:** abc123def
**Environment:** Production

## Changes
- [ADMIN-WEB-10] Membership manager
- [ADMIN-WEB-11] Gamification manager
- [ADMIN-WEB-12] Info & legal content manager
- [ADMIN-WEB-13] Profile field manager

## Migrations
- 2026_09_25_120305_create_info_contents_table
- 2026_09_25_120306_create_legal_contents_table

## Rollback Plan
- Git commit: xyz789abc (last stable)
- Database backup: ~/backups/vbat_20260925_133000.sql.gz

## Result
✅ Deployment successful
✅ Health check passed
✅ Smoke tests passed
✅ Mobile team notified

## Issues
None

## Next Steps
- Monitor logs for 1 hour
- Schedule Phase J (Contract Freeze)
```
