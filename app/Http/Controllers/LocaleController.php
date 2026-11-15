<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch application locale and redirect back.
     */
    public function switch(string $locale, Request $request): RedirectResponse
    {
        if (in_array($locale, SetLocale::SUPPORTED_LOCALES, true)) {
            $request->session()->put('locale', $locale);
            cookie()->queue(cookie()->forever('locale', $locale));
            $request->session()->flash('locale_just_switched', true);
        }

        $redirect = $request->query('redirect');
        if (
            is_string($redirect)
            && $redirect !== ''
            && str_starts_with($redirect, '/')
            && ! str_starts_with($redirect, '//')
            && ! str_starts_with($redirect, '/\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $redirect)
            && ! str_contains($redirect, '/lang/')
        ) {
            return redirect()->to($redirect);
        }

        $referer = $request->header('Referer');
        if ($referer && ! str_contains($referer, '/lang/')) {
            return redirect()->to($referer);
        }

        $previous = url()->previous();
        if ($previous && ! str_contains($previous, '/lang/')) {
            return redirect()->to($previous);
        }

        return redirect()->route('home');
    }
}
