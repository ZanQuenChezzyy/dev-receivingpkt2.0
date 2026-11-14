<?php

namespace App\Filament\Resources\MonitoringKarungs\Tables;

use App\Models\MonitoringKarung;
use App\Services\SyncMonitoringKarungWithDeliveryOrderService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class MonitoringKarungsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ColumnGroup::make('Informasi PO & Vendor Karung', [
                    TextColumn::make('vendor_name')
                        ->label('Vendor')
                        ->icon('heroicon-m-building-office-2')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('no_po')
                        ->label('Nomor PO')
                        ->icon('heroicon-m-document-duplicate')
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->copyable()
                        ->copyMessage('Nomor PO disalin!')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('qty_po')
                        ->label('Alokasi PO')
                        ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Lbr')
                        ->weight(FontWeight::SemiBold)
                        ->alignEnd()
                        ->sortable(),

                    TextColumn::make('kategori_karung')
                        ->label('Kategori')
                        ->badge()
                        ->color('info')
                        ->placeholder('-'),

                    TextColumn::make('lokasi_pabrik')
                        ->label('Lokasi')
                        ->badge()
                        ->color('gray')
                        ->placeholder('-')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                ColumnGroup::make('Progress Pengujian QC & Legalitas SA SAP', [
                    TextColumn::make('progress_qc_display')
                        ->label('Realisasi QC')
                        ->getStateUsing(function (MonitoringKarung $record): HtmlString {
                            $totalQc = number_format($record->total_qty_qc, 0, ',', '.');
                            $percent = $record->persentase_qc;
                            $tahapCount = $record->qcItems()->count();

                            return new HtmlString("
                                <div class='min-w-[130px]'>
                                    <div class='flex items-center justify-between text-[11px] mb-1 font-semibold text-emerald-700 dark:text-emerald-300'>
                                        <span>{$totalQc} Lbr</span>
                                        <span>{$percent}%</span>
                                    </div>
                                    <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden'>
                                        <div class='bg-emerald-500 h-1.5 rounded-full' style='width: {$percent}%'></div>
                                    </div>
                                    <div class='text-[10px] text-gray-400 mt-0.5'>{$tahapCount} Tahap Pengujian</div>
                                </div>
                            ");
                        }),

                    TextColumn::make('sa_status')
                        ->label('Status SA SAP')
                        ->getStateUsing(function (MonitoringKarung $record): string {
                            $hasPending = $record->has_pending_sa;
                            $count = $record->qcItems()->count();
                            if ($count === 0) {
                                return 'Belum Ada QC';
                            }

                            return $hasPending ? 'Ada Belum SA' : 'Semua SA Terbit';
                        })
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Semua SA Terbit' => 'success',
                            'Ada Belum SA' => 'danger',
                            default => 'gray',
                        })
                        ->icon(fn (string $state): string => match ($state) {
                            'Semua SA Terbit' => 'heroicon-m-check-badge',
                            'Ada Belum SA' => 'heroicon-m-exclamation-triangle',
                            default => 'heroicon-m-clock',
                        }),
                ]),

                ColumnGroup::make('Progress Pengiriman & Penagihan', [
                    TextColumn::make('progress_kirim_display')
                        ->label('Realisasi Kirim')
                        ->getStateUsing(function (MonitoringKarung $record): HtmlString {
                            $totalKirim = number_format($record->total_qty_kirim, 0, ',', '.');
                            $percent = $record->persentase_kirim;
                            $sisa = number_format($record->sisa_qty_po, 0, ',', '.');

                            return new HtmlString("
                                <div class='min-w-[130px]'>
                                    <div class='flex items-center justify-between text-[11px] mb-1 font-semibold text-primary-700 dark:text-primary-300'>
                                        <span>{$totalKirim} Lbr</span>
                                        <span>{$percent}%</span>
                                    </div>
                                    <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden'>
                                        <div class='bg-primary-500 h-1.5 rounded-full' style='width: {$percent}%'></div>
                                    </div>
                                    <div class='text-[10px] text-gray-400 mt-0.5'>Sisa: {$sisa} Lbr</div>
                                </div>
                            ");
                        }),

                    TextColumn::make('linked_dos_count')
                        ->label('DO Terhubung')
                        ->getStateUsing(function (MonitoringKarung $record): string {
                            $count = $record->deliveryItems()->whereNotNull('delivery_order_receipt_id')->count();

                            return $count > 0 ? "{$count} DO Receipt" : 'Belum Ada DO';
                        })
                        ->badge()
                        ->color(fn (string $state): string => str_contains($state, 'DO Receipt') ? 'info' : 'gray')
                        ->icon('heroicon-m-link'),

                    TextColumn::make('latest_do')
                        ->label('DO Terkini')
                        ->getStateUsing(fn (MonitoringKarung $record) => $record->deliveryItems()->latest('id')->value('no_do') ?: '-')
                        ->icon('heroicon-m-truck')
                        ->placeholder('-'),

                    TextColumn::make('latest_invoice')
                        ->label('Invoice Terkini')
                        ->getStateUsing(fn (MonitoringKarung $record) => $record->deliveryItems()->latest('id')->value('no_invoice') ?: '-')
                        ->icon('heroicon-m-receipt-percent')
                        ->placeholder('-'),
                ]),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Selesai' => 'success',
                        'Terkendala' => 'danger',
                        default => 'primary',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('vendor_name')
                    ->label('Filter Vendor')
                    ->options(fn () => MonitoringKarung::select('vendor_name')->distinct()->pluck('vendor_name', 'vendor_name')->toArray())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Selesai' => 'Selesai',
                        'Terkendala' => 'Terkendala',
                    ]),

                SelectFilter::make('kategori_karung')
                    ->label('Kategori Karung')
                    ->options([
                        'Prill' => 'Prill',
                        'Granul' => 'Granul',
                        'NPK' => 'NPK',
                        'Retail' => 'Retail',
                        'Standard' => 'Standard',
                    ]),

                Filter::make('belum_sa')
                    ->label('Hanya Yang Belum SA SAP')
                    ->query(function (Builder $query) {
                        $query->whereHas('qcItems', function (Builder $q) {
                            $q->whereNull('no_sa')->orWhere('no_sa', '')->orWhere('status_qc', 'BLM SA');
                        });
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('syncDo')
                        ->label('Sinkronkan DO Receipt')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (MonitoringKarung $record): void {
                            $service = app(SyncMonitoringKarungWithDeliveryOrderService::class);
                            $result = $service->sync($record);

                            Notification::make()
                                ->title('Sinkronisasi DO Receipt Berhasil')
                                ->body("{$result['linked_do_count']} DO terhubung ({$result['new_deliveries']} tahap baru, {$result['updated_deliveries']} tahap diperbarui)")
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('syncDoReceipts')
                        ->label('Sinkronkan DO Receipt')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function ($records): void {
                            $service = app(SyncMonitoringKarungWithDeliveryOrderService::class);
                            $totalLinked = 0;
                            foreach ($records as $record) {
                                $res = $service->sync($record);
                                $totalLinked += $res['linked_do_count'];
                            }

                            Notification::make()
                                ->title('Sinkronisasi Selesai')
                                ->body("Berhasil menghubungkan {$totalLinked} data Penerimaan DO pada PO terpilih.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
