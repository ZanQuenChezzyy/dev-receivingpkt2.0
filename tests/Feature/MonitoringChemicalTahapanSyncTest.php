<?php

use App\Filament\Resources\MonitoringChemicals\Schemas\MonitoringChemicalForm;
use App\Models\ChemicalQcTuv;
use App\Models\DeliveryOrderReceipt;
use App\Models\LocationReceiving;
use App\Models\MonitoringChemical;
use App\Models\MonitoringChemicalDetail;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Services\SyncChemicalToDeliveryOrderService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
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

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function ($table) {
            $table->id();
            $table->string('purchase_order_no')->nullable();
            $table->string('item_no')->nullable();
            $table->string('purchase_order_and_item')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty_po', 12, 3)->default(0);
            $table->string('uoi')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('net_price', 18, 2)->default(0);
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('chemical_qc_tuvs')) {
        Schema::create('chemical_qc_tuvs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->string('tahapan_name')->nullable();
            $table->decimal('qty_qc_tuv', 12, 3)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('monitoring_chemicals')) {
        Schema::create('monitoring_chemicals', function ($table) {
            $table->id();
            $table->string('material_category')->nullable();
            $table->string('qc_by')->nullable();
            $table->string('do_number')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->date('received_date')->nullable();
            $table->string('doc_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('monitoring_chemical_details')) {
        Schema::create('monitoring_chemical_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('monitoring_chemical_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->unsignedBigInteger('chemical_qc_tuv_id')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('tahapan')->nullable();
            $table->boolean('is_qty_tolerance')->default(false);
            $table->boolean('has_update_progress')->default(false);
            $table->date('tanggal_pengajuan_simala')->nullable();
            $table->date('tanggal_pengambilan_sample')->nullable();
            $table->date('tanggal_terbit_coa')->nullable();
            $table->integer('leadtime_coa')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('monitoring_npk_id')->nullable();
            $table->unsignedBigInteger('monitoring_chemical_id')->nullable();
            $table->string('material_category')->nullable();
            $table->string('delivery_order_no')->nullable();
            $table->date('received_date')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('source_type')->nullable();
            $table->string('stage')->nullable();
            $table->string('document_code')->nullable();
            $table->dateTime('post_103')->nullable();
            $table->string('qr_103_code')->nullable();
            $table->string('receipt_mode')->nullable();
            $table->string('dof_number')->nullable();
            $table->date('dof_date')->nullable();
            $table->boolean('is_physically_received')->default(false);
            $table->date('physical_received_date')->nullable();
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->date('pending_date')->nullable();
            $table->date('pending_resolved_date')->nullable();
            $table->string('incoterms')->nullable();
            $table->string('current_location')->nullable();
            $table->date('eta_date')->nullable();
            $table->integer('arrival_sequence')->nullable();
            $table->string('document_path')->nullable();
            $table->text('description')->nullable();
            $table->decimal('termin_percentage', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->integer('item_no')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('uoi')->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->string('aac')->nullable();
            $table->string('abc_indicator')->nullable();
            $table->string('requisitioner')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->default(0);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->boolean('is_different_location')->default(false);
            $table->boolean('is_qty_tolerance')->default(false);
            $table->unsignedBigInteger('warehouse_destination_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    $this->user = User::create([
        'name' => 'Tester User',
        'email' => 'tester@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->location = LocationReceiving::create(['name' => 'Gudang Utama']);

    $this->po = PurchaseOrderIssued::create([
        'purchase_order_no' => 'PO-TEST-100',
        'item_no' => '10',
        'purchase_order_and_item' => 'PO-TEST-100-10',
        'material_code' => 'MAT-001',
        'description' => 'Karung 50kg Pupuk',
        'qty_po' => 5000,
        'uoi' => 'BAG',
        'material_type' => 'ZPKG',
        'net_price' => 1000,
        'total_amount_in_lc' => 5000000,
    ]);
});

test('calculateTahapan accurately determines stage for Karung based on TUV targets', function () {
    // Buat target QC Karung
    ChemicalQcTuv::create([
        'purchase_order_issued_id' => $this->po->id,
        'tahapan_name' => 'TAHAP 1 Karung',
        'qty_qc_tuv' => 2000,
    ]);

    ChemicalQcTuv::create([
        'purchase_order_issued_id' => $this->po->id,
        'tahapan_name' => 'TAHAP 2 Karung',
        'qty_qc_tuv' => 3000,
    ]);

    // Penerimaan pertama 1500 (masuk tahap 1)
    $tahap1 = MonitoringChemicalForm::calculateTahapan($this->po->id, 1500, 'Karung');
    expect($tahap1)->toBe('TAHAP 1 Karung');

    // Buat record penerimaan riwayat 1500
    $chem = MonitoringChemical::create([
        'material_category' => 'Karung',
        'qc_by' => 'ISTEK',
        'do_number' => 'DO-001',
        'received_by' => $this->user->id,
        'created_by' => $this->user->id,
        'received_date' => now(),
    ]);

    MonitoringChemicalDetail::create([
        'monitoring_chemical_id' => $chem->id,
        'purchase_order_issued_id' => $this->po->id,
        'quantity' => 1500,
        'tahapan' => 'TAHAP 1 Karung',
        'location_id' => $this->location->id,
    ]);

    // Penerimaan kedua 1000 (sisa tahap 1 adalah 500, sisa 500 masuk tahap 2 -> TAHAP 1,2 Karung)
    $tahapMulti = MonitoringChemicalForm::calculateTahapan($this->po->id, 1000, 'Karung');
    expect($tahapMulti)->toBe('TAHAP 1,2 Karung');

    // Jika history sudah 5000, penerimaan berikutnya melebihi target
    MonitoringChemicalDetail::create([
        'monitoring_chemical_id' => $chem->id,
        'purchase_order_issued_id' => $this->po->id,
        'quantity' => 3500,
        'tahapan' => 'TAHAP 2 Karung',
        'location_id' => $this->location->id,
    ]);

    $tahapPenuh = MonitoringChemicalForm::calculateTahapan($this->po->id, 500, 'Karung');
    expect($tahapPenuh)->toBe('TAHAPAN KARUNG PENUH / MELEBIHI TARGET');
});

test('calculateTahapan automatically generates stage for Chemical based on arrival history', function () {
    // Belum ada riwayat
    $tahap1 = MonitoringChemicalForm::calculateTahapan($this->po->id, 100, 'Chemical');
    expect($tahap1)->toBe('TAHAP 1');

    // Buat riwayat 1 penerimaan
    $chem = MonitoringChemical::create([
        'material_category' => 'Chemical',
        'qc_by' => 'PPE',
        'do_number' => 'DO-CHEM-01',
        'received_by' => $this->user->id,
        'created_by' => $this->user->id,
        'received_date' => now(),
    ]);

    MonitoringChemicalDetail::create([
        'monitoring_chemical_id' => $chem->id,
        'purchase_order_issued_id' => $this->po->id,
        'quantity' => 100,
        'tahapan' => 'TAHAP 1',
        'location_id' => $this->location->id,
    ]);

    // Penerimaan kedua -> TAHAP 2
    $tahap2 = MonitoringChemicalForm::calculateTahapan($this->po->id, 200, 'Chemical');
    expect($tahap2)->toBe('TAHAP 2');
});

test('SyncChemicalToDeliveryOrderService syncs stage and document_code to DeliveryOrderReceipt', function () {
    $chem = MonitoringChemical::create([
        'material_category' => 'Karung',
        'qc_by' => 'ISTEK',
        'do_number' => 'DO-KARUNG-88',
        'received_by' => $this->user->id,
        'created_by' => $this->user->id,
        'received_date' => '2026-10-28',
    ]);

    $detail = MonitoringChemicalDetail::create([
        'monitoring_chemical_id' => $chem->id,
        'purchase_order_issued_id' => $this->po->id,
        'quantity' => 1000,
        'tahapan' => 'TAHAP 1 Karung',
        'location_id' => $this->location->id,
    ]);

    $service = app(SyncChemicalToDeliveryOrderService::class);
    $dor = $service->sync($chem);

    expect($dor)->toBeInstanceOf(DeliveryOrderReceipt::class)
        ->and($dor->stage)->toBe('TAHAP 1 Karung')
        ->and($dor->delivery_order_no)->toBe('DO-KARUNG-88')
        ->and($dor->document_code)->toContain('TAHAP1KARUNG');

    // Update tahapan pada monitoring chemical detail dan sync ulang
    $detail->update(['tahapan' => 'TAHAP 2 Karung']);
    $chem->refresh();

    $updatedDor = $service->sync($chem);
    expect($updatedDor->stage)->toBe('TAHAP 2 Karung')
        ->and($updatedDor->document_code)->toContain('TAHAP2KARUNG');
});
