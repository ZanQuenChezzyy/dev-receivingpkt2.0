<?php

namespace App\Filament\Resources\MaterialIssues\Tables;

use App\Filament\Exports\MaterialIssueExporter;
use App\Models\MaterialIssue;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class MaterialIssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Informasi Dokumen', [
                    TextColumn::make('jenis_mir')
                        ->label('Jenis')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'digital' => 'primary',
                            'manual' => 'warning',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                    TextColumn::make('mir_number')
                        ->label('No. MIR')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->copyable()
                        ->sortable()
                        ->formatStateUsing(fn($state, $record) => $record->jenis_mir === 'manual' ? 'MIR Manual' : $state),

                    TextColumn::make('tanggal')
                        ->label('Tanggal')
                        ->icon(Heroicon::CalendarDays)
                        ->iconColor('gray')
                        ->date('d F Y')
                        ->sortable()
                        ->formatStateUsing(function ($state, $record) {
                            if ($record->jenis_mir === 'manual') {
                                return $record->created_at?->format('d F Y');
                            }
                            return $state ? \Carbon\Carbon::parse($state)->format('d F Y') : null;
                        }),
                ]),

                ColumnGroup::make('Detail Permintaan', [
                    TextColumn::make('purchase_order_issued_id')
                        ->label('Nomor PO')
                        ->icon('heroicon-m-shopping-cart')
                        ->weight(FontWeight::SemiBold)
                        ->searchable(query: function ($query, $search) {
                            $query->where('po_number', 'like', "%{$search}%")
                                ->orWhereHas('purchaseOrderIssued', function ($q) use ($search) {
                                    $q->where('purchase_order_no', 'like', "%{$search}%");
                                });
                        })
                        ->sortable()
                        ->getStateUsing(fn($record) => $record->jenis_mir === 'manual' ? $record->po_number : $record->purchaseOrderIssued?->purchase_order_no),

                    TextColumn::make('diminta_oleh')
                        ->label('Diminta Oleh')
                        ->icon('heroicon-m-user')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('npk')
                        ->label('NPK')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('departemen')
                        ->label('Departemen')
                        ->icon('heroicon-m-building-office-2')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('bagian')
                        ->label('Bagian')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                ColumnGroup::make('Log Sistem', [
                    TextColumn::make('createdBy.name')
                        ->label('Dibuat Oleh')
                        ->icon(Heroicon::User)
                        ->color('gray')
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
                Action::make('lihat_dokumen')
                    ->label('Lihat Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->button()
                    ->outlined()
                    ->color('warning')
                    ->url(fn(MaterialIssue $record): ?string => ($record->jenis_mir === 'manual' && !empty($record->image_path)) ? Storage::disk('public')->url($record->image_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->jenis_mir === 'manual' && !empty($record->image_path)),

                Action::make('cetak_mir')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->button()
                    ->outlined()
                    ->color('success')
                    ->url(fn(MaterialIssue $record): ?string => $record->jenis_mir === 'digital' ? route('filament.admin.resources.material-issues.print', $record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->jenis_mir === 'digital'),
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
                    BulkAction::make('cetak_mir_bulk')
                        ->label('Cetak MIR Terpilih')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->implode(',');

                            return redirect()->route('filament.admin.resources.material-issues.print_bulk', ['ids' => $ids]);
                        }),
                    ExportBulkAction::make()
                        ->exporter(MaterialIssueExporter::class)
                        ->icon(Heroicon::ArrowUpTray)
                        ->color('gray'),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada Material Issues (MIR)')
            ->emptyStateDescription('Buat catatan pengambilan barang baru melalui form MIR.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('id', 'desc');
    }
}
