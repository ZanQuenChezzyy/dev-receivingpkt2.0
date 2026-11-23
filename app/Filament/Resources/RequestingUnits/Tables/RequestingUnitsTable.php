<?php

namespace App\Filament\Resources\RequestingUnits\Tables;

use App\Enums\AgingOperationalStatus;
use App\Models\RequestingUnit;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RequestingUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->withCount([
                    'nonStockAgingControls as active_outstanding_count' => function (Builder $sub): void {
                        $sub->whereIn('operational_status', [
                            AgingOperationalStatus::BelumDiambil->value,
                            AgingOperationalStatus::ScheduledHold->value,
                        ]);
                    },
                ]);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Unit Peminta')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (RequestingUnit $record): string => $record->is_pilot ? 'Unit Prioritas' : 'Unit Reguler'),
                TextColumn::make('requisitioner_aliases')
                    ->label('Alias Requisitioner')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('Belum ada alias'),
                TextColumn::make('pic_name')
                    ->label('PIC Unit')
                    ->searchable()
                    ->placeholder('Belum diisi')
                    ->description(fn (RequestingUnit $record): string => collect([$record->pic_email, $record->pic_phone])->filter()->implode(' • ')),
                TextColumn::make('avp_name')
                    ->label('AVP (Warning 31–60H)')
                    ->searchable()
                    ->placeholder('Belum diisi')
                    ->description(fn (RequestingUnit $record): string => collect([$record->avp_title, $record->avp_email])->filter()->implode(' • ')),
                TextColumn::make('vp_name')
                    ->label('VP (Critical >60H)')
                    ->searchable()
                    ->placeholder('Belum diisi')
                    ->description(fn (RequestingUnit $record): string => collect([$record->vp_title, $record->vp_email])->filter()->implode(' • '))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('active_outstanding_count')
                    ->label('Item Outstanding')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success'),
                IconColumn::make('is_pilot')
                    ->label('Pilot')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('is_pilot', 'desc')
            ->filters([
                TernaryFilter::make('is_pilot')
                    ->label('Unit Prioritas'),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
