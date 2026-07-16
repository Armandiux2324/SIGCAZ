<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Settings;
use App\Http\Requests\Settings\StoreSettingsRequest;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = Settings::first();

        return response()->json([
            'data' => [
                'event_address' => $settings->event_address,
                'event_date' => optional($settings->event_date)->format('Y-m-d'),
                'event_time' => optional($settings->event_date)->format('H:i'),
                'event_image_url' => $settings->event_image_path ? asset('storage/' . $settings->event_image_path) : null,
            ],
        ], 200);
    }
    public function update(StoreSettingsRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $settings = Settings::first();

            $data = $request->safe()->except('event_image');

            if ($request->hasFile('event_image')) {
                if ($settings->event_image_path) {
                    Storage::disk('public')->delete($settings->event_image_path);
                }

                $data['event_image_path'] = $request->file('event_image')->store('event_images', 'public');
            }

            $settings->fill($data);
            $settings->save();

            DB::commit();

            return response()->json([
                'message' => 'Configuración actualizada correctamente.',
                'data' => [
                    'event_address' => $settings->event_address,
                    'event_date' => optional($settings->event_date)->format('Y-m-d'),
                    'event_time' => optional($settings->event_date)->format('H:i'),
                    'event_image_url' => $settings->event_image_path ? asset('storage/' . $settings->event_image_path): null,
                ],
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'message' => 'Error al actualizar la configuración.',
            ], 500);
        }
    }
}
