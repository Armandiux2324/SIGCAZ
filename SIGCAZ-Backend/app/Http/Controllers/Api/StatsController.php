<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Register;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    // Datos para la gráfica de "Participantes por..." según el filtro seleccionado
    public function chart(Request $request): JsonResponse
    {
        try {
            $filter = $request->query('filter', 'gender');

            [$labels, $values] = match ($filter) {
                'registered' => $this->chartRegistered(),
                'gender' => $this->chartGender(),
                'shirt_size' => $this->chartShirtSize(),
                'origin_type' => $this->chartOriginType(),
                'state' => $this->chartState(),
                'municipality' => $this->chartMunicipality(),
                'group' => $this->chartGroup(),
                'accommodation_type' => $this->chartAccommodationType(),
                'participation_count' => $this->chartParticipationCount(),
                default => [[], []],
            };

            return response()->json([
                'data' => [
                    'filter' => $filter,
                    'labels' => $labels,
                    'values' => $values,
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Error al obtener los datos de la gráfica.'], 500);
        }
    }

    // Datos para la gráfica de "Registros por año"
    public function byYear(): JsonResponse
    {
        try {
            $data = Register::selectRaw('YEAR(created_at) as year, COUNT(*) as total')->groupBy('year')->orderBy('year')->get();

            return response()->json([
                'data' => [
                    'labels' => $data->pluck('year')->map(fn ($y) => (string) $y)->all(),
                    'values' => $data->pluck('total')->all(),
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Error al obtener los registros por año.'], 500);
        }
    }

    private function chartRegistered(): array
    {
        return [
            ['Asistieron', 'Pendientes'],
            [
                Participant::whereNotNull('attended_at')->count(),
                Participant::whereNull('attended_at')->count(),
            ],
        ];
    }

    private function chartGender(): array
    {
        $rows = Participant::selectRaw('gender, COUNT(*) as total')->groupBy('gender')->get();

        return [
            $rows->map(fn ($r) => $r->gender === 'male' ? 'Masculino' : 'Femenino')->all(),
            $rows->pluck('total')->all(),
        ];
    }

    private function chartShirtSize(): array
    {
        $order = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        $rows = Participant::selectRaw('shirt_size, COUNT(*) as total')->groupBy('shirt_size')->get()->sortBy(fn ($r) => array_search($r->shirt_size, $order))->values();

        return [$rows->pluck('shirt_size')->all(), $rows->pluck('total')->all()];
    }

    private function chartOriginType(): array
    {
        $rows = Register::selectRaw('origin_type, COUNT(*) as total')->groupBy('origin_type')->get();

        return [
            $rows->map(fn ($r) => $r->origin_type === 'national' ? 'Nacional' : 'Estatal')->all(),
            $rows->pluck('total')->all(),
        ];
    }

    private function chartState(): array
    {
        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->selectRaw('registers.state, COUNT(participants.id) as total')
            ->groupBy('registers.state')->orderBy('total', 'desc')->limit(10)->get();

        return [$rows->pluck('state')->all(), $rows->pluck('total')->all()];
    }

    private function chartMunicipality(): array
    {
        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->selectRaw('registers.municipality, COUNT(participants.id) as total')
            ->groupBy('registers.municipality')->orderBy('total', 'desc')->limit(10)->get();

        return [$rows->pluck('municipality')->all(), $rows->pluck('total')->all()];
    }

    private function chartGroup(): array
    {
        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->selectRaw('registers.group, COUNT(participants.id) as total')
            ->groupBy('registers.group')->orderBy('total', 'desc')->get();

        return [$rows->pluck('group')->all(), $rows->pluck('total')->all()];
    }

    private function chartAccommodationType(): array
    {
        $labels = [
            'airbnb' => 'Airbnb',
            'hotel' => 'Hotel',
            'own_home' => 'Casa propia',
            'family_or_friends' => 'Casa de familiares o amigos',
        ];

        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->selectRaw('registers.accommodation_type, COUNT(participants.id) as total')
            ->groupBy('registers.accommodation_type')->orderBy('total', 'desc')->get();

        return [
            $rows->map(fn ($r) => $labels[$r->accommodation_type] ?? $r->accommodation_type)->all(),
            $rows->pluck('total')->all(),
        ];
    }

    private function chartParticipationCount(): array
    {
        $rows = Participant::selectRaw('is_first_time, participation_count, COUNT(*) as total')
            ->groupBy('is_first_time', 'participation_count')
            ->orderBy('is_first_time', 'desc')->orderBy('participation_count')->get();

        return [
            $rows->map(fn ($r) => $r->is_first_time ? 'Primera vez' : $r->participation_count . ' veces')->all(),
            $rows->pluck('total')->all(),
        ];
    }
}