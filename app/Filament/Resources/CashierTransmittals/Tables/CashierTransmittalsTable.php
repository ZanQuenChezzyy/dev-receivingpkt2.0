<?php

namespace App\Filament\Resources\CashierTransmittals\Tables;

use App\Models\CashierTransmittal;
use App\Services\UserBadgeRenderer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CashierTransmittalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['createdBy', 'invoicingReceipts.pic:id,name'])
                ->withCount('invoicingReceipts')
                ->withSum('invoicingReceipts', 'subtotal_exclude_ppn')
                ->orderByDesc('sent_date')
                ->orderByDesc('id')
            )
            ->columns([
                ColumnGroup::make('Informasi Transmittal', [
                    TextColumn::make('transmittal_no')
                        ->label('No. Transmittal')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->copyable()
                        ->copyMessage('No. Transmittal disalin!')
                        ->sortable(),

                    TextColumn::make('sent_date')
                        ->label('Tanggal Kirim')
                        ->icon(Heroicon::CalendarDays)
                        ->iconColor('gray')
                        ->date('d F Y')
                        ->description(fn (CashierTransmittal $record): ?string => $record->sent_date
                            ? $record->sent_date->locale('id')->translatedFormat('l')
                            : null)
                        ->sortable(),

                    TextColumn::make('status')
                        ->label('Status Batch')
                        ->badge()
                        ->color(fn (?string $state): string => $state === CashierTransmittal::STATUS_LOCKED ? 'success' : 'info')
                        ->icon(fn (?string $state): string => $state === CashierTransmittal::STATUS_LOCKED ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open')
                        ->description(fn (CashierTransmittal $record): string => $record->isLocked()
                            ? 'Selesai / batch terkunci'
                            : 'Menampung gabungan PIC di tgl ini')
                        ->sortable(),
                ]),

                ColumnGroup::make('Ringkasan Invoice', [
                    TextColumn::make('invoicing_receipts_count')
                        ->label('Jumlah Invoice')
                        ->badge()
                        ->color('info')
                        ->formatStateUsing(fn ($state): string => ((int) $state).' Invoice')
                        ->alignCenter()
                        ->sortable(),

                    TextColumn::make('invoicing_receipts_sum_subtotal_exclude_ppn')
                        ->label('Total Nilai (Excl. PPN)')
                        ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) ($state ?? 0), 0, ',', '.'))
                        ->weight(FontWeight::Bold)
                        ->color('success')
                        ->alignEnd()
                        ->sortable(),

                    TextColumn::make('involved_pics')
                        ->label('PIC Terlibat')
                        ->getStateUsing(function (CashierTransmittal $record): ?string {
                            $names = $record->invoicingReceipts
                                ->pluck('pic.name')
                                ->filter()
                                ->unique()
                                ->values();

                            return $names->isNotEmpty() ? $names->implode(', ') : null;
                        })
                        ->placeholder('Belum Ada PIC')
                        ->wrap()
                        ->limit(45)
                        ->tooltip(fn (?string $state): ?string => $state),

                    TextColumn::make('destination')
                        ->label('Tujuan')
                        ->icon('heroicon-m-building-office')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                ColumnGroup::make('Log Sistem', [
                    TextColumn::make('createdBy.name')
                        ->label('Dibuat Oleh')
                        ->html()
                        ->formatStateUsing(fn ($state, CashierTransmittal $record) => UserBadgeRenderer::render($record->createdBy))
                        ->searchable(query: fn (Builder $query, string $search): Builder => UserBadgeRenderer::searchQuery($query, 'createdBy', $search))
                        ->sortable(),

                    TextColumn::make('printed_at')
                        ->label('Terakhir Dicetak')
                        ->dateTime('d M Y H:i')
                        ->placeholder('Belum Dicetak')
                        ->sortable(),

                    TextColumn::make('created_at')
                        ->label('Tgl Dibuat')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                Filter::make('sent_date')
                    ->label('Rentang Tanggal Kirim')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal Kirim')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal Kirim')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('Dari: '.Carbon::parse($data['from'])->locale('id')->translatedFormat('d M Y'))
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('Sampai: '.Carbon::parse($data['until'])->locale('id')->translatedFormat('d M Y'))
                                ->removeField('until');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('status')
                    ->label('Status Batch')
                    ->options([
                        CashierTransmittal::STATUS_OPEN => 'Terbuka',
                        CashierTransmittal::STATUS_LOCKED => 'Terkunci',
                    ]),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->button()
                    ->outlined()
                    ->visible(fn (CashierTransmittal $record): bool => (int) ($record->invoicing_receipts_count ?? 0) > 0)
                    ->url(fn (CashierTransmittal $record): string => route('filament.admin.resources.cashier-transmittals.print', [
                        'cashierTransmittal' => $record->transmittal_no,
                    ]))
                    ->openUrlInNewTab(),

                ViewAction::make()
                    ->label('Detail'),

                Action::make('toggleLock')
                    ->label(fn (CashierTransmittal $record): string => $record->isLocked() ? 'Buka' : 'Kunci')
                    ->icon(fn (CashierTransmittal $record): string => $record->isLocked() ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (CashierTransmittal $record): string => $record->isLocked() ? 'warning' : 'gray')
                    ->requiresConfirmation()
                    ->modalHeading(fn (CashierTransmittal $record): string => $record->isLocked() ? 'Buka Kembali Batch?' : 'Kunci Batch Transmittal?')
                    ->modalDescription(fn (CashierTransmittal $record): string => $record->isLocked()
                        ? 'Batch akan kembali menerima gabungan invoice baru pada tanggal yang sama.'
                        : 'Pengiriman baru pada tanggal yang sama setelah ini akan otomatis membuat nomor batch berikutnya.')
                    ->action(function (CashierTransmittal $record): void {
                        $newStatus = $record->isLocked()
                            ? CashierTransmittal::STATUS_OPEN
                            : CashierTransmittal::STATUS_LOCKED;

                        $record->update(['status' => $newStatus]);

                        Notification::make()
                            ->title($newStatus === CashierTransmittal::STATUS_LOCKED ? 'Batch Dikunci' : 'Batch Dibuka Kembali')
                            ->body("Transmittal {$record->transmittal_no} kini berstatus {$newStatus}.")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Hapus')
                    ->modalDescription('Invoice barang tidak akan terhapus — hanya dilepas dari nomor transmittal ini.'),
            ])
            ->emptyStateHeading('Belum Ada Riwayat Transmittal Kasir')
            ->emptyStateDescription('Tandai invoice pada halaman Invoice Barang dengan aksi "Kirim ke Kasir", atau gunakan tombol "Tarik Invoice per Tanggal" di atas.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
