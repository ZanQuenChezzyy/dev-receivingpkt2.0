<?php

use App\Filament\Imports\InvoicingReceiptImporter;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\Vendor;
use App\Services\InvoicingAssignmentService;
use App\Services\InvoicingCsvImportService;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('invoicing_receipts');
    Schema::dropIfExists('grs_rdtv_items');
    Schema::dropIfExists('grs_rdtvs');
    Schema::dropIfExists('delivery_order_receipt_details');
    Schema::dropIfExists('delivery_order_receipts');
    Schema::dropIfExists('purchase_order_issueds');
    Schema::dropIfExists('vendors');

    Schema::create('vendors', function ($table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('vendor_number')->nullable();
        $table->text('email')->nullable();
        $table->timestamps();
    });

    Schema::create('purchase_order_issueds', function ($table) {
        $table->id();
        $table->string('purchase_order_and_item')->nullable();
        $table->string('mrp_type')->nullable();
        $table->string('material_type')->nullable();
        $table->string('purchase_order_no')->nullable();
        $table->integer('item_no')->nullable();
        $table->string('material_code')->nullable();
        $table->string('description')->nullable();
        $table->decimal('qty_po', 12, 3)->default(0);
        $table->string('uoi')->nullable();
        $table->string('vendor_id')->nullable();
        $table->string('vendor_name')->nullable();
        $table->date('date_create')->nullable();
        $table->date('delivery_date_po')->nullable();
        $table->decimal('net_price', 18, 2)->nullable();
        $table->decimal('total_amount_in_lc', 18, 2)->default(0);
        $table->string('requisitioner')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_order_receipts', function ($table) {
        $table->id();
        $table->string('delivery_order_no')->nullable();
        $table->date('received_date')->nullable();
        $table->date('physical_received_date')->nullable();
        $table->boolean('is_physically_received')->default(true);
        $table->string('source_type')->nullable();
        $table->string('document_code')->nullable();
        $table->string('status')->nullable();
        $table->string('receipt_mode')->nullable();
        $table->timestamps();
    });

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
        $table->decimal('total_amount_snapshot', 18, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('grs_rdtvs', function ($table) {
        $table->id();
        $table->date('transaction_date')->nullable();
        $table->string('category')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();
    });

    Schema::create('grs_rdtv_items', function ($table) {
        $table->id();
        $table->unsignedBigInteger('grs_rdtv_id')->nullable();
        $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
        $table->string('document_code')->nullable();
        $table->string('file_path')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    Schema::create('invoicing_receipts', function ($table) {
        $table->id();
        $table->unsignedBigInteger('grs_rdtv_item_id')->nullable();
        $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
        $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
        $table->unsignedBigInteger('vendor_id')->nullable();
        $table->unsignedBigInteger('pic_id')->nullable();
        $table->date('tanggal_terima_grs')->nullable();
        $table->string('jenis')->default('GRS');
        $table->string('no_grs')->nullable();
        $table->string('no_rdtv')->nullable();
        $table->string('no_po')->nullable();
        $table->string('no_do')->nullable();
        $table->string('padi')->nullable();
        $table->string('material_type')->nullable();
        $table->string('vendor_name')->nullable();
        $table->text('deskripsi')->nullable();
        $table->string('email_vendor')->nullable();
        $table->date('tanggal_info_grs')->nullable();
        $table->string('no_invoice')->nullable();
        $table->string('faktur_pajak')->nullable();
        $table->text('nama_barang')->nullable();
        $table->decimal('qty_penagihan', 15, 4)->nullable();
        $table->decimal('qty_po', 15, 4)->nullable();
        $table->string('uoi', 20)->nullable();
        $table->decimal('subtotal_exclude_ppn', 18, 2)->nullable();
        $table->decimal('subtotal_include_ppn', 18, 2)->nullable();
        $table->date('tanggal_terima_koreksi_invoice')->nullable();
        $table->date('tanggal_kirim_kasir')->nullable();
        $table->integer('lama_pekerjaan')->nullable();
        $table->text('keterangan')->nullable();
        $table->string('dokumen_verifikasi_grs')->nullable();
        $table->string('dokumen_invoice')->nullable();
        $table->json('dokumen_kelengkapan')->nullable();
        $table->string('status')->default('Menunggu Verifikasi');
        $table->timestamps();
    });

    // Mock InvoicingAssignmentService agar tidak memerlukan tabel Spatie Permission di SQLite memory
    $mockAssignment = Mockery::mock(InvoicingAssignmentService::class);
    $mockAssignment->shouldReceive('determinePicId')->andReturn(null);
    app()->instance(InvoicingAssignmentService::class, $mockAssignment);
});

function sampleMitrakuMultiSectionCsv(): string
{
    return <<<'CSV'
Invoice Number,SAP Document Number,Transaction Type,Vendor Code,Vendor Name,Currency,Invoice Amount,Net Amount,Total Deduction,Total Adjustment,Receivable Details,Deduction Details,Adjustment Details,Tax Verification Notes,Amount Paid,Invoice Date,Payment Date,Clearing Document Number,Payment Term,Estimate Payment Date,Area,Work Unit,BG Number,BG Valid,BG Expiry Range,Status,Created By,Created On,Priority,Any AR,Vendor NPWP,Description,Invoice Year,Payment Method,Total Receivable,Exchange Rate Used,PO Numbers,Tax Number,Tax Date,Tax Status,Tax Approval Date,Tax Certificate Number,Transmittal Cashier to Accounting,Transmittal Cashier to Accounting Date,Transmittal Final Verification to Finance,Transmittal Final Verification to Finance Date,Receivable Documents,Deduction Category,Deduction Description,Sub Transaction Type,Sub Transaction Description,Is Cover
INV-20260036,,PO Barang,1000022010,ERDY JAYA MAKMUR,IDR,37260000,,0,0,,,,,0,2026-11-02,,,,,Pusat,"Dept. Perencanaan, Penerimaan & Pergudangan",,,,PO Submitted,,2026-11-04 00:06:55,No,No,0601253586724000,"PEMBELIAN WIREMESH, KNITTED",2026,Telegraphic Transfer (TT),0,,5300066895,03002600453809710,01-11-2026,Waiting validation,,TR-999,2026-11-05,,,,,,,,Non Raw Materials,"WIREMESH, KNITTED",
200.0126.000269,,PO Jasa Distribusi,1000013741,MULTI TERMINAL INDONESIA,IDR,1391092560,,0,0,,,,,0,2026-10-26,,,,,Pusat,"Dept. Perencanaan, Penerimaan & Pergudangan",,,,PO Drafted,,2026-11-04 01:59:21,No,No,0021066204093000,Jasa Angkutan Bahan Baku NPK dari Surabaya ke Bontang (DTPS) Periode Bulan Agustus Tahap 2 Km Hasil Bahari 9 (80%),2026,Telegraphic Transfer (TT),0,,5320073779,03002600446812643,26-10-2026,Waiting validation,,,,,,,,,,,,
Invoice Number,Date & Time,Action,Status,User,Role,Notes
INV-20260036,2026-11-04 00:06:55,Drafted,PO Drafted,Husdayanti,Inputter,"PEMBELIAN WIREMESH, KNITTED"
,2026-11-04 00:06:55,Submitted,PO Submitted,Husdayanti,Inputter,
200.0126.000269,2026-11-04 01:59:22,Drafted,PO Drafted,Rizki Ardita,Inputter,Jasa Angkutan Bahan Baku NPK dari Surabaya ke Bontang (DTPS) Periode Bulan Agustus Tahap 2 Km Hasil Bahari 9 (80%)
Invoice Number,Transaction Type,PO Number,PO Type,PO Currency,PO Amount,Contract Number,Contract Amount,GR / SA / BA-BL Number,GR / SA / BA-BL Date,Delivery / BATE / SIK-STO Date,DO / BAPP / Timesheet Number,Receipt-DO / BAPP / Arrival Deadline Date,Completion / Actual / Arrival Date,Payment Type,Type of Transport,Manual Entry,PO Notes
INV-20260036,PO Barang,5300066895,PO PKT (Pupuk Kaltim),IDR,37260000,5300066895,37260000,5007705001,2026-10-31,2026-12-22,202600026,2026-10-17,,,,Yes,
200.0126.000269,PO Jasa Distribusi,5320073779,PO PI (Pupuk Indonesia),IDR,1391092560,4000000838,1391092560,09/SBY-BXT/VIII/2026,2026-08-18,2026-08-18,,2026-08-18,2026-08-18,,Sea Transport,Yes,Jasa Angkutan Bahan Baku NPK dari Surabaya ke Bontang (DTPS) Periode Bulan Agustus Tahap 2 Km Hasil Bahari 9 (80%)
CSV;
}

it('auto-links by PO and arrival date even when admin inputs random DO number, and ignores Created On, Transmittal Cashier Date, Table 2, and GR Date', function () {
    $vendor = Vendor::create([
        'vendor_number' => '1000022010',
        'name' => 'ERDY JAYA MAKMUR',
        'email' => ['erdy@vendor.co.id'],
    ]);

    $po = PurchaseOrderIssued::create([
        'purchase_order_and_item' => '5300066895-10',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'purchase_order_no' => '5300066895',
        'item_no' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'qty_po' => 10,
        'uoi' => 'EA',
        'vendor_id' => '1000022010',
        'vendor_name' => 'ERDY JAYA MAKMUR',
        'date_create' => '2026-10-01',
        'delivery_date_po' => '2026-12-22',
        'total_amount_in_lc' => 37260000,
        'requisitioner' => 'BENGKEL',
    ]);

    // Admin penerimaan DO memasukkan nomor DO sembarangan ('DO-ASAL-KETIK-99'),
    // tetapi tanggal terima barang (received_date) sesuai yaitu 2026-10-17.
    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-ASAL-KETIK-99',
        'received_date' => '2026-10-17',
        'physical_received_date' => '2026-10-17',
        'is_physically_received' => true,
        'source_type' => 'Sparepart',
        'document_code' => '5300066895-01-DOASAL-17102026',
        'status' => 'GRS',
        'receipt_mode' => 'Standard',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'total_amount_snapshot' => 37260000,
    ]);

    // Buat DO ke-2 di tanggal berbeda untuk memastikan sistem memilih yang tanggalnya 2026-10-17
    $doOtherDate = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-LAIN-88',
        'received_date' => '2026-10-01',
        'physical_received_date' => '2026-10-01',
        'is_physically_received' => true,
        'source_type' => 'Sparepart',
        'document_code' => '5300066895-02-DOLAIN-01102026',
        'status' => 'GRS',
        'receipt_mode' => 'Standard',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $doOtherDate->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 5,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'total_amount_snapshot' => 18630000,
    ]);

    $service = app(InvoicingCsvImportService::class);
    $summary = $service->importFromString(sampleMitrakuMultiSectionCsv(), onlyPoBarang: true);

    expect($summary['created'])->toBe(1)
        ->and($summary['linked_do'])->toBe(1)
        ->and($summary['skipped'])->toBe(1); // PO Jasa Distribusi dilewati

    $invoice = InvoicingReceipt::where('no_invoice', 'INV-20260036')->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->delivery_order_receipt_id)->toBe($do->id)
        ->and($invoice->purchase_order_issued_id)->toBe($po->id)
        ->and($invoice->vendor_id)->toBe($vendor->id)
        ->and($invoice->no_po)->toBe('5300066895')
        ->and($invoice->no_grs)->toBe('5007705001')
        ->and($invoice->faktur_pajak)->toBe('03002600453809710')
        ->and((float) $invoice->subtotal_exclude_ppn)->toBe(37260000.0)
        ->and((float) $invoice->subtotal_include_ppn)->toBe(41358600.0)
        // Pastikan Created On, Transmittal Cashier Date, dan GR Date TIDAK masuk ke kolom tanggal internal
        ->and($invoice->tanggal_terima_koreksi_invoice)->toBeNull()
        ->and($invoice->tanggal_kirim_kasir)->toBeNull()
        ->and($invoice->tanggal_terima_grs)->toBeNull();
});

