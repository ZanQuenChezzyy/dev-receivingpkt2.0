<!DOCTYPE html>
<html>
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="content-type">
    <style type="text/css">
        @page {
            size: 210mm 330mm portrait;
            margin: 10mm 10mm;
        }

        body, * {
            font-family: 'Tahoma', sans-serif !important;
        }

        body {
            font-size: 7pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }

        /* PKT Branding */
        .pkt-blue { color: #0054A6; }
        .pkt-orange { color: #F26522; }
        .bg-pkt-blue { background-color: #0054A6; }
        .bg-pkt-orange { background-color: #F26522; }

        .document-title {
            font-size: 16pt;
            font-weight: bold;
            color: #1e293b;
            line-height: 1.2;
        }

        .document-subtitle {
            font-size: 7pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-label {
            font-size: 5.5pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 8.5pt;
            font-weight: 700;
            color: #1e293b;
        }

        .header-divider {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 12px;
            padding-bottom: 12px;
        }

        /* Destination Box */
        .destination-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #F26522; /* PKT Orange Accent */
            padding: 6px 10px;
        }

        /* Data Table */
        .data-table {
            margin-top: 15px;
        }

        .data-table th {
            background-color: #0054A6; /* PKT Blue */
            color: #ffffff;
            font-weight: 700;
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6pt 4pt;
            border: 1px solid #004080;
            vertical-align: middle;
        }

        .data-table td {
            padding: 5pt 4pt;
            border: 1px solid #cbd5e1;
            color: #334155;
            vertical-align: middle;
            font-size: 6.5pt;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f1f5f9; /* Slate 100 for zebra */
        }
        
        .page {
            width: 100%;
        }
    </style>
    <title>Bulk Transmittals</title>
</head>
<body>
    @foreach ($transmittals as $transmittal)
        <div class="page" style="{{ $loop->first ? 'page-break-before: auto;' : 'page-break-before: always;' }}">
            <!-- Top Branding Line -->
            <table style="width: 100%; height: 6px; margin-bottom: 15px;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="background-color: #0054A6; width: 75%; height: 6px;"></td>
                    <td style="background-color: #F26522; width: 25%; height: 6px;"></td>
                </tr>
            </table>

            <div class="header-divider">
                <table>
                    <tr>
                        <td style="width: 65%; vertical-align: bottom;">

                            <div class="document-title">TRANSMITTAL PENGIRIMAN GUDANG</div>
                            <div class="document-subtitle">Pengiriman Barang Receiving</div>
                        </td>
                        <td style="width: 35%; vertical-align: bottom; text-align: right;">
                            <div class="info-label">TANGGAL CETAK</div>
                            <div class="info-value">{{ now()->translatedFormat('d F Y, H:i') }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <table style="margin-bottom: 15px;">
                <tr>
                    <td style="width: 30%; vertical-align: top;">
                        <div class="info-label">DIKIRIM OLEH</div>
                        <div class="info-value">{{ $transmittal->destination->pic->name ?? '-' }}</div>
                    </td>
                    <td style="width: 30%; vertical-align: top;">
                        <div class="info-label">DITERIMA OLEH</div>
                        <div class="info-value">-</div>
                    </td>
                    <td style="width: 40%; vertical-align: top;">
                        <div class="destination-box text-right">
                            <div class="info-label">TUJUAN RECEIVING</div>
                            @php
                                $dest = strtoupper($transmittal->destination->name ?? '-');
                                if ($dest === 'ISTEK') $dest = 'INSPEKSI TEKNIK 2';
                            @endphp
                            <div class="info-value pkt-orange">{{ $dest }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 3%; white-space: nowrap;">NO.</th>
                        <th class="text-center" style="width: 8%;">TGL KIRIM</th>
                        <th class="text-center" style="width: 9%;">NO. PO</th>
                        <th class="text-center" style="width: 5%;">ITEM</th>
                        <th class="text-center" style="width: 9%;">MATERIAL</th>
                        <th class="text-left" style="width: 25%;">DESCRIPTION</th>
                        <th class="text-center" style="width: 6%;">QTY RCV</th>
                        <th class="text-center" style="width: 5%;">UOI</th>
                        <th class="text-center" style="width: 15%;">GUDANG TUJUAN</th>
                        <th class="text-center" style="width: 15%;">PENYIMPANAN 3P01</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transmittal->items as $index => $item)
                        @php
                            $detail = $item->detail;
                            $poNo = optional($detail->purchaseOrderIssued)->purchase_order_no ?? '-';
                            $storeArea = optional($detail->locationReceiving)->name ?? '-';
                            if ($storeArea == '-' || empty($storeArea)) {
                                $storeArea = 'FLOOR-E';
                            }
                        @endphp
                        <tr>
                            <td class="text-center font-bold" style="color: #475569;">{{ $index + 1 }}</td>
                            <td class="text-center">{{ $transmittal->tanggal->format('d/m/Y') }}</td>
                            <td class="text-center font-bold">{{ $poNo }}</td>
                            <td class="text-center">{{ $detail->item_no ?? '-' }}</td>
                            <td class="text-center">{{ $detail->material_code ?? '-' }}</td>
                            <td class="text-left">{{ preg_replace('/\s+/', ' ', trim($detail->description ?? '-')) }}</td>
                            <td class="text-center font-bold">{{ $detail->quantity !== null ? number_format((float) $detail->quantity) : '-' }}</td>
                            <td class="text-center">{{ $detail->uoi ?? '-' }}</td>
                            @php
                                $destCol = strtoupper($transmittal->destination->name ?? '-');
                                if ($destCol === 'ISTEK') $destCol = 'INSPEKSI TEKNIK 2';
                            @endphp
                            <td class="text-center" style="color: #475569;">{{ $destCol }}</td>
                            <td class="text-center" style="color: #475569;">{{ strtoupper($storeArea) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
