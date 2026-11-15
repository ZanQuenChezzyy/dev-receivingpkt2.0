<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales in the application.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_LOCALES = ['id', 'en'];

    /**
     * Handle an incoming request and set application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = config('app.locale', 'id');

        $locale = $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? $defaultLocale;

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $defaultLocale;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
