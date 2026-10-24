<?php

namespace App\Filament\Resources\PicMaterialAssignments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PicMaterialAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aturan Pembagian PIC Material')
                    ->description('Tentukan apakah tipe material ini bebas dikerjakan siapa saja atau khusus untuk user tertentu')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Select::make('material_type')
                            ->label('Tipe Material')
                            ->options([
                                'ZSP' => 'ZSP',
                                'ZFP' => 'ZFP',
                                'ZPM' => 'ZPM',
                                'ZRM' => 'ZRM',
                                'ZSM' => 'ZSM',
                            ])
                            ->unique(ignoreRecord: true)
                            ->required(),

                        ToggleButtons::make('assignment_mode')
                            ->label('Skema Penugasan')
                            ->options([
                                'free_pool' => 'Bebas',
                                'user' => 'PIC Tertentu',
                            ])
                            ->colors([
                                'free_pool' => 'success',
                                'user' => 'warning',
                            ])
                            ->icons([
                                'free_pool' => 'heroicon-m-user-group',
                                'user' => 'heroicon-m-user',
                            ])
                            ->default('free_pool')
                            ->live()
                            ->inline()
                            ->required(),

                        Select::make('users')
                            ->label('Pilih PIC Khusus (Staff Invoice)')
                            ->relationship(
                                name: 'users',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Bisa memilih satu atau lebih staf invoice. Jika lebih dari 1 orang dipilih, dokumen GRS akan dibagi rata secara bergantian (Fair Round-Robin).')
                            ->required(fn (Get $get) => $get('assignment_mode') === 'user')
                            ->visible(fn (Get $get) => $get('assignment_mode') === 'user')
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Catatan Aturan (Opsional)')
                            ->placeholder('Contoh: Material sparepart rutin bebas diambil oleh staff receiving shift manapun.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
