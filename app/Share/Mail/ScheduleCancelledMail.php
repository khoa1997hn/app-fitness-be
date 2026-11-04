<?php

declare(strict_types=1);

namespace App\Share\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ScheduleCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public string $firstName,
        public string $notice,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.gym_mail_schedule_cancelled_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.schedule-cancelled',
        );
    }
}
