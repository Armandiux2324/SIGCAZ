<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU26 - Validar la exportación del reporte de participantes agrupados por participaciones previas.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantWithParticipation(bool $isFirstTime, ?int $count, string $email): void
{
    $participant = [
        'first_name' => 'PU26',
        'last_name' => 'Test',
        'phone' => '33'.random_int(10000000, 99999999),
        'email' => $email,
        'gender' => 'male',
        'shirt_size' => 'M',
        'is_first_time' => $isFirstTime,
    ];

    if (! $isFirstTime) {
        $participant['participation_count'] = $count;
    }

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
        'participants' => [$participant],
    ])->assertCreated();
}

test('PU26 - exporta el reporte de participantes agrupados por participaciones previas', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipantWithParticipation(true, null, 'nuevo1.pu26@example.com');
    createReportParticipantWithParticipation(true, null, 'nuevo2.pu26@example.com');
    createReportParticipantWithParticipation(false, 3, 'repite1.pu26@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/participation-count');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_participaciones_previas_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu26_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    expect($sheet->getCell('A1')->getValue())->toBe('Primera vez');
    expect($sheet->getCell('C1')->getValue())->toBe('Total');

    // Primer bloque: quienes participan por primera vez ("Sí"), agrupados juntos
    expect($sheet->getCell('A2')->getValue())->toBe('Sí');
    expect($sheet->getCell('C2')->getValue())->toBe(2);

    // Segundo bloque: quienes ya habían participado, con su conteo previo
    expect($sheet->getCell('A3')->getValue())->toBe('No');
    expect($sheet->getCell('B3')->getValue())->toBe(3);
    expect($sheet->getCell('C3')->getValue())->toBe(1);
});

test('PU26 - rechaza la exportación por participaciones previas sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/participation-count');

    $response->assertUnauthorized();
});