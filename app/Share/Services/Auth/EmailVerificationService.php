<?php

namespace App\Share\Services\Auth;

use App\Share\Mail\VerifyEmailMail;
use App\Share\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    private const EXPIRES_HOURS = 24;

    public function send(User $user): void
    {
        $token = Crypt::encryptString((string) json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'expires_at' => now()->addHours(self::EXPIRES_HOURS)->getTimestamp(),
        ]));
        $url = rtrim((string) config('app.frontend_url'), '/').'/verify-email?token='.rawurlencode($token);

        Mail::to($user->email)->queue(new VerifyEmailMail($user->first_name, $url, self::EXPIRES_HOURS));
    }

    /**
     * Bấm lại link đã dùng vẫn thành công. Token gắn với email nên đổi email thì link cũ hết tác dụng.
     *
     * @return bool false khi token sai hoặc hết hạn
     */
    public function verify(string $token): bool
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return false;
        }

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->getTimestamp()) {
            return false;
        }

        $user = User::query()->where('id', $payload['id'] ?? 0)->where('email', $payload['email'] ?? '')->first();

        if ($user === null) {
            return false;
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return true;
    }

    public function resend(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user?->needsEmailVerification()) {
            $this->send($user);
        }
    }
}
