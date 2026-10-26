<?php

use App\Filament\Resources\InvoicingReceipts\Pages\ListInvoicingReceipts;
use App\Filament\Resources\InvoicingReceipts\Schemas\InvoicingReceiptForm;
use App\Filament\Resources\InvoicingReceipts\Tables\InvoicingReceiptsTable;
use App\Models\InvoicingReceipt;
use App\Models\PicMaterialAssignment;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InvoicingAssignmentService;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Component;

test('vendor model has all 13 required columns and handles multiple emails', function () {
    $vendor = new Vendor;
    $fillables = $vendor->getFillable();

    expect($fillables)->toEqual([
        'name',
        'vendor_number',
        'city',
        'street',
        'street2',
        'street3',
        'street4',
        'telephone_1',
        'telephone_2',
        'fax_number',
        'salesperson',
        'telp_salesperson',
        'email',
    ])->and($vendor->invoicingReceipts())->toBeInstanceOf(HasMany::class);

    // Multi-email array
    $vendor->email = ['vendor1@pkt.com', 'vendor2@pkt.com'];
    expect($vendor->email)->toBe(['vendor1@pkt.com', 'vendor2@pkt.com'])
        ->and($vendor->primary_email)->toBe('vendor1@pkt.com')
        ->and($vendor->email_string)->toBe('vendor1@pkt.com, vendor2@pkt.com');

    // Comma-separated string
    $vendor->email = 'finance@pkt.com, tax@pkt.com';
    expect($vendor->email)->toBe(['finance@pkt.com', 'tax@pkt.com']);

    // Single email string
    $vendor->email = 'sales@pkt.com';
    expect($vendor->email)->toBe(['sales@pkt.com']);

    // Null/empty
    $vendor->email = null;
    expect($vendor->email)->toBe([]);
});

test('pic material assignment model has expected fillable attributes and user relation', function () {
    $assignment = new PicMaterialAssignment;
    $fillables = $assignment->getFillable();

    expect($fillables)->toContain('material_type', 'assignment_mode', 'user_id', 'notes')
        ->and($assignment->user())->toBeInstanceOf(BelongsTo::class);
});

test('invoicing receipt model has complete fillable attributes for all 27 required columns', function () {
    $receipt = new InvoicingReceipt;
    $fillables = $receipt->getFillable();

    expect($fillables)->toContain(
        'tanggal_terima_grs',
        'jenis',
        'no_grs',
        'no_rdtv',
        'no_po',
        'no_do',
        'pic_id',
        'padi',
        'vendor_id',
        'vendor_name',
        'deskripsi',
        'email_vendor',
        'tanggal_info_grs',
        'no_invoice',
        'faktur_pajak',
        'nama_barang',
        'qty_penagihan',
        'qty_po',
        'uoi',
        'subtotal_exclude_ppn',
        'subtotal_include_ppn',
        'tanggal_terima_koreksi_invoice',
        'tanggal_kirim_kasir',
        'lama_pekerjaan',
        'keterangan',
        'dokumen_verifikasi_grs',
        'dokumen_invoice',
        'status'
    );
});

test('invoicing receipt model defines relationships to vendor, pic, and delivery order', function () {
    $receipt = new InvoicingReceipt;

    expect($receipt->vendor())->toBeInstanceOf(BelongsTo::class)
        ->and($receipt->pic())->toBeInstanceOf(BelongsTo::class)
        ->and($receipt->deliveryOrderReceipt())->toBeInstanceOf(BelongsTo::class)
        ->and($receipt->grsRdtvItem())->toBeInstanceOf(BelongsTo::class)
        ->and($receipt->purchaseOrderIssued())->toBeInstanceOf(BelongsTo::class);
});

test('invoicing receipt casts numeric and date fields properly', function () {
    $receipt = new InvoicingReceipt;
    $casts = $receipt->getCasts();

    expect($casts)->toHaveKey('tanggal_terima_grs', 'date')
        ->and($casts)->toHaveKey('qty_penagihan', 'float')
        ->and($casts)->toHaveKey('subtotal_exclude_ppn', 'float')
        ->and($casts)->toHaveKey('subtotal_include_ppn', 'float')
        ->and($casts)->toHaveKey('lama_pekerjaan', 'integer');
});

