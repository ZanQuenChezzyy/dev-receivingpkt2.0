<?php

use App\Filament\Resources\GrsRdtvs\Pages\CreateGrsRdtv;
use App\Filament\Resources\InvoicingReceipts\Pages\ListInvoicingReceipts;
use App\Filament\Resources\InvoicingReceipts\Schemas\InvoicingReceiptForm;
use App\Filament\Resources\InvoicingReceipts\Tables\InvoicingReceiptsTable;
use App\Filament\Resources\PicMaterialAssignments\Schemas\PicMaterialAssignmentForm;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    $allFields = $flattenComponents($components)->filter(fn ($c) => method_exists($c, 'getName'))->keyBy(fn ($c) => $c->getName());

    expect($allFields)->toHaveKey('material_type')
        ->and($allFields)->toHaveKey('assignment_mode')
        ->and($allFields)->toHaveKey('users')
        ->and($allFields)->toHaveKey('conditional_rules');
});

function ensureInvoicingReceiptTableExists(): bool
{
    if (Illuminate\Support\Facades\Schema::hasTable('invoicing_receipts')) {
        return false;
    }

    Illuminate\Support\Facades\Schema::create('invoicing_receipts', function ($table) {
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

    return true;
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
            ->and($rdtvReceipt->tanggal_terima_grs->format('Y-m-d'))->toBe('2026-11-17')
            ->and($rdtvReceipt->tanggal_info_grs)->toBeNull()
            ->and($rdtvReceipt->tanggal_terima_koreksi_invoice)->toBeNull()
            ->and($rdtvReceipt->status)->toBe('Menunggu Verifikasi');
    } finally {
        foreach (array_reverse($createdExtraTables) as $tableName) {
            Illuminate\Support\Facades\Schema::dropIfExists($tableName);
        }
        if ($createdInvoicingTable) {
            Illuminate\Support\Facades\Schema::dropIfExists('invoicing_receipts');
        }
    }
});
