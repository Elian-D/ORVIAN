<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: letter portrait;
            margin: 0.5cm;
        }
        body {
            font-family: 'Helvetica', sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td {
            /* Reducimos el padding vertical para pegar más las filas */
            padding: 5px; 
            vertical-align: top;
            width: 33.33%;
        }
        .carnet {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            /* Padding interno más ajustado */
            padding: 10px 5px; 
            text-align: center;
            /* Eliminamos el height: 6.5cm para que no sobre espacio */
            min-height: 4cm; 
        }
        .school-name {
            font-size: 7px;
            color: #64748b;
            margin-bottom: 5px;
            height: 10px;
            text-transform: uppercase;
        }
        .qr-img {
            /* QR un poco más pequeño para ganar espacio vertical */
            width: 110px;
            height: 110px;
        }
        .student-name {
            font-size: 10px;
            font-weight: bold;
            margin-top: 5px;
            display: block;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .student-info {
            font-size: 8px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <table>
        @foreach($students->chunk(3) as $row)
            <tr>
                @foreach($row as $student)
                    <td>
                        <div class="carnet">
                            <div class="school-name">{{ $school->name }}</div>
                            
                            @php
                                $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                                    ->size(120)
                                    ->margin(0)
                                    ->generate($student->qr_code));
                            @endphp
                            <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr-img">

                            <span class="student-name">{{ $student->full_name }}</span>
                            <div class="student-info">
                                {{ $student->section->full_label }}
                                <br>
                            </div>
                        </div>
                    </td>
                @endforeach
                
                @for($i = 0; $i < (3 - count($row)); $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>