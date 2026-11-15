<?php

use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Livewire\LanguageSwitcher;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Tests\TestCase;

test('application defaults to id locale', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $response->assertStatus(200);
    expect(App::getLocale())->toBe('id');
});

test('it switches to english via route and sets session and cookie', function () {
    /** @var TestCase $this */
    $response = $this->get(route('locale.switch', 'en'));

    $response->assertRedirect();
    $response->assertSessionHas('locale', 'en');
    $response->assertCookie('locale', 'en');
});

test('it switches to indonesian via route and sets session and cookie', function () {
    /** @var TestCase $this */
    $response = $this->withSession(['locale' => 'en'])
        ->get(route('locale.switch', 'id'));

    $response->assertRedirect();
    $response->assertSessionHas('locale', 'id');
    $response->assertCookie('locale', 'id');
});

test('it ignores invalid locale and falls back to default', function () {
    /** @var TestCase $this */
    $response = $this->get(route('locale.switch', 'fr'));

    $response->assertRedirect();
    $response->assertSessionMissing('locale');
});

test('set locale middleware sets app locale from session', function () {
    /** @var TestCase $this */
    $response = $this->withSession(['locale' => 'en'])->get('/');

    expect(App::getLocale())->toBe('en');
    expect(__('Beranda'))->toBe('Home');
    expect(__('Penerimaan Receiving'))->toBe('Receiving');
    expect(__('Penerimaan DO'))->toBe('DO Receipts');
    expect(DeliveryOrderReceiptResource::getNavigationGroup())->toBe('Receiving');
    expect(DeliveryOrderReceiptResource::getNavigationLabel())->toBe('DO Receipts');
    expect(__('Pusat Layanan'))->toBe('Service Center');
    expect(__('Unit Receiving'))->toBe('Receiving Unit');
    expect(__('Masuk Dashboard'))->toBe('Enter Dashboard');
    expect(__('Integrasi PO Terbit & DO Receipt'))->toBe('Issued PO & DO Receipt Integration');
    expect(__('07 · GUDANG'))->toBe('07 · WAREHOUSE');
    expect(__('Alur Proses Penerimaan Barang Gudang Receiving Pupuk Kaltim'))->toBe('Goods Receiving Process Workflow Pupuk Kaltim Receiving Warehouse');
    expect(__('16 Persyaratan Kelengkapan Dokumen Penagihan'))->toBe('16 Invoicing Document Requirements');
    expect(__('Riwayat Pergerakan Dokumen'))->toBe('Document Movement History');

    $response->assertSee('Service Center');
    $response->assertSee('Receiving Unit');
    $response->assertSee('Enter Dashboard');
    $response->assertSee('Total DO Items');

    // Authenticated user sees the full dashboard & SOP diagrams
    $user = new User(['name' => 'Admin']);
    $authResponse = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/');
    $authResponse->assertSee('07 · WAREHOUSE');

    // Authenticated user sees the full dashboard & SOP diagrams
    $user = new User(['name' => 'Admin']);
    $authResponse = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/');
    $authResponse->assertSee('07 · WAREHOUSE');
});

test('set locale middleware sets app locale from cookie when session is empty', function () {
    /** @var TestCase $this */
    $this->withCookies(['locale' => 'en'])->get('/');

    expect(App::getLocale())->toBe('en');
    expect(__('Beranda'))->toBe('Home');
});

test('translations return indonesian when locale is id', function () {
    /** @var TestCase $this */
    $this->withSession(['locale' => 'id'])->get('/');

    expect(App::getLocale())->toBe('id');
    expect(__('Beranda'))->toBe('Beranda');
    expect(__('Penerimaan Receiving'))->toBe('Penerimaan Receiving');
    expect(__('Penerimaan DO'))->toBe('Penerimaan DO');
    expect(DeliveryOrderReceiptResource::getNavigationGroup())->toBe('Penerimaan Receiving');
    expect(DeliveryOrderReceiptResource::getNavigationLabel())->toBe('Penerimaan DO');
});

test('livewire language switcher component renders and can switch locale', function () {
    Livewire::test(LanguageSwitcher::class)
        ->assertSet('currentLocale', 'id')
        ->assertSee('ID')
        ->assertSee('EN')
        ->assertSee('segmented-spring-pill-round')
        ->assertSee('absolute inset-y-[3px] left-[3px]')
        ->assertSee('dark:text-white')
        ->call('switchLocale', 'en')
        ->assertRedirect();

    expect(session('locale'))->toBe('en');
});

test('filament navigation groups are ordered according to required specification', function () {
    /** @var TestCase $this */
    App::setLocale('id');
    Filament::setCurrentPanel('admin');
    $panel = Filament::getCurrentPanel();

    $groups = array_values(array_map(
        fn ($g) => $g instanceof NavigationGroup ? $g->getLabel() : $g,
        $panel->getNavigationGroups()
    ));

    expect($groups)->toBe([
        'Penerimaan Receiving',
        'Penerimaan Jasa / Invoice',
        'Transaksi',
        'Pengeluaran Material',
        'Laporan',
        'Data Master',
        'Kelola Pengguna',
    ]);
});

test('vendor po tracking page renders without blade parse errors', function () {
    /** @var TestCase $this */
    $response = $this->get(route('frontend.vendor.tracking'));
    $response->assertStatus(200);
});

test('frontend navbar renders language switcher on desktop and mobile', function () {
    /** @var TestCase $this */
    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertSee(route('locale.switch', 'id'));
    $response->assertSee(route('locale.switch', 'en'));
    $response->assertSee('ID');
    $response->assertSee('EN');
});

test('language switcher does not use wire:navigate so persisted topbar reloads cleanly', function () {
    /** @var TestCase $this */
    $html = Livewire::test(LanguageSwitcher::class)->html();

    expect($html)->toContain(route('locale.switch', 'id'))
        ->toContain(route('locale.switch', 'en'))
        ->not->toContain('wire:navigate');
});

test('vendor tracking page renders translated title in english without bare tour call', function () {
    /** @var TestCase $this */
    $this->withSession(['locale' => 'en'])
        ->get(route('frontend.vendor.tracking'))
        ->assertStatus(200)
        ->assertSee('Track PO Document Movement - Pupuk Kalimantan Timur Vendor Portal', false)
        ->assertDontSee('onclick="startAiTour()"', false);
});
