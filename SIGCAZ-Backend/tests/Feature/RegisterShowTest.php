<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

// PU34 - Validar la consulta del detalle de un registro específico, incluyendo el código QR de sus participantes.
// Requerimiento relacionado: RFA-020
// Diseño relacionado: D25

function createRegisterForShowTest(string $email): int
{
    Mail::fake();

    $response = test()->postJson('/api/v1/registers', [
        'origin_type' => 'state',
        'state' => 'Zacatecas',
        'municipality' => 'Guadalupe',
        'group' => 'Cuadrilla 1',
        'attendance_type' => 'alone',
        'participant_count' => 1,
        'accommodation_type' => 'hotel',
        'stay_days' => 2,
        'transport_method' => 'car',
        'folio_delivery_method' => 'email',
        'participants' => [[
            'first_name' => 'Test',
            'last_name' => 'PU34',
            'phone' => '49'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'female',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ]);

    return $response->json('data.id');
}

test('PU34 - obtiene el detalle de un registro específico con sus participantes y su código QR', function () {
    $registerId = createRegisterForShowTest('pu34-1@example.com');
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson("/api/v1/registers/{$registerId}");

    $response->assertOk()
        ->assertJsonPath('data.id', $registerId)
        ->assertJsonCount(1, 'data.participants')
        ->assertJsonPath('data.participants.0.email', 'pu34-1@example.com');

    expect($response->json('data.participants.0.qr_path'))->not->toBeNull();
});

test('PU34 - responde 404 al consultar un registro que no existe', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/registers/999999');

    $response->assertStatus(404);
});

test('PU34 - rechaza la consulta sin autenticación', function () {
    $registerId = createRegisterForShowTest('pu34-2@example.com');

    $response = $this->getJson("/api/v1/registers/{$registerId}");

    $response->assertStatus(401);
});
