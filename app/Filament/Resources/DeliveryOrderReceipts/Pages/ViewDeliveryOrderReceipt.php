<?php

namespace App\Filament\Resources\DeliveryOrderReceipts\Pages;

use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Filament\Resources\DeliveryOrderReceipts\Tables\DeliveryOrderReceiptsTable;
use App\Models\DeliveryOrderReceipt;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Schema;

class ViewDeliveryOrderReceipt extends ViewRecord
{
    protected static string $resource = DeliveryOrderReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cek_softcopy_ajukan_ulang')
                ->label('Cek Softcopy & Ajukan Ulang')
                ->icon('heroicon-m-document-arrow-up')
                ->color('warning')
                ->visible(function (): bool {
                    /** @var DeliveryOrderReceipt $record */
                    $record = $this->record;

                    return Schema::hasTable('rdtv_compliances')
                        && $record->rdtvCompliances()->where('status', 'Menunggu Review')->exists();
                })
                ->modalHeading(fn (): string => "Softcopy Sertifikat Susulan — DO: {$this->record->delivery_order_no}")
                ->modalDescription('Vendor telah mengunggah softcopy sertifikat pemenuhan melalui portal Lacak PO. Periksa berkas di bawah untuk diajukan ulang ke proses GRS.')
                ->modalSubmitActionLabel('Simpan & Tindak Lanjuti')
                ->schema(fn (): array => DeliveryOrderReceiptsTable::buildRdtvComplianceModalSchema($this->record))
                ->action(fn (array $data) => DeliveryOrderReceiptsTable::handleRdtvComplianceReview($this->record, $data)),
            EditAction::make(),
        ];
    }
}