it('falls back to matching by DO number when arrival date does not match, and updates existing GRS-generated InvoicingReceipt without duplicating', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_and_item' => '5300066895-10',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'purchase_order_no' => '5300066895',
        'item_no' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'qty_po' => 10,
        'uoi' => 'EA',
        'vendor_id' => '1000022010',
        'vendor_name' => 'ERDY JAYA MAKMUR',
        'date_create' => '2026-10-01',
        'delivery_date_po' => '2026-12-22',
        'total_amount_in_lc' => 37260000,
        'requisitioner' => 'BENGKEL',
    ]);

    // Ada 2 DO pada PO yang sama, keduanya tanggalnya TIDAK sesuai dengan CSV (bukan 2026-10-17),
    // tetapi salah satunya memiliki Nomor DO yang sesuai dengan CSV ('202600026').
    $doWrongNumber = DeliveryOrderReceipt::create([
        'delivery_order_no' => '99999999',
        'received_date' => '2026-10-20',
        'physical_received_date' => '2026-10-20',
        'is_physically_received' => true,
        'source_type' => 'Sparepart',
        'document_code' => '5300066895-01-99999999-20102026',
        'status' => 'GRS',
        'receipt_mode' => 'Standard',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $doWrongNumber->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 5,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'total_amount_snapshot' => 18630000,
    ]);

    $doMatchingNumber = DeliveryOrderReceipt::create([
        'delivery_order_no' => '202600026',
        'received_date' => '2026-10-25', // Berbeda dari 2026-10-17 di CSV
        'physical_received_date' => '2026-10-25',
        'is_physically_received' => true,
        'source_type' => 'Sparepart',
        'document_code' => '5300066895-02-202600026-25102026',
        'status' => 'GRS',
        'receipt_mode' => 'Standard',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $doMatchingNumber->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'total_amount_snapshot' => 37260000,
    ]);

    // Simulasikan InvoicingReceipt yang sudah ter-generate otomatis dari upload slip GRS sebelumnya
    $existingInvoice = InvoicingReceipt::create([
        'delivery_order_receipt_id' => $doMatchingNumber->id,
        'purchase_order_issued_id' => $po->id,
        'tanggal_terima_grs' => '2026-11-01',
        'jenis' => 'GRS',
        'no_grs' => null,
        'no_po' => '5300066895',
        'no_do' => '202600026',
        'no_invoice' => null,
        'faktur_pajak' => null,
        'status' => 'Menunggu Verifikasi',
    ]);

    $service = app(InvoicingCsvImportService::class);
    $summary = $service->importFromString(sampleMitrakuMultiSectionCsv(), onlyPoBarang: true);

    expect($summary['updated'])->toBe(1)
        ->and($summary['created'])->toBe(0)
        ->and($summary['linked_do'])->toBe(1)
        ->and(InvoicingReceipt::count())->toBe(1);

    $existingInvoice->refresh();

    expect($existingInvoice->delivery_order_receipt_id)->toBe($doMatchingNumber->id)
        ->and($existingInvoice->no_invoice)->toBe('INV-20260036')
        ->and($existingInvoice->no_grs)->toBe('5007705001')
        ->and($existingInvoice->faktur_pajak)->toBe('03002600453809710')
        ->and($existingInvoice->tanggal_terima_grs->format('Y-m-d'))->toBe('2026-11-01');
});

