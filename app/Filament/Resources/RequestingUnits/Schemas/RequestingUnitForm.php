<?php

namespace App\Filament\Resources\RequestingUnits\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RequestingUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Unit Peminta & Pemetaan Requisitioner')
                    ->description('Nama unit baku serta daftar kata kunci requisitioner SAP untuk pemetaan otomatis material Non-Stock dan PD.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Unit Peminta')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->placeholder('Contoh: Rendal TA P5'),
                            TagsInput::make('requisitioner_aliases')
                                ->label('Alias / Kode Requisitioner SAP')
                                ->placeholder('Ketik kode lalu tekan Enter (mis. RENDAL-TAP5)')
                                ->helperText('Dipakai untuk pencocokan otomatis terhadap kolom Requisitioner PO/DO. Alias yang lebih spesifik diprioritaskan.'),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('is_pilot')
                                ->label('Unit Pilot Pareto (Prioritas 80/20)')
                                ->helperText('Tandai jika unit ini termasuk gelombang pilot pengendalian aging.')
                                ->default(false),
                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Kontak PIC Unit (Zona Normal 0–14H & Attention 15–30H)')
                    ->description('Penanggung jawab utama (focal point) di unit peminta untuk menerima notifikasi kedatangan dan reminder tahap 1.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('pic_name')
                                ->label('Nama PIC')
                                ->maxLength(255)
                                ->placeholder('Contoh: Ibnu Sina Al Barry'),
                            TextInput::make('pic_npk')
                                ->label('NPK PIC')
                                ->maxLength(30)
                                ->placeholder('Opsional'),
                            TextInput::make('pic_email')
                                ->label('Email PIC (Zimbra / Perusahaan)')
                                ->email()
                                ->maxLength(255)
                                ->placeholder('nama@pupukkaltim.com'),
                            TextInput::make('pic_phone')
                                ->label('Nomor HP / WhatsApp PIC')
                                ->tel()
                                ->maxLength(40)
                                ->placeholder('08xxxxxxxxxx'),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Eskalasi Level 1 — AVP Unit Peminta (Zona Warning 31–60H)')
                    ->description('Tujuan konfirmasi kendala dan komitmen jadwal pengambilan saat umur material melewati 30 hari.')
                    ->icon('heroicon-o-shield-exclamation')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('avp_name')
                                ->label('Nama AVP')
                                ->maxLength(255),
                            TextInput::make('avp_title')
                                ->label('Jabatan AVP')
                                ->maxLength(255)
                                ->placeholder('Contoh: AVP Rendal TA P5'),
                            TextInput::make('avp_email')
                                ->label('Email AVP')
                                ->email()
                                ->maxLength(255)
                                ->placeholder('avp@pupukkaltim.com'),
                            TextInput::make('avp_phone')
                                ->label('Nomor HP / WhatsApp AVP')
                                ->tel()
                                ->maxLength(40)
                                ->placeholder('08xxxxxxxxxx'),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Eskalasi Level 2 — VP Unit Peminta (Zona Critical > 60H)')
                    ->description('Tujuan eskalasi formal lintas departemen saat umur material melewati 60 hari.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('vp_name')
                                ->label('Nama VP')
                                ->maxLength(255),
                            TextInput::make('vp_title')
                                ->label('Jabatan VP')
                                ->maxLength(255)
                                ->placeholder('Contoh: VP Pemeliharaan'),
                            TextInput::make('vp_email')
                                ->label('Email VP')
                                ->email()
                                ->maxLength(255)
                                ->placeholder('vp@pupukkaltim.com'),
                            TextInput::make('vp_phone')
                                ->label('Nomor HP / WhatsApp VP')
                                ->tel()
                                ->maxLength(40)
                                ->placeholder('08xxxxxxxxxx'),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
