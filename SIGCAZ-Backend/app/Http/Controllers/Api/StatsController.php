<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Register;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Throwable;

class StatsController extends Controller
{
    public function summary(): JsonResponse
    {
        try {
            $totalUsers = User::count();
            $totalRegisters = Register::count();
            $attended = Participant::whereNotNull('attended_at')->count();
            $pending = Participant::whereNull('attended_at')->count();

            return response()->json([
                'data' => [
                    'total_users' => $totalUsers,
                    'total_registers' => $totalRegisters,
                    'attended' => $attended,
                    'pending' => $pending,
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Error al obtener estadísticas.'], 500);
        }
    }
}