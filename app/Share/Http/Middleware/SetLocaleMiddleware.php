<?php

namespace App\Share\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('x-locale') ?? config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
