<?php

use App\Models\Register;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU17 - Validar la edición de un registro y de sus participantes (agregar, actualizar y eliminar).
// Requerimiento relacionado: RFA-011
// Diseño relacionado: D16

function createGroupRegisterForPU17(string $suffix = 'a'): Register
{
    test()->postJson('/api/v1/registers', [
        'origin_type' => 'state',
        'state' => 'Zacatecas',
        'municipality' => 'Guadalupe',
        'group' => 'Cuadrilla 1',
        'attendance_type' => 'accompanied',
        'participant_count' => 2,
        'accommodation_type' => 'hotel',
        'stay_days' => 2,
        'transport_method' => 'car',
        'folio_delivery_method' => 'email',
        'participants' => [
            [
                'first_name' => 'Armando',
                'last_name' => 'Candelas',
                'phone' => '49'.random_int(10000000, 99999999),
                'email' => "armando.pu17.{$suffix}@example.com",
                'gender' => 'male',
                'shirt_size' => 'L',
                'is_first_time' => true,
            ],
            [
                'first_name' => 'María',
                'last_name' => 'López',
                'phone' => '49'.random_int(10000000, 99999999),
                'email' => "maria.pu17.{$suffix}@example.com",
                'gender' => 'female',
                'shirt_size' => 'M',
                'is_first_time' => true,
            ],
        ],
    ])->assertCreated();

    return Register::with('participants')->latest()->first();
}

beforeEach(function () {
    Storage::fake('public');
    Mail::fake();
});

test('PU17 - actualiza los datos generales del registro', function () {
    $register = createGroupRegisterForPU17();
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson("/api/v1/registers/{$register->id}", [
        'accommodation_type' => 'own_home',
        'stay_days' => 5,
    ]);

    $response->assertOk()->assertJsonPath('data.accommodation_type', 'own_home')->assertJsonPath('data.stay_days', 5);
});

test('PU17 - agrega, actualiza y elimina participantes en la misma edición', function () {
    $register = createGroupRegisterForPU17();
    $armando = $register->participants->firstWhere('first_name', 'Armando');
    // María (el segundo participante) se omite a propósito -> debe eliminarse

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson("/api/v1/registers/{$register->id}", [
        'attendance_type' => 'accompanied',
        'participants' => [
            [
                'id' => $armando->id,
                'first_name' => 'Armando',
                'last_name' => 'Candelas Alvarado', // actualizado
                'phone' => $armando->phone,
                'email' => $armando->email,
                'gender' => 'male',
                'shirt_size' => 'L',
                'is_first_time' => true,
            ],
            [
                'first_name' => 'Pedro', // nuevo participante
                'last_name' => 'Ramírez',
                'phone' => '49'.random_int(10000000, 99999999),
                'email' => 'pedro.pu17.nuevo@example.com',
                'gender' => 'male',
                'shirt_size' => 'XL',
                'is_first_time' => true,
            ],
        ],
    ]);

    $response->assertOk()->assertJsonCount(2, 'data.participants')->assertJsonPath('data.participant_count', 2);

    $this->assertDatabaseHas('participants', ['id' => $armando->id, 'last_name' => 'Candelas Alvarado']);
    $this->assertDatabaseMissing('participants', ['email' => $register->participants->firstWhere('first_name', 'María')->email]);
    $this->assertDatabaseHas('participants', ['email' => 'pedro.pu17.nuevo@example.com']);
});

test('PU17 - rechaza la edición cuando el correo de un participante ya está registrado por otro', function () {
    $register = createGroupRegisterForPU17();
    $armando = $register->participants->firstWhere('first_name', 'Armando');
    $maria = $register->participants->firstWhere('first_name', 'María');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson("/api/v1/registers/{$register->id}", [
        'participants' => [[
            'id' => $armando->id,
            'first_name' => 'Armando',
            'last_name' => 'Candelas',
            'phone' => $armando->phone,
            'email' => $maria->email, // correo de María, ya registrado
            'gender' => 'male',
            'shirt_size' => 'L',
            'is_first_time' => true,
        ]],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['participants.0.email']);
});

test('PU17 - rechaza la edición cuando el id de participante no pertenece al registro', function () {
    $register = createGroupRegisterForPU17('a');
    $otroRegistro = createGroupRegisterForPU17('b');
    $participanteAjeno = $otroRegistro->participants->first();

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson("/api/v1/registers/{$register->id}", [
        'participants' => [[
            'id' => $participanteAjeno->id,
            'first_name' => 'Intruso',
            'last_name' => 'Ajeno',
            'phone' => '49'.random_int(10000000, 99999999),
            'email' => 'intruso.pu17@example.com',
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['participants.0.id']);
});

test('PU17 - responde 404 al editar un registro que no existe', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson('/api/v1/registers/999999', ['stay_days' => 3]);

    $response->assertNotFound();
});

test('PU17 - rechaza la edición sin autenticación', function () {
    $register = createGroupRegisterForPU17();

    $response = $this->putJson("/api/v1/registers/{$register->id}", ['stay_days' => 3]);

    $response->assertUnauthorized();
});