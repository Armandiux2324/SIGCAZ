<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// PU9 - Validar que el Administrador pueda editar la información y el rol de otro usuario.
// Requerimiento relacionado: RFA-003
// Diseño relacionado: D8

test('PU9 - el Administrador edita el nombre y el rol de otro usuario', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['name' => 'Dulce Mota', 'role' => 'staff']);
    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/users/{$target->id}", [
        'name' => 'Dulce Mota Azuara',
        'role' => 'admin',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Usuario actualizado correctamente.',
            'data' => ['id' => $target->id, 'name' => 'Dulce Mota Azuara', 'role' => 'admin'],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'name' => 'Dulce Mota Azuara',
        'role' => 'admin',
    ]);
});

test('PU9 - rechaza la edición cuando el correo ya pertenece a otro usuario', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create();
    User::factory()->create(['email' => 'ocupado@sigcaz.mx']);
    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/users/{$target->id}", [
        'email' => 'ocupado@sigcaz.mx',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

test('PU9 - responde 404 al intentar editar un usuario que no existe', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/v1/users/999999', [
        'name' => 'No existe',
    ]);

    $response->assertNotFound();
});

test('PU9 - rechaza la edición sin autenticación', function () {
    $target = User::factory()->create();

    $response = $this->putJson("/api/v1/users/{$target->id}", [
        'name' => 'Sin sesión',
    ]);

    $response->assertUnauthorized();
});