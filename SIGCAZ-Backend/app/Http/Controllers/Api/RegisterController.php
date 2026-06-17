<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Register\StoreRegisterRequest;
use App\Models\Register;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegisterController extends Controller
{
    public function store(StoreRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $register = DB::transaction(function () use ($data) {

            $register = Register::create([
                'origin_type' => $data['origin_type'],
                'state' => $data['state'],
                'municipality' => $data['municipality'],
                'group_id' => $data['group_id'] ?? null,
                'is_first_time' => $data['is_first_time'],
                'participation_count' => $data['participation_count'],
                'attendance_type' => $data['attendance_type'],
                'participant_count' => $data['participant_count'],
                'accommodation_type' => $data['accommodation_type'],
                'lodging' => $data['lodging'] ?? null,
                'stay_days' => $data['stay_days'],
                'folio_delivery_method' => $data['folio_delivery_method'],
            ]);

            foreach ($data['participants'] as $participantData) {

                $participant = $register->participants()->create([
                    'first_name' => $participantData['first_name'],
                    'last_name' => $participantData['last_name'],
                    'phone' => $participantData['phone'],
                    'email' => $participantData['email'],
                    'gender' => $participantData['gender'],
                    'shirt_size' => $participantData['shirt_size'],
                ]);
            }

            return $register->load([
                'group',
                'participants',
            ]);
        });

        return response()->json([
            'message' => 'Registro creado exitosamente.',
            'data' => $register,
        ], 201);
    }
}
