<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DairyIQ Report - {{ $batch->batch_number }}</title>
    @include('reports.partials.styles')
</head>
<body class="report-preview">
    <div class="preview-toolbar">
        <a href="{{ route('reports.show', $batch) }}">Download PDF</a>
    </div>

    @include('reports.partials.content')
</body>
</html>
