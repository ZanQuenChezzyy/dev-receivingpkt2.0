<?php

namespace App\Filament\Exports;

use App\Models\DeliveryOrderReceipt;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class DeliveryOrderReceiptExporter extends Exporter
{
    protected static ?string $model = DeliveryOrderReceipt::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('monitoringNpk.id')->label('ID Monitoring NPK'),
            ExportColumn::make('monitoringChemical.id')->label('ID Monitoring Chemical'),
            ExportColumn::make('delivery_order_no')->label('No. DO'),
            ExportColumn::make('received_date')->label('Tanggal Terima'),
            ExportColumn::make('received_by')->label('Diterima Oleh'),
            ExportColumn::make('created_by')->label('Dibuat Oleh'),
            ExportColumn::make('source_type')->label('Tipe Sumber'),
            ExportColumn::make('stage')->label('Tahap (Stage)'),
            ExportColumn::make('document_code')->label('Kode Dokumen'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('post_103')->label('Post 103'),
            ExportColumn::make('qr_103_code')->label('Kode QR 103'),
            ExportColumn::make('delay_reason')->label('Alasan Terlambat'),
            ExportColumn::make('delay_notes')->label('Catatan Terlambat'),
            ExportColumn::make('pending_date')->label('Tanggal Pending'),
            ExportColumn::make('pending_resolved_date')->label('Tanggal Selesai Pending'),
            ExportColumn::make('document_path')->label('File Dokumen'),
            ExportColumn::make('receipt_mode')->label('Mode Penerimaan'),
            ExportColumn::make('dof_number')->label('No. DOF'),
            ExportColumn::make('dof_date')->label('Tanggal DOF'),
            ExportColumn::make('is_physically_received')->label('Diterima Fisik?'),
            ExportColumn::make('arrival_sequence')->label('Urutan Kedatangan'),
            ExportColumn::make('incoterms')->label('Incoterms'),
            ExportColumn::make('current_location')->label('Lokasi Saat Ini'),
            ExportColumn::make('eta_date')->label('Tanggal ETA'),
            ExportColumn::make('physical_received_date')->label('Tanggal Fisik Diterima'),
            ExportColumn::make('created_at')->label('Dibuat Pada'),
            ExportColumn::make('updated_at')->label('Diperbarui Pada'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your delivery order receipt export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            DateRangePicker::make('created_at_range')
                ->label('Rentang Waktu Penerimaan')
                ->placeholder('Pilih tanggal awal dan akhir (kosongkan untuk mengekspor semua)')
                ->extraAttributes(['class' => 'order-first mb-4']),
        ];
    }

    public function getXlsxCellStyle(): ?Style
    {
        return (new Style)
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(12)
            ->setFontName('Calibri')
            ->setFontColor(Color::rgb(255, 255, 255))
            ->setBackgroundColor(Color::rgb(15, 23, 42)) // Slate 900
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public function configureXlsxWriterBeforeClose(Writer $writer): Writer
    {
        $sheetView = new SheetView;
        $sheetView->setFreezeRow(2); // Membekukan baris pertama (Header) agar tetap terlihat saat di-scroll

        $sheet = $writer->getCurrentSheet();
        $sheet->setSheetView($sheetView);
        $sheet->setName('Export DO');

        return $writer;
    }
}
