<?php

use App\Models\Settings;

// PU31 - Validar la consulta de la configuración vigente del evento.
// Requerimiento relacionado: RFA-016
// Diseño relacionado: D21

test('PU31 - obtiene la dirección, fecha, hora e imagen configuradas del evento', function () {
    $settings = Settings::first();
    $settings->update([
        'event_address' => 'Plaza de Armas, Zacatecas, Zac.',
        'event_date' => '2026-11-15 10:30:00',
        'event_image_path' => 'event_images/evento.jpg',
    ]);

    $response = $this->getJson('/api/v1/settings');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'event_address' => 'Plaza de Armas, Zacatecas, Zac.',
                'event_date' => '2026-11-15',
                'event_time' => '10:30',
            ],
        ]);

    expect($response->json('data.event_image_url'))->toContain('event_images/evento.jpg');
});

test('PU31 - devuelve event_image_url en null cuando no se ha configurado una imagen', function () {
    $settings = Settings::first();
    $settings->update(['event_image_path' => null]);

    $response = $this->getJson('/api/v1/settings');

    $response->assertOk()->assertJsonPath('data.event_image_url', null);
});

test('PU31 - la consulta es pública y no requiere autenticación', function () {
    // (se usa en el header del sitio público y de la app móvil sin sesión).
    $response = $this->getJson('/api/v1/settings');

    $response->assertOk();
});