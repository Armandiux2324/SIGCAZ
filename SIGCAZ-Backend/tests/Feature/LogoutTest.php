<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// PU33 - Validar el cierre de sesión de un usuario autenticado.
// Requerimiento relacionado: RFA-018
// Diseño relacionado: D23

test('PU33 - cierra la sesión del usuario autenticado y elimina su token de acceso', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/logout');

    $response->assertNoContent();
    expect($user->tokens()->count())->toBe(0);
});

test('PU33 - el token eliminado ya no puede usarse en una solicitud posterior', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/logout')
        ->assertNoContent();

    // ya autenticado de la petición anterior en vez de volver a validar el token.
    app('auth')->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/me');

    $response->assertStatus(401);
});

test('PU33 - rechaza el cierre de sesión sin autenticación', function () {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401);
});