<?php

use App\Models\Settings;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// PU31 - Validar la actualización de la fecha, imagen y dirección del evento.
// Requerimiento relacionado: RFA-017/RFA-018/RFA-019
// Diseño relacionado: D22

test('PU31 - el Administrador actualiza la dirección, fecha e imagen del evento', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/v1/settings', [
        'event_address' => 'Plaza de Armas, Zacatecas, Zac.',
        'event_date' => '2026-11-15 10:00:00',
        'event_image' => UploadedFile::fake()->image('evento.jpg'),
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Configuración actualizada correctamente.',
            'data' => [
                'event_address' => 'Plaza de Armas, Zacatecas, Zac.',
                'event_date' => '2026-11-15',
                'event_time' => '10:00',
            ],
        ]);

    expect($response->json('data.event_image_url'))->not->toBeNull();

    $settings = Settings::first();
    expect($settings->event_address)->toBe('Plaza de Armas, Zacatecas, Zac.');
    Storage::disk('public')->assertExists($settings->event_image_path);
});

test('PU31 - al subir una nueva imagen, elimina la anterior del storage', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $this->putJson('/api/v1/settings', [
        'event_address' => 'Dirección inicial',
        'event_date' => '2026-10-01 09:00:00',
        'event_image' => UploadedFile::fake()->image('primera.jpg'),
    ])->assertOk();

    $primeraRuta = Settings::first()->event_image_path;
    Storage::disk('public')->assertExists($primeraRuta);

    $this->putJson('/api/v1/settings', [
        'event_address' => 'Dirección actualizada',
        'event_date' => '2026-10-02 09:00:00',
        'event_image' => UploadedFile::fake()->image('segunda.jpg'),
    ])->assertOk();

    $segundaRuta = Settings::first()->event_image_path;

    Storage::disk('public')->assertMissing($primeraRuta);
    Storage::disk('public')->assertExists($segundaRuta);
});

test('PU31 - se puede actualizar la dirección y fecha sin enviar una nueva imagen', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/v1/settings', [
        'event_address' => 'Solo texto, sin imagen',
        'event_date' => '2026-12-01 08:00:00',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.event_address', 'Solo texto, sin imagen');
});

test('PU31 - rechaza la actualización cuando faltan campos requeridos', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/v1/settings', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['event_address', 'event_date']);
});

test('PU31 - rechaza la actualización cuando el archivo de imagen no es una imagen válida', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/v1/settings', [
        'event_address' => 'Dirección',
        'event_date' => '2026-12-01 08:00:00',
        'event_image' => UploadedFile::fake()->create('documento.pdf', 100),
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['event_image']);
});

test('PU31 - rechaza la actualización cuando la solicita un usuario con rol Personal', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson('/api/v1/settings', [
        'event_address' => 'Intento no autorizado',
        'event_date' => '2026-12-01 08:00:00',
    ]);

    $response->assertForbidden();
});

test('PU31 - rechaza la actualización sin autenticación', function () {
    $response = $this->putJson('/api/v1/settings', [
        'event_address' => 'Sin sesión',
        'event_date' => '2026-12-01 08:00:00',
    ]);

    $response->assertUnauthorized();
});