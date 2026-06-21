<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    public function store(StoreUserRequest $request): JsonResponse {
        try {
            $data = $request->validated();
            $data['role'] = $data['role'] ?? 'staff';
            $data['email_verified_at'] = now();

            $user = User::create($data);

            return response()->json([
                'message' => 'Usuario creado correctamente.',
                'data' => $user,
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al crear el usuario. Intenta nuevamente más tarde.',
            ], 500);
        }
    }
}
