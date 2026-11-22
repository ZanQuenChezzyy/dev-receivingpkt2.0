<?php

use App\Filament\Resources\CashierTransmittals\CashierTransmittalResource;
use App\Filament\Resources\CashierTransmittals\Tables\CashierTransmittalsTable;
use App\Filament\Resources\InvoicingReceipts\Tables\InvoicingReceiptsTable;
use App\Http\Controllers\Admin\CashierTransmittalPrintController;
use App\Models\CashierTransmittal;
use App\Models\InvoicingReceipt;
use App\Models\User;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

beforeEach(function () {
    Schema::dropIfExists('invoicing_receipt_items');
    Schema::dropIfExists('invoicing_receipts');
    Schema::dropIfExists('cashier_transmittals');
    Schema::dropIfExists('grs_rdtv_items');
    Schema::dropIfExists('delivery_order_receipts');
    Schema::dropIfExists('purchase_order_issueds');
    Schema::dropIfExists('vendors');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->string('npk')->nullable();
        $table->string('avatar_url')->nullable();
        $table->timestamps();
    });

    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('vendor_number')->nullable();
        $table->text('email')->nullable();
        $table->timestamps();
    });

    Schema::create('purchase_order_issueds', function (Blueprint $table) {
        $table->id();
        $table->string('purchase_order_no')->nullable();
        $table->string('vendor_name')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_order_receipts', function (Blueprint $table) {
        $table->id();
        $table->string('delivery_order_no')->nullable();
        $table->timestamps();
    });

    Schema::create('grs_rdtv_items', function (Blueprint $table) {
        $table->id();
        $table->string('file_path')->nullable();
        $table->timestamps();
    });

    $createCashierMigration = include database_path('migrations/2026_11_22_194126_create_cashier_transmittals_table.php');
    $createCashierMigration->up();

    $createInvoicingMigration = include database_path('migrations/2026_10_24_121247_create_invoicing_receipts_table.php');
    $createInvoicingMigration->up();

    $addCashierFkMigration = include database_path('migrations/2026_11_22_194127_add_cashier_transmittal_id_to_invoicing_receipts_table.php');
    $addCashierFkMigration->up();
});

