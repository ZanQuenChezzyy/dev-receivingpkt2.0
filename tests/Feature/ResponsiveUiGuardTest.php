<?php

use App\Services\EmailTemplateRenderer;

it('keeps Filament admin theme protected against table dropdown clipping and vertical tab overflow', function (): void {
    $manifestPath = public_path('build/manifest.json');
    expect(file_exists($manifestPath))->toBeTrue();

    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    expect($manifest)->toHaveKey('resources/css/filament/admin/theme.css');

    $themeCssPath = public_path('build/'.$manifest['resources/css/filament/admin/theme.css']['file']);
    expect(file_exists($themeCssPath))->toBeTrue();

    $css = (string) file_get_contents($themeCssPath);

    // 1. Dropdown tabel memakai viewport-fixed agar tidak pernah terpotong overflow-x:auto
    expect($css)
        ->toContain('.fi-dropdown-viewport-fixed')
        ->toContain('.fi-sc-tabs.fi-vertical')
        ->toContain('overflow-x:clip');

    // 2. Aturan lama yang memaksa .fi-ta-ctn overflow-y:hidden tidak boleh kembali
    $sourceCss = (string) file_get_contents(resource_path('css/filament/admin/theme.css'));
    expect($sourceCss)
        ->not->toContain(".fi-ta-ctn,\n.fi-ta-main,\n.fi-ta-content {\n    overflow-y: hidden !important;\n}")
        ->toContain('.fi-sc-tabs:not(.fi-vertical) > .fi-sc-tabs-tab')
        ->toContain('.fi-tabs:not(.fi-vertical)');
});

it('renders corporate email HTML with dark-mode contrast lock and mobile responsive header stacking', function (): void {
    $renderer = app(EmailTemplateRenderer::class);
    $html = $renderer->wrapInCorporateLayout('<p>Test isi email</p>', 'GRS');

    expect($html)
        ->toContain('name="color-scheme" content="light only"')
        ->toContain('name="supported-color-schemes" content="light only"')
        ->toContain('color-scheme: light only !important')
        ->toContain('.pkt-email-hero-title')
        ->toContain('-webkit-text-fill-color: #ffffff !important')
        ->toContain('@media only screen and (max-width: 520px)')
        ->toContain('.pkt-email-hero-badge-cell');
});

it('locks preview iframe color-scheme to light and avoids duplicate moon icon on ALEX AI toggle', function (): void {
    $previewBlade = (string) file_get_contents(resource_path('views/filament/pages/partials/email-template-preview.blade.php'));
    expect($previewBlade)
        ->toContain('color-scheme: light')
        ->toContain('h-[460px] sm:h-[560px]');

    $aiToggleBlade = (string) file_get_contents(resource_path('views/livewire/ai-system-toggle.blade.php'));
    expect($aiToggleBlade)
        ->not->toContain('heroicon-s-moon')
        ->toContain('heroicon-o-sparkles');
});
