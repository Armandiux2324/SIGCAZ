<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

// PU19 - Validar la exportación del reporte general de participantes registrados.
// Requerimiento relacionado: RFA-013
// Diseño relacionado: D18

function createReportParticipant(string $firstName, string $email): void
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
            'first_name' => $firstName,
            'last_name' => 'PU19',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

function readXlsxFromResponse($response): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
    $tmpFile = tempnam(sys_get_temp_dir(), 'pu_report_').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $spreadsheet = IOFactory::load($tmpFile);
    unlink($tmpFile);

    return $spreadsheet;
}

test('PU19 - exporta el reporte de participantes en formato xlsx con los datos correctos', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipant('Carlos', 'carlos.pu19@example.com');
    createReportParticipant('Ana', 'ana.pu19@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/participants');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('reporte_participantes_');

    $sheet = readXlsxFromResponse($response)->getActiveSheet();
    expect($sheet->getCell('A1')->getValue())->toBe('Folio');
    expect($sheet->getCell('B1')->getValue())->toBe('Nombre');

    $emails = [];
    for ($row = 2; $row <= 3; $row++) {
        $emails[] = $sheet->getCell("E{$row}")->getValue();
    }
    expect($emails)->toContain('carlos.pu19@example.com', 'ana.pu19@example.com');
});

test('PU19 - filtra el reporte de participantes por año cuando se envía el parámetro year', function () {
    Storage::fake('public');
    Mail::fake();
    createReportParticipant('Carlos', 'carlos.pu19b@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->get('/api/v1/reports/participants?year='.(now()->year + 5));

    $response->assertOk();

    $sheet = readXlsxFromResponse($response)->getActiveSheet();
    // Solo debe quedar la fila de encabezado, sin filas de datos, porque
    // ningún participante fue creado en un año futuro.
    expect($sheet->getHighestRow())->toBe(1);
});

test('PU19 - rechaza la exportación de reportes sin autenticación', function () {
    $response = $this->getJson('/api/v1/reports/participants');

    $response->assertUnauthorized();
});