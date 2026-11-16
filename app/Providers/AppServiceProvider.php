<?php

namespace App\Providers;

use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DatePicker::configureUsing(function (DatePicker $component) {
            $component->displayFormat('d F Y');
        });

        ActionGroup::configureUsing(function (ActionGroup $group) {
            $group->dropdownTeleport();
        });
    }
}
