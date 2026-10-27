<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Guest $guest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Petit rappel — '.$this->guest->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.reminder',
            with: ['unsubscribeUrl' => $this->unsubscribeUrl()],
        );
    }

    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('reminders.unsubscribe.show', ['guest' => $this->guest]);
    }
}
