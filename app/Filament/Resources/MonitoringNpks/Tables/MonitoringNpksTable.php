<?php

namespace App\Filament\Resources\MonitoringNpks\Tables;

use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MonitoringNpksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // 📄 GRUP 1: INFORMASI DOKUMEN
                ColumnGroup::make('Informasi Dokumen', [
                    TextColumn::make('purchaseOrderIssued.purchase_order_no')
                        ->label('Nomor PO')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->copyable()
                        ->copyMessage('Nomor PO disalin!')
                        ->sortable(),

                    TextColumn::make('delivery_oder_number')
                        ->label('Nomor DO')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->color('gray')
                        ->searchable()
                        ->copyable()
                        ->copyMessage('Nomor DO disalin!')
                        ->sortable(),
                ]),

                // ⚙️ GRUP 2: TAHAPAN & KELENGKAPAN
                ColumnGroup::make('Tahapan & Kelengkapan', [
                    IconColumn::make('sample_receivied_date')
                        ->label('Sample')
                        ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                        ->color(fn ($state) => $state ? 'success' : 'gray')
                        ->tooltip(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y') : 'Belum Ada'),

                    IconColumn::make('delivery_oder_delivery_date')
                        ->label('DO Dikirim')
                        ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                        ->color(fn ($state) => $state ? 'success' : 'gray')
                        ->tooltip(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y') : 'Belum Ada'),

                    IconColumn::make('received_date')
                        ->label('Penerimaan')
                        ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                        ->color(fn ($state) => $state ? 'success' : 'gray')
                        ->tooltip(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y') : 'Belum Ada'),

                    IconColumn::make('purchase_order_103_date')
                        ->label('POST 103')
                        ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                        ->color(fn ($state) => $state ? 'success' : 'gray')
                        ->tooltip(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y') : 'Belum Ada'),

                    IconColumn::make('laprima_date')
                        ->label('LAPRIMA')
                        ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                        ->color(fn ($state) => $state ? 'success' : 'gray')
                        ->tooltip(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y') : 'Belum Ada'),

                    IconColumn::make('coa_date')
                        ->label('COA')
                        ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                        ->color(fn ($state) => $state ? 'success' : 'gray')
                        ->tooltip(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y') : 'Belum Ada'),
                ]),

                // 🏁 GRUP 3: STATUS AKHIR
                ColumnGroup::make('Status Akhir', [
                    IconColumn::make('purchase_order_status')
                        ->label('Status PO')
                        ->icon(fn ($record) => ($record->purchase_order_status === 'A' && is_array($record->purchase_order_status_a_files) && count($record->purchase_order_status_a_files) > 0) ||
                            ($record->purchase_order_status === 'B' && filled($record->purchase_order_status_b_date))
                            ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-circle'
                        )
                        ->color(fn ($record) => ($record->purchase_order_status === 'A' && is_array($record->purchase_order_status_a_files) && count($record->purchase_order_status_a_files) > 0) ||
                            ($record->purchase_order_status === 'B' && filled($record->purchase_order_status_b_date))
                            ? 'success' : 'warning'
                        )
                        ->tooltip(fn ($record) => $record->purchase_order_status ?: 'Belum Ada'),

                    TextColumn::make('doc_status')
                        ->label('Status Dokumen')
                        ->badge()
                        ->icon(fn (string $state): string => match (strtolower($state)) {
                            'completed' => 'heroicon-m-check-badge',
                            'outstanding' => 'heroicon-m-clock',
                            default => 'heroicon-m-document',
                        })
                        ->color(fn (string $state): string => match (strtolower($state)) {
                            'completed' => 'success',
                            'outstanding' => 'warning',
                            default => 'gray',
                        }),
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('gray')
                        ->slideOver(),
                    EditAction::make()
                        ->color('info')
                        ->slideOver(),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada Monitoring NPK')
            ->emptyStateDescription('Buat data monitoring NPK baru untuk melacak proses dari PO, DO hingga COA.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
