<x-mail::message>
# Hello {{ $user->first_name }}

You requested a password reset. Your new password is:

**{{ $password }}**

Please sign in and change your password if you wish.

If you did not request this, please contact support.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
