<?php

use App\Models\Participant;
use App\Models\Register;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU18 - Validar la eliminación de un registro, sus participantes y sus códigos QR.
// Requerimiento relacionado: RFA-012
// Diseño relacionado: D17

function createRegisterForPU18(string $suffix = 'a'): Register
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
            'first_name' => 'Carlos',
            'last_name' => 'PU18',
            'phone' => '33'.random_int(10000000, 99999999),
            'email' => "carlos.pu18.{$suffix}@example.com",
            'gender' => 'male',
            'shirt_size' => 'M',
            'is_first_time' => true,
        ]],
    ])->assertCreated();

    return Register::with('participants')->latest()->first();
}

test('PU18 - elimina el registro, sus participantes y sus códigos QR', function () {
    $register = createRegisterForPU18();
    $participant = $register->participants->first();
    $qrPath = $participant->qr_path;

    Storage::disk('public')->assertExists($qrPath);

    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->deleteJson("/api/v1/registers/{$register->id}");

    $response->assertOk()->assertJson(['message' => 'Registro eliminado correctamente.']);

    $this->assertDatabaseMissing('registers', ['id' => $register->id]);
    $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
    Storage::disk('public')->assertMissing($qrPath);
});

test('PU18 - responde 404 al eliminar un registro que no existe', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->deleteJson('/api/v1/registers/999999');

    $response->assertNotFound()->assertJson(['message' => 'Registro no encontrado.']);
});

test('PU18 - rechaza la eliminación sin autenticación', function () {
    $register = createRegisterForPU18();

    $response = $this->deleteJson("/api/v1/registers/{$register->id}");

    $response->assertUnauthorized();
    $this->assertDatabaseHas('registers', ['id' => $register->id]);
});