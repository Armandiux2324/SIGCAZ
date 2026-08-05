<?php

use App\Models\Register;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU30 - Validar la obtención de datos para la gráfica de registros por año.
// Requerimiento relacionado: RFA-015
// Diseño relacionado: D21

function createRegisterInYear(int $year): void
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
            'first_name' => 'PU30',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => 'pu30.'.$year.'.'.random_int(1000, 9999).'@example.com',
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();

    Register::latest()->first()->forceFill(['created_at' => "{$year}-06-15 10:00:00"])->save();
}

test('PU30 - agrupa correctamente el total de registros por año, ordenados ascendentemente', function () {
    Storage::fake('public');
    Mail::fake();

    createRegisterInYear(2024);
    createRegisterInYear(2025);
    createRegisterInYear(2025);
    createRegisterInYear(2026);

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/stats/by-year');

    $response->assertOk();

    $labels = $response->json('data.labels');
    $values = $response->json('data.values');
    $result = array_combine($labels, $values);

    expect($labels)->toBe(['2024', '2025', '2026']);
    expect($result['2024'])->toBe(1);
    expect($result['2025'])->toBe(2);
    expect($result['2026'])->toBe(1);
});

test('PU30 - devuelve listas vacías cuando no hay registros', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/stats/by-year');

    $response->assertOk()->assertJsonPath('data.labels', [])->assertJsonPath('data.values', []);
});

test('PU30 - rechaza la consulta de registros por año sin autenticación', function () {
    $response = $this->getJson('/api/v1/stats/by-year');

    $response->assertUnauthorized();
});