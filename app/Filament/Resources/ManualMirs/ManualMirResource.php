<?php

namespace App\Filament\Resources\ManualMirs;

use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Filament\Resources\ManualMirs\Pages\ManageManualMirs;
use App\Models\ManualMir;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ManualMirResource extends Resource
{
    protected static ?string $model = ManualMir::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return 'MIR Manual';
    }

    public static function getPluralModelLabel(): string
    {
        return 'MIR Manual';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    TextInput::make('po_number')
                        ->label('Nomor PO')
                        ->placeholder('Masukkan Nomor PO')
                        ->required(),
                    Select::make('delivery_order_receipt_id')
                        ->label('Referensi Penerimaan DO (Opsional)')
                        ->relationship('deliveryOrderReceipt', 'document_code')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),
                FileUpload::make('image_path')
                    ->label('Gambar/Dokumen MIR')
                    ->image()
                    ->disk('public')
                    ->directory('manual-mirs')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->required(),
                Hidden::make('created_by')
                    ->default(fn() => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('Nomor PO')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('deliveryOrderReceipt.document_code')
                    ->label('Referensi DO')
                    ->icon('heroicon-m-document-duplicate')
                    ->badge()
                    ->color('success')
                    ->placeholder('Tanpa Referensi DO')
                    ->url(fn($record) => $record->delivery_order_receipt_id ? DeliveryOrderReceiptResource::getUrl('view', ['record' => $record->delivery_order_receipt_id]) : null)
                    ->sortable()
                    ->searchable(),

                ImageColumn::make('image_path')
                    ->label('Gambar/Dokumen')
                    ->disk('public')
                    ->alignCenter()
                    ->square()
                    ->extraImgAttributes(['loading' => 'lazy']),

                TextColumn::make('creator.name')
                    ->label('Diunggah Oleh')
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Waktu Unggah')
                    ->dateTime('d M Y, H:i')
                    ->icon('heroicon-m-clock')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('view_document')
                    ->label('Lihat Dokumen')
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->outlined()
                    ->color('info')
                    ->url(fn($record) => Storage::disk('public')->url($record->image_path))
                    ->openUrlInNewTab(),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageManualMirs::route('/'),
        ];
    }
}
