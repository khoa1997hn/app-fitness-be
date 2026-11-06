<?php

declare(strict_types=1);

namespace App\Share\Mail;

use App\Share\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Đặt lại mật khẩu / Reset your password',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.forgot-password',
        );
    }
}
