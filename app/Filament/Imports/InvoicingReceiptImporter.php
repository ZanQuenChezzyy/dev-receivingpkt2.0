<?php

namespace App\Filament\Imports;

use App\Models\InvoicingReceipt;
use App\Services\InvoicingCsvImportService;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Number;

class InvoicingReceiptImporter extends Importer
{
    protected static ?string $model = InvoicingReceipt::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('no_invoice')
                ->label('Invoice Number')
                ->requiredMapping()
                ->guess(['invoice number', 'no invoice', 'no_invoice', 'nomor invoice'])
                ->rules(['required', 'max:255'])
                ->exampleHeader('Invoice Number')
                ->example('INV-20260036'),

            ImportColumn::make('transaction_type')
                ->label('Transaction Type')
                ->guess(['transaction type', 'jenis transaksi'])
                ->exampleHeader('Transaction Type')
                ->example('PO Barang'),

            ImportColumn::make('no_po')
                ->label('PO Number')
                ->requiredMapping()
                ->guess(['po number', 'po numbers', 'no po', 'no_po', 'purchase order no'])
                ->rules(['required'])
                ->exampleHeader('PO Number')
                ->example('5300066895'),

            ImportColumn::make('vendor_code')
                ->label('Vendor Code')
                ->guess(['vendor code', 'vendor id', 'vendor_id', 'vendor number'])
                ->exampleHeader('Vendor Code')
                ->example('1000022010'),

            ImportColumn::make('vendor_name')
                ->label('Vendor Name')
                ->guess(['vendor name', 'vendor_name', 'nama vendor'])
                ->exampleHeader('Vendor Name')
                ->example('ERDY JAYA MAKMUR'),

            ImportColumn::make('faktur_pajak')
                ->label('Tax Number (Faktur Pajak)')
                ->guess(['tax number', 'faktur pajak', 'faktur_pajak'])
                ->exampleHeader('Tax Number')
                ->example('03002600453809710'),

            ImportColumn::make('subtotal_exclude_ppn')
                ->label('PO / Invoice Amount (Exclude PPN)')
                ->guess(['po amount', 'invoice amount', 'subtotal exclude ppn', 'subtotal_exclude_ppn'])
                ->exampleHeader('PO Amount')
                ->example('37260000'),

            ImportColumn::make('deskripsi')
                ->label('Description')
                ->guess(['description', 'deskripsi', 'po notes'])
                ->exampleHeader('Description')
                ->example('PEMBELIAN WIREMESH, KNITTED'),

            ImportColumn::make('no_grs')
                ->label('GR / SA / BA-BL Number (No. GRS)')
                ->guess(['gr / sa / ba-bl number', 'no grs', 'no_grs', 'gr number'])
                ->exampleHeader('GR / SA / BA-BL Number')
                ->example('5007705001'),

            ImportColumn::make('no_do')
                ->label('DO / BAPP / Timesheet Number (No. DO)')
                ->guess(['do / bapp / timesheet number', 'no do', 'no_do', 'delivery order no'])
                ->exampleHeader('DO / BAPP / Timesheet Number')
                ->example('202600026'),

            ImportColumn::make('arrival_deadline_date')
                ->label('Receipt-DO / BAPP / Arrival Deadline Date')
                ->guess(['receipt-do / bapp / arrival deadline date', 'arrival deadline date', 'received_date'])
                ->exampleHeader('Receipt-DO / BAPP / Arrival Deadline Date')
                ->example('2026-10-29'),

            ImportColumn::make('actual_arrival_date')
                ->label('Completion / Actual / Arrival Date')
                ->guess(['completion / actual / arrival date', 'actual arrival date', 'physical_received_date'])
                ->exampleHeader('Completion / Actual / Arrival Date')
                ->example('2026-10-29'),

            ImportColumn::make('created_by')
                ->label('Created By (PIC)')
                ->guess(['created by', 'pic'])
                ->exampleHeader('Created By')
                ->example('STAF INVOICE 1'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Toggle::make('only_po_barang')
                ->label('Hanya impor transaksi "PO Barang"')
                ->helperText('Jika aktif, baris transaksi non-barang (seperti "PO Jasa Distribusi") akan otomatis dilewati.')
                ->default(true),
        ];
    }

    public function resolveRecord(): ?InvoicingReceipt
    {
        $onlyPoBarang = (bool) ($this->options['only_po_barang'] ?? true);
        $txType = trim((string) ($this->data['transaction_type'] ?? $this->originalData['Transaction Type'] ?? ''));

        if ($onlyPoBarang && $txType !== '' && strcasecmp($txType, 'PO Barang') !== 0) {
            return null;
        }

        $record = app(InvoicingCsvImportService::class)->resolveRecordForImporter(
            mappedData: $this->data,
            originalData: $this->originalData,
        );

        if (! $record) {
            return null;
        }

        if ($record->exists && ! $record->isDirty()) {
            return null;
        }

        return $record;
    }

    public function fillRecord(): void
    {
        // Seluruh atribut dan relasi (DeliveryOrderReceipt, PurchaseOrderIssued, Vendor, GrsRdtvItem, PPN 11%)
        // sudah diisi secara lengkap di dalam resolveRecord() melalui InvoicingCsvImportService.
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data Invoice Barang selesai. '.Number::format($import->successful_rows).' baris berhasil diimpor dan ditautkan ke Penerimaan Barang.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diimpor.';
        }

        return $body;
    }

    public function getJobMiddleware(): array
    {
        return [];
    }
}
