<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Register;

class RegisterCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $register;

    public function __construct(Register $register)
    {
        $this->register = $register;
    }

    public function build()
    {
        return $this->subject('Confirmación de registro a la cabalgata')
            ->view('emails.register-created');
    }
}
