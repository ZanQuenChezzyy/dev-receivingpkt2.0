<?php

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use App\Models\PurchaseOrderIssued;
use App\Services\NonStockAgingImportService;
use Database\Seeders\RequestingUnitSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat file XLSX multi-sheet valid secara terprogram untuk pengujian streaming XMLReader.
 *
 * @param  array<string, list<list<string|int|float>>>  $sheets
 */
function createTestXlsx(array $sheets): string
{
    $path = tempnam(sys_get_temp_dir(), 'aging_xlsx_').'.xlsx';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $sharedStrings = [];
    $sharedIndex = [];
    $intern = function (string $value) use (&$sharedStrings, &$sharedIndex): int {
        if (! array_key_exists($value, $sharedIndex)) {
            $sharedIndex[$value] = count($sharedStrings);
            $sharedStrings[] = $value;
        }

        return $sharedIndex[$value];
    };

    $sheetEntries = [];
    $relEntries = [];
    $contentTypeOverrides = [];
    $sheetNo = 1;

    foreach ($sheets as $sheetName => $rows) {
        $rid = 'rId'.$sheetNo;
        $sheetXmlPath = "xl/worksheets/sheet{$sheetNo}.xml";
        $sheetEntries[] = '<sheet name="'.htmlspecialchars($sheetName, ENT_XML1).'" sheetId="'.$sheetNo.'" r:id="'.$rid.'"/>';
        $relEntries[] = '<Relationship Id="'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$sheetNo.'.xml"/>';
        $contentTypeOverrides[] = '<Override PartName="/'.$sheetXmlPath.'" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';

        $rowsXml = '';
        foreach ($rows as $rIdx => $cols) {
            $rowNum = $rIdx + 1;
            $cellsXml = '';
            foreach ($cols as $cIdx => $val) {
                $colLetter = '';
                $n = $cIdx + 1;
                while ($n > 0) {
                    $rem = ($n - 1) % 26;
                    $colLetter = chr(65 + $rem).$colLetter;
                    $n = intdiv($n - 1, 26);
                }
                $ref = $colLetter.$rowNum;
                if (is_int($val) || is_float($val)) {
                    $cellsXml .= '<c r="'.$ref.'"><v>'.$val.'</v></c>';
                } else {
                    $idx = $intern((string) $val);
                    $cellsXml .= '<c r="'.$ref.'" t="s"><v>'.$idx.'</v></c>';
                }
            }
            $rowsXml .= '<row r="'.$rowNum.'">'.$cellsXml.'</row>';
        }

        $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$rowsXml.'</sheetData></worksheet>';
        $zip->addFromString($sheetXmlPath, $worksheetXml);
        $sheetNo++;
    }

    $ssItems = '';
    foreach ($sharedStrings as $s) {
        $ssItems .= '<si><t>'.htmlspecialchars($s, ENT_XML1).'</t></si>';
    }
    $zip->addFromString(
        'xl/sharedStrings.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">'
        .$ssItems.'</sst>'
    );

    $relEntries[] = '<Relationship Id="rId'.($sheetNo).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';

    $zip->addFromString(
        'xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        .'<sheets>'.implode('', $sheetEntries).'</sheets></workbook>'
    );

    $zip->addFromString(
        'xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .implode('', $relEntries).'</Relationships>'
    );

    $zip->addFromString(
        '_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        .'</Relationships>'
    );

    $zip->addFromString(
        '[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        .implode('', $contentTypeOverrides).'</Types>'
    );

    $zip->close();

    return $path;
}

