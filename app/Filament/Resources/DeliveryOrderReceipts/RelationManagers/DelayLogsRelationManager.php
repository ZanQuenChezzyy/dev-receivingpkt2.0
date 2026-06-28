<?php

namespace App\Filament\Resources\DeliveryOrderReceipts\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;

class DelayLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'delayLogs';

    protected static ?string $title = 'Riwayat Penundaan';
    protected static ?string $modelLabel = 'Riwayat Penundaan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('delay_reason')
                    ->label('Alasan')
                    ->columnSpanFull(),
                Textarea::make('delay_notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Riwayat Penundaan')
                    ->description('Informasi lengkap mengenai log penundaan ini.')
                    ->schema([
                        TextEntry::make('delay_reason')
                            ->label('Alasan')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'PO Belum Confirm' => 'warning',
                                'Barang Diambil User Langsung (Tanpa Monitor)' => 'info',
                                'Fisik Kelebihan Kirim (Over-delivery)' => 'danger',
                                'Penundaan Selesai (Clear)' => 'success',
                                default => 'gray',
                            })
                            ->placeholder('-'),
                        TextEntry::make('createdBy.name')
                            ->label('Diubah Oleh')
                            ->icon('heroicon-o-user-circle')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Waktu Perubahan')
                            ->dateTime('d F Y, H:i:s')
                            ->icon('heroicon-o-clock')
                            ->placeholder('-'),
                        TextEntry::make('delay_notes')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('delay_reason')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-o-clock'),
                TextColumn::make('delay_reason')
                    ->label('Alasan')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PO Belum Confirm' => 'warning',
                        'Barang Diambil User Langsung (Tanpa Monitor)' => 'info',
                        'Fisik Kelebihan Kirim (Over-delivery)' => 'danger',
                        'Penundaan Selesai (Clear)' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('delay_notes')
                    ->label('Catatan')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('createdBy.name')
                    ->label('Oleh')
                    ->searchable()
                    ->icon('heroicon-o-user-circle')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                // Read-only logic: logs should only be generated automatically
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
