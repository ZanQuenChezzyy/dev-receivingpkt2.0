<?php

namespace App\Filament\Resources\PicMaterialAssignments\Schemas;

use App\Models\PicMaterialAssignment;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PicMaterialAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Aturan PIC Material')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('material_type')
                                ->label('Tipe Material')
                                ->badge()
                                ->color('primary'),
                            TextEntry::make('assignment_mode')
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
                                }),
                            TextEntry::make('users.name')
                                ->label('PIC Utama Ditugaskan')
                                ->badge()
                                ->color('info')
                                ->separator(', ')
                                ->placeholder('Bebas ke Siapapun (Open Pool)'),
                        ]),
                        TextEntry::make('notes')
                            ->label('Catatan Aturan')
                            ->placeholder('-'),
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label('Dibuat Pada')
                                ->dateTime()
                                ->placeholder('-'),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui Pada')
                                ->dateTime()
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Aturan Tambahan / Pengecualian PIC')
                    ->icon('heroicon-o-arrows-pointing-out')
                    ->visible(fn (PicMaterialAssignment $record): bool => $record->assignment_mode === 'user' && ! empty($record->conditional_rules))
                    ->schema([
                        RepeatableEntry::make('conditional_rules')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('criteria_type')
                                        ->label('Kriteria')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'material_code' => 'Kode Material',
                                            'description_contains' => 'Kata Kunci Deskripsi',
                                            default => $state,
                                        })
                                        ->color('info'),
                                    TextEntry::make('target')
                                        ->label('Kondisi / Nilai')
                                        ->state(fn ($component) => ($component->getContainer()->getParentComponent()->getState()['criteria_type'] ?? '') === 'material_code'
                                            ? ($component->getContainer()->getParentComponent()->getState()['material_code'] ?? '-')
                                            : ($component->getContainer()->getParentComponent()->getState()['description_keyword'] ?? '-'))
                                        ->weight('bold'),
                                    TextEntry::make('user_ids')
                                        ->label('Dialihkan ke PIC')
                                        ->badge()
                                        ->color('success')
                                        ->state(function ($state) {
                                            $ids = (array) ($state ?? []);
                                            if (empty($ids)) {
                                                return '-';
                                            }

                                            return User::whereIn('id', $ids)->pluck('name')->join(', ');
                                        }),
                                    TextEntry::make('notes')
                                        ->label('Catatan Aturan')
                                        ->placeholder('-'),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
