<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The email form of a notification: the rendered subject and body, in the person's language. Sent from a queued
 * delivery job, so the mailable itself is not queued again.
 */
class NotificationMail extends Mailable
{
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $text,
        public readonly string $language,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notification', with: ['text' => $this->text, 'language' => $this->language, 'direction' => $this->language === 'ar' ? 'rtl' : 'ltr']);
    }
}