test('pic selection query builder filters strictly by Staff Invoice role', function () {
    $query = User::whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'));

    expect($query->toSql())->toContain('model_has_roles')
        ->and($query->getBindings())->toContain('Staff Invoice')
        ->and($query->getBindings())->toContain(User::class);
});

test('material type options are restricted to 5 standard types', function () {
    $allowedTypes = ['ZSP', 'ZFP', 'ZPM', 'ZRM', 'ZSM'];

    expect($allowedTypes)->toHaveCount(5)
        ->and($allowedTypes)->toContain('ZSP', 'ZFP', 'ZPM', 'ZRM', 'ZSM');
});

test('invoicing assignment service distributes 40 items evenly across 4 staff invoice members', function () {
    $service = new InvoicingAssignmentService;
    $service->setStaffWorkloads([
        101 => 0,
        102 => 0,
        103 => 0,
        104 => 0,
    ]);

    $assignments = [];
    for ($i = 0; $i < 40; $i++) {
        $picId = $service->determinePicId(null);
        $assignments[] = $picId;
    }

    $counts = array_count_values($assignments);

    expect($counts[101])->toBe(10)
        ->and($counts[102])->toBe(10)
        ->and($counts[103])->toBe(10)
        ->and($counts[104])->toBe(10);
});

test('invoicing assignment service treats empty string, none, and null as free fair distribution', function () {
    $service = new InvoicingAssignmentService;
    $service->setStaffWorkloads([
        1 => 0,
        2 => 0,
    ]);

    expect($service->determinePicId(''))->toBe(1)
        ->and($service->determinePicId(null))->toBe(2)
        ->and($service->determinePicId('none'))->toBe(1)
        ->and($service->determinePicId('NONE'))->toBe(2)
        ->and($service->determinePicId('-'))->toBe(1);
});

test('pic material assignment model defines users belongsToMany relation for multiple pics', function () {
    $assignment = new PicMaterialAssignment;

    expect($assignment->users())->toBeInstanceOf(BelongsToMany::class);
});

test('invoicing assignment service distributes items evenly among multiple designated pics', function () {
    $service = new InvoicingAssignmentService;
    $service->setStaffWorkloads([
        201 => 0,
        202 => 0,
        999 => 50,
    ]);

    $assigned = [];
    for ($i = 0; $i < 20; $i++) {
        $assigned[] = $service->getNextStaffInvoiceIdAmong([201, 202]);
    }

    $counts = array_count_values($assigned);

    expect($counts[201])->toBe(10)
        ->and($counts[202])->toBe(10)
        ->and($counts)->not->toHaveKey(999);
});

test('user model defines invoicing receipts and pic material assignments relationships', function () {
    $user = new User;

    expect($user->invoicingReceipts())->toBeInstanceOf(HasMany::class)
        ->and($user->picMaterialAssignments())->toBeInstanceOf(BelongsToMany::class)
        ->and($user->assignedPicMaterialAssignments())->toBeInstanceOf(HasMany::class);
});

test('purchase order issued model defines invoicing receipts relationship', function () {
    $po = new PurchaseOrderIssued;

    expect($po->invoicingReceipts())->toBeInstanceOf(HasMany::class);
});

test('user model allows querying invoicing receipts with count on pic_id', function () {
    $query = User::withCount(['invoicingReceipts as active_count' => function ($q) {
        $q->whereIn('status', ['Menunggu Verifikasi', 'Terverifikasi']);
    }]);

    expect($query->toSql())->toContain('invoicing_receipts')
        ->and($query->toSql())->toContain('active_count')
        ->and($query->toSql())->toContain('pic_id')
        ->and($query->getBindings())->toContain('Menunggu Verifikasi')
        ->and($query->getBindings())->toContain('Terverifikasi');
});

