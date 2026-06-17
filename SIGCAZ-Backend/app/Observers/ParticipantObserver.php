<?php

namespace App\Observers;

use App\Models\Participant;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class ParticipantObserver
{
    public function created(Participant $participant): void
    {
        $register = $participant->register;

        $origin = match ($register->origin_type) {
            'national' => 'NACIONAL',
            'state' => 'ESTATAL',
        };

        $folio = sprintf('CAB-%s-%s-%s',$origin,now()->year,$participant->id);

        $fileName = "{$folio}.png";

        $path = "qrs/{$fileName}";

        $result = Builder::create()->writer(new PngWriter())->data($folio)->size(300)->margin(10)->build();

        Storage::disk('public')->put(
            $path,
            $result->getString()
        );

        $participant->updateQuietly([
            'folio' => $folio,
            'qr_path' => $path,
        ]);
    }
}