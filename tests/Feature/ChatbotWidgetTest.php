<?php

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderIssued;
use App\Models\Setting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    if (! Schema::hasTable('settings')) {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order_no')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->string('status')->default('Diterima');
            $table->date('received_date')->nullable();
            $table->date('post_103')->nullable();
            $table->text('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('uoi')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('qc_histories')) {
        Schema::create('qc_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('transmittals')) {
        Schema::create('transmittals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->string('transmittal_no')->nullable();
            $table->string('type')->nullable();
            $table->string('destination')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('transmittal_items')) {
        Schema::create('transmittal_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('transmittal_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('locations')) {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_destinations')) {
        Schema::create('warehouse_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittals')) {
        Schema::create('warehouse_transmittals', function (Blueprint $table) {
            $table->id();
            $table->string('transmittal_no')->nullable();
            $table->string('type')->nullable();
            $table->string('destination')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issues')) {
        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('mir_number')->nullable();
            $table->string('diminta_oleh')->nullable();
            $table->string('departemen')->nullable();
            $table->date('tanggal')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtvs')) {
        Schema::create('grs_rdtvs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->date('transaction_date')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('grs_rdtv_id')->nullable();
            $table->string('status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_delay_logs')) {
        Schema::create('delivery_order_receipt_delay_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->text('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->unsignedBigInteger('material_issue_id')->nullable();
            $table->decimal('diserahkan', 12, 3)->default(0);
            $table->string('stage_when_issued')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittal_items')) {
        Schema::create('warehouse_transmittal_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->timestamps();
        });
    }
});

test('chatbot widget component renders on frontend', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertSee('chatbot-widget-container');
});

test('chatbot widget toggles open state', function () {
    Livewire::test('chatbot-widget')
        ->assertSet('isOpen', false)
        ->call('toggleChat')
        ->assertSet('isOpen', true)
        ->call('toggleChat')
        ->assertSet('isOpen', false);
});

test('chatbot widget shows maintenance message when ai system is inactive', function () {
    Setting::updateOrCreate(
        ['key' => 'ai_system_active'],
        ['value' => '0']
    );

    Livewire::test('chatbot-widget')
        ->call('refreshAiStatus')
        ->assertSet('isAiActive', false)
        ->set('message', 'Halo ALEX')
        ->call('sendMessage')
        ->assertSee('Offline / Maintenance');
});

test('chatbot widget renders real-time status stream target when processing message', function () {
    Setting::updateOrCreate(
        ['key' => 'ai_system_active'],
        ['value' => '1']
    );

    Livewire::test('chatbot-widget')
        ->call('refreshAiStatus')
        ->assertSet('isAiActive', true)
        ->set('message', 'Cek status PO 1234567890')
        ->call('sendMessage')
        ->assertSet('isTyping', true)
        ->assertDispatched('process-ai-response')
        ->assertSeeHtml('wire:stream="ai-status-stream"')
        ->assertSeeHtml('wire:stream="ai-reply-stream"')
        ->assertSee('ALEX • Menganalisis...');
});

test('chatbot widget discriminates tool-requiring queries from direct general questions', function () {
    Setting::updateOrCreate(
        ['key' => 'ai_system_active'],
        ['value' => '1']
    );

    $component = Livewire::test('chatbot-widget');
    $instance = $component->instance();
    $reflection = new ReflectionClass($instance);
    $method = $reflection->getMethod('shouldProvideTools');
    $method->setAccessible(true);

    // Should require tools (has specific numbers or search target)
    expect($method->invoke($instance, 'Cek status Penerimaan untuk PO 5300063366'))->toBeTrue();
    expect($method->invoke($instance, 'Lacak DO 12345/SJ/2026'))->toBeTrue();
    expect($method->invoke($instance, 'Cek MIR-2026-001'))->toBeTrue();
    expect($method->invoke($instance, 'Tampilkan daftar riwayat pengambilan barang bulan ini'))->toBeTrue();

    // Should NOT require tools (general questions, SOP, chit-chat, or follow-ups)
    expect($method->invoke($instance, 'jadi boleh diambil tidak?'))->toBeFalse();
    expect($method->invoke($instance, 'kenapa ditolak?'))->toBeFalse();
    expect($method->invoke($instance, 'apa itu MIGO 103?'))->toBeFalse();
    expect($method->invoke($instance, 'apa bedanya GRS dan RDTV?'))->toBeFalse();
    expect($method->invoke($instance, 'Halo ALEX'))->toBeFalse();
    expect($method->invoke($instance, 'Terima kasih bantuannya'))->toBeFalse();
});

test('toolCariDataPenerimaan automatically catches typos with missing zeros and resolves to actual 10-digit SAP PO', function () {
    Setting::updateOrCreate(
        ['key' => 'ai_system_active'],
        ['value' => '1']
    );

    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300063366',
    ]);

    $receipt = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-2026-999',
        'status' => 'RDTV',
        'received_date' => now(),
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $receipt->id,
        'purchase_order_issued_id' => $po->id,
        'material_code' => 'MAT-12345',
        'description' => 'CALIBRATION TOOL OEM',
        'quantity' => 2,
        'uoi' => 'EA',
    ]);

    $component = Livewire::test('chatbot-widget');
    $instance = $component->instance();
    $reflection = new ReflectionClass($instance);
    $method = $reflection->getMethod('toolCariDataPenerimaan');
    $method->setAccessible(true);

    // Test with 9 digits (530063366 missing one zero from 5300063366)
    $result = $method->invoke($instance, '530063366');

    expect($result)->toContain('DETEKSI TYPO NOMOR PO AKTIF');
    expect($result)->toContain('5300063366');
    expect($result)->toContain('DO No:');
});
