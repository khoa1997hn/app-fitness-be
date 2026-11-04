<?php

namespace Tests\Feature;

use App\Share\Mail\ForgotPasswordMail;
use App\Share\Mail\GymNoticeMail;
use App\Share\Mail\ScheduleCancelledMail;
use App\Share\Mail\VerifyEmailMail;
use App\Share\Models\User;
use Illuminate\Mail\Markdown;
use Tests\TestCase;

class MailPresentationTest extends TestCase
{
    public function test_all_emails_render_both_languages_and_context_links_in_html_and_text(): void
    {
        config(['app.frontend_url' => 'https://members.example.test/']);
        app()->setLocale('en');
        $user = new User(['first_name' => 'An <script>alert(1)</script>']);
        $tokenUrl = 'https://members.example.test/verify-email?token=abc%2B123&source=email';
        $cases = [
            [new VerifyEmailMail($user->first_name, $tokenUrl, 24), 'Xác thực email', 'Verify email', $tokenUrl],
            [new ForgotPasswordMail($user, 'New<&Password123'), 'Mật khẩu mới', 'Your new password', 'https://members.example.test/login'],
            [new ScheduleCancelledMail($user->first_name, 'Lịch Pilates đã bị hủy.', 'Your Pilates session was cancelled.'), 'Lịch sử tập', 'Session history', 'https://members.example.test/hoc-vien/khoa-hoc-cua-toi?tab=history'],
            [new GymNoticeMail('Thanh toán thành công', $user->first_name, 'Đơn KORE-123 đã thanh toán.', titleEn: 'Payment confirmed', noticeEn: 'Order KORE-123 has been paid.', actionPath: '/admin/payments/123'), 'Đơn KORE-123', 'Order KORE-123', 'https://members.example.test/admin/payments/123'],
        ];

        foreach ($cases as [$mail, $vi, $en, $actionUrl]) {
            $html = $mail->render();
            $text = (string) app(Markdown::class)->renderText($mail->content()->markdown, $mail->buildViewData());
            foreach ([$html, $text] as $body) {
                $this->assertStringContainsString($vi, $body);
                $this->assertStringContainsString($en, $body);
                $this->assertStringContainsString('Đội ngũ KORE', $body);
                $this->assertStringContainsString('The KORE team', $body);
                $this->assertStringContainsString('https://members.example.test/login', $body);
                $this->assertStringNotContainsString('localhost', $body);
                $this->assertStringNotContainsString('messages.', $body);
            }
            $this->assertStringContainsString('href="'.e($actionUrl).'"', $html);
            $this->assertStringContainsString($actionUrl, html_entity_decode($text));
            $this->assertStringContainsString(' / ', $mail->envelope()->subject);
            $this->assertStringNotContainsString('<script>', $html);
            $this->assertStringNotContainsString('<x-mail', $html);
        }
        $this->assertStringContainsString('New&lt;&amp;Password123', $cases[1][0]->render());
    }
}
