<?php

namespace App\Filament\Resources\MonitoringChemicals\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class MonitoringChemicalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // 📄 GRUP 1: INFORMASI DOKUMEN
                ColumnGroup::make('Informasi Dokumen', [
                    TextColumn::make('do_number')
                        ->label('Nomor DO')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->copyable()
                        ->copyMessage('Nomor DO disalin!')
                        ->sortable(),

                    TextColumn::make('material_category')
                        ->label('Kategori Material')
                        ->icon('heroicon-m-tag')
                        ->iconColor('info')
                        ->color('info')
                        ->badge()
                        ->searchable(),

                    TextColumn::make('received_date')
                        ->label('Tanggal Terima')
                        ->icon(Heroicon::CalendarDays)
                        ->iconColor('gray')
                        ->date('d F Y')
                        ->description(fn ($record) => $record->received_date ? Carbon::parse($record->received_date)->translatedFormat('l') : '-')
                        ->sortable(),
                ]),

                // ⚙️ GRUP 2: STATUS & QC
                ColumnGroup::make('Status & Pemeriksaan', [
                    TextColumn::make('doc_status')
                        ->label('Status Dokumen')
                        ->badge()
                        ->color(fn (?string $state): string => match (strtolower($state ?? '')) {
                            'approved', 'selesai', 'diterima', 'completed' => 'success',
                            'pending', 'proses' => 'warning',
                            'rejected', 'ditolak' => 'danger',
                            default => 'gray',
                        })
                        ->icon(fn (?string $state): string => match (strtolower($state ?? '')) {
                            'approved', 'selesai', 'diterima', 'completed' => 'heroicon-m-check-badge',
                            'pending', 'proses' => 'heroicon-m-clock',
                            'rejected', 'ditolak' => 'heroicon-m-x-circle',
                            default => 'heroicon-m-document',
                        })
                        ->searchable(),

                    TextColumn::make('qc_by')
                        ->label('Diperiksa Oleh (QC)')
                        ->icon('heroicon-m-shield-check')
                        ->color(fn ($state) => $state ? 'success' : 'warning')
                        ->formatStateUsing(fn ($state) => $state ?? 'Belum Diperiksa')
                        ->badge()
                        ->searchable(),
                ]),

                // 👤 GRUP 3: LOG SISTEM & PERSONIL
                ColumnGroup::make('Log Sistem & Personil', [
                    TextColumn::make('receivedBy.name')
                        ->label('Penerima')
                        ->icon(Heroicon::User)
                        ->iconColor('warning')
                        ->color('warning')
                        ->badge()
                        ->searchable(),

                    TextColumn::make('createdBy.name')
                        ->label('Dibuat Oleh')
                        ->icon('heroicon-m-computer-desktop')
                        ->color('gray')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('created_at')
                        ->label('Tgl Dibuat')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->label('Tgl Diperbarui')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('gray')
                        ->slideOver(),
                    EditAction::make()
                        ->color('info')
                        ->slideOver(),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada Monitoring Chemicals')
            ->emptyStateDescription('Buat data monitoring bahan kimia baru untuk melacak penerimaan dan Quality Control.')
            ->emptyStateIcon('heroicon-o-beaker');
    }
}
