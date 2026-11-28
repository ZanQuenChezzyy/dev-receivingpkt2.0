<?php

namespace App\Filament\Resources\CalendarNotes;

use App\Filament\Resources\CalendarNotes\Pages\ManageCalendarNotes;
use App\Models\CalendarNote;
use App\Models\User;
use App\Services\UserBadgeRenderer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use UnitEnum;

class CalendarNoteResource extends Resource
{
    protected static ?string $model = CalendarNote::class;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-calendar-days';

    public static function getNavigationLabel(): string
    {
        return 'Catatan Kalender';
    }

    public static function getModelLabel(): string
    {
        return 'Catatan Kalender';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Catatan Kalender';
    }

    public static function canAccess(): bool
    {
        /** @var ?User $user */
        $user = Auth::user();

        return CalendarNote::canUserManage($user);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccess();
    }

    /**
     * Komponen form Filament bersama untuk Data Master Catatan Kalender dan modal pada widget Halaman Utama.
     *
     * @return list<Component>
     */
    public static function formComponents(): array
    {
        return [
            Grid::make(['default' => 1, 'sm' => 2])
                ->schema([
                    Select::make('user_id')
                        ->label('Personel Terkait (Opsional)')
                        ->placeholder('Umum / Seluruh Tim')
                        ->options(fn (): array => User::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get(['id', 'name', 'npk'])
                            ->mapWithKeys(fn (User $u): array => [
                                $u->id => $u->name.($u->npk ? " ({$u->npk})" : ''),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->nullable(),

                    Select::make('category')
                        ->label('Kategori')
                        ->options(CalendarNote::categoryOptions())
                        ->default(CalendarNote::CATEGORY_DINAS)
                        ->native(false)
                        ->required(),
                ]),

            TextInput::make('title')
                ->label('Judul Catatan')
                ->placeholder('Contoh: Dinas mutasi bahan baku')
                ->required()
                ->minLength(2)
                ->maxLength(140)
                ->columnSpanFull(),

            Grid::make(['default' => 1, 'sm' => 2])
                ->schema([
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->native(false)
                        ->required()
                        ->live(),

                    DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->native(false)
                        ->required()
                        ->minDate(fn (Get $get) => $get('start_date'))
                        ->afterOrEqual('start_date'),
                ]),

            Textarea::make('notes')
                ->label('Keterangan Tambahan (Opsional)')
                ->placeholder('Lokasi dinas, pengganti sementara, atau keterangan lain...')
                ->rows(3)
                ->maxLength(500)
                ->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::formComponents())
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('start_date')
                    ->label('Rentang Tanggal')
                    ->formatStateUsing(fn (CalendarNote $record): string => $record->formattedDateRange().' '.$record->start_date?->format('Y'))
                    ->description(fn (CalendarNote $record): string => $record->start_date && $record->end_date
                        ? $record->start_date->format('d/m/Y').' – '.$record->end_date->format('d/m/Y')
                        : '-')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CalendarNote::categoryOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        CalendarNote::CATEGORY_DINAS => 'info',
                        CalendarNote::CATEGORY_CUTI => 'warning',
                        CalendarNote::CATEGORY_KEGIATAN => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('user.name')
                    ->label('Personel Terkait')
                    ->state(fn (CalendarNote $record): HtmlString => new HtmlString(
                        $record->user
                            ? UserBadgeRenderer::render($record->user)
                            : '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-300">Umum / Seluruh Tim</span>'
                    ))
                    ->html()
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Judul & Keterangan')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (CalendarNote $record): ?string => $record->notes)
                    ->wrap()
                    ->searchable(['title', 'notes']),

                TextColumn::make('creator.name')
                    ->label('Dicatat Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(CalendarNote::categoryOptions())
                    ->native(false),

                SelectFilter::make('user_id')
                    ->label('Personel')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Ubah Catatan Kalender')
                    ->modalWidth('lg'),
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
            'index' => ManageCalendarNotes::route('/'),
        ];
    }
}
