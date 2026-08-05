<?php

use App\Mail\RegisterCreatedMail;
use App\Models\Register;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// PU2 - Validar el registro de participación grupal con generación de formularios para cada integrante.
// Requerimiento relacionado: RFP-003
// Diseño relacionado: D1

test('PU2 - registra una participación grupal con varios integrantes', function () {
    Storage::fake('public');
    Mail::fake();

    $payload = [
        'origin_type' => 'state',
        'state' => 'Zacatecas',
        'municipality' => 'Guadalupe',
        'group' => 'Cuadrilla 1',
        'attendance_type' => 'accompanied',
        'participant_count' => 3,
        'accommodation_type' => 'hotel',
        'stay_days' => 2,
        'transport_method' => 'car',
        'folio_delivery_method' => 'email',
        'participants' => [
            [
                'first_name' => 'Armando',
                'last_name' => 'Candelas Alvarado',
                'phone' => '4921234567',
                'email' => 'armandocandelasalvarado@gmail.com',
                'gender' => 'male',
                'shirt_size' => 'L',
                'is_first_time' => false,
                'participation_count' => 3,
            ],
            [
                'first_name' => 'María',
                'last_name' => 'López Hernández',
                'phone' => '4922345678',
                'email' => 'light.art.drawings@gmail.com',
                'gender' => 'female',
                'shirt_size' => 'M',
                'is_first_time' => true,
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Ramírez Torres',
                'phone' => '4923456789',
                'email' => '482300209@alumnos.utzac.edu.mx',
                'gender' => 'male',
                'shirt_size' => 'XL',
                'is_first_time' => false,
                'participation_count' => 3,
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/registers', $payload);

    $response->assertCreated()->assertJsonPath('data.participant_count', 3)->assertJsonCount(3, 'data.participants');

    $this->assertDatabaseCount('registers', 1);
    $this->assertDatabaseCount('participants', 3);

    $register = Register::with('participants')->first();
    expect($register->participants)->toHaveCount(3);

    // Se encoló un correo por cada integrante del grupo
    Mail::assertQueued(RegisterCreatedMail::class, 3);
});

test('PU2 - rechaza el registro cuando el conteo de participantes no coincide con el arreglo enviado', function () {
    Storage::fake('public');
    Mail::fake();

    $payload = [
        'origin_type' => 'state',
        'state' => 'Zacatecas',
        'municipality' => 'Guadalupe',
        'group' => 'Cuadrilla 1',
        'attendance_type' => 'accompanied',
        'participant_count' => 3, // dice 3...
        'accommodation_type' => 'hotel',
        'stay_days' => 2,
        'transport_method' => 'car',
        'folio_delivery_method' => 'email',
        'participants' => [ // ...pero solo manda 2
            [
                'first_name' => 'Armando',
                'last_name' => 'Candelas Alvarado',
                'phone' => '4921234567',
                'email' => 'armandocandelasalvarado@gmail.com',
                'gender' => 'male',
                'shirt_size' => 'L',
                'is_first_time' => false,
                'participation_count' => 3,
            ],
            [
                'first_name' => 'María',
                'last_name' => 'López Hernández',
                'phone' => '4922345678',
                'email' => 'light.art.drawings@gmail.com',
                'gender' => 'female',
                'shirt_size' => 'M',
                'is_first_time' => true,
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/registers', $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors(['participant_count']);

    $this->assertDatabaseCount('registers', 0);
    $this->assertDatabaseCount('participants', 0);
    Mail::assertNothingQueued();
});

test('PU2 - rechaza el registro cuando attendance_type es "alone" pero el conteo de participantes es mayor a 1', function () {
    $payload = [
        'origin_type' => 'state',
        'state' => 'Zacatecas',
        'municipality' => 'Guadalupe',
        'group' => 'Cuadrilla 1',
        'attendance_type' => 'alone',
        'participant_count' => 2,
        'accommodation_type' => 'hotel',
        'stay_days' => 2,
        'transport_method' => 'car',
        'folio_delivery_method' => 'email',
        'participants' => [
            [
                'first_name' => 'Armando',
                'last_name' => 'Candelas Alvarado',
                'phone' => '4921234567',
                'email' => 'armandocandelasalvarado@gmail.com',
                'gender' => 'male',
                'shirt_size' => 'L',
                'is_first_time' => false,
                'participation_count' => 3,
            ],
            [
                'first_name' => 'María',
                'last_name' => 'López Hernández',
                'phone' => '4922345678',
                'email' => 'light.art.drawings@gmail.com',
                'gender' => 'female',
                'shirt_size' => 'M',
                'is_first_time' => true,
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/registers', $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors(['participant_count']);

    $this->assertDatabaseCount('registers', 0);
});