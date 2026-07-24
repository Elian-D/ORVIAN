<?php

use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\School;
use App\Models\Tenant\Student;
use App\Models\User;
use App\Services\Attendance\PlantelAttendanceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Mide el costo del lado Laravel (BD + transacción + orquestación) del flujo
 * facial, aislado del microservicio Python real (se mockea con Http::fake()).
 * Sirve para saber cuánto suma Laravel al tiempo total por estudiante, y para
 * proyectar el total de la jornada sin tener que correr 800 fotos reales.
 */
function fakeVerifyResponse(int $matchedStudentId): array
{
    return [
        'success' => true,
        'matched' => true,
        'student_id' => $matchedStudentId,
        'student_name' => 'Estudiante Test',
        'confidence' => 92.5,
        'distance' => 0.35,
        'faces_detected' => 1,
    ];
}

beforeEach(function () {
    Cache::flush();

    $this->school = School::factory()->create();

    $this->owner = User::factory()->create(['school_id' => $this->school->id]);
    Auth::login($this->owner);

    $this->shift = SchoolShift::create([
        'school_id' => $this->school->id,
        'type' => SchoolShift::TYPE_MORNING,
        'start_time' => '07:00:00',
        'end_time' => '12:00:00',
        'late_threshold_minutes' => 15,
    ]);

    // Matrícula completa del plantel: 800 estudiantes con encoding, como en el
    // escenario real que estamos midiendo.
    $this->students = Student::factory()
        ->count(800)
        ->create([
            'school_id' => $this->school->id,
            'is_active' => true,
            'face_encoding' => json_encode(array_fill(0, 128, 0.1)),
        ]);

    $this->session = DailyAttendanceSession::create([
        'school_id' => $this->school->id,
        'school_shift_id' => $this->shift->id,
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $this->owner->id,
        'total_expected' => 800,
    ]);
});

test('facial roster cache loads all 800 encodings only once per window', function () {
    Http::fake([
        '*/api/v1/verify/*' => Http::sequence()
            ->push(fakeVerifyResponse($this->students[0]->id))
            ->push(fakeVerifyResponse($this->students[1]->id)),
    ]);

    $service = app(PlantelAttendanceService::class);

    $service->recordByFacial(
        schoolId: $this->school->id,
        sessionId: $this->session->id,
        photo: UploadedFile::fake()->image('rostro.jpg'),
    );

    // Segunda llamada (otro estudiante) no debe volver a golpear la BD para
    // reconstruir los 800 encodings: debe salir de la caché de 5 minutos.
    $service->recordByFacial(
        schoolId: $this->school->id,
        sessionId: $this->session->id,
        photo: UploadedFile::fake()->image('rostro2.jpg'),
    );

    Http::assertSentCount(2);
    expect(Cache::has("facial_encodings_school_{$this->school->id}"))->toBeTrue();
});

test('measures average Laravel-side overhead per facial scan and projects the full morning window', function () {
    $sampleSize = 100; // muestra representativa; se extrapola al resto abajo
    $sample = $this->students->take($sampleSize);

    Http::fake(function ($request) {
        // Cada llamada trae el payload completo de known_encodings (los 800),
        // igual que en producción — así medimos también el costo real de
        // decodificar/enviar ese payload en cada request, no solo el mock.
        $payload = $request->data();
        $known = json_decode($payload['known_encodings'] ?? '[]', true);
        expect(count($known))->toBe(800);

        return Http::response([
            'success' => true,
            'matched' => true,
            'student_id' => $known[array_rand($known)]['id'],
            'student_name' => 'Estudiante Test',
            'confidence' => 91.0,
            'distance' => 0.4,
            'faces_detected' => 1,
        ]);
    });

    $service = app(PlantelAttendanceService::class);
    $timings = [];

    foreach ($sample as $i => $student) {
        $start = microtime(true);

        $service->recordByFacial(
            schoolId: $this->school->id,
            sessionId: $this->session->id,
            photo: UploadedFile::fake()->image("rostro-{$i}.jpg"),
        );

        $timings[] = microtime(true) - $start;
    }

    sort($timings);
    $avg = array_sum($timings) / count($timings);
    $p95 = $timings[(int) floor(count($timings) * 0.95) - 1];

    // Overhead de Laravel puro (sin el cómputo real de Python ni la latencia
    // de red real) para 760 estudiantes (10% de 800 no pasa por el kiosk).
    $projectedTotalSeconds = $avg * 760;

    fwrite(STDOUT, sprintf(
        "\n[Laravel overhead] avg=%.1fms p95=%.1fms | proyectado 760 registros = %.1fs (%.1f min)\n",
        $avg * 1000,
        $p95 * 1000,
        $projectedTotalSeconds,
        $projectedTotalSeconds / 60,
    ));

    // El lado Laravel (BD + transacción + caché) no debería, por sí solo,
    // consumir más de ~150ms por estudiante; el resto del presupuesto de los
    // 4-6s por escaneo facial (ver docs/features) es red + Python + UX física.
    expect($avg)->toBeLessThan(0.15);
});