beforeEach(function (): void {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('purchase_order_issueds')) {
        Schema::create('purchase_order_issueds', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_order_and_item')->nullable();
            $table->string('material_type')->nullable();
            $table->string('mrp_type')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->integer('item_no')->default(10);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty_po', 15, 2)->default(1);
            $table->string('uoi')->default('EA');
            $table->string('vendor_name')->nullable();
            $table->string('currency')->default('IDR');
            $table->decimal('net_price', 18, 2)->default(0);
            $table->decimal('total_amount_in_lc', 18, 2)->default(0);
            $table->string('requisitioner')->nullable();
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

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->date('received_date')->nullable();
            $table->date('physical_received_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_details')) {
        Schema::create('delivery_order_receipt_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->integer('item_no')->default(10);
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('material_code')->nullable();
            $table->string('description')->nullable();
            $table->string('uoi')->default('EA');
            $table->string('mrp_type')->nullable();
            $table->string('material_type')->nullable();
            $table->string('requisitioner')->nullable();
            $table->decimal('total_amount_snapshot', 18, 2)->default(0);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtvs')) {
        Schema::create('grs_rdtvs', function (Blueprint $table): void {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('category')->default('GRS');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('grs_rdtv_items')) {
        Schema::create('grs_rdtv_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grs_rdtv_id');
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('material_issue_details')) {
        Schema::create('material_issue_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('material_issue_id');
            $table->unsignedBigInteger('delivery_order_receipt_detail_id');
            $table->decimal('diminta', 15, 2)->default(0);
            $table->decimal('diserahkan', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    foreach ([
        '2026_11_23_214628_create_requesting_units_table.php',
        '2026_11_23_214629_create_nonstock_aging_controls_table.php',
        '2026_11_23_214629_create_nonstock_aging_logs_table.php',
        '2026_11_23_214630_create_nonstock_aging_snapshots_table.php',
    ] as $file) {
        $migration = include database_path('migrations/'.$file);
        $migration->down();
        $migration->up();
    }
});

test('imports multi-sheet xlsx workbook with GRS lookup, IDR currency safety, PIC extraction, and idempotent upsert', function (): void {
    $this->seed(RequestingUnitSeeder::class);

    // PO USD yang sudah ada di database dengan nilai LC IDR valid
    $poUsd = PurchaseOrderIssued::query()->create([
        'purchase_order_and_item' => '5300057304-10',
        'purchase_order_no' => '5300057304',
        'item_no' => 10,
        'mrp_type' => 'NONSTOCK',
        'material_type' => 'ZSP',
        'description' => 'GAUGE PRESSURE TRANSMITTER',
        'qty_po' => 1,
        'uoi' => 'EA',
        'vendor_name' => 'PT VENDOR USD',
        'currency' => 'USD',
        'net_price' => 10000,
        'total_amount_in_lc' => 163_806_000,
        'requisitioner' => 'HAR1-TAP5 MR 6648/MR/VII/2024',
    ]);

    $xlsxPath = createTestXlsx([
        'GRS' => [
            ['PO by Item', 'Entry Date', 'Posting Date', 'Purchase Order', 'Item', 'Material', 'Material Description', 'Text', 'Amount in LC', 'QTY GR', 'MRP TYPE', 'MRP TYPE'],
            ['5300057304-10', '08/11/2025', '08/11/2025', '5300057304', 10, '6050364', 'GAUGE PRESSURE TRANSMITTER', 'FLOOR-A', 163806000, 1, 'NONSTOCK', 'ZSP'],
            ['5300060968-20', '20/10/2025', '20/10/2025', '5300060968', 20, '6050999', 'GATE VALVE 3/4 IN', 'FLOOR-C', 63600000, 40, 'PD', 'ZSP'],
        ],
        'R02' => [
            ['PO by Item', 'Nomor PO', 'Item PO', 'Vendor Name', 'Qty PO', 'Satuan PO', 'Total Amount in Local Curr'],
            ['5300060968-20', '5300060968', 20, 'RISKA BAHARI UTAMA Pusat, PT.', 40, 'EA', '63.600.000'],
        ],
        'MONITORING' => [
            ['PO by Item', 'Purchase Order', 'Item', 'Material', 'Material Description', 'Keterangan', 'Requisitioner', 'Contact Person', 'Vendor Name', 'MRP', 'Total Qty GR', 'Satuan PO', 'Currency PO', 'Total Value', 'Total Qty Issued', 'Avalaible Value', 'Cek Fisik', 'Status Pengambilan', 'Tanggal Update', 'Catatan'],
            ['5300057304-10', '5300057304', 10, '6050364', 'GAUGE PRESSURE TRANSMITTER', 'FLOOR-A', 'HAR1-TAP5 MR 6648/MR/VII/2024', 'Achmad Bastomi 082252699992', 'PT VENDOR USD', 'NONSTOCK', 1, 'EA', 'USD', 10000, 0, 10000, 'TRUE', 'BELUM DIAMBIL', '24-August-2026', 'Sudah dihubungi'],
            ['5300060968-20', '5300060968', 20, '', '', '', 'LAB KIMIA', 'Ibnu Sina 085247376525', '', '', 0, '', 'IDR', 0, 0, 0, 'FALSE', 'ON CHECK', '23-October-2026', 'UNCONFIRMED'],
            ['5000007883-20', '5000007883', 20, '6054216', 'SEAT VALVE EUR', 'FLOOR-D', 'RENDAL-TAP3', '', 'BORSIG', 'PD', 4, 'EA', 'EUR', 10604, 0, 10604, 'TRUE', 'BELUM DIAMBIL', '15-August-2026', 'AVAILABLE'],
            ['5300039513-10', '5300039513', 10, '6030878', 'NUT LOCK', 'FLR A', 'RENHAR-P2', '', 'VENDOR', 'NONSTOCK', 1, 'EA', 'IDR', 5700000, 1, 0, 'TRUE', 'DIPINDAH', '15-August-2026', 'CONFIRMED BY GD 1'],
        ],
    ]);

    try {
        $service = app(NonStockAgingImportService::class);
        $res = $service->import($xlsxPath, ['only_active_statuses' => false]);

        expect($res['mode'])->toBe('workbook_monitoring')
            ->and($res['rows_scanned'])->toBe(4)
            ->and($res['created'])->toBe(4)
            ->and($res['matched_db_po'])->toBe(1)
            ->and($res['matched_unit'])->toBe(4)
            ->and($res['contacts_extracted'])->toBe(2)
            ->and($res['foreign_currency_unconverted_count'])->toBe(1);

        $c1 = NonStockAgingControl::query()->where('po_by_item', '5300057304-10')->firstOrFail();
        expect($c1->purchase_order_issued_id)->toBe($poUsd->id)
            ->and($c1->initial_value_lc)->toBe(163_806_000.0)
            ->and($c1->grs_posting_date?->toDateString())->toBe('2025-11-08')
            ->and($c1->pic_name_override)->toBe('Achmad Bastomi')
            ->and($c1->pic_phone_override)->toBe('082252699992')
            ->and($c1->requestingUnit?->name)->toBe('Rendal TA P5')
            ->and($c1->followup_stage)->toBe(AgingFollowupStage::ReminderTahap1);

        // Item 2 terisi penuh dari tab GRS + R02 meskipun kolom di MONITORING kosong
        $c2 = NonStockAgingControl::query()->where('po_by_item', '5300060968-20')->firstOrFail();
        expect($c2->mrp_type_snapshot)->toBe('PD')
            ->and($c2->initial_value_lc)->toBe(63_600_000.0)
            ->and($c2->operational_status)->toBe(AgingOperationalStatus::OnCheck)
            ->and($c2->physical_checked)->toBeFalse()
            ->and($c2->vendor_name_snapshot)->toBe('RISKA BAHARI UTAMA, PT.');

        // Item 3 EUR tanpa LC tidak boleh dijumlahkan mentah sebagai 10.604 Rupiah
        $c3 = NonStockAgingControl::query()->where('po_by_item', '5000007883-20')->firstOrFail();
        expect($c3->initial_value_lc)->toBeNull();

        // Item 4 Dipindah tercatat lokasi tujuan
        $c4 = NonStockAgingControl::query()->where('po_by_item', '5300039513-10')->firstOrFail();
        expect($c4->operational_status)->toBe(AgingOperationalStatus::Dipindah)
            ->and($c4->moved_to_location)->toBe('CONFIRMED BY GD 1');

        // Set c1 sebagai Scheduled Hold lalu re-import -> tidak boleh duplikat & Scheduled Hold harus terlindungi
        $c1->update([
            'operational_status' => AgingOperationalStatus::ScheduledHold,
            'planned_pickup_date' => '2026-12-20',
            'scheduled_hold_reason' => 'Menunggu jadwal TA P5',
        ]);

        $res2 = $service->import($xlsxPath, ['only_active_statuses' => false]);
        expect($res2['created'])->toBe(0)
            ->and($res2['updated'])->toBe(4)
            ->and(NonStockAgingControl::query()->count())->toBe(4)
            ->and(NonStockAgingLog::query()->count())->toBe(4)
            ->and($c1->fresh()->operational_status)->toBe(AgingOperationalStatus::ScheduledHold)
            ->and($c1->fresh()->planned_pickup_date?->toDateString())->toBe('2026-12-20');
    } finally {
        @unlink($xlsxPath);
    }
});

test('supports active-only filter, semicolon CSV with BOM, and artisan CLI dry-run & execute', function (): void {
    $csvPath = tempnam(sys_get_temp_dir(), 'aging_csv_').'.csv';
    $csvContent = "\xEF\xBB\xBF"
        ."PO by Item;Purchase Order;Item;Material Description;Keterangan;Requisitioner;Contact Person;MRP;Total Qty GR;Currency PO;Total Value;Cek Fisik;Status Pengambilan;Catatan\n"
        ."5300070001-10;5300070001;10;VALVE CSV;FLOOR-B;LAB;Hafid 081393659432;NONSTOCK;2;IDR;12.500.000;TRUE;BELUM DIAMBIL;AVAILABLE\n"
        ."5300070002-10;5300070002;10;DONE CSV;FLOOR-B;LAB;;NONSTOCK;1;IDR;5.000.000;TRUE;DIAMBIL;SELESAI\n";
    file_put_contents($csvPath, $csvContent);

    try {
        // Dry-run via Artisan CLI tidak boleh menyimpan baris ke DB
        $this->artisan('nonstock:import-monitoring', [
            'path' => $csvPath,
            '--active-only' => true,
            '--dry-run' => true,
        ])->assertSuccessful();

        expect(NonStockAgingControl::query()->count())->toBe(0);

        // Eksekusi nyata dengan --active-only hanya menyimpan 1 item aktif
        $this->artisan('nonstock:import-monitoring', [
            'path' => $csvPath,
            '--active-only' => true,
        ])->assertSuccessful();

        expect(NonStockAgingControl::query()->count())->toBe(1);

        $item = NonStockAgingControl::query()->where('po_by_item', '5300070001-10')->firstOrFail();
        expect($item->initial_value_lc)->toBe(12_500_000.0)
            ->and($item->pic_name_override)->toBe('Hafid')
            ->and($item->pic_phone_override)->toBe('081393659432');
    } finally {
        @unlink($csvPath);
    }
});

test('imports standalone GRS MB51 xlsx sheet when MONITORING tab is absent', function (): void {
    $xlsxPath = createTestXlsx([
        'GRS' => [
            ['PO by Item', 'Entry Date', 'Posting Date', 'Purchase Order', 'Item', 'Material', 'Material Description', 'Text', 'Amount in LC', 'QTY GR', 'MRP TYPE', 'MRP TYPE'],
            ['5300080001-10', '01/10/2026', '01/10/2026', '5300080001', 10, '6099001', 'PUMP SEAL KIT', 'FLOOR-A', 85000000, 1, 'NONSTOCK', 'ZSP'],
        ],
    ]);

    try {
        $result = app(NonStockAgingImportService::class)->import($xlsxPath);

        expect($result['mode'])->toBe('grs_only')
            ->and($result['created'])->toBe(1);

        $item = NonStockAgingControl::query()->where('po_by_item', '5300080001-10')->firstOrFail();
        expect($item->data_source)->toBe('import_mb51')
            ->and($item->grs_posting_date?->toDateString())->toBe('2026-10-01')
            ->and($item->initial_value_lc)->toBe(85_000_000.0)
            ->and($item->location_text)->toBe('FLOOR-A');
    } finally {
        @unlink($xlsxPath);
    }
});
