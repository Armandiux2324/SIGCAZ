<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// PU11 - Validar la búsqueda de usuarios por coincidencia parcial de correo.
// Requerimiento relacionado: RFA-005
// Diseño relacionado: D10

test('PU11 - encuentra usuarios cuyo correo contiene el texto buscado', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['email' => 'dulce@sigcaz.mx']);
    User::factory()->create(['email' => 'armando@sigcaz.mx']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users/search?email=dulce');

    $response->assertOk()->assertJson(['message' => 'Usuarios encontrados.'])->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'dulce@sigcaz.mx');
});

test('PU11 - excluye al propio usuario autenticado de los resultados', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.dulce@sigcaz.mx']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users/search?email=dulce');

    $response->assertNotFound();
});

test('PU11 - responde 404 cuando ningún correo coincide con la búsqueda', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['email' => 'dulce@sigcaz.mx']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users/search?email=noexiste');

    $response->assertNotFound()->assertJson(['message' => 'No se encontraron usuarios con ese correo.', 'data' => []]);
});

test('PU11 - rechaza la búsqueda cuando el parámetro email está vacío', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users/search?email=');

    $response->assertUnprocessable()->assertJson(['message' => 'El parámetro email es requerido.']);
});

test('PU11 - rechaza la búsqueda cuando el parámetro email no se envía', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users/search');

    $response->assertUnprocessable()->assertJson(['message' => 'El parámetro email es requerido.']);
});

test('PU11 - rechaza la búsqueda cuando el email contiene solo espacios en blanco', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users/search?email='.urlencode('   '));

    $response->assertUnprocessable()->assertJson(['message' => 'El parámetro email es requerido.']);
});

test('PU11 - rechaza la búsqueda cuando la solicita un usuario con rol Personal', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/users/search?email=dulce');

    $response->assertForbidden();
});