<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU23 - Validar la exportación del reporte de participantes agrupados por municipio.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantWithMunicipality(string $state, string $municipality, string $email): void
{
    test()->postJson('/api/v1/registers', [
        'origin_type' => 'state',
        'state' => $state,
        'municipality' => $municipality,
        'group' => 'Cuadrilla 2',
        'attendance_type' => 'alone',
        'participant_count' => 1,
        'accommodation_type' => 'airbnb',
        'stay_days' => 4,
        'transport_method' => 'car',
        'folio_delivery_method' => 'phone',
        'participants' => [[
            'first_name' => 'PU23',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU23 - exporta el reporte de participantes agrupados por estado y municipio', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipantWithMunicipality('Zacatecas', 'Guadalupe', 'g1.pu23@example.com');
    createReportParticipantWithMunicipality('Zacatecas', 'Guadalupe', 'g2.pu23@example.com');
    createReportParticipantWithMunicipality('Zacatecas', 'Fresnillo', 'f1.pu23@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/municipality');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_municipios_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu23_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    expect($sheet->getCell('A1')->getValue())->toBe('Estado');
    expect($sheet->getCell('B1')->getValue())->toBe('Municipio');
    expect($sheet->getCell('C1')->getValue())->toBe('Total de participantes');

    $totals = [];
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $totals[$sheet->getCell("B{$row}")->getValue()] = $sheet->getCell("C{$row}")->getValue();
    }

    expect($totals['Guadalupe'])->toBe(2);
    expect($totals['Fresnillo'])->toBe(1);
});

test('PU23 - rechaza la exportación por municipio sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/municipality');

    $response->assertUnauthorized();
});