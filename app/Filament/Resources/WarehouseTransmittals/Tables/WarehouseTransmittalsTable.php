<?php

namespace App\Filament\Resources\WarehouseTransmittals\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Indicator;

class WarehouseTransmittalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // 📄 GRUP 1: INFORMASI TRANSMITTAL
                ColumnGroup::make('Informasi Transmittal', [
                    TextColumn::make('transmittal_no')
                        ->label('Transmittal No')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->copyable()
                        ->copyMessage('Transmittal No disalin!')
                        ->sortable(),

                    TextColumn::make('tanggal')
                        ->label('Tanggal')
                        ->icon(Heroicon::CalendarDays)
                        ->iconColor('gray')
                        ->date('d F Y')
                        ->description(fn ($record) => Carbon::parse($record->tanggal)->translatedFormat('l'))
                        ->sortable(),
                ]),

                // 🎯 GRUP 2: TUJUAN PENGIRIMAN
                ColumnGroup::make('Tujuan Pengiriman', [
                    TextColumn::make('destination.name')
                        ->label('Tujuan Gudang')
                        ->icon('heroicon-m-map-pin')
                        ->badge()
                        ->color('info')
                        ->searchable()
                        ->sortable(),
                ]),

                // 👤 GRUP 3: LOG SISTEM
                ColumnGroup::make('Log Sistem', [
                    TextColumn::make('createdBy.name')
                        ->label('Dibuat Oleh')
                        ->icon(Heroicon::User)
                        ->iconColor('gray')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('created_at')
                        ->label('Tgl Dibuat')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->label('Tgl Diperbarui')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal GRS'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal GRS'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('Dari: ' . Carbon::parse($data['from'])->toFormattedDateString())
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('Sampai: ' . Carbon::parse($data['until'])->toFormattedDateString())
                                ->removeField('until');
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Cetak Transmittal')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->button()
                    ->outlined()
                    ->url(fn ($record) => route('filament.admin.resources.warehouse-transmittals.print', ['transmittal' => $record->transmittal_no]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('print_bulk')
                        ->label('Cetak Transmittal Terpilih')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();

                            return redirect()->route('filament.admin.resources.warehouse-transmittals.print_bulk', ['transmittals' => $ids]);
                        }),
                ])
                    ->label('Cetak Dipilih')
                    ->icon(Heroicon::Printer),
            ])
            ->emptyStateHeading('Belum ada Transmittal Gudang')
            ->emptyStateDescription('Buat data transmittal gudang baru dengan melakukan generate melalui halaman Daftar Pengiriman Gudang.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->modifyQueryUsing(function ($query) {
                // Jika user bukan super_admin atau Administrator, tampilkan yang ditugaskan kepadanya atau yang dia buat
                if (! Auth::user()->hasRole(['super_admin', 'Administrator'])) {
                    $query->where(function ($q) {
                        $q->whereHas('destination', function ($q2) {
                            $q2->where('pic_id', Auth::id());
                        })->orWhere('created_by', Auth::id());
                    });
                }
            });
    }
}
