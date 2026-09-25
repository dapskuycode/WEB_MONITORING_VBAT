# Admin Web Staging Seed Accounts & Data

## Test User Accounts

### Super Admin
**Email:** admin@vbat.local  
**Password:** password  
**Role:** Super Admin  
**Permissions:** Full access to all admin pages

### Sponsor Admin
**Email:** sponsor@vbat.local  
**Password:** password  
**Role:** Sponsor  
**Sponsor:** PT Mitra Sponsor (Kontribusi tier)  
**Permissions:** Limited to sponsor dashboard

### Regular User
**Email:** user@vbat.local  
**Password:** password  
**Role:** User  
**Entitlement:** Android Basic (free)  
**Status:** Active

---

## Seeded Data

### Sponsors (5 total)
1. **PT Mitra Sponsor** - Kontribusi tier
2. **Tokopedia** - Bronze tier
3. **Shopee** - Silver tier
4. **OVO** - Gold tier
5. **Dana** - Platinum tier

### Courses (3 total)
1. **Android Basic**
   - 3 lessons
   - 9 materials (video + PDF mix)
   - 2 quizzes

2. **iPhone Intermediate**
   - 3 lessons
   - 9 materials (video + PDF mix)
   - 2 quizzes

3. **Free Class: Hardware Solutions**
   - 2 lessons
   - 6 materials (video only)
   - 1 quiz

### Rule Configs (7 total)
- `completion_threshold`: 70
- `attempt_limit`: 5
- `hardware_threshold`: 90
- `package_android_price`: 800000
- `package_iphone_price`: 2000000
- `package_bundling_price`: 2500000
- `alumni_cutoff_date`: 2027-01-01 00:00:00

### Default Entitlements
- admin@vbat.local: All courses (Super Admin)
- user@vbat.local: Android Basic (free tier)

---

## API Test Data

### Quiz Example (Seeded)
```json
{
  "id": 1,
  "title": "Android Basics Quiz",
  "course_id": 1,
  "passing_score": 70,
  "attempts_allowed": 5,
  "created_at": "2026-09-25T11:00:00Z"
}
```

### Material Example (Seeded)
```json
{
  "id": 1,
  "lesson_id": 1,
  "title": "Introduction to Android",
  "material_type": "video",
  "youtube_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
  "status": "published",
  "is_required": true,
  "sort_order": 1
}
```

---

## How to Seed Staging

### Option 1: Fresh Database
```bash
php artisan migrate:fresh --seed
```

### Option 2: Refresh Existing
```bash
php artisan migrate:refresh --seed
```

### Option 3: Manual Seed Only
```bash
php artisan db:seed
```

---

## Verifying Seeds

### Check users
```bash
php artisan tinker
> User::all();
```

### Check sponsors
```bash
> Sponsor::all();
```

### Check courses
```bash
> Course::all();
```

### Check rule configs
```bash
> RuleConfig::all();
```

---

## Note

All seeded passwords are **password** (plain text in seeder for staging only).  
For production, use strong credentials and .env variables.
