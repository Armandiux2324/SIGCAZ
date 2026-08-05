<?php

use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU28 - Validar el resumen en tiempo real de usuarios, registros y asistencias.
// Requerimiento relacionado: RFA-014
// Diseño relacionado: D19

function createRegisterForStats(string $email): Participant
{
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
            'first_name' => 'PU28',
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

test('PU28 - devuelve el resumen correcto de usuarios, registros, asistencias y pendientes', function () {
    Storage::fake('public');
    Mail::fake();

    $staff = User::factory()->create(['role' => 'staff']); // cuenta dentro de total_users
    User::factory()->count(2)->create();

    $asistio = createRegisterForStats('asistio.pu28@example.com');
    $asistio->update(['attended_at' => now()]);
    createRegisterForStats('pendiente.pu28@example.com');

    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/stats/summary');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'total_users' => 3,
                'total_registers' => 2,
                'attended' => 1,
                'pending' => 1,
            ],
        ]);
});

test('PU28 - filtra el resumen por año cuando se envía el parámetro year', function () {
    Storage::fake('public');
    Mail::fake();

    $staff = User::factory()->create(['role' => 'staff']);
    createRegisterForStats('actual.pu28@example.com');
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/stats/summary?year='.(now()->year + 5));

    $response->assertOk()->assertJsonPath('data.total_registers', 0)->assertJsonPath('data.attended', 0)->assertJsonPath('data.pending', 0);
});

test('PU28 - rechaza la consulta del resumen sin autenticación', function () {
    $response = $this->getJson('/api/v1/stats/summary');

    $response->assertUnauthorized();
});