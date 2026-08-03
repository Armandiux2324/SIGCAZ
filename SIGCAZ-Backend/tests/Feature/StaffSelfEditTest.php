<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// PU14 - Validar que un usuario con rol Personal solo pueda editar su propio perfil.
// Requerimiento relacionado: RFA-008
// Diseño relacionado: D13

test('PU14 - un usuario Personal puede editar su propia información', function () {
    $staff = User::factory()->create(['name' => 'César Acosta', 'role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson("/api/v1/users/{$staff->id}", [
        'name' => 'César Acosta P.',
    ]);

    $response->assertOk()->assertJsonPath('data.name', 'César Acosta P.');

    $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'César Acosta P.']);
});

test('PU14 - un usuario Personal es rechazado al intentar editar el perfil de otro usuario', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $other = User::factory()->create(['name' => 'Objetivo']);
    Sanctum::actingAs($staff);

    $response = $this->putJson("/api/v1/users/{$other->id}", [
        'name' => 'Hackeo',
    ]);

    $response->assertForbidden()->assertJson(['message' => 'No tienes permisos para modificar a otro usuario.']);

    $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => 'Objetivo']);
});

test('PU14 - un usuario Personal no puede escalar su propio rol a Administrador', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->putJson("/api/v1/users/{$staff->id}", [
        'name' => 'Intento de escalada',
        'role' => 'admin',
    ]);

    $response->assertOk();

    // El controlador ya borra el rol si no es admin
    $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'staff']);
});