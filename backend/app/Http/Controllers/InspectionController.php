<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Inspection Controller
 * 
 * Handles sync, retrieval, and management of inspections
 * Supports offline-first PWA with conflict resolution
 */
class InspectionController extends Controller
{
    /**
     * Sync Inspections
     * 
     * POST /api/inspections/sync
     * 
     * Accepts array of inspections from offline queue
     * Resolves conflicts using updated_at timestamp
     * Idempotent - same payload can be resent safely
     * 
     * Request body:
     * [
     *   {
     *     "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *     "form_data": { ...all form fields },
     *     "status": "draft",
     *     "created_at": "2026-02-11T10:00:00Z",
     *     "updated_at": "2026-02-11T10:30:00Z"
     *   }
     * ]
     * 
     * Response (200):
     * [
     *   {
     *     "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *     "status": "synced",
     *     "id": 1
     *   },
     *   {
     *     "uuid": "223e4567-e89b-12d3-a456-426614174000",
     *     "status": "skipped",
     *     "reason": "Server version is newer"
     *   }
     * ]
     */
    public function sync(Request $request)
    {
        // Validate request
        $request->validate([
            '*.uuid' => 'required|uuid',
            '*.form_data' => 'required|array',
            '*.status' => 'required|in:draft,submitted,reviewed,approved',
            '*.created_at' => 'required|date_format:Y-m-d\TH:i:s\Z',
            '*.updated_at' => 'required|date_format:Y-m-d\TH:i:s\Z',
        ]);

        $inspector = $request->user();
        $results = [];

        foreach ($request->all() as $payload) {
            try {
                $result = $this->syncSingleInspection($inspector, $payload);
                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = [
                    'uuid' => $payload['uuid'],
                    'status' => 'failed',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json($results, 200);
    }

    /**
     * Sync single inspection - handles conflict resolution
     * 
     * Returns: {uuid, status: synced|skipped|failed, id, reason}
     */
    /**
     * Sync single inspection - handles conflict resolution
     *
     * @param  User  $inspector
     * @param  array $payload
     * @return array
     */
    private function syncSingleInspection(User $inspector, array $payload): array
    {
        $uuid = $payload['uuid'];
        $incomingUpdatedAt = \Carbon\Carbon::parse($payload['updated_at']);

        // Check if inspection exists locally
        $existing = Inspection::where('uuid', $uuid)
            ->where('inspector_id', $inspector->id)
            ->first();

        if ($existing) {
            // Compare timestamps - server wins if same or newer
            if ($existing->updated_at >= $incomingUpdatedAt) {
                // Server version is same or newer - skip
                SyncLog::create([
                    'inspection_id' => $existing->id,
                    'action' => 'skipped',
                    'message' => 'Server version is same or newer',
                    'payload' => $payload,
                    'inspector_id' => $inspector->id,
                ]);

                return [
                    'uuid' => $uuid,
                    'status' => 'skipped',
                    'reason' => 'Server version is same or newer',
                    'id' => $existing->id,
                ];
            }

            // Client version is newer - update
            $existing->update([
                'form_data' => $payload['form_data'],
                'status' => $payload['status'],
                'synced_at' => now(),
                'updated_at' => $incomingUpdatedAt,
            ]);

            SyncLog::create([
                'inspection_id' => $existing->id,
                'action' => 'updated',
                'message' => 'Updated from offline sync',
                'payload' => $payload,
                'inspector_id' => $inspector->id,
            ]);

            return [
                'uuid' => $uuid,
                'status' => 'synced',
                'id' => $existing->id,
                'message' => 'Updated',
            ];
        }

        // New inspection - create it
        $inspection = Inspection::create([
            'uuid' => $uuid,
            'inspector_id' => $inspector->id,
            'form_data' => $payload['form_data'],
            'status' => $payload['status'],
            'synced_at' => now(),
            'created_at' => \Carbon\Carbon::parse($payload['created_at']),
            'updated_at' => $incomingUpdatedAt,
        ]);

        SyncLog::create([
            'inspection_id' => $inspection->id,
            'action' => 'created',
            'message' => 'Created from offline sync',
            'payload' => $payload,
            'inspector_id' => $inspector->id,
        ]);

        return [
            'uuid' => $uuid,
            'status' => 'synced',
            'id' => $inspection->id,
            'message' => 'Created',
        ];
    }

    /**
     * Get all inspections for authenticated inspector
     * 
     * GET /api/inspections
     * Query params:
     *   - page: pagination (default 1)
     *   - per_page: items per page (default 15)
     *   - status: filter by status (draft|submitted|reviewed|approved)
     *   - from_date: filter by created date (YYYY-MM-DD)
     *   - to_date: filter by created date (YYYY-MM-DD)
     * 
     * Response (200):
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *       "status": "draft",
     *       "created_at": "2026-02-11T10:00:00Z",
     *       "updated_at": "2026-02-11T10:30:00Z"
     *     }
     *   ],
     *   "meta": {
     *     "total": 100,
     *     "per_page": 15,
     *     "current_page": 1
     *   }
     * }
     */
    public function index(Request $request)
    {
        $inspector = $request->user();
        $query = Inspection::query()->with('inspector');

        if ($inspector->role !== 'admin') {
            $query->where('inspector_id', $inspector->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('inspector', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereRaw("form_data::text ilike ?", ["%{$search}%"]);
            });
        }

        // Paginate
        $per_page = $request->input('per_page', 15);
        $inspections = $query->latest('updated_at')->paginate($per_page);

        return response()->json([
            'data' => $inspections->items(),
            'meta' => [
                'total' => $inspections->total(),
                'per_page' => $inspections->perPage(),
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get single inspection by UUID
     * 
     * GET /api/inspections/{uuid}
     * 
     * Response (200):
     * {
     *   "id": 1,
     *   "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *   "status": "draft",
     *   "form_data": { ...all fields },
     *   "created_at": "2026-02-11T10:00:00Z",
     *   "updated_at": "2026-02-11T10:30:00Z"
     * }
     */
    public function show(Request $request, string $uuid)
    {
        $inspector = $request->user();
        $query = Inspection::where('uuid', $uuid)->with('inspector');

        if ($inspector->role !== 'admin') {
            $query->where('inspector_id', $inspector->id);
        }

        $inspection = $query->firstOrFail();

        return response()->json($inspection, 200);
    }

    /**
     * Export inspection as PDF
     * 
     * GET /api/inspections/{uuid}/export/pdf
     * 
     * Returns PDF file
     */
    public function exportPdf(Request $request, string $uuid)
    {
        $inspector = $request->user();
        $query = Inspection::where('uuid', $uuid);

        if ($inspector->role !== 'admin') {
            $query->where('inspector_id', $inspector->id);
        }

        $inspection = $query->firstOrFail();

        // DomPDF will be implemented in export service
        return response()->json([
            'message' => 'PDF export - implement with DomPDF',
            'uuid' => $uuid,
        ], 200);
    }

    /**
     * Export all inspections as CSV
     * 
     * GET /api/inspections/export/csv
     * 
     * Returns CSV file
     */
    public function exportCsv(Request $request)
    {
        $inspector = $request->user();
        $query = Inspection::query()->with('inspector');

        if ($inspector->role !== 'admin') {
            $query->where('inspector_id', $inspector->id);
        }

        $inspections = $query->latest('created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inspections_export.csv"'
        ];

        $callback = function () use ($inspections) {
            $file = fopen('php://output', 'w');
            // CSV headers
            fputcsv($file, [
                'UUID',
                'Status',
                'Landing Site',
                'Inspector',
                'Created Date',
                'Updated Date',
            ]);

            // CSV rows
            foreach ($inspections as $inspection) {
                fputcsv($file, [
                    $inspection->uuid,
                    $inspection->status,
                    $inspection->getLandingSiteName(),
                    $inspection->inspector?->email,
                    $inspection->created_at->format('Y-m-d H:i:s'),
                    $inspection->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
