<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $typeLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }

        .header { border-bottom: 3px solid #f97316; padding-bottom: 12px; margin-bottom: 16px; }
        .header-table { width: 100%; }
        .logo-cell { width: 70px; vertical-align: middle; }
        .logo-img { width: 55px; height: 55px; border-radius: 6px; object-fit: contain; }
        .logo-placeholder { width: 55px; height: 55px; background: #e2e8f0; border-radius: 6px; display: inline-block; }
        .info-cell { vertical-align: middle; padding-left: 10px; }
        .school-name { font-size: 15px; font-weight: bold; color: #0f172a; }
        .report-type { font-size: 11px; color: #475569; margin-top: 3px; }
        .period { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .data-table th {
            background: #f8fafc; color: #64748b; font-size: 9px;
            text-transform: uppercase; letter-spacing: 0.04em;
            padding: 7px 8px; text-align: left; border-bottom: 2px solid #e2e8f0;
        }
        .data-table td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9.5px; }
        .data-table tr:nth-child(even) td { background: #f8fafc; }

        .footer { border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .footer-table { width: 100%; }
        .footer-left { font-size: 9px; color: #94a3b8; }
        .footer-right { text-align: right; font-size: 9px; color: #94a3b8; }
        .page-number:after { content: counter(page); }

        @page { margin: 18mm 14mm; }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo" />
                    @else
                        <span class="logo-placeholder"></span>
                    @endif
                </td>
                <td class="info-cell">
                    <div class="school-name">{{ $school->name }}</div>
                    <div class="report-type">Reporte de Asistencia — {{ $typeLabel }}</div>
                    <div class="period">
                        {{ \Carbon\Carbon::parse($dateFrom)->isoFormat('D MMM YYYY') }}
                        —
                        {{ \Carbon\Carbon::parse($dateTo)->isoFormat('D MMM YYYY') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $row)
                <tr>
                    @foreach(array_values($row) as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}" style="text-align:center; padding:20px; color:#94a3b8;">
                        No hay datos para mostrar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Generado el {{ now()->isoFormat('D MMM YYYY, h:mm A') }} · {{ $school->name }}
                </td>
                <td class="footer-right">
                    Página <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
