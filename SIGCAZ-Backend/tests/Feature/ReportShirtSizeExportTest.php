<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU21 - Validar la exportación del reporte de participantes agrupados por talla.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipantWithSize(string $size, string $email): void
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
            'first_name' => 'PU21',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => $size,
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU21 - exporta el reporte de participantes agrupados por talla, ordenado de menor a mayor', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipantWithSize('L', 'l1.pu21@example.com');
    createReportParticipantWithSize('S', 's1.pu21@example.com');
    createReportParticipantWithSize('L', 'l2.pu21@example.com');
    createReportParticipantWithSize('M', 'm1.pu21@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/shirt-size');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_tallas_');

    $tmpFile = tempnam(sys_get_temp_dir(), 'pu21_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    unlink($tmpFile);

    $rows = [];
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $rows[$sheet->getCell("A{$row}")->getValue()] = $sheet->getCell("B{$row}")->getValue();
    }

    // Orden esperado S, M, L (según el arreglo $order del controlador)
    expect(array_keys($rows))->toBe(['S', 'M', 'L']);
    expect($rows['L'])->toBe(2);
    expect($rows['S'])->toBe(1);
    expect($rows['M'])->toBe(1);
});

test('PU21 - rechaza la exportación por talla sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/shirt-size');

    $response->assertUnauthorized();
});