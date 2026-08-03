<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU15 - Validar la consulta del listado de registros.
// Requerimiento relacionado: RFA-009
// Diseño relacionado: D14

function createSimpleRegister(string $email): void
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
            'first_name' => 'Test',
            'last_name' => 'PU15',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU15 - un usuario autenticado obtiene el listado paginado de registros con sus participantes', function () {
    Storage::fake('public');
    Mail::fake();

    createSimpleRegister('pu15.uno@example.com');
    createSimpleRegister('pu15.dos@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/registers');

    $response->assertOk()
        ->assertJson(['message' => 'Listado de registros obtenido correctamente.'])->assertJsonCount(2, 'data.data')
        ->assertJsonPath('data.data.0.participants.0.first_name', 'Test');
});

test('PU15 - respeta el parámetro per_page', function () {
    Storage::fake('public');
    Mail::fake();

    createSimpleRegister('pu15.a@example.com');
    createSimpleRegister('pu15.b@example.com');
    createSimpleRegister('pu15.c@example.com');

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/registers?per_page=1');

    $response->assertOk()->assertJsonCount(1, 'data.data');
});

test('PU15 - responde 404 cuando no hay registros', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/registers');

    $response->assertNotFound()->assertJson(['message' => 'No se encontraron registros.', 'data' => []]);
});

test('PU15 - rechaza la consulta sin autenticación', function () {
    $response = $this->getJson('/api/v1/registers');

    $response->assertUnauthorized();
});