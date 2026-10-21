# Functional Test Checklist - VBAT Ponsel Web Portal
**Audit Type:** Business Logic, API, Database, State Management  
**Priority:** Ignore UI aesthetics, focus functional correctness  
**Created:** 22 Oktober 2026  

## I. AUTHENTICATION & ROLE LOGIC

### 1.1 Role System Enforcement
- [ ] **CRITICAL:** "Owner" role must be completely eradicated
- [ ] Only valid roles: "Super Admin", "Sponsor", "Teknisi/Siswa"
- [ ] Role assignments in database (users table) must be valid
- [ ] Middleware must reject requests with invalid roles
- [ ] API endpoints must filter by valid roles only

### 1.2 Authentication Flow
- [ ] Login with valid credentials (Super Admin, Sponsor, Teknisi/Siswa)
- [ ] Login with invalid credentials → 401/Unauthorized
- [ ] Remember me functionality (cookie persistence)
- [ ] Password reset flow
- [ ] Session timeout (idle/logout)

### 1.3 Authorization Middleware
- [ ] Sponsor CANNOT access Admin-only routes
- [ ] Admin CANNOT access Sponsor-specific routes (except moderation)
- [ ] Teknisi/Siswa has limited access only
- [ ] 403 Forbidden responses for role violations

## II. SPONSOR MODULES

### 2.1 Sponsor Dashboard
- [ ] **CRITICAL:** Sponsors MUST NOT see Views, Clicks, CTR analytics
- [ ] Quota cards display correct tier-based limits
- [ ] Quota usage updates in real-time
- [ ] Dashboard loads only sponsor-owned data
- [ ] No access to admin analytics endpoints

### 2.2 Katalog Produk (Product Catalog)
- [ ] CRUD operations: Create, Read, Update, Delete products
- [ ] File upload validation: Max 2MB, valid extensions (.jpg, .png, .webp)
- [ ] Price/discount calculations work correctly
- [ ] Quota enforcement: Cannot exceed product limit per tier
- [ ] Soft delete/archive functionality
- [ ] Bulk operations validation

