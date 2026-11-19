<?php

use App\Models\InvoicingReceipt;
use App\Services\GrsPdfParserService;

test('grs pdf parser service extracts all required header and line data from sample pdf', function () {
    $fixturePath = base_path('tests/Fixtures/contoh_GRS.pdf');
    expect(file_exists($fixturePath))->toBeTrue();

    $service = app(GrsPdfParserService::class);
    $data = $service->parse($fixturePath);

    expect($data['no_grs'])->toBe('5007731110')
        ->and($data['tanggal_release_gr'])->toBe('2026-11-10')
        ->and($data['delivery_date'])->toBe('2026-11-23')
        ->and($data['tanggal_penerimaan'])->toBe('2026-10-09')
        ->and($data['no_po'])->toBe('5300066637')
        ->and($data['tanggal_po'])->toBe('2026-08-19')
        ->and($data['no_do'])->toBe('66637')
        ->and($data['vendor_name'])->toBe('NUANSA DHARMA CIPTA, PT.')
        ->and($data['subtotal_exclude_ppn'])->toBe(40770000.0)
        ->and($data['nama_barang'])->toBe('VIDEO CONFERENCE DEP DANBA')
        ->and($data['uoi'])->toBe('SET')
        ->and($data['qty_penagihan'])->toBe(1.0);
});

test('invoicing receipt model supports delivery_date, tanggal_penerimaan, and tanggal_po in fillable and casts', function () {
    $receipt = new InvoicingReceipt([
        'no_po' => '5300066637',
        'delivery_date' => '2026-11-23',
        'tanggal_penerimaan' => '2026-10-09',
        'tanggal_po' => '2026-08-19',
        'no_grs' => '5007731110',
    ]);

    expect($receipt->getFillable())->toContain('delivery_date', 'tanggal_penerimaan', 'tanggal_po')
        ->and($receipt->getCasts())->toHaveKeys(['delivery_date', 'tanggal_penerimaan', 'tanggal_po'])
        ->and($receipt->getCasts()['delivery_date'])->toBe('date')
        ->and($receipt->getCasts()['tanggal_penerimaan'])->toBe('date')
        ->and($receipt->getCasts()['tanggal_po'])->toBe('date')
        ->and($receipt->delivery_date?->format('Y-m-d'))->toBe('2026-11-23')
        ->and($receipt->tanggal_penerimaan?->format('Y-m-d'))->toBe('2026-10-09')
        ->and($receipt->tanggal_po?->format('Y-m-d'))->toBe('2026-08-19')
        ->and($receipt->no_grs)->toBe('5007731110');
});

test('grs pdf parser service gracefully handles non-pdf string and missing fields', function () {
    $service = app(GrsPdfParserService::class);
    $data = $service->parse('This is not a pdf');

    expect($data['no_grs'])->toBeNull()
        ->and($data['delivery_date'])->toBeNull()
        ->and($data['tanggal_penerimaan'])->toBeNull()
        ->and($data['tanggal_release_gr'])->toBeNull();
});
