<?php

namespace App\Filament\Resources\ChemicalQcTuvs;

use App\Filament\Resources\ChemicalQcTuvs\Pages\ManageChemicalQcTuvs;
use App\Models\ChemicalQcTuv;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\RawJs;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ChemicalQcTuvResource extends Resource
{
    protected static ?string $model = ChemicalQcTuv::class;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-shield-check';

    public static function getNavigationLabel(): string
    {
        return 'QC TUV';
    }

    public static function getModelLabel(): string
    {
        return 'QC TUV';
    }

    public static function getPluralModelLabel(): string
    {
        return 'QC TUV';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi QC TUV')
                    ->description('Masukkan detail informasi QC TUV.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('purchase_order_issued_id')
                                ->relationship('purchaseOrderIssued', 'purchase_order_no')
                                ->label('Purchase Order')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Group::make([
                                Select::make('tahapan_name')
                                    ->label('Tahapan')
                                    ->options(collect(range(1, 100))->mapWithKeys(fn($i) => ["TAHAP $i TUV" => "TAHAP $i TUV"]))
                                    ->searchable()
                                    ->required(),
                                TextInput::make('qty_qc_tuv')
                                    ->label('Kuantitas QC TUV')
                                    ->placeholder('Masukkan Kuantitas')
                                    ->numeric()
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(','),
                            ]),
                        ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail QC TUV')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('purchaseOrderIssued.purchase_order_no')
                                ->label('Purchase Order')
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->copyable(),
                            TextEntry::make('tahapan_name')
                                ->label('Tahapan'),
                            TextEntry::make('qty_qc_tuv')
                                ->label('Kuantitas')
                                ->numeric()
                                ->badge()
                                ->color('info'),
                            TextEntry::make('created_at')
                                ->label('Dibuat Pada')
                                ->dateTime()
                                ->placeholder('-'),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui Pada')
                                ->dateTime()
                                ->placeholder('-'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Informasi QC', [
                    TextColumn::make('purchaseOrderIssued.purchase_order_no')
                        ->label('Purchase Order')
                        ->searchable()
                        ->sortable()
                        ->icon('heroicon-m-document-text')
                        ->color('primary')
                        ->weight(FontWeight::Bold),
                    TextColumn::make('tahapan_name')
                        ->label('Tahapan')
                        ->searchable(),
                    TextColumn::make('qty_qc_tuv')
                        ->label('Kuantitas')
                        ->numeric()
                        ->badge()
                        ->color('info')
                        ->sortable(),
                ]),
                ColumnGroup::make('Sistem', [
                    TextColumn::make('created_at')
                        ->label('Dibuat')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('updated_at')
                        ->label('Diperbarui')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageChemicalQcTuvs::route('/'),
        ];
    }
}
