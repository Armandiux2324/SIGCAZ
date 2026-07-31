<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Register;
use App\Models\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class RegisterReceiptPdfService
{
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function build(Participant $participant): PdfInstance
    {
        return $this->buildFromParticipants(collect([$participant]), $participant->register);
    }

    public function buildForRegister(Register $register): PdfInstance
    {
        $register->loadMissing('participants');

        return $this->buildFromParticipants($register->participants, $register);
    }
    public function eventInfo(): array
    {
        $settings = Settings::first();

        return [
            'eventDate' => $this->formatEventDate($settings?->event_date),
            'eventAddress' => $settings?->event_address,
        ];
    }

    private function buildFromParticipants(Collection $participants, Register $register): PdfInstance
    {
        $eventInfo = $this->eventInfo();

        $items = $participants->map(function (Participant $participant) {
            $qrBase64 = null;

            if ($participant->qr_path && Storage::disk('public')->exists($participant->qr_path)) {
                $qrBase64 = base64_encode(Storage::disk('public')->get($participant->qr_path));
            }

            return [
                'participant' => $participant,
                'qrBase64' => $qrBase64,
            ];
        });

        return Pdf::loadView('pdf.register-receipt', [
            'register' => $register,
            'items' => $items,
            'eventDate' => $eventInfo['eventDate'],
            'eventAddress' => $eventInfo['eventAddress'],
        ])->setPaper('letter');
    }

    private function formatEventDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->day . ' de ' . ucfirst(self::MESES[$date->month]) . ' del ' . $date->year;
    }
}