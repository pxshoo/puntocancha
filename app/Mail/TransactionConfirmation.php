<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TransactionConfirmation extends Mailable
{
    public function __construct(public array $confirmation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->confirmation['demo'] ? '[PRUEBA] ' : '').$this->confirmation['title'].' · PuntoCancha');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.transaction-confirmation');
    }
}
