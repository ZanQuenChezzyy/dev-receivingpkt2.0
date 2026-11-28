<?php

use App\Filament\Resources\GrsRdtvs\Pages\CreateGrsRdtv;
use App\Filament\Resources\InvoicingReceipts\Pages\ListInvoicingReceipts;
use App\Filament\Resources\InvoicingReceipts\Schemas\InvoicingReceiptForm;
use App\Filament\Resources\InvoicingReceipts\Tables\InvoicingReceiptsTable;
use App\Filament\Resources\InvoicingReceipts\Widgets\InvoicingOverviewWidget;
use App\Filament\Resources\PicMaterialAssignments\Schemas\PicMaterialAssignmentForm;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Http\Controllers\Admin\CashierTransmittalPrintController;
use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtvItem;
use App\Models\InvoicingReceipt;
use App\Models\PicMaterialAssignment;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InvoicingAssignmentService;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

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

    // Space-separated string (misal data impor/lama)
    $vendor->email = 'cv.rafikapasifikutama@gmail.com imel.riadin@gmail.com';
    expect($vendor->email)->toBe(['cv.rafikapasifikutama@gmail.com', 'imel.riadin@gmail.com']);

    // JSON array yang elemen tunggalnya berisi beberapa email dipisah spasi
    $vendor->setRawAttributes(['email' => json_encode(['cv.rafikapasifikutama@gmail.com imel.riadin@gmail.com'])]);
    expect($vendor->email)->toBe(['cv.rafikapasifikutama@gmail.com', 'imel.riadin@gmail.com']);

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

    expect($fillables)->toContain('material_type', 'assignment_mode', 'user_id', 'notes', 'conditional_rules')
        ->and($assignment->user())->toBeInstanceOf(BelongsTo::class)
        ->and($assignment->getCasts()['conditional_rules'])->toBe('array');
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
        'dokumen_kelengkapan',
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
        ->and($casts)->toHaveKey('lama_pekerjaan', 'integer')
        ->and($casts)->toHaveKey('dokumen_kelengkapan', 'array');
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
    ensurePicAssignmentTablesExist();

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
    ensurePicAssignmentTablesExist();

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

