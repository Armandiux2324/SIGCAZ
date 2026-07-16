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
    public function summary(Request $request): JsonResponse
    {
        try {
            $year = $request->query('year');

            $totalUsers = User::count();

            $totalRegisters = Register::query()
                ->when($year, fn ($q) => $q->whereYear('created_at', $year))
                ->count();

            $attended = $this->participantsQuery($year)->whereNotNull('attended_at')->count();
            $pending = $this->participantsQuery($year)->whereNull('attended_at')->count();

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

    private function participantsQuery(?string $year)
    {
        return Participant::query()
            ->when($year, fn ($q) => $q->whereHas('register', fn ($r) => $r->whereYear('created_at', $year)));
    }

    // Datos para la gráfica de "Participantes por..." según el filtro seleccionado
    public function chart(Request $request): JsonResponse
    {
        try {
            $filter = $request->query('filter', 'gender');
            $year = $request->query('year');

            [$labels, $values] = match ($filter) {
                'registered' => $this->chartRegistered($year),
                'gender' => $this->chartGender($year),
                'shirt_size' => $this->chartShirtSize($year),
                'origin_type' => $this->chartOriginType($year),
                'state' => $this->chartState($year),
                'municipality' => $this->chartMunicipality($year),
                'group' => $this->chartGroup($year),
                'accommodation_type' => $this->chartAccommodationType($year),
                'participation_count' => $this->chartParticipationCount($year),
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

    private function chartRegistered(?string $year): array
    {
        return [
            ['Asistieron', 'Pendientes'],
            [
                $this->participantsQuery($year)->whereNotNull('attended_at')->count(),
                $this->participantsQuery($year)->whereNull('attended_at')->count(),
            ],
        ];
    }

    private function chartGender(?string $year): array
    {
        $rows = $this->participantsQuery($year)
            ->selectRaw('gender, COUNT(*) as total')->groupBy('gender')->get();

        return [
            $rows->map(fn ($r) => $r->gender === 'male' ? 'Masculino' : 'Femenino')->all(),
            $rows->pluck('total')->all(),
        ];
    }

    private function chartShirtSize(?string $year): array
    {
        $order = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        $rows = $this->participantsQuery($year)
            ->selectRaw('shirt_size, COUNT(*) as total')->groupBy('shirt_size')->get()
            ->sortBy(fn ($r) => array_search($r->shirt_size, $order))->values();

        return [$rows->pluck('shirt_size')->all(), $rows->pluck('total')->all()];
    }

    private function chartOriginType(?string $year): array
    {
        $rows = Register::query()
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->selectRaw('origin_type, COUNT(*) as total')->groupBy('origin_type')->get();

        return [
            $rows->map(fn ($r) => $r->origin_type === 'national' ? 'Nacional' : 'Estatal')->all(),
            $rows->pluck('total')->all(),
        ];
    }

    private function chartState(?string $year): array
    {
        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->when($year, fn ($q) => $q->whereYear('registers.created_at', $year))
            ->selectRaw('registers.state, COUNT(participants.id) as total')
            ->groupBy('registers.state')->orderBy('total', 'desc')->limit(10)->get();

        return [$rows->pluck('state')->all(), $rows->pluck('total')->all()];
    }

    private function chartMunicipality(?string $year): array
    {
        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->when($year, fn ($q) => $q->whereYear('registers.created_at', $year))
            ->selectRaw('registers.municipality, COUNT(participants.id) as total')
            ->groupBy('registers.municipality')->orderBy('total', 'desc')->limit(10)->get();

        return [$rows->pluck('municipality')->all(), $rows->pluck('total')->all()];
    }

    private function chartGroup(?string $year): array
    {
        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->when($year, fn ($q) => $q->whereYear('registers.created_at', $year))
            ->selectRaw('registers.group, COUNT(participants.id) as total')
            ->groupBy('registers.group')->orderBy('total', 'desc')->get();

        return [$rows->pluck('group')->all(), $rows->pluck('total')->all()];
    }

    private function chartAccommodationType(?string $year): array
    {
        $labels = [
            'airbnb' => 'Airbnb',
            'hotel' => 'Hotel',
            'own_home' => 'Casa propia',
            'family_or_friends' => 'Casa de familiares o amigos',
        ];

        $rows = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->when($year, fn ($q) => $q->whereYear('registers.created_at', $year))
            ->selectRaw('registers.accommodation_type, COUNT(participants.id) as total')
            ->groupBy('registers.accommodation_type')->orderBy('total', 'desc')->get();

        return [
            $rows->map(fn ($r) => $labels[$r->accommodation_type] ?? $r->accommodation_type)->all(),
            $rows->pluck('total')->all(),
        ];
    }

    private function chartParticipationCount(?string $year): array
    {
        $rows = $this->participantsQuery($year)->selectRaw('is_first_time, participation_count, COUNT(*) as total')
            ->groupBy('is_first_time', 'participation_count')->orderBy('is_first_time', 'desc')->orderBy('participation_count')->get();

        return [
            $rows->map(fn ($r) => $r->is_first_time ? 'Primera vez' : $r->participation_count . ' veces')->all(),
            $rows->pluck('total')->all(),
        ];
    }
}