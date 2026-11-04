<?php

namespace Tests\Feature;

use App\Share\Enums\RegistrationSource;
use App\Share\Enums\Role;
use App\Share\Mail\VerifyEmailMail;
use App\Share\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'new@example.test';

    private const PASSWORD = 'password123';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function register(): string
    {
        Mail::fake();
        $this->postJson('/api/v1/auth/register', [
            'email' => self::EMAIL, 'password' => self::PASSWORD, 'first_name' => 'New', 'last_name' => 'Member', 'registration_source' => 'web',
        ])->assertCreated();

        $token = null;
        Mail::assertQueued(VerifyEmailMail::class, function (VerifyEmailMail $mail) use (&$token) {
            parse_str((string) parse_url($mail->url, PHP_URL_QUERY), $query);
            $token = $query['token'];

            return $mail->hasTo(self::EMAIL);
        });

        return $token;
    }

    private function login()
    {
        return $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => self::PASSWORD]);
    }

    public function test_self_registered_user_must_verify_before_login(): void
    {
        $token = $this->register();

        $this->login()->assertForbidden()->assertJsonValidationErrors('email_unverified');

        $this->postJson('/api/v1/auth/email/verify', ['token' => $token])->assertOk();
        $this->postJson('/api/v1/auth/email/verify', ['token' => $token])->assertOk();
        $this->login()->assertOk()->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_wrong_password_does_not_reveal_unverified_state(): void
    {
        $this->register();

        $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'wrong-password'])->assertUnauthorized();
    }

    public function test_invalid_or_expired_token_is_rejected(): void
    {
        $token = $this->register();

        $this->postJson('/api/v1/auth/email/verify', ['token' => 'garbage'])->assertUnprocessable();

        Carbon::setTestNow(now()->addHours(25));
        $this->postJson('/api/v1/auth/email/verify', ['token' => $token])->assertUnprocessable();
        $this->assertNull(User::query()->where('email', self::EMAIL)->first()->email_verified_at);
    }

    public function test_resend_only_mails_unverified_self_registered_users(): void
    {
        $token = $this->register();

        Mail::fake();
        $this->postJson('/api/v1/auth/email/resend', ['email' => self::EMAIL])->assertOk();
        Mail::assertQueued(VerifyEmailMail::class, 1);

        Mail::fake();
        $this->postJson('/api/v1/auth/email/resend', ['email' => 'missing@example.test'])->assertOk();
        $this->postJson('/api/v1/auth/email/verify', ['token' => $token])->assertOk();
        $this->postJson('/api/v1/auth/email/resend', ['email' => self::EMAIL])->assertOk();
        Mail::assertNothingQueued();
    }

    public function test_staff_created_account_logs_in_without_verifying(): void
    {
        Mail::fake();
        User::factory()->create([
            'email' => self::EMAIL, 'password' => self::PASSWORD, 'role' => Role::Member, 'registration_source' => RegistrationSource::Receptionist,
        ]);

        $this->login()->assertOk();
        Mail::assertNothingQueued();
    }
}
