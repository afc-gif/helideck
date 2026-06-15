<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Inspection Detail - {{ $inspection->getLandingSiteName() }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #1c6f6b;
        }

        body {
            background: linear-gradient(135deg, #f6f5f2 0%, #f0ede5 100%);
            padding: 24px 0;
        }

        .container {
            max-width: 900px;
        }

        h1 {
            color: var(--accent);
            font-weight: 700;
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

        .section-title {
            background: #f5f5f5;
            padding: 12px 16px;
            border-left: 4px solid var(--accent);
            margin-top: 24px;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 16px;
        }

        .field {
            padding: 12px;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }

        .field-label {
            font-weight: 600;
            color: var(--accent);
            font-size: 12px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field-value {
            color: #333;
            word-break: break-word;
        }

        .meta-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .meta-card {
            background: #fff;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-align: center;
        }

        .meta-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .meta-value {
            font-weight: 600;
            color: var(--accent);
        }

        .badge {
            font-size: 12px;
            padding: 6px 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Back Link -->
        <a href="{{ route('inspections.index') }}" class="text-muted mb-3 d-inline-block">← Back to Inspections</a>

        <!-- Header -->
        <div class="mb-4">
            <h1>{{ $inspection->getLandingSiteName() }}</h1>
            <p class="text-muted">UUID: {{ $inspection->uuid }}</p>
        </div>

        <!-- Meta Information -->
        <div class="meta-info mb-4">
            <div class="meta-card">
                <div class="meta-label">Status</div>
                <div class="meta-value">
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
                </div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Inspector</div>
                <div class="meta-value">{{ $inspection->inspector->name }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Created</div>
                <div class="meta-value">{{ $inspection->created_at->format('M d, Y') }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Synced</div>
                <div class="meta-value">
                    @if($inspection->synced_at)
                        <small>✓ {{ $inspection->synced_at->format('M d, Y') }}</small>
                    @else
                        <small class="text-warning">Pending</small>
                    @endif
                </div>
            </div>
        </div>

        <!-- Form Data -->
        <div class="card">
            <div class="card-body">
                @foreach($inspection->form_data as $sectionKey => $sectionData)
                    @if(is_array($sectionData) && !empty($sectionData))
                        <div class="section-title">{{ ucwords(str_replace('_', ' ', $sectionKey)) }}</div>

                        <div class="field-row">
                            @foreach($sectionData as $fieldKey => $fieldValue)
                                @if(!is_array($fieldValue) && !is_object($fieldValue))
                                    <div class="field">
                                        <div class="field-label">{{ ucwords(str_replace('_', ' ', $fieldKey)) }}</div>
                                        <div class="field-value">{{ $fieldValue ?? '—' }}</div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-4 mb-5">
            <a href="{{ route('inspections.export.pdf', $inspection->uuid) }}" class="btn btn-primary">
                ↓ Export PDF
            </a>
            <a href="{{ route('inspections.index') }}" class="btn btn-outline-secondary">
                Back to List
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
</html>
