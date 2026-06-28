<?php

namespace App\Filament\Resources\Transmittals\Pages;

use App\Filament\Resources\Transmittals\TransmittalResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListTransmittals extends ListRecords
{
    protected static string $resource = TransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulk_scan')
                ->label('Scan Transmittal QC')
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->url(fn(): string => TransmittalResource::getUrl('bulk-scan')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make()
                ->icon(Heroicon::ListBullet),
            'Kirim' => Tab::make()
                ->icon(Heroicon::PaperAirplane)
                ->modifyQueryUsing(fn($query) => $query->where('type', 'Kirim')),
            'Kembali' => Tab::make()
                ->icon(Heroicon::ArrowUturnLeft)
                ->modifyQueryUsing(fn($query) => $query->where('type', 'Kembali')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'Kirim';
    }
}
