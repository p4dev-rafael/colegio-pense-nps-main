<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('survey_batches.pdf.title') }} — {{ $report->batch->title }}</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --surface: #f8fafc;
            --accent: #0e7490;
            --accent-soft: #ecfeff;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --neutral: #64748b;
        }

        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 0.9;
            color: var(--ink);
            background: #fff;
        }

        h1, h2, h3, h4 {
            line-height: 1;
            margin: 0;
            font-weight: 700;
        }

        p {
            margin: 0;
        }

        .page-break {
            page-break-before: always;
        }

        .report-header {
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .report-header__eyebrow {
            font-size: 8pt;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
            font-weight: 700;
        }

        .report-header__title {
            font-size: 18pt;
            margin-top: 4px;
        }

        .report-header__meta {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px 16px;
            color: var(--muted);
            font-size: 8.5pt;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .card--accent {
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
            border-color: #99f6e4;
        }

        .card__title {
            font-size: 10pt;
            margin-bottom: 8px;
            color: var(--ink);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .kpi {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px;
            background: #fff;
        }

        .kpi__label {
            font-size: 8pt;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .kpi__value {
            font-size: 16pt;
            font-weight: 700;
            margin-top: 4px;
            color: var(--accent);
        }

        .kpi__value--muted {
            color: var(--muted);
            font-size: 14pt;
        }

        .two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        .table th,
        .table td {
            border-bottom: 1px solid var(--line);
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted);
            background: #fff;
        }

        .table td.num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .comment {
            border-left: 3px solid var(--accent);
            padding: 6px 8px;
            margin-bottom: 6px;
            background: #fff;
        }

        .comment__meta {
            font-size: 8pt;
            color: var(--muted);
            margin-bottom: 3px;
        }

        .comment__text {
            white-space: pre-wrap;
            line-height: 0.95;
        }

        .response-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px 12px;
            font-size: 8.5pt;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .response-meta strong {
            color: var(--ink);
        }

        .qa-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .qa-row:last-child {
            border-bottom: 0;
        }

        .qa-row__label {
            color: var(--muted);
        }

        .qa-row__value {
            font-weight: 700;
            text-align: right;
        }

        .qa-row--text {
            grid-template-columns: 1fr;
        }

        .qa-row--text .qa-row__value {
            text-align: left;
            font-weight: 400;
            margin-top: 3px;
            white-space: pre-wrap;
            line-height: 0.95;
            color: var(--ink);
        }

        .teacher-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            margin-top: 8px;
            background: #fff;
        }

        .teacher-card__name {
            font-size: 9.5pt;
            margin-bottom: 6px;
        }

        .empty-state {
            color: var(--muted);
            font-size: 9pt;
            padding: 8px 0;
        }

        .section-heading {
            font-size: 12pt;
            margin: 14px 0 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid var(--line);
        }
    </style>
</head>
<body>
    @include('pdf.survey-batch.partials.summary', ['report' => $report])

    @foreach ($report->responses as $response)
        <div class="page-break">
            @include('pdf.survey-batch.partials.response', ['response' => $response, 'index' => $loop->iteration])
        </div>
    @endforeach
</body>
</html>
