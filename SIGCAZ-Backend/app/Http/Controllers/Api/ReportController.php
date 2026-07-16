<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportController extends Controller
{
    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6B1B1B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    private function download(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // 1. Participantes registrados (listado completo)
    public function participants(): StreamedResponse
    {
        $rows = Participant::with('register')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Participantes');

        $headers = [
            'Folio', 'Nombre', 'Apellidos', 'Teléfono', 'Correo',
            'Género', 'Talla', 'Primera vez', 'Participaciones previas',
            'Cuadrilla', 'Estado', 'Municipio', 'Asistió',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:M1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($rows as $p) {
            $sheet->fromArray([
                $p->folio,
                $p->first_name,
                $p->last_name,
                $p->phone,
                $p->email,
                $p->gender_label,
                $p->shirt_size,
                $p->is_first_time_label,
                $p->participation_count ? $p->participation_count : '—',
                $p->register?->group,
                $p->register?->state,
                $p->register?->municipality,
                $p->attended_at ? 'Sí' : 'No',
            ], null, "A{$r}");
            $r++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_participantes_' . now()->format('Ymd'));
    }

    // 2. Por género
    public function byGender(): StreamedResponse
    {
        $data = Participant::selectRaw('gender, COUNT(*) as total')->groupBy('gender')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Género');

        $sheet->fromArray(['Género', 'Total'], null, 'A1');
        $sheet->getStyle('A1:B1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($data as $row) {
            $sheet->setCellValue("A{$r}", $row->gender === 'male' ? 'Masculino' : 'Femenino');
            $sheet->setCellValue("B{$r}", $row->total);
            $r++;
        }

        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_genero_' . now()->format('Ymd'));
    }

    // 3. Por talla
    public function byShirtSize(): StreamedResponse
    {
        $order = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        $data = Participant::selectRaw('shirt_size, COUNT(*) as total')->groupBy('shirt_size')->get()->sortBy(fn ($r) => array_search($r->shirt_size, $order));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Talla');

        $sheet->fromArray(['Talla', 'Total'], null, 'A1');
        $sheet->getStyle('A1:B1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($data as $row) {
            $sheet->setCellValue("A{$r}", $row->shirt_size);
            $sheet->setCellValue("B{$r}", $row->total);
            $r++;
        }

        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_tallas_' . now()->format('Ymd'));
    }

    // 4. Por estado
    public function byState(): StreamedResponse
    {
        $data = Participant::join('registers', 'participants.register_id', '=', 'registers.id')->selectRaw('registers.state, COUNT(participants.id) as total')
            ->groupBy('registers.state')->orderBy('total', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Estado');

        $sheet->fromArray(['Estado', 'Total de participantes'], null, 'A1');
        $sheet->getStyle('A1:B1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($data as $row) {
            $sheet->setCellValue("A{$r}", $row->state);
            $sheet->setCellValue("B{$r}", $row->total);
            $r++;
        }

        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_estados_' . now()->format('Ymd'));
    }

    // 5. Por municipio
    public function byMunicipality(): StreamedResponse
    {
        $data = Participant::join('registers', 'participants.register_id', '=', 'registers.id')
            ->selectRaw('registers.state, registers.municipality, COUNT(participants.id) as total')->groupBy('registers.state', 'registers.municipality')
            ->orderBy('registers.state')->orderBy('total', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Municipio');

        $sheet->fromArray(['Estado', 'Municipio', 'Total de participantes'], null, 'A1');
        $sheet->getStyle('A1:C1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($data as $row) {
            $sheet->setCellValue("A{$r}", $row->state);
            $sheet->setCellValue("B{$r}", $row->municipality);
            $sheet->setCellValue("C{$r}", $row->total);
            $r++;
        }

        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_municipios_' . now()->format('Ymd'));
    }

    // 6. Por cuadrilla (group)
    public function byGroup(): StreamedResponse
    {
        $data = Participant::join('registers', 'participants.register_id', '=', 'registers.id')->selectRaw('registers.group, COUNT(participants.id) as total')
            ->groupBy('registers.group')->orderBy('total', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Cuadrilla');

        $sheet->fromArray(['Cuadrilla', 'Total de participantes'], null, 'A1');
        $sheet->getStyle('A1:B1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($data as $row) {
            $sheet->setCellValue("A{$r}", $row->group);
            $sheet->setCellValue("B{$r}", $row->total);
            $r++;
        }

        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_cuadrillas_' . now()->format('Ymd'));
    }

    // 7. Por tipo de hospedaje
    public function byAccommodation(): StreamedResponse
    {
        $labels = [
            'airbnb' => 'Airbnb',
            'hotel' => 'Hotel',
            'own_home' => 'Casa propia',
            'family_or_friends' => 'Casa de familiares o amigos',
        ];

        $data = Participant::join('registers', 'participants.register_id', '=', 'registers.id')->selectRaw('registers.accommodation_type, COUNT(participants.id) as total')
            ->groupBy('registers.accommodation_type')->orderBy('total', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Hospedaje');

        $sheet->fromArray(['Tipo de hospedaje', 'Total de participantes'], null, 'A1');
        $sheet->getStyle('A1:B1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($data as $row) {
            $sheet->setCellValue("A{$r}", $labels[$row->accommodation_type] ?? $row->accommodation_type);
            $sheet->setCellValue("B{$r}", $row->total);
            $r++;
        }

        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_hospedaje_' . now()->format('Ymd'));
    }

    // 8. Por cantidad de participaciones previas
    public function byParticipationCount(): StreamedResponse
    {
        $data = Participant::selectRaw('is_first_time, participation_count, COUNT(*) as total')->groupBy('is_first_time', 'participation_count')
            ->orderBy('is_first_time', 'desc')->orderBy('participation_count')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Participaciones Previas');

        $sheet->fromArray(['Primera vez', 'Participaciones previas', 'Total'], null, 'A1');
        $sheet->getStyle('A1:C1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($data as $row) {
            $sheet->setCellValue("A{$r}", $row->is_first_time ? 'Sí' : 'No');
            $sheet->setCellValue("B{$r}", $row->is_first_time ? '—' : $row->participation_count);
            $sheet->setCellValue("C{$r}", $row->total);
            $r++;
        }

        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_participaciones_previas_' . now()->format('Ymd'));
    }

    // 9. Asistencia / inasistencia
    public function attendance(): StreamedResponse
    {
        $rows = Participant::with('register')->orderByRaw('attended_at IS NULL, attended_at ASC')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia');

        $headers = [
            'Folio', 'Nombre', 'Apellidos', 'Cuadrilla',
            'Estado', 'Municipio', 'Asistió', 'Hora de asistencia',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->applyFromArray($this->headerStyle());

        $r = 2;
        foreach ($rows as $p) {
            $sheet->fromArray([
                $p->folio,
                $p->first_name,
                $p->last_name,
                $p->register?->group,
                $p->register?->state,
                $p->register?->municipality,
                $p->attended_at ? 'Sí' : 'No',
                $p->attended_at ? $p->attended_at->format('d/m/Y H:i:s') : '—',
            ], null, "A{$r}");

            // Verde si asistió, rojo si no
            $color = $p->attended_at ? 'C6EFCE' : 'FFC7CE';
            $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);

            $r++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'reporte_asistencia_' . now()->format('Ymd'));
    }
}