<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// PU10 - Validar la consulta del listado paginado de usuarios.
// Requerimiento relacionado: RFA-004
// Diseño relacionado: D9

test('PU10 - el Administrador obtiene el listado paginado de usuarios sin incluirse a sí mismo', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(2)->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users');

    $response->assertOk()->assertJson(['message' => 'Información de usuarios obtenida correctamente.'])->assertJsonCount(2, 'data.data');

    $ids = collect($response->json('data.data'))->pluck('id');
    expect($ids)->not->toContain($admin->id);
});

test('PU10 - respeta el parámetro per_page sin superar el máximo de 100', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(5)->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users?per_page=2');

    $response->assertOk()->assertJsonCount(2, 'data.data')->assertJsonPath('data.per_page', 2);
});

test('PU10 - responde 404 cuando no hay otros usuarios registrados', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users');

    $response->assertNotFound()->assertJson(['message' => 'No se encontraron usuarios registrados.', 'data' => []]);
});

test('PU10 - rechaza la consulta cuando la solicita un usuario con rol Personal', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/users');

    $response->assertForbidden();
});

test('PU10 - rechaza la consulta sin autenticación', function () {
    $response = $this->getJson('/api/v1/users');

    $response->assertUnauthorized();
});