it('merges invoices from multiple PICs on the same sent date into one single CashierTransmittal and tracks daily history', function () {
    $picAlex = User::create([
        'name' => 'Alex',
        'email' => 'alex@receiving.com',
        'password' => Hash::make('password'),
    ]);
    $picCamel = User::create([
        'name' => 'Camel',
        'email' => 'camel@receiving.com',
        'password' => Hash::make('password'),
    ]);

    // PIC 1 (Alex) punya 2 invoice GRS
    $invAlex1 = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300081001',
        'vendor_name' => 'PT Vendor Satu',
        'no_invoice' => 'INV-ALEX-01',
        'faktur_pajak' => '010.001-26.00000001',
        'subtotal_exclude_ppn' => 10000000,
        'pic_id' => $picAlex->id,
        'status' => 'Terverifikasi',
    ]);
    $invAlex2 = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300081002',
        'vendor_name' => 'PT Vendor Dua',
        'no_invoice' => 'INV-ALEX-02',
        'faktur_pajak' => '010.001-26.00000002',
        'subtotal_exclude_ppn' => 15000000,
        'pic_id' => $picAlex->id,
        'status' => 'Terverifikasi',
    ]);

    // PIC 2 (Camel) punya 2 invoice GRS + 1 RDTV (harus dilewati)
    $invCamel1 = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300081003',
        'vendor_name' => 'PT Vendor Tiga',
        'no_invoice' => 'INV-CAMEL-01',
        'faktur_pajak' => '010.001-26.00000003',
        'subtotal_exclude_ppn' => 20000000,
        'pic_id' => $picCamel->id,
        'status' => 'Terverifikasi',
    ]);
    $rdtvCamel = InvoicingReceipt::create([
        'jenis' => 'RDTV',
        'no_po' => '5300081999',
        'vendor_name' => 'PT Retur Barang',
        'no_rdtv' => '4900099001',
        'pic_id' => $picCamel->id,
        'status' => 'Menunggu Verifikasi',
    ]);

    // 1. Alex kirim tanggal 2026-11-22 -> otomatis membuat TR-KSR/20261122/001
    $resAlex = CashierTransmittal::attachReceipts([$invAlex1, $invAlex2], '2026-11-22', $picAlex->id);

    // 2. Camel kirim tanggal yang SAMA (2026-11-22) -> otomatis GABUNG ke TR-KSR/20261122/001 (bukan buat transmittal baru!)
    $resCamel = CashierTransmittal::attachReceipts([$invCamel1, $rdtvCamel], '2026-11-22', $picCamel->id);

    expect($resAlex['transmittal'])->not->toBeNull()
        ->and($resCamel['transmittal'])->not->toBeNull()
        ->and($resCamel['transmittal']->id)->toBe($resAlex['transmittal']->id)
        ->and($resAlex['transmittal']->transmittal_no)->toBe('TR-KSR/20261122/001')
        ->and($resCamel['attached_count'])->toBe(1)
        ->and($resCamel['skipped_rdtv_count'])->toBe(1)
        ->and(CashierTransmittal::count())->toBe(1)
        ->and($resAlex['transmittal']->invoicingReceipts()->count())->toBe(3)
        ->and((float) $resAlex['transmittal']->invoicingReceipts()->sum('subtotal_exclude_ppn'))->toBe(45000000.0);

    // RDTV tidak boleh masuk transmittal
    $rdtvCamel->refresh();
    expect($rdtvCamel->cashier_transmittal_id)->toBeNull();

    // 3. Pengiriman di tanggal BERBEDA (2026-11-23) membentuk riwayat harian terpisah TR-KSR/20261123/001
    $invNextDay = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300082001',
        'vendor_name' => 'PT Vendor Besok',
        'no_invoice' => 'INV-NEXT-01',
        'subtotal_exclude_ppn' => 5000000,
        'pic_id' => $picAlex->id,
        'status' => 'Terverifikasi',
    ]);

    $resNextDay = CashierTransmittal::attachReceipts([$invNextDay], '2026-11-23', $picAlex->id);

    expect($resNextDay['transmittal']->transmittal_no)->toBe('TR-KSR/20261123/001')
        ->and(CashierTransmittal::count())->toBe(2)
        ->and($resNextDay['transmittal']->invoicingReceipts()->count())->toBe(1);
});

it('creates a new sequential batch on the same date once the previous batch is locked', function () {
    $pic = User::create([
        'name' => 'Nafisa',
        'email' => 'nafisa@receiving.com',
        'password' => Hash::make('password'),
    ]);

    $morningInv = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300083001',
        'no_invoice' => 'INV-PAGI-01',
        'subtotal_exclude_ppn' => 12000000,
        'pic_id' => $pic->id,
        'status' => 'Terverifikasi',
    ]);
    $afternoonInv = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300083002',
        'no_invoice' => 'INV-SORE-01',
        'subtotal_exclude_ppn' => 8000000,
        'pic_id' => $pic->id,
        'status' => 'Terverifikasi',
    ]);

    $batch1 = CashierTransmittal::attachReceipts([$morningInv], '2026-11-22', $pic->id)['transmittal'];
    $batch1->update(['status' => CashierTransmittal::STATUS_LOCKED]);

    $batch2 = CashierTransmittal::attachReceipts([$afternoonInv], '2026-11-22', $pic->id)['transmittal'];

    expect($batch1->transmittal_no)->toBe('TR-KSR/20261122/001')
        ->and($batch2->transmittal_no)->toBe('TR-KSR/20261122/002')
        ->and($batch1->invoicingReceipts()->count())->toBe(1)
        ->and($batch2->invoicingReceipts()->count())->toBe(1);
});

