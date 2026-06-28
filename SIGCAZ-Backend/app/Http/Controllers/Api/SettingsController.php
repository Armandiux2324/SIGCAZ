<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Settings;
use App\Http\Requests\Settings\StoreSettingsRequest;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function update(StoreSettingsRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $settings = Settings::findOrFail($id);

            $data = $request->validated();

            $settings->fill($data);
            $settings->save();

            if ($request->hasFile('event_image_path')) {
                $path = $request->file('event_image_path')->store('event_images', 'public');
            }

            DB::commit();

            return response()->json([
                'message' => 'Configuración actualizada correctamente.',
                'data' => $settings,
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar el configuración: ' . $e->getMessage(),
            ], 500);
        }
    }
}
