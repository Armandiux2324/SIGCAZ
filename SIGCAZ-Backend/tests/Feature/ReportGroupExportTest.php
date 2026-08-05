<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU24 - Validar la exportación del reporte de participantes agrupados por cuadrilla.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantWithGroup(string $group, string $email): void
{
    test()->postJson('/api/v1/registers', [
        'origin_type' => 'national',
        'state' => 'Jalisco',
        'municipality' => 'Guadalajara',
        'group' => $group,
        'attendance_type' => 'alone',
        'participant_count' => 1,
        'accommodation_type' => 'airbnb',
        'stay_days' => 4,
        'transport_method' => 'car',
        'folio_delivery_method' => 'phone',
        'participants' => [[
            'first_name' => 'PU24',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU24 - exporta el reporte de participantes agrupados por cuadrilla, ordenado de mayor a menor', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipantWithGroup('Cuadrilla 1', 'c1a.pu24@example.com');
    createReportParticipantWithGroup('Cuadrilla 1', 'c1b.pu24@example.com');
    createReportParticipantWithGroup('Cuadrilla 3', 'c3a.pu24@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/group');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_cuadrillas_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu24_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    expect($sheet->getCell('A1')->getValue())->toBe('Cuadrilla');
    expect($sheet->getCell('B1')->getValue())->toBe('Total de participantes');

    $totals = [];
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $totals[$sheet->getCell("A{$row}")->getValue()] = $sheet->getCell("B{$row}")->getValue();
    }

    expect(array_key_first($totals))->toBe('Cuadrilla 1');
    expect($totals['Cuadrilla 1'])->toBe(2);
    expect($totals['Cuadrilla 3'])->toBe(1);
});

test('PU24 - rechaza la exportación por cuadrilla sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/group');

    $response->assertUnauthorized();
});