<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU29 - Validar la obtención de datos para la gráfica de participantes por género.
// Requerimiento relacionado: RFA-015
// Diseño relacionado: D20

function createParticipantForChart(string $gender, string $email): void
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
            'first_name' => 'PU29',
            'last_name' => 'Test',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => $email,
            'gender' => $gender,
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();
}

test('PU29 - devuelve las etiquetas y valores correctos para la gráfica de género (filtro por defecto)', function () {
    Storage::fake('public');
    Mail::fake();
    createParticipantForChart('male', 'h1.pu29@example.com');
    createParticipantForChart('male', 'h2.pu29@example.com');
    createParticipantForChart('female', 'm1.pu29@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/stats/chart'); // sin 'filter' -> por defecto 'gender'

    $response->assertOk()->assertJsonPath('data.filter', 'gender');

    $labels = $response->json('data.labels');
    $values = $response->json('data.values');
    $result = array_combine($labels, $values);

    expect($result['Masculino'])->toBe(2);
    expect($result['Femenino'])->toBe(1);
});

test('PU29 - también responde explícitamente al pedir filter=gender', function () {
    Storage::fake('public');
    Mail::fake();
    createParticipantForChart('female', 'm2.pu29@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/stats/chart?filter=gender');

    $response->assertOk()->assertJsonPath('data.filter', 'gender')->assertJsonPath('data.labels.0', 'Femenino')->assertJsonPath('data.values.0', 1);
});

test('PU29 - filtra la gráfica de género por año', function () {
    Storage::fake('public');
    Mail::fake();
    createParticipantForChart('male', 'actual.pu29@example.com');

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/stats/chart?filter=gender&year='.(now()->year + 5));

    $response->assertOk()->assertJsonPath('data.labels', [])->assertJsonPath('data.values', []);
});

test('PU29 - rechaza la consulta de la gráfica sin autenticación', function () {
    $response = $this->getJson('/api/v1/stats/chart?filter=gender');

    $response->assertUnauthorized();
});