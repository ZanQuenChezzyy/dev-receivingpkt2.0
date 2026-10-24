<?php

namespace App\Filament\Resources\PicMaterialAssignments;

use App\Filament\Resources\PicMaterialAssignments\Pages\CreatePicMaterialAssignment;
use App\Filament\Resources\PicMaterialAssignments\Pages\EditPicMaterialAssignment;
use App\Filament\Resources\PicMaterialAssignments\Pages\ListPicMaterialAssignments;
use App\Filament\Resources\PicMaterialAssignments\Pages\ViewPicMaterialAssignment;
use App\Filament\Resources\PicMaterialAssignments\Schemas\PicMaterialAssignmentForm;
use App\Filament\Resources\PicMaterialAssignments\Schemas\PicMaterialAssignmentInfolist;
use App\Filament\Resources\PicMaterialAssignments\Tables\PicMaterialAssignmentsTable;
use App\Models\PicMaterialAssignment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PicMaterialAssignmentResource extends Resource
{
    protected static ?string $model = PicMaterialAssignment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-user-group';

    public static function getNavigationLabel(): string
    {
        return 'Aturan PIC Material';
    }

    public static function getModelLabel(): string
    {
        return 'Aturan PIC Material';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Aturan PIC Material';
    }

    public static function form(Schema $schema): Schema
    {
        return PicMaterialAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PicMaterialAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PicMaterialAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPicMaterialAssignments::route('/'),
            'create' => CreatePicMaterialAssignment::route('/create'),
            'view' => ViewPicMaterialAssignment::route('/{record}'),
            'edit' => EditPicMaterialAssignment::route('/{record}/edit'),
        ];
    }
}
