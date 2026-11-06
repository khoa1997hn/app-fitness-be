<?php

namespace App\Share\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $previousLocale = app()->getLocale();
        $locale = $request->header('x-locale');

        if (! in_array($locale, config('translatable.locales'), true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        try {
            return $next($request);
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
