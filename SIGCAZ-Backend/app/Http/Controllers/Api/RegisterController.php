<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Register\StoreRegisterRequest;
use App\Http\Requests\Register\SearchRegisterRequest;
use App\Models\Register;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Mail\RegisterCreatedMail;
use Illuminate\Support\Facades\Mail;
use App\Services\RegisterReceiptPdfService;

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
                'group' => $data['group'],
                'attendance_type' => $data['attendance_type'],
                'participant_count' => $data['participant_count'],
                'accommodation_type' => $data['accommodation_type'],
                'lodging' => $data['lodging'] ?? null,
                'stay_days' => $data['stay_days'],
                'transport_method' => $data['transport_method'],
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
                    'is_first_time' => $data['is_first_time'],
                    'participation_count' => $data['participation_count'],
                ]);
            }

            return $register->load([
                'participants',
            ]);
        });

        foreach ($register->participants as $participant) {
            Mail::to($participant->email)->queue(new RegisterCreatedMail($register, $participant));
        }

        return response()->json([
            'message' => 'Registro creado exitosamente. Recibirás un correo de confirmación con los detalles de tu registro.',
            'data' => $register,
        ], 201);
    }

    public function search(SearchRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $participant = Participant::where('folio', $data['folio'])->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])->first();

        if (! $participant) {
            return response()->json([
                'message' => 'No se encontró ningún registro de un participante con ese folio y correo.',
            ], 404);
        }

        $register = $participant->register->load('participants');

        return response()->json([
            'message' => 'Registro encontrado exitosamente.',
            'data' => $register,
        ]);
    }

    public function receipt(SearchRegisterRequest $request, RegisterReceiptPdfService $pdfService)
    {
        $data = $request->validated();

        $participant = Participant::where('folio', $data['folio'])->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])->first();

        if (! $participant) {
            return response()->json([
                'message' => 'No se encontró ningún registro con ese folio y correo.',
            ], 404);
        }

        $pdf = $pdfService->build($participant);

        return $pdf->download("comprobante-{$participant->folio}.pdf");
    }
}
