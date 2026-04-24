<?php

namespace App\Http\Controllers\App\Students;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPrintController extends Controller
{
    public function printQrSheet(Request $request)
    {
        // Validar que se recibieron IDs
        $studentIds = explode(',', $request->input('students', ''));
        
        if (empty($studentIds)) {
            abort(400, 'No se especificaron estudiantes para imprimir.');
        }

        // Cargar estudiantes con relaciones necesarias
        $students = Student::with(['section.grade', 'section.shift', 'school'])
            ->whereIn('id', $studentIds)
            ->where('school_id', Auth::user()->school_id) // Seguridad
            ->get();

        if ($students->isEmpty()) {
            abort(404, 'No se encontraron estudiantes válidos.');
        }

        // Generar PDF
        $pdf = Pdf::loadView('printables.qr-sheet', [
            'students' => $students,
            'school' => Auth::user()->school,
        ]);

        // Configurar opciones de DomPDF
        $pdf->setPaper('letter', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true); // Para cargar imágenes desde Storage

        // Retornar PDF para descarga
        return $pdf->stream('carnets_' . now()->format('Y-m-d_His') . '.pdf');
    }
}