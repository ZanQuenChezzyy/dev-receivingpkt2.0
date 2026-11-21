<!DOCTYPE html>
<html>
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="content-type">
    <style type="text/css">
        @page {
            size: 210mm 330mm portrait;
            margin: 10mm 10mm 16mm 10mm;
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
            font-size: 14pt;
            font-weight: bold;
            color: #1e293b;
            line-height: 1.25;
        }

        .document-subtitle {
            font-size: 7pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 2px;
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
            margin-top: 14px;
        }

        .data-table th {
            background-color: #0054A6; /* PKT Blue */
            color: #ffffff;
            font-weight: 700;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6.5pt 5pt;
            border: 1px solid #004080;
            vertical-align: middle;
        }

        .data-table td {
            padding: 5.5pt 5pt;
            border: 1px solid #cbd5e1;
            color: #334155;
            vertical-align: middle;
            font-size: 7pt;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f1f5f9; /* Slate 100 for zebra */
        }

        .data-table tfoot td {
            background-color: #e2e8f0;
            border: 1px solid #94a3b8;
            color: #0f172a;
            font-weight: 700;
            font-size: 7.5pt;
            padding: 6pt 5pt;
        }

        /* Signature Area */
        .signature-table {
            margin-top: 28px;
            page-break-inside: avoid;
        }

        .signature-box {
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 8px 10px;
            vertical-align: top;
        }

        .signature-space {
            height: 46px;
        }

        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            font-size: 7pt;
            font-weight: 700;
            color: #1e293b;
        }

        /* Watermark Background (Diagonal, Subtle) */
        .pdf-watermark {
            position: fixed;
            top: 44%;
            left: -10%;
            width: 120%;
            text-align: center;
            transform: rotate(-30deg);
            font-size: 18pt;
            font-weight: 700;
            color: #94a3b8;
            opacity: 0.11;
            z-index: -1000;
            letter-spacing: 1px;
        }

        /* Fixed Bottom Footer Watermark */
        .pdf-footer {
            position: fixed;
            bottom: -9mm;
            left: 0;
            right: 0;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            font-size: 6.5pt;
            color: #64748b;
        }
    </style>
    <title>Transmittal Pengiriman Invoice Ke Kasir - {{ $printedAt->translatedFormat('d F Y, H:i') }}</title>
</head>
<body>
    <!-- Subtle Diagonal Watermark -->
    <div class="pdf-watermark">
        {{ $watermarkText }}
    </div>

    <!-- Fixed Bottom Watermark Footer -->
    <div class="pdf-footer">
        <table>
            <tr>
                <td class="text-left font-bold" style="color: #475569;">
                    {{ $watermarkText }}
                </td>
                <td class="text-right" style="color: #64748b;">
                    Total Dokumen: {{ $receipts->count() }} Invoice &bull; Dicetak: {{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WITA
                </td>
            </tr>
        </table>
    </div>

    <!-- Top Branding Line -->
    <table style="width: 100%; height: 6px; margin-bottom: 15px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="background-color: #0054A6; width: 75%; height: 6px;"></td>
            <td style="background-color: #F26522; width: 25%; height: 6px;"></td>
        </tr>
    </table>

    <!-- Header Title & Print Date -->
    <div class="header-divider">
        <table>
            <tr>
                <td style="width: 65%; vertical-align: bottom;">
                    <div class="document-title">TRANSMITTAL PENGIRIMAN INVOICE KE KASIR</div>
                    <div class="document-subtitle">Dokumen Penyerahan Berkas Tagihan Invoice &bull; Unit Penerimaan Invoice</div>
                </td>
                <td style="width: 35%; vertical-align: bottom; text-align: right;">
                    <div class="info-label">TANGGAL &amp; WAKTU CETAK</div>
                    <div class="info-value">{{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WITA</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Sender, Receiver, and Destination Box -->
    <table style="margin-bottom: 14px;">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                <div class="info-label">DIKIRIM OLEH</div>
                <div class="info-value">{{ $senderName }}</div>
                @if (! empty($sentDate))
                    <div style="font-size: 6.5pt; color: #64748b; margin-top: 2px;">
                        Tgl Kirim: {{ $sentDate->locale('id')->translatedFormat('d F Y') }}
                    </div>
                @endif
            </td>
            <td style="width: 30%; vertical-align: top;">
                <div class="info-label">DITERIMA OLEH</div>
                <div class="info-value">-</div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="destination-box text-right">
                    <div class="info-label">TUJUAN</div>
                    <div class="info-value pkt-blue">DEPARTMENT AKUNTANSI</div>
                    <div style="font-size: 6pt; color: #64748b; font-weight: 700; margin-top: 1px;">Dept. Akuntansi / Kasir PT Pupuk Kaltim</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Invoice Transmittal Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%; white-space: nowrap;">NO.</th>
                <th class="text-center" style="width: 14%;">NO. PO</th>
                <th class="text-left" style="width: 26%;">NAMA VENDOR</th>
                <th class="text-center" style="width: 17%;">NO. INVOICE</th>
                <th class="text-center" style="width: 19%;">NO. FAKTUR PAJAK</th>
                <th class="text-right" style="width: 19%;">SUBTOTAL (EXCLUDE PPN)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipts as $index => $receipt)
                @php
                    $vendorName = $receipt->vendor_name ?: ($receipt->vendor?->name ?? '-');
                    $subtotalVal = $receipt->subtotal_exclude_ppn;
                    $formattedSubtotal = $subtotalVal !== null
                        ? 'Rp '.number_format((float) $subtotalVal, 0, ',', '.')
                        : '-';
                @endphp
                <tr>
                    <td class="text-center font-bold" style="color: #475569;">{{ $index + 1 }}</td>
                    <td class="text-center font-bold">{{ $receipt->no_po ?: '-' }}</td>
                    <td class="text-left">{{ $vendorName }}</td>
                    <td class="text-center font-bold">{{ $receipt->no_invoice ?: '-' }}</td>
                    <td class="text-center">{{ $receipt->faktur_pajak ?: '-' }}</td>
                    <td class="text-right font-bold" style="white-space: nowrap;">{{ $formattedSubtotal }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right uppercase">Total Subtotal (Exclude PPN) &mdash; {{ $receipts->count() }} Dokumen</td>
                <td class="text-right pkt-blue" style="white-space: nowrap;">Rp {{ number_format((float) $totalSubtotalExclude, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Handover Signature Block -->
    <table class="signature-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 47%; padding-right: 12px;">
                <div class="signature-box">
                    <div class="info-label">DISERAHKAN OLEH (UNIT PENERIMAAN INVOICE)</div>
                    <div class="signature-space"></div>
                    <div class="signature-line">{{ $senderName }}</div>
                    <div style="font-size: 6pt; color: #64748b; margin-top: 2px;">Tanggal: {{ ($sentDate ?? $printedAt)->locale('id')->translatedFormat('d F Y') }}</div>
                </div>
            </td>
            <td style="width: 6%;"></td>
            <td style="width: 47%; padding-left: 12px;">
                <div class="signature-box">
                    <div class="info-label">DITERIMA OLEH (DEPT. AKUNTANSI / KASIR)</div>
                    <div class="signature-space"></div>
                    <div class="signature-line">( ............................................................ )</div>
                    <div style="font-size: 6pt; color: #64748b; margin-top: 2px;">Tanggal &amp; Paraf Penerima</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
