<?php

namespace App\Filament\Resources\DeliveryOrderReceiptDetails;

use App\Filament\Resources\DeliveryOrderReceiptDetails\Pages\ManageDeliveryOrderReceiptDetails;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderIssued;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DeliveryOrderReceiptDetailResource extends Resource
{
    protected static ?string $model = DeliveryOrderReceiptDetail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;

    protected static string|\UnitEnum|null $navigationGroup = 'Penerimaan Receiving';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Detail Penerimaan DO';
    }

    public static function getModelLabel(): string
    {
        return 'Detail Penerimaan DO';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Detail Penerimaan DO';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Material')
                    ->icon(Heroicon::OutlinedCube)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('purchase_order_issued_id')
                                ->label('No. PO')
                                ->relationship('purchaseOrderIssued', 'purchase_order_no')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('item_no')
                                ->label('Item No.')
                                ->numeric()
                                ->required(),
                            TextInput::make('material_code')
                                ->label('Material Code / Stock No.'),
                            Textarea::make('description')
                                ->label('Material Description')
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Data Penerimaan')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('quantity')
                                ->label('Qty Diterima')
                                ->numeric()
                                ->required(),
                            TextInput::make('uoi')
                                ->label('Unit of Issue (UoI)'),
                            Toggle::make('is_qty_tolerance')
                                ->label('Toleransi Qty Aktif?')
                                ->inline(false),
                            TextInput::make('total_amount_snapshot')
                                ->label('Total Nilai (Snapshot)')
                                ->numeric()
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(false),
                            Select::make('delivery_order_receipt_id')
                                ->label('Referensi DO')
                                ->relationship('deliveryOrderReceipt', 'document_code')
                                ->searchable()
                                ->preload(),
                        ]),
                    ]),

                Section::make('Penyimpanan & Spesifikasi')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('location_id')
                                ->label('Lokasi Penyimpanan')
                                ->relationship('locationReceiving', 'name')
                                ->searchable()
                                ->preload(),
                            Toggle::make('is_different_location')
                                ->label('Berbeda Lokasi?')
                                ->inline(false),
                            TextInput::make('mrp_type')
                                ->label('MRP Type'),
                            TextInput::make('material_type')
                                ->label('Material Type'),
                            TextInput::make('abc_indicator')
                                ->label('ABC Indicator'),
                            TextInput::make('aac')
                                ->label('AAC Indicator'),
                        ]),
                    ]),

                Section::make('Log Sistem')
                    ->icon(Heroicon::OutlinedCog)
                    ->schema([
                        TextInput::make('requisitioner')
                            ->label('Requisitioner'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ColumnGroup::make('Informasi Material', [
                    TextColumn::make('purchaseOrderIssued.purchase_order_no')
                        ->label('No. PO & Item')
                        ->description(fn($record) => 'Item No: ' . $record->item_no)
                        ->searchable()
                        ->icon(Heroicon::DocumentText)
                        ->iconColor('primary')
                        ->color('primary')
                        ->sortable(),

                    TextColumn::make('description')
                        ->label('Material')
                        ->description(fn($record) => 'Stock No: ' . (!empty($record->material_code) ? $record->material_code : 'None'))
                        ->searchable(['description', 'material_code'])
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-cube')
                        ->iconColor('gray')
                        ->limit(30),
                ]),

                // ⚖️ GRUP 2: PENERIMAAN & NILAI
                ColumnGroup::make('Data Penerimaan', [
                    TextColumn::make('quantity')
                        ->label('Qty Diterima')
                        ->numeric()
                        ->suffix(fn($record) => " {$record->uoi}")
                        ->badge() // Jadikan badge agar menonjol
                        ->color('success')
                        ->sortable(),

                    TextColumn::make('is_qty_tolerance')
                        ->label('Toleransi')
                        ->badge()
                        ->color(fn($state) => $state ? 'danger' : 'success')
                        ->icon(fn($state) => $state ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                        ->formatStateUsing(function ($state, $record) {
                            // 1. Jika False (Normal), tampilkan teks biasa
                            if (!$state) {
                                return 'Normal';
                            }

                            // 2. Jika True (Toleransi), hitung jumlah kelebihannya
                            $poId = $record->purchase_order_issued_id;
                            $itemNo = $record->item_no;

                            if ($poId && $itemNo) {
                                // Ambil target Qty dari PO
                                $poItem = PurchaseOrderIssued::find($poId);
                                $qtyPo = $poItem ? (float) $poItem->qty_po : 0;

                                // Hitung total Qty yang sudah masuk ke database untuk item ini
                                $totalReceived = DeliveryOrderReceiptDetail::where('purchase_order_issued_id', $poId)
                                    ->where('item_no', $itemNo)
                                    ->sum('quantity');

                                // Kalkulasi selisih (kelebihan)
                                $lebihan = $totalReceived - $qtyPo;

                                // Jika benar-benar berlebih, tampilkan angkanya
                                if ($lebihan > 0) {
                                    // Format angka agar rapi (misal: +1.500 EA)
                                    $fmtLebihan = rtrim(rtrim(number_format($lebihan, 4, ',', '.'), '0'), ',');

                                    return "Toleransi (+{$fmtLebihan} {$record->uoi})";
                                }
                            }

                            return 'Toleransi Aktif';
                        }),

                    TextColumn::make('total_amount_snapshot')
                        ->label('Total Nilai')
                        ->money('IDR', locale: 'id')
                        ->weight(FontWeight::SemiBold)
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(),
                ]),

                // 🏢 GRUP 3: LOKASI & SPESIFIKASI
                ColumnGroup::make('Penyimpanan & Spesifikasi', [
                    TextColumn::make('locationReceiving.name')
                        ->label('Lokasi Penyimpanan')
                        ->icon('heroicon-m-map-pin')
                        ->description(fn($record) => $record->is_different_location ? 'Berbeda Lokasi' : 'Lokasi Utama')
                        ->color(fn($record) => $record->is_different_location ? 'warning' : 'gray')
                        ->searchable()
                        ->sortable(),

                    // Menggabungkan MRP & Material Type agar hemat kolom
                    TextColumn::make('mrp_type')
                        ->label('MRP / Mat. Type')
                        ->description(fn($record) => $record->material_type)
                        ->badge()
                        ->color('info')
                        ->searchable(),

                    // Menggabungkan ABC & AAC Indicator
                    TextColumn::make('abc_indicator')
                        ->label('ABC / AAC Ind.')
                        ->html() // Wajib agar HTML bisa dirender
                        ->getStateUsing(function ($record) {
                            // Ambil data ABC, jika kosong set jadi 'None'
                            $abc = !empty($record->abc_indicator) ? $record->abc_indicator : 'None';

                            // Ambil data AAC, jika kosong set jadi '-'
                            $aac = !empty($record->aac) ? $record->aac : '-';

                            // Rangkai menjadi HTML dengan menambahkan prefix ABC dan AAC
                            return "
                                <div class='font-medium text-gray-300'>ABC: {$abc}</div>
                                <div class='text-sm text-gray-400'>AAC: {$aac}</div>
                            ";
                        })
                        // HAPUS ->badge() dan ->color() karena styling sudah diatur lewat class HTML di atas
                        ->searchable(query: function ($query, string $search) {
                            $query->where('abc_indicator', 'like', "%{$search}%")
                                ->orWhere('aac', 'like', "%{$search}%");
                        }),
                ]),

                // 👤 GRUP 4: SISTEM (Bisa di-hide/toggle oleh user)
                ColumnGroup::make('Log Sistem', [
                    TextColumn::make('requisitioner')
                        ->label('Requisitioner')
                        ->icon('heroicon-m-user-circle')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('created_at')
                        ->label('Tgl Dibuat')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->label('Tgl Diupdate')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                SelectFilter::make('purchase_order_issued_id')
                    ->label('Nomor PO')
                    ->relationship('purchaseOrderIssued', 'purchase_order_no')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('location_id')
                    ->label('Lokasi Penyimpanan')
                    ->relationship('locationReceiving', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_qty_tolerance')
                    ->label('Status Toleransi')
                    ->placeholder('Semua Data')
                    ->trueLabel('Hanya Toleransi')
                    ->falseLabel('Normal (Tanpa Toleransi)'),
                SelectFilter::make('mrp_type')
                    ->label('MRP Type')
                    ->options([
                        'V1' => 'V1',
                        'PD' => 'PD',
                        'INVESTASI' => 'INVESTASI',
                        'NONSTOCK' => 'NONSTOCK',
                        'NOT FOUND' => 'NOT FOUND',
                    ])
                    ->native(false),
                DateRangeFilter::make('created_at')
                    ->label('Tgl Dibuat')
                    ->placeholder('Pilih rentang tanggal'),
            ], layout: FiltersLayout::Dropdown)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->filtersFormColumns(3)
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('info'),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->hasRole('Developer');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDeliveryOrderReceiptDetails::route('/'),
        ];
    }
}
