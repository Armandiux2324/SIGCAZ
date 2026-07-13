<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrScan\StoreScanRequest;
use App\Models\Participant;
use App\Models\QrScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class QrScanController extends Controller
{
    private function participantSummary(Participant $participant): array
    {
        return [
            'folio' => $participant->folio,
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'shirt_size' => $participant->shirt_size,
            'gender' => $participant->gender_label,
        ];
    }

    public function scan(StoreScanRequest $request): JsonResponse
    {
        try {
            $folio = $request->validated()['folio'];
            $scannerId = $request->user()->id;

            $result = DB::transaction(function () use ($folio, $scannerId) {

                $participant = Participant::where('folio', $folio)->lockForUpdate()->first();

                if (! $participant) {
                    return [
                        'status' => 'invalid',
                        'message' => 'El folio escaneado no corresponde a ningún participante registrado.',
                        'data' => null,
                        'http' => 404,
                    ];
                }

                if ($participant->attended_at) {
                    return [
                        'status' => 'duplicate',
                        'message' => 'Este participante ya fue registrado anteriormente.',
                        'data' => $this->participantSummary($participant),
                        'http' => 409,
                    ];
                }

                QrScan::create([
                    'participant_id' => $participant->id,
                    'scanned_by' => $scannerId,
                    'status' => 'valid',
                    'scanned_at' => now(),
                ]);

                $participant->update(['attended_at' => now()]);

                return [
                    'status' => 'valid',
                    'message' => 'Asistencia registrada correctamente.',
                    'data' => $this->participantSummary($participant),
                    'http' => 200,
                ];
            });

            return response()->json([
                'message' => $result['message'],
                'status' => $result['status'],
                'data' => $result['data'],
            ], $result['http']);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al procesar el escaneo.',
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $scans = QrScan::with('participant')->latest('scanned_at')->paginate(50)->through(function ($scan) {
                    return [
                        'id' => $scan->id,
                        'scanned_at' => $scan->scanned_at,
                        'status' => $scan->status,
                        'participant' => $scan->participant ? $this->participantSummary($scan->participant) : null,
                    ];
                });

            return response()->json([
                'message' => 'Historial de escaneos obtenido correctamente.',
                'data' => $scans,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al obtener el historial de escaneos.',
            ], 500);
        }
    }
}