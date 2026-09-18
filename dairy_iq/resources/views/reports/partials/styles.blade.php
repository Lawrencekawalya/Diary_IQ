<style>
    @page {
        size: A4;
        margin: 12mm;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        color: #172033;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10px;
        line-height: 1.25;
    }

    .preview-toolbar {
        background: #0f2f80;
        color: #fff;
        padding: 12px 18px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .preview-toolbar a {
        background: #fff;
        border-radius: 6px;
        color: #0f2f80;
        display: inline-block;
        font-weight: 700;
        padding: 8px 14px;
        text-decoration: none;
    }

    .report-page {
        margin: 0 auto;
        page-break-after: auto;
        page-break-inside: auto;
        padding: 0;
        width: 160mm;
    }

    .report-header {
        border-bottom: 3px solid #0f2f80;
        display: block;
        margin-bottom: 10px;
        padding-bottom: 7px;
        width: 100%;
    }

    .report-header > div {
        display: block;
        width: 100%;
    }

    .report-header > div:last-child {
        font-size: 9px;
        margin-top: 3px;
        text-align: left;
    }

    .section-divider {
        border-top: 1px solid #d8e3f7;
        margin: 10px 0;
    }

    .brand {
        color: #0f2f80;
        font-size: 18px;
        font-weight: 800;
    }

    .muted {
        color: #607089;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .grid {
        display: table;
        table-layout: fixed;
        width: 100%;
    }

    .grid-cell {
        display: block;
        padding-right: 0;
        vertical-align: top;
        width: 100%;
    }

    .grid-cell:last-child {
        padding-left: 0;
        padding-right: 0;
    }

    .card {
        border: 1px solid #d8e3f7;
        border-radius: 10px;
        margin-bottom: 9px;
        padding: 9px;
    }

    .summary {
        background: #eaf4ff;
        border-color: #b9d8ff;
    }

    h1, h2, h3 {
        color: #0f2f80;
        margin: 0 0 9px;
    }

    h2 {
        font-size: 14px;
    }

    table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 100%;
    }

    th {
        background: #0f2f80;
        color: #fff;
        text-align: left;
    }

    th, td {
        border: 1px solid #cdd6e5;
        padding: 4px;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .prediction {
        font-size: 30px;
        font-weight: 800;
        margin: 5px 0;
    }

    .prediction-high {
        color: #16853a;
    }

    .prediction-medium {
        color: #b77900;
    }

    .prediction-low {
        color: #b42318;
    }

    .bar-track {
        background: #edf2f7;
        border-radius: 999px;
        height: 10px;
        overflow: hidden;
    }

    .bar {
        background: #0f2f80;
        height: 100%;
    }

    .status-normal {
        color: #16853a;
        font-weight: 700;
    }

    .status-out {
        color: #b42318;
        font-weight: 700;
    }

    .chart-row {
        display: table;
        margin-bottom: 6px;
        width: 100%;
    }

    .chart-label,
    .chart-bar,
    .chart-value {
        display: table-cell;
        vertical-align: middle;
    }

    .chart-label {
        width: 90px;
    }

    .chart-bar {
        width: auto;
    }

    .chart-value {
        padding-left: 10px;
        text-align: right;
        width: 72px;
    }

    @media screen {
        body.report-preview {
            background: #f3f6fb;
        }

        body.report-preview .report-page {
            background: #fff;
            box-shadow: 0 8px 30px rgba(15, 47, 128, 0.12);
            margin: 20px auto;
            min-height: 267mm;
            padding: 14mm;
            width: 198mm;
        }
    }

    @media print {
        .preview-toolbar {
            display: none;
        }

        body {
            background: #fff;
        }

        .report-page {
            min-height: auto;
        }
    }
</style>