it('normalizes multi-section Mitraku CSV and imports rows via Filament InvoicingReceiptImporter', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_and_item' => '5300066895-10',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'purchase_order_no' => '5300066895',
        'item_no' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'qty_po' => 10,
        'uoi' => 'EA',
        'vendor_id' => '1000022010',
        'vendor_name' => 'ERDY JAYA MAKMUR',
        'date_create' => '2026-10-01',
        'delivery_date_po' => '2026-12-22',
        'total_amount_in_lc' => 37260000,
        'requisitioner' => 'BENGKEL',
    ]);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-RANDOM-123',
        'received_date' => '2026-10-17',
        'physical_received_date' => '2026-10-17',
        'is_physically_received' => true,
        'source_type' => 'Sparepart',
        'document_code' => '5300066895-01-DORANDOM-17102026',
        'status' => 'GRS',
        'receipt_mode' => 'Standard',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'total_amount_snapshot' => 37260000,
    ]);

    $service = app(InvoicingCsvImportService::class);
    expect($service->isMultiSectionMitrakuCsv(sampleMitrakuMultiSectionCsv()))->toBeTrue();

    $flattened = $service->flattenMultiSectionCsvToString(sampleMitrakuMultiSectionCsv());
    expect($service->isMultiSectionMitrakuCsv($flattened))->toBeFalse();

    // Baca CSV hasil flatten seperti yang dilakukan Filament ImportAction
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $flattened);
    rewind($stream);

    $headers = fgetcsv($stream);
    $rows = [];
    while (($row = fgetcsv($stream)) !== false) {
        $rows[] = array_combine($headers, $row);
    }
    fclose($stream);

    // Hanya ada 2 baris gabungan (INV-20260036 dan 200.0126.000269), Tabel 2 (Log Aksi) sudah dibuang
    expect(count($rows))->toBe(2);

    $columnMap = [
        'no_invoice' => 'Invoice Number',
        'transaction_type' => 'Transaction Type',
        'no_po' => 'PO Number',
        'vendor_code' => 'Vendor Code',
        'vendor_name' => 'Vendor Name',
        'faktur_pajak' => 'Tax Number',
        'subtotal_exclude_ppn' => 'PO Amount',
        'deskripsi' => 'Description',
        'no_grs' => 'GR / SA / BA-BL Number',
        'no_do' => 'DO / BAPP / Timesheet Number',
        'arrival_deadline_date' => 'Receipt-DO / BAPP / Arrival Deadline Date',
        'actual_arrival_date' => 'Completion / Actual / Arrival Date',
        'created_by' => 'Created By',
    ];

    $importModel = new Import;
    $importer = new InvoicingReceiptImporter(
        import: $importModel,
        columnMap: $columnMap,
        options: ['only_po_barang' => true],
    );

    foreach ($rows as $row) {
        $importer($row);
    }

    // Hanya PO Barang (INV-20260036) yang masuk, PO Jasa Distribusi dilewati
    expect(InvoicingReceipt::count())->toBe(1);

    $invoice = InvoicingReceipt::first();
    expect($invoice->no_invoice)->toBe('INV-20260036')
        ->and($invoice->delivery_order_receipt_id)->toBe($do->id)
        ->and($invoice->no_grs)->toBe('5007705001')
        ->and($invoice->faktur_pajak)->toBe('03002600453809710');
});

