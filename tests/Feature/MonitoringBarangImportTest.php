<?php

use App\Filament\Resources\InvoicingReceipts\Pages\ListInvoicingReceipts;
use App\Filament\Resources\InvoicingReceipts\Tables\InvoicingReceiptsTable;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\InvoicingReceipt;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InvoicingCsvImportService;
use App\Services\MonitoringBarangImportService;
use Filament\Tables\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    Schema::dropIfExists('invoicing_receipts');
    Schema::dropIfExists('grs_rdtv_items');
    Schema::dropIfExists('grs_rdtvs');
    Schema::dropIfExists('delivery_order_receipt_details');
    Schema::dropIfExists('delivery_order_receipts');
    Schema::dropIfExists('purchase_order_issueds');
    Schema::dropIfExists('vendors');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->string('npk')->nullable();
        $table->string('avatar_url')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    if (! Schema::hasTable('permissions')) {
        $permissionMigration = include database_path('migrations/2026_05_15_101350_create_permission_tables.php');
        $permissionMigration->up();
    }

    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('vendor_number')->nullable();
        $table->text('email')->nullable();
        $table->timestamps();
    });

    Schema::create('purchase_order_issueds', function (Blueprint $table) {
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

    Schema::create('delivery_order_receipts', function (Blueprint $table) {
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

    Schema::create('delivery_order_receipt_details', function (Blueprint $table) {
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

    Schema::create('grs_rdtvs', function (Blueprint $table) {
        $table->id();
        $table->date('transaction_date')->nullable();
        $table->string('category')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();
    });

    Schema::create('grs_rdtv_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('grs_rdtv_id')->nullable();
        $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
        $table->string('document_code')->nullable();
        $table->string('file_path')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    $createInvoicingMigration = include database_path('migrations/2026_10_24_121247_create_invoicing_receipts_table.php');
    $createInvoicingMigration->up();
    $addInvoiceDocMigration = include database_path('migrations/2026_10_24_135117_add_dokumen_invoice_to_invoicing_receipts_table.php');
    $addInvoiceDocMigration->up();
    $addKelengkapanMigration = include database_path('migrations/2026_10_26_194128_add_dokumen_kelengkapan_to_invoicing_receipts_table.php');
    $addKelengkapanMigration->up();
    $addKategoriMigration = include database_path('migrations/2026_11_17_182934_add_kategori_barang_to_invoicing_receipts_table.php');
    $addKategoriMigration->up();
});

function seedMonitoringPicUsers(): array
{
    $emails = [
        'alex@receiving.com' => 'Alex',
        'camel@receiving.com' => 'Camel',
        'husdayanti@receiving.com' => 'Husdayanti',
        'eva@receiving.com' => 'Eva',
        'nafisa@receiving.com' => 'Nafisa',
        'rizki@receiving.com' => 'Rizki',
        'wahyu@receiving.com' => 'Wahyu',
    ];

    $users = [];
    foreach ($emails as $email => $name) {
        $users[$email] = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    return $users;
}

function sampleMonitoringBarangCsv(): string
{
    return <<<'CSV'
 ,Tanggal Terima GRS,Jenis,No. GRS,No. RDTV,No. PO,PIC,Vendor,Deskripsi,Email,Tanggal Info GRS,,Vendor,PO,No. Invoice,No. DO,Faktur Pajak,Nama Barang,"Quantity 
Penagihan","Quantity 
di PO",UOI,"Sub Total
(Exclude PPN)","Sub Total
(Include PPN)","Tanggal 
terima & koreksi Invoice",Tanggal Kirim Kasir,"Lama 
Pekerjaan",Keterangan
10698,2026-10-23,Karung,5007686214,,5000009409,CAMEL,DASAPLAST NUSANTARA ,-,dasaplast.smg@gmail.com,2026-10-25,,DASAPLAST NUSANTARA,5000009409,,,,-,,,,,0,,,0,
10699,2026-10-25,Batubara/Methanol,5007689042,TAHAP 7,5300066465,ELLA,HUMPUSS ,METHANOL:[CH3OH],gendhis.sekar@humpuss.co.id,2026-10-27,,HUMPUSS,5300066465,223/MEOH-13/10-2026,0954/HS-S/,03002600441782470,METHANOL:[CH3OH],600,600,TON,7680000000,8524800000,2026-10-27,2026-10-28,1,
10700,2026-10-25,Chemical/Bahan Baku,5007687166,,5000009256,WAHYU,PERMATA DWITUNGGAL ABADI ,"SULPHURIC ACID,TECH:[H2SO4];98% MIN",csmarketing@permataabadi.co.id robbyprawirapermana@gmail.com,2026-10-26,,PERMATA DWITUNGGAL ABADI,5000009256,INV.PDA.2026-0613,,03002600453962096,"SULPHURIC ACID,TECH:[H2SO4];98% MIN",25500,300000,KG,355470000,394571700,2026-11-10,2026-11-10,0,
10701,2026-10-25,Chemical/Bahan Baku,5007687759,,5000009400,NAFISA,VERONA MULTIKIMIA ABADI ,PEWARNA UREA;RED;3% ORG,indra.verona@yahoo.com verona.sby@gmail.com,2026-10-25,,VERONA MULTIKIMIA ABADI,5000009400,,,,PEWARNA UREA;RED;3% ORG,,,,,0,,,0,
10702,2026-10-25,Karung,5007689766,,5000009406,CAMEL,MURNI MAPAN MAKMUR ,SACK:UREA;PIHC;SS;50KG,simonsoetanto@mmmpas.com,2026-10-27,,MURNI MAPAN MAKMUR,5000009406,,,,SACK:UREA;PIHC;SS;50KG,,,,,0,,,0,
10703,2026-10-25,Chemical/Bahan Baku,5007687163,,5000009374,NAFISA,CLARIANT KUJANG CATALYSTS ,CATALYST:LTS,dinda.elefani@clariant-kujang.com marsa.rudwinanda@clariant-kujang.com,2026-10-25,,CLARIANT KUJANG CATALYSTS,5000009374,9051495321,54947106,03002600458805437,CATALYST:LTS,54,54,M3,21599994600,23975994006,2026-11-12,2026-11-12,0,
10704,2026-10-26,Chemical/Bahan Baku,5007662550,,5300062754,NAFISA,SAMATOR GAS INDUSTRI ,"NITROGEN
NITROGEN",mkt_kutai@sgi-gas.com se_bontang@sgi-gas.com hiel.ade@samator.com abidfikry77@gmail.com,2026-10-26,,SAMATOR GAS INDUSTRI,5300062754,1203871,SGIO1067738,04002600436179422,"NITROGEN
NITROGEN",1,30,BT,100000,111000,2026-10-26,2026-10-26,0,
10705,2026-10-26,Chemical/Bahan Baku,5007678864,,5000009399,NAFISA,PETROSIDA GRESIK ,pewARNA UREA;RED;3% ORG,korporasipetrosida@gmail.com,2026-10-26,,PETROSIDA GRESIK,5000009399,000127/10/PJL-KIM/26,27-3/08/SJ-KIM/2026,03002600439089910,pewARNA UREA;RED;3% ORG,50000,440000,KG,305000000,338550000,2026-10-29,2026-10-29,0,
10706,2026-10-29,Sparepart,5007694619,,5300062383,EVA,INDOBARA BAHANA ,"RING,RETAINING:CS;EBARA 150X100IFWM DP
SETSCREW:EBARA 150X100IFWM-3215XA;M-10
BEARING,SLEEVE:EBARA;400X300CDHT;POS.052
GASKET:EBARA;150X100UCWM;POS.117-01",info@indobara.co.id,2026-11-03,,INDOBARA BAHANA,5300062383,,,,"RING,RETAINING:CS;EBARA 150X100IFWM DP
SETSCREW:EBARA 150X100IFWM-3215XA;M-10
BEARING,SLEEVE:EBARA;400X300CDHT;POS.052
GASKET:EBARA;150X100UCWM;POS.117-01",,,,,0,,,0,
10707,2026-10-29,Sparepart,5007697783,,5300066419,ALEX,NUANSA DHARMA CIPTA ,FURNITURE KANTOR DEP P&P,sspristi1@gmail.com,2026-10-30,,NUANSA DHARMA CIPTA,5300066419,SG/INV/2610/00767,66419,04002600433686172,FURNITURE KANTOR DEP P&P,1,1,UNIT,61035000,67748850,,,0,
10708,2026-10-29,Sparepart,5007697779,,5300066316,ALEX,NUANSA DHARMA CIPTA ,TISSUE ROLL,sspristi1@gmail.com,2026-10-30,,NUANSA DHARMA CIPTA,5300066316,,,,TISSUE ROLL,,,,,0,,,0,
10709,2026-10-29,Sparepart,5007697707,,5300066803,ALEX,BONTANG PRATAMA INTERNASIONAL ,"BAND IT VALUCLIPS STAINLESS STEEL 3/4""",bontangpratamains@gmail.com,2026-11-30,,BONTANG PRATAMA INTERNASIONAL,5300066803,,,,"BAND IT VALUCLIPS STAINLESS STEEL 3/4""",,,,,0,,,,
10710,2026-10-29,Sparepart,5007697383,,5300065755,ELLA,DUTA KARSACIPTA BUANA ,"STEAM TRAP TBH71-105W 1""",sales@dkbuana.co.id,2026-11-02,,DUTA KARSACIPTA BUANA,5300065755,IN001975,DO001644,03002600458662170,"STEAM TRAP TBH71-105W 1""",4,4,EA,200000000,222000000,2026-11-10,2026-11-11,,
CSV;
}

it('imports the 13 sample monitoring barang rows with PIC mapping, status Selesai, date fallback, and incomplete row tracking', function () {
    $users = seedMonitoringPicUsers();

    $service = app(MonitoringBarangImportService::class);
    $summary = $service->importFromString(sampleMonitoringBarangCsv());

    expect($summary['total_rows'])->toBe(13)
        ->and($summary['created'])->toBe(13)
        ->and($summary['updated'])->toBe(0)
        ->and($summary['fallback_kasir_count'])->toBe(1) // Baris 10707
        ->and($summary['unmatched_pics'])->toBeEmpty()
        ->and(InvoicingReceipt::count())->toBe(13);

    // 10698: Tanpa invoice -> Menunggu Verifikasi, PIC CAMEL, kategori_barang Karung
    $row10698 = InvoicingReceipt::where('no_grs', '5007686214')->first();
    expect($row10698)->not->toBeNull()
        ->and($row10698->pic_id)->toBe($users['camel@receiving.com']->id)
        ->and($row10698->status)->toBe('Menunggu Verifikasi')
        ->and($row10698->jenis)->toBe('GRS')
        ->and($row10698->kategori_barang)->toBe('Karung')
        ->and($row10698->vendor_name)->toBe('DASAPLAST NUSANTARA')
        ->and($row10698->tanggal_kirim_kasir)->toBeNull();

    // 10699: Ada invoice + Tanggal Kirim Kasir terisi + No. RDTV = TAHAP 7 + No. GRS terisi -> jenis GRS, status Selesai
    $row10699 = InvoicingReceipt::where('no_grs', '5007689042')->first();
    expect($row10699)->not->toBeNull()
        ->and($row10699->pic_id)->toBe($users['husdayanti@receiving.com']->id)
        ->and($row10699->status)->toBe('Selesai')
        ->and($row10699->jenis)->toBe('GRS')
        ->and($row10699->kategori_barang)->toBe('Batubara/Methanol')
        ->and($row10699->no_rdtv)->toBe('TAHAP 7')
        ->and($row10699->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-10-28')
        ->and($row10699->lama_pekerjaan)->toBe(1)
        ->and((float) $row10699->subtotal_include_ppn)->toBe(8524800000.0);

    // 10700: Multi-email spasi dinormalisasi jadi koma & tanpa No. DO -> tercatat di incomplete_invoiced_rows
    $row10700 = InvoicingReceipt::where('no_grs', '5007687166')->first();
    expect($row10700->pic_id)->toBe($users['wahyu@receiving.com']->id)
        ->and($row10700->status)->toBe('Selesai')
        ->and($row10700->email_vendor)->toBe('csmarketing@permataabadi.co.id, robbyprawirapermana@gmail.com');

    // 10703: PIC NAFISA -> nafisa@receiving.com
    $row10703 = InvoicingReceipt::where('no_grs', '5007687163')->first();
    expect($row10703->pic_id)->toBe($users['nafisa@receiving.com']->id)
        ->and($row10703->status)->toBe('Selesai')
        ->and((float) $row10703->subtotal_include_ppn)->toBe(23975994006.0);

    // 10706: PIC EVA & deskripsi multiline
    $row10706 = InvoicingReceipt::where('no_grs', '5007694619')->first();
    expect($row10706->pic_id)->toBe($users['eva@receiving.com']->id)
        ->and($row10706->status)->toBe('Menunggu Verifikasi')
        ->and($row10706->deskripsi)->toContain('SETSCREW:EBARA');

    // 10707: Ada invoice, tanggal koreksi & kasir kosong -> tanggal_terima_koreksi_invoice null, tanggal_kirim_kasir = tanggal_info_grs + 3 hari (2026-11-02)
    $row10707 = InvoicingReceipt::where('no_grs', '5007697783')->first();
    expect($row10707->pic_id)->toBe($users['alex@receiving.com']->id)
        ->and($row10707->status)->toBe('Selesai')
        ->and($row10707->tanggal_terima_koreksi_invoice)->toBeNull()
        ->and($row10707->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-11-02')
        ->and($row10707->lama_pekerjaan)->toBe(0);

    // 10710: Ada invoice + koreksi & kasir terisi, lama_pekerjaan kosong di CSV -> dihitung otomatis 1 hari
    $row10710 = InvoicingReceipt::where('no_grs', '5007697383')->first();
    expect($row10710->status)->toBe('Selesai')
        ->and($row10710->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-11-11')
        ->and($row10710->lama_pekerjaan)->toBe(1);

    // Cek incomplete_invoiced_rows memuat baris 10700 (tanpa No. DO) dan 10707 (tanpa Tgl Terima & Koreksi Invoice)
    $incompleteByPo = collect($summary['incomplete_invoiced_rows'])->keyBy('no_po');
    expect($incompleteByPo->has('5000009256'))->toBeTrue()
        ->and($incompleteByPo->get('5000009256')['missing_fields'])->toContain('No. DO')
        ->and($incompleteByPo->has('5300066419'))->toBeTrue()
        ->and($incompleteByPo->get('5300066419')['missing_fields'])->toContain('Tanggal Terima & Koreksi Invoice');
});

it('maps all 11 PIC aliases correctly and reports unmatched PICs', function () {
    $users = seedMonitoringPicUsers();

    $csv = <<<'CSV'
 ,Tanggal Terima GRS,Jenis,No. GRS,No. RDTV,No. PO,PIC,Vendor,Deskripsi,Email,Tanggal Info GRS,,Vendor,PO,No. Invoice,No. DO,Faktur Pajak,Nama Barang,Quantity Penagihan,Quantity di PO,UOI,Sub Total (Exclude PPN),Sub Total (Include PPN),Tanggal terima & koreksi Invoice,Tanggal Kirim Kasir,Lama Pekerjaan,Keterangan
1,2026-10-25,Sparepart,5001000001,,5300000001,ALEX,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000001,,,,,,,,,,,,
2,2026-10-25,Sparepart,5001000002,,5300000002,HENGKY,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000002,,,,,,,,,,,,
3,2026-10-25,Sparepart,5001000003,,5300000003,MICHAEL,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000003,,,,,,,,,,,,
4,2026-10-25,Sparepart,5001000004,,5300000004,CAMEL,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000004,,,,,,,,,,,,
5,2026-10-25,Sparepart,5001000005,,5300000005,ELLA,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000005,,,,,,,,,,,,
6,2026-10-25,Sparepart,5001000006,,5300000006,PUGUH,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000006,,,,,,,,,,,,
7,2026-10-25,Sparepart,5001000007,,5300000007,EVA,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000007,,,,,,,,,,,,
8,2026-10-25,Sparepart,5001000008,,5300000008,ICAN,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000008,,,,,,,,,,,,
9,2026-10-25,Sparepart,5001000009,,5300000009,NAFISA,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000009,,,,,,,,,,,,
10,2026-10-25,Sparepart,5001000010,,5300000010,RIZKI,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000010,,,,,,,,,,,,
11,2026-10-25,Sparepart,5001000011,,5300000011,WAHYU,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000011,,,,,,,,,,,,
12,2026-10-25,Sparepart,5001000012,,5300000012,UNKNOWN_PIC,VENDOR A,ITEM,a@test.com,2026-10-26,,VENDOR A,5300000012,,,,,,,,,,,,
CSV;

    $summary = app(MonitoringBarangImportService::class)->importFromString($csv);

    expect($summary['created'])->toBe(12)
        ->and($summary['unmatched_pics'])->toHaveCount(1)
        ->and($summary['unmatched_pics'][0])->toContain('UNKNOWN_PIC');

    expect(InvoicingReceipt::where('no_grs', '5001000001')->value('pic_id'))->toBe($users['alex@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000002')->value('pic_id'))->toBe($users['alex@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000003')->value('pic_id'))->toBe($users['alex@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000004')->value('pic_id'))->toBe($users['camel@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000005')->value('pic_id'))->toBe($users['husdayanti@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000006')->value('pic_id'))->toBe($users['husdayanti@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000007')->value('pic_id'))->toBe($users['eva@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000008')->value('pic_id'))->toBe($users['nafisa@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000009')->value('pic_id'))->toBe($users['nafisa@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000010')->value('pic_id'))->toBe($users['rizki@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000011')->value('pic_id'))->toBe($users['wahyu@receiving.com']->id)
        ->and(InvoicingReceipt::where('no_grs', '5001000012')->value('pic_id'))->toBeNull();
});

it('handles all cashier date fallback tiers including leaving null when all dates are missing, and preserves explicit subtotal include PPN without adding 11% again', function () {
    seedMonitoringPicUsers();

    $csv = <<<'CSV'
 ,Tanggal Terima GRS,Jenis,No. GRS,No. RDTV,No. PO,PIC,Vendor,Deskripsi,Email,Tanggal Info GRS,,Vendor,PO,No. Invoice,No. DO,Faktur Pajak,Nama Barang,Quantity Penagihan,Quantity di PO,UOI,Sub Total (Exclude PPN),Sub Total (Include PPN),Tanggal terima & koreksi Invoice,Tanggal Kirim Kasir,Lama Pekerjaan,Keterangan
1,2026-10-01,Sparepart,5002000001,,5300000021,ALEX,VENDOR A,BARANG 1,a@test.com,2026-10-05,,VENDOR A,5300000021,INV-KOREKSI,DO-1,010,BARANG 1,1,1,EA,1000000,1120000,2026-10-09,,,
2,2026-10-01,Sparepart,5002000002,,5300000022,ALEX,VENDOR A,BARANG 2,a@test.com,2026-10-05,,VENDOR A,5300000022,INV-INFO3,DO-2,010,BARANG 2,1,1,EA,1000000,,,,,
3,2026-10-01,Sparepart,5002000003,,5300000023,ALEX,VENDOR A,BARANG 3,a@test.com,,,VENDOR A,5300000023,INV-ALL-EMPTY,DO-3,010,BARANG 3,1,1,EA,2000000,2220000,,,,
CSV;

    $summary = app(MonitoringBarangImportService::class)->importFromString($csv);

    // Baris 1: Ada koreksi -> tanggal_kirim_kasir ikut koreksi (2026-10-09); include PPN tetap 1120000 (tidak ditimpa/ditambah 11%)
    $r1 = InvoicingReceipt::where('no_grs', '5002000001')->first();
    expect($r1->status)->toBe('Selesai')
        ->and($r1->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-10-09')
        ->and((float) $r1->subtotal_include_ppn)->toBe(1120000.0);

    // Baris 2: Koreksi & kasir kosong -> tanggal_kirim_kasir = info_grs + 3 hari (2026-10-08); include PPN kosong -> auto +11% (1110000)
    $r2 = InvoicingReceipt::where('no_grs', '5002000002')->first();
    expect($r2->status)->toBe('Selesai')
        ->and($r2->tanggal_terima_koreksi_invoice)->toBeNull()
        ->and($r2->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-10-08')
        ->and((float) $r2->subtotal_include_ppn)->toBe(1110000.0);

    // Baris 3: Ketiga tanggal (kasir, koreksi, info GRS) kosong semua -> tetap null (tidak ambil tanggal terima GRS) & masuk warning
    $r3 = InvoicingReceipt::where('no_grs', '5002000003')->first();
    expect($r3->status)->toBe('Selesai')
        ->and($r3->tanggal_kirim_kasir)->toBeNull()
        ->and((float) $r3->subtotal_include_ppn)->toBe(2220000.0);

    $incompleteR3 = collect($summary['incomplete_invoiced_rows'])->firstWhere('no_invoice', 'INV-ALL-EMPTY');
    expect($incompleteR3)->not->toBeNull()
        ->and($incompleteR3['missing_fields'])->toContain('Tanggal Kirim Kasir', 'Tanggal Terima & Koreksi Invoice', 'Tanggal Info GRS');
});

it('keeps multi-stage GRS (unique No. GRS) and RDTV vs GRS on the same PO separate, auto-links DO/PO/Vendor, and is idempotent on re-import', function () {
    $users = seedMonitoringPicUsers();

    $vendor = Vendor::create([
        'vendor_number' => '10000999',
        'name' => 'PT SINAR JAYA',
        'email' => ['finance@sinarjaya.co.id'],
    ]);

    $po = PurchaseOrderIssued::create([
        'purchase_order_and_item' => '5300099999-10',
        'mrp_type' => 'PD',
        'material_type' => 'ZSP',
        'purchase_order_no' => '5300099999',
        'item_no' => 10,
        'material_code' => '60009999',
        'description' => 'POMPA INDUSTRI',
        'qty_po' => 10,
        'uoi' => 'UNIT',
        'vendor_id' => '10000999',
        'vendor_name' => 'PT SINAR JAYA',
        'total_amount_in_lc' => 50000000,
    ]);

    $do1 = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'SJ-001',
        'received_date' => '2026-10-10',
        'physical_received_date' => '2026-10-10',
        'is_physically_received' => true,
        'source_type' => 'Sparepart',
        'document_code' => '5300099999-01-SJ001',
        'status' => 'GRS',
    ]);
    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $do1->id,
        'purchase_order_issued_id' => $po->id,
        'item_no' => 10,
        'quantity' => 5,
        'material_code' => '60009999',
        'description' => 'POMPA INDUSTRI',
        'uoi' => 'UNIT',
        'material_type' => 'ZSP',
    ]);

    $csvInitial = <<<'CSV'
 ,Tanggal Terima GRS,Jenis,No. GRS,No. RDTV,No. PO,PIC,Vendor,Deskripsi,Email,Tanggal Info GRS,,Vendor,PO,No. Invoice,No. DO,Faktur Pajak,Nama Barang,Quantity Penagihan,Quantity di PO,UOI,Sub Total (Exclude PPN),Sub Total (Include PPN),Tanggal terima & koreksi Invoice,Tanggal Kirim Kasir,Lama Pekerjaan,Keterangan
1,2026-10-12,Sparepart,,RDTV-001,5300099999,ALEX,PT SINAR JAYA,POMPA INDUSTRI (TOLAK),,2026-10-13,,PT SINAR JAYA,5300099999,,SJ-REJECT,,POMPA INDUSTRI (TOLAK),2,10,UNIT,,,,,,,,,
2,2026-10-15,Sparepart,5009000001,,5300099999,ALEX,PT SINAR JAYA,POMPA INDUSTRI TAHAP 1,,2026-10-16,,PT SINAR JAYA,5300099999,,SJ-001,,POMPA INDUSTRI TAHAP 1,5,10,UNIT,,,,,,,,,
3,2026-10-20,Sparepart,5009000002,,5300099999,RIZKI,PT SINAR JAYA,POMPA INDUSTRI TAHAP 2,,2026-10-21,,PT SINAR JAYA,5300099999,INV-TAHAP-2,SJ-002,03002600000000001,POMPA INDUSTRI TAHAP 2,5,10,UNIT,25000000,27750000,,,,
CSV;

    $service = app(MonitoringBarangImportService::class);
    $firstSummary = $service->importFromString($csvInitial);

    expect($firstSummary['created'])->toBe(3)
        ->and($firstSummary['linked_do'])->toBe(1)
        ->and(InvoicingReceipt::count())->toBe(3);

    $rdtvRow = InvoicingReceipt::where('jenis', 'RDTV')->where('no_rdtv', 'RDTV-001')->first();
    $grsRow1 = InvoicingReceipt::where('no_grs', '5009000001')->first();
    $grsRow2 = InvoicingReceipt::where('no_grs', '5009000002')->first();

    expect($rdtvRow)->not->toBeNull()
        ->and($rdtvRow->status)->toBe('Menunggu Verifikasi')
        ->and($rdtvRow->vendor_id)->toBe($vendor->id)
        ->and($grsRow1)->not->toBeNull()
        ->and($grsRow1->jenis)->toBe('GRS')
        ->and($grsRow1->delivery_order_receipt_id)->toBe($do1->id)
        ->and($grsRow1->purchase_order_issued_id)->toBe($po->id)
        ->and($grsRow1->material_type)->toBe('ZSP')
        ->and($grsRow1->email_vendor)->toBe('finance@sinarjaya.co.id')
        ->and($grsRow2)->not->toBeNull()
        ->and($grsRow2->status)->toBe('Selesai')
        ->and($grsRow2->pic_id)->toBe($users['rizki@receiving.com']->id)
        ->and($grsRow2->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-10-24');

    // Re-import persis sama -> 0 created, 0 updated, 3 unchanged
    $secondSummary = $service->importFromString($csvInitial);
    expect($secondSummary['created'])->toBe(0)
        ->and($secondSummary['updated'])->toBe(0)
        ->and($secondSummary['unchanged'])->toBe(3)
        ->and(InvoicingReceipt::count())->toBe(3);

    // Update baris Tahap 1 (5009000001) dengan No. Invoice -> hanya Tahap 1 ter-update ke Selesai, RDTV & Tahap 2 tetap utuh
    $csvUpdated = str_replace(
        '2,2026-10-15,Sparepart,5009000001,,5300099999,ALEX,PT SINAR JAYA,POMPA INDUSTRI TAHAP 1,,2026-10-16,,PT SINAR JAYA,5300099999,,SJ-001,,POMPA INDUSTRI TAHAP 1,5,10,UNIT,,,,,,,,,',
        '2,2026-10-15,Sparepart,5009000001,,5300099999,ALEX,PT SINAR JAYA,POMPA INDUSTRI TAHAP 1,,2026-10-16,,PT SINAR JAYA,5300099999,INV-TAHAP-1,SJ-001,03002600000000002,POMPA INDUSTRI TAHAP 1,5,10,UNIT,25000000,27750000,2026-10-18,2026-10-19,1,',
        $csvInitial
    );

    $thirdSummary = $service->importFromString($csvUpdated);
    expect($thirdSummary['created'])->toBe(0)
        ->and($thirdSummary['updated'])->toBe(1)
        ->and($thirdSummary['unchanged'])->toBe(2)
        ->and(InvoicingReceipt::count())->toBe(3);

    $grsRow1->refresh();
    expect($grsRow1->status)->toBe('Selesai')
        ->and($grsRow1->no_invoice)->toBe('INV-TAHAP-1')
        ->and($grsRow1->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-10-19');
});

it('directly imports an Excel (.xlsx) Monitoring Barang workbook with formula cells, title row, and CLI command', function () {
    $users = seedMonitoringPicUsers();

    $tempXlsxPath = tempnam(sys_get_temp_dir(), 'monitoring_barang_').'.xlsx';
    $zip = new ZipArchive;
    $zip->open($tempXlsxPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);

    $contentTypesXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;

    $relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;

    $workbookXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Monitoring Barang" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML;

    $workbookRelsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML;

    // Baris 1: Judul tabel (untuk memastikan deteksi header dinamis melewati baris banner)
    // Baris 2: Header Monitoring Barang
    // Baris 3 & 4: Data dengan FormulaCell (<f>...</f><v>cached</v>) dan angka serial tanggal Excel (46320 = 2026-10-25)
    $sheet1Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>REKAP MONITORING BARANG PKT</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t> </t></is></c>
      <c r="B2" t="inlineStr"><is><t>Tanggal Terima GRS</t></is></c>
      <c r="C2" t="inlineStr"><is><t>Jenis</t></is></c>
      <c r="D2" t="inlineStr"><is><t>No. GRS</t></is></c>
      <c r="E2" t="inlineStr"><is><t>No. RDTV</t></is></c>
      <c r="F2" t="inlineStr"><is><t>No. PO</t></is></c>
      <c r="G2" t="inlineStr"><is><t>PIC</t></is></c>
      <c r="H2" t="inlineStr"><is><t>Vendor</t></is></c>
      <c r="I2" t="inlineStr"><is><t>Deskripsi</t></is></c>
      <c r="J2" t="inlineStr"><is><t>Email</t></is></c>
      <c r="K2" t="inlineStr"><is><t>Tanggal Info GRS</t></is></c>
      <c r="L2" t="inlineStr"><is><t></t></is></c>
      <c r="M2" t="inlineStr"><is><t>Vendor</t></is></c>
      <c r="N2" t="inlineStr"><is><t>PO</t></is></c>
      <c r="O2" t="inlineStr"><is><t>No. Invoice</t></is></c>
      <c r="P2" t="inlineStr"><is><t>No. DO</t></is></c>
      <c r="Q2" t="inlineStr"><is><t>Faktur Pajak</t></is></c>
      <c r="R2" t="inlineStr"><is><t>Nama Barang</t></is></c>
      <c r="S2" t="inlineStr"><is><t>Quantity 
Penagihan</t></is></c>
      <c r="T2" t="inlineStr"><is><t>Quantity 
di PO</t></is></c>
      <c r="U2" t="inlineStr"><is><t>UOI</t></is></c>
      <c r="V2" t="inlineStr"><is><t>Sub Total
(Exclude PPN)</t></is></c>
      <c r="W2" t="inlineStr"><is><t>Sub Total
(Include PPN)</t></is></c>
      <c r="X2" t="inlineStr"><is><t>Tanggal 
terima &amp; koreksi Invoice</t></is></c>
      <c r="Y2" t="inlineStr"><is><t>Tanggal Kirim Kasir</t></is></c>
      <c r="Z2" t="inlineStr"><is><t>Lama 
Pekerjaan</t></is></c>
      <c r="AA2" t="inlineStr"><is><t>Keterangan</t></is></c>
    </row>
    <row r="3">
      <c r="A3"><v>10699</v></c>
      <c r="B3"><v>46320</v></c>
      <c r="C3" t="inlineStr"><is><t>Batubara/Methanol</t></is></c>
      <c r="D3" t="inlineStr"><is><t>5007689042</t></is></c>
      <c r="E3" t="inlineStr"><is><t>TAHAP 7</t></is></c>
      <c r="F3" t="inlineStr"><is><t>5300066465</t></is></c>
      <c r="G3" t="inlineStr"><is><t>PUGUH</t></is></c>
      <c r="H3" t="inlineStr"><is><t>HUMPUSS </t></is></c>
      <c r="I3" t="inlineStr"><is><t>METHANOL:[CH3OH]</t></is></c>
      <c r="J3" t="inlineStr"><is><t>gendhis.sekar@humpuss.co.id</t></is></c>
      <c r="K3" t="inlineStr"><is><t>2026-10-27</t></is></c>
      <c r="M3" t="inlineStr"><is><t>HUMPUSS</t></is></c>
      <c r="N3"><f>F3</f><v>5300066465</v></c>
      <c r="O3" t="inlineStr"><is><t>223/MEOH-13/10-2026</t></is></c>
      <c r="P3" t="inlineStr"><is><t>0954/HS-S/</t></is></c>
      <c r="Q3" t="inlineStr"><is><t>03002600441782470</t></is></c>
      <c r="R3" t="inlineStr"><is><t>METHANOL:[CH3OH]</t></is></c>
      <c r="S3"><v>600</v></c>
      <c r="T3"><v>600</v></c>
      <c r="U3" t="inlineStr"><is><t>TON</t></is></c>
      <c r="V3"><v>7680000000</v></c>
      <c r="W3"><f>V3*1.11</f><v>8524800000</v></c>
      <c r="X3" t="inlineStr"><is><t>2026-10-27</t></is></c>
      <c r="Y3" t="inlineStr"><is><t>2026-10-28</t></is></c>
      <c r="Z3"><f>Y3-X3</f><v>1</v></c>
    </row>
    <row r="4">
      <c r="A4"><v>10707</v></c>
      <c r="B4" t="inlineStr"><is><t>2026-10-29</t></is></c>
      <c r="C4" t="inlineStr"><is><t>Sparepart</t></is></c>
      <c r="D4" t="inlineStr"><is><t>5007697783</t></is></c>
      <c r="F4" t="inlineStr"><is><t>5300066419</t></is></c>
      <c r="G4" t="inlineStr"><is><t>HENGKY</t></is></c>
      <c r="H4" t="inlineStr"><is><t>NUANSA DHARMA CIPTA</t></is></c>
      <c r="I4" t="inlineStr"><is><t>FURNITURE KANTOR DEP P&amp;P</t></is></c>
      <c r="J4" t="inlineStr"><is><t>sspristi1@gmail.com</t></is></c>
      <c r="K4" t="inlineStr"><is><t>2026-10-30</t></is></c>
      <c r="O4" t="inlineStr"><is><t>SG/INV/2610/00767</t></is></c>
      <c r="P4" t="inlineStr"><is><t>66419</t></is></c>
      <c r="Q4" t="inlineStr"><is><t>04002600433686172</t></is></c>
      <c r="R4" t="inlineStr"><is><t>FURNITURE KANTOR DEP P&amp;P</t></is></c>
      <c r="S4"><v>1</v></c>
      <c r="T4"><v>1</v></c>
      <c r="U4" t="inlineStr"><is><t>UNIT</t></is></c>
      <c r="V4"><v>61035000</v></c>
      <c r="W4"><v>67748850</v></c>
    </row>
  </sheetData>
</worksheet>
XML;

    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $relsXml);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
    $zip->close();

    try {
        $this->artisan('invoicing:import-monitoring', ['file' => $tempXlsxPath])
            ->assertExitCode(0);

        expect(InvoicingReceipt::count())->toBe(2);

        $row3 = InvoicingReceipt::where('no_grs', '5007689042')->first();
        expect($row3)->not->toBeNull()
            ->and($row3->tanggal_terima_grs->format('Y-m-d'))->toBe('2026-10-25')
            ->and($row3->pic_id)->toBe($users['husdayanti@receiving.com']->id)
            ->and($row3->status)->toBe('Selesai')
            ->and((float) $row3->subtotal_include_ppn)->toBe(8524800000.0)
            ->and($row3->lama_pekerjaan)->toBe(1);

        $row4 = InvoicingReceipt::where('no_grs', '5007697783')->first();
        expect($row4)->not->toBeNull()
            ->and($row4->pic_id)->toBe($users['alex@receiving.com']->id)
            ->and($row4->status)->toBe('Selesai')
            ->and($row4->tanggal_terima_koreksi_invoice)->toBeNull()
            ->and($row4->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2026-11-02')
            ->and((float) $row4->subtotal_include_ppn)->toBe(67748850.0);
    } finally {
        @unlink($tempXlsxPath);
    }
});

it('registers Selesai tab, single unified importInvoiceBarang action, and kategori_barang column/filter in Filament UI', function () {
    $page = app(ListInvoicingReceipts::class);
    $tabs = $page->getTabs();

    expect($tabs)->toHaveKey('selesai');

    $table = new Table($page);
    InvoicingReceiptsTable::configure($table);

    expect($table->getColumn('kategori_barang'))->not->toBeNull()
        ->and($table->getFilter('kategori_barang'))->not->toBeNull()
        ->and($table->getFilter('status'))->not->toBeNull();

    $headerMethod = new ReflectionMethod($page, 'getHeaderActions');
    $headerMethod->setAccessible(true);
    $headerActions = collect($headerMethod->invoke($page))->map(fn ($a) => $a->getName())->values()->all();

    expect($headerActions)->toBe(['importInvoiceBarang', 'create']);

    $selesaiRecord = new InvoicingReceipt([
        'no_po' => '5300066419',
        'jenis' => 'GRS',
        'status' => 'Selesai',
    ]);
    $modalView = (string) view('filament.actions.input-invoice-modal-header', ['record' => $selesaiRecord]);
    expect($modalView)->toContain('Selesai')
        ->and($modalView)->toContain('bg-success-100');
});

it('auto-detects Monitoring Barang vs Mitraku CSV format cleanly for single import flow', function () {
    $monitoringPath = tempnam(sys_get_temp_dir(), 'mon_detect_').'.csv';
    $mitrakuPath = tempnam(sys_get_temp_dir(), 'mit_detect_').'.csv';

    file_put_contents($monitoringPath, sampleMonitoringBarangCsv());
    file_put_contents($mitrakuPath, "Invoice Number,SAP Document Number,Transaction Type,PO Numbers\nINV-001,,PO Barang,5300066895\n");

    try {
        $monitoringService = app(MonitoringBarangImportService::class);
        $mitrakuService = app(InvoicingCsvImportService::class);

        expect($monitoringService->canHandleFile($monitoringPath))->toBeTrue()
            ->and($monitoringService->canHandleFile($mitrakuPath))->toBeFalse()
            ->and($mitrakuService->canHandleFile($mitrakuPath))->toBeTrue()
            ->and($mitrakuService->canHandleFile($monitoringPath))->toBeFalse();
    } finally {
        @unlink($monitoringPath);
        @unlink($mitrakuPath);
    }
});

it('supports multi-chunk upload assembly, deletion, extension guard, and ownership isolation', function () {
    Storage::fake('local');

    $owner = User::create([
        'name' => 'Owner User',
        'email' => 'owner-chunk@receiving.com',
        'password' => Hash::make('secret123'),
        'is_active' => true,
        'npk' => '111111',
    ]);

    $otherUser = User::create([
        'name' => 'Other User',
        'email' => 'other-chunk@receiving.com',
        'password' => Hash::make('secret123'),
        'is_active' => true,
        'npk' => '222222',
    ]);

    $csvPayload = sampleMonitoringBarangCsv();
    $splitAt = (int) floor(strlen($csvPayload) / 2);
    $part1 = substr($csvPayload, 0, $splitAt);
    $part2 = substr($csvPayload, $splitAt);
    $uploadId = (string) Str::uuid();

    // Tamu yang belum login ditolak
    $this->postJson(route('filament.admin.resources.invoicing-receipts.import_chunks'), [
        'upload_id' => $uploadId,
        'chunk_index' => 0,
        'total_chunks' => 1,
        'total_size' => strlen($csvPayload),
        'original_name' => 'monitoring.csv',
        'chunk' => UploadedFile::fake()->createWithContent('chunk0.part', $csvPayload),
    ])->assertUnauthorized();

    // Ekstensi selain .xlsx/.csv ditolak
    $this->actingAs($owner)
        ->postJson(route('filament.admin.resources.invoicing-receipts.import_chunks'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 1,
            'total_size' => 12,
            'original_name' => 'shell.php',
            'chunk' => UploadedFile::fake()->createWithContent('chunk0.part', '<?php echo 1;'),
        ])->assertStatus(422);

    // Bagian pertama -> status uploading
    $this->actingAs($owner)
        ->postJson(route('filament.admin.resources.invoicing-receipts.import_chunks'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'total_size' => strlen($csvPayload),
            'original_name' => 'monitoring_barang.csv',
            'chunk' => UploadedFile::fake()->createWithContent('chunk0.part', $part1),
        ])
        ->assertOk()
        ->assertJson([
            'status' => 'uploading',
            'chunk_index' => 0,
            'total_chunks' => 2,
        ]);

    // Bagian kedua -> tergabung utuh byte-per-byte dan folder chunk dibersihkan
    $completedResponse = $this->actingAs($owner)
        ->postJson(route('filament.admin.resources.invoicing-receipts.import_chunks'), [
            'upload_id' => $uploadId,
            'chunk_index' => 1,
            'total_chunks' => 2,
            'total_size' => strlen($csvPayload),
            'original_name' => 'monitoring_barang.csv',
            'chunk' => UploadedFile::fake()->createWithContent('chunk1.part', $part2),
        ])
        ->assertOk()
        ->assertJson([
            'status' => 'completed',
            'name' => 'monitoring_barang.csv',
            'size' => strlen($csvPayload),
        ]);

    $assembledPath = $completedResponse->json('path');
    expect($assembledPath)->toStartWith("temp-monitoring-barang-imports/{$owner->id}-")
        ->and(Storage::disk('local')->exists($assembledPath))->toBeTrue()
        ->and(Storage::disk('local')->get($assembledPath))->toBe($csvPayload)
        ->and(Storage::disk('local')->exists("temp-monitoring-barang-imports/chunks/{$owner->id}/{$uploadId}"))->toBeFalse();

    // User lain tidak boleh menghapus file milik owner maupun path traversal
    $this->actingAs($otherUser)
        ->deleteJson(route('filament.admin.resources.invoicing-receipts.import_chunks.destroy'), [
            'path' => $assembledPath,
        ])->assertOk();
    expect(Storage::disk('local')->exists($assembledPath))->toBeTrue();

    $this->actingAs($otherUser)
        ->deleteJson(route('filament.admin.resources.invoicing-receipts.import_chunks.destroy'), [
            'path' => '../.env',
        ])->assertOk();

    // Owner menghapus filenya sendiri -> terhapus bersih
    $this->actingAs($owner)
        ->deleteJson(route('filament.admin.resources.invoicing-receipts.import_chunks.destroy'), [
            'path' => $assembledPath,
        ])->assertOk();
    expect(Storage::disk('local')->exists($assembledPath))->toBeFalse();
});

it('renders chunked uploader in importInvoiceBarang modal, imports uploaded file, and rejects unowned paths', function () {
    Storage::fake('local');
    $users = seedMonitoringPicUsers();
    $actingUser = $users['alex@receiving.com'];

    $csvPayload = sampleMonitoringBarangCsv();
    $uploadId = (string) Str::uuid();

    $response = $this->actingAs($actingUser)
        ->postJson(route('filament.admin.resources.invoicing-receipts.import_chunks'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 1,
            'total_size' => strlen($csvPayload),
            'original_name' => 'monitoring.csv',
            'chunk' => UploadedFile::fake()->createWithContent('chunk0.part', $csvPayload),
        ])->assertOk();

    $assembledPath = $response->json('path');

    // Pastikan modal merender ViewField chunked upload dengan binding state yang tepat,
    // lalu path traversal ditolak tanpa mengimpor data
    $testable = Livewire::actingAs($actingUser)
        ->test(ListInvoicingReceipts::class)
        ->mountAction('importInvoiceBarang')
        ->assertActionMounted('importInvoiceBarang');

    $schemaMethod = new ReflectionMethod($testable->instance(), 'getMountedActionSchema');
    $schemaMethod->setAccessible(true);
    $modalHtml = $schemaMethod->invoke($testable->instance())->toHtml();

    expect($modalHtml)
        ->toContain('data-upload-url')
        ->toContain('mountedActions.0.data.monitoring_file');

    $testable
        ->fillForm([
            'monitoring_file' => '../.env',
        ])
        ->callMountedAction();

    expect(InvoicingReceipt::count())->toBe(0);

    // Path hasil chunked upload berhasil diimpor lalu file sementara otomatis dihapus
    Livewire::actingAs($actingUser)
        ->test(ListInvoicingReceipts::class)
        ->mountAction('importInvoiceBarang')
        ->fillForm([
            'monitoring_file' => $assembledPath,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(InvoicingReceipt::count())->toBe(13)
        ->and(Storage::disk('local')->exists($assembledPath))->toBeFalse();
});

it('handles real-workbook xlsx quirks: out-of-range date styles, error cells, scientific notation, negative formula days, multiline uoi/qty, and BARANG_ sheet priority', function () {
    $users = seedMonitoringPicUsers();

    $tempXlsxPath = sys_get_temp_dir().'/monitoring_quirks_'.uniqid().'.xlsx';
    $zip = new ZipArchive;
    expect($zip->open($tempXlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();

    $contentTypesXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;

    $relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;

    $workbookXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Master Data Vendor" sheetId="1" r:id="rId1"/>
    <sheet name="BARANG_BACKUP" sheetId="2" r:id="rId2"/>
    <sheet name="BARANG_" sheetId="3" r:id="rId3"/>
  </sheets>
</workbook>
XML;

    $workbookRelsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>
</Relationships>
XML;

    // Style xf index 1 memakai custom numFmt 188 (dd/mm/yyyy)
    $stylesXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="1">
    <numFmt numFmtId="188" formatCode="dd/mm/yyyy"/>
  </numFmts>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    <xf numFmtId="188" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/>
  </cellXfs>
</styleSheet>
XML;

    $sheet1Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1"><c r="A1" t="inlineStr"><is><t>NAMA VENDOR</t></is></c></row>
  </sheetData>
</worksheet>
XML;

    $sheet2Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>Tanggal Terima GRS</t></is></c>
      <c r="B1" t="inlineStr"><is><t>No. GRS</t></is></c>
      <c r="C1" t="inlineStr"><is><t>No. PO</t></is></c>
      <c r="D1" t="inlineStr"><is><t>No. Invoice</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>2026-01-01</t></is></c>
      <c r="B2" t="inlineStr"><is><t>5009999999</t></is></c>
      <c r="C2" t="inlineStr"><is><t>5300999999</t></is></c>
      <c r="D2" t="inlineStr"><is><t>BACKUP-SHOULD-NOT-WIN</t></is></c>
    </row>
  </sheetData>
</worksheet>
XML;

    // Sheet3 = BARANG_ dengan kasus nyata:
    // - Cell No. DO bernilai 2.057894E7 (20578940) bersandar style tanggal s="1" (dd/mm/yyyy)
    // - Cell No. GRS bernilai 5.006259101E9 & No. PO bernilai 5.300058418E9
    // - Cell Deskripsi berisi error cell t="e" (#N/A)
    // - Cell Quantity Penagihan multiline "1\n10\n5" -> 16 & UOI multiline > 20 char
    // - Cell Lama Pekerjaan negatif -46183 -> tidak disimpan negatif
    // - Tanggal dengan double slash "6//11/25" -> 2025-11-06 & "TAHUN 2023" -> null
    $sheet3Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>Tanggal Terima GRS</t></is></c>
      <c r="B1" t="inlineStr"><is><t>Jenis</t></is></c>
      <c r="C1" t="inlineStr"><is><t>No. GRS</t></is></c>
      <c r="D1" t="inlineStr"><is><t>No. PO</t></is></c>
      <c r="E1" t="inlineStr"><is><t>PIC</t></is></c>
      <c r="F1" t="inlineStr"><is><t>PADI</t></is></c>
      <c r="G1" t="inlineStr"><is><t>Vendor</t></is></c>
      <c r="H1" t="inlineStr"><is><t>Deskripsi</t></is></c>
      <c r="I1" t="inlineStr"><is><t>Tanggal Info GRS</t></is></c>
      <c r="J1" t="inlineStr"><is><t>No. Invoice</t></is></c>
      <c r="K1" t="inlineStr"><is><t>No. DO</t></is></c>
      <c r="L1" t="inlineStr"><is><t>Quantity Penagihan</t></is></c>
      <c r="M1" t="inlineStr"><is><t>UOI</t></is></c>
      <c r="N1" t="inlineStr"><is><t>Sub Total (Exclude PPN)</t></is></c>
      <c r="O1" t="inlineStr"><is><t>Sub Total (Include PPN)</t></is></c>
      <c r="P1" t="inlineStr"><is><t>Tanggal terima &amp; koreksi Invoice</t></is></c>
      <c r="Q1" t="inlineStr"><is><t>Tanggal Kirim Kasir</t></is></c>
      <c r="R1" t="inlineStr"><is><t>Lama Pekerjaan</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>Sudah Invoice</t></is></c>
    </row>
    <row r="3">
      <c r="A3" s="1"><v>45738</v></c>
      <c r="B3" t="inlineStr"><is><t>Sparepart</t></is></c>
      <c r="C3"><v>5.006259101E9</v></c>
      <c r="D3"><v>5.300058418E9</v></c>
      <c r="E3" t="inlineStr"><is><t>ICAN</t></is></c>
      <c r="F3" t="inlineStr"><is><t>UMKM</t></is></c>
      <c r="G3" t="inlineStr"><is><t>PT QUIRK MAJU</t></is></c>
      <c r="H3" t="e"><v>#N/A</v></c>
      <c r="I3" t="inlineStr"><is><t>6//11/25</t></is></c>
      <c r="J3"><v>1.1029263E7</v></c>
      <c r="K3" s="1"><v>2.057894E7</v></c>
      <c r="L3" t="inlineStr"><is><t>1
10
5</t></is></c>
      <c r="M3" t="inlineStr"><is><t>EA
SET
SET
EA
ROLL</t></is></c>
      <c r="N3"><v>1.29573E7</v></c>
      <c r="O3"><v>14382603</v></c>
      <c r="P3" t="inlineStr"><is><t>TAHUN 2023</t></is></c>
      <c r="R3"><v>-46183</v></c>
    </row>
  </sheetData>
</worksheet>
XML;

    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $relsXml);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
    $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);
    $zip->addFromString('xl/worksheets/sheet3.xml', $sheet3Xml);
    $zip->close();

    try {
        $service = app(MonitoringBarangImportService::class);
        expect($service->canHandleFile($tempXlsxPath))->toBeTrue();

        $summary = $service->importFromFile($tempXlsxPath);
        expect($summary['created'])->toBe(1)
            ->and(InvoicingReceipt::where('no_invoice', 'BACKUP-SHOULD-NOT-WIN')->exists())->toBeFalse();

        $record = InvoicingReceipt::where('no_grs', '5006259101')->first();
        expect($record)->not->toBeNull()
            ->and($record->no_po)->toBe('5300058418')
            ->and($record->padi)->toBe('UMKM')
            ->and($record->pic_id)->toBe($users['nafisa@receiving.com']->id)
            ->and($record->deskripsi)->toBeNull()
            ->and($record->tanggal_terima_grs->format('Y-m-d'))->toBe('2025-03-22')
            ->and($record->tanggal_info_grs->format('Y-m-d'))->toBe('2025-11-06')
            ->and($record->no_invoice)->toBe('11029263')
            ->and($record->no_do)->toBe('20578940')
            ->and((float) $record->qty_penagihan)->toBe(16.0)
            ->and($record->uoi)->toBe('EA/SET/ROLL')
            ->and((float) $record->subtotal_exclude_ppn)->toBe(12957300.0)
            ->and((float) $record->subtotal_include_ppn)->toBe(14382603.0)
            ->and($record->tanggal_terima_koreksi_invoice)->toBeNull()
            ->and($record->tanggal_kirim_kasir->format('Y-m-d'))->toBe('2025-11-09')
            ->and($record->lama_pekerjaan)->toBeNull()
            ->and($record->status)->toBe('Selesai');
    } finally {
        @unlink($tempXlsxPath);
    }
});

it('links DeliveryOrderReceipts via multiline No. DO, slash-formatted DO segments, and direct orphan No. DO fallback without false cross-PO linking', function () {
    seedMonitoringPicUsers();

    // Setup PO 5300061881 dengan 2 DO
    $po1 = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300061881',
        'material_type' => 'ZRM',
        'vendor_name' => 'PT VENDOR A',
        'description' => 'MATERIAL A',
        'qty_po' => 10,
        'uoi' => 'EA',
    ]);
    $doMulti1 = DeliveryOrderReceipt::create([
        'delivery_order_no' => '9510207908',
        'document_code' => 'DO-5300061881-1',
        'status' => 'Diterima',
    ]);
    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $doMulti1->id,
        'purchase_order_issued_id' => $po1->id,
        'material_type' => 'ZRM',
        'description' => 'MATERIAL A',
        'uoi' => 'EA',
    ]);

    // Setup PO 5000008380 dengan DO "IV/26.001"
    $po2 = PurchaseOrderIssued::create([
        'purchase_order_no' => '5000008380',
        'material_type' => 'ZRM',
        'vendor_name' => 'PT VENDOR B',
        'description' => 'MATERIAL B',
        'qty_po' => 5,
        'uoi' => 'EA',
    ]);
    $doSlash = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'IV/26.001',
        'document_code' => 'DO-5000008380-1',
        'status' => 'Diterima',
    ]);
    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $doSlash->id,
        'purchase_order_issued_id' => $po2->id,
        'material_type' => 'ZRM',
        'description' => 'MATERIAL B',
        'uoi' => 'EA',
    ]);

    // Setup PO 5300061623 dengan DO "371"
    $po3 = PurchaseOrderIssued::create([
        'purchase_order_no' => '5300061623',
        'material_type' => 'ZRM',
        'vendor_name' => 'PT VENDOR C',
        'description' => 'MATERIAL C',
        'qty_po' => 2,
        'uoi' => 'SET',
    ]);
    $doPrefix = DeliveryOrderReceipt::create([
        'delivery_order_no' => '371',
        'document_code' => 'DO-5300061623-1',
        'status' => 'Diterima',
    ]);
    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $doPrefix->id,
        'purchase_order_issued_id' => $po3->id,
        'material_type' => 'ZRM',
        'description' => 'MATERIAL C',
        'uoi' => 'SET',
    ]);

    // DO orphan tanpa link PO detail di DB -> harus bisa ditautkan lewat No. DO langsung
    $doOrphan = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'SADO2609-0075',
        'document_code' => 'DO-ORPHAN-1',
        'status' => 'Diterima',
    ]);

    // DO milik PO lain (5000004330) dengan nomor pendek "40" -> TIDAK BOLEH tertaut ke PO berbeda 5300059489
    $poOther = PurchaseOrderIssued::create([
        'purchase_order_no' => '5000004330',
        'vendor_name' => 'PT OTHER',
    ]);
    $doOther = DeliveryOrderReceipt::create([
        'delivery_order_no' => '40',
        'document_code' => 'DO-OTHER-40',
        'status' => 'Diterima',
    ]);
    DeliveryOrderReceiptDetail::create([
        'delivery_order_receipt_id' => $doOther->id,
        'purchase_order_issued_id' => $poOther->id,
    ]);

    $csv = <<<'CSV'
Tanggal Terima GRS,Jenis,No. GRS,No. PO,PIC,Vendor,Tanggal Info GRS,No. Invoice,No. DO
2026-10-01,Sparepart,5009100001,5300061881,ALEX,PT VENDOR A,2026-10-02,INV-DO-1,"9510207908
9510895235"
2026-10-01,Sparepart,5009100002,5000008380,ALEX,PT VENDOR B,2026-10-02,INV-DO-2,"PESWA/IV/26,001"
2026-10-01,Sparepart,5009100003,5300061623,ALEX,PT VENDOR C,2026-10-02,INV-DO-3,371/IX/25
2026-10-01,Sparepart,5009100004,,ALEX,PT ORPHAN,2026-10-02,INV-DO-4,SADO2609-0075
2026-10-01,Sparepart,5009100005,5300059489,ALEX,PT VENDOR X,2026-10-02,INV-DO-5,40
CSV;

    $summary = app(MonitoringBarangImportService::class)->importFromString($csv);

    expect($summary['created'])->toBe(5)
        ->and($summary['linked_do'])->toBe(4)
        ->and(InvoicingReceipt::where('no_grs', '5009100001')->value('delivery_order_receipt_id'))->toBe($doMulti1->id)
        ->and(InvoicingReceipt::where('no_grs', '5009100002')->value('delivery_order_receipt_id'))->toBe($doSlash->id)
        ->and(InvoicingReceipt::where('no_grs', '5009100003')->value('delivery_order_receipt_id'))->toBe($doPrefix->id)
        ->and(InvoicingReceipt::where('no_grs', '5009100004')->value('delivery_order_receipt_id'))->toBe($doOrphan->id)
        ->and(InvoicingReceipt::where('no_grs', '5009100005')->value('delivery_order_receipt_id'))->toBeNull();
});
