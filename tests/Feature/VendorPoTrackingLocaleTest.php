<?php

use App\Livewire\Frontend\VendorPoTracking;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDelayLog;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GrsRdtv;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\LocationReceiving;
use App\Models\PurchaseOrderIssued;
use App\Models\QcHistory;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

beforeEach(function (): void {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('vendors')) {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('vendor_number')->nullable();
            $table->text('email')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_order_no')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->integer('item_no')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty_po', 12, 3)->default(0);
            $table->string('uoi')->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->date('date_create')->nullable();
            $table->date('delivery_date_po')->nullable();
            $table->string('po_status')->nullable();
            $table->string('requisitioner')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('net_price', 18, 2)->default(0);
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('location_receivings')) {
        Schema::create('location_receivings', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_destinations')) {
        Schema::create('warehouse_destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('pic_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->date('received_date')->nullable();
            $table->date('physical_received_date')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('stage')->nullable();
            $table->string('document_code')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('post_103')->nullable();
            $table->string('qr_103_code')->nullable();
            $table->string('receipt_mode')->nullable();
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table): void {
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
            $table->decimal('total_amount_snapshot', 18, 2)->default(0);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('warehouse_destination_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('qc_histories')) {
        Schema::create('qc_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtvs')) {
        Schema::create('grs_rdtvs', function (Blueprint $table): void {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('category')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id');
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->string('document_code')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('invoicing_receipts')) {
        Schema::create('invoicing_receipts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_item_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('pic_id')->nullable();
            $table->date('tanggal_terima_grs')->nullable();
            $table->string('jenis')->nullable();
            $table->string('no_grs')->nullable();
            $table->string('no_rdtv')->nullable();
            $table->string('no_po')->nullable();
            $table->string('no_do')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('email_vendor')->nullable();
            $table->string('no_invoice')->nullable();
            $table->date('tanggal_kirim_kasir')->nullable();
            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('dokumen_verifikasi_grs')->nullable();
            $table->string('dokumen_invoice')->nullable();
            $table->json('dokumen_kelengkapan')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_delay_logs')) {
        Schema::create('delivery_order_receipt_delay_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->string('delay_reason');
            $table->text('delay_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_termins')) {
        Schema::create('delivery_order_receipt_termins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->string('stage');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->dateTime('post_103')->nullable();
            $table->string('qr_103_code')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittals')) {
        Schema::create('warehouse_transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('transmittal_no')->nullable();
            $table->unsignedBigInteger('warehouse_destination_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittal_items')) {
        Schema::create('warehouse_transmittal_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_transmittal_id');
            $table->unsignedBigInteger('warehouse_delivery_batch_item_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issues')) {
        Schema::create('material_issues', function (Blueprint $table): void {
            $table->id();
            $table->string('mir_number')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('diminta_oleh')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('material_issue_id');
            $table->unsignedBigInteger('delivery_order_receipt_detail_id')->nullable();
            $table->decimal('diminta', 12, 2)->default(0);
            $table->decimal('diserahkan', 12, 2)->default(0);
            $table->string('stage_when_issued')->nullable();
            $table->timestamps();
        });
    }
});

/**
 * Seed a complete vendor PO tracking scenario for testing.
 *
 * @return array{vendor: Vendor, po: PurchaseOrderIssued, do: DeliveryOrderReceipt}
 */
function seedVendorPoTrackingScenario(): array
{
    $user = User::create([
        'name' => 'Michael Siburian',
        'email' => 'michael@pkt.local',
        'password' => 'secret',
    ]);

    $vendor = Vendor::create([
        'name' => 'MITSUBISHI HEAVY INDUSTRIES ASIA PA',
        'vendor_number' => 'V001',
        'email' => ['finance@mhi-asia.com'],
    ]);

    $po = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300065613',
        'vendor_id' => 'V001',
        'vendor_name' => 'MITSUBISHI HEAVY INDUSTRIES ASIA PA',
        'item_no' => 10,
        'material_code' => 'MAT-1001',
        'description' => 'Compressor Spare Part',
        'qty_po' => 4,
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ERSA',
        'date_create' => '2025-06-13',
        'delivery_date_po' => '2026-10-30',
        'po_status' => 'Active',
        'requisitioner' => 'Plant Ops',
        'currency' => 'USD',
        'total_amount_in_lc' => 125000000,
    ]);

    $location = LocationReceiving::create(['name' => 'Gudang Receiving Utama']);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'PKT-231',
        'received_date' => '2026-10-30',
        'physical_received_date' => '2026-10-30',
        'received_by' => $user->id,
        'created_by' => $user->id,
        'document_code' => '5300065613-04-PKT-231-30102026',
        'status' => 'GRS',
        'post_103' => '2026-11-01 08:39:00',
        'qr_103_code' => '50077067062026',
        'receipt_mode' => 'Standard',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 4,
        'material_code' => 'MAT-1001',
        'description' => 'Compressor Spare Part',
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ERSA',
        'location_id' => $location->id,
    ]);

    $qcSend = QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kirim',
        'created_by' => $user->id,
    ]);
    $qcSend->forceFill(['created_at' => Carbon::parse('2026-11-03 07:37:00')])->saveQuietly();

    $qcBack = QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
        'created_by' => $user->id,
    ]);
    $qcBack->forceFill(['created_at' => Carbon::parse('2026-11-05 14:15:00')])->saveQuietly();

    $grs = GrsRdtv::create([
        'transaction_date' => '2026-11-10',
        'category' => 'GRS',
        'created_by' => $user->id,
    ]);

    GrsRdtvItem::create([
        'grs_rdtv_id' => $grs->id,
        'delivery_order_receipt_id' => $do->id,
        'document_code' => 'GRS-2026-001',
        'status' => 'Issued',
    ]);

    DeliveryOrderReceiptDelayLog::create([
        'delivery_order_receipt_id' => $do->id,
        'delay_reason' => 'PO Belum Confirm',
        'delay_notes' => 'Menunggu konfirmasi pengadaan.',
        'created_by' => $user->id,
    ]);

    InvoicingReceipt::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'vendor_id' => $vendor->id,
        'tanggal_terima_grs' => '2026-11-11',
        'no_po' => '5300065613',
        'no_do' => 'PKT-231',
        'no_invoice' => 'INV-PKT-231',
        'tanggal_kirim_kasir' => '2026-11-12',
        'status' => 'Menunggu Verifikasi',
    ]);

    return [
        'vendor' => $vendor,
        'po' => $po,
        'do' => $do,
    ];
}

