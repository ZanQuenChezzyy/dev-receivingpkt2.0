<?php

use App\Livewire\Frontend\PdNonstockList;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

beforeEach(function () {
    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function ($table) {
            $table->id();
            $table->string('purchase_order_no')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('item_no')->nullable();
            $table->decimal('qty_po', 12, 3)->default(0);
            $table->string('uoi')->nullable();
            $table->string('description')->nullable();
            $table->string('material_code')->nullable();
            $table->string('requisitioner')->nullable();
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('location_receivings')) {
        Schema::create('location_receivings', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function ($table) {
            $table->id();
            $table->string('status')->nullable();
            $table->string('stage')->nullable();
            $table->date('pending_date')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->unsignedBigInteger('location_receiving_id')->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->string('item_no')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->decimal('diserahkan', 12, 3)->default(0);
            $table->timestamps();
        });
    }
});

test('it renders material list page with default PD tab', function () {
    /** @var TestCase $this */
    $response = $this->get(route('frontend.list-material'));

    $response->assertStatus(200);
    $response->assertSee('Material PD');
    $response->assertSee('Material Non-Stock');
});

test('it switches tab to NONSTOCK and back to PD reactively', function () {
    Livewire::test(PdNonstockList::class)
        ->assertSet('activeTab', 'PD')
        ->assertSee('transform: translateX(0%)')
        ->call('setTab', 'NONSTOCK')
        ->assertSet('activeTab', 'NONSTOCK')
        ->assertSee('transform: translateX(100%)')
        ->call('setTab', 'PD')
        ->assertSet('activeTab', 'PD')
        ->assertSee('transform: translateX(0%)');
});

test('it toggles layout between grid and table', function () {
    Livewire::test(PdNonstockList::class)
        ->assertSet('layout', 'grid')
        ->set('layout', 'table')
        ->assertSet('layout', 'table')
        ->set('layout', 'grid')
        ->assertSet('layout', 'grid');
});

test('it accepts activeTab from query string', function () {
    Livewire::withQueryParams(['activeTab' => 'NONSTOCK'])
        ->test(PdNonstockList::class)
        ->assertSet('activeTab', 'NONSTOCK')
        ->assertSee('transform: translateX(100%)');
});

test('material list page renders english title and guarded footer tour trigger', function () {
    /** @var TestCase $this */
    if (! Schema::hasTable('settings')) {
        Schema::create('settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    Setting::updateOrCreate(['key' => 'ai_system_active'], ['value' => '1']);

    $this->withSession(['locale' => 'en'])
        ->get(route('frontend.list-material'))
        ->assertStatus(200)
        ->assertSee('PD &amp; Non-Stock Material List', false)
        ->assertDontSee('onclick="startAiTour()"', false)
        ->assertSee('window.startAiTour', false);
});
