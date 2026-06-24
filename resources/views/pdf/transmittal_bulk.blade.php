<!DOCTYPE html>
<html>
<head>
    <title>Bulk Transmittals</title>
    <style>
        @page {
            size: A4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: black;
            margin: 0;
            padding: 10px;
        }
        .page {
            width: 100%;
        }
        .header-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin-bottom: 15px;
        }
        .info-table td {
            border: 1px solid black;
            padding: 5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
        }
        .data-table th, .data-table td {
            border: 1px solid black;
            padding: 5px;
            word-wrap: break-word;
        }
        .data-table th {
            background-color: #fff2cc;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
    </style>
</head>
<body>
    @foreach($transmittals as $transmittal)
    <div class="page" style="{{ $loop->first ? 'page-break-before: auto;' : 'page-break-before: always;' }}">
        <div class="header-title">TANGGAL CETAK : {{ now()->format('d/m/Y H:i:s') }}</div>

        <table class="info-table">
            <tr>
                <td style="width: 50%; font-weight: bold;" class="text-center" rowspan="1">
                    TRANSMITTAL<br>
                    PENGIRIMAN BARANG RECEIVING
                </td>
                <td style="width: 15%; font-weight: bold;">DIKIRIM OLEH:</td>
                <td style="width: 35%;" class="text-center">{{ $transmittal->destination->pic->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;" class="text-center">
                    RECEIVING --&gt; {{ strtoupper($transmittal->destination->name ?? '-') }}
                </td>
                <td style="font-weight: bold;">DITERIMA OLEH:</td>
                <td class="text-center">-</td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 3%;">NO.</th>
                    <th style="width: 8%;">TANGGAL<br>KIRIM</th>
                    <th style="width: 9%;">PO NO.</th>
                    <th style="width: 5%;">ITEM NO</th>
                    <th style="width: 8%;">MATERIAL<br>CODE</th>
                    <th style="width: 20%;">DESCRIPTION</th>
                    <th style="width: 6%;">QTY<br>RECEIVED</th>
                    <th style="width: 5%;">UOI</th>
                    <th style="width: 12%;">GUDANG TUJUAN</th>
                    <th style="width: 12%;">STORE AREA 3P01</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transmittal->items as $index => $item)
                    @php
                        $detail = $item->detail;
                        $poNo = optional($detail->purchaseOrderIssued)->purchase_order_no ?? '-';
                        $storeArea = optional($detail->locationReceiving)->name ?? '-';
                        if ($storeArea == '-' || empty($storeArea)) {
                            $storeArea = 'FLOOR-E';
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $transmittal->tanggal->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $poNo }}</td>
                        <td class="text-center">{{ $detail->item_no ?? '-' }}</td>
                        <td class="text-center">{{ $detail->material_code ?? '-' }}</td>
                        <td>{{ $detail->description ?? '-' }}</td>
                        <td class="text-center">{{ $detail->quantity !== null ? (float) $detail->quantity : '-' }}</td>
                        <td class="text-center">{{ $detail->uoi ?? '-' }}</td>
                        <td class="text-center">{{ strtoupper($transmittal->destination->name ?? '-') }}</td>
                        <td class="text-center">{{ strtoupper($storeArea) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</body>
</html>
