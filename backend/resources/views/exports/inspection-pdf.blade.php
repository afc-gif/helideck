<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helideck Inspection Report - {{ $inspection->uuid }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            font-size: 12px;
        }

        .meta-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            font-size: 12px;
        }

        .meta-field {
            border: 1px solid #ddd;
            padding: 10px;
            background: #f9f9f9;
        }

        .meta-field label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .section {
            page-break-inside: avoid;
            margin-bottom: 30px;
        }

        .section-title {
            background: #f0f0f0;
            padding: 10px;
            font-weight: bold;
            border-left: 4px solid #1c6f6b;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .section-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            font-size: 11px;
        }

        .field {
            border: 1px solid #e0e0e0;
            padding: 8px;
            background: #fafafa;
        }

        .field-label {
            font-weight: bold;
            color: #1c6f6b;
            margin-bottom: 3px;
            font-size: 10px;
        }

        .field-value {
            color: #333;
            word-wrap: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }

        table th {
            background: #e8e8e8;
            padding: 8px;
            text-align: left;
            border: 1px solid #ccc;
            font-weight: bold;
        }

        table td {
            padding: 6px;
            border: 1px solid #ccc;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>HELIDECK INSPECTION REPORT</h1>
        <p>Rivers Conquest 20 Helideck Report - HDIR Rev 15</p>
        <p>Report ID: {{ $inspection->uuid }}</p>
    </div>

    <!-- Metadata -->
    <div class="meta-info">
        <div class="meta-field">
            <label>Status:</label>
            <span>{{ ucfirst($inspection->status) }}</span>
        </div>
        <div class="meta-field">
            <label>Inspector:</label>
            <span>{{ $inspection->inspector->name }}</span>
        </div>
        <div class="meta-field">
            <label>Created Date:</label>
            <span>{{ $inspection->created_at->format('M d, Y H:i') }}</span>
        </div>
        <div class="meta-field">
            <label>Last Updated:</label>
            <span>{{ $inspection->updated_at->format('M d, Y H:i') }}</span>
        </div>
    </div>

    <!-- Form Sections -->
    @foreach($formData as $sectionKey => $sectionData)
        @if(is_array($sectionData) && !empty($sectionData))
            <div class="section">
                <div class="section-title">
                    {{ ucwords(str_replace('_', ' ', $sectionKey)) }}
                </div>

                <div class="section-body">
                    @foreach($sectionData as $fieldKey => $fieldValue)
                        @if(!is_array($fieldValue) && !is_object($fieldValue))
                            <div class="field">
                                <div class="field-label">
                                    {{ ucwords(str_replace('_', ' ', $fieldKey)) }}
                                </div>
                                <div class="field-value">
                                    {{ $fieldValue ?? '—' }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <!-- Footer -->
    <div class="footer">
        <p>
            Generated on {{ now()->format('M d, Y H:i:s') }}
            <br>
            This is an automated PDF export from the Helideck Inspection System
        </p>
    </div>
</body>
</html>
