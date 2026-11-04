<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Mail\GymNoticeMail;
use App\Share\Models\Order;
use App\Share\Models\User;
use App\Share\Services\Gym\TermsDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use setasign\Fpdi\Tfpdf\Fpdi;
use Tests\TestCase;
use tFPDF;

class TermsDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function signatureDataUrl(): string
    {
        $image = imagecreatetruecolor(300, 100);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imageline($image, 10, 50, 290, 50, imagecolorallocate($image, 88, 28, 35));
        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function orderWithSignature(?string $signature, array $attributes = []): Order
    {
        return new Order([
            'code' => 'GYM-TERMS',
            'user_id' => User::factory()->create()->id,
            'type' => OrderType::Package,
            'name' => 'Gói',
            'amount' => 1000,
            'status' => OrderStatus::Success,
            'terms_signature' => $signature,
            ...$attributes,
        ]);
    }

    private function pageCount(string $pdf): int
    {
        $path = tempnam(sys_get_temp_dir(), 'kore-terms-');
        file_put_contents($path, $pdf);

        try {
            return (new Fpdi)->setSourceFile($path);
        } finally {
            unlink($path);
        }
    }

    public function test_public_endpoints_return_placement_and_pdf(): void
    {
        $this->getJson('/api/v1/terms-document')
            ->assertOk()
            ->assertJsonPath('data.page', config('gym.terms_document.signature.page'))
            ->assertJsonStructure(['data' => ['page', 'x', 'y', 'width', 'height']]);

        $this->get('/api/v1/terms-document/file')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Thay file điều khoản mà test này lỗi nghĩa là file mới không đọc được hoặc sai trang chữ ký.
     */
    public function test_signature_is_embedded_into_the_configured_document(): void
    {
        $plain = file_get_contents(config('gym.terms_document.path'));
        $signed = app(TermsDocumentService::class)->signedCopy($this->orderWithSignature($this->signatureDataUrl()));

        $this->assertStringStartsWith('%PDF-', $signed);
        $this->assertStringContainsString('/Subtype /Image', $signed);
        $this->assertSame($this->pageCount($plain), $this->pageCount($signed));
    }

    public function test_multi_page_document_keeps_every_page(): void
    {
        $pdf = new tFPDF;
        $pdf->AddPage();
        $pdf->AddPage();
        $path = tempnam(sys_get_temp_dir(), 'kore-terms-');
        $pdf->Output('F', $path);
        config(['gym.terms_document.path' => $path, 'gym.terms_document.signature.page' => 2]);

        try {
            $signed = app(TermsDocumentService::class)->signedCopy($this->orderWithSignature($this->signatureDataUrl()));
            $this->assertSame(2, $this->pageCount($signed));

            config(['gym.terms_document.signature.page' => 3]);
            $this->expectException(RuntimeException::class);
            app(TermsDocumentService::class)->signedCopy($this->orderWithSignature($this->signatureDataUrl()));
        } finally {
            unlink($path);
        }
    }

    public function test_signer_name_and_date_embed_unicode_font_only_when_present(): void
    {
        $pdf = new tFPDF;
        $pdf->AddPage();
        $path = tempnam(sys_get_temp_dir(), 'kore-terms-');
        $pdf->Output('F', $path);
        config(['gym.terms_document.path' => $path, 'gym.terms_document.signature.page' => 1]);

        try {
            $service = app(TermsDocumentService::class);

            $withSigner = $service->signedCopy($this->orderWithSignature($this->signatureDataUrl(), [
                'buyer_name' => 'Nguyễn Thị Ánh Tuyết',
                'terms_accepted_at' => '2026-11-04 10:00:00',
            ]));
            $this->assertStringContainsString('/FontFile2', $withSigner);

            $withoutSigner = $service->signedCopy($this->orderWithSignature($this->signatureDataUrl()));
            $this->assertStringNotContainsString('/FontFile2', $withoutSigner);
        } finally {
            unlink($path);
        }
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);

        app(TermsDocumentService::class)->signedCopy($this->orderWithSignature('data:image/png;base64,bm90LWEtcG5n'));
    }

    public function test_accept_terms_rejects_signature_that_is_not_a_real_png(): void
    {
        $member = User::factory()->create();
        $order = $this->orderWithSignature(null);
        $order->user_id = $member->id;
        $order->status = OrderStatus::PendingTerms;
        $order->save();

        $this->withToken(auth('api')->login($member))
            ->postJson("/api/v1/orders/{$order->code}/accept-terms", [
                'terms_accepted' => true,
                'signature_data' => 'data:image/png;base64,bm90LWEtcG5n',
                'buyer_name' => 'An',
                'buyer_phone' => '0900000004',
                'buyer_email' => 'an@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('signature_data');
    }

    public function test_mail_attaches_signed_copy_only_when_requested(): void
    {
        $order = $this->orderWithSignature($this->signatureDataUrl());
        $order->save();

        $attachment = (new GymNoticeMail('Tiêu đề', 'An', 'Nội dung', $order->id))->attachments()[0];
        $pdf = $attachment->attachWith(fn ($path) => $path, fn ($data) => $data());

        $this->assertSame("dieu-khoan-{$order->code}.pdf", $attachment->as);
        $this->assertSame('application/pdf', $attachment->mime);
        $this->assertStringStartsWith('%PDF-', $pdf);

        $this->assertSame([], (new GymNoticeMail('Tiêu đề', 'An', 'Nội dung'))->attachments());
    }
}
