<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Register\StoreRegisterRequest;
use App\Http\Requests\Register\SearchRegisterRequest;
use App\Http\Requests\Register\UpdateRegisterRequest;
use App\Models\Register;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Mail\RegisterCreatedMail;
use Illuminate\Support\Facades\Mail;
use App\Services\RegisterReceiptPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 20), 100);

            $query = Register::query()->with('participants');

            $registers = $query->latest()->paginate($perPage);

            if ($registers->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron registros.',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'message' => 'Listado de registros obtenido correctamente.',
                'data' => $registers,
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al obtener el listado de registros.',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $register = Register::with('participants')->find($id);

            if (! $register) {
                return response()->json([
                    'message' => 'Registro no encontrado.',
                ], 404);
            }

            return response()->json([
                'message' => 'Información del registro obtenida correctamente.',
                'data' => $register,
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al obtener la información del registro.',
            ], 500);
        }
    }

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
                    'is_first_time' => $participantData['is_first_time'],
                    'participation_count' => $participantData['participation_count'] ?? 0,
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

    public function update(UpdateRegisterRequest $request, Register $register): JsonResponse
    {
        try {
            $data = $request->validated();
            $participantsData = $data['participants'] ?? null;
            unset($data['participants']);

            $register = DB::transaction(function () use ($register, $data, $participantsData) {
                if ($participantsData !== null) {
                    $this->syncParticipants($register, $participantsData);
                    $data['participant_count'] = count($participantsData);
                }

                $register->fill($data);
                $register->save();

                return $register->load('participants');
            });

            return response()->json([
                'message' => 'Registro actualizado correctamente.',
                'data' => $register,
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al actualizar el registro.',
            ], 500);
        }
    }

    private function syncParticipants(Register $register, array $participantsData): void
    {
        $incomingIds = collect($participantsData)->pluck('id')->filter()->all();

        $toDelete = $register->participants()->whereNotIn('id', $incomingIds)->get();

        foreach ($toDelete as $participant) {
            if ($participant->qr_path) {
                Storage::disk('public')->delete($participant->qr_path);
            }

            $participant->delete();
        }

        foreach ($participantsData as $participantData) {
            $payload = [
                'first_name' => $participantData['first_name'],
                'last_name' => $participantData['last_name'],
                'phone' => $participantData['phone'],
                'email' => $participantData['email'],
                'gender' => $participantData['gender'],
                'shirt_size' => $participantData['shirt_size'],
                'is_first_time' => $participantData['is_first_time'],
                'participation_count' => $participantData['participation_count'] ?? 0,
            ];

            if (! empty($participantData['id'])) {
                $participant = $register->participants()->whereKey($participantData['id'])->first();

                if ($participant) {
                    $participant->fill($payload);
                    $participant->save();
                    continue;
                }
            }

            $register->participants()->create($payload);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $register = Register::with('participants')->find($id);

            if (! $register) {
                return response()->json([
                    'message' => 'Registro no encontrado.',
                ], 404);
            }

            foreach ($register->participants as $participant) {
                if ($participant->qr_path) {
                    Storage::disk('public')->delete($participant->qr_path);
                }
            }

            $register->delete();

            return response()->json([
                'message' => 'Registro eliminado correctamente.',
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al eliminar el registro.',
            ], 500);
        }
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