it('directly normalizes and imports a multi-sheet Excel (.xlsx) workbook without requiring Save As CSV', function () {
    $po = PurchaseOrderIssued::create([
        'purchase_order_and_item' => '5300066895-10',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'purchase_order_no' => '5300066895',
        'item_no' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'qty_po' => 10,
        'uoi' => 'EA',
        'vendor_id' => '1000022010',
        'vendor_name' => 'ERDY JAYA MAKMUR',
        'date_create' => '2026-10-01',
        'delivery_date_po' => '2026-12-22',
        'total_amount_in_lc' => 37260000,
        'requisitioner' => 'BENGKEL',
    ]);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-XLSX-999',
        'received_date' => '2026-10-17',
        'physical_received_date' => '2026-10-17',
        'is_physically_received' => true,
        'source_type' => 'Sparepart',
        'document_code' => '5300066895-01-DOXLSX-17102026',
        'status' => 'GRS',
        'receipt_mode' => 'Standard',
    ]);

    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 10,
        'material_code' => '60001234',
        'description' => 'WIREMESH, KNITTED',
        'uoi' => 'EA',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'total_amount_snapshot' => 37260000,
    ]);

    // Buat file .xlsx sementara dengan 3 worksheet terpisah (Sheet 1: Header, Sheet 2: Log History, Sheet 3: Detail PO/GRS/DO)
    $tempXlsxPath = tempnam(sys_get_temp_dir(), 'mitraku_xlsx_');
    $zip = new ZipArchive;
    $zip->open($tempXlsxPath, ZipArchive::OVERWRITE);

    $sheet1Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>Invoice Number</t></is></c>
      <c r="B1" t="inlineStr"><is><t>SAP Document Number</t></is></c>
      <c r="C1" t="inlineStr"><is><t>Transaction Type</t></is></c>
      <c r="D1" t="inlineStr"><is><t>Vendor Code</t></is></c>
      <c r="E1" t="inlineStr"><is><t>Vendor Name</t></is></c>
      <c r="F1" t="inlineStr"><is><t>Invoice Amount</t></is></c>
      <c r="G1" t="inlineStr"><is><t>Description</t></is></c>
      <c r="H1" t="inlineStr"><is><t>PO Numbers</t></is></c>
      <c r="I1" t="inlineStr"><is><t>Tax Number</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>INV-20260036</t></is></c>
      <c r="B2" t="inlineStr"><is><t></t></is></c>
      <c r="C2" t="inlineStr"><is><t>PO Barang</t></is></c>
      <c r="D2" t="inlineStr"><is><t>1000022010</t></is></c>
      <c r="E2" t="inlineStr"><is><t>ERDY JAYA MAKMUR</t></is></c>
      <c r="F2"><v>37260000</v></c>
      <c r="G2" t="inlineStr"><is><t>PEMBELIAN WIREMESH, KNITTED</t></is></c>
      <c r="H2" t="inlineStr"><is><t>5300066895</t></is></c>
      <c r="I2" t="inlineStr"><is><t>03002600453809710</t></is></c>
    </row>
  </sheetData>
