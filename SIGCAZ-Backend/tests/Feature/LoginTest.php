<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// PU7 - Validar el inicio de sesión con credenciales correctas e incorrectas.
// Requerimiento relacionado: RFA-001
// Diseño relacionado: D6

test('PU7 - inicia sesión con credenciales correctas y recibe un token', function () {
    $user = User::factory()->create([
        'email' => 'admin@sigcaz.mx',
        'password' => Hash::make('12345678'),
        'role' => 'admin',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@sigcaz.mx',
        'password' => '12345678',
    ]);

    $response->assertOk()->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email', 'role']])
        ->assertJson([
            'token_type' => 'Bearer',
            'user' => ['id' => $user->id, 'email' => 'admin@sigcaz.mx', 'role' => 'admin'],
        ]);

    expect($response->json('access_token'))->not->toBeEmpty();
});

test('PU7 - rechaza el inicio de sesión con contraseña incorrecta', function () {
    User::factory()->create([
        'email' => 'admin@sigcaz.mx',
        'password' => Hash::make('12345678'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@sigcaz.mx',
        'password' => 'contraseña-incorrecta',
    ]);

    $response->assertUnauthorized()->assertJson(['message' => 'Credenciales inválidas']);
});

test('PU7 - rechaza el inicio de sesión con un correo que no existe', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'no-existe@sigcaz.mx',
        'password' => '12345678',
    ]);

    $response->assertUnauthorized()->assertJson(['message' => 'Credenciales inválidas']);
});

test('PU7 - rechaza el inicio de sesión cuando faltan credenciales', function () {
    $response = $this->postJson('/api/v1/login', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
});