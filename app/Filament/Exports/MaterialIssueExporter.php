<?php

namespace App\Filament\Exports;

use App\Models\MaterialIssue;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class MaterialIssueExporter extends Exporter
{
    protected static ?string $model = MaterialIssue::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'purchaseOrderIssued',
            'deliveryOrderReceipt',
            'createdBy',
            'materialIssueDetails.deliveryOrderReceiptDetail.locationReceiving',
            'materialIssueDetails.deliveryOrderReceiptDetail.warehouseDestination',
        ]);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('jenis_mir')
                ->label('Jenis MIR'),
            ExportColumn::make('mir_number')
                ->label('No. MIR'),
            ExportColumn::make('tanggal')
                ->label('Tanggal'),
            ExportColumn::make('po_number')
                ->label('Nomor PO Manual'),
            ExportColumn::make('purchaseOrderIssued.purchase_order_no')
                ->label('Nomor PO Digital'),
            ExportColumn::make('deliveryOrderReceipt.delivery_order_no')
                ->label('Nomor DO'),
            ExportColumn::make('item_no')
                ->label('Item No')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->deliveryOrderReceiptDetail?->item_no ?: '-')
                        ->implode("\n");
                }),
            ExportColumn::make('material_code')
                ->label('Kode Material')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->deliveryOrderReceiptDetail?->material_code ?: '-')
                        ->implode("\n");
                }),
            ExportColumn::make('description')
                ->label('Deskripsi Material')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->deliveryOrderReceiptDetail?->description ?: '-')
                        ->implode("\n");
                }),
            ExportColumn::make('uoi')
                ->label('Satuan (UOI)')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->deliveryOrderReceiptDetail?->uoi ?: '-')
                        ->implode("\n");
                }),
            ExportColumn::make('qty_total')
                ->label('Qty Total DO')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->deliveryOrderReceiptDetail?->quantity ?? '-')
                        ->implode("\n");
                }),
            ExportColumn::make('qty_diminta')
                ->label('Qty Diminta')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->diminta ?? '-')
                        ->implode("\n");
                }),
            ExportColumn::make('qty_diambil')
                ->label('Qty Diambil (Diserahkan)')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->diserahkan ?? '-')
                        ->implode("\n");
                }),
            ExportColumn::make('boh')
                ->label('Sisa BOH')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->boh ?? '-')
                        ->implode("\n");
                }),
            ExportColumn::make('location_name')
                ->label('Lokasi')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->deliveryOrderReceiptDetail?->locationReceiving?->name ?: ($detail->deliveryOrderReceiptDetail?->warehouseDestination?->name ?: '-'))
                        ->implode("\n");
                }),
            ExportColumn::make('stage_when_issued')
                ->label('Stage Saat Diambil')
                ->state(function (MaterialIssue $record): string {
                    return $record->materialIssueDetails
                        ->map(fn ($detail) => $detail->stage_when_issued ?: '-')
                        ->implode("\n");
                }),
            ExportColumn::make('no_hp')
                ->label('No. HP'),
            ExportColumn::make('no_reservasi')
                ->label('No. Reservasi'),
            ExportColumn::make('departemen')
                ->label('Departemen'),
            ExportColumn::make('bagian')
                ->label('Bagian'),
            ExportColumn::make('no_jor_wo')
                ->label('No. JOR/WO'),
            ExportColumn::make('digunakan_untuk')
                ->label('Digunakan Untuk'),
            ExportColumn::make('no_alat')
                ->label('No. Alat'),
            ExportColumn::make('kode_biaya')
                ->label('Kode Biaya/Cost Center'),
            ExportColumn::make('diminta_oleh')
                ->label('Diminta Oleh'),
            ExportColumn::make('npk')
                ->label('NPK'),
            ExportColumn::make('disetujui_oleh')
                ->label('Disetujui Oleh'),
            ExportColumn::make('disetujui_npk')
                ->label('NPK Disetujui')
                ->enabledByDefault(false),
            ExportColumn::make('diketahui_oleh')
                ->label('Diketahui Oleh'),
            ExportColumn::make('diserahkan_oleh')
                ->label('Diserahkan Oleh'),
            ExportColumn::make('diserahkan_npk')
                ->label('NPK Diserahkan')
                ->enabledByDefault(false),
            ExportColumn::make('diterima_oleh')
                ->label('Diterima Oleh'),
            ExportColumn::make('createdBy.name')
                ->label('Dibuat Oleh'),
            ExportColumn::make('created_at')
                ->label('Tgl Dibuat'),
            ExportColumn::make('updated_at')
                ->label('Tgl Diperbarui'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your material issue export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(12)
            ->setFontName('Arial')
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(34, 197, 94)) // Warna hijau agar fresh
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public function getXlsxCellStyle(): ?Style
    {
        return (new Style)
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setShouldWrapText(true)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public function configureXlsxWriterBeforeClose(Writer $writer): Writer
    {
        $sheetView = new SheetView;
        $sheetView->setFreezeRow(2);

        $sheet = $writer->getCurrentSheet();
        $sheet->setSheetView($sheetView);
        $sheet->setName('Export MIR');

        return $writer;
    }
}
