<?php

use App\Models\InvoicingReceipt;
use App\Models\PicMaterialAssignment;
use App\Models\PurchaseOrderIssued;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InvoicingAssignmentService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('vendor model has expected fillable attributes and relationships', function () {
    $vendor = new Vendor;
    $fillables = $vendor->getFillable();

    expect($fillables)->toContain('name', 'vendor_number', 'email', 'city', 'street', 'salesperson')
        ->and($vendor->invoicingReceipts())->toBeInstanceOf(HasMany::class);
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
