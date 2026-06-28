<?php

namespace App\Filament\Resources\DeliveryOrderReceipts\Pages;

use App\Filament\Exports\DeliveryOrderReceiptExporter;
use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Models\DeliveryOrderReceipt;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListDeliveryOrderReceipts extends ListRecords
{
    protected static string $resource = DeliveryOrderReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(DeliveryOrderReceiptExporter::class)
                ->columnMappingColumns(4)
                ->label('Ekspor Data Penerimaan')
                ->color('gray')
                ->icon(Heroicon::DocumentArrowDown)
                ->modifyQueryUsing(function (Builder $query, ExportAction $action) {
                    $data = $action->getData();
                    if (!empty($data['created_at_range'])) {
                        $dates = explode(' - ', $data['created_at_range']);
                        if (count($dates) === 2) {
                            $startDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    }
                }),
            CreateAction::make()
                ->label('Tambah Penerimaan DO')
                ->icon(Heroicon::PlusCircle),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make('Semua')
                ->icon('heroicon-o-list-bullet'),
            'Belum 103' => Tab::make('Belum 103')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('post_103'))
                ->icon('heroicon-o-document-minus')
                ->badgeColor('info')
                ->badge(DeliveryOrderReceipt::whereNull('post_103')->count()),
            'Pending Dokumen' => Tab::make('Pending Dokumen')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'Pending'))
                ->icon('heroicon-o-clock')
                ->badgeColor('danger')
                ->badge(DeliveryOrderReceipt::where('status', 'Pending')->count()),
            'Menunggu Kedatangan' => Tab::make('Menunggu Kedatangan')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_physically_received', false)->where('receipt_mode', '!=', 'Standard'))
                ->icon('heroicon-o-truck')
                ->badgeColor('warning')
                ->badge(DeliveryOrderReceipt::where('is_physically_received', false)->where('receipt_mode', '!=', 'Standard')->count()),
        ];
    }
}
