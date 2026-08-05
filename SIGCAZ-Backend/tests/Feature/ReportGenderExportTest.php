<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU20 - Validar la exportación del reporte de participantes agrupados por género.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantWithGender(string $gender, string $email): void
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
            'first_name' => 'PU20',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => $gender,
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU20 - exporta el reporte de participantes agrupados por género con los totales correctos', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipantWithGender('male', 'hombre1.pu20@example.com');
    createReportParticipantWithGender('male', 'hombre2.pu20@example.com');
    createReportParticipantWithGender('female', 'mujer1.pu20@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/gender');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_genero_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu20_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    $totals = [];
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $totals[$sheet->getCell("A{$row}")->getValue()] = $sheet->getCell("B{$row}")->getValue();
    }

    expect($totals['Masculino'])->toBe(2);
    expect($totals['Femenino'])->toBe(1);
});

test('PU20 - rechaza la exportación por género sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/gender');

    $response->assertUnauthorized();
});
