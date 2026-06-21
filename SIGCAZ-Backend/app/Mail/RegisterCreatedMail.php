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

class RegisterCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Register $register, public Participant $participant,) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de registro a la cabalgata',
        );
    }

    public function content(): Content
    {
        $qrBase64 = null;

        if ($this->participant->qr_path && Storage::disk('public')->exists($this->participant->qr_path)) {
            $qrBase64 = base64_encode(Storage::disk('public')->get($this->participant->qr_path));
        }

        return new Content(
            view: 'emails.register-created',
            with: [
                'register' => $this->register,
                'participant' => $this->participant,
                'qrBase64' => $qrBase64,
            ],
        );
    }
}