<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU25 - Validar la exportación del reporte de participantes agrupados por tipo de hospedaje.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantWithAccommodation(string $accommodationType, string $email): void
{
    test()->postJson('/api/v1/registers', [
        'origin_type' => 'national',
        'state' => 'Jalisco',
        'municipality' => 'Guadalajara',
        'group' => 'Cuadrilla 2',
        'attendance_type' => 'alone',
        'participant_count' => 1,
        'accommodation_type' => $accommodationType,
        'stay_days' => 4,
        'transport_method' => 'car',
        'folio_delivery_method' => 'phone',
        'participants' => [[
            'first_name' => 'PU25',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU25 - exporta el reporte de participantes agrupados por tipo de hospedaje con etiquetas legibles', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipantWithAccommodation('hotel', 'h1.pu25@example.com');
    createReportParticipantWithAccommodation('hotel', 'h2.pu25@example.com');
    createReportParticipantWithAccommodation('own_home', 'o1.pu25@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/accommodation');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_hospedaje_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu25_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    expect($sheet->getCell('A1')->getValue())->toBe('Tipo de hospedaje');
    expect($sheet->getCell('B1')->getValue())->toBe('Total de participantes');

    $totals = [];
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $totals[$sheet->getCell("A{$row}")->getValue()] = $sheet->getCell("B{$row}")->getValue();
    }

    // Las etiquetas deben venir traducidas al español, no el valor crudo del enum
    expect($totals)->toHaveKey('Hotel');
    expect($totals)->toHaveKey('Casa propia');
    expect($totals['Hotel'])->toBe(2);
    expect($totals['Casa propia'])->toBe(1);
});

test('PU25 - rechaza la exportación por tipo de hospedaje sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/accommodation');

    $response->assertUnauthorized();
});