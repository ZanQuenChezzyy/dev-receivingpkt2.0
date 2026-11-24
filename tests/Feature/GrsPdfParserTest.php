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
        ->and($data['no_rdtv'])->toBeNull()
        ->and($data['delivery_date'])->toBeNull()
        ->and($data['tanggal_penerimaan'])->toBeNull()
        ->and($data['tanggal_release_gr'])->toBeNull();
});

test('grs pdf parser service extracts rdtv fields including no document return as no_rdtv', function () {
    $sampleRdtvText = <<<'TXT'
Nama Vendor : Flow Technology Indonesia
Alamat Vendor : JL. RAYA DARMO PERMAI TIMUR 18 G-H
Sukomanunggal Kota Surabaya
Jawa Timur 60189
Indonesia
Halaman : 1 of 1
Tanggal Cetak : 21.11.2026

RETURN DELIVERY TO VENDOR
No Document Return : 5007756200

No PO : 5300065382
Tanggal PO : 07.05.2026
Tanggal Penerimaan : 02.11.2026
Tanggal Penolakan : 21.11.2026
No Surat Jalan : FTGI-1026-0115
No BL/AWB :

No Kode Material Deskripsi/Spesifikasi UOM Plant Qty PO Qty Received Qty Reject No Inspection Lot Reason for Movement Keterangan
1 6043966 IMPELLER,PUMP:RHEINHT;RCE-80/320;POS.230 EA D601 1 1 1 10000464480 Out of Scope TIDAK MELAMPIRKAN COO FROM MANUFACTURE
TXT;

    $service = app(GrsPdfParserService::class);
    $data = $service->parse($sampleRdtvText);

    expect($data['no_rdtv'])->toBe('5007756200')
        ->and($data['no_po'])->toBe('5300065382')
        ->and($data['tanggal_po'])->toBe('2026-05-07')
        ->and($data['tanggal_penerimaan'])->toBe('2026-11-02')
        ->and($data['tanggal_penolakan'])->toBe('2026-11-21')
        ->and($data['no_do'])->toBe('FTGI-1026-0115')
        ->and($data['vendor_name'])->toBe('Flow Technology Indonesia')
        ->and($data['nama_barang'])->toBe('IMPELLER,PUMP:RHEINHT;RCE-80/320;POS.230')
        ->and($data['uoi'])->toBe('EA')
        ->and($data['qty_penagihan'])->toBe(1.0)
        ->and($data['alasan_penolakan'])->toBe('TIDAK MELAMPIRKAN COO FROM MANUFACTURE');
});

test('grs pdf parser service extracts rdtv fields directly from real rdtv pdf fixture', function () {
    $fixturePath = base_path('tests/Fixtures/contoh_RDTV.pdf');
    expect(file_exists($fixturePath))->toBeTrue();

    $service = app(GrsPdfParserService::class);
    $data = $service->parse($fixturePath);

    expect($data['no_rdtv'])->toBe('5007756200')
        ->and($data['no_po'])->toBe('5300065382')
        ->and($data['tanggal_po'])->toBe('2026-05-07')
        ->and($data['tanggal_penerimaan'])->toBe('2026-11-02')
        ->and($data['tanggal_penolakan'])->toBe('2026-11-21')
        ->and($data['no_do'])->toBe('FTGI-1026-0115')
        ->and($data['vendor_name'])->toBe('Flow Technology Indonesia')
        ->and($data['uoi'])->toBe('EA')
        ->and($data['qty_penagihan'])->toBe(1.0)
        ->and($data['alasan_penolakan'])->toBe('TIDAK MELAMPIRKAN COO FROM MANUFACTURE');
});

test('grs pdf parser decodes Nitro Pro style PDFs with indirect /Font dictionary, /R7 font names, compressed 1-byte CMap, and literal strings', function () {
    // Bangun peta CMap 1-byte (<00><ff>) mirip output Nitro Pro 13 (tanpa spasi antar <hex>)
    $sampleText = '22.12.2026 Delivery Date GOODS RECEIPT SLIP No Release GR : 5007756199 No PO : 5300067497 Tanggal PO : 28.10.2026 Tanggal Penerimaan : 11.11.2026 Tanggal Release GR : 21.11.2026 No. Surat Jalan : 05429715';
    $charToByte = [];
    $bfRanges = [];
    $nextCode = 1;
    for ($i = 0; $i < strlen($sampleText); $i++) {
        $ch = $sampleText[$i];
        if (! isset($charToByte[$ch])) {
            $hexSrc = sprintf('%02x', $nextCode);
            $hexDst = sprintf('%04x', ord($ch));
            $charToByte[$ch] = chr($nextCode);
            $bfRanges[] = "<{$hexSrc}><{$hexSrc}><{$hexDst}>";
            $nextCode++;
        }
    }

    $cmapStream = '/CIDInit /ProcSet findresource begin 12 dict begin begincmap /CMapType 2 def 1 begincodespacerange <00><ff> endcodespacerange '
        .count($bfRanges).' beginbfrange '.implode(' ', $bfRanges).' endbfrange endcmap';
    $compressedCmap = gzcompress($cmapStream);

    // Encode teks menjadi literal string PDF dengan escape karakter spesial
    $encodedBytes = '';
    for ($i = 0; $i < strlen($sampleText); $i++) {
        $b = $charToByte[$sampleText[$i]];
        if ($b === '\\' || $b === '(' || $b === ')') {
            $encodedBytes .= '\\'.$b;
        } elseif ($b === "\n") {
            $encodedBytes .= '\\n';
        } elseif ($b === "\r") {
            $encodedBytes .= '\\r';
        } else {
            $encodedBytes .= $b;
        }
    }

    $contentStream = gzcompress("BT /R7 9.96 Tf 0 1 -1 0 100 100 Tm ({$encodedBytes})' ET");

    $fakeNitroPdf = "%PDF-1.7\n"
        ."4 0 obj\n<</Type /Page /Resources <</Font 15 0 R>> /Contents 5 0 R>>\nendobj\n"
        ."5 0 obj\n<</Filter /FlateDecode /Length ".strlen($contentStream).">>\nstream\n{$contentStream}\nendstream\nendobj\n"
        ."7 0 obj\n<</Type /Font /Subtype /TrueType /ToUnicode 18 0 R>>\nendobj\n"
        ."15 0 obj\n<</R7 7 0 R>>\nendobj\n"
        ."18 0 obj\n<</Filter /FlateDecode /Length ".strlen($compressedCmap).">>\nstream\n{$compressedCmap}\nendstream\nendobj\n";

    $data = app(GrsPdfParserService::class)->parse($fakeNitroPdf);

    expect($data['no_grs'])->toBe('5007756199')
        ->and($data['delivery_date'])->toBe('2026-12-22')
        ->and($data['no_po'])->toBe('5300067497')
        ->and($data['tanggal_po'])->toBe('2026-10-28')
        ->and($data['tanggal_penerimaan'])->toBe('2026-11-11')
        ->and($data['tanggal_release_gr'])->toBe('2026-11-21')
        ->and($data['no_do'])->toBe('05429715');
});
