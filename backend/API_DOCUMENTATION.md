# Helideck Inspection System - Backend API Documentation

## Overview

This Laravel backend provides a production-ready API for the offline-first PWA frontend. It handles:
- Token-based authentication (Laravel Sanctum)
- Offline sync with conflict resolution
- Persistent inspection storage
- Admin dashboard for reviewing submissions
- PDF and CSV exports

## Architecture

### Database Schema

**users** table
```
- id (primary)
- name
- email (unique)
- password (hashed)
- role (admin | inspector)
- timestamps
```

**inspections** table
```
- id (primary)
- uuid (unique) - Client-side reference
- inspector_id (FK to users)
- form_data (JSON) - Complete form submission
- status (draft|submitted|reviewed|approved)
- synced_at (timestamp)
- timestamps
```

**sync_logs** table (audit trail)
```
- id (primary)
- inspection_id (FK)
- action (created|updated|skipped|failed)
- message (reason)
- payload (JSON)
- inspector_id (FK)
- synced_at
```

## API Endpoints

### Public Endpoints

#### POST /api/login
Authenticates an inspector and returns a long-lived token.

**Request:**
```json
{
  "email": "inspector@example.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 1,
    "name": "John Inspector",
    "email": "inspector@example.com",
    "role": "inspector"
  }
}
```

**Error (401):**
```json
{
  "message": "The provided credentials are incorrect."
}
```

---

### Protected Endpoints (require auth:sanctum)

All protected endpoints require:
```
Authorization: Bearer {token}
```

#### GET /api/user
Returns authenticated user details.

**Response (200):**
```json
{
  "id": 1,
  "name": "John Inspector",
  "email": "inspector@example.com",
  "role": "inspector"
}
```

#### POST /api/logout
Revokes current authentication token.

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

#### POST /api/inspections/sync
**Syncs offline queue to server with conflict resolution**

This is the core endpoint for the offline-first PWA. It handles:
- Creating new inspections
- Updating existing inspections
- Detecting conflicts using `updated_at` timestamps
- Idempotent updates (safe to retry)
- Audit logging via sync_logs

**Request:**
```json
[
  {
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "form_data": {
      "cover": {
        "landing_site_name": "Rivers Conquest 20",
        "owner_operator": "Bristow Helicopters",
        "inspector": "John Doe",
        "operational_clearance": "Day/Night",
        "d_value": "0.5",
        "t_value": "0.3",
        "date_current_inspection": "2026-02-11",
        "date_next_inspection": "2026-03-11"
      },
      "installation_details": {
        "installation_type": "Offshore Platform",
        "platform_type": "FPSO"
      }
    },
    "status": "draft",
    "created_at": "2026-02-11T10:00:00Z",
    "updated_at": "2026-02-11T10:30:00Z"
  }
]
```

**Response (200):**
```json
[
  {
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "status": "synced",
    "id": 5,
    "message": "Created"
  },
  {
    "uuid": "223e4567-e89b-12d3-a456-426614174000",
    "status": "skipped",
    "reason": "Server version is same or newer",
    "id": 4
  },
  {
    "uuid": "323e4567-e89b-12d3-a456-426614174000",
    "status": "failed",
    "reason": "Validation error: landing_site_name is required"
  }
]
```

**Per-record sync logic:**
1. Find inspection by UUID and inspector_id
2. If exists:
   - Compare `updated_at` timestamps
   - If server is newer: skip (returns "skipped")
   - If client is newer: update and log as "updated"
3. If not exists: create new inspection and log as "created"
4. Return status for each record (idempotent - safe to retry)

---

#### GET /api/inspections
Lists all inspections for authenticated inspector with pagination and filtering.

**Query Parameters:**
```
- page: 1, 2, 3... (default 1)
- per_page: 5, 10, 15... (default 15)
- status: draft | submitted | reviewed | approved
- from_date: YYYY-MM-DD
- to_date: YYYY-MM-DD
```