it('pulls unassigned historical receipts by date, prints combined PDF with transmittal number, and detaches safely on delete', function () {
    $pic = User::create([
        'name' => 'Rizki',
        'email' => 'rizki@receiving.com',
        'password' => Hash::make('password'),
    ]);

    $legacy1 = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300084001',
        'vendor_name' => 'PT Arsip Jaya',
        'no_invoice' => 'INV-ARSIP-01',
        'faktur_pajak' => '010.001-26.99887766',
        'subtotal_exclude_ppn' => 33000000,
        'tanggal_kirim_kasir' => '2026-11-20',
        'pic_id' => $pic->id,
        'status' => 'Selesai',
    ]);
    $legacy2 = InvoicingReceipt::create([
        'jenis' => 'GRS',
        'no_po' => '5300084002',
        'vendor_name' => 'PT Arsip Makmur',
        'no_invoice' => 'INV-ARSIP-02',
        'subtotal_exclude_ppn' => 17000000,
        'tanggal_kirim_kasir' => '2026-11-20',
        'pic_id' => $pic->id,
        'status' => 'Terkirim Kasir',
    ]);

    $synced = CashierTransmittal::syncUnassignedReceiptsForDate('2026-11-20', $pic->id);
    $transmittal = $synced['transmittal'];

    expect($transmittal)->not->toBeNull()
        ->and($synced['attached_count'])->toBe(2)
        ->and($transmittal->transmittal_no)->toBe('TR-KSR/20261120/001')
        ->and($transmittal->printed_at)->toBeNull();

    // Cetak PDF gabungan melalui controller
    $controller = new CashierTransmittalPrintController;
    $response = $controller->printTransmittal($transmittal);
    $transmittal->refresh();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF')
        ->and($transmittal->printed_at)->not->toBeNull();

    // Pastikan HTML PDF memuat nomor transmittal gabungan
    $html = view('pdf.cashier_transmittal', [
        'receipts' => collect([$legacy1, $legacy2]),
        'printedAt' => now(),
        'sentDate' => $transmittal->sent_date,
        'transmittalNo' => $transmittal->transmittal_no,
        'destination' => $transmittal->destination,
        'senderName' => $pic->name,
        'totalSubtotalExclude' => 50000000,
        'watermarkText' => 'Dicetak menggunakan Mokondo V2.0 - dev.receivingpkt.com',
    ])->render();

    expect($html)
        ->toContain('TR-KSR/20261120/001')
        ->toContain('PT Arsip Jaya')
        ->toContain('PT Arsip Makmur')
        ->toContain('Rp 50.000.000');

    // Hapus transmittal tidak boleh menghapus data invoice barang (hanya melepas FK)
    $transmittal->delete();
    $legacy1->refresh();
    $legacy2->refresh();

    expect(InvoicingReceipt::count())->toBe(2)
        ->and($legacy1->cashier_transmittal_id)->toBeNull()
        ->and($legacy2->cashier_transmittal_id)->toBeNull();
});

it('exposes CashierTransmittalResource table columns and links No. Transmittal Kasir on InvoicingReceiptsTable', function () {
    $livewire = new class extends Component implements HasSchemas, HasTable
    {
        use InteractsWithSchemas;
        use InteractsWithTable;

        public function render()
        {
            return '<div></div>';
        }
    };

    $transmittalTable = CashierTransmittalsTable::configure(Table::make($livewire));
    $transmittalColumns = collect($transmittalTable->getColumns())->keyBy(fn ($c) => $c->getName());

    expect(CashierTransmittalResource::getNavigationLabel())->toBe('Transmittal Kasir')
        ->and($transmittalColumns)->toHaveKey('transmittal_no')
        ->and($transmittalColumns)->toHaveKey('sent_date')
        ->and($transmittalColumns)->toHaveKey('status')
        ->and($transmittalColumns)->toHaveKey('invoicing_receipts_count')
        ->and($transmittalColumns)->toHaveKey('invoicing_receipts_sum_subtotal_exclude_ppn')
        ->and($transmittalColumns)->toHaveKey('involved_pics');

    $invoicingTable = InvoicingReceiptsTable::configure(Table::make($livewire));
    $invoicingColumns = collect($invoicingTable->getColumns())->keyBy(fn ($c) => $c->getName());

    expect($invoicingColumns)->toHaveKey('cashierTransmittal.transmittal_no');
});