test('locale switch preserves valid relative redirect with query params and blocks open redirects', function (): void {
    /** @var TestCase $this */
    $validRedirect = '/lacak-po-vendor?po=5300065613';

    $response = $this->get(route('locale.switch', [
        'locale' => 'en',
        'redirect' => $validRedirect,
    ]));

    $response->assertRedirect($validRedirect);
    $response->assertSessionHas('locale', 'en');
    $response->assertSessionHas('locale_just_switched', true);

    foreach (['//evil.com/phish', '/\\evil.com', 'https://evil.com/phish'] as $maliciousRedirect) {
        $blockedResponse = $this->get(route('locale.switch', [
            'locale' => 'en',
            'redirect' => $maliciousRedirect,
        ]));

        $blockedResponse->assertRedirect(route('home'));
    }

    $page = $this->get(route('frontend.vendor.tracking'));
    $page->assertOk();
    $page->assertSee('window.location.assign($el.href', false);
});

test('verified vendor stays verified when switching locale and requires verification for another vendor PO', function (): void {
    /** @var TestCase $this */
    seedVendorPoTrackingScenario();

    Vendor::create([
        'name' => 'PT LAINNYA',
        'vendor_number' => 'V999',
        'email' => ['other@vendor.com'],
    ]);

    PurchaseOrderIssued::create([
        'purchase_order_no' => '5300099999',
        'vendor_id' => 'V999',
        'vendor_name' => 'PT LAINNYA',
        'item_no' => 10,
        'qty_po' => 1,
        'uoi' => 'EA',
        'po_status' => 'Active',
    ]);

    $component = Livewire::test(VendorPoTracking::class)
        ->set('poNumber', '5300065613')
        ->call('initiateSearch')
        ->assertSet('showVerificationModal', true)
        ->assertSet('isVerified', false)
        ->set('verificationEmail', 'finance@mhi-asia.com')
        ->call('verifyVendorEmail')
        ->assertSet('isVerified', true)
        ->assertSet('showVerificationModal', false);

    expect(session('vendor_po_tracking.verified'))->toMatchArray([
        'email' => 'finance@mhi-asia.com',
        'vendor_number' => 'V001',
        'po_number' => '5300065613',
    ]);

    // Switch locale from EN -> ID with ?po= preserved
    $this->get(route('locale.switch', [
        'locale' => 'id',
        'redirect' => '/lacak-po-vendor?po=5300065613',
    ]))->assertRedirect('/lacak-po-vendor?po=5300065613');

    Livewire::withQueryParams(['po' => '5300065613'])
        ->test(VendorPoTracking::class)
        ->assertSet('isVerified', true)
        ->assertSet('showVerificationModal', false)
        ->assertSet('activePo', '5300065613');

    // Switch locale EN with proxy-stripped query string (locale_just_switched fallback)
    $this->withSession(['locale' => 'en', 'locale_just_switched' => true]);
    App::setLocale('en');

    Livewire::test(VendorPoTracking::class)
        ->assertSet('poNumber', '5300065613')
        ->assertSet('isVerified', true)
        ->assertSet('showVerificationModal', false);

    // Searching a different vendor's PO still requires verification
    Livewire::withQueryParams(['po' => '5300099999'])
        ->test(VendorPoTracking::class)
        ->assertSet('isVerified', false)
        ->assertSet('showVerificationModal', true);

    // Resetting search forgets verified session state
    $component->call('resetSearch');
    expect(session('vendor_po_tracking.verified'))->toBeNull();
});