**Example:** `GET /api/inspections?status=submitted&per_page=20&page=1`

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "uuid": "123e4567-e89b-12d3-a456-426614174000",
      "inspector_id": 1,
      "status": "draft",
      "synced_at": "2026-02-11T10:45:00Z",
      "created_at": "2026-02-11T10:00:00Z",
      "updated_at": "2026-02-11T10:30:00Z"
    }
  ],
  "meta": {
    "total": 150,
    "per_page": 15,
    "current_page": 1,
    "last_page": 10
  }
}
```

---

#### GET /api/inspections/{uuid}
Retrieves single inspection by UUID.

**Response (200):**
```json
{
  "id": 1,
  "uuid": "123e4567-e89b-12d3-a456-426614174000",
  "inspector_id": 1,
  "form_data": { ...all fields },
  "status": "draft",
  "synced_at": "2026-02-11T10:45:00Z",
  "created_at": "2026-02-11T10:00:00Z",
  "updated_at": "2026-02-11T10:30:00Z"
}
```

**Error (404):** Inspection not found

---

#### GET /api/inspections/{uuid}/export/pdf
Exports single inspection as PDF.

**Response:** PDF file stream

---

#### GET /api/inspections/export/csv
Exports all inspections as CSV.

**Response:** CSV file stream

---

## Sync Logic Deep Dive

### Conflict Resolution

The system resolves conflicts using **server-friendly timestamp comparison**:

```php
$incomingUpdatedAt = Carbon::parse($payload['updated_at']);

if ($existing->updated_at >= $incomingUpdatedAt) {
    // Server has same or newer version → skip update
    // Prevents data loss from older edits
    return 'skipped';
} else {
    // Client has newer version → update
    $existing->update(['form_data' => $payload['form_data']]);
    return 'synced';
}
```

**Why this works:**
- Offline edits have timestamps from when the client made the edit
- Server-side edits have timestamps from when server processed the update
- Comparing timestamps ensures the latest version always wins
- Idempotent: resending same payload always produces same result

### Audit Trail

Every sync creates a `SyncLog` entry:
```php
SyncLog::create([
    'inspection_id' => $inspection->id,
    'action' => 'created|updated|skipped|failed',
    'message' => 'Reason if applicable',
    'payload' => $payload, // What was sent
    'inspector_id' => $inspector->id,
    'synced_at' => now(),
]);
```

Admins can debug sync issues via logs.

---

## Security & Validation

### Authentication
- Uses Laravel Sanctum tokens
- Tokens valid for 1 year (suitable for offline work)
- Tokens scoped to prevent unauthorized access
- Each request checked against authenticated user's ID

### Authorization
- Inspectors can only sync/view their own inspections
- Admins can view all inspections (implement with policies)
- Routes protected with `auth:sanctum` middleware

### Validation
All payloads validated before processing:
```php
$request->validate([
    '*.uuid' => 'required|uuid',
    '*.form_data' => 'required|array',
    '*.status' => 'required|in:draft,submitted,reviewed,approved',
    '*.created_at' => 'required|date_format:Y-m-d\TH:i:s\Z',
    '*.updated_at' => 'required|date_format:Y-m-d\TH:i:s\Z',
]);
```

### JSON Stored Safely
Form data stored as JSON with proper casting:
```php
protected $casts = [
    'form_data' => 'json',
    'synced_at' => 'datetime',
];
```

---

## Admin Dashboard

### Accessible at: `/admin/inspections`

Features:
- ✅ List all inspections with pagination
- ✅ Filter by status, date range
- ✅ Search by landing site / inspector
- ✅ View inspection detail
- ✅ Export single inspection as PDF
- ✅ Export all as CSV
- ✅ Status badges and sync tracking

---

## Error Handling

### Common Errors

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "0.uuid": ["The uuid field is required."]
  }
}
```

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**404 Not Found:**
```json
{
  "message": "Resource not found"
}
```

---

## Testing the API

### Login
```bash
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "inspector@example.com",
    "password": "password"
  }'
```

### List Inspections
```bash
curl -H "Authorization: Bearer TOKEN" \
  http://localhost:8001/api/inspections
```

### Sync Offline Queue
```bash
curl -X POST http://localhost:8001/api/inspections/sync \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '[...]'
```

---

## Production Checklist

- [ ] Configure `.env` with database credentials
- [ ] Run migrations: `php artisan migrate`
- [ ] Create admin user: `php artisan tinker` → `User::create(...)`
- [ ] Set up CORS if frontend on different domain
- [ ] Enable HTTPS in production
- [ ] Configure token expiration as needed
- [ ] Set up logging and monitoring
- [ ] Test offline sync thoroughly
- [ ] Implement admin policies for role-based access
- [ ] Set up PDF export with DomPDF

---

## Next Steps

1. **Implement Admin Policies** - Restrict admin access
2. **PDF Export** - Install `barryvdh/laravel-dompdf` and use export service
3. **Frontend Integration** - Update app.js to call these endpoints
4. **Database Seeding** - Create test users and inspections
5. **Error Logging** - Implement comprehensive error tracking
