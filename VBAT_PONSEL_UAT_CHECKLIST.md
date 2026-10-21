# UAT Checklist - VBAT Ponsel Web Portal

**Dokumen:** User Acceptance Testing Checklist  
**Aplikasi:** VBAT Ponsel Web Portal  
**Peran:** Super Admin & Mitra Sponsor  
**Format:** Markdown  
**Bahasa:** Bahasa Indonesia  
**Tanggal Dibuat:** 21 Oktober 2026  
**Status:** Draft untuk Testing  

---

## I. GLOBAL UI/UX & THEMING (Admin & Sponsor)

### 1.1 Light Mode & Dark Mode Toggle

#### Positive Test
- [ ] Dark mode dapat diaktifkan dari menu profil/settings
- [ ] Light mode dapat diaktifkan dari menu profil/settings
- [ ] Mode preference tersimpan dan persisten setelah logout/login
- [ ] Semua elemen UI berubah tema saat toggle mode

#### Negative Test / Validation
- [ ] Sistem tidak merender dengan glowing effects atau "AI slop" visual
- [ ] Background colors transisi smooth (tidak flickering)
- [ ] Warna tidak berantakan saat mode diubah

#### UI/UX Test
- [ ] Text color pada Light Mode: #212121 (primary), #828282 (secondary) - terbaca jelas
- [ ] Text color pada Dark Mode: kontras cukup terhadap background
- [ ] Background Light Mode: #F4F6F8 (app), #FFFFFF (cards)
- [ ] Background Dark Mode: konsisten dengan admin design (dark slate/zinc)
- [ ] Semua icon dan button visibility tetap optimal di kedua mode
- [ ] No hardcoded black/white - gunakan design token colors

---

### 1.2 Sidebar Navigation (Collapse/Expand & Active States)

