<?php

namespace App\Filament\Resources\PicMaterialAssignments\Schemas;

use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            ->label('Pilih PIC Utama (Staff Invoice)')
                            ->relationship(
                                name: 'users',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('PIC default untuk tipe material ini jika tidak memenuhi kriteria aturan tambahan di bawah. Jika lebih dari 1 orang dipilih, dokumen GRS akan dibagi rata secara bergantian (Fair Round-Robin).')
                            ->required(fn (Get $get) => $get('assignment_mode') === 'user')
                            ->visible(fn (Get $get) => $get('assignment_mode') === 'user')
                            ->columnSpanFull(),

                        Repeater::make('conditional_rules')
                            ->label('Aturan Tambahan / Pengecualian PIC (Conditional Rules)')
                            ->helperText('Alihkan dokumen ke PIC khusus jika memenuhi kode material tertentu atau kata kunci dalam deskripsi barang. Sistem mengevaluasi aturan pengecualian dari atas ke bawah.')
                            ->itemLabel(function (array $state): ?string {
                                $type = $state['criteria_type'] ?? null;
                                if (! $type) {
                                    return 'Aturan Baru';
                                }
                                $target = $type === 'material_code'
                                    ? ('Kode Material: '.($state['material_code'] ?? '-'))
                                    : ('Deskripsi memuat: "'.($state['description_keyword'] ?? '-').'"');

                                $userIds = (array) ($state['user_ids'] ?? []);
                                if (! empty($userIds)) {
                                    $names = User::whereIn('id', $userIds)->pluck('name')->join(', ');

                                    return "{$target} → PIC: {$names}";
                                }

                                return $target;
                            })
                            ->schema([
                                Select::make('criteria_type')
                                    ->label('Kriteria Pengalihan')
                                    ->options([
                                        'material_code' => 'Berdasarkan Kode Material',
                                        'description_contains' => 'Berdasarkan Kata Kunci Deskripsi',
                                    ])
                                    ->default('material_code')
                                    ->native(false)
                                    ->live()
                                    ->required()
                                    ->columnSpan(['sm' => 12, 'md' => 4]),

                                TextInput::make('material_code')
                                    ->label('Kode Material')
                                    ->placeholder('Contoh: 300001 (atau 300001, 300002)')
                                    ->helperText('Bisa masukkan satu kode atau beberapa kode dipisahkan tanda koma.')
                                    ->visible(fn (Get $get) => ($get('criteria_type') ?? 'material_code') === 'material_code')
                                    ->required(fn (Get $get) => ($get('criteria_type') ?? 'material_code') === 'material_code')
                                    ->columnSpan(['sm' => 12, 'md' => 8]),

                                TextInput::make('description_keyword')
                                    ->label('Kata Kunci Deskripsi')
                                    ->placeholder('Contoh: COAL atau BATUBARA')
                                    ->helperText('Akan dicek jika deskripsi material memuat kata ini (tidak sensitif huruf besar/kecil).')
                                    ->visible(fn (Get $get) => $get('criteria_type') === 'description_contains')
                                    ->required(fn (Get $get) => $get('criteria_type') === 'description_contains')
                                    ->columnSpan(['sm' => 12, 'md' => 8]),

                                Select::make('user_ids')
                                    ->label('Dialihkan ke PIC Khusus (Staff Invoice)')
                                    ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'Staff Invoice'))->pluck('name', 'id'))
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required()
                                    ->helperText('Pilih satu atau lebih PIC. Jika lebih dari 1 orang, penugasan akan dibagi secara bergantian (Fair Round-Robin).')
                                    ->columnSpanFull(),

                                TextInput::make('notes')
                                    ->label('Keterangan / Catatan Aturan (Opsional)')
                                    ->placeholder('Contoh: Khusus pengadaan batubara untuk boiler')
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->collapsible()
                            ->collapsed(false)
                            ->defaultItems(0)
                            ->addActionLabel('+ Tambah Aturan Pengecualian')
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
