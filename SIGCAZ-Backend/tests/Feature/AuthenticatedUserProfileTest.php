<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// PU13 - Validar la consulta de la información del usuario autenticado.
// Requerimiento relacionado: RFA-007
// Diseño relacionado: D12

test('PU13 - un usuario autenticado obtiene su propia información', function () {
    $user = User::factory()->create([
        'name' => 'César Acosta Piñon',
        'role' => 'staff',
    ]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJson([
            'message' => 'Información del usuario autenticado obtenida correctamente.',
            'data' => ['id' => $user->id, 'name' => 'César Acosta Piñon', 'role' => 'staff'],
        ]);
});

test('PU13 - también funciona para un usuario con rol Administrador', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/me');

    $response->assertOk()->assertJsonPath('data.id', $admin->id)->assertJsonPath('data.role', 'admin');
});

test('PU13 - no expone la contraseña del usuario en la respuesta', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/me');

    $response->assertOk()->assertJsonMissingPath('data.password');
});

test('PU13 - rechaza la consulta sin autenticación', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
});