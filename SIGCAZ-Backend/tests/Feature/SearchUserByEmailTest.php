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

    $response->assertNotFound()
        ->assertJson(['message' => 'No se encontraron usuarios con ese correo.', 'data' => []]);
});

test('PU11 - BUG DETECTADO: con email vacío no rechaza la búsqueda, devuelve todos los usuarios', function () {
    // La validación `if (! $email)` en searchByEmail() nunca se cumple, porque
    // $request->string('email') devuelve un objeto Stringable, y en PHP los objetos
    // siempre son "truthy" sin importar si el string interno está vacío.
    // Resultado real: con email='' se ejecuta un LIKE '%%' que coincide con todo.
    // Este test documenta el comportamiento ACTUAL, no el esperado según el mensaje
    // "El parámetro email es requerido." Si corriges el código (por ejemplo usando
    // $request->query('email', '') === '' o Stringable::isEmpty()), este test dejará
    // de pasar y deberá reemplazarse por uno que sí espere el 422.
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(3)->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users/search?email=');

    $response->assertOk()->assertJsonCount(3, 'data');
});

test('PU11 - rechaza la búsqueda cuando la solicita un usuario con rol Personal', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/v1/users/search?email=dulce');

    $response->assertForbidden();
});