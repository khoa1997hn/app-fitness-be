<?php

namespace Tests\Feature;

use App\Share\Mail\ForgotPasswordMail;
use App\Share\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function requestLink(User $user): array
    {
        Mail::fake();
        config(['app.frontend_url' => 'https://members.example.test']);
        $oldHash = $user->password;
        $this->postJson('/api/v1/auth/password/reset', ['email' => $user->email])->assertOk();
        $query = [];
        Mail::assertSent(ForgotPasswordMail::class, function (ForgotPasswordMail $mail) use ($user, &$query) {
            $this->assertSame('https://members.example.test/reset-password', strtok($mail->url, '?'));
            parse_str(parse_url($mail->url, PHP_URL_QUERY), $query);

            return $mail->hasTo($user->email);
        });
        $this->assertSame($oldHash, $user->refresh()->password);

        return $query;
    }

    private function payload(array $query): array
    {
        return [...$query, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];
    }

    public function test_link_changes_password_only_on_confirmation_and_cannot_be_reused(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        $query = $this->requestLink($user);
        $this->assertTrue(Password::tokenExists($user, $query['token']));
        $this->postJson('/api/v1/auth/password/confirm', $this->payload($query))->assertOk();
        $this->assertTrue(Hash::check('NewPassword123', $user->refresh()->password));
        $this->assertFalse(Hash::check('OldPassword123', $user->password));
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'NewPassword123'])->assertOk();
        $this->postJson('/api/v1/auth/password/confirm', $this->payload($query))->assertUnprocessable();
    }

    public function test_unknown_and_deleted_accounts_receive_same_response_without_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $user->delete();
        $missing = $this->postJson('/api/v1/auth/password/reset', ['email' => 'missing@example.test'])->assertOk();
        $deleted = $this->postJson('/api/v1/auth/password/reset', ['email' => $user->email])->assertOk();
        $this->assertSame($missing->json(), $deleted->json());
        Mail::assertNothingSent();
    }

    public function test_invalid_expired_wrong_email_and_deleted_account_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $query = $this->requestLink($user);
        $oldHash = $user->password;
        $this->postJson('/api/v1/auth/password/confirm', $this->payload([...$query, 'token' => 'invalid']))->assertUnprocessable();
        $this->postJson('/api/v1/auth/password/confirm', $this->payload([...$query, 'email' => $other->email]))->assertUnprocessable();
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();
        $this->postJson('/api/v1/auth/password/confirm', $this->payload($query))->assertUnprocessable();
        $this->travelBack();
        $this->assertSame($oldHash, $user->refresh()->password);
        $user->delete();
        $this->postJson('/api/v1/auth/password/confirm', $this->payload($query))->assertUnprocessable();
    }

    public function test_invalid_password_does_not_consume_token_and_sending_is_throttled(): void
    {
        $user = User::factory()->create();
        $query = $this->requestLink($user);
        $this->postJson('/api/v1/auth/password/reset', ['email' => $user->email])->assertOk();
        Mail::assertSent(ForgotPasswordMail::class, 1);
        foreach ([['password' => 'short'], ['password' => str_repeat('x', 51)], ['password_confirmation' => 'mismatch']] as $invalid) {
            $this->postJson('/api/v1/auth/password/confirm', [...$this->payload($query), ...$invalid])->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/password/confirm', $this->payload($query))->assertOk();
    }

    public function test_new_link_replaces_previous_link(): void
    {
        $user = User::factory()->create();
        $first = $this->requestLink($user);
        $this->travel(61)->seconds();
        $second = $this->requestLink($user);
        $this->postJson('/api/v1/auth/password/confirm', $this->payload($first))->assertUnprocessable();
        $this->postJson('/api/v1/auth/password/confirm', $this->payload($second))->assertOk();
    }
}
