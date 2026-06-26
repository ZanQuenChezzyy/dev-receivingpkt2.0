<?php

namespace App\Filament\Resources\Transmittals\Tables;

use App\Filament\Resources\Transmittals\TransmittalResource;
use App\Models\Transmittal;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class TransmittalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Informasi Transmittal', [
                    TextColumn::make('transmittal_no')
                        ->label('Nomor & Tanggal')
                        ->searchable()
                        ->sortable()
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->copyable()
                        ->copyMessage('Nomor disalin')
                        ->description(fn (Transmittal $record): string => $record->created_at ? $record->created_at->format('d M Y, H:i') : '-'),
                ]),

                ColumnGroup::make('Status & Tujuan', [
                    TextColumn::make('type')
                        ->label('Tipe')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'Kirim' => 'primary',
                            'Kembali' => 'warning',
                            default => 'gray',
                        })
                        ->icon(fn ($state) => match ($state) {
                            'Kirim' => 'heroicon-m-paper-airplane',
                            'Kembali' => 'heroicon-m-arrow-uturn-left',
                            default => 'heroicon-m-document',
                        }),

                    TextColumn::make('destination')
                        ->label('Tujuan')
                        ->formatStateUsing(fn ($state, Transmittal $record) => $record->type === 'Kembali' ? 'Receiving' : $state)
                        ->badge()
                        ->color(fn ($state, Transmittal $record) => $record->type === 'Kembali' ? 'warning' : match ($state) {
                            'ISTEK' => 'info',
                            'PPE' => 'success',
                            default => 'gray',
                        })
                        ->icon(fn ($state, Transmittal $record) => $record->type === 'Kembali' ? 'heroicon-m-home-modern' : match ($state) {
                            'ISTEK' => 'heroicon-m-building-office',
                            'PPE' => 'heroicon-m-building-office-2',
                            default => 'heroicon-m-map-pin',
                        }),
                ]),

                ColumnGroup::make('Statistik', [
                    TextColumn::make('total_documents')
                        ->getStateUsing(fn ($record) => $record->transmittalItems()->count())
                        ->label('Total Dokumen')
                        ->badge()
                        ->color('gray')
                        ->formatStateUsing(fn ($state) => new HtmlString("<strong>{$state}</strong> Dokumen"))
                        ->icon('heroicon-m-document-duplicate'),
                ]),

                ColumnGroup::make('Sistem', [
                    TextColumn::make('createdBy.name')
                        ->label('Dibuat Oleh')
                        ->searchable()
                        ->sortable()
                        ->icon('heroicon-m-user-circle')
                        ->color('gray'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Transmittal')
                    ->options([
                        'Kirim' => 'Kirim',
                        'Kembali' => 'Kembali',
                    ]),
                SelectFilter::make('destination')
                    ->label('Tujuan')
                    ->options([
                        'ISTEK' => 'ISTEK',
                        'PPE' => 'PPE',
                    ]),
            ])
            ->recordUrl(null)
            ->recordAction(ViewAction::class)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info'),
                    EditAction::make()
                        ->url(fn (Transmittal $record): string => TransmittalResource::getUrl('bulk-scan', ['id' => $record->id]))
                        ->color('warning')
                        ->icon('heroicon-m-qr-code')
                        ->label('Lanjutkan Scan'),
                    DeleteAction::make()
                        ->color('danger'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
