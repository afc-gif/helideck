<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <style>
    body { font-family: "Times New Roman", serif; font-size: 12px; }
    h1 { text-align: center; }
    .section { margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #444; padding: 4px; }
  </style>
</head>
<body>
  <h1>Helideck Inspection Report</h1>

  <div class="section">
    <strong>Landing Site:</strong> {{ $inspection->landing_site_name }}<br />
    <strong>Owner/Operator:</strong> {{ $inspection->owner_operator }}<br />
    <strong>Inspection Date:</strong> {{ $inspection->inspection_date }}
  </div>

  <div class="section">
    <h3>Form Data</h3>
    <pre>{{ json_encode($inspection->form_data, JSON_PRETTY_PRINT) }}</pre>
  </div>
</body>
</html>
