<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Vendor')
                    ->description('Data identitas resmi vendor PT Pupuk Kaltim')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Vendor')
                            ->placeholder('Contoh: PT. Sumber Makmur Perkasa')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('vendor_number')
                            ->label('Vendor Number (SAP)')
                            ->placeholder('Contoh: 10005432')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Alamat Kantor')
                    ->description('Detail domisili perusahaan (City & Street 1-4)')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('city')
                            ->label('City (Kota)')
                            ->placeholder('Bontang / Balikpapan / Jakarta'),
                        TextInput::make('street')
                            ->label('Street (Alamat Utama)')
                            ->placeholder('Jalan utama perusahaan'),
                        TextInput::make('street2')
                            ->label('Street 2')
                            ->placeholder('Alamat baris 2'),
                        TextInput::make('street3')
                            ->label('Street 3')
                            ->placeholder('Alamat baris 3'),
                        TextInput::make('street4')
                            ->label('Street 4')
                            ->placeholder('Alamat baris 4'),
                    ])->columns(2),

                Section::make('Kontak & Salesperson')
                    ->description('Kontak kantor dan perwakilan penjualan (Salesperson)')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('telephone_1')
                            ->label('Telephone 1')
                            ->tel()
                            ->placeholder('0548-xxxxx'),
                        TextInput::make('telephone_2')
                            ->label('Telephone 2')
                            ->tel()
                            ->placeholder('0811-xxxxx'),
                        TextInput::make('fax_number')
                            ->label('Fax Number')
                            ->placeholder('0548-xxxxx'),
                        TextInput::make('salesperson')
                            ->label('Salesperson')
                            ->placeholder('Nama sales representative'),
                        TextInput::make('telp_salesperson')
                            ->label('Telp. Salesperson')
                            ->tel()
                            ->placeholder('0812-xxxxx'),
                    ])->columns(2),

                Section::make('Email Vendor')
                    ->description('Alamat email resmi vendor (dapat memiliki lebih dari 1 email)')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        TagsInput::make('email')
                            ->label('Email')
                            ->placeholder('Ketik email lalu tekan Enter...')
                            ->nestedRecursiveRules(['email'])
                            ->helperText('Vendor dapat memiliki lebih dari 1 email. Masukkan alamat email lalu tekan Enter.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
