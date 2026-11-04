<x-mail::message>
<p class="eyebrow">KORE ACCOUNT</p>

# Your new password

<p class="greeting">Hello {{ $user->first_name }},</p>

You requested a password reset. Your new password is:

<x-mail::panel>
<span class="password">{{ $password }}</span>
</x-mail::panel>

Please sign in and change your password if you wish.

If you did not request this, please contact support.

Thanks,<br>
The KORE team
</x-mail::message>
