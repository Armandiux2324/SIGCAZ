<?php

use App\Models\Participant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// PU5 - Validar la consulta de un registro mediante folio y correo del participante.
// Requerimiento relacionado: RFP-007
// Diseño relacionado: D4

function createRegisterForPU5(): Participant
{
    Storage::fake('public');
    Mail::fake();

    $payload = [
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
            'first_name' => 'Armando',
            'last_name' => 'Candelas Alvarado',
            'phone' => '4921234567',
            'email' => 'armandocandelasalvarado@gmail.com',
            'gender' => 'male',
            'shirt_size' => 'L',
            'is_first_time' => false,
            'participation_count' => 3,
        ]],
    ];

    test()->postJson('/api/v1/registers', $payload)->assertCreated();

    return Participant::first()->fresh();
}

test('PU5 - encuentra el registro cuando el folio y el correo coinciden', function () {
    $participant = createRegisterForPU5();

    $response = $this->getJson('/api/v1/registers/search?'.http_build_query([
        'folio' => $participant->folio,
        'email' => $participant->email,
    ]));

    $response->assertOk()->assertJson(['message' => 'Registro encontrado exitosamente.'])
        ->assertJsonPath('data.id', $participant->register_id)->assertJsonPath('data.participants.0.folio', $participant->folio);
});

test('PU5 - responde 404 cuando el folio y el correo no coinciden con ningún participante', function () {
    $participant = createRegisterForPU5();

    $response = $this->getJson('/api/v1/registers/search?'.http_build_query([
        'folio' => $participant->folio,
        'email' => 'correo-que-no-coincide@example.com',
    ]));

    $response->assertNotFound()->assertJson(['message' => 'No se encontró ningún registro de un participante con ese folio y correo.']);
});

test('PU5 - responde 404 cuando el folio no existe', function () {
    createRegisterForPU5();

    $response = $this->getJson('/api/v1/registers/search?'.http_build_query([
        'folio' => 'CAB-NACIONAL-2026-9999',
        'email' => 'armandocandelasalvarado@gmail.com',
    ]));

    $response->assertNotFound()->assertJson(['message' => 'No se encontró ningún registro de un participante con ese folio y correo.']);
});

test('PU5 - rechaza la consulta cuando falta el folio o el correo', function () {
    $response = $this->getJson('/api/v1/registers/search?folio=CAB-NACIONAL-2026-1');

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
});