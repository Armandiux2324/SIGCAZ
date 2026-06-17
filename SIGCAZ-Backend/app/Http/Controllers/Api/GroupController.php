<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class GroupController extends Controller
{
    public function store(StoreGroupRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            $group = Group::create($data);

            DB::commit();

            return response()->json([
                'message' => 'Cuadra guardada correctamente.',
                'data' => $group,
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear la cuadra: ' . $e->getMessage(),
            ], 500);
        }
    }
}
