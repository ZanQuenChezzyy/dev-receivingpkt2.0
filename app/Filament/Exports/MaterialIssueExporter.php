<?php

namespace App\Filament\Exports;

use App\Models\MaterialIssue;
use App\Models\MaterialIssueDetail;
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
    protected static ?string $model = MaterialIssueDetail::class;

    public static function modifyQuery(Builder $query): Builder
    {
        if ($query->getModel() instanceof MaterialIssue) {
            $materialIssueIdsQuery = (clone $query)->reorder()->select($query->getModel()->getQualifiedKeyName());

            return MaterialIssueDetail::query()
                ->whereIn('material_issue_id', $materialIssueIdsQuery)
                ->with([
                    'materialIssue.purchaseOrderIssued',
                    'materialIssue.deliveryOrderReceipt',
                    'materialIssue.createdBy',
                    'deliveryOrderReceiptDetail.locationReceiving',
                    'deliveryOrderReceiptDetail.warehouseDestination',
                ]);
        }

        return $query->with([
            'materialIssue.purchaseOrderIssued',
            'materialIssue.deliveryOrderReceipt',
            'materialIssue.createdBy',
            'deliveryOrderReceiptDetail.locationReceiving',
            'deliveryOrderReceiptDetail.warehouseDestination',
        ]);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('materialIssue.id')
                ->label('ID MIR'),
            ExportColumn::make('materialIssue.jenis_mir')
                ->label('Jenis MIR'),
            ExportColumn::make('materialIssue.mir_number')
                ->label('No. MIR'),
            ExportColumn::make('materialIssue.tanggal')
                ->label('Tanggal'),
            ExportColumn::make('materialIssue.po_number')
                ->label('Nomor PO Manual'),
            ExportColumn::make('materialIssue.purchaseOrderIssued.purchase_order_no')
                ->label('Nomor PO Digital'),
            ExportColumn::make('materialIssue.deliveryOrderReceipt.delivery_order_no')
                ->label('Nomor DO'),
            ExportColumn::make('deliveryOrderReceiptDetail.item_no')
                ->label('Item No')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->deliveryOrderReceiptDetail?->item_no ?: '-')),
            ExportColumn::make('deliveryOrderReceiptDetail.material_code')
                ->label('Kode Material')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->deliveryOrderReceiptDetail?->material_code ?: '-')),
            ExportColumn::make('deliveryOrderReceiptDetail.description')
                ->label('Deskripsi Material')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->deliveryOrderReceiptDetail?->description ?: '-')),
            ExportColumn::make('deliveryOrderReceiptDetail.uoi')
                ->label('Satuan (UOI)')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->deliveryOrderReceiptDetail?->uoi ?: '-')),
            ExportColumn::make('deliveryOrderReceiptDetail.quantity')
                ->label('Qty Total DO')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->deliveryOrderReceiptDetail?->quantity ?? '-')),
            ExportColumn::make('diminta')
                ->label('Qty Diminta')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->diminta ?? '-')),
            ExportColumn::make('diserahkan')
                ->label('Qty Diambil (Diserahkan)')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->diserahkan ?? '-')),
            ExportColumn::make('boh')
                ->label('Sisa BOH')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->boh ?? '-')),
            ExportColumn::make('location_name')
                ->label('Lokasi')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->deliveryOrderReceiptDetail?->locationReceiving?->name ?: ($record->deliveryOrderReceiptDetail?->warehouseDestination?->name ?: '-'))),
            ExportColumn::make('stage_when_issued')
                ->label('Stage Saat Diambil')
                ->state(fn (MaterialIssueDetail $record): string => (string) ($record->stage_when_issued ?: '-')),
            ExportColumn::make('materialIssue.no_hp')
                ->label('No. HP'),
            ExportColumn::make('materialIssue.no_reservasi')
                ->label('No. Reservasi'),
            ExportColumn::make('materialIssue.departemen')
                ->label('Departemen'),
            ExportColumn::make('materialIssue.bagian')
                ->label('Bagian'),
            ExportColumn::make('materialIssue.no_jor_wo')
                ->label('No. JOR/WO'),
            ExportColumn::make('materialIssue.digunakan_untuk')
                ->label('Digunakan Untuk'),
            ExportColumn::make('materialIssue.no_alat')
                ->label('No. Alat'),
            ExportColumn::make('materialIssue.kode_biaya')
                ->label('Kode Biaya/Cost Center'),
            ExportColumn::make('materialIssue.diminta_oleh')
                ->label('Diminta Oleh'),
            ExportColumn::make('materialIssue.npk')
                ->label('NPK'),
            ExportColumn::make('materialIssue.disetujui_oleh')
                ->label('Disetujui Oleh'),
            ExportColumn::make('materialIssue.disetujui_npk')
                ->label('NPK Disetujui')
                ->enabledByDefault(false),
            ExportColumn::make('materialIssue.diketahui_oleh')
                ->label('Diketahui Oleh'),
            ExportColumn::make('materialIssue.diserahkan_oleh')
                ->label('Diserahkan Oleh'),
            ExportColumn::make('materialIssue.diserahkan_npk')
                ->label('NPK Diserahkan')
                ->enabledByDefault(false),
            ExportColumn::make('materialIssue.diterima_oleh')
                ->label('Diterima Oleh'),
            ExportColumn::make('materialIssue.createdBy.name')
                ->label('Dibuat Oleh'),
            ExportColumn::make('materialIssue.created_at')
                ->label('Tgl Dibuat'),
            ExportColumn::make('materialIssue.updated_at')
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
