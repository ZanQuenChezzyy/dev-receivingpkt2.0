<?php

namespace App\Filament\Resources\PicMaterialAssignments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PicMaterialAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->label('PIC Khusus Ditugaskan')
                    ->badge()
                    ->color('info')
                    ->separator(', ')
                    ->placeholder('Bebas ke Siapapun (Open Pool)'),
                TextEntry::make('notes')
                    ->label('Catatan Aturan')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
