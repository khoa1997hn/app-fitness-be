<?php

declare(strict_types=1);

namespace App\Share\Services\Auth;

use App\Share\Mail\ForgotPasswordMail;
use App\Share\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

readonly class ForgotPasswordService
{
    private const PASSWORD_RULE = 'required|string|min:8|max:50';

    public function sendNewPasswordIfUserExists(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $password = $this->generatePassword();

        $user->update(['password' => $password]);

        Mail::to($user->email)->send(new ForgotPasswordMail($user, $password));
    }

    private function generatePassword(): string
    {
        do {
            $password = Str::password(12);
        } while (! Validator::make(['password' => $password], ['password' => self::PASSWORD_RULE])->passes());

        return $password;
    }
}
