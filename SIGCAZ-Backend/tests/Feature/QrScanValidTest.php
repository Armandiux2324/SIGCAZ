<?php

use App\Models\Participant;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU35 - Validar el escaneo de un código QR válido y el registro de asistencia.
// Requerimiento relacionado: RFEM-001
// Diseño relacionado: D27

function createParticipantForScan(string $email): Participant
{
    Storage::fake('public');
    Mail::fake();

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
            'first_name' => 'PU35',
            'last_name' => 'Test',
            'phone' => '35'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();

    return Participant::where('email', $email)->first();
}

test('PU35 - registra la asistencia al escanear un folio válido', function () {
    $participant = createParticipantForScan('valido.pu35@example.com');
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->postJson('/api/v1/scans', ['folio' => $participant->folio]);

    $response->assertOk()
        ->assertJson([
            'status' => 'valid',
            'message' => 'Asistencia registrada correctamente.',
            'data' => [
                'folio' => $participant->folio,
                'first_name' => 'PU35',
                'last_name' => 'Test',
            ],
        ]);

    $this->assertDatabaseHas('qr_scans', [
        'participant_id' => $participant->id,
        'scanned_by' => $staff->id,
        'status' => 'valid',
    ]);

    $participant->refresh();
    expect($participant->attended_at)->not->toBeNull();
});

test('PU35 - rechaza el escaneo cuando falta el folio', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->postJson('/api/v1/scans', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['folio']);
});

test('PU35 - rechaza el escaneo sin autenticación', function () {
    $participant = createParticipantForScan('sinsesion.pu35@example.com');

    $response = $this->postJson('/api/v1/scans', ['folio' => $participant->folio]);

    $response->assertUnauthorized();

    // No debe quedar rastro de un escaneo que nunca debió procesarse
    $this->assertDatabaseCount('qr_scans', 0);
    expect($participant->fresh()->attended_at)->toBeNull();
});