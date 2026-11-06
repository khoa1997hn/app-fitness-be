<?php

namespace Tests\Feature;

use App\Share\Enums\UserStatus;
use App\Share\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_catalog_covers_every_message_and_preserves_placeholders(): void
    {
        $vi = require lang_path('vi/messages.php');
        $en = require lang_path('en/messages.php');

        $this->assertSame([], array_diff_key($vi, $en));

        foreach ($vi as $key => $message) {
            preg_match_all('/:[a-z_]+/', $message, $viParameters);
            preg_match_all('/:[a-z_]+/', $en[$key], $enParameters);
            sort($viParameters[0]);
            sort($enParameters[0]);
            $this->assertSame($viParameters[0], $enParameters[0], $key);
        }
    }

    public function test_validation_response_and_field_errors_follow_request_locale(): void
    {
        $this->withHeader('x-locale', 'en')->postJson('/api/v1/auth/login')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The provided data is invalid.')
            ->assertJsonPath('errors.email.0', 'The email field is required.')
            ->assertJsonPath('errors.password.0', 'The password field is required.');

        $this->withHeader('x-locale', 'vi')->postJson('/api/v1/auth/login')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Dữ liệu không hợp lệ.')
            ->assertJsonPath('errors.password.0', 'Vui lòng nhập mật khẩu.');
    }

    public function test_custom_phone_validation_does_not_fall_back_to_vietnamese(): void
    {
        app()->setLocale('en');
        $validator = Validator::make(['phone' => 'invalid'], ['phone' => ['regex:/^\+?\d{7,15}$/']]);

        $this->assertSame(
            'Invalid phone number. Please enter 7–15 digits, optionally starting with +.',
            $validator->errors()->first('phone')
        );
    }

    public function test_failed_login_message_is_localized(): void
    {
        $this->withHeader('x-locale', 'en')->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.test', 'password' => 'wrong',
        ])->assertUnauthorized()->assertJsonPath('message', 'The email or password is incorrect.');
    }

    public function test_locale_is_applied_before_authentication_and_to_missing_routes(): void
    {
        $this->withHeader('x-locale', 'en')->getJson('/api/v1/auth/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'You are not logged in or your session has expired.');

        $this->withHeader('x-locale', 'en')->getJson('/api/v1/nonexistent-route')
            ->assertNotFound()
            ->assertJsonPath('message', 'The requested resource was not found.');
    }

    public function test_locked_account_rejection_uses_the_requested_locale(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Locked]);

        $this->withToken(auth('api')->login($user))
            ->withHeader('x-locale', 'en')->getJson('/api/v1/auth/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Your account has been locked. Please contact the studio.');
    }

    public function test_business_errors_are_localized_and_parameters_are_substituted(): void
    {
        $user = User::factory()->create(['password' => 'Secret123']);
        $this->withToken(auth('api')->login($user))->withHeader('x-locale', 'en')
            ->patchJson('/api/v1/users/me/password', [
                'current_password' => 'wrong',
                'password' => 'NewSecret123',
                'password_confirmation' => 'NewSecret123',
            ])->assertUnprocessable()
            ->assertJsonPath('message', 'Your current password is incorrect.');

        $this->assertSame(
            'Bookings cannot be cancelled within 8 hours of the start time.',
            __('messages.gym_booking_cancel_locked', ['hours' => 8], 'en')
        );
        $this->assertSame(
            'Please accept and sign the terms before paying.',
            __('messages.gym_terms_required', [], 'en')
        );
    }

    public function test_success_message_is_localized_and_request_locale_does_not_leak(): void
    {
        $this->withHeader('x-locale', 'en')->getJson('/api/v1/packages')
            ->assertOk()->assertJsonPath('message', 'Success');
        $this->assertSame('vi', app()->getLocale());

        $this->withHeader('x-locale', 'vi')->getJson('/api/v1/packages')
            ->assertOk()->assertJsonPath('message', 'Thành công.');

        $this->withHeader('x-locale', 'invalid')->getJson('/api/v1/packages')
            ->assertOk()->assertJsonPath('message', 'Thành công.');

        $this->withoutHeader('x-locale')->getJson('/api/v1/packages')
            ->assertOk()->assertJsonPath('message', 'Thành công.');
    }

    public function test_account_deletion_success_message_is_localized(): void
    {
        $user = User::factory()->create();
        $this->withToken(auth('api')->login($user))->withHeader('x-locale', 'en')
            ->deleteJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('message', 'Your account has been deleted successfully');
    }
}
