<?php

namespace App\Filament\Resources\RequestingUnits;

use App\Filament\Resources\RequestingUnits\Pages\CreateRequestingUnit;
use App\Filament\Resources\RequestingUnits\Pages\EditRequestingUnit;
use App\Filament\Resources\RequestingUnits\Pages\ListRequestingUnits;
use App\Filament\Resources\RequestingUnits\Schemas\RequestingUnitForm;
use App\Filament\Resources\RequestingUnits\Tables\RequestingUnitsTable;
use App\Models\RequestingUnit;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RequestingUnitResource extends Resource
{
    protected static ?string $model = RequestingUnit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-building-office-2';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Monitoring Receiving');
    }

    public static function getNavigationLabel(): string
    {
        return __('Unit Peminta & PIC');
    }

    public static function getModelLabel(): string
    {
        return __('Unit Peminta & PIC');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Unit Peminta & PIC');
    }

    public static function form(Schema $schema): Schema
    {
        return RequestingUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequestingUnitsTable::configure($table);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRequestingUnits::route('/'),
            'create' => CreateRequestingUnit::route('/create'),
            'edit' => EditRequestingUnit::route('/{record}/edit'),
        ];
    }
}
