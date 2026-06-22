<?php

namespace App\Services;

use App\Models\Participant;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Illuminate\Support\Facades\Storage;

class RegisterReceiptPdfService
{
    public function build(Participant $participant): PdfInstance
    {
        $register = $participant->register;

        $qrBase64 = null;

        if ($participant->qr_path &&Storage::disk('public')->exists($participant->qr_path)) {
            $qrBase64 = base64_encode(Storage::disk('public')->get($participant->qr_path));
        }

        return Pdf::loadView('pdf.register-receipt', [
            'register' => $register,
            'participant' => $participant,
            'qrBase64' => $qrBase64,
        ])->setPaper('letter');
    }
}