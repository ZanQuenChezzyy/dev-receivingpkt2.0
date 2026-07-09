<?php

namespace App\Filament\Exports;

use App\Models\MaterialIssue;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;

class MaterialIssueExporter extends Exporter
{
    protected static ?string $model = MaterialIssue::class;

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
            ExportColumn::make('diketahui_oleh')
                ->label('Diketahui Oleh'),
            ExportColumn::make('diserahkan_oleh')
                ->label('Diserahkan Oleh'),
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
        $body = 'Your material issue export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
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
        return (new Style())
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }
}
