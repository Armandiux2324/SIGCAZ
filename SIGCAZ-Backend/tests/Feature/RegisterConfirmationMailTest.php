<?php

use App\Mail\RegisterCreatedMail;
use App\Models\Participant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// PU4 - Validar el envío del correo de confirmación de registro con QR y comprobante adjunto.
// Requerimiento relacionado: RFP-006
// Diseño relacionado: D3

function registerPayloadForPU4(): array
{
    return [
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
}

test('PU4 - encola el correo de confirmación con asunto correcto, QR y comprobante adjunto', function () {
    Storage::fake('public');
    Mail::fake();

    $response = $this->postJson('/api/v1/registers', registerPayloadForPU4());
    $response->assertCreated();

    $participant = Participant::first();

    Mail::assertQueued(RegisterCreatedMail::class, function (RegisterCreatedMail $mail) use ($participant) {
        expect($mail->envelope()->subject)->toBe('Confirmación de registro a la cabalgata');
        expect($mail->participant->id)->toBe($participant->id);
        expect($mail->register->id)->toBe($participant->register_id);

        // Debe llevar el comprobante en PDF como adjunto
        $attachments = $mail->attachments();
        expect($attachments)->toHaveCount(1);

        return true;
    });
});

test('PU4 - no encola ningún correo cuando el registro falla la validación', function () {
    Mail::fake();

    $payload = registerPayloadForPU4();
    unset($payload['participants']); // fuerza la falla de validación

    $response = $this->postJson('/api/v1/registers', $payload);

    $response->assertUnprocessable();
    Mail::assertNothingQueued();
});

test('PU4 - encola un correo independiente por cada participante en un registro grupal', function () {
    Storage::fake('public');
    Mail::fake();

    $payload = registerPayloadForPU4();
    $payload['attendance_type'] = 'accompanied';
    $payload['participant_count'] = 2;
    $payload['participants'] = [
        $payload['participants'][0],
        [
            'first_name' => 'Ana',
            'last_name' => 'Pérez Ruiz',
            'phone' => '3319876543',
            'email' => 'ana.perez@example.com',
            'gender' => 'female',
            'shirt_size' => 'S',
            'is_first_time' => true,
        ],
    ];

    $this->postJson('/api/v1/registers', $payload)->assertCreated();

    Mail::assertQueued(RegisterCreatedMail::class, 2);
    Mail::assertQueued(RegisterCreatedMail::class, fn (RegisterCreatedMail $mail) => $mail->participant->email === 'carlos.martinez@example.com');
    Mail::assertQueued(RegisterCreatedMail::class, fn (RegisterCreatedMail $mail) => $mail->participant->email === 'ana.perez@example.com');
});