<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Helideck Inspections - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #1c6f6b;
            --danger: #b12b2b;
            --ok: #1b7f3a;
            --warn: #b67d00;
        }

        body {
            background: linear-gradient(135deg, #f6f5f2 0%, #f0ede5 100%);
            padding: 24px 0;
        }

        .container {
            max-width: 1200px;
        }

        h1 {
            color: var(--accent);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card {
            border: 1px solid #e3e2dd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        .btn-primary:hover {
            background-color: #165959;
            border-color: #165959;
        }

        .btn-success {
            background-color: var(--ok);
            border-color: var(--ok);
        }

        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }

        .badge {
            font-size: 12px;
            padding: 6px 10px;
        }

        .text-muted {
            font-size: 13px;
        }

        .table td {
            vertical-align: middle;
        }

        .status-badge {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>Helideck Inspections</h1>
                <p class="text-muted">Manage and review inspection submissions</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('inspections.export.csv') }}" class="btn btn-success">
                    ↓ Export CSV
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('inspections.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inspections Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Landing Site</th>
                            <th>Inspector</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Synced</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inspections as $inspection)
                            <tr>
                                <td>
                                    <strong>{{ $inspection->getLandingSiteName() }}</strong>
                                    <br>
                                    <small class="text-muted">{{ substr($inspection->uuid, 0, 13) }}...</small>
                                </td>
                                <td>{{ $inspection->inspector->name }}</td>
                                <td>
                                    @switch($inspection->status)
                                        @case('draft')
                                            <span class="badge bg-secondary">Draft</span>
                                            @break
                                        @case('submitted')
                                            <span class="badge bg-info">Submitted</span>
                                            @break
                                        @case('reviewed')
                                            <span class="badge bg-warning">Reviewed</span>
                                            @break
                                        @case('approved')
                                            <span class="badge bg-success">Approved</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $inspection->created_at->format('M d, Y') }}</td>
                                <td>{{ $inspection->updated_at->format('M d, Y H:i') }}</td>
                                <td>
                                    @if($inspection->synced_at)
                                        <small class="text-success">✓ {{ $inspection->synced_at->format('M d') }}</small>
                                    @else
                                        <small class="text-warning">○ Pending</small>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('inspections.show', $inspection->uuid) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('inspections.export.pdf', $inspection->uuid) }}" class="btn btn-sm btn-outline-success">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <p class="text-muted mb-0">No inspections found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($inspections->hasPages())
            <div class="row mt-4">
                <div class="col-md-12">
                    {{ $inspections->links() }}
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

  {{ $inspections->links() }}
</body>
</html>
