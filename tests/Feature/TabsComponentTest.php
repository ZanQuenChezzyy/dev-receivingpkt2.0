<?php

use Illuminate\Support\Facades\Blade;

test('filament tabs component renders horizontal scroll container with navigation arrows', function () {
    $html = Blade::render('<x-filament::tabs><button class="fi-tabs-item">Tab 1</button><button class="fi-tabs-item">Tab 2</button></x-filament::tabs>');

    expect($html)
        ->toContain('fi-tabs-scroll-outer')
        ->toContain('fi-tabs-scroll-inner')
        ->toContain('fi-tabs-arrow-left')
        ->toContain('fi-tabs-arrow-right')
        ->toContain('fi-tabs')
        ->toContain('role="tablist"')
        ->toContain('svg')
        ->toContain('Tab 1')
        ->toContain('Tab 2');
});

test('filament vertical tabs component renders vertical nav without horizontal arrows', function () {
    $html = Blade::render('<x-filament::tabs :vertical="true"><button class="fi-tabs-item">Tab 1</button></x-filament::tabs>');

    expect($html)
        ->toContain('fi-vertical')
        ->not->toContain('fi-tabs-scroll-outer')
        ->not->toContain('fi-tabs-arrow-left');
});