### 2.3 Pengajuan Kampanye (Campaign Submissions)
#### 2.3.1 Hero Slider
- [ ] Form submission with valid media (16:9 ratio)
- [ ] Media size validation: Max 5MB
- [ ] URL input validation (https:// required)
- [ ] Default status: "Pending Approval"
- [ ] Cannot edit after submission (read-only until approval)

#### 2.3.2 Horizontal Banner
- [ ] Form submission with valid media (3:1 ratio)
- [ ] Media size validation: Max 3MB
- [ ] Default status: "Pending Approval"
- [ ] Quota deduction logic triggers correctly

#### 2.3.3 Card Banner
- [ ] Form submission with valid media (4:5 ratio)
- [ ] Media size validation: Max 2MB
- [ ] Default status: "Pending Approval"
- [ ] Quota check before submission

#### 2.3.4 Pop-Up Banner
- [ ] Form submission with valid media (1:1 ratio)
- [ ] Media size validation: Max 1.5MB
- [ ] Default status: "Pending Approval"
- [ ] Display duration settings validation

#### 2.3.5 Campaign History Table
- [ ] Shows only sponsor's campaigns
- [ ] Columns: Media, Status, Submission Date, Actions
- [ ] **NO analytics columns** (Views, Clicks, CTR)
- [ ] Filter by status (Pending, Approved, Rejected)
- [ ] Pagination works correctly

### 2.4 Pengajuan Push Notification
- [ ] Form validation: Title, Message, Target Audience required
- [ ] Schedule datetime validation (future dates only)
- [ ] Default status: "Pending Approval"
- [ ] FCM payload structure validation
- [ ] Cannot edit scheduled notifications
- [ ] History table shows correct status

### 2.5 Quota Management System
- [ ] Tier-based quota calculation works
- [ ] Quota deduction upon campaign approval
- [ ] Quota restoration upon campaign rejection
- [ ] Real-time quota updates
- [ ] Cannot exceed quota limits
- [ ] Quota rollover logic (if applicable)

## III. ADMIN MODULES

### 3.1 Admin Analytics Dashboard
- [ ] KPI cards load correct aggregated data
- [ ] Date filters work (Today, Week, Month, Custom Range)
- [ ] Export to Excel function works
- [ ] Charts render with correct data
- [ ] Performance: Loads within 3 seconds

### 3.2 Analytics Sponsor
- [ ] Sponsor list loads with correct tier badges
- [ ] "Lihat Detail" button opens sponsor analytics
- [ ] Sponsor analytics show Views, Clicks, CTR
- [ ] Data filtering by date range works
- [ ] Export functionality for sponsor analytics

### 3.3 Manajemen Sponsor
- [ ] CRUD operations: Add, Edit, Delete Sponsor
- [ ] Inline tabs navigation works
- [ ] Search & Filter by name, tier, status
- [ ] Status changes (Active, Suspended, Banned)
- [ ] Quota progress bars display correctly
- [ ] Tier assignment logic works
- [ ] Modal forms validate all inputs

### 3.4 Produk Part (Admin View)
- [ ] Clean table view with sorting
- [ ] Prominent price/discount display
- [ ] Edit/Delete functions work
- [ ] Bulk operations (activate/deactivate)
- [ ] Search & filter by category, price range

### 3.5 Moderasi Kampanye
#### 3.5.1 Placement & Status Tabs
- [ ] Tab navigation works correctly
- [ ] Slot indicators show correct availability
- [ ] Campaign list loads with pending approvals first

#### 3.5.2 Approve/Reject Flow
- [ ] Approve modal with optional reason input
- [ ] Reject modal with mandatory reason input
- [ ] Status updates immediately reflect
- [ ] Sponsor notified of decision
- [ ] Quota deducted upon approval
- [ ] Campaign becomes active immediately

#### 3.5.3 Slot Validation
- [ ] Cannot approve if slot quota exceeded
- [ ] Priority campaigns get slot preference
- [ ] Concurrent campaign limits enforced

### 3.6 Event & Diskon Shop
- [ ] Create Event modal validates all fields
- [ ] Date period validation (start < end)
- [ ] Assign products to event works
- [ ] Discount calculations (percentage vs fixed)
- [ ] Edit/Delete event functionality
- [ ] Event status (Upcoming, Active, Ended)

### 3.7 Bulk Upload Kelas
- [ ] Drag-and-drop zone validation
- [ ] File format validation (.CSV, .XLSX)
- [ ] Download template works
- [ ] Upload process with progress indicator
- [ ] Data validation before saving to DB
- [ ] Error handling for malformed rows
- [ ] Table history renders uploaded data

### 3.8 Push Notification (FCM)
#### 3.8.1 Approval Queue
- [ ] Tab navigation (Menunggu Approval vs Riwayat)
- [ ] List shows pending notifications
- [ ] Approve/Tolak buttons work
- [ ] Approval modal with scheduling options
- [ ] Status updates immediately

#### 3.8.2 Send New Notification
- [ ] Form validation: Title, Message, Target required
- [ ] Schedule options (Immediate, Scheduled)
- [ ] FCM payload structure correct
- [ ] Notification sent successfully
- [ ] Delivery status tracked

#### 3.8.3 FCM Integration
- [ ] FCM API endpoint configured
- [ ] Authentication token valid
- [ ] Payload structure matches FCM spec
- [ ] Error handling for failed sends
- [ ] Retry logic for failed notifications

### 3.9 Daftar Akun User
- [ ] Search functionality works
- [ ] Filter by Role/Status
- [ ] **CRITICAL:** "Owner" role not in filter options
- [ ] View details shows correct user info
- [ ] Edit user functionality (role changes)
- [ ] Deactivate/Activate user

### 3.10 Settings
- [ ] Profile update (Name, Email) works
- [ ] Save button uses VBAT Solid Blue (#1a73e8)
- [ ] Delete Account modal (Red Outline)
- [ ] Password change validation
- [ ] Email verification flow

## IV. DATABASE CONSISTENCY

### 4.1 Data Integrity
- [ ] Foreign key constraints work
- [ ] Cascade delete configured correctly
- [ ] Unique constraints enforced
- [ ] Data validation at DB level

### 4.2 Sponsor Tier Matrix
- [ ] 7 tiers exist: Free, Kontribusi, Bronze, Silver, Gold, Platinum, Diamond
- [ ] 14 benefit categories correctly mapped
- [ ] Free tier has all benefits disabled
- [ ] Tier upgrades preserve existing data

### 4.3 Campaign Status Flow
- [ ] Default: Pending Approval
- [ ] Admin: Approve → Active
- [ ] Admin: Reject → Rejected
- [ ] Sponsor: Cannot change status
- [ ] Archived campaigns preserved

### 4.4 Quota Tracking
- [ ] Quota usage increments correctly
- [ ] Quota restored on rejection
- [ ] Quota limits enforced at DB level
- [ ] Historical quota tracking

## V. API FUNCTIONALITY

### 5.1 REST API Endpoints
- [ ] All endpoints return proper HTTP codes
- [ ] JSON response structure consistent
- [ ] Error handling with meaningful messages
- [ ] Rate limiting applied
- [ ] CORS configured correctly

### 5.2 Authentication APIs
- [ ] Login API returns token
- [ ] Token validation works
- [ ] Refresh token functionality
- [ ] Logout invalidates token

### 5.3 Sponsor APIs
- [ ] **CRITICAL:** No analytics endpoints exposed to sponsors
- [ ] Campaign submission API validates all fields
- [ ] Quota check API returns current usage
- [ ] Product management API works
- [ ] Push notification API validates payload

### 5.4 Admin APIs
- [ ] Analytics data API returns correct aggregates
- [ ] Campaign moderation API updates status
- [ ] Sponsor management API CRUD operations
- [ ] Bulk upload API processes files
- [ ] FCM notification API sends correctly

### 5.5 WebSocket/Real-time
- [ ] Real-time quota updates work
- [ ] Notification badges update
- [ ] Campaign status updates in real-time
- [ ] Connection stability

## VI. INTEGRATIONS

### 6.1 Midtrans Payment Gateway
- [ ] Payment payload structure correct
- [ ] Success/failure callbacks handled
- [ ] Transaction status sync
- [ ] Refund processing

### 6.2 FCM (Firebase Cloud Messaging)
- [ ] Push notification payload correct
- [ ] Target audience filtering
- [ ] Delivery tracking
- [ ] Error handling for failed sends

### 6.3 Excel Export
- [ ] Data formatting correct
- [ ] Large datasets handled
- [ ] File download works
- [ ] Template generation

## VII. SECURITY & VALIDATION

### 7.1 Input Validation
- [ ] File upload size limits enforced
- [ ] File extension validation
- [ ] SQL injection prevention
- [ ] XSS protection
- [ ] CSRF tokens validated

### 7.2 Business Rule Validation
- [ ] Cannot exceed tier quotas
- [ ] Cannot edit approved campaigns
- [ ] Cannot access other sponsor data
- [ ] Cannot bypass moderation

### 7.3 Data Privacy
- [ ] Sponsor data isolated by owner
- [ ] Analytics data only for admin
- [ ] PII protection
- [ ] GDPR compliance basics

## VIII. PERFORMANCE & SCALABILITY

### 8.1 Database Performance
- [ ] Indexes on frequently queried columns
- [ ] Query optimization for large datasets
- [ ] Pagination works for all lists
- [ ] No N+1 query problems

### 8.2 API Performance
- [ ] Response times < 2 seconds
- [ ] Caching implemented where appropriate
- [ ] Bulk operations optimized
- [ ] Concurrent request handling

### 8.3 File Handling
- [ ] Image processing optimized
- [ ] Large file uploads handled
- [ ] Storage cleanup for old files
- [ ] CDN integration (if applicable)

## IX. ERROR HANDLING

### 9.1 Graceful Degradation
- [ ] 404 for invalid routes
- [ ] 403 for unauthorized access
- [ ] 422 for validation errors
- [ ] 500 with generic error message
- [ ] Error logging implemented

### 9.2 User-Friendly Messages
- [ ] Validation errors explain what's wrong
- [ ] Success messages confirm actions
- [ ] Warning messages for potential issues
- [ ] Info messages for system status

## X. TEST COVERAGE REQUIREMENTS

### 10.1 Automated Tests Required
- [ ] Auth tests: Invalid credentials, role validation
- [ ] Validation tests: File upload limits, CSV parsing
- [ ] Workflow tests: Campaign submission → approval → activation
- [ ] Calculation tests: Discounts, quota deductions
- [ ] Integration tests: FCM, Midtrans mocks
- [ ] Security tests: Role bypass attempts

### 10.2 Test Data Requirements
- [ ] Test database with realistic data
- [ ] All 7 sponsor tiers populated
- [ ] Sample campaigns in all statuses
- [ ] Mock FCM/Midtrans responses
- [ ] Edge case test scenarios

---

## EXECUTION SUMMARY

**Total Test Cases:** 150+ functional checks  
**Priority:** Fix CRITICAL items first (Owner role, Sponsor analytics)  
**Testing Method:** Automated + manual verification  
**Exit Criteria:** All CRITICAL items passing, 95%+ overall pass rate  

**Next Steps:**
1. Scan codebase for "Owner" role references
2. Verify sponsor analytics restriction
3. Write missing automated tests
4. Execute test suite
5. Fix functional bugs found