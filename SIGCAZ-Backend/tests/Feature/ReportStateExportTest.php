<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU22 - Validar la exportación del reporte de participantes agrupados por estado de origen.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantWithState(string $state, string $email): void
{
    test()->postJson('/api/v1/registers', [
        'origin_type' => 'national',
        'state' => $state,
        'municipality' => 'Guadalajara',
        'group' => 'Cuadrilla 2',
        'attendance_type' => 'alone',
        'participant_count' => 1,
        'accommodation_type' => 'airbnb',
        'stay_days' => 4,
        'transport_method' => 'car',
        'folio_delivery_method' => 'phone',
        'participants' => [[
            'first_name' => 'PU22',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU22 - exporta el reporte de participantes agrupados por estado de origen, ordenado de mayor a menor', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipantWithState('Zacatecas', 'z1.pu22@example.com');
    createReportParticipantWithState('Zacatecas', 'z2.pu22@example.com');
    createReportParticipantWithState('Jalisco', 'j1.pu22@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/state');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_estados_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu22_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    expect($sheet->getCell('A1')->getValue())->toBe('Estado');
    expect($sheet->getCell('B1')->getValue())->toBe('Total de participantes');

    $totals = [];
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $totals[$sheet->getCell("A{$row}")->getValue()] = $sheet->getCell("B{$row}")->getValue();
    }

    // Orden esperado: Zacatecas primero por tener mayor total
    expect(array_key_first($totals))->toBe('Zacatecas');
    expect($totals['Zacatecas'])->toBe(2);
    expect($totals['Jalisco'])->toBe(1);
});

test('PU22 - rechaza la exportación por estado sin autenticación', function () {
    $response = $this->get('/api/v1/reports/state');

    $response->assertUnauthorized();
});