<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\Register;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Services\RegisterReceiptPdfService;
use Illuminate\Mail\Mailables\Attachment;

class RegisterCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Register $register, public Participant $participant) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirmación de registro a la cabalgata');
    }

    public function content(): Content
    {
        $qrBinary = null;

        if ($this->participant->qr_path && Storage::disk('public')->exists($this->participant->qr_path)) {
            $qrBinary = Storage::disk('public')->get($this->participant->qr_path);
        }

        return new Content(
            view: 'emails.register-created',
            with: [
                'register' => $this->register,
                'participant' => $this->participant,
                'qrBinary' => $qrBinary,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = app(RegisterReceiptPdfService::class)->build($this->participant);

        return [
            Attachment::fromData(fn () => $pdf->output(), "comprobante-{$this->participant->folio}.pdf")->withMime('application/pdf'),
        ];
    }
}