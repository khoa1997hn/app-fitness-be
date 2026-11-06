<?php

declare(strict_types=1);

namespace App\Share\Services\Auth;

use App\Share\Mail\ForgotPasswordMail;
use App\Share\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

readonly class ForgotPasswordService
{
    public function sendResetLinkIfUserExists(string $email): void
    {
        Password::sendResetLink(['email' => $email], function (User $user, string $token): void {
            $url = rtrim(config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->email,
            ]);

            Mail::to($user->email)->send(new ForgotPasswordMail($user, $url));
        });
    }
}
