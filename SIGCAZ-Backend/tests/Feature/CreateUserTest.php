<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

// PU8 - Validar el registro de un nuevo usuario por parte del Administrador.
// Requerimiento relacionado: RFA-002
// Diseño relacionado: D7

test('PU8 - el Administrador crea un nuevo usuario con la contraseña cifrada', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Dulce Mota',
        'email' => 'dulce@sigcaz.mx',
        'password' => '12345678',
        'phone' => '4929876543',
        'role' => 'staff',
    ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Usuario creado correctamente.',
            'data' => ['name' => 'Dulce Mota', 'email' => 'dulce@sigcaz.mx', 'role' => 'staff'],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'dulce@sigcaz.mx', 'role' => 'staff']);

    $newUser = User::where('email', 'dulce@sigcaz.mx')->first();
    expect(Hash::check('12345678', $newUser->password))->toBeTrue();
    expect($newUser->password)->not->toBe('12345678');
});

test('PU8 - rechaza la creación cuando un usuario Personal intenta crear otro usuario', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Intruso',
        'email' => 'intruso@sigcaz.mx',
        'password' => '12345678',
    ]);

    $response->assertForbidden()->assertJson(['message' => 'No tienes permisos para realizar esta acción.']);

    $this->assertDatabaseMissing('users', ['email' => 'intruso@sigcaz.mx']);
});

test('PU8 - rechaza la creación sin autenticación', function () {
    $response = $this->postJson('/api/v1/users', [
        'name' => 'Sin sesión',
        'email' => 'sinsesion@sigcaz.mx',
        'password' => '12345678',
    ]);

    $response->assertUnauthorized();
});

test('PU8 - rechaza la creación con un correo ya registrado', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    User::factory()->create(['email' => 'dulce@sigcaz.mx']);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Dulce Duplicada',
        'email' => 'dulce@sigcaz.mx',
        'password' => '12345678',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
});