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
        }

        $referer = $request->header('Referer');
        if ($referer && ! str_contains($referer, '/lang/')) {
            return redirect()->to($referer);
        }

        return redirect()->back(fallback: route('home'));
    }
}
