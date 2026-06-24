<?php

namespace App\Filament\Resources\WarehouseTransmittals\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class WarehouseTransmittalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transmittal_no')
                    ->searchable()
                    ->sortable()
                    ->label('Transmittal No'),
                TextColumn::make('destination.name')
                    ->searchable()
                    ->sortable()
                    ->label('Tujuan Gudang'),
                TextColumn::make('tanggal')
                    ->date()
                    ->sortable()
                    ->label('Tanggal'),
                TextColumn::make('createdBy.name')
                    ->searchable()
                    ->sortable()
                    ->label('Dibuat Oleh'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Cetak Transmittal')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn($record) => route('filament.admin.resources.warehouse-transmittals.print', ['transmittal' => $record->id]))
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
                ]),
            ])
            ->modifyQueryUsing(function ($query) {
                // Jika user bukan super_admin atau Administrator, tampilkan yang ditugaskan kepadanya atau yang dia buat
                if (!auth()->user()->hasRole(['super_admin', 'Administrator'])) {
                    $query->where(function ($q) {
                        $q->whereHas('destination', function ($q2) {
                            $q2->where('pic_id', auth()->id());
                        })->orWhere('created_by', auth()->id());
                    });
                }
            });
    }
}
