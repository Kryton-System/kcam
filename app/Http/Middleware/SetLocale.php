<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen language (English or Bangla) for the request.
 * The choice lives in the session; the app default (APP_LOCALE) applies until one is made.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'bn'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : config('app.locale'));

        return $next($request);
    }
}
