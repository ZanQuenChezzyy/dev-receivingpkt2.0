<!DOCTYPE html>
<html>
<head>
    <title>QR Code DO</title>
    <style>
        @page {
            size: 3in 2in landscape;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 12px 6px 4px 24px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: black;
            line-height: 1.15;
        }
        .page {
            width: 100%;
            page-break-inside: avoid;
        }
        .page ~ .page {
            page-break-before: always;
        }
        
        /* Material Label Table */
        .header {
            width: 100%;
            border-bottom: 1px solid black;
            margin-bottom: 4px;
            padding-bottom: 2px;
            border-collapse: collapse;
        }
        .header td {
            vertical-align: bottom;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .info-table td {
            vertical-align: top;
            padding: 1px 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .lbl {
            font-weight: bold;
        }
        .colon {
            text-align: center;
        }
        .val {
            font-weight: bold;
        }
        .qr-cell {
            text-align: right;
            vertical-align: top;
        }
        .qr-img {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }
        .highlight {
            background-color: black;
            color: white;
        }
        .highlight td {
            padding-top: 1.5px;
            padding-bottom: 1.5px;
        }
        .highlight .lbl {
            padding-left: 3px;
        }
        .desc {
            display: inline-block;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        /* Document Label Table */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-weight: bold;
        }
        .doc-table td {
            padding: 2px;
            vertical-align: top;
            word-break: break-word;
        }
        .no-wrap {
            white-space: nowrap;
        }
        .wrap-text {
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
    </style>
</head>
<body>

    @if($mode === 'material' || $mode === 'both')
        @foreach ($items as $item)
            @php
                $itemNo = explode(' ', $item['label'])[1] ?? null;
                $detail = $do->deliveryOrderReceiptDetails->firstWhere('item_no', $itemNo);

                $poNo = optional(optional($detail)->purchaseOrderIssued)->purchase_order_no ?? '-';
                $mrpType = $detail ? $detail->mrp_type : '-';
                $itemNo = $detail->item_no ?? '-';
                $material = $detail->material_code ?? '-';
                $receivedBy = optional($do->receivedBy)->name ?? '-';
                $qtyReceived = $detail ? $detail->quantity . ' ' . $detail->uoi : '-';

                $tahun = '-';
                $poDate = optional(optional($detail)->purchaseOrderIssued)->date_create;

                if ($poDate) {
                    try { $tahun = \Carbon\Carbon::parse($poDate)->format('Y'); } catch (\Throwable $e) {}
                }
                if ($tahun === '-' && $do->received_date) {
                    try { $tahun = \Carbon\Carbon::parse($do->received_date)->format('Y'); } catch (\Throwable $e) {}
                }
                
                $mrpMap = ['INVESTASI' => 'INV', 'NONSTOCK' => 'NSTK', 'PD' => 'PD', 'V1' => 'V1'];
                $mrpCombined = $mrpType !== '-' ? ($mrpMap[$mrpType] ?? $mrpType) : '-';
                
                $itemCombined = $itemNo . ' | ' . $mrpCombined;
                $stockLabel = 'STOCK NO';
                
                $locationRaw = $detail?->is_different_location
                    ? optional($detail->locationReceiving)->name ?? 'Lokasi Beda (Tidak diketahui)'
                    : optional(optional($do->deliveryOrderReceiptDetails->first())->locationReceiving)->name ?? 'Lokasi Utama (Tidak diketahui)';
                $location = $locationRaw;
                
                $receivedAt = '-';
                if ($do->received_date) {
                    try { $receivedAt = \Carbon\Carbon::parse($do->received_date)->format('d/m/Y'); } catch (\Throwable $e) {}
                }

                $rawDesc = (string) ($detail->description ?? '-');
                $descFlat = preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\n", "\r", "\t"], ' ', $rawDesc));
                $descOneLine = mb_substr($descFlat, 0, 40); // Allow slightly longer desc as it can wrap
            @endphp

            <div class="page">
                <table class="header">
                    <tr>
                        <td style="font-weight: bold; font-size: 10px; padding-bottom: 2px;">LABEL MATERIAL</td>
                        <td style="text-align: right; padding-bottom: 2px;"><img src="{{ $logo }}" style="height: 12px;"></td>
                    </tr>
                </table>

                <table class="info-table">
                    <!-- DOMPDF Layout Lock -->
                    <tr style="height: 0; padding: 0; margin: 0;">
                        <td style="width: 32%; height: 0; padding: 0; margin: 0; border: none;"></td>
                        <td style="width: 4%; height: 0; padding: 0; margin: 0; border: none;"></td>
                        <td style="width: 46%; height: 0; padding: 0; margin: 0; border: none;"></td>
                        <td style="width: 18%; height: 0; padding: 0; margin: 0; border: none;"></td>
                    </tr>
                    
                    <tr class="highlight">
                        <td class="lbl" style="font-size: 12px;">NOMOR PO</td>
                        <td class="colon" style="font-size: 12px;">:</td>
                        <td class="val" style="font-size: 15px;">{{ $poNo }}</td>
                        <td class="qr-cell" rowspan="3" style="background-color: white;">
                            <img src="{{ $item['qr'] }}" class="qr-img">
                        </td>
                    </tr>
                    <tr class="highlight">
                        <td class="lbl" style="font-size: 12px;">ITEM NO</td>
                        <td class="colon" style="font-size: 12px;">:</td>
                        <td class="val" style="font-size: 12px;">{{ $itemCombined }}</td>
                    </tr>
                    <tr class="highlight">
                        <td class="lbl" style="font-size: 12px;">{{ $stockLabel }}</td>
                        <td class="colon" style="font-size: 12px;">:</td>
                        <td class="val" style="font-size: 12px;">{{ $mrpType !== 'NONSTOCK' && $mrpType !== 'PD1' ? $material : '-' }}</td>
                    </tr>
                    <tr><td colspan="4" style="height: 3px;"></td></tr>
                    <tr>
                        <td class="lbl">DESKRIPSI</td>
                        <td class="colon">:</td>
                        <td class="val" colspan="2"><span class="desc">{!! $descOneLine !!}</span></td>
                    </tr>
                    <tr>
                        <td class="lbl">QTY DITERIMA</td>
                        <td class="colon">:</td>
                        <td class="val" colspan="2">{{ $qtyReceived }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">TGL TERIMA</td>
                        <td class="colon">:</td>
                        <td class="val" colspan="2">{{ $receivedAt }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">DITERIMA OLEH</td>
                        <td class="colon">:</td>
                        <td class="val" colspan="2">{{ $receivedBy }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">LOKASI</td>
                        <td class="colon">:</td>
                        <td class="val" colspan="2" style="max-width: 130px;">{{ $location }}</td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endif

    @if($mode === 'document' || $mode === 'both')
        @php
            $allMrpTypes = $do->deliveryOrderReceiptDetails->pluck('mrp_type')->filter();
            $mrpMap = ['INVESTASI' => 'INV', 'NONSTOCK' => 'NSTK', 'PD' => 'PD', 'V1' => 'V1'];
            
            $mrpCounts = $allMrpTypes->countBy();
            $dominantType = $mrpCounts->sortDesc()->keys()->first();
            $dominantLabel = $dominantType ? ($mrpMap[$dominantType] ?? $dominantType) : '-';
            
            $hasNonStock = $allMrpTypes->contains('NONSTOCK');
            $fontSize = $hasNonStock ? '14px' : '16px';
            
            $tahun = '-';
            $firstDetail = $do->deliveryOrderReceiptDetails->first();
            $poDate = optional(optional($firstDetail)->purchaseOrderIssued)->date_create;
            if ($poDate) {
                try { $tahun = \Carbon\Carbon::parse($poDate)->format('Y'); } catch (\Throwable $e) {}
            }
            if ($tahun === '-' && $do->received_date) {
                try { $tahun = \Carbon\Carbon::parse($do->received_date)->format('Y'); } catch (\Throwable $e) {}
            }
            $receivedBy = optional($do->receivedBy)->name ?? '-';
        @endphp

        <div class="page">
            <table class="doc-table">
                <!-- DOMPDF Layout Lock -->
                <tr style="height: 0; padding: 0; margin: 0;">
                    <td style="width: 22%; height: 0; padding: 0; margin: 0; border: none;"></td>
                    <td style="width: 38%; height: 0; padding: 0; margin: 0; border: none;"></td>
                    <td style="width: 4%; height: 0; padding: 0; margin: 0; border: none;"></td>
                    <td style="width: 36%; height: 0; padding: 0; margin: 0; border: none;"></td>
                </tr>

                <tr>
                    <td rowspan="2" style="text-align: center; padding-right: 10px; vertical-align: top;">
                        <img src="{{ $qrDo }}" style="width: 47px; height: 47px;">
                    </td>
                    <td colspan="3" style="padding-bottom: 4px;">
                        <div style="border: 1px solid #000; padding: 4px 8px; font-size: {{ $fontSize }}; text-align: center; background-color: #000;">
                            <strong style="color: #fff;">
                                {{ optional(optional($firstDetail)->purchaseOrderIssued)->purchase_order_no ?? '-' }} | {{ $tahun }} | {{ $dominantLabel }}
                            </strong>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="no-wrap">TAHAPAN</td>
                    <td class="wrap-text" colspan="2">: {{ $do->stage ?? 'Tidak Ada' }}</td>
                </tr>
                <tr>
                    <td colspan="4" style="padding: 0; margin: 0; border: none;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0; padding: 0; font-weight: bold;">
                            <!-- Nested Layout Lock -->
                            <tr style="height: 0; padding: 0; margin: 0;">
                                <td style="width: 38%; height: 0; padding: 0; margin: 0; border: none;"></td>
                                <td style="width: 4%; height: 0; padding: 0; margin: 0; border: none;"></td>
                                <td style="width: 58%; height: 0; padding: 0; margin: 0; border: none;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px; vertical-align: top;">NOMOR DO</td>
                                <td style="text-align: center; padding: 2px; vertical-align: top;">:</td>
                                <td style="padding: 2px; word-break: break-word; vertical-align: top;">{{ $do->delivery_oder_no }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px; vertical-align: top;">TANGGAL TERIMA</td>
                                <td style="text-align: center; padding: 2px; vertical-align: top;">:</td>
                                <td style="padding: 2px; word-break: break-word; vertical-align: top;">{{ \Carbon\Carbon::parse($do->received_date)->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px; vertical-align: top;">TOTAL ITEM</td>
                                <td style="text-align: center; padding: 2px; vertical-align: top;">:</td>
                                <td style="padding: 2px; word-break: break-word; vertical-align: top;">{{ $do->deliveryOrderReceiptDetails->count() }} Item</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px; vertical-align: top;">DITERIMA OLEH</td>
                                <td style="text-align: center; padding: 2px; vertical-align: top;">:</td>
                                <td style="padding: 2px; word-break: break-word; vertical-align: top;">{{ $receivedBy }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: left; padding-top: 6px;">
                        <img src="{{ $logo }}" style="height: 15px;">
                        <div style="font-size: 7px; margin-top: 2px;">
                            QR Dicetak Menggunakan Sistem<br>
                            MOKONDO v2.0 (Receiving)
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif
</body>
</html>
