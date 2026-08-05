<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU16 - Validar la búsqueda y filtrado de registros por folio, nombre, correo o teléfono.
// Requerimiento relacionado: RFA-010
// Diseño relacionado: D15

function createRegisterForPU16(string $firstName, string $email, string $phone): void
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
            'last_name' => 'PU16',
            'phone' => $phone,
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU16 - encuentra un registro buscando por nombre del participante', function () {
    Storage::fake('public');
    Mail::fake();
    createRegisterForPU16('Carlos', 'carlos.pu16@example.com', '3311110001');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/registers/search-filter?q=Carlos');

    $response->assertOk()->assertJson(['message' => 'Registro encontrado.'])->assertJsonPath('data.participants.0.email', 'carlos.pu16@example.com');
});

test('PU16 - encuentra un registro buscando por correo o teléfono', function () {
    Storage::fake('public');
    Mail::fake();
    createRegisterForPU16('Ana', 'ana.pu16@example.com', '3311110002');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $this->getJson('/api/v1/registers/search-filter?q=ana.pu16@example.com')->assertOk()->assertJsonPath('data.participants.0.first_name', 'Ana');

    $this->getJson('/api/v1/registers/search-filter?q=3311110002')->assertOk()->assertJsonPath('data.participants.0.first_name', 'Ana');
});

test('PU16 - responde 404 cuando la búsqueda no coincide con ningún registro', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/registers/search-filter?q=noexiste');

    $response->assertNotFound()->assertJson(['message' => 'No se encontró ningún registro con esa búsqueda.', 'data' => null]);
});

test('PU16 - rechaza la búsqueda cuando el parámetro q está vacío', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/registers/search-filter?q=');

    $response->assertUnprocessable()->assertJson(['message' => 'El parámetro de búsqueda es requerido.']);
});

test('PU16 - rechaza la búsqueda sin autenticación', function () {
    $response = $this->getJson('/api/v1/registers/search-filter?q=Carlos');

    $response->assertUnauthorized();
});