<?php

declare(strict_types=1);

namespace App\Share\Mail;

use App\Share\Models\Order;
use App\Share\Services\Gym\TermsDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class GymNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public string $title,
        public string $firstName,
        public string $notice,
        public ?int $signedTermsOrderId = null,
        public string $titleEn = '',
        public string $noticeEn = '',
        public string $actionPath = '/',
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title.($this->titleEn !== '' ? ' / '.$this->titleEn : ''));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.gym-notice');
    }

    /**
     * Bản sao điều khoản đã ký chỉ được tạo lúc gửi, không lưu lại trên server.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->signedTermsOrderId === null) {
            return [];
        }

        $order = Order::query()->findOrFail($this->signedTermsOrderId);

        return [
            Attachment::fromData(
                fn () => app(TermsDocumentService::class)->signedCopy($order),
                "dieu-khoan-{$order->code}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
