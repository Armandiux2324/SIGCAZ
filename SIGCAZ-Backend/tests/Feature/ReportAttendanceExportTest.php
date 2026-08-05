<?php

use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU27 - Validar la exportación del reporte de asistencia e inasistencia.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantForAttendance(string $email): Participant
{
    test()->postJson('/api/v1/registers', [
        'origin_type' => 'national',
        'state' => 'Jalisco',
        'municipality' => 'Guadalajara',
        'group' => 'Cuadrilla 2',
        'attendance_type' => 'alone',
        'participant_count' => 1,
        'accommodation_type' => 'airbnb',
        'stay_days' => 4,
        'transport_method' => 'car',
        'folio_delivery_method' => 'phone',
        'participants' => [[
            'first_name' => 'PU27',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();

    return Participant::where('email', $email)->first();
}

test('PU27 - exporta el reporte de asistencia marcando quién asistió y quién no', function () {
    Storage::fake('public');
    Mail::fake();

    $asistio = createReportParticipantForAttendance('asistio.pu27@example.com');
    $asistio->update(['attended_at' => now()]);
    createReportParticipantForAttendance('noasistio.pu27@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/attendance');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_asistencia_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu27_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    $asistieron = [];
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $asistieron[$sheet->getCell("A{$row}")->getValue()] = $sheet->getCell("G{$row}")->getValue();
    }

    expect($asistieron[$asistio->folio])->toBe('Sí');
});

test('PU27 - rechaza la exportación de asistencia sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/attendance');

    $response->assertUnauthorized();
});