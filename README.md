# Helideck Inspection Reporting (Offline-First)

This workspace includes a vanilla JS PWA frontend and a Laravel backend scaffold that mirrors the **Rivers Conquest 20 Helideck Report** form.

## Structure
- `frontend/` Offline-first PWA (HTML/CSS/JS, Service Worker, IndexedDB)
- `backend/` Laravel scaffold (controllers, routes, models, migrations, Blade admin views)

## Frontend (Offline-First PWA)
Key files:
- `frontend/index.html`
- `frontend/styles.css`
- `frontend/app.js`
- `frontend/idb.js`
- `frontend/sw.js`
- `frontend/manifest.json`

Features:
- Offline-first operation with cached assets
- IndexedDB persistence for drafts and sync queue
- Sync statuses: `pending`, `synced`, `failed`
- Auto-sync on connectivity restoration
- Required field validation
- Typed and optional drawn signatures

## Backend (Laravel)
Key files:
- `backend/routes/api.php`
- `backend/app/Http/Controllers/AuthController.php`
- `backend/app/Http/Controllers/InspectionController.php`
- `backend/app/Models/Inspection.php`
- `backend/database/migrations/2026_02_11_000001_create_inspections_table.php`
- `backend/database/migrations/2026_02_11_000002_create_sync_logs_table.php`
- `backend/resources/views/admin/inspections/*.blade.php`

Requirements (install in your Laravel app):
- Laravel latest stable
- `laravel/sanctum`
- `barryvdh/laravel-dompdf` (for PDF export)

## API Endpoints
- `POST /api/login`
- `POST /api/logout`
- `POST /api/inspections/sync`
- `GET /api/inspections`
- `GET /api/inspections/{id}`
- `GET /api/inspections/{id}/export/pdf`
- `GET /api/inspections/export/csv`

## Example API Payloads

Login request:
```json
{ "email": "inspector@example.com", "password": "secret" }
```

Login response:
```json
{ "token": "sanctum-token", "user": { "id": 12, "email": "inspector@example.com" } }
```

Sync request:
```json
{
  "inspections": [
    {
      "id": "1e3cda7c-2f2f-4e88-9a2a-6f4f4a1e2c01",
      "updated_at": "2026-02-02T12:30:00Z",
      "data": {
        "landing_site_name": "RIVERS CONQUEST 20",
        "owner_operator": "JAD CONSTRUCTION",
        "date_current_inspection": "2026-02-02"
      }
    }
  ]
}
```

Sync response:
```json
{ "synced_ids": ["1e3cda7c-2f2f-4e88-9a2a-6f4f4a1e2c01"], "failed_ids": [] }
```

## Sync Rules
- Client sends local inspections with `updated_at`.
- Server uses **latest updated_at wins** for conflict resolution.
- No data loss: server keeps last authoritative form_data.

## Notes
- The frontend uses JSON-driven sections to mirror the PDF form.
- You can extend the schema in `frontend/app.js` for new inspection forms.
- For production, replace the Blade export with a formatted PDF template matching the original form layout.