test('vendor PO tracking timeline, checklist, pipeline stage, and material status render in english when locale is en and indonesian when id', function (): void {
    /** @var TestCase $this */
    seedVendorPoTrackingScenario();

    $verifiedSession = [
        'vendor_po_tracking.verified' => [
            'email' => 'finance@mhi-asia.com',
            'vendor_number' => 'V001',
            'po_number' => '5300065613',
        ],
    ];

    // English rendering check
    $this->withSession([...$verifiedSession, 'locale' => 'en']);
    App::setLocale('en');
    Carbon::setLocale('en');

    $enComponent = Livewire::withQueryParams(['po' => '5300065613'])
        ->test(VendorPoTracking::class)
        ->assertSet('isVerified', true)
        ->assertSee('Purchase Order (PO) Issued')
        ->assertSee('Physical Goods Arrival')
        ->assertSee('Post 103 (QC Submission)')
        ->assertSee('Documents Sent from Receiving to ISTEK (QC Transmittal Out)')
        ->assertSee('Documents Returned from ISTEK to Receiving (QC Transmittal In)')
        ->assertSee('GRS Document Issued')
        ->assertSee('Delay: PO Not Confirmed Yet')
        ->assertSee('Invoice Document Receipt (Invoicing)')
        ->assertSee('Documents Sent to Cashier for Payment')
        ->assertSee('30 Oct 2026')
        ->assertSee('ORIGINAL PO')
        ->assertSee('Fully Received')
        ->assertDontSee('Kedatangan Fisik Barang')
        ->assertDontSee('Purchase Order (PO) Diterbitkan');

    $enData = $enComponent->get('trackingData');
    expect($enData['pipeline']['current_stage_label'])->toBe('GRS Issued (105)');
    expect($enData['material_items'][0]['status_text'])->toBe('Fully Received');
    expect($enData['checklist_definitions']['po_asli']['badge'])->toBe('Mandatory');

    // Full HTTP response check when verified in EN session
    $httpResponse = $this->withSession([...$verifiedSession, 'locale' => 'en'])
        ->get(route('frontend.vendor.tracking', ['po' => '5300065613']));
    $httpResponse->assertOk();
    $httpResponse->assertSee('Document Movement History');
    $httpResponse->assertSee('Purchase Order (PO) Issued');
    $httpResponse->assertDontSee('Kedatangan Fisik Barang');

    // Indonesian rendering check
    $this->withSession([...$verifiedSession, 'locale' => 'id']);
    App::setLocale('id');
    Carbon::setLocale('id');

    $idComponent = Livewire::withQueryParams(['po' => '5300065613'])
        ->test(VendorPoTracking::class)
        ->assertSet('isVerified', true)
        ->assertSee('Purchase Order (PO) Diterbitkan')
        ->assertSee('Kedatangan Fisik Barang')
        ->assertSee('Post 103 (Pengajuan QC)')
        ->assertSee('Dokumen Dikirim dari Receiving ke ISTEK (Transmittal QC Kirim)')
        ->assertSee('30 Okt 2026')
        ->assertSee('PO ASLI')
        ->assertSee('Diterima Lengkap');

    $idData = $idComponent->get('trackingData');
    expect($idData['pipeline']['current_stage_label'])->toBe('GRS Terbit (105)');
    expect($idData['checklist_definitions']['po_asli']['badge'])->toBe('Wajib');
});
