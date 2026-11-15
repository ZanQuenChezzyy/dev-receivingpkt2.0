<?php

namespace App\Livewire;

use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale = 'id';

    public function mount(): void
    {
        $this->currentLocale = app()->getLocale();
    }

    public function switchLocale(string $locale): void
    {
        if (in_array($locale, SetLocale::SUPPORTED_LOCALES, true)) {
            session()->put('locale', $locale);
            cookie()->queue(cookie()->forever('locale', $locale));
            app()->setLocale($locale);
            $this->currentLocale = $locale;

            $this->redirect(request()->header('Referer') ?: filament()->getUrl(), navigate: true);
        }
    }

    public function render(): View
    {
        $this->currentLocale = app()->getLocale();

        return view('livewire.language-switcher', [
            'currentLocale' => $this->currentLocale,
        ]);
    }
}
