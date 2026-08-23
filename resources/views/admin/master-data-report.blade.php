<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['config']['label'] }} - Core Farmasi UBP</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f8ff;
            --card: #ffffff;
            --line: #d7e4ff;
            --text: #10214a;
            --muted: #5a6f9f;
            --brand: #1d5fe4;
            --brand-soft: #e9f1ff;
            --accent: #14b8a6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, #eef6ff 0%, var(--bg) 100%);
            color: var(--text);
        }
        .wrap {
            max-width: 1440px;
            margin: 0 auto;
            padding: 32px 24px 40px;
        }
        .hero, .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(24, 66, 148, 0.08);
        }
        .hero {
            padding: 28px;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .eyebrow, .section-label {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--brand);
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 12px;
            font-size: clamp(34px, 4vw, 48px);
            line-height: 1.02;
        }
        .hero p, .muted {
            color: var(--muted);
            line-height: 1.7;
            margin: 0;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .stat {
            background: linear-gradient(180deg, #0e63ec 0%, #1585e7 100%);
            color: white;
            border-radius: 18px;
            padding: 18px;
        }
        .stat strong {
            display: block;
            font-size: 32px;
            line-height: 1;
            margin-top: 10px;
        }
        .grid {
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr);
            gap: 24px;
        }
        .card {
            padding: 22px;
        }
        .filters form {
            display: grid;
            gap: 14px;
        }
        label {
            display: grid;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
        }
        input, select {
            width: 100%;
            min-height: 46px;
            border: 1px solid #cddbf7;
            border-radius: 14px;
            padding: 0 14px;
            font-size: 14px;
            color: var(--text);
            background: white;
        }
        .actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 6px;
        }
        .button, button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            border-radius: 14px;
            border: 1px solid transparent;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            padding: 0 16px;
            cursor: pointer;
        }
        .button-primary, button {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
        }
        .button-soft {
            background: var(--brand-soft);
            color: var(--brand);
            border-color: #cfe0ff;
        }
        .button-ghost {
            background: white;
            color: var(--text);
            border-color: #d4def1;
        }
        .pill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .pill {
            border-radius: 999px;
            padding: 7px 12px;
            background: #eef5ff;
            color: #2755b0;
            font-size: 13px;
            font-weight: 700;
        }
        .table-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-end;
            margin-bottom: 14px;
        }
        .table-wrap {
            overflow: auto;
            border: 1px solid #dce7fb;
            border-radius: 18px;
        }
        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #edf2fb;
            vertical-align: top;
            font-size: 14px;
        }
        th {
            background: #f8fbff;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #5771ab;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .empty {
            padding: 36px 20px;
            text-align: center;
            color: var(--muted);
        }
        @media print {
            body { background: white; }
            .wrap { max-width: none; padding: 0; }
            .hero, .filters, .no-print {
                box-shadow: none;
                border: none;
            }
            .no-print {
                display: none !important;
            }
            .grid {
                display: block;
            }
            .card {
                padding: 0;
                margin-bottom: 16px;
            }
            .table-wrap {
                overflow: visible;
                border: none;
            }
            table {
                min-width: 0;
            }
        }
        @media (max-width: 1080px) {
            .hero,
            .grid {
                grid-template-columns: 1fr;
            }
            .actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="hero">
            <div>
                <span class="eyebrow">Laporan Master Data</span>
                <h1>{{ $report['config']['label'] }}</h1>
                <p>Gunakan filter untuk menyiapkan preview, cetak, file Excel, atau PDF dari data master yang sedang dibutuhkan.</p>
            </div>
            <div class="stats">
                <div class="stat">
                    Total data
                    <strong>{{ number_format($report['total']) }}</strong>
                </div>
                <div class="stat" style="background: linear-gradient(180deg, #0f766e 0%, #14b8a6 100%);">
                    Filter aktif
                    <strong>{{ count($report['active_filters']) }}</strong>
                </div>
            </div>
        </section>

        <div class="grid">
            <aside class="card filters no-print">
                <span class="section-label">Filter Laporan</span>
                <form method="get" action="{{ route('admin.reports.show', $type) }}">
                    @foreach ($report['config']['filters'] as $key => $definition)
                        <label>
                            <span>{{ $definition['label'] }}</span>
                            @if (($definition['type'] ?? 'text') === 'select')
                                <select name="{{ $key }}">
                                    <option value="">Semua</option>
                                    @foreach (($definition['options'] ?? []) as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected(($report['filters'][$key] ?? null) !== null && (string) $report['filters'][$key] === (string) $optionValue)>
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input
                                    type="text"
                                    name="{{ $key }}"
                                    value="{{ $report['filters'][$key] ?? '' }}"
                                    @if (isset($definition['maxlength'])) maxlength="{{ $definition['maxlength'] }}" @endif
                                >
                            @endif
                        </label>
                    @endforeach

                    <div class="actions">
                        <button type="submit">Terapkan Filter</button>
                        <a class="button button-soft" href="{{ route('admin.reports.show', $type) }}">Reset</a>
                    </div>

                    <div class="actions">
                        <button type="button" class="button button-ghost" onclick="window.print()">Preview / Print</button>
                        <a class="button button-ghost" href="{{ route('admin.reports.excel', array_merge(['type' => $type], request()->query())) }}">Download Excel</a>
                    </div>

                    <div class="actions">
                        <a class="button button-ghost" href="{{ route('admin.reports.pdf', array_merge(['type' => $type], request()->query())) }}">Download PDF</a>
                        <a class="button button-soft" href="{{ url()->previous() }}">Kembali ke Master Data</a>
                    </div>
                </form>

                @if ($report['active_filters'] !== [])
                    <div style="margin-top: 18px;">
                        <strong>Filter aktif</strong>
                        <div class="pill-list">
                            @foreach ($report['active_filters'] as $label => $value)
                                <span class="pill">{{ $label }}: {{ $value }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>

            <section class="card">
                <div class="table-head">
                    <div>
                        <span class="section-label">{{ $report['config']['resource_label'] }}</span>
                        <div class="muted">Preview data yang sudah mengikuti filter saat ini.</div>
                    </div>
                    <div class="muted">Dicetak: {{ now()->format('d M Y H:i') }}</div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                @foreach ($report['columns'] as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['rows'] as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td class="empty" colspan="{{ count($report['columns']) }}">Belum ada data yang cocok dengan filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
