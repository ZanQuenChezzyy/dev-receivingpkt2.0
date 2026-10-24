<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama Vendor')
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
                        TextInput::make('email')
                            ->label('Email Resmi')
                            ->placeholder('vendor@perusahaan.com')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_3')
                            ->label('Name 3')
                            ->placeholder('Informasi tambahan nama vendor')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Kontak & Salesperson')
                    ->description('Kontak kantor dan perwakilan penjualan (Salesperson)')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('telephone_1')
                            ->label('Telepon 1')
                            ->tel()
                            ->placeholder('0548-xxxxx'),
                        TextInput::make('telephone_2')
                            ->label('Telepon 2')
                            ->tel()
                            ->placeholder('0811-xxxxx'),
                        TextInput::make('fax_number')
                            ->label('Nomor Fax')
                            ->placeholder('0548-xxxxx'),
                        TextInput::make('salesperson')
                            ->label('Nama Salesperson')
                            ->placeholder('Nama perwakilan vendor'),
                        TextInput::make('telp_salesperson')
                            ->label('Telp. Salesperson')
                            ->tel()
                            ->placeholder('0812-xxxxx'),
                    ])->columns(2),

                Section::make('Alamat Kantor')
                    ->description('Detail domisili perusahaan')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('city')
                            ->label('Kota')
                            ->placeholder('Bontang / Balikpapan / Jakarta'),
                        TextInput::make('street')
                            ->label('Alamat Street 1')
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
            ]);
    }
}
