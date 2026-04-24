<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte de Asistencia de Plantel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            background: #ffffff;
        }

        /* ── Header ── */
        .header {
            border-bottom: 3px solid #f97316;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-inner {
            width: 100%;
        }
        .header-logo-cell {
            width: 70px;
            vertical-align: middle;
        }
        .header-logo {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: contain;
        }
        .header-logo-placeholder {
            width: 60px;
            height: 60px;
            background: #e2e8f0;
            border-radius: 8px;
            display: inline-block;
        }
        .header-info-cell {
            vertical-align: middle;
            padding-left: 12px;
        }
        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        .report-title {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        .report-period {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .header-stats-cell {
            vertical-align: middle;
            text-align: right;
        }

        /* ── Summary cards ── */
        .summary-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        .summary-card {
            text-align: center;
            padding: 10px 8px;
            border-radius: 8px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            display: block;
        }
        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
            margin-top: 2px;
        }
        .card-rate    { background: #fff7ed; color: #ea580c; }
        .card-present { background: #f0fdf4; color: #16a34a; }
        .card-late    { background: #fffbeb; color: #d97706; }
        .card-absent  { background: #fef2f2; color: #dc2626; }
        .card-excused { background: #eff6ff; color: #2563eb; }

        /* ── Data table ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 7px 8px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .badge-present { background: #dcfce7; color: #16a34a; }
        .badge-late    { background: #fef9c3; color: #a16207; }
        .badge-absent  { background: #fee2e2; color: #dc2626; }
        .badge-excused { background: #dbeafe; color: #1d4ed8; }
        .badge-method  { background: #f1f5f9; color: #475569; }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 8px;
        }
        .footer-table {
            width: 100%;
        }
        .footer-left {
            font-size: 9px;
            color: #94a3b8;
            vertical-align: bottom;
        }
        .footer-right {
            text-align: right;
            font-size: 9px;
            color: #94a3b8;
            vertical-align: bottom;
        }
        .page-number:after {
            content: counter(page);
        }

        @page {
            margin: 18mm 14mm;
        }
    </style>
</head>
<body>

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="header">
        <table class="header-inner">
            <tr>
                <td class="header-logo-cell">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="header-logo" alt="Logo" />
                    @else
                        <span class="header-logo-placeholder"></span>
                    @endif
                </td>
                <td class="header-info-cell">
                    <div class="school-name">{{ $school->name }}</div>
                    <div class="report-title">Reporte de Asistencia de Plantel</div>
                    <div class="report-period">
                        Período: {{ \Carbon\Carbon::parse($dateFrom)->isoFormat('D MMM YYYY') }}
                        —
                        {{ \Carbon\Carbon::parse($dateTo)->isoFormat('D MMM YYYY') }}
                    </div>
                </td>
                <td class="header-stats-cell" style="width: 120px;">
                    <div style="font-size: 22px; font-weight: bold; color: #f97316;">{{ $meta['rate'] }}%</div>
                    <div style="font-size: 9px; color: #94a3b8;">Tasa de asistencia</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Resumen ──────────────────────────────────────────────────────────── --}}
    <table class="summary-table">
        <tr>
            <td style="width: 20%; padding-right: 6px;">
                <div class="summary-card card-present">
                    <span class="summary-value">{{ $meta['present'] }}</span>
                    <span class="summary-label">Presentes</span>
                </div>
            </td>
            <td style="width: 20%; padding-right: 6px;">
                <div class="summary-card card-late">
                    <span class="summary-value">{{ $meta['late'] }}</span>
                    <span class="summary-label">Tardanzas</span>
                </div>
            </td>
            <td style="width: 20%; padding-right: 6px;">
                <div class="summary-card card-absent">
                    <span class="summary-value">{{ $meta['absent'] }}</span>
                    <span class="summary-label">Ausentes</span>
                </div>
            </td>
            <td style="width: 20%; padding-right: 6px;">
                <div class="summary-card card-excused">
                    <span class="summary-value">{{ $meta['excused'] }}</span>
                    <span class="summary-label">Justificados</span>
                </div>
            </td>
            <td style="width: 20%;">
                <div class="summary-card" style="background: #f8fafc; color: #475569;">
                    <span class="summary-value">{{ $meta['total'] }}</span>
                    <span class="summary-label">Total registros</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Tabla de registros ───────────────────────────────────────────────── --}}
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Estudiante</th>
                <th>Cédula</th>
                <th>Sección</th>
                <th>Tanda</th>
                <th>Hora</th>
                <th>Estado</th>
                <th>Método</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                @php
                    $badgeClass = match($record->status) {
                        'present' => 'badge-present',
                        'late'    => 'badge-late',
                        'absent'  => 'badge-absent',
                        'excused' => 'badge-excused',
                        default   => 'badge-method',
                    };
                    $grade   = $record->student->section?->grade?->name;
                    $section = $record->student->section?->label;
                @endphp
                <tr>
                    <td>{{ $record->date->isoFormat('D MMM YYYY') }}</td>
                    <td>{{ $record->student->full_name }}</td>
                    <td>{{ $record->student->rnc ?? '—' }}</td>
                    <td>{{ $grade && $section ? "{$grade}° - {$section}" : '—' }}</td>
                    <td>{{ $record->shift?->type ?? '—' }}</td>
                    <td>{{ $record->time?->format('h:i A') ?? '—' }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $record->status_label }}</span></td>
                    <td><span class="badge badge-method">{{ $record->method_label }}</span></td>
                    <td>{{ $record->registeredBy?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding: 20px; color: #94a3b8;">
                        No hay registros para el período seleccionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Footer ──────────────────────────────────────────────────────────── --}}
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
