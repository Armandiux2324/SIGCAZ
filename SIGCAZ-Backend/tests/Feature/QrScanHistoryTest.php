<?php

use App\Models\Participant;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU36 - Validar la consulta del historial de escaneos ordenado cronológicamente.
// Requerimiento relacionado: RFEM-002/RFEM-003
// Diseño relacionado: D28

function createParticipantForHistory(string $email): Participant
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
            'first_name' => 'PU36',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();

    return Participant::where('email', $email)->first();
}

function createScanAt(Participant $participant, User $scanner, string $status, string $scannedAt): QrScan
{
    return QrScan::create([
        'participant_id' => $participant->id,
        'scanned_by' => $scanner->id,
        'status' => $status,
        'scanned_at' => $scannedAt,
    ]);
}

test('PU36 - devuelve el historial de escaneos ordenado del más reciente al más antiguo', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $p1 = createParticipantForHistory('primero.pu36@example.com');
    $p2 = createParticipantForHistory('segundo.pu36@example.com');
    $p3 = createParticipantForHistory('tercero.pu36@example.com');

    createScanAt($p1, $staff, 'valid', '2026-08-01 09:00:00');
    createScanAt($p2, $staff, 'valid', '2026-08-01 09:05:00');
    createScanAt($p3, $staff, 'invalid', '2026-08-01 09:10:00');

    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/scans');

    $response->assertOk()->assertJson(['message' => 'Historial de escaneos obtenido correctamente.']);

    $folios = collect($response->json('data.data'))->pluck('participant.folio');

    expect($folios->all())->toBe([$p3->folio, $p2->folio, $p1->folio]);
});

test('PU36 - incluye el estado y los datos del participante en cada escaneo', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $participant = createParticipantForHistory('detalle.pu36@example.com');
    createScanAt($participant, $staff, 'valid', now());

    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/scans');

    $response->assertOk()->assertJsonPath('data.data.0.status', 'valid')->assertJsonPath('data.data.0.participant.folio', $participant->folio)
        ->assertJsonPath('data.data.0.participant.first_name', 'PU36');
});

test('PU36 - pagina el historial de escaneos', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    for ($i = 0; $i < 3; $i++) {
        $participant = createParticipantForHistory("pagina{$i}.pu36@example.com");
        createScanAt($participant, $staff, 'valid', now()->addMinutes($i));
    }

    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/scans');

    $response->assertOk()->assertJsonPath('data.total', 3)->assertJsonPath('data.per_page', 50);
});

test('PU36 - devuelve el historial vacío cuando no hay escaneos registrados', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/scans');

    $response->assertOk()->assertJsonPath('data.total', 0)->assertJsonCount(0, 'data.data');
});

test('PU36 - rechaza la consulta del historial sin autenticación', function () {
    $response = $this->getJson('/api/v1/scans');

    $response->assertUnauthorized();
});