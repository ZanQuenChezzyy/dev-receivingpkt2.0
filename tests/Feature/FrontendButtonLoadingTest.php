<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasTable('settings')) {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('npk')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('roles')) {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('model_has_roles')) {
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function (Blueprint $table): void {
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
        Schema::create('location_receivings', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
            $table->string('stage')->nullable();
            $table->date('pending_date')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table): void {
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

    if (! Schema::hasTable('requesting_units')) {
        Schema::create('requesting_units', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->decimal('diserahkan', 12, 3)->default(0);
            $table->timestamps();
        });
    }
});

it('renders the frontend top progress bar and vite bundle on public pages', function (string $routeName): void {
    $response = $this->get(route($routeName));

    $response->assertSuccessful();
    $response->assertSee('id="pkt-page-load-bar"', false);
})->with([
    'home' => ['home'],
    'list material' => ['frontend.list-material'],
    'lacak po vendor' => ['frontend.vendor.tracking'],
    'form mir' => ['frontend.mir.create'],
]);

it('builds frontend button loader script and styles into the vite manifest', function (): void {
    $manifestPath = public_path('build/manifest.json');
    expect(file_exists($manifestPath))->toBeTrue();

    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    expect($manifest)->toHaveKeys(['resources/js/app.js', 'resources/css/app.css']);

    $jsAsset = public_path('build/'.$manifest['resources/js/app.js']['file']);
    $cssAsset = public_path('build/'.$manifest['resources/css/app.css']['file']);

    expect(file_exists($jsAsset))->toBeTrue()
        ->and(file_exists($cssAsset))->toBeTrue();

    $jsContent = (string) file_get_contents($jsAsset);
    $cssContent = (string) file_get_contents($cssAsset);

    expect($jsContent)
        ->toContain('is-btn-loading')
        ->toContain('is-btn-loading-compact')
        ->toContain('btn-inline-spinner')
        ->toContain('pkt-page-load-bar')
        ->toContain('livewire:navigated');

    expect($cssContent)
        ->toContain('.is-btn-loading')
        ->toContain('.is-btn-loading-compact')
        ->toContain('.btn-inline-spinner')
        ->toContain('#pkt-page-load-bar');
});
