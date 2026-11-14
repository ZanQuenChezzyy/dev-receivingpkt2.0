<?php

namespace App\Filament\Resources\MonitoringKarungs\RelationManagers;

use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliveryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveryItems';

    protected static ?string $title = 'Tahapan Pengiriman & DO';

    protected static string|BackedEnum|null $icon = 'heroicon-m-truck';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->deliveryItems()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'primary';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 3])->schema([
                    TextInput::make('tahap_kirim')
                        ->label('Tahap Pengiriman')
                        ->placeholder('cth: 1, 2, 3')
                        ->required(),

                    TextInput::make('qty_kirim')
                        ->label('Kuantitas Kirim (Lembar)')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->suffix('Lembar'),

                    DatePicker::make('tanggal_penerimaan')
                        ->label('Tanggal Penerimaan Fisik')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                ]),

                Grid::make(['default' => 1, 'sm' => 3])->schema([
                    TextInput::make('no_invoice')
                        ->label('Nomor Invoice')
                        ->placeholder('Faktur tagihan vendor'),

                    TextInput::make('no_do')
                        ->label('Nomor Surat Jalan (DO)')
                        ->placeholder('Delivery Order vendor'),

                    TextInput::make('no_surat')
                        ->label('Nomor Surat Pengantar / Berkas'),
                ]),

                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    TextInput::make('qty_supply')
                        ->label('Qty Realisasi Supply')
                        ->numeric()
                        ->suffix('Lembar')
                        ->placeholder('Jumlah yang disuplai'),

                    TextInput::make('qty_surat')
                        ->label('Qty Pada Surat')
                        ->numeric()
                        ->suffix('Lembar')
                        ->placeholder('Jumlah pada surat jalan'),
                ]),

                Textarea::make('keterangan')
                    ->label('Catatan Pengiriman')
                    ->placeholder('Catatan surat jalan atau gudang.')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tahap_kirim')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('tahap_kirim')
                    ->label('Tahap')
                    ->badge()
                    ->color('primary')
                    ->weight(FontWeight::Bold),

                TextColumn::make('qty_kirim')
                    ->label('Qty Kirim')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Lbr')
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->summarize(
                        Sum::make()
                            ->label('Total Kirim')
                            ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Lbr')
                    ),

                TextColumn::make('no_invoice')
                    ->label('No. Invoice')
                    ->icon('heroicon-m-receipt-percent')
                    ->iconColor('success')
                    ->copyable()
                    ->copyMessage('No. Invoice disalin')
                    ->placeholder('-'),

                TextColumn::make('no_do')
                    ->label('No. DO (Surat Jalan)')
                    ->icon('heroicon-m-truck')
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('No. DO disalin')
                    ->placeholder('-'),

                TextColumn::make('no_surat')
                    ->label('No. Surat')
                    ->placeholder('-'),

                TextColumn::make('qty_supply')
                    ->label('Qty Supply')
                    ->formatStateUsing(fn ($state) => $state ? number_format((int) $state, 0, ',', '.').' Lbr' : '-')
                    ->alignEnd()
                    ->summarize(
                        Sum::make()
                            ->label('Total Supply')
                            ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Lbr')
                    ),

                TextColumn::make('qty_surat')
                    ->label('Qty Surat')
                    ->formatStateUsing(fn ($state) => $state ? number_format((int) $state, 0, ',', '.').' Lbr' : '-')
                    ->alignEnd(),

                TextColumn::make('tanggal_penerimaan')
                    ->label('Tgl Terima Fisik')
                    ->date('d/m/Y')
                    ->icon('heroicon-m-calendar')
                    ->placeholder('-'),

                TextColumn::make('keterangan')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_invoice')
                    ->label('Status Penagihan Invoice')
                    ->options([
                        'with_invoice' => 'Sudah Ada Invoice',
                        'without_invoice' => 'Belum Ada Invoice',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'with_invoice' => $query->whereNotNull('no_invoice')->where('no_invoice', '!=', ''),
                            'without_invoice' => $query->where(fn ($q) => $q->whereNull('no_invoice')->orWhere('no_invoice', '')),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('+ Tambah Pengiriman Fisik')
                    ->modalHeading('Tambah Tahap Pengiriman Fisik & DO')
                    ->modalWidth('3xl'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Ubah Data Pengiriman Fisik')
                    ->modalWidth('3xl'),

                DeleteAction::make(),
            ]);
    }
}
