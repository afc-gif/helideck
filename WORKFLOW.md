# Complete Workflow - Inspector Portal + Admin Dashboard

## Inspector Flow

### 1. **Authentication** (`/login.html`)
- Inspector visits `/login.html`
- Can **Sign Up** (creates account) or **Login** (existing account)
- Backend: `POST /api/register` or `POST /api/login`
- Returns token + user info
- Token stored in `localStorage`
- Redirects to `/index.html` (PWA form)

### 2. **Fill Inspection Form** (`/index.html` - PWA)
Inspector can:
- Fill out Helideck Inspection Report form
- **Save as Draft** → Stored in IndexedDB locally
- Work **completely offline**
- Form saved with UUID for tracking
- Multiple drafts supported

### 3. **Submit Inspection**
Inspector clicks **Submit** button:
- Validates required fields
- Saves with `status: "submitted"`
- Queues for sync
- Shows message: "Submitted! Will sync when online"

### 4. **Automatic Sync**
When inspector goes online:
- PWA detects connectivity (online event)
- Calls backend `POST /api/inspections/sync` with queued inspections
- Backend handles conflict resolution using timestamps
- Frontend updates sync status
- Inspection marked as synced

**Sync Response Example:**
```json
[
  {
    "uuid": "123...",
    "status": "synced",
    "id": 5,
    "message": "Created"
  }
]
```

### 5. **Inspector's Portal** (Future feature)
- View all their own inspections (list)
- View submission status
- Resubmit if needed

---

## Admin Flow

### 1. **View Admin Dashboard** (`/admin/inspections`)
Admin (role: "admin") visits admin dashboard:
- Lists ALL inspections from all inspectors
- Shows inspection details:
  - Landing site name
  - Inspector who submitted
  - Status (draft, submitted, reviewed, approved)
  - Created date
  - Last updated date
  - Sync status

### 2. **Filter & Search**
Admin can filter by:
- **Status** (draft, submitted, reviewed, approved)
- **Date Range** (from_date, to_date)
- Pagination (per_page, page)

### 3. **View Inspection Detail**
Admin clicks "View" to see:
- Full inspection form data
- All sections (cover, installation, etc.)
- Metadata (who submitted, when, status)

### 4. **Export**
Admin can:
- **Export Single Inspection as PDF** → Formatted report
- **Export All as CSV** → For data analysis

### 5. **Status Management** (Future feature)
Admin can:
- Change status (draft → submitted → reviewed → approved)
- Add notes/comments
- Reject with reason

---

## Data Flow Diagram

```
INSPECTOR                           BACKEND                        ADMIN
────────────────────────────────────────────────────────────────────────

1. Sign Up/Login
   /login.html ─────────────────→ POST /api/login
                ←───────────────── token + user
   (store token in localStorage)

2. Fill & Save Form
   IndexedDB (offline storage)
   
3. Submit
   Queue inspection for sync
   
4. Go Online
   ─────────────────────────────→ POST /api/inspections/sync
                ←───────────────── sync results
   Update IndexedDB (synced: true)

5. Admin Reviews
                                                      GET /admin/inspections
                                                      (lists all submissions)
                                                      
                                                      GET /inspections/{uuid}
                                                      (views detail)
                                                      
                                                      GET /inspections/export/csv
                                                      (exports data)
```

---

## Key Files

### Frontend (PWA)
- `login.html` - Authentication page (Sign up + Login)
- `index.html` - Main inspection form
- `app.js` - Form logic + sync + auth
- `idb.js` - IndexedDB management
- `sw.js` - Service Worker (offline)
- `styles.css` - Responsive design

### Backend (Laravel)
- `AuthController.php` - Login/Register/Logout
- `InspectionController.php` - Sync, list, detail, export
- `routes/api.php` - API endpoints
- `Models/*` - User, Inspection, SyncLog
- `resources/views/admin/inspections/*` - Admin dashboard

---

## API Endpoints Reference

### Public Endpoints
```
POST /api/login       → Inspector login
POST /api/register    → Inspector sign up
```

### Protected Endpoints (require token)
```
POST /api/logout                           → Logout
GET /api/user                              → Get current user

POST /api/inspections/sync                 → Sync offline queue
GET /api/inspections                       → List inspections
GET /api/inspections/{uuid}                → View inspection
GET /api/inspections/{uuid}/export/pdf     → Export as PDF
GET /api/inspections/export/csv            → Export as CSV
```

---

## Security

✅ Token-based authentication (Laravel Sanctum)
✅ 1-year token validity (suitable for offline work)
✅ User-scoped queries (inspectors only see their data)
✅ Admin access control (via roles)
✅ Input validation on all endpoints
✅ Conflict resolution prevents data loss

---

## Offline Capabilities

✅ Complete form completion offline
✅ IndexedDB persistence
✅ Service Worker caching
✅ Automatic sync when online
✅ Sync queue with retry
✅ Conflict resolution with timestamps

---

## Next Steps / Future Features

1. **Inspector Portal** - Personal dashboard to view their submissions
2. **Admin Approval Workflow** - Change inspection status with comments
3. **Notifications** - Email when inspection submitted/reviewed
4. **Audit Trail** - Full history of changes
5. **Signature Storage** - Store drawn/typed signatures with inspections
6. **Mobile App** - Native iOS/Android version
7. **Real-time Sync** - WebSockets for live updates
8. **Advanced Reports** - Charts, analytics for admin

---

## Testing Checklist

- [ ] Inspector can sign up
- [ ] Inspector can log in
- [ ] Inspector can fill form offline
- [ ] Inspector can save draft
- [ ] Inspector can submit
- [ ] Inspector data syncs when online
- [ ] Admin can view all submissions
- [ ] Admin can filter by status/date
- [ ] Admin can export PDF
- [ ] Admin can export CSV
- [ ] Conflict resolution works (test offline edits)
- [ ] Service Worker caches form
- [ ] Works on mobile devices