</worksheet>
XML;

    $sheet2Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>Invoice Number</t></is></c>
      <c r="B1" t="inlineStr"><is><t>Date &amp; Time</t></is></c>
      <c r="C1" t="inlineStr"><is><t>Action</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>INV-20260036</t></is></c>
      <c r="B2" t="inlineStr"><is><t>2026-11-04 00:06:55</t></is></c>
      <c r="C2" t="inlineStr"><is><t>Submitted</t></is></c>
    </row>
  </sheetData>
</worksheet>
XML;

    $sheet3Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>Invoice Number</t></is></c>
      <c r="B1" t="inlineStr"><is><t>Transaction Type</t></is></c>
      <c r="C1" t="inlineStr"><is><t>PO Number</t></is></c>
      <c r="D1" t="inlineStr"><is><t>PO Amount</t></is></c>
      <c r="E1" t="inlineStr"><is><t>GR / SA / BA-BL Number</t></is></c>
      <c r="F1" t="inlineStr"><is><t>DO / BAPP / Timesheet Number</t></is></c>
      <c r="G1" t="inlineStr"><is><t>Receipt-DO / BAPP / Arrival Deadline Date</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>INV-20260036</t></is></c>
      <c r="B2" t="inlineStr"><is><t>PO Barang</t></is></c>
      <c r="C2" t="inlineStr"><is><t>5300066895</t></is></c>
      <c r="D2"><v>37260000</v></c>
      <c r="E2" t="inlineStr"><is><t>5007705001</t></is></c>
      <c r="F2" t="inlineStr"><is><t>202600026</t></is></c>
      <c r="G2" t="inlineStr"><is><t>2026-10-17</t></is></c>
    </row>
  </sheetData>
</worksheet>
XML;

    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
    $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);
    $zip->addFromString('xl/worksheets/sheet3.xml', $sheet3Xml);
    $zip->close();

    try {
        $service = app(InvoicingCsvImportService::class);
        $service->normalizeMitrakuCsvFileInPlace($tempXlsxPath);

        $normalizedContent = file_get_contents($tempXlsxPath);
        expect($service->isXlsxContent($normalizedContent))->toBeFalse()
            ->and($normalizedContent)->toContain('GR / SA / BA-BL Number')
            ->and($normalizedContent)->toContain('5007705001');

        $summary = $service->importFromFile($tempXlsxPath, onlyPoBarang: true);
        expect($summary['created'])->toBe(1)
            ->and($summary['linked_do'])->toBe(1);

        $invoice = InvoicingReceipt::where('no_invoice', 'INV-20260036')->first();
        expect($invoice)->not->toBeNull()
            ->and($invoice->delivery_order_receipt_id)->toBe($do->id)
            ->and($invoice->no_grs)->toBe('5007705001')
            ->and($invoice->faktur_pajak)->toBe('03002600453809710');
    } finally {
        @unlink($tempXlsxPath);
    }
});
