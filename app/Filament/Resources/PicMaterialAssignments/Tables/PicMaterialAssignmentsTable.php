<?php

namespace App\Filament\Resources\PicMaterialAssignments\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PicMaterialAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('material_type')
                    ->label('Tipe Material')
                    ->badge()
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-m-tag')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignment_mode')
                    ->label('Skema Pembagian')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'user' => 'Khusus PIC Tertentu',
                        'free_pool' => 'Bebas ke Siapapun (Pool)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'user' => 'warning',
                        'free_pool' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'user' => 'heroicon-m-user',
                        'free_pool' => 'heroicon-m-user-group',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                TextColumn::make('users.name')
                    ->label('PIC Ditugaskan')
                    ->badge()
                    ->color('info')
                    ->separator(', ')
                    ->placeholder('Bebas ke Siapapun')
                    ->searchable(),

                TextColumn::make('notes')
                    ->label('Catatan Aturan')
                    ->placeholder('-')
                    ->limit(40),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('material_type')
                    ->label('Tipe Material')
                    ->options([
                        'ZSP' => 'ZSP',
                        'ZFP' => 'ZFP',
                        'ZPM' => 'ZPM',
                        'ZRM' => 'ZRM',
                        'ZSM' => 'ZSM',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
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
            ]);
    }
}
