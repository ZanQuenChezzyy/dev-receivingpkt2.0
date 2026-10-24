<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VendorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('vendor_number'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('name_3')
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->placeholder('-'),
                TextEntry::make('street')
                    ->placeholder('-'),
                TextEntry::make('street2')
                    ->placeholder('-'),
                TextEntry::make('street3')
                    ->placeholder('-'),
                TextEntry::make('street4')
                    ->placeholder('-'),
                TextEntry::make('telephone_1')
                    ->placeholder('-'),
                TextEntry::make('telephone_2')
                    ->placeholder('-'),
                TextEntry::make('fax_number')
                    ->placeholder('-'),
                TextEntry::make('salesperson')
                    ->placeholder('-'),
                TextEntry::make('telp_salesperson')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
