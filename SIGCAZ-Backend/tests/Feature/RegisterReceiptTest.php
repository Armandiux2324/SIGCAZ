<?php

use App\Models\Participant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// PU6 - Validar la descarga del comprobante de registro en PDF.
// Requerimiento relacionado: RFP-008
// Diseño relacionado: D5

function createRegisterForPU6(): Participant
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

test('PU6 - descarga el comprobante en PDF con folio y correo válidos', function () {
    $participant = createRegisterForPU6();

    $response = $this->getJson('/api/v1/registers/receipt?'.http_build_query([
        'folio' => $participant->folio,
        'email' => $participant->email,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');

    $disposition = $response->headers->get('content-disposition');
    expect($disposition)->toContain('attachment');
    expect($disposition)->toContain('comprobante-');
    expect($disposition)->toContain('.pdf');
});

test('PU6 - responde 404 cuando el folio y el correo no coinciden con ningún participante', function () {
    createRegisterForPU6();

    $response = $this->getJson('/api/v1/registers/receipt?'.http_build_query([
        'folio' => 'CAB-NACIONAL-2026-9999',
        'email' => 'no-existe@example.com',
    ]));

    $response->assertNotFound()->assertJson(['message' => 'No se encontró ningún registro con ese folio y correo.']);
});

test('PU6 - rechaza la descarga cuando falta el folio', function () {
    $response = $this->getJson('/api/v1/registers/receipt?email=armandocandelasalvarado@gmail.com');

    $response->assertUnprocessable()->assertJsonValidationErrors(['folio']);
});