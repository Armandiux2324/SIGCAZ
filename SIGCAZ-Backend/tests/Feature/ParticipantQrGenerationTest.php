<?php

use App\Models\Participant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// PU3 - Validar la generación automática de folio y código QR al crear un participante.
// Requerimientos relacionados: RFP-004 (Generación de Folio), RFP-005 (Codificación del QR)
// Diseño relacionado: D2

function registerPayloadForPU3(string $originType): array
{
    return [
        'origin_type' => $originType,
        'state' => 'Zacatecas',
        'municipality' => 'Zacatecas',
        'group' => 'Cuadrilla 1',
        'attendance_type' => 'alone',
        'participant_count' => 1,
        'accommodation_type' => 'hotel',
        'stay_days' => 2,
        'transport_method' => 'car',
        'folio_delivery_method' => 'email',
        'participants' => [[
            'first_name' => 'Test',
            'last_name' => 'PU3',
            'phone' => '4920000000',
            'email' => 'pu3.'.$originType.'@example.com',
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ];
}

test('PU3 - genera folio con prefijo NACIONAL y su código QR en storage', function () {
    Storage::fake('public');
    Mail::fake();

    $response = $this->postJson('/api/v1/registers', registerPayloadForPU3('national'));
    $response->assertCreated();

    $participant = Participant::first();
    $year = now()->year;

    expect($participant->folio)->toBe("CAB-NACIONAL-{$year}-{$participant->id}");
    expect($participant->qr_path)->toBe("qrs/CAB-NACIONAL-{$year}-{$participant->id}.png");

    Storage::disk('public')->assertExists($participant->qr_path);
    expect(Storage::disk('public')->size($participant->qr_path))->toBeGreaterThan(0);
});

test('PU3 - genera folio con prefijo ESTATAL cuando el origen es estatal', function () {
    Storage::fake('public');
    Mail::fake();

    $response = $this->postJson('/api/v1/registers', registerPayloadForPU3('state'));
    $response->assertCreated();

    $participant = Participant::first();
    $year = now()->year;

    expect($participant->folio)->toBe("CAB-ESTATAL-{$year}-{$participant->id}");
    Storage::disk('public')->assertExists($participant->qr_path);
});

test('PU3 - no genera folio ni QR si el registro no llega a crearse por datos inválidos', function () {
    Storage::fake('public');

    // origin_type inválido -> falla la validación antes de llegar a crear nada
    $payload = registerPayloadForPU3('national');
    $payload['origin_type'] = 'invalid_origin';

    $response = $this->postJson('/api/v1/registers', $payload);

    $response->assertUnprocessable();
    $this->assertDatabaseCount('participants', 0);
    Storage::disk('public')->assertDirectoryEmpty('qrs');
});