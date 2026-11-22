<?php

namespace App\Filament\Resources\CashierTransmittals\RelationManagers;

use App\Models\CashierTransmittal;
use App\Models\InvoicingReceipt;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicingReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'invoicingReceipts';

    protected static ?string $title = 'Daftar Invoice dalam Transmittal';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_invoice')
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('vendor_name')
                    ->label('Nama Vendor')
                    ->getStateUsing(fn (InvoicingReceipt $record): ?string => $record->vendor_name ?: ($record->vendor?->name ?: $record->purchaseOrderIssued?->vendor_name))
                    ->placeholder('Vendor Belum Ada')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('pic.name')
                    ->label('PIC')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Belum Ada PIC')
                    ->searchable(),

                TextColumn::make('no_grs')
                    ->label('No. GRS')
                    ->placeholder('No. GRS Belum Ada')
                    ->searchable(),

                TextColumn::make('no_invoice')
                    ->label('No. Invoice')
                    ->weight(FontWeight::SemiBold)
                    ->placeholder('Invoice Belum Ada')
                    ->searchable(),

                TextColumn::make('faktur_pajak')
                    ->label('No. Faktur Pajak')
                    ->placeholder('Faktur Pajak Belum Ada')
                    ->searchable(),

                TextColumn::make('subtotal_exclude_ppn')
                    ->label('Subtotal (Exclude PPN)')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->placeholder('Nilai Belum Ada')
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->alignEnd(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => in_array($state, ['Terkirim Kasir', 'Selesai'], true) ? 'success' : 'info'),
            ])
            ->headerActions([
                Action::make('attachInvoices')
                    ->label('Tambah Invoice ke Batch Ini')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof CashierTransmittal && ! $this->getOwnerRecord()->isLocked())
                    ->modalHeading('Tambah Invoice ke Transmittal Kasir')
                    ->modalDescription('Pilih invoice GRS yang belum masuk ke transmittal kasir manapun untuk ditambahkan ke batch ini.')
                    ->schema([
                        Select::make('receipt_ids')
                            ->label('Pilih Invoice GRS')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (): array {
                                return InvoicingReceipt::query()
                                    ->whereNull('cashier_transmittal_id')
                                    ->where(function ($query): void {
                                        $query->whereNull('jenis')
                                            ->orWhere('jenis', '!=', 'RDTV');
                                    })
                                    ->orderByDesc('id')
                                    ->limit(200)
                                    ->get(['id', 'no_po', 'no_invoice', 'vendor_name'])
                                    ->mapWithKeys(function (InvoicingReceipt $r): array {
                                        $inv = filled($r->no_invoice) ? $r->no_invoice : 'Tanpa No. Inv';
                                        $vendor = filled($r->vendor_name) ? $r->vendor_name : '-';

                                        return [$r->id => "PO {$r->no_po} | {$inv} — {$vendor}"];
                                    })
                                    ->all();
                            }),
                    ])
                    ->action(function (array $data): void {
                        /** @var CashierTransmittal $transmittal */
                        $transmittal = $this->getOwnerRecord();
                        $ids = array_map('intval', (array) ($data['receipt_ids'] ?? []));

                        $records = InvoicingReceipt::query()
                            ->whereIn('id', $ids)
                            ->where(function ($query): void {
                                $query->whereNull('jenis')
                                    ->orWhere('jenis', '!=', 'RDTV');
                            })
                            ->get();

                        foreach ($records as $record) {
                            $record->update([
                                'cashier_transmittal_id' => $transmittal->id,
                                'tanggal_kirim_kasir' => $transmittal->sent_date?->toDateString() ?? now()->toDateString(),
                                'status' => $record->status === 'Selesai' ? 'Selesai' : 'Terkirim Kasir',
                            ]);
                        }

                        Notification::make()
                            ->title('Invoice Ditambahkan')
                            ->body("{$records->count()} invoice berhasil dimasukkan ke {$transmittal->transmittal_no}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('detach')
                    ->label('Keluarkan')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Keluarkan Invoice dari Transmittal?')
                    ->modalDescription('Invoice ini hanya akan dilepas dari nomor transmittal ini (data invoice tetap ada).')
                    ->action(function (InvoicingReceipt $record): void {
                        $record->update([
                            'cashier_transmittal_id' => null,
                        ]);

                        Notification::make()
                            ->title('Invoice Dikeluarkan')
                            ->body("PO {$record->no_po} telah dilepas dari transmittal.")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Belum Ada Invoice di Transmittal Ini')
            ->emptyStateDescription('Tambahkan invoice melalui tombol di atas atau melalui aksi "Kirim ke Kasir" di halaman Invoice Barang.');
    }
}