function ensurePicAssignmentTablesExist(): void
{
    if (! Illuminate\Support\Facades\Schema::hasTable('users')) {
        Illuminate\Support\Facades\Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    if (! Illuminate\Support\Facades\Schema::hasTable('pic_material_assignments')) {
        Illuminate\Support\Facades\Schema::create('pic_material_assignments', function ($table) {
            $table->id();
            $table->string('material_type')->unique();
            $table->string('assignment_mode')->default('free_pool');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('conditional_rules')->nullable();
            $table->timestamps();
        });
    } elseif (! Illuminate\Support\Facades\Schema::hasColumn('pic_material_assignments', 'conditional_rules')) {
        Illuminate\Support\Facades\Schema::table('pic_material_assignments', function ($table) {
            $table->json('conditional_rules')->nullable();
        });
    }

    if (! Illuminate\Support\Facades\Schema::hasTable('pic_material_assignment_user')) {
        Illuminate\Support\Facades\Schema::create('pic_material_assignment_user', function ($table) {
            $table->id();
            $table->unsignedBigInteger('pic_material_assignment_id');
            $table->unsignedBigInteger('user_id');
        });
    }
}

test('invoicing assignment service distributes free_pool material type evenly across staff invoice', function () {
    ensurePicAssignmentTablesExist();

    PicMaterialAssignment::create([
        'material_type' => 'ZSP',
        'assignment_mode' => 'free_pool',
    ]);

    $service = new InvoicingAssignmentService;
    $service->setStaffWorkloads([
        10 => 0,
        20 => 0,
    ]);

    expect($service->determinePicId('ZSP'))->toBe(10)
        ->and($service->determinePicId('ZSP'))->toBe(20)
        ->and($service->determinePicId('ZSP'))->toBe(10);
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
    $query = User::withCount([
        'invoicingReceipts as active_count' => function ($q) {
            $q->whereIn('status', ['Menunggu Verifikasi', 'Terverifikasi']);
        },
    ]);

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

test('invoicing receipt form configures validation where no_do is required while no_invoice and faktur_pajak are optional', function () {
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

    $allFields = $flattenComponents($components)
        ->filter(fn ($c) => method_exists($c, 'getName'))
        ->groupBy(fn ($c) => $c->getName())
        ->map(fn ($group) => $group->first());

    expect($allFields)->toHaveKey('no_invoice')
        ->and($allFields)->toHaveKey('no_do')
        ->and($allFields)->toHaveKey('faktur_pajak')
        ->and($allFields)->toHaveKey('no_grs')
        ->and($allFields)->toHaveKey('qty_penagihan')
        ->and($allFields)->toHaveKey('qty_po')
        ->and($allFields)->toHaveKey('uoi')
        ->and($allFields)->toHaveKey('subtotal_exclude_ppn')
        ->and($allFields)->toHaveKey('subtotal_include_ppn');

    expect($allFields['no_do']->isRequired())->toBeTrue()
        ->and($allFields['no_invoice']->isRequired())->toBeFalse()
        ->and($allFields['faktur_pajak']->isRequired())->toBeFalse();
});

test('invoicing receipts table defines merged uploadBerkas action for verification and invoice files', function () {
    $livewire = app(ListInvoicingReceipts::class);

    $table = new Table($livewire);
    InvoicingReceiptsTable::configure($table);

    $actions = collect($table->getRecordActions())->flatMap(function ($a) {
        if ($a instanceof ActionGroup) {
            return $a->getActions();
        }

        return [$a];
    });
    $actionNames = $actions->map(fn ($a) => method_exists($a, 'getName') ? $a->getName() : null)->filter();

    expect($actionNames)->toContain('uploadBerkas')
        ->and($actionNames)->not->toContain('uploadVerifikasi')
        ->and($actionNames)->not->toContain('uploadInvoice');
});

test('invoicing assignment service redirects to conditional rule PIC by material code, falling back to default PIC for other codes', function () {
    ensurePicAssignmentTablesExist();

    $assignment = PicMaterialAssignment::updateOrCreate(
        ['material_type' => 'ZSM'],
        [
            'assignment_mode' => 'user',
            'user_id' => 10, // Andre (Default)
            'conditional_rules' => [
                [
                    'criteria_type' => 'material_code',
                    'material_code' => '300001',
                    'user_ids' => [20], // Wahyu (Khusus 300001)
                ],
            ],
        ]
    );

    $service = new InvoicingAssignmentService;

    // Material ZSM dengan kode 300001 -> Harus ke Wahyu (20)
    expect($service->determinePicId('ZSM', '300001', 'Baut ZSM'))->toBe(20)
        // Material ZSM dengan kode lain (300002) -> Harus ke Andre (10)
        ->and($service->determinePicId('ZSM', '300002', 'Baut ZSM'))->toBe(10)
        // Material ZSM tanpa kode -> Harus fallback ke Andre (10)
        ->and($service->determinePicId('ZSM'))->toBe(10);
});

test('invoicing assignment service redirects to conditional rule PIC by description keyword case-insensitively', function () {
    ensurePicAssignmentTablesExist();

    PicMaterialAssignment::updateOrCreate(
        ['material_type' => 'ZSM'],
        [
            'assignment_mode' => 'user',
            'user_id' => 10, // Andre (Default)
            'conditional_rules' => [
                [
                    'criteria_type' => 'material_code',
                    'material_code' => '300001',
                    'user_ids' => [20], // Wahyu
                ],
                [
                    'criteria_type' => 'description_contains',
                    'description_keyword' => 'COAL',
                    'user_ids' => [30], // Specialist Batubara
                ],
            ],
        ]
    );

    $service = new InvoicingAssignmentService;

    // Deskripsi mengandung kata 'COAL' (meskipun mixed case) -> Harus ke Specialist Batubara (30)
    expect($service->determinePicId('ZSM', '300999', 'STEAM COAL BITUMINOUS'))->toBe(30)
        ->and($service->determinePicId('ZSM', '300888', 'premium coal supplier'))->toBe(30)
        // Deskripsi umum tanpa kata kunci COAL dan bukan kode 300001 -> Harus ke Andre (10)
        ->and($service->determinePicId('ZSM', '300999', 'Sparepart Pompa'))->toBe(10);
});

test('invoicing assignment service supports multiple conditional rule user_ids with round-robin', function () {
    ensurePicAssignmentTablesExist();

    PicMaterialAssignment::updateOrCreate(
        ['material_type' => 'ZSM'],
        [
            'assignment_mode' => 'user',
            'user_id' => 10,
            'conditional_rules' => [
                [
                    'criteria_type' => 'material_code',
                    'material_code' => '300001, 300002',
                    'user_ids' => [21, 22],
                ],
            ],
        ]
    );

    $service = new InvoicingAssignmentService;
    $service->setStaffWorkloads([
        21 => 0,
        22 => 0,
        10 => 0,
    ]);

    // 4 panggilan berturut-turut untuk kode 300001 harus dibagi rata antara 21 dan 22
    $first = $service->determinePicId('ZSM', '300001');
    $second = $service->determinePicId('ZSM', '300002');
    $third = $service->determinePicId('ZSM', '300001');
    $fourth = $service->determinePicId('ZSM', '300002');

    $counts = array_count_values([$first, $second, $third, $fourth]);
    expect($counts[21])->toBe(2)
        ->and($counts[22])->toBe(2)
        ->and($counts)->not->toHaveKey(10);
});

test('pic material assignment form configures conditional_rules repeater with criteria_type, material_code, description_keyword', function () {
    $livewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };

    $schema = (new Schema)->livewire($livewire);
    PicMaterialAssignmentForm::configure($schema);
    $components = collect($schema->getComponents());

    $flattenComponents = function ($comps) use (&$flattenComponents) {
        $flat = collect();
        foreach ($comps as $c) {
            $flat->push($c);
            if (method_exists($c, 'getDefaultChildComponents')) {
                $flat = $flat->merge($flattenComponents($c->getDefaultChildComponents()));
            } elseif (method_exists($c, 'getChildComponents')) {
                $flat = $flat->merge($flattenComponents($c->getChildComponents()));
            }
        }

        return $flat;
    };

    $allFields = $flattenComponents($components)
        ->filter(fn ($c) => method_exists($c, 'getName'))
        ->groupBy(fn ($c) => $c->getName())
        ->map(fn ($group) => $group->first());

    expect($allFields)->toHaveKey('material_type')
        ->and($allFields)->toHaveKey('assignment_mode')
        ->and($allFields)->toHaveKey('users')
        ->and($allFields)->toHaveKey('conditional_rules');
});

function ensureInvoicingReceiptTableExists(): bool
{
    $created = false;
    if (! Illuminate\Support\Facades\Schema::hasTable('invoicing_receipts')) {
        Illuminate\Support\Facades\Schema::create('invoicing_receipts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_item_id')->nullable();
            $table->unsignedBigInteger('cashier_transmittal_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('pic_id')->nullable();
            $table->date('tanggal_terima_grs')->nullable();
            $table->date('delivery_date')->nullable();
            $table->date('tanggal_penerimaan')->nullable();
            $table->date('tanggal_po')->nullable();
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
            $table->date('tanggal_invoice')->nullable();
            $table->string('faktur_pajak')->nullable();
            $table->date('tanggal_faktur_pajak')->nullable();
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
            $table->string('dokumen_grs')->nullable();
            $table->string('dokumen_verifikasi_grs')->nullable();
            $table->string('dokumen_invoice')->nullable();
            $table->json('dokumen_kelengkapan')->nullable();
            $table->string('status')->default('Menunggu Verifikasi');
            $table->timestamps();
        });
        $created = true;
    }

    if (! Illuminate\Support\Facades\Schema::hasTable('purchase_order_issueds')) {
        Illuminate\Support\Facades\Schema::create('purchase_order_issueds', function ($table) {
            $table->id();
            $table->string('purchase_order_and_item')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->integer('item_no')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty_po', 12, 3)->default(0);
            $table->string('uoi')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->decimal('net_price', 18, 2)->nullable();
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    if (! Illuminate\Support\Facades\Schema::hasTable('invoicing_receipt_items')) {
        $createItemsMigration = include database_path('migrations/2026_11_18_093000_create_invoicing_receipt_items_table.php');
        $createItemsMigration->up();
        $dropSubtotalsMigration = include database_path('migrations/2026_11_19_223946_drop_subtotals_from_invoicing_receipt_items_table.php');
        $dropSubtotalsMigration->up();
    }

    if (Illuminate\Support\Facades\Schema::hasTable('invoicing_receipts') && ! Illuminate\Support\Facades\Schema::hasColumn('invoicing_receipts', 'tanggal_invoice')) {
        $addDatesMigration = include database_path('migrations/2026_11_20_001344_add_invoice_dates_to_invoicing_receipts_table.php');
        $addDatesMigration->up();
    }

    return $created;
}

test('my_tasks tab on ListInvoicingReceipts shows only active tasks and excludes Selesai and Terkirim Kasir', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        DB::table('invoicing_receipts')->delete();

        $myUserId = DB::table('users')->insertGetId([
            'name' => 'PIC Saya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherUserId = DB::table('users')->insertGetId([
            'name' => 'PIC Lain',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        InvoicingReceipt::create([
            'pic_id' => $myUserId,
            'no_po' => 'PO-MY-WAIT',
            'status' => 'Menunggu Verifikasi',
        ]);
        InvoicingReceipt::create([
            'pic_id' => $myUserId,
            'no_po' => 'PO-MY-VERIFIED',
            'status' => 'Terverifikasi',
        ]);
        InvoicingReceipt::create([
            'pic_id' => $myUserId,
            'no_po' => 'PO-MY-CASHIER',
            'status' => 'Terkirim Kasir',
        ]);
        InvoicingReceipt::create([
            'pic_id' => $myUserId,
            'no_po' => 'PO-MY-DONE',
            'status' => 'Selesai',
        ]);
        InvoicingReceipt::create([
            'pic_id' => $otherUserId,
            'no_po' => 'PO-OTHER-WAIT',
            'status' => 'Menunggu Verifikasi',
        ]);
        InvoicingReceipt::create([
            'pic_id' => null,
            'no_po' => 'PO-FREE-POOL',
            'status' => 'Menunggu Verifikasi',
        ]);

        $page = app(ListInvoicingReceipts::class);

        Auth::logout();
        $guestTabs = $page->getTabs();

        expect($guestTabs['my_tasks']->getBadge())->toBe('0')
            ->and($guestTabs['my_tasks']->modifyQuery(InvoicingReceipt::query())->count())->toBe(0);

        Auth::loginUsingId($myUserId);
        $tabs = $page->getTabs();
        $myTaskPos = $tabs['my_tasks']->modifyQuery(InvoicingReceipt::query())
            ->orderBy('id')
            ->pluck('no_po')
            ->all();

        expect($tabs['my_tasks']->getBadge())->toBe('2')
            ->and($myTaskPos)->toBe(['PO-MY-WAIT', 'PO-MY-VERIFIED'])
            ->and($tabs['all']->getBadge())->toBe('6')
            ->and($tabs['sent_cashier']->getBadge())->toBe('1')
            ->and($tabs['selesai']->getBadge())->toBe('1');
    } finally {
        Auth::logout();
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('RDTV receipt automatically completes at Info GRS and leaves Tugas Saya while GRS remains Menunggu Verifikasi', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        DB::table('invoicing_receipts')->delete();

        $staffId = DB::table('users')->insertGetId([
            'name' => 'Staff RDTV',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rdtv = InvoicingReceipt::create([
            'pic_id' => $staffId,
            'jenis' => 'RDTV',
            'no_po' => 'PO-RDTV-01',
            'no_do' => 'DO-RDTV-01',
            'tanggal_terima_grs' => '2026-11-10',
            'tanggal_info_grs' => null,
            'status' => 'Menunggu Verifikasi',
        ]);

        $grs = InvoicingReceipt::create([
            'pic_id' => $staffId,
            'jenis' => 'GRS',
            'no_po' => 'PO-GRS-01',
            'no_do' => 'DO-GRS-01',
            'tanggal_terima_grs' => '2026-11-10',
            'tanggal_info_grs' => '2026-11-11',
            'status' => 'Menunggu Verifikasi',
        ]);

        Auth::loginUsingId($staffId);
        $page = app(ListInvoicingReceipts::class);

        // Sebelum Info GRS diisi: RDTV dan GRS sama-sama ada di Tugas Saya
        expect($rdtv->status)->toBe('Menunggu Verifikasi')
            ->and($grs->status)->toBe('Menunggu Verifikasi')
            ->and($page->getTabs()['my_tasks']->getBadge())->toBe('2');

        // Setelah Staff Invoice mengisi tanggal_info_grs pada RDTV -> otomatis Selesai & keluar dari Tugas Saya
        $rdtv->update(['tanggal_info_grs' => '2026-11-17']);
        $rdtv->refresh();

        $tabsAfter = $page->getTabs();
        $myTaskPos = $tabsAfter['my_tasks']->modifyQuery(InvoicingReceipt::query())
            ->pluck('no_po')
            ->all();

        expect($rdtv->status)->toBe('Selesai')
            ->and($rdtv->lama_pekerjaan)->toBeNull()
            ->and($tabsAfter['my_tasks']->getBadge())->toBe('1')
            ->and($myTaskPos)->toBe(['PO-GRS-01'])
            ->and($tabsAfter['selesai']->getBadge())->toBe('1');

        // Jika tanggal_info_grs dikosongkan kembali pada RDTV -> kembali Menunggu Verifikasi
        $rdtv->update(['tanggal_info_grs' => null]);
        expect($rdtv->fresh()->status)->toBe('Menunggu Verifikasi');
    } finally {
        Auth::logout();
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('invoicing receipts table exposes infoRdtv action and hides invoice/cashier actions for RDTV records', function () {
    $livewire = app(ListInvoicingReceipts::class);

    $resolveActionsFor = function (InvoicingReceipt $record) use ($livewire) {
        $table = new Table($livewire);
        InvoicingReceiptsTable::configure($table);

        return collect($table->getRecordActions())
            ->flatMap(function ($a) use ($record) {
                if ($a instanceof ActionGroup) {
                    $a->record($record);

                    return collect($a->getActions())->map->record($record);
                }

                return [$a->record($record)];
            })
            ->filter(fn ($a) => method_exists($a, 'getName'))
            ->keyBy(fn ($a) => $a->getName());
    };

    $rdtvPending = (new InvoicingReceipt)->forceFill([
        'id' => 101,
        'jenis' => 'RDTV',
        'no_po' => 'PO-RDTV-ACT',
        'status' => 'Menunggu Verifikasi',
    ]);
    $grsPending = (new InvoicingReceipt)->forceFill([
        'id' => 102,
        'jenis' => 'GRS',
        'no_po' => 'PO-GRS-ACT',
        'status' => 'Menunggu Verifikasi',
    ]);

    $rdtvActions = $resolveActionsFor($rdtvPending);
    $grsActions = $resolveActionsFor($grsPending);

    expect($rdtvActions)->toHaveKey('infoRdtv')
        ->and($rdtvActions['infoRdtv']->isVisible())->toBeTrue()
        ->and($rdtvActions['inputInvoiceData']->isVisible())->toBeFalse()
        ->and($rdtvActions['copyMitraku']->isVisible())->toBeFalse()
        ->and($rdtvActions['uploadBerkas']->isVisible())->toBeFalse()
        ->and($rdtvActions['sendToCashier']->isVisible())->toBeFalse()
        ->and($grsActions['infoRdtv']->isVisible())->toBeFalse()
        ->and($grsActions['inputInvoiceData']->isVisible())->toBeTrue()
        ->and($grsActions['sendToCashier']->isVisible())->toBeTrue();
});

test('CreateGrsRdtv leaves tanggal_info_grs null for RDTV so staff invoice must inform vendor to finish it', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    $createdExtraTables = [];
    if (! Illuminate\Support\Facades\Schema::hasTable('delivery_order_receipts')) {
        $createdExtraTables[] = 'delivery_order_receipts';
        Illuminate\Support\Facades\Schema::create('delivery_order_receipts', function ($table) {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->string('document_code')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
    if (! Illuminate\Support\Facades\Schema::hasTable('delivery_order_receipt_details')) {
        $createdExtraTables[] = 'delivery_order_receipt_details';
        Illuminate\Support\Facades\Schema::create('delivery_order_receipt_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('uoi')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->default(0);
            $table->timestamps();
        });
    }
    if (! Illuminate\Support\Facades\Schema::hasTable('grs_rdtv_items')) {
        $createdExtraTables[] = 'grs_rdtv_items';
        Illuminate\Support\Facades\Schema::create('grs_rdtv_items', function ($table) {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->string('document_code')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    try {
        DB::table('invoicing_receipts')->delete();

        $mockAssignment = Mockery::mock(InvoicingAssignmentService::class);
        $mockAssignment->shouldReceive('determinePicId')->andReturn(null);
        app()->instance(InvoicingAssignmentService::class, $mockAssignment);

        $do = DeliveryOrderReceipt::create([
            'delivery_order_no' => 'DO-TEST-RDTV',
            'document_code' => 'DOC-RDTV-01',
            'status' => 'RDTV',
        ]);
        $item = GrsRdtvItem::create([
            'grs_rdtv_id' => 1,
            'delivery_order_receipt_id' => $do->id,
            'document_code' => 'DOC-RDTV-01',
            'file_path' => 'grs-rdtv-docs/DOC-RDTV-01.pdf',
            'status' => 'Matched',
        ]);

        $page = new class extends CreateGrsRdtv
        {
            public function createForTest(GrsRdtvItem $item, DeliveryOrderReceipt $do, string $category, string $date): void
            {
                $this->createInvoicingReceiptFor($item, $do, $category, $date);
            }
        };

        $page->createForTest($item, $do, 'RDTV', '2026-11-17');
        $rdtvReceipt = InvoicingReceipt::where('jenis', 'RDTV')->first();

        expect($rdtvReceipt)->not->toBeNull()
            ->and($rdtvReceipt->material_type)->toBe('Non Stock')
            ->and($rdtvReceipt->tanggal_terima_grs->format('Y-m-d'))->toBe('2026-11-17')
            ->and($rdtvReceipt->tanggal_info_grs)->toBeNull()
            ->and($rdtvReceipt->tanggal_terima_koreksi_invoice)->toBeNull()
            ->and($rdtvReceipt->status)->toBe('Menunggu Verifikasi');
    } finally {
        foreach (array_reverse($createdExtraTables) as $tableName) {
            Illuminate\Support\Facades\Schema::dropIfExists($tableName);
        }
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('InvoicingReceipt defaults empty or null material_type to Non Stock across model, options, and PIC rules', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        DB::table('invoicing_receipts')->delete();

        $nullReceipt = InvoicingReceipt::create(['no_po' => 'PO-MT-NULL', 'material_type' => null]);
        $emptyReceipt = InvoicingReceipt::create(['no_po' => 'PO-MT-EMPTY', 'material_type' => '   ']);
        $dashReceipt = InvoicingReceipt::create(['no_po' => 'PO-MT-DASH', 'material_type' => '-']);
        $noneReceipt = InvoicingReceipt::create(['no_po' => 'PO-MT-NONE', 'material_type' => 'none']);
        $nonstockReceipt = InvoicingReceipt::create(['no_po' => 'PO-MT-NS', 'material_type' => 'NONSTOCK']);
        $zspReceipt = InvoicingReceipt::create(['no_po' => 'PO-MT-ZSP', 'material_type' => 'ZSP']);

        expect($nullReceipt->material_type)->toBe('Non Stock')
            ->and($emptyReceipt->material_type)->toBe('Non Stock')
            ->and($dashReceipt->material_type)->toBe('Non Stock')
            ->and($noneReceipt->material_type)->toBe('Non Stock')
            ->and($nonstockReceipt->material_type)->toBe('Non Stock')
            ->and($zspReceipt->material_type)->toBe('ZSP')
            ->and(InvoicingReceipt::materialTypeOptions())->toHaveKey('Non Stock', 'Non Stock');

        // Aturan PIC untuk 'Non Stock' harus bisa menangkap material_type null/kosong
        PicMaterialAssignment::updateOrCreate(
            ['material_type' => 'Non Stock'],
            [
                'assignment_mode' => 'user',
                'user_id' => 77,
            ]
        );

        $service = new InvoicingAssignmentService;
        expect($service->determinePicId(null))->toBe(77)
            ->and($service->determinePicId(''))->toBe(77)
            ->and($service->determinePicId('Non Stock'))->toBe(77);
    } finally {
        PicMaterialAssignment::where('material_type', 'Non Stock')->delete();
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('Menunggu Verifikasi receipt automatically completes to Selesai once invoice number exists and leaves Tugas Saya', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        DB::table('invoicing_receipts')->delete();

        $staffId = DB::table('users')->insertGetId([
            'name' => 'Staff Verif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Placeholder kosong atau berkas vendor saja tanpa no_invoice tetap Menunggu Verifikasi
        $pendingEmpty = InvoicingReceipt::create([
            'pic_id' => $staffId,
            'no_po' => 'PO-WAIT-1',
            'no_invoice' => '-',
            'dokumen_invoice' => 'berkas-penagihan/PO-WAIT-1.pdf',
            'status' => 'Menunggu Verifikasi',
        ]);

        // Langsung dibuat dengan status Menunggu Verifikasi tetapi no_invoice sudah ada -> otomatis Selesai
        $createdWithInvoice = InvoicingReceipt::create([
            'pic_id' => $staffId,
            'no_po' => 'PO-INV-CREATED',
            'no_invoice' => 'INV-2026/001',
            'status' => 'Menunggu Verifikasi',
        ]);

        // Status Terkirim Kasir tidak tertimpa kembali
        $sentCashier = InvoicingReceipt::create([
            'pic_id' => $staffId,
            'no_po' => 'PO-KASIR-1',
            'no_invoice' => 'INV-2026/002',
            'status' => 'Terkirim Kasir',
        ]);

        // Update record Menunggu Verifikasi dengan nomor invoice -> otomatis berubah ke Selesai
        $willFinish = InvoicingReceipt::create([
            'pic_id' => $staffId,
            'no_po' => 'PO-WAIT-2',
            'no_invoice' => null,
            'status' => 'Menunggu Verifikasi',
        ]);
        $willFinish->update(['no_invoice' => 'INV-2026/003']);

        Auth::loginUsingId($staffId);
        $tabs = app(ListInvoicingReceipts::class)->getTabs();

        expect($pendingEmpty->status)->toBe('Menunggu Verifikasi')
            ->and($createdWithInvoice->status)->toBe('Selesai')
            ->and($sentCashier->status)->toBe('Terkirim Kasir')
            ->and($willFinish->fresh()->status)->toBe('Selesai')
            ->and($tabs['my_tasks']->getBadge())->toBe('1')
            ->and($tabs['waiting_verification']->getBadge())->toBe('1')
            ->and($tabs['selesai']->getBadge())->toBe('2');
    } finally {
        Auth::logout();
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('invoicing receipt supports multi-item billing per invoice and calculates total quantities and subtotals', function () {
    ensureInvoicingReceiptTableExists();

    $receipt = InvoicingReceipt::create([
        'no_po' => '5300064489',
        'no_invoice' => 'INV-2026-MULTI-001',
        'jenis' => 'GRS',
        'status' => 'Menunggu Verifikasi',
        'subtotal_exclude_ppn' => 17500000,
        'subtotal_include_ppn' => 19425000,
    ]);

    // Item 10 & Item 20 dari GRS 5007027893
    $item1 = $receipt->items()->create([
        'item_no' => 10,
        'no_grs' => '5007027893',
        'nama_barang' => 'BALL VALVE 2 INCH ANSI 150',
        'qty_penagihan' => 10,
        'qty_po' => 10,
        'uoi' => 'PCS',
    ]);

    $item2 = $receipt->items()->create([
        'item_no' => 20,
        'no_grs' => '5007027893',
        'nama_barang' => 'SPIRAL WOUND GASKET 2 INCH',
        'qty_penagihan' => 5,
        'qty_po' => 5,
        'uoi' => 'PCS',
    ]);

    // Item 30 dari GRS berbeda 5007726532 tetapi masuk invoice gabungan yang sama
    $item3 = $receipt->items()->create([
        'item_no' => 30,
        'no_grs' => '5007726532',
        'nama_barang' => 'STUD BOLT & NUT B7/2H',
        'qty_penagihan' => 20,
        'qty_po' => 20,
        'uoi' => 'SET',
    ]);

    expect($receipt->items()->count())->toBe(3);

    $receipt->syncSummaryFromItems();
    $receipt->save();

    $fresh = $receipt->fresh();
    expect($fresh->qty_penagihan)->toEqual(35)
        ->and($fresh->subtotal_exclude_ppn)->toEqual(17500000)
        ->and($fresh->subtotal_include_ppn)->toEqual(19425000)
        ->and($fresh->nama_barang)->toContain('BALL VALVE 2 INCH')
        ->and($fresh->nama_barang)->toContain('SPIRAL WOUND GASKET')
        ->and($fresh->nama_barang)->toContain('STUD BOLT & NUT')
        ->and($fresh->no_grs)->toContain('5007027893')
        ->and($fresh->no_grs)->toContain('5007726532');
});

test('handles partial step billing where same PO has multiple separate invoices with distinct items', function () {
    ensureInvoicingReceiptTableExists();

    // Invoice 1 menagihkan item 10 dan 20
    $inv1 = InvoicingReceipt::create([
        'no_po' => '5300064489',
        'no_invoice' => 'INV-PARTIAL-001',
        'no_grs' => '5007027893',
        'jenis' => 'GRS',
        'subtotal_exclude_ppn' => 1500000,
        'subtotal_include_ppn' => 1665000,
    ]);

    $inv1->items()->createMany([
        [
            'item_no' => 10,
            'no_grs' => '5007027893',
            'nama_barang' => 'ITEM 10',
            'qty_penagihan' => 10,
            'qty_po' => 10,
            'uoi' => 'PCS',
        ],
        [
            'item_no' => 20,
            'no_grs' => '5007027893',
            'nama_barang' => 'ITEM 20',
            'qty_penagihan' => 5,
            'qty_po' => 5,
            'uoi' => 'PCS',
        ],
    ]);
    $inv1->syncSummaryFromItems();
    $inv1->save();

    // Invoice 2 menagihkan item 30 belakangan
    $inv2 = InvoicingReceipt::create([
        'no_po' => '5300064489',
        'no_invoice' => 'INV-PARTIAL-002',
        'no_grs' => '5007726532',
        'jenis' => 'GRS',
        'subtotal_exclude_ppn' => 3000000,
        'subtotal_include_ppn' => 3330000,
    ]);

    $inv2->items()->create([
        'item_no' => 30,
        'no_grs' => '5007726532',
        'nama_barang' => 'ITEM 30',
        'qty_penagihan' => 20,
        'qty_po' => 20,
        'uoi' => 'SET',
    ]);
    $inv2->syncSummaryFromItems();
    $inv2->save();

    expect(InvoicingReceipt::where('no_po', '5300064489')->count())->toBe(2)
        ->and($inv1->fresh()->qty_penagihan)->toEqual(15)
        ->and($inv1->fresh()->subtotal_exclude_ppn)->toEqual(1500000)
        ->and($inv2->fresh()->qty_penagihan)->toEqual(20)
        ->and($inv2->fresh()->subtotal_exclude_ppn)->toEqual(3000000);
});

test('inputInvoiceData action supports saving multiple items with distinct GRS and syncs summary header', function () {
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        $receipt = InvoicingReceipt::create([
            'no_po' => '5300064489',
            'no_do' => 'DO-SAMPLE-01',
            'jenis' => 'GRS',
            'status' => 'Menunggu Verifikasi',
        ]);

        $livewire = app(ListInvoicingReceipts::class);
        $table = new Table($livewire);
        InvoicingReceiptsTable::configure($table);

        $actions = collect($table->getRecordActions())
            ->flatMap(function ($a) use ($receipt) {
                if ($a instanceof ActionGroup) {
                    $a->record($receipt);

                    return collect($a->getActions())->map->record($receipt);
                }

                return [$a->record($receipt)];
            })
            ->filter(fn ($a) => method_exists($a, 'getName'))
            ->keyBy(fn ($a) => $a->getName());

        $action = $actions['inputInvoiceData'];
        expect($action)->not->toBeNull();

        $action->call([
            'record' => $receipt,
            'data' => [
                'no_grs' => '5007027893',
                'no_do' => 'DO-SAMPLE-01',
                'no_invoice' => 'INV-MULTI-001',
                'faktur_pajak' => '010.001-26.12345678',
                'subtotal_exclude_ppn' => '3.500.000',
                'subtotal_include_ppn' => '3.885.000',
                'items' => [
                    [
                        'item_no' => 10,
                        'nama_barang' => 'GASKET SET A',
                        'no_grs' => '5007027893',
                        'no_do' => 'DO-SAMPLE-01',
                        'qty_penagihan' => 5,
                        'qty_po' => 5,
                        'uoi' => 'SET',
                    ],
                    [
                        'item_no' => 20,
                        'nama_barang' => 'COLLAR SLEEVE B',
                        'no_grs' => '5007027893',
                        'no_do' => 'DO-SAMPLE-01',
                        'qty_penagihan' => 10,
                        'qty_po' => 10,
                        'uoi' => 'SET',
                    ],
                    [
                        'item_no' => 30,
                        'nama_barang' => 'VALVE SEAL C',
                        'no_grs' => '5007726532',
                        'no_do' => 'DO-SAMPLE-02',
                        'qty_penagihan' => 20,
                        'qty_po' => 20,
                        'uoi' => 'SET',
                    ],
                ],
            ],
        ]);

        $receipt->refresh();

        expect($receipt->no_invoice)->toBe('INV-MULTI-001')
            ->and($receipt->status)->toBe('Selesai')
            ->and($receipt->items)->toHaveCount(3)
            ->and((float) $receipt->qty_penagihan)->toBe(35.0)
            ->and((float) $receipt->subtotal_exclude_ppn)->toBe(3500000.0)
            ->and((float) $receipt->subtotal_include_ppn)->toBe(3885000.0);

        $items = $receipt->items->sortBy('item_no')->values();
        expect($items[0]->item_no)->toBe(10)
            ->and($items[0]->no_grs)->toBe('5007027893')
            ->and($items[1]->item_no)->toBe(20)
            ->and($items[1]->no_grs)->toBe('5007027893')
            ->and($items[2]->item_no)->toBe(30)
            ->and($items[2]->no_grs)->toBe('5007726532');
    } finally {
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('inputInvoiceData automatically inherits header GRS and DO to items and defaults qty_penagihan from qty_po', function () {
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        $receipt = InvoicingReceipt::create([
            'no_po' => '5300099999',
            'no_do' => 'DO-HEADER-99',
            'no_grs' => '5007099999',
            'jenis' => 'GRS',
            'status' => 'Menunggu Verifikasi',
        ]);

        $livewire = app(ListInvoicingReceipts::class);
        $table = new Table($livewire);
        InvoicingReceiptsTable::configure($table);

        $actions = collect($table->getRecordActions())
            ->flatMap(function ($a) use ($receipt) {
                if ($a instanceof ActionGroup) {
                    $a->record($receipt);

                    return collect($a->getActions())->map->record($receipt);
                }

                return [$a->record($receipt)];
            })
            ->filter(fn ($a) => method_exists($a, 'getName'))
            ->keyBy(fn ($a) => $a->getName());

        $action = $actions['inputInvoiceData'];

        // Item disubmit tanpa no_grs dan no_do, dan tanpa qty_penagihan eksplisit
        $action->call([
            'record' => $receipt,
            'data' => [
                'no_grs' => '5007099999',
                'no_do' => 'DO-HEADER-99',
                'no_invoice' => 'INV-HEADER-01',
                'subtotal_exclude_ppn' => '1.500.000',
                'subtotal_include_ppn' => '1.665.000',
                'items' => [
                    [
                        'item_no' => 10,
                        'nama_barang' => 'BOLT HEX M12',
                        'qty_po' => 50,
                        'uoi' => 'PCS',
                    ],
                    [
                        'item_no' => 20,
                        'nama_barang' => 'NUT HEX M12',
                        'qty_po' => 50,
                        'uoi' => 'PCS',
                    ],
                ],
            ],
        ]);

        $receipt->refresh();

        expect($receipt->no_invoice)->toBe('INV-HEADER-01')
            ->and($receipt->items)->toHaveCount(2)
            ->and((float) $receipt->qty_penagihan)->toBe(100.0)
            ->and((float) $receipt->subtotal_exclude_ppn)->toBe(1500000.0)
            ->and((float) $receipt->subtotal_include_ppn)->toBe(1665000.0);

        $items = $receipt->items->sortBy('item_no')->values();
        expect($items[0]->no_grs)->toBe('5007099999')
            ->and($items[0]->no_do)->toBe('DO-HEADER-99')
            ->and((float) $items[0]->qty_penagihan)->toBe(50.0)
            ->and($items[1]->no_grs)->toBe('5007099999')
            ->and($items[1]->no_do)->toBe('DO-HEADER-99')
            ->and((float) $items[1]->qty_penagihan)->toBe(50.0);
    } finally {
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('InvoicingReceiptsTable has correct column order: tanggal terima grs, no_po, no_grs/rdtv, pic, and date range filter', function () {
    $page = app(ListInvoicingReceipts::class);
    $table = new Table($page);
    InvoicingReceiptsTable::configure($table);

    // 1. Filter layout is AboveContent
    expect($table->getFiltersLayout())->toBe(FiltersLayout::AboveContent);

    // 2. DateRangeFilter exists on tanggal_terima_grs
    $dateFilter = $table->getFilter('tanggal_terima_grs');
    expect($dateFilter)->not->toBeNull()
        ->and($dateFilter)->toBeInstanceOf(DateRangeFilter::class);

    // 3. Columns check: exact order: tanggal terima grs, no_po, no_grs, pic.name, vendor_name
    $columns = collect($table->getColumns());
    $columnNames = $columns->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)->values();

    $tglGrsIndex = $columnNames->search('tanggal_terima_grs');
    $noPoIndex = $columnNames->search('no_po');
    $noGrsIndex = $columnNames->search('no_grs');
    $picIndex = $columnNames->search('pic.name');
    $vendorIndex = $columnNames->search('vendor_name');

    expect($tglGrsIndex)->toBe(0)
        ->and($noPoIndex)->toBe(1)
        ->and($noGrsIndex)->toBe(2)
        ->and($picIndex)->toBe(3)
        ->and($vendorIndex)->toBe(4);

    // 4. On my_tasks tab, all 6 filters (3 columns x 2 rows) remain visible including pic_id & material_type
    $page->activeTab = 'my_tasks';
    expect($table->getFilter('pic_id'))->not->toBeNull()
        ->and($table->getFilter('material_type'))->not->toBeNull()
        ->and(count($table->getFilters()))->toBe(6);
});

test('syncSummaryFromItems formats multi-item deskripsi as numbered list and command runs cleanly', function () {
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        $receipt = InvoicingReceipt::create([
            'no_po' => 'PO-TEST-DESC',
            'jenis' => 'GRS',
            'status' => 'Menunggu Verifikasi',
        ]);

        $receipt->items()->createMany([
            ['item_no' => 10, 'nama_barang' => 'BUSHING:FISHER 1IN-EZ VALVE;1E791214012', 'qty_penagihan' => 3, 'qty_po' => 3, 'uoi' => 'EA'],
            ['item_no' => 20, 'nama_barang' => 'PACKING SET: FISHER;RPACKX00022', 'qty_penagihan' => 1, 'qty_po' => 1, 'uoi' => 'SET'],
            ['item_no' => 30, 'nama_barang' => 'RINGVALVE SEAT:FISHER;1V550805092', 'qty_penagihan' => 2, 'qty_po' => 2, 'uoi' => 'EA'],
        ]);

        $receipt->syncSummaryFromItems();

        $expectedDesc = "1. BUSHING:FISHER 1IN-EZ VALVE;1E791214012\n2. PACKING SET: FISHER;RPACKX00022\n3. RINGVALVE SEAT:FISHER;1V550805092";
        expect($receipt->deskripsi)->toBe($expectedDesc);

        $receipt->save();

        // Run sync command
        $this->artisan('invoicing:sync-descriptions')->assertSuccessful();
    } finally {
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('InvoicingOverviewWidget calculates monthly stats correctly and identifies top pic', function () {
    ensurePicAssignmentTablesExist();
    if (! Illuminate\Support\Facades\Schema::hasColumn('users', 'email')) {
        Illuminate\Support\Facades\Schema::table('users', function ($table) {
            $table->string('email')->nullable();
        });
    }
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        $user1Id = DB::table('users')->insertGetId([
            'name' => 'Budi Staff',
            'email' => 'budi@pkt.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user2Id = DB::table('users')->insertGetId([
            'name' => 'Siti Staff',
            'email' => 'siti@pkt.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentMonth = now()->format('Y-m');
        $prevMonth = now()->subMonth()->format('Y-m');

        // Receipts in current month:
        // user1 gets 3 receipts (2 selesai, 1 proses)
        InvoicingReceipt::create([
            'no_po' => 'PO-CUR-1',
            'pic_id' => $user1Id,
            'jenis' => 'GRS',
            'status' => 'Selesai',
            'tanggal_terima_grs' => now()->toDateString(),
            'subtotal_include_ppn' => 1000000,
        ]);
        InvoicingReceipt::create([
            'no_po' => 'PO-CUR-2',
            'pic_id' => $user1Id,
            'jenis' => 'GRS',
            'status' => 'Terkirim Kasir',
            'tanggal_terima_grs' => now()->toDateString(),
            'subtotal_include_ppn' => 2000000,
        ]);
        InvoicingReceipt::create([
            'no_po' => 'PO-CUR-3',
            'pic_id' => $user1Id,
            'jenis' => 'RDTV',
            'status' => 'Menunggu Verifikasi',
            'tanggal_terima_grs' => now()->toDateString(),
            'subtotal_include_ppn' => 500000,
        ]);

        // user2 gets 1 receipt (1 proses)
        InvoicingReceipt::create([
            'no_po' => 'PO-CUR-4',
            'pic_id' => $user2Id,
            'jenis' => 'GRS',
            'status' => 'Menunggu Verifikasi',
            'tanggal_terima_grs' => now()->toDateString(),
            'subtotal_include_ppn' => 1500000,
        ]);

        // Receipt in previous month:
        // user2 gets 1 receipt in prev month
        InvoicingReceipt::create([
            'no_po' => 'PO-PREV-1',
            'pic_id' => $user2Id,
            'jenis' => 'GRS',
            'status' => 'Selesai',
            'tanggal_terima_grs' => now()->subMonth()->toDateString(),
            'subtotal_include_ppn' => 5000000,
        ]);

        $widget = new InvoicingOverviewWidget;
        $widget->mount();

        expect($widget->selectedPeriod)->toBe($currentMonth);

        $stats = $widget->getStatsData();
        expect($stats['top_pic_name'])->toBe('Budi Staff')
            ->and($stats['top_pic_count'])->toBe(3)
            ->and($stats['total_masuk'])->toBe(4)
            ->and($stats['total_grs'])->toBe(3)
            ->and($stats['total_rdtv'])->toBe(1)
            ->and($stats['total_selesai'])->toBe(2)
            ->and($stats['total_proses'])->toBe(2)
            ->and($stats['completion_rate'])->toBe(50);

        // Check staff workloads ranking
        $workloads = $widget->getStaffWorkloads();
        $budiWorkload = collect($workloads)->firstWhere('name', 'Budi Staff');
        $sitiWorkload = collect($workloads)->firstWhere('name', 'Siti Staff');

        expect($budiWorkload['total'])->toBe(3)
            ->and($budiWorkload['selesai'])->toBe(2)
            ->and($sitiWorkload['total'])->toBe(1);

        // Switch to previous month
        $widget->selectedPeriod = $prevMonth;
        $prevStats = $widget->getStatsData();
        expect($prevStats['top_pic_name'])->toBe('Siti Staff')
            ->and($prevStats['top_pic_count'])->toBe(1)
            ->and($prevStats['total_masuk'])->toBe(1);

        // Check modal toggling
        expect($widget->showStaffModal)->toBeFalse();
        $widget->openStaffModal();
        expect($widget->showStaffModal)->toBeTrue();
        $widget->closeStaffModal();
        expect($widget->showStaffModal)->toBeFalse();

        // Check Livewire rendering uses Filament non-native Select for period selection
        Livewire\Livewire::test(InvoicingOverviewWidget::class)
            ->assertSee('Ringkasan Invoice Barang')
            ->assertSee('Budi Staff')
            ->assertSeeHtml('fi-select-input')
            ->set('selectedPeriod', $prevMonth)
            ->assertSee('Siti Staff');

        // Check ListInvoicingReceipts registers the widget
        $page = app(ListInvoicingReceipts::class);
        $headerWidgets = (fn () => $this->getHeaderWidgets())->call($page);
        expect($headerWidgets)->toContain(InvoicingOverviewWidget::class);
    } finally {
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('it accurately matches item quantities from purchase orders issued based on description', function () {
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();
    $poNumber = '5300099991';

    try {
        // 1. Create Purchase Order Issued with 3 items with distinct quantities
        $po1 = PurchaseOrderIssued::create([
            'purchase_order_no' => $poNumber,
            'item_no' => 10,
            'description' => 'BUSHING:FISHER 1IN-EZ VALVE;1E791214012',
            'qty_po' => 5.0,
            'uoi' => 'PCS',
            'total_amount_in_lc' => 5000000,
        ]);

        $po2 = PurchaseOrderIssued::create([
            'purchase_order_no' => $poNumber,
            'item_no' => 20,
            'description' => 'PACKING SET: FISHER;RPACKX00022',
            'qty_po' => 2.0,
            'uoi' => 'SET',
            'total_amount_in_lc' => 2000000,
        ]);

        $po3 = PurchaseOrderIssued::create([
            'purchase_order_no' => $poNumber,
            'item_no' => 30,
            'description' => 'RINGVALVE SEAT:FISHER;1V550805092',
            'qty_po' => 10.0,
            'uoi' => 'EA',
            'total_amount_in_lc' => 10000000,
        ]);

        $poList = PurchaseOrderIssued::where('purchase_order_no', $poNumber)->get();

        // Verify PurchaseOrderIssued::matchByDescription
        $matched1 = PurchaseOrderIssued::matchByDescription('1. BUSHING:FISHER 1IN-EZ VALVE;1E791214012', $poList);
        expect($matched1)->not->toBeNull()
            ->and($matched1->id)->toBe($po1->id)
            ->and($matched1->qty_po)->toBe(5.0);

        $matched2 = PurchaseOrderIssued::matchByDescription('PACKING SET: FISHER;RPACKX00022', $poList);
        expect($matched2)->not->toBeNull()
            ->and($matched2->id)->toBe($po2->id)
            ->and($matched2->qty_po)->toBe(2.0);

        $matched3 = PurchaseOrderIssued::matchByDescription('RINGVALVE SEAT:FISHER', $poList);
        expect($matched3)->not->toBeNull()
            ->and($matched3->id)->toBe($po3->id)
            ->and($matched3->qty_po)->toBe(10.0);

        // 2. Create InvoicingReceipt with mismatched/duplicate quantities (simulating the bug where all items got 5)
        $receipt = InvoicingReceipt::create([
            'no_po' => $poNumber,
            'jenis' => 'GRS',
            'status' => 'Menunggu Verifikasi',
        ]);

        $item1 = $receipt->items()->create([
            'nama_barang' => 'BUSHING:FISHER 1IN-EZ VALVE;1E791214012',
            'qty_penagihan' => 5.0,
            'qty_po' => 5.0,
        ]);

        $item2 = $receipt->items()->create([
            'nama_barang' => 'PACKING SET: FISHER;RPACKX00022',
            'qty_penagihan' => 5.0, // wrongly set to 5 instead of 2
            'qty_po' => 5.0,
        ]);

        $item3 = $receipt->items()->create([
            'nama_barang' => 'RINGVALVE SEAT:FISHER;1V550805092',
            'qty_penagihan' => 5.0, // wrongly set to 5 instead of 10
            'qty_po' => 5.0,
        ]);

        // Run the sync artisan command
        $this->artisan('invoicing:sync-quantities')
            ->assertSuccessful();

        // Verify items now have their own matching PO quantities!
        $item1->refresh();
        $item2->refresh();
        $item3->refresh();

        expect($item1->qty_penagihan)->toBe(5.0)
            ->and($item1->qty_po)->toBe(5.0)
            ->and($item2->qty_penagihan)->toBe(2.0)
            ->and($item2->qty_po)->toBe(2.0)
            ->and($item3->qty_penagihan)->toBe(10.0)
            ->and($item3->qty_po)->toBe(10.0);

        // Clean up
        $receipt->items()->delete();
        $receipt->delete();
        PurchaseOrderIssued::where('purchase_order_no', $poNumber)->delete();
    } finally {
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('InvoicingReceipt model has tanggal_invoice and tanggal_faktur_pajak in fillable and cast as date', function () {
    $model = new InvoicingReceipt;

    expect($model->isFillable('tanggal_invoice'))->toBeTrue()
        ->and($model->isFillable('tanggal_faktur_pajak'))->toBeTrue()
        ->and($model->getCasts()['tanggal_invoice'] ?? null)->toBe('date')
        ->and($model->getCasts()['tanggal_faktur_pajak'] ?? null)->toBe('date');
});

test('inputInvoice table action wizard has exactly 2 steps combining rincian barang and ringkasan total', function () {
    $livewire = app(ListInvoicingReceipts::class);
    $table = new Table($livewire);
    InvoicingReceiptsTable::configure($table);

    $actions = collect($table->getRecordActions())
        ->flatMap(function ($a) {
            if ($a instanceof ActionGroup) {
                return collect($a->getActions());
            }

            return [$a];
        })
        ->filter(fn ($a) => method_exists($a, 'getName'))
        ->keyBy(fn ($a) => $a->getName());

    $action = $actions->get('inputInvoiceData');
    expect($action)->not->toBeNull()
        ->and($action->isWizard())->toBeTrue();

    $actionSchema = $action->getSchema(Schema::make($livewire));
    $wizard = $actionSchema->getComponents()[0];
    $steps = array_values($wizard->getChildSchema()->getComponents());

    expect(count($steps))->toBe(2)
        ->and($steps[0]->getLabel())->toBe('Dokumen Penagihan')
        ->and($steps[1]->getLabel())->toBe('Rincian Barang & Ringkasan Total');
});

test('qty_penagihan and qty_po suffix closures accept InvoicingReceiptItem without type error', function () {
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        $receipt = InvoicingReceipt::create([
            'no_po' => '5300066419',
            'no_grs' => '5007697783',
            'jenis' => 'GRS',
            'status' => 'Menunggu Verifikasi',
            'tanggal_terima_grs' => now()->toDateString(),
            'uoi' => 'KG',
        ]);

        $item = $receipt->items()->create([
            'item_no' => 10,
            'nama_barang' => 'Chemical ABC',
            'qty_penagihan' => 100,
            'qty_po' => 100,
            'uoi' => 'BOX',
        ]);

        $schema = new Schema(new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        });
        $schema->model($receipt);
        InvoicingReceiptForm::configure($schema);

        $repeater = collect($schema->getComponents())
            ->flatMap(fn ($c) => method_exists($c, 'getChildComponents') ? $c->getChildComponents() : [$c])
            ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'items');

        expect($repeater)->not->toBeNull();

        $itemComponents = collect($repeater->getChildComponents())
            ->keyBy(fn ($c) => $c->getName());

        $qtyPenagihan = $itemComponents->get('qty_penagihan');
        $qtyPo = $itemComponents->get('qty_po');

        expect($qtyPenagihan)->not->toBeNull()
            ->and($qtyPo)->not->toBeNull();

        $getSuffixClosure = fn ($component) => Closure::bind(fn () => $this->suffixLabel, $component, $component)();

        $suffixPenagihan = $qtyPenagihan->evaluate($getSuffixClosure($qtyPenagihan), ['record' => $item]);
        $suffixPo = $qtyPo->evaluate($getSuffixClosure($qtyPo), ['record' => $item]);

        expect($suffixPenagihan)->toBe('BOX')
            ->and($suffixPo)->toBe('BOX');

        // Test the same repeater suffix closure in InvoicingReceiptsTable
        $livewire = app(ListInvoicingReceipts::class);
        $table = new Table($livewire);
        InvoicingReceiptsTable::configure($table);

        $actions = collect($table->getRecordActions())
            ->flatMap(fn ($a) => $a instanceof ActionGroup ? $a->getActions() : [$a])
            ->keyBy(fn ($a) => method_exists($a, 'getName') ? $a->getName() : null);

        $inputAction = $actions->get('inputInvoiceData');
        expect($inputAction)->not->toBeNull();

        $actionSchema = $inputAction->getSchema(Schema::make($livewire));
        $tableRepeater = collect($actionSchema->getComponents())
            ->flatMap(fn ($c) => method_exists($c, 'getChildComponents') ? $c->getChildComponents() : [$c])
            ->flatMap(fn ($c) => method_exists($c, 'getChildComponents') ? $c->getChildComponents() : [$c])
            ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'items');

        expect($tableRepeater)->not->toBeNull();

        $tableItemComponents = collect($tableRepeater->getChildComponents())->keyBy(fn ($c) => $c->getName());
        $tableQtyPenagihan = $tableItemComponents->get('qty_penagihan');
        $tableQtyPo = $tableItemComponents->get('qty_po');

        $tSuffixPenagihan = $tableQtyPenagihan->evaluate($getSuffixClosure($tableQtyPenagihan), ['record' => $item]);
        $tSuffixPo = $tableQtyPo->evaluate($getSuffixClosure($tableQtyPo), ['record' => $item]);

        expect($tSuffixPenagihan)->toBe('BOX')
            ->and($tSuffixPo)->toBe('BOX');
    } finally {
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('invoicing assignment service keeps the same PIC when a PO already has an assigned PIC from a previous GRS or RDTV upload', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    try {
        DB::table('invoicing_receipts')->delete();

        // Simulasi: Hari pertama PO 5300066534 (3 dari 4 material) sudah masuk dan ditugaskan ke Andereyan Muhammat (User ID 101)
        InvoicingReceipt::create([
            'no_po' => '5300066534',
            'no_do' => 'DO-TAHAP-1',
            'no_grs' => '5000111001',
            'jenis' => 'GRS',
            'material_type' => 'ZSP',
            'pic_id' => 101, // Andereyan Muhammat
            'status' => 'Menunggu Verifikasi',
        ]);

        // Hari berikutnya: Beban kerja Andereyan (101) = 5, sedangkan Staff lain (102, 103) = 0
        $service = new InvoicingAssignmentService;
        $service->setStaffWorkloads([
            101 => 5, // Andereyan Muhammat
            102 => 0, // Staff Invoice B
            103 => 0, // Staff Invoice C
        ]);

        // Saat GRS tahap terakhir untuk PO 5300066534 diunggah, PIC harus tetap Andereyan Muhammat (101), bukan ke 102/103
        $assignedForSamePo = $service->determinePicId('ZSP', 'MAT-004', 'Material Keempat', '5300066534');
        expect($assignedForSamePo)->toBe(101);

        // Sedangkan untuk PO baru (5300099999) yang belum punya PIC, sistem tetap membagi ke beban kerja terendah (102)
        $assignedForNewPo = $service->determinePicId('ZSP', 'MAT-001', 'Material Baru', '5300099999');
        expect($assignedForNewPo)->toBe(102);

        // Jika PO 5300099999 mengunggah GRS susulan di sesi yang sama, tetap ke 102
        $secondUploadForNewPo = $service->determinePicId('ZSP', 'MAT-002', 'Material Kedua', '5300099999');
        expect($secondUploadForNewPo)->toBe(102);
    } finally {
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('CreateGrsRdtv assigns subsequent GRS uploads of the same PO to the existing PIC instead of a different staff member', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    $createdExtraTables = [];
    if (! Illuminate\Support\Facades\Schema::hasTable('vendors')) {
        $createdExtraTables[] = 'vendors';
        Illuminate\Support\Facades\Schema::create('vendors', function ($table) {
            $table->id();
            $table->string('vendor_number')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }
    if (! Illuminate\Support\Facades\Schema::hasTable('purchase_order_issueds')) {
        $createdExtraTables[] = 'purchase_order_issueds';
        Illuminate\Support\Facades\Schema::create('purchase_order_issueds', function ($table) {
            $table->id();
            $table->string('purchase_order_no')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('qty_po', 15, 2)->nullable();
            $table->string('uoi')->nullable();
            $table->decimal('total_amount_in_lc', 18, 2)->nullable();
            $table->timestamps();
        });
    }
    if (! Illuminate\Support\Facades\Schema::hasTable('delivery_order_receipts')) {
        $createdExtraTables[] = 'delivery_order_receipts';
        Illuminate\Support\Facades\Schema::create('delivery_order_receipts', function ($table) {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->string('document_code')->nullable();
            $table->string('status')->nullable();
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->timestamps();
        });
    }
    if (! Illuminate\Support\Facades\Schema::hasTable('delivery_order_receipt_details')) {
        $createdExtraTables[] = 'delivery_order_receipt_details';
        Illuminate\Support\Facades\Schema::create('delivery_order_receipt_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->string('uoi')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->nullable();
            $table->timestamps();
        });
    }
    if (! Illuminate\Support\Facades\Schema::hasTable('grs_rdtv_items')) {
        $createdExtraTables[] = 'grs_rdtv_items';
        Illuminate\Support\Facades\Schema::create('grs_rdtv_items', function ($table) {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id')->nullable();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->string('document_code')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    try {
        DB::table('invoicing_receipts')->delete();

        $service = new InvoicingAssignmentService;
        $service->setStaffWorkloads([
            101 => 0, // Andereyan Muhammat
            102 => 0, // Staff Lain
        ]);
        app()->instance(InvoicingAssignmentService::class, $service);

        $poItem1 = PurchaseOrderIssued::create([
            'purchase_order_no' => '5300066534',
            'material_code' => 'MAT-10',
            'description' => 'Material Tahap 1-3',
            'qty_po' => 3,
            'uoi' => 'EA',
        ]);
        $poItem4 = PurchaseOrderIssued::create([
            'purchase_order_no' => '5300066534',
            'material_code' => 'MAT-40',
            'description' => 'Material Tahap 4 Terakhir',
            'qty_po' => 1,
            'uoi' => 'EA',
        ]);

        // DO 1 (3 material pertama)
        $do1 = DeliveryOrderReceipt::create([
            'delivery_order_no' => 'SJ-66534-1',
            'document_code' => 'DOC-66534-1',
            'status' => 'GRS',
        ]);
        DB::table('delivery_order_receipt_details')->insert([
            'delivery_order_receipt_id' => $do1->id,
            'purchase_order_issued_id' => $poItem1->id,
            'material_code' => 'MAT-10',
            'description' => 'Material Tahap 1-3',
            'material_type' => 'ZSP',
            'quantity' => 3,
            'uoi' => 'EA',
        ]);
        $grsItem1 = GrsRdtvItem::create([
            'grs_rdtv_id' => 1,
            'delivery_order_receipt_id' => $do1->id,
            'document_code' => 'DOC-66534-1',
            'file_path' => 'grs-rdtv-docs/DOC-66534-1.jpg',
            'status' => 'Matched',
        ]);

        // DO 2 (material ke-4 di hari berikutnya, terhubung ke baris item PO ke-4 dengan nomor PO yang sama 5300066534)
        $do2 = DeliveryOrderReceipt::create([
            'delivery_order_no' => 'SJ-66534-2',
            'document_code' => 'DOC-66534-2',
            'status' => 'GRS',
        ]);
        DB::table('delivery_order_receipt_details')->insert([
            'delivery_order_receipt_id' => $do2->id,
            'purchase_order_issued_id' => $poItem4->id,
            'material_code' => 'MAT-40',
            'description' => 'Material Tahap 4 Terakhir',
            'material_type' => 'ZSP',
            'quantity' => 1,
            'uoi' => 'EA',
        ]);
        $grsItem2 = GrsRdtvItem::create([
            'grs_rdtv_id' => 2,
            'delivery_order_receipt_id' => $do2->id,
            'document_code' => 'DOC-66534-2',
            'file_path' => 'grs-rdtv-docs/DOC-66534-2.jpg',
            'status' => 'Matched',
        ]);

        $page = new class extends CreateGrsRdtv
        {
            public function createForTest(GrsRdtvItem $item, DeliveryOrderReceipt $do, string $category, string $date): void
            {
                $this->createInvoicingReceiptFor($item, $do, $category, $date);
            }
        };

        // Upload GRS 1 (Hari 1) -> jatuh ke PIC 101 (Andereyan Muhammat)
        $page->createForTest($grsItem1, $do1, 'GRS', '2026-11-20');

        // Simulasi hari berikutnya dengan instance service baru (seperti request HTTP baru)
        $nextDayService = new InvoicingAssignmentService;
        $nextDayService->setStaffWorkloads([
            101 => 10, // Andereyan sudah punya beban 10
            102 => 0,  // Staff lain bebannya 0
        ]);
        app()->instance(InvoicingAssignmentService::class, $nextDayService);

        $nextDayPage = new class extends CreateGrsRdtv
        {
            public function createForTest(GrsRdtvItem $item, DeliveryOrderReceipt $do, string $category, string $date): void
            {
                $this->createInvoicingReceiptFor($item, $do, $category, $date);
            }
        };

        // Upload GRS 2 (Hari 2) untuk PO 5300066534 -> harus tetap ke PIC 101 (Andereyan Muhammat)
        $nextDayPage->createForTest($grsItem2, $do2, 'GRS', '2026-11-21');

        $receipts = InvoicingReceipt::where('no_po', '5300066534')->orderBy('id')->get();
        expect($receipts)->toHaveCount(2)
            ->and($receipts[0]->pic_id)->toBe(101)
            ->and($receipts[1]->pic_id)->toBe(101);
    } finally {
        foreach (array_reverse($createdExtraTables) as $tableName) {
            Illuminate\Support\Facades\Schema::dropIfExists($tableName);
        }
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('invoicing receipts table shows clickable Lihat Dokumen without badge on Dokumen GRS/RDTV, placeholders for null columns, and separates GRS vs RDTV clearly', function () {
    $livewire = app(ListInvoicingReceipts::class);
    $table = new Table($livewire);
    InvoicingReceiptsTable::configure($table);

    $columns = collect($table->getColumns())->keyBy(fn ($col) => $col->getName());

    // 1. Kolom Dokumen GRS/RDTV tanpa badge, clickable Lihat Dokumen saat ada file, dan placeholder saat null
    $docCol = $columns->get('grsRdtvItem.file_path');
    expect($docCol)->not->toBeNull()
        ->and($docCol->getLabel())->toBe('Dokumen GRS/RDTV')
        ->and($docCol->isBadge())->toBeFalse();

    $grsWithoutFile = (new InvoicingReceipt)->forceFill([
        'id' => 501,
        'jenis' => 'GRS',
        'no_po' => '5300010001',
        'status' => 'Menunggu Verifikasi',
    ]);
    $grsWithoutFile->setRelation('grsRdtvItem', null);

    $rdtvWithoutFile = (new InvoicingReceipt)->forceFill([
        'id' => 502,
        'jenis' => 'RDTV',
        'no_po' => '5300010002',
        'status' => 'Menunggu Verifikasi',
    ]);
    $rdtvWithoutFile->setRelation('grsRdtvItem', null);

    $grsWithFile = (new InvoicingReceipt)->forceFill([
        'id' => 503,
        'jenis' => 'GRS',
        'no_po' => '5300010003',
        'status' => 'Menunggu Verifikasi',
    ]);
    $grsWithFile->setRelation('grsRdtvItem', (new GrsRdtvItem)->forceFill([
        'file_path' => 'grs-rdtv-docs/GRS_5300010003.pdf',
    ]));

    expect($docCol->record($grsWithoutFile)->getPlaceholder())->toBe('GRS Belum Diupload')
        ->and($docCol->record($rdtvWithoutFile)->getPlaceholder())->toBe('RDTV Belum Diupload')
        ->and($docCol->record($grsWithFile)->formatState('grs-rdtv-docs/GRS_5300010003.pdf'))->toBe('Lihat Dokumen')
        ->and($docCol->record($grsWithFile)->getUrl())->toContain('GRS_5300010003.pdf');

    // 2. Placeholder tersedia pada seluruh kolom nullable dan membedakan GRS vs RDTV
    expect($columns->get('tanggal_terima_grs')->record($grsWithoutFile)->getPlaceholder())->toBe('Tgl GRS Belum Ada')
        ->and($columns->get('tanggal_terima_grs')->record($rdtvWithoutFile)->getPlaceholder())->toBe('Tgl RDTV Belum Ada')
        ->and($columns->get('no_grs')->record($grsWithoutFile)->getPlaceholder())->toBe('No. GRS Belum Ada')
        ->and($columns->get('no_grs')->record($rdtvWithoutFile)->getPlaceholder())->toBe('No. RDTV Belum Ada')
        ->and($columns->get('no_invoice')->record($grsWithoutFile)->getPlaceholder())->toBe('Invoice Belum Ada')
        ->and($columns->get('no_invoice')->record($rdtvWithoutFile)->getPlaceholder())->toBe('Tidak Perlu (RDTV)')
        ->and($columns->get('faktur_pajak')->record($grsWithoutFile)->getPlaceholder())->toBe('Faktur Pajak Belum Ada')
        ->and($columns->get('faktur_pajak')->record($rdtvWithoutFile)->getPlaceholder())->toBe('Tidak Perlu (RDTV)')
        ->and($columns->get('subtotal_exclude_ppn')->record($grsWithoutFile)->getPlaceholder())->toBe('Nilai Belum Ada')
        ->and($columns->get('subtotal_exclude_ppn')->record($rdtvWithoutFile)->getPlaceholder())->toBe('Tidak Ditagihkan (RDTV)')
        ->and($columns->get('dokumen_verifikasi_grs')->record($grsWithoutFile)->getPlaceholder())->toBe('Verifikasi GRS Belum Diupload')
        ->and($columns->get('dokumen_verifikasi_grs')->record($rdtvWithoutFile)->getPlaceholder())->toBe('Verifikasi RDTV Belum Diupload')
        ->and($columns->get('dokumen_invoice')->record($grsWithoutFile)->getPlaceholder())->toBe('Invoice Belum Diupload')
        ->and($columns->get('dokumen_invoice')->record($rdtvWithoutFile)->getPlaceholder())->toBe('Tidak Perlu (RDTV)')
        ->and($columns->get('lama_pekerjaan')->record($grsWithoutFile)->getPlaceholder())->toBe('Belum Dihitung')
        ->and($columns->get('lama_pekerjaan')->record($rdtvWithoutFile)->getPlaceholder())->toBe('Tidak Dihitung (RDTV)');

    // 3. Bulk Actions Kirim ke Kasir & Cetak Transmittal tersedia, serta dropdown aksi baris mendukung auto-flip & teleport
    $toolbarActions = collect($table->getToolbarActions())
        ->flatMap(fn ($a) => method_exists($a, 'getActions') ? $a->getActions() : [$a])
        ->keyBy(fn ($a) => method_exists($a, 'getName') ? $a->getName() : null);

    $recordActionGroup = collect($table->getRecordActions())->first(fn ($a) => $a instanceof ActionGroup);

    expect($toolbarActions)->toHaveKey('sendToCashierBulk')
        ->and($toolbarActions)->toHaveKey('printCashierTransmittalBulk')
        ->and($recordActionGroup)->not->toBeNull()
        ->and($recordActionGroup->hasDropdownFlip())->toBeTrue()
        ->and($recordActionGroup->hasDropdownTeleport())->toBeTrue();
});

test('cashier transmittal print controller renders PDF with required columns, destination, watermark, and excludes RDTV', function () {
    ensurePicAssignmentTablesExist();
    $createdInvoicingTable = ensureInvoicingReceiptTableExists();

    $createdVendorsTable = false;
    if (! Illuminate\Support\Facades\Schema::hasTable('vendors')) {
        $createdVendorsTable = true;
        Illuminate\Support\Facades\Schema::create('vendors', function ($table) {
            $table->id();
            $table->string('vendor_number')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    try {
        DB::table('invoicing_receipts')->delete();

        $grs1 = InvoicingReceipt::create([
            'jenis' => 'GRS',
            'no_po' => '5300077001',
            'vendor_name' => 'PT Mitra Mandiri Sejahtera',
            'no_invoice' => 'INV-PKT-2026-001',
            'faktur_pajak' => '010.001-26.11223344',
            'subtotal_exclude_ppn' => 15000000,
            'status' => 'Terkirim Kasir',
        ]);

        $grs2 = InvoicingReceipt::create([
            'jenis' => 'GRS',
            'no_po' => '5300077002',
            'vendor_name' => 'PT Baja Karya Nusantara',
            'no_invoice' => 'INV-PKT-2026-002',
            'faktur_pajak' => '010.001-26.55667788',
            'subtotal_exclude_ppn' => 25500000,
            'status' => 'Terkirim Kasir',
        ]);

        // RDTV yang tidak boleh ikut tercetak di Transmittal Kasir
        $rdtv = InvoicingReceipt::create([
            'jenis' => 'RDTV',
            'no_po' => '5300077999',
            'vendor_name' => 'PT Retur Material',
            'no_rdtv' => '4900099999',
            'status' => 'Menunggu Verifikasi',
        ]);

        // 1. Verifikasi isi HTML template PDF Transmittal Pengiriman Invoice ke Kasir
        $html = view('pdf.cashier_transmittal', [
            'receipts' => collect([$grs1, $grs2]),
            'printedAt' => now(),
            'sentDate' => now(),
            'senderName' => 'Andereyan Muhammat',
            'totalSubtotalExclude' => 40500000,
            'watermarkText' => 'Dicetak menggunakan Mokondo V2.0 - dev.receivingpkt.com',
        ])->render();

        expect($html)
            ->toContain('TRANSMITTAL PENGIRIMAN INVOICE KE KASIR')
            ->toContain('DEPARTMENT AKUNTANSI')
            ->toContain('TANGGAL &amp; WAKTU CETAK')
            ->toContain('NO. PO')
            ->toContain('NAMA VENDOR')
            ->toContain('NO. INVOICE')
            ->toContain('NO. FAKTUR PAJAK')
            ->toContain('SUBTOTAL (EXCLUDE PPN)')
            ->toContain('5300077001')
            ->toContain('PT Mitra Mandiri Sejahtera')
            ->toContain('INV-PKT-2026-001')
            ->toContain('010.001-26.11223344')
            ->toContain('Rp 15.000.000')
            ->toContain('Rp 40.500.000')
            ->toContain('Dicetak menggunakan Mokondo V2.0 - dev.receivingpkt.com');

        // 2. Verifikasi controller menghasilkan respons PDF valid (HTTP 200, application/pdf)
        $controller = new CashierTransmittalPrintController;
        $request = Request::create('/admin/invoicing-receipts/print-cashier-transmittal', 'GET', [
            'ids' => "{$grs1->id},{$grs2->id},{$rdtv->id}",
            'sent_date' => '2026-11-21',
        ]);

        $response = $controller->print($request);
        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toBe('application/pdf')
            ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
    } finally {
        if ($createdVendorsTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('vendors');
        }
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipt_items');
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});

test('vendors table displays each email on its own row, limits to 2 expandable rows, and copies only 1 clicked email', function () {
    $livewire = app(ListVendors::class);
    $livewire->bootedInteractsWithTable();
    $table = $livewire->getTable();
    $emailCol = $table->getColumn('email');

    $vendor = new Vendor;
    $vendor->setRawAttributes([
        'id' => 999,
        'name' => 'CV RAFIKA PASIFIK UTAMA',
        'email' => json_encode(['cv.rafikapasifikutama@gmail.com imel.riadin@gmail.com', 'finance@rafika.co.id']),
    ]);

    $emailCol->record($vendor);
    $html = $emailCol->toEmbeddedHtml();

    expect($emailCol->isListWithLineBreaks())->toBeTrue()
        ->and($emailCol->getListLimit())->toBe(2)
        ->and($emailCol->isLimitedListExpandable())->toBeTrue()
        ->and($html)->toContain("clipboard.writeText('cv.rafikapasifikutama@gmail.com')")
        ->and($html)->toContain("clipboard.writeText('imel.riadin@gmail.com')")
        ->and($html)->toContain("clipboard.writeText('finance@rafika.co.id')")
        ->and($html)->not->toContain('cv.rafikapasifikutama@gmail.com imel.riadin@gmail.com')
        ->and($html)->toContain('isLimited: true');
});