#### Positive Test
- [ ] Sidebar dapat diklik untuk di-collapse (minimal width, icon-only)
- [ ] Sidebar dapat di-expand kembali ke full width
- [ ] Menu items menampilkan label saat expanded, hanya icon saat collapsed
- [ ] Active menu item highlight dengan warna VBAT Solid Blue (#1a68f0)
- [ ] Menu item yang sedang aktif sesuai dengan route/page saat ini
- [ ] Sub-menu items dapat di-expand/collapse (e.g., Kampanye submenu di Sponsor)

#### Negative Test / Validation
- [ ] Sidebar collapse tidak menyebabkan content shift atau layout breaking
- [ ] Icon visibility tetap jelas saat collapsed (no overlapping text)
- [ ] Sub-menu tidak terjebak dalam state expanded setelah collapse parent
- [ ] Navigation state konsisten saat refresh halaman

#### UI/UX Test
- [ ] Collapse/expand button prominent dan mudah ditemukan
- [ ] Transition collapse/expand smooth (tidak instant jerky)
- [ ] Sidebar styling konsisten antara Admin dan Sponsor
- [ ] Icons menggunakan FontAwesome 6.6.0 (bukan SVG hardcode)
- [ ] Active state styling jelas (underline, background, atau border)

---

### 1.3 Sidebar Navigation - Role-Based Menu (Admin vs Sponsor)

#### Positive Test - Admin Menu
- [ ] Admin melihat menu: Dashboard, Analitik Aplikasi, Analitik Sponsor, Manajemen Sponsor, Produk Part, Moderasi Kampanye, Event & Diskon Shop, Bulk Upload Kelas, Push Notification (FCM), Daftar Akun User, Settings
- [ ] Semua menu admin accessible dan routing benar

#### Positive Test - Sponsor Menu
- [ ] Sponsor melihat menu: Dashboard Sponsor, Katalog Produk Saya, Pengajuan Kampanye (submenu: Hero Slider, Horizontal Slider, Card Slider, Pop Up Iklan), Pengajuan Push Notification, Notifikasi, Settings
- [ ] Semua menu sponsor accessible dan routing benar

#### Negative Test / Validation
- [ ] Admin tidak bisa mengakses Sponsor-only menu via URL manipulation
- [ ] Sponsor tidak bisa mengakses Admin-only menu via URL manipulation
- [ ] 403 Access Denied ditampilkan jika mencoba access non-authorized route
- [ ] Sidebar menu tidak menampilkan item yang tidak diizinkan user

#### UI/UX Test
- [ ] Sidebar styling konsisten visual antara Admin dan Sponsor
- [ ] Menu items tersusun logis dan organized by feature group
- [ ] Settings accessible dari sidebar ATAU profile dropdown (user prefer profile dropdown saja)

---

### 1.4 Toast Notifications (Success, Error, Warning, Info)

#### Positive Test
- [ ] Success toast muncul saat action berhasil (Simpan, Hapus, Approve, etc.)
- [ ] Error toast muncul saat action gagal (validation error, server error)
- [ ] Warning toast muncul untuk notifikasi penting (e.g., "Data belum disimpan")
- [ ] Info toast muncul untuk informasi general (e.g., "Proses upload dimulai")
- [ ] Toast auto-dismiss setelah 3-5 detik
- [ ] Toast position konsisten (top-right atau bottom-right, bukan random)

#### Negative Test / Validation
- [ ] Toast tidak menumpuk berlebihan (max 3 toast visible)
- [ ] Toast dismiss button bekerja dengan klik
- [ ] Toast message jelas dan informatif (bukan generic "Error" tanpa detail)
- [ ] Toast tidak di-cut-off pada resolusi mobile/tablet

#### UI/UX Test
- [ ] Success toast: warna hijau/green (#22c55e atau similar)
- [ ] Error toast: warna merah/red (#ef4444 atau similar)
- [ ] Warning toast: warna kuning/amber (#f59e0b atau similar)
- [ ] Info toast: warna biru/blue (VBAT Solid Blue #1a68f0)
- [ ] Toast icon jelas (checkmark, x-circle, exclamation, info-circle)
- [ ] Toast text readable pada Light & Dark mode

---

### 1.5 Confirmation Modals & Dialogs

#### Positive Test
- [ ] Delete action menampilkan confirmation modal dengan warning text jelas
- [ ] Modal memiliki "Batalkan" (Gray button) dan "Hapus" (Red button)
- [ ] Klik "Batalkan" menutup modal tanpa action
- [ ] Klik "Hapus" menjalankan delete dan menampilkan success toast
- [ ] Modal menampilkan item name/identifier yang akan dihapus

#### Negative Test / Validation
- [ ] Modal tidak bisa ditutup dengan klik outside (harus button)
- [ ] Tidak ada multiple-click issue untuk action buttons
- [ ] Modal backdrop blocking interaction dengan halaman di belakang

#### UI/UX Test
- [ ] Modal title bold dan jelas (e.g., "Hapus Sponsor?")
- [ ] Modal centered pada viewport
- [ ] Buttons: "Batalkan" Gray (#6B7280), "Hapus" Red (#EF4444)
- [ ] Button text menggunakan Title Case / Sentence case konsisten
- [ ] Modal shadow/border sesuai design admin

---

## II. AUTHENTICATION

### 2.1 Login Page

#### Positive Test - Valid Credentials
- [ ] Admin login dengan email + password valid berhasil
- [ ] Sponsor login dengan email + password valid berhasil
- [ ] Post-login redirect ke dashboard masing-masing (Admin → /dashboard, Sponsor → /sponsor/dashboard)
- [ ] Session token tersimpan dan user tetap logged-in setelah refresh

#### Positive Test - Remember Me
- [ ] "Ingat saya" checkbox dapat di-tick
- [ ] Setelah login dengan "Ingat saya" ticked, email tersimpan di form
- [ ] Next login, email pre-filled otomatis

#### Positive Test - Forgot Password
- [ ] "Lupa sandi?" link membawa ke password reset page
- [ ] User input email → reset link dikirim
- [ ] Reset link valid dan bisa membuka password reset form
- [ ] Password baru dapat diset dan login berhasil dengan password baru

#### Negative Test / Validation
- [ ] Login dengan email invalid (format bukan email) → Error message
- [ ] Login dengan email tidak terdaftar → Error message "Email atau Password salah"
- [ ] Login dengan password salah → Error message "Email atau Password salah"
- [ ] Password field tidak menampilkan teks plaintext (masked dengan *)
- [ ] Empty email/password → Validation message muncul sebelum submit
- [ ] SQL injection attempt pada email field → Rejected atau sanitized
- [ ] Multiple failed login attempts → No account lockout (verify policy)

#### UI/UX Test
- [ ] Login form centered dan responsive pada mobile
- [ ] Logo VBAT ditampilkan di login page
- [ ] Form fields memiliki label jelas dan placeholder helpful
- [ ] "Show Password" toggle button tersedia
- [ ] Submit button: Solid Blue (#1a68f0), bukan black
- [ ] Links (Lupa sandi?, Sign Up) styled konsisten (blue, underline on hover)
- [ ] Error message berwarna merah dan icon exclamation visible
- [ ] Success message / redirect smooth tanpa UI glitch

---

### 2.2 Logout

#### Positive Test
- [ ] Logout button visible di menu profil
- [ ] Klik logout → Session dihapus
- [ ] Post-logout redirect ke login page
- [ ] Back button setelah logout tidak bisa access protected routes

#### Negative Test / Validation
- [ ] Expired session → Redirect ke login page automatically
- [ ] Invalid token → Redirect ke login dengan error message

#### UI/UX Test
- [ ] Logout button clear dengan icon (e.g., sign-out-alt from FontAwesome)

---

## III. MODUL SUPER ADMIN

### 3.1 Dashboard Admin

#### Positive Test
- [ ] Dashboard load dengan all KPI cards visible
- [ ] Chart renders dengan data terbaru
- [ ] Date filter dapat diklik dan date picker muncul
- [ ] Date range selection mengupdate chart + KPI cards data

#### Negative Test / Validation
- [ ] Invalid date range → Error message atau validation
- [ ] No data untuk period tertentu → Chart dan KPI graceful handling (show 0, bukan error)

#### UI/UX Test
- [ ] KPI cards styling: #FFFFFF background, shadow light, border subtle
- [ ] Chart colors: bukan AI-slop gradients, solid VBAT Solid Blue (#1a68f0) dan secondary colors
- [ ] Grid layout responsive pada tablet/mobile
- [ ] Date picker icon dan clear button present dan functional

---

### 3.2 Analitik Aplikasi

#### Positive Test
- [ ] Page load dengan all metrics dan charts visible
- [ ] Date range filter dapat diubah (default: last 30 days)
- [ ] Export to Excel button download file .xlsx dengan data terkini
- [ ] KPI cards menampilkan angka, label, dan trend indicator (↑/↓)

#### Negative Test / Validation
- [ ] Export tanpa data → System handle gracefully (empty file atau message)
- [ ] Date range invalid → Validation atau default applied
- [ ] Very large data export → No timeout atau loading indicator visible

#### UI/UX Test
- [ ] Export button: Solid Blue atau Outline style konsisten
- [ ] Chart title, legend, axes labels jelas dan readable
- [ ] KPI trend indicator (↑/↓) colored (green up, red down)
- [ ] No hardcoded SVG for chart elements

---

### 3.3 Analitik Sponsor

#### Positive Test
- [ ] Sponsor list load dengan all data visible
- [ ] Search box dapat filter sponsor by name
- [ ] "Lihat Detail" button buka detail sponsor modal/page
- [ ] Tier badge menampilkan dengan warna/styling sesuai tier

#### Negative Test / Validation
- [ ] Search dengan keyword tidak ada match → Show "Tidak ada data" message
- [ ] Pagination (jika ada) berfungsi benar saat navigate halaman
- [ ] Sort by column (jika ada) bekerja ascending/descending

#### UI/UX Test
- [ ] Sponsor list table: header bold, rows striped atau hover highlight
- [ ] Tier badge styling: berbeda warna per tier (e.g., Gold, Silver, Bronze)
- [ ] "Lihat Detail" button styled konsisten (Solid Blue or Outline)
- [ ] Search icon visible di search input

---

### 3.4 Manajemen Sponsor

#### Positive Test - View & Search
- [ ] Sponsor list load dengan semua data visible
- [ ] Search box dapat filter sponsor by name/email/tier
- [ ] Filter by status (Active, Inactive, Pending) bekerja
- [ ] Pagination navigate bekerja jika data > 10 items
- [ ] Sort by column (Name, Email, Tier, Status) bekerja

#### Positive Test - Add Sponsor (via Modal)
- [ ] "Tambah Sponsor" button buka Add Sponsor modal
- [ ] Form fields: Name, Email, Phone, Tier, Status, Quota (fields TBD sesuai requirement)
- [ ] All fields dapat diinput dengan valid data
- [ ] Submit button "Simpan" simpan data baru
- [ ] Success toast ditampilkan, modal close, list refresh dengan sponsor baru

#### Positive Test - Edit Sponsor (via Modal)
- [ ] Row sponsor → "Edit" button buka Edit Sponsor modal
- [ ] Modal pre-fill dengan data sponsor saat ini
- [ ] Form fields dapat diubah
- [ ] Submit "Simpan" update data
- [ ] Success toast, modal close, list refresh dengan data updated

#### Positive Test - Delete Sponsor
- [ ] Row sponsor → "Delete" button trigger delete
- [ ] Confirmation modal muncul dengan sponsor name
- [ ] "Hapus" button confirm delete
- [ ] Success toast, list refresh, sponsor removed

#### Positive Test - Quota Progress Bar
- [ ] Quota column menampilkan progress bar visual
- [ ] Progress bar percentage sesuai dengan used/total quota

#### Negative Test / Validation
- [ ] Add form: Submit tanpa name → Validation error "Name required"
- [ ] Add form: Submit dengan email invalid format → Validation error "Email format invalid"
- [ ] Add form: Email sudah terdaftar → Error message
- [ ] Edit form: Change status ke Inactive → Confirm perubahan status
- [ ] Delete confirmation: Klik cancel → Modal close, no delete
- [ ] Edit: Try to override existing email → Validation atau error message

#### UI/UX Test
- [ ] "Tambah Sponsor" button: Solid Blue (#1a68f0)
- [ ] Edit/Delete buttons styled subtle (outline atau small secondary button)
- [ ] Form labels bold, required fields marked dengan *
- [ ] Modal title clear (e.g., "Tambah Sponsor Baru" or "Edit Sponsor")
- [ ] Input fields focused state: border VBAT Blue
- [ ] Error messages displayed inline near field atau top of form
- [ ] Status column: Active = green badge, Inactive = gray badge, Pending = yellow badge
- [ ] Quota progress bar: full-width, colored gradient (e.g., blue), with percentage text

---

### 3.5 Produk Part (Admin)

#### Positive Test - View & Search
- [ ] Product list load dengan all columns visible: SKU, Name, Price, Discount, Stock, Action
- [ ] Search box dapat filter by product name/SKU
- [ ] Price dan discount prominently displayed
- [ ] Sort by column bekerja (Price ascending/descending, etc.)

#### Positive Test - Edit Product
- [ ] Row product → "Edit" button buka edit modal/form
- [ ] Can update: Name, Price, Discount, Stock, Description
- [ ] Submit "Simpan" update product data
- [ ] Success toast, list refresh

#### Positive Test - Delete Product
- [ ] Row product → "Delete" button
- [ ] Confirmation modal with product name
- [ ] "Hapus" confirm → Product deleted, success toast, list refresh

#### Negative Test / Validation
- [ ] Edit: Price input dengan text (bukan angka) → Validation error "Harus angka"
- [ ] Edit: Discount > Price → Validation error atau warning
- [ ] Edit: Stock negative → Validation error
- [ ] Delete: Cannot delete if product linked to active campaign (verify business rule)

#### UI/UX Test
- [ ] Price display: formatted dengan currency symbol (Rp) dan thousands separator
- [ ] Discount displayed as percentage (e.g., "15%") dan highlighted
- [ ] Table responsive pada tablet (collapse columns atau horizontal scroll)
- [ ] Edit/Delete buttons subtle styling

---

### 3.6 Moderasi Kampanye (Campaign Approval)

#### Positive Test - View Campaigns
- [ ] Campaign list load dengan tab navigation: "Penempatan" dan "Status"
- [ ] "Penempatan" tab menampilkan campaigns grouped by placement type (Hero, Horizontal, Card, Pop-Up)
- [ ] "Status" tab menampilkan campaigns grouped by status (Menunggu Approval, Disetujui, Ditolak)
- [ ] Each campaign card/row shows: Sponsor name, placement type, slot count, date submitted, action buttons

#### Positive Test - Approve Campaign
- [ ] Campaign row → "Setujui" button buka approval modal
- [ ] Modal shows campaign details
- [ ] Confirm "Setujui" → Campaign status = "Disetujui", success toast
- [ ] List refresh, campaign moved to "Disetujui" tab/section

#### Positive Test - Reject Campaign
- [ ] Campaign row → "Tolak" button buka rejection modal
- [ ] Modal has text input for rejection reason (mandatory)
- [ ] "Tolak" button submit reason → Campaign status = "Ditolak"
- [ ] Success toast, list refresh, campaign moved to "Ditolak" tab

#### Negative Test / Validation
- [ ] Reject without reason → Validation "Alasan tolak wajib diisi"
- [ ] Approving already-approved campaign → Validation atau no change
- [ ] Double-click approve button → No duplicate action, loading state visible

#### UI/UX Test
- [ ] Tab navigation: active tab underline or background highlight (VBAT Blue)
- [ ] Campaign card shows: sponsor logo, placement type with icon, date formatted (e.g., "21 Oct 2026")
- [ ] "Setujui" button: Green or success color
- [ ] "Tolak" button: Red or warning color
- [ ] Slot indicators (e.g., "2/5 slots used") clear and visible
- [ ] Modal title and content clearly labeled

---

### 3.7 Event & Diskon Shop

#### Positive Test - View Events
- [ ] Event list load dengan all data visible
- [ ] Can view: Event name, Date range, Products assigned, Status

#### Positive Test - Create Event
- [ ] "Buat Event" button buka Create Event modal
- [ ] Form fields: Event name, Start date, End date, Description
- [ ] Product selection (multi-select or modal picker)
- [ ] Submit "Buat Event" → Event created, success toast, list refresh

#### Positive Test - Edit Event
- [ ] Event row → "Edit" button
- [ ] Form pre-filled dengan current event data
- [ ] Can update name, date range, products
- [ ] Submit "Simpan" → Event updated, success toast, list refresh

#### Positive Test - Delete Event
- [ ] Event row → "Delete" button
- [ ] Confirmation modal
- [ ] "Hapus" confirm → Event deleted, success toast, list refresh

#### Negative Test / Validation
- [ ] Create: Start date > End date → Validation error "Tanggal mulai harus sebelum tanggal akhir"
- [ ] Create: End date in past → Warning atau validation
- [ ] Create: No products selected → Validation "Pilih minimal 1 produk"

#### UI/UX Test
- [ ] Date picker opens correctly, can select date range
- [ ] Product selection shows product list with checkboxes
- [ ] Form labeled clearly with required field indicators

---

### 3.8 Bulk Upload Kelas

#### Positive Test - Upload CSV/XLSX
- [ ] "Upload File" or drag-and-drop zone visible
- [ ] Can select .CSV or .XLSX file
- [ ] Download template button provides sample file
- [ ] Upload valid file → Processing message, then success toast with rows imported count
- [ ] Upload history table shows: filename, upload date, status, rows processed

#### Negative Test / Validation
- [ ] Upload wrong file format (e.g., .txt, .pdf) → Error message "Format file harus CSV atau XLSX"
- [ ] Upload file > size limit → Error message "File terlalu besar, max 5MB"
- [ ] Upload file dengan invalid data (e.g., price = "ABC") → Error message listing rows with errors
- [ ] Upload empty file → Validation error
- [ ] Duplicate rows in file → Warning atau skip handling

#### UI/UX Test
- [ ] Drag-and-drop zone clear dengan dashed border, icon, instruction text
- [ ] File name displayed after selection
- [ ] Progress indicator visible during upload
- [ ] Success/error message clearly formatted
- [ ] Download template button easy to find

---

### 3.9 Push Notification (FCM)

#### Positive Test - View Notifications
- [ ] Notification list with tabs: "Menunggu Approval" dan "Riwayat"
- [ ] "Menunggu Approval" shows pending notifications with: Title, Message, Target audience, Status
- [ ] "Riwayat" shows sent/rejected notifications with: Title, Date sent, Status, Recipients count

#### Positive Test - Approve Notification
- [ ] Notification row → "Setujui" button
- [ ] Confirmation dialog: "Setujui dan kirim?"
- [ ] Click confirm → Notification sent to audience, success toast, moved to "Riwayat" tab

#### Positive Test - Reject Notification
- [ ] Notification row → "Tolak" button
- [ ] Rejection modal with reason input (mandatory)
- [ ] Submit "Tolak" → Notification rejected, reason saved, success toast, moved to "Riwayat"

#### Positive Test - Send New Notification
- [ ] "Kirim Notifikasi" button buka Create Notification form/modal
- [ ] Form fields: Title, Message, Target audience (Semua Pengguna / Sponsor Tertentu), Schedule (Kirim Sekarang / Jadwalkan)
- [ ] If schedule: Date/time picker visible
- [ ] Submit → Notification created, queued for approval, success toast

#### Negative Test / Validation
- [ ] Submit without title → Validation "Title wajib diisi"
- [ ] Submit without message → Validation "Message wajib diisi"
- [ ] Schedule date in past → Validation "Tanggal harus masa depan"
- [ ] Message > character limit → Warning atau truncation notification

#### UI/UX Test
- [ ] Tab navigation: active tab highlighted with VBAT Blue underline
- [ ] Form labels clear, required fields marked with *
- [ ] Audience selection: radio buttons atau dropdown for "Semua" vs "Tertentu"
- [ ] Schedule section visible only when "Jadwalkan" selected
- [ ] Character count visible for title/message fields (if applicable)

---

### 3.10 Daftar Akun User

#### Positive Test - View Users
- [ ] User list load with columns: Name, Email, Role, Status, Action
- [ ] Search box filter by name/email
- [ ] Filter by Role (Admin, Owner, Sponsor, User) - verify Owner role NOT displayed
- [ ] Filter by Status (Active, Inactive, Suspended)
- [ ] Pagination navigate bekerja

#### Positive Test - View User Details
- [ ] User row → "Lihat Detail" button
- [ ] Detail page/modal shows: Name, Email, Role, Status, Join date, Last login
- [ ] Can view linked sponsor (jika applicable)

#### Negative Test / Validation
- [ ] Search dengan keyword tidak ada match → "Tidak ada data" message
- [ ] Try to filter by "Owner" role → Role option not available (verify business rule)
- [ ] Access user detail → Verify jika role is displayed correctly (bukan gabungan roles)

#### UI/UX Test
- [ ] User list table: header bold, rows with alternating background (light gray/white in light mode)
- [ ] Role badge dengan warna berbeda: Admin = blue, Sponsor = green, User = gray
- [ ] Status badge: Active = green, Inactive = gray, Suspended = red
- [ ] Search icon visible, clear button available

---

### 3.11 Settings (Admin)

#### Positive Test - Profile Update
- [ ] Settings page load dengan current profile data: Name, Email
- [ ] Name field dapat diubah
- [ ] Email field dapat diubah (dengan validation jika email unique)
- [ ] Submit "Simpan" → Update profile, success toast

#### Positive Test - Delete Account
- [ ] "Hapus Akun" button visible (Red outline styling)
- [ ] Click → Delete confirmation modal with warning message
- [ ] Input password untuk confirm (security measure)
- [ ] "Hapus Akun" button → Account deleted, logout, redirect ke login page

#### Negative Test / Validation
- [ ] Update: Empty name → Validation "Nama wajib diisi"
- [ ] Update: Invalid email format → Validation "Format email tidak valid"
- [ ] Update: Email already in use → Error message
- [ ] Delete account: Wrong password → Error "Password salah"
- [ ] Delete account: Cancel confirmation → Modal close, account not deleted

#### UI/UX Test
- [ ] Profile form: labels bold, input fields with border
- [ ] "Simpan" button: Solid Blue (#1a68f0)
- [ ] "Hapus Akun" button: Red outline (#EF4444)
- [ ] Form validation errors inline near field
- [ ] Delete account warning text clear dan red-colored

---

## IV. MODUL MITRA SPONSOR

### 4.1 Dashboard Sponsor

#### Positive Test - Dashboard Load
- [ ] Dashboard load dengan all content visible
- [ ] Quota cards (4 types) display: Hero Slider, Horizontal Slider, Card Slider, Pop Up Iklan
- [ ] Each quota card shows: Used count, Total count, Percentage or progress bar
- [ ] Header section shows: Sponsor name, tier badge, avatar

#### Positive Test - Quick Overview Tables
- [ ] Campaign table shows recent campaigns: Kampanye name, Penempatan, Status
- [ ] Product table shows recent products: Product name, Thumbnail, Price
- [ ] Both tables clickable to navigate to respective pages

#### Negative Test / Validation
- [ ] No analytics/CTR/Views columns visible (only quota focus, NO analytics)
- [ ] Empty data: Sponsor with no campaigns/products → Tables show "Belum ada data" message
- [ ] Quota exceeded scenario: Show warning or visual indicator

#### UI/UX Test
- [ ] Quota cards: #FFFFFF background, subtle shadow, VBAT Blue accent color
- [ ] Card layout responsive pada tablet/mobile (stack vertically)
- [ ] Progress bars: Colored gradient (blue primary), percentage text centered
- [ ] Table headers bold, consistent with admin dashboard styling
- [ ] No hardcoded SVG icons for quota types (use FontAwesome: images, sliders-h, clone, bullhorn)
- [ ] Logo visible same as admin dashboard
- [ ] Quick action buttons (e.g., "Ajukan Kampanye") visible dan styled Solid Blue

---

### 4.2 Katalog Produk Saya (Sponsor Products)

#### Positive Test - View Products
- [ ] Product list load dengan all data visible: Thumbnail, Name, Price, Discount, Stock, Action
- [ ] Search box filter by product name
- [ ] Price prominently displayed with currency (Rp) dan thousands separator
- [ ] Discount displayed as percentage (e.g., "15%")

#### Positive Test - Add Product
- [ ] "Tambah Produk" button buka Add Product modal/form
- [ ] Form fields: Name, SKU, Description, Price, Discount, Stock, Category, Image upload
- [ ] Image upload: can select .jpg/.png file, preview shown
- [ ] Submit "Simpan" → Product created, success toast, list refresh

#### Positive Test - Edit Product
- [ ] Product row → "Edit" button
- [ ] Modal pre-fill dengan current product data
- [ ] Can update all fields
- [ ] Image: can replace current image atau keep existing
- [ ] Submit "Simpan" → Product updated, success toast, list refresh

#### Positive Test - Delete Product
- [ ] Product row → "Delete" button
- [ ] Confirmation modal with product name
- [ ] "Hapus" confirm → Product deleted, success toast, list refresh

#### Negative Test / Validation
- [ ] Add: Submit tanpa name → Validation "Nama produk wajib diisi"
- [ ] Add: Price input "ABC" → Validation "Harga harus angka"
- [ ] Add: Discount > Price → Validation "Diskon tidak boleh melebihi harga"
- [ ] Add: Stock negative → Validation "Stok tidak boleh negatif"
- [ ] Add: Image > 2MB → Error message "Gambar max 2MB"
- [ ] Add: Image format tidak support (e.g., .gif, .webp) → Error message "Format gambar harus JPG atau PNG"
- [ ] Edit: Upload new image → Old image replaced correctly
- [ ] Delete: Product linked to active campaign → Validation atau warning message

#### UI/UX Test
- [ ] "Tambah Produk" button: Solid Blue (#1a68f0)
- [ ] Form labels bold, required fields marked with *
- [ ] Image upload: drag-and-drop zone atau file picker button clear
- [ ] Image preview size appropriate (e.g., 150x150px thumbnail)
- [ ] Edit/Delete buttons subtle styling
- [ ] Product table: thumbnail image 100x100px, responsive on mobile

---

### 4.3 Pengajuan Kampanye - Hero Slider

#### Positive Test - View Campaigns
- [ ] Campaign list shows: Campaign name, Date submitted, Status, Action
- [ ] Status badge: "Menunggu Approval", "Disetujui", "Ditolak"
- [ ] Only campaign info visible, NO analytics (CTR, Views, etc.)

#### Positive Test - Submit Campaign
- [ ] "Ajukan Kampanye" button buka submission form/modal
- [ ] Form fields: Campaign name, Duration (start-end date), Media upload (image), URL, Headline, Description
- [ ] Media validation: Max 2MB, format .jpg/.png
- [ ] Image aspect ratio: 16:9 (untuk Hero slider)
- [ ] URL field: dapat input valid URL
- [ ] Submit "Ajukan" → Campaign submitted, success toast, list refresh dengan status "Menunggu Approval"

#### Positive Test - Edit Campaign (if pending)
- [ ] Campaign with status "Menunggu Approval" → "Edit" button available
- [ ] Can update all fields
- [ ] Submit "Simpan" → Campaign updated, stays in "Menunggu Approval"

#### Positive Test - Delete Campaign (if pending)
- [ ] Campaign with status "Menunggu Approval" → "Delete" button
- [ ] Confirmation modal
- [ ] "Hapus" confirm → Campaign deleted, success toast

#### Negative Test / Validation
- [ ] Submit without campaign name → Validation "Nama kampanye wajib diisi"
- [ ] Submit without media → Validation "Media wajib diupload"
- [ ] Submit: Media size > 2MB → Error "Gambar max 2MB"
- [ ] Submit: Media format not jpg/png → Error "Format gambar harus JPG atau PNG"
- [ ] Submit: Image aspect ratio not 16:9 → Warning atau auto-crop notification
- [ ] Submit without URL → Validation "URL wajib diisi"
- [ ] Submit with invalid URL (not http/https) → Validation "URL harus valid (http/https)"
- [ ] Submit without duration → Validation "Durasi kampanye wajib diisi"
- [ ] Edit/Delete: Campaign sudah approved → Button disabled atau not visible

#### UI/UX Test
- [ ] "Ajukan Kampanye" button: Solid Blue (#1a68f0)
- [ ] Form labels clear, required fields marked with *
- [ ] Media upload: drag-and-drop zone atau file picker button
- [ ] Image preview shown after upload (aspect ratio indicator if mismatch)
- [ ] URL field: can clear, shows error on invalid input
- [ ] Duration fields: date picker, can select range
- [ ] Form validation errors inline near field
- [ ] Status badge: "Menunggu Approval" = Yellow, "Disetujui" = Green, "Ditolak" = Red

---

### 4.4 Pengajuan Kampanye - Horizontal Slider

#### Positive Test - View Campaigns
- [ ] Campaign list shows: Campaign name, Date submitted, Status, Action
- [ ] Only campaign info visible, NO analytics

#### Positive Test - Submit Campaign
- [ ] "Ajukan Kampanye" button buka submission form
- [ ] Form fields: Campaign name, Duration, Media upload (image), URL, Headline, Description
- [ ] Media validation: Max 2MB, format .jpg/.png
- [ ] Image aspect ratio: 3:1 (untuk Horizontal slider)
- [ ] Submit "Ajukan" → Campaign submitted, success toast, status "Menunggu Approval"

#### Positive Test - Edit/Delete (if pending)
- [ ] Same as Hero Slider (if pending, can edit/delete)

#### Negative Test / Validation
- [ ] Submit: Media > 2MB → Error
- [ ] Submit: Image aspect ratio not 3:1 → Warning atau indicator
- [ ] Submit: Invalid URL → Validation error
- [ ] All other validations same as Hero Slider

#### UI/UX Test
- [ ] Form layout consistent dengan Hero Slider
- [ ] Aspect ratio indicator (3:1) visible near image preview
- [ ] All styling consistent dengan app design

---

### 4.5 Pengajuan Kampanye - Card Slider

#### Positive Test - View Campaigns
- [ ] Campaign list shows: Campaign name, Date submitted, Status, Action
- [ ] Only campaign info visible, NO analytics

#### Positive Test - Submit Campaign
- [ ] "Ajukan Kampanye" button buka submission form
- [ ] Form fields: Campaign name, Duration, Media upload (image), URL, Headline, Description
- [ ] Media validation: Max 2MB, format .jpg/.png
- [ ] Image aspect ratio: 4:5 (untuk Card slider)
- [ ] Submit "Ajukan" → Campaign submitted, success toast, status "Menunggu Approval"

#### Positive Test - Edit/Delete (if pending)
- [ ] Same as Hero Slider (if pending, can edit/delete)

#### Negative Test / Validation
- [ ] Submit: Image aspect ratio not 4:5 → Warning atau indicator
- [ ] All other validations same as Hero Slider

#### UI/UX Test
- [ ] Aspect ratio indicator (4:5) visible near image preview
- [ ] All styling consistent dengan app design

---

### 4.6 Pengajuan Kampanye - Pop Up Iklan

#### Positive Test - View Campaigns
- [ ] Campaign list shows: Campaign name, Date submitted, Status, Action
- [ ] Only campaign info visible, NO analytics

#### Positive Test - Submit Campaign
- [ ] "Ajukan Kampanye" button buka submission form
- [ ] Form fields: Campaign name, Duration, Media upload (image), URL, Headline, Description
- [ ] Media validation: Max 2MB, format .jpg/.png
- [ ] Image aspect ratio: Per design spec (TBD, document if exists)
- [ ] Submit "Ajukan" → Campaign submitted, success toast, status "Menunggu Approval"

#### Positive Test - Edit/Delete (if pending)
- [ ] Same as Hero Slider (if pending, can edit/delete)

#### Negative Test / Validation
- [ ] All validations same as Hero Slider

#### UI/UX Test
- [ ] Form layout consistent dengan Hero Slider
- [ ] Aspect ratio indicator visible if applicable
- [ ] All styling consistent dengan app design

---

### 4.7 Pengajuan Push Notification

#### Positive Test - View Notifications
- [ ] Notification list shows: Title, Message, Target, Date submitted, Status
- [ ] Status badge: "Menunggu Approval", "Disetujui", "Ditolak"
- [ ] Only notification info visible, NO analytics

#### Positive Test - Submit Notification
- [ ] "Kirim Notifikasi" button buka submission form/modal
- [ ] Form fields: Title, Message, Target audience, Schedule (Kirim Sekarang / Jadwalkan)
- [ ] If schedule: Date/time picker visible
- [ ] Submit "Ajukan" → Notification queued, success toast, status "Menunggu Approval"

#### Positive Test - Edit Notification (if pending)
- [ ] Notification with status "Menunggu Approval" → "Edit" button
- [ ] Can update all fields
- [ ] Submit "Simpan" → Notification updated, stays "Menunggu Approval"

#### Positive Test - Delete Notification (if pending)
- [ ] Notification with status "Menunggu Approval" → "Delete" button
- [ ] Confirmation modal
- [ ] "Hapus" confirm → Notification deleted, success toast

#### Negative Test / Validation
- [ ] Submit without title → Validation "Judul wajib diisi"
- [ ] Submit without message → Validation "Pesan wajib diisi"
- [ ] Submit: Schedule date in past → Validation "Tanggal harus masa depan"
- [ ] Submit: Title > character limit → Warning atau truncation
- [ ] Submit: Message > character limit → Warning atau truncation
- [ ] Edit/Delete: Notification sudah approved → Button disabled atau not visible

#### UI/UX Test
- [ ] "Kirim Notifikasi" button: Solid Blue (#1a68f0)
- [ ] Form labels clear, required fields marked with *
- [ ] Schedule section visible only when "Jadwalkan" selected
- [ ] Date/time picker functional
- [ ] Character count visible untuk title/message
- [ ] Status badge color consistent dengan campaign notifications
- [ ] Form validation errors inline near field

---

### 4.8 Notifikasi (Sponsor Notification Center)

#### Positive Test - View Notifications
- [ ] Notification center/page load dengan list notifications
- [ ] Each notification shows: Title, Message (excerpt), Date, Status (Read/Unread), Type icon
- [ ] Can mark notification as read/unread
- [ ] Click notification → Open detail atau dismiss

#### Negative Test / Validation
- [ ] Empty notification list → Show "Belum ada notifikasi" message
- [ ] Very old notifications → Can scroll/paginate

#### UI/UX Test
- [ ] Unread notifications: Bold text, background highlight (light color)
- [ ] Read notifications: Normal text, subtle background
- [ ] Notification icon berbeda per type (e.g., approval, rejection, system)
- [ ] Date formatted human-readable (e.g., "21 Oct, 10:30" atau "2 jam yang lalu")

---

### 4.9 Settings (Sponsor)

#### Positive Test - Profile Update
- [ ] Settings page load dengan current profile data: Company name, Email, Phone
- [ ] Fields dapat diubah
- [ ] Submit "Simpan" → Profile updated, success toast

#### Positive Test - Delete Account
- [ ] "Hapus Akun" button visible (Red outline styling)
- [ ] Click → Delete confirmation modal with warning
- [ ] Input password untuk confirm
- [ ] "Hapus Akun" button → Account deleted, logout, redirect login page

#### Negative Test / Validation
- [ ] Update: Empty company name → Validation "Nama perusahaan wajib diisi"
- [ ] Update: Invalid email → Validation "Format email tidak valid"
- [ ] Update: Email already in use → Error message
- [ ] Delete: Wrong password → Error "Password salah"
- [ ] Delete: Cancel confirmation → Modal close, account not deleted

#### UI/UX Test
- [ ] Form labels bold, input fields with border
- [ ] "Simpan" button: Solid Blue (#1a68f0)
- [ ] "Hapus Akun" button: Red outline (#EF4444)
- [ ] Delete account warning clear, red-colored

---

## V. CROSS-FUNCTIONAL TEST CASES

### 5.1 Responsiveness (Mobile, Tablet, Desktop)

#### Positive Test - Mobile (375px width)
- [ ] All pages load correctly pada mobile viewport
- [ ] Sidebar collapse to hamburger menu
- [ ] Tables stack vertically atau horizontal scroll
- [ ] Forms full-width, no overflow
- [ ] Buttons easy to tap (min 44px height)
- [ ] Touch interactions work (no hover-only features)

#### Positive Test - Tablet (768px width)
- [ ] Pages load correctly pada tablet
- [ ] Sidebar visible atau collapsible
- [ ] Tables readable dengan slight scroll
- [ ] 2-column layouts adapt to single/dual column as needed

#### Positive Test - Desktop (1920px width)
- [ ] Pages load correctly pada full desktop
- [ ] Sidebar full-width visible
- [ ] Tables full-width, all columns visible
- [ ] Multi-column layouts optimal

#### UI/UX Test
- [ ] No text overflow atau cutoff at any breakpoint
- [ ] Images responsive (max-width: 100%)
- [ ] Font sizes readable at all breakpoints (min 14px body, 16px on mobile)

---

### 5.2 Form Validation & Error Handling

#### Positive Test - Field Validation
- [ ] Required fields: Empty value triggers validation message before submit
- [ ] Email field: Non-email format triggers validation
- [ ] Number fields: Non-numeric input triggers validation
- [ ] URL field: Invalid URL format triggers validation
- [ ] Date fields: Invalid date format triggers validation
- [ ] Phone field: Invalid format triggers validation

#### Negative Test - Error Messages
- [ ] Error messages clear dan specific (bukan generic "Error")
- [ ] Multiple errors: All errors displayed atau listed
- [ ] Error message color: Red (#EF4444) atau similar warning color
- [ ] Error icons (exclamation) visible
- [ ] Error messages dismiss when field fixed

#### UI/UX Test
- [ ] Error messages inline near field atau above form
- [ ] Input field with error: red border atau background tint
- [ ] Helper text visible untuk field requirements (e.g., "Max 2MB" untuk upload)

---

### 5.3 Accessibility (Basic Checks)

#### Positive Test - Keyboard Navigation
- [ ] Can tab through all interactive elements (buttons, inputs, links)
- [ ] Tab order logical (left-to-right, top-to-bottom)
- [ ] Enter key activates buttons
- [ ] Escape key close modals

#### Positive Test - Screen Reader (Manual Check)
- [ ] Form labels associated dengan input fields (dapat dibaca screen reader)
- [ ] Button text clear (bukan icon-only)
- [ ] Modal has ARIA attributes (aria-modal, aria-labelledby)
- [ ] Images have alt text (jika decorative, empty alt)

#### UI/UX Test
- [ ] Focus indicator visible pada keyboard navigation (outline atau highlight)
- [ ] Color not only indicator (e.g., error tidak hanya red, include icon/text)
- [ ] Contrast ratio adequate untuk text (min 4.5:1 untuk body text WCAG AA)

---

### 5.4 Performance & Load Time

#### Positive Test - Page Load
- [ ] Dashboard loads dalam < 2 seconds (initial load)
- [ ] Table data load within reasonable time (< 3 seconds untuk 100+ rows)
- [ ] Modal/form open instantly atau with brief loading indication
- [ ] Image load smoothly (progressive loading preferred)

#### Positive Test - Interaction Response
- [ ] Button click response immediate (visual feedback)
- [ ] Form submit: Loading state visible, no double-submit
- [ ] Search/filter: Results update within 1 second
- [ ] No freezing atau UI lag saat interaction

#### UI/UX Test
- [ ] Loading indicator (spinner/skeleton) visible saat loading
- [ ] Progress indication untuk long operations (e.g., file upload)

---

### 5.5 Data Consistency & Persistence

#### Positive Test - CRUD Operations
- [ ] Create: Data saved dan visible in list immediately
- [ ] Read: Data display matches what was created
- [ ] Update: Changes saved dan reflected immediately
- [ ] Delete: Data removed dan no longer in list
- [ ] Refresh page: All data persists correctly

#### Positive Test - Session Persistence
- [ ] Login: Session maintained across page refresh
- [ ] Navigation: Unsaved data lost after page refresh (expected behavior, inform user)
- [ ] Logout: Session cleared completely

#### Negative Test - Concurrent Actions
- [ ] Two users editing same record: Later change overwrites atau conflict handling
- [ ] Delete while editing: Deleted record reflected, form shows error

---

## VI. SUMMARY & SIGN-OFF

### Test Execution Dates
- [ ] Testing Start Date: _______________
- [ ] Testing End Date: _______________

### Test Results Summary
- [ ] Total Test Cases: _______________
- [ ] Passed: _______________
- [ ] Failed: _______________
- [ ] Blocked/Skipped: _______________
- [ ] Pass Rate: _______________%

### Critical Issues Found
- [ ] None
- [ ] 1-3 Critical
- [ ] 4+ Critical

### UAT Sign-Off
- [ ] Sponsor Role Functionality: ✓ / ✗
- [ ] Admin Role Functionality: ✓ / ✗
- [ ] UI/UX Design Compliance: ✓ / ✗
- [ ] Performance Acceptable: ✓ / ✗
- [ ] Accessibility Basic Check: ✓ / ✗
- [ ] Ready for Production: ✓ / ✗

### Sign-Off By
- **QA Lead:** _________________________ Date: _____________
- **Product Owner:** _________________________ Date: _____________
- **Client/Sponsor:** _________________________ Date: _____________

---

## Appendix A: Design Token Reference

**Primary Color:** VBAT Solid Blue (#1a68f0)  
**Error Color:** Red (#EF4444)  
**Success Color:** Green (#22c55e)  
**Warning Color:** Amber (#f59e0b)  
**Info Color:** Blue (#3b82f6)  

**Light Mode Background:** #F4F6F8 (app), #FFFFFF (cards)  
**Dark Mode Background:** Dark slate/zinc (consistent with admin design)  

**Typography:**  
- Primary Text: #212121 (Light), #F5F5F5 (Dark)
- Secondary Text: #828282 (Light), #B0B0B0 (Dark)
- Font Family: Instrument Sans / Inter / Avenir Next  

**Button Styling:**
- Primary: Solid Blue (#1a68f0), white text
- Secondary: Outline or Gray background
- Delete: Red outline (#EF4444)
- Cancel: Gray outline

---

## Appendix B: Role & Permissions Matrix

| Feature | Super Admin | Sponsor |
|---------|-------------|---------|
| Dashboard | Full | Limited (Quota-only) |
| Analitik | Full | None |
| Manajemen Sponsor | Full | None |
| Produk Part | Full | None |
| Moderasi Kampanye | Full | None |
| Katalog Produk | None | Full (Own Products) |
| Pengajuan Kampanye | None | Full |
| Push Notification | Approve | Submit Only |
| Settings | Full | Limited (Profile Only) |

---

## Appendix C: Test Environment Requirements

- **Browser:** Chrome (latest), Firefox (latest), Safari (latest), Edge (latest)
- **OS:** Windows 10/11, macOS 12+, Ubuntu 20.04+
- **Mobile:** iOS 14+, Android 10+
- **Screen Resolutions:** 375px, 768px, 1024px, 1920px
- **Network:** Stable 5Mbps+
- **Test Server:** Staging environment (not production)

---

**Document Version:** 1.0  
**Last Updated:** 21 Oktober 2026  
**Next Review:** Post-UAT