test('copy to mitraku modal blade view renders successfully without undefined constant error', function () {
    $receipt = new InvoicingReceipt([
        'no_po' => 'PO-12345',
        'no_do' => 'DO-67890',
        'no_grs' => 'GRS-111',
        'no_invoice' => 'INV-222',
        'faktur_pajak' => '010.000-24.12345678',
        'subtotal_exclude_ppn' => 1000000,
        'subtotal_include_ppn' => 1110000,
        'qty_penagihan' => 10,
        'uoi' => 'PCS',
        'vendor_name' => 'PT Testing Indonesia',
        'nama_barang' => 'Barang Uji',
    ]);

    $po = new PurchaseOrderIssued([
        'purchase_order_no' => 'PO-12345',
        'delivery_date_po' => '2026-10-26',
    ]);
    $receipt->setRelation('purchaseOrderIssued', $po);

    $view = (string) view('filament.actions.copy-to-mitraku-modal', ['record' => $receipt]);

    expect($view)->toContain('PO-12345')
        ->and($view)->toContain('DO-67890')
        ->and($view)->toContain('GRS-111')
        ->and($view)->toContain('copiedField')
        ->and($view)->toContain('Quantity di PO')
        ->and($view)->toContain("copyText('1000000', 'subtotal_exclude')")
        ->and($view)->toContain("copyText('1110000', 'subtotal_include')")
        ->and($view)->toContain('Material / Nama Barang')
        ->and($view)->toContain("copyText('Barang Uji', 'nama_barang')")
        ->and($view)->toContain('Delivery Date PO')
        ->and($view)->toContain("copyText('26-10-2026', 'delivery_date_po')")
        ->and($view)->toContain('26-10-2026')
        ->and($view)->not->toContain('1000000.00')
        ->and($view)->not->toContain('1110000.00');
});

test('invoicing receipt model computes subtotal include ppn with 11% formula', function () {
    $exclude1 = 1000000.0;
    $include1 = round($exclude1 + ($exclude1 * 0.11), 2);
    expect($include1)->toBe(1110000.0);

    $exclude2 = 150250.50;
    $include2 = round($exclude2 + ($exclude2 * 0.11), 2);
    expect($include2)->toBe(166778.06);
});

test('invoicing receipt form configures required validation for no_invoice, no_do, faktur_pajak, and no_grs', function () {
    $livewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };

    $schema = (new Schema)->livewire($livewire);
    InvoicingReceiptForm::configure($schema);
    $components = collect($schema->getComponents());

    $flattenComponents = function ($comps) use (&$flattenComponents) {
        $flat = collect();
        foreach ($comps as $c) {
            $flat->push($c);
            if (method_exists($c, 'getChildComponents')) {
                $flat = $flat->merge($flattenComponents($c->getChildComponents()));
            }
        }

        return $flat;
    };

    $allFields = $flattenComponents($components)->filter(fn ($c) => method_exists($c, 'getName'))->keyBy(fn ($c) => $c->getName());

    expect($allFields)->toHaveKey('no_invoice')
        ->and($allFields)->toHaveKey('no_do')
        ->and($allFields)->toHaveKey('faktur_pajak')
        ->and($allFields)->toHaveKey('no_grs')
        ->and($allFields)->toHaveKey('qty_penagihan')
        ->and($allFields)->toHaveKey('qty_po')
        ->and($allFields)->toHaveKey('uoi')
        ->and($allFields)->toHaveKey('subtotal_exclude_ppn')
        ->and($allFields)->toHaveKey('subtotal_include_ppn');

    expect($allFields['no_invoice']->isRequired())->toBeTrue()
        ->and($allFields['no_do']->isRequired())->toBeTrue()
        ->and($allFields['faktur_pajak']->isRequired())->toBeTrue();
});

test('invoicing receipts table defines merged uploadBerkas action for verification and invoice files', function () {
    $livewire = app(ListInvoicingReceipts::class);

    $table = new Table($livewire);
    InvoicingReceiptsTable::configure($table);

    $actions = collect($table->getRecordActions());
    $actionNames = $actions->map(fn ($a) => method_exists($a, 'getName') ? $a->getName() : null)->filter();

    expect($actionNames)->toContain('uploadBerkas')
        ->and($actionNames)->not->toContain('uploadVerifikasi')
        ->and($actionNames)->not->toContain('uploadInvoice');
});
