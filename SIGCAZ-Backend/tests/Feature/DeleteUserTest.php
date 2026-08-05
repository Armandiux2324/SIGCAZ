<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// PU12 - Validar la eliminación de un usuario del sistema.
// Requerimiento relacionado: RFA-006
// Diseño relacionado: D11

test('PU12 - el Administrador elimina un usuario existente', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['email' => 'baja@sigcaz.mx', 'password' => bcrypt('12345678')]);
    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/users/{$target->id}");

    $response->assertOk()->assertJson(['message' => 'Usuario eliminado correctamente.']);

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('PU12 - el usuario eliminado ya no puede iniciar sesión', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['email' => 'baja@sigcaz.mx', 'password' => bcrypt('12345678')]);
    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/users/{$target->id}")->assertOk();

    $response = $this->postJson('/api/v1/login', [
        'email' => 'baja@sigcaz.mx',
        'password' => '12345678',
    ]);

    $response->assertUnauthorized();
});

test('PU12 - responde 404 al intentar eliminar un usuario que no existe', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->deleteJson('/api/v1/users/999999');

    $response->assertNotFound()->assertJson(['message' => 'Usuario no encontrado.']);
});

test('PU12 - rechaza la eliminación cuando la solicita un usuario con rol Personal', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $target = User::factory()->create();
    Sanctum::actingAs($staff);

    $response = $this->deleteJson("/api/v1/users/{$target->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('PU12 - rechaza la eliminación sin autenticación', function () {
    $target = User::factory()->create();

    $response = $this->deleteJson("/api/v1/users/{$target->id}");

    $response->assertUnauthorized();
});