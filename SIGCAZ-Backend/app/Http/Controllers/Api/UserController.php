<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    public function me(Request $request){
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Error. Usuario no autenticado.',
                ], 401);
            }

            return response()->json([
                'message' => 'Información del usuario autenticado obtenida correctamente.',
                'data' => $user,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener la información del usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse {
        try {
            $authUser = $request->user();
            $perPage = min($request->integer('per_page', 20), 100);

            $users = User::where('id', '!=', $authUser->id)->paginate($perPage);

            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron usuarios registrados.',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'message' => 'Información de usuarios obtenida correctamente.',
                'data' => $users,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener el listado de usuarios: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                ], 404);
            }

            return response()->json([
                'message' => 'Información del usuario obtenida correctamente.',
                'data' => $user,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener la información del usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role ?? 'staff',
                'email_verified_at' => now(),
            ]);

            return response()->json([
                'message' => 'Usuario creado correctamente.',
                'data' => $user,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al crear el usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $authUser = $request->user();

            if ($authUser->role === 'staff' && $authUser->id !== $user->id) {
                return response()->json([
                    'message' => 'No tienes permisos para modificar a otro usuario.',
                ], 403);
            }

            $data = $request->validated();

            if ($authUser->role === 'staff') {
                unset($data['role']);
            }

            if (array_key_exists('password', $data)) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->fill($data);
            $user->save();

            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'data' => $user,
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al actualizar el usuario. Intenta nuevamente más tarde.',
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                ], 404);
            }

            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado correctamente.',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar el usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchByEmail(Request $request): JsonResponse
    {
        try {
            $email = $request->string('email')->trim()->lower();

            if (! $email) {
                return response()->json([
                    'message' => 'El parámetro email es requerido.',
                ], 422);
            }

            $users = User::where('id', '!=', $request->user()->id)->whereRaw('LOWER(email) LIKE ?', ["%{$email}%"])->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron usuarios con ese correo.',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'message' => 'Usuarios encontrados.',
                'data' => $users,
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Error al buscar usuarios.'], 500);
        }
    }
}
