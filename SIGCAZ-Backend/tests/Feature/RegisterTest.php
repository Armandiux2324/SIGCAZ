<?php

use App\Mail\RegisterCreatedMail;
use App\Models\Participant;
use App\Models\Register;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// PU1 - Validar el registro de participación individual mediante el formulario del participante.
// Requerimiento relacionado: RFP-002
// Diseño relacionado: D1

test('PU1 - registra una participación individual y encola el correo de confirmación', function () {
    Storage::fake('public'); // evita escribir el QR real en disco durante la prueba
    Mail::fake();            // evita encolar/enviar correos reales

    $payload = [
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
            'first_name' => 'Carlos',
            'last_name' => 'Martínez Soto',
            'phone' => '3312345678',
            'email' => 'carlos.martinez@example.com',
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ];

    $response = $this->postJson('/api/v1/registers', $payload);

    // Resultado esperado: HTTP 201 con el registro y el participante creados
    $response->assertCreated()
        ->assertJson([
            'message' => 'Registro creado exitosamente. Recibirás un correo de confirmación con los detalles de tu registro.',
        ])->assertJsonPath('data.origin_type', 'national')->assertJsonPath('data.participants.0.first_name', 'Carlos')
        ->assertJsonPath('data.participants.0.email', 'carlos.martinez@example.com');

    // El registro y el participante quedaron guardados en la misma transacción
    $this->assertDatabaseCount('registers', 1);
    $this->assertDatabaseHas('participants', [
        'first_name' => 'Carlos',
        'last_name' => 'Martínez Soto',
        'email' => 'carlos.martinez@example.com',
    ]);

    $register = Register::first();
    $participant = Participant::first();

    expect($register->participants)->toHaveCount(1);
    expect($participant->register_id)->toBe($register->id);

    // Se encoló el correo de confirmación para el participante correspondiente
    Mail::assertQueued(RegisterCreatedMail::class, function (RegisterCreatedMail $mail) use ($participant) {
        return $mail->participant->id === $participant->id;
    });
});

test('PU1 - rechaza el registro cuando faltan campos obligatorios', function () {
    $response = $this->postJson('/api/v1/registers', [
        'origin_type' => 'national',
        // sin 'state', 'municipality', 'participants', etc.
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['state', 'municipality', 'participants']);

    $this->assertDatabaseCount('registers', 0);
});