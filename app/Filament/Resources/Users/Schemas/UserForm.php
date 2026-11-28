<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Services\BadgeMilestoneService;
use App\Support\UserProfileFormSupport;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isEdit = $schema->getOperation() === 'edit' || $schema->getRecord() instanceof User;

        if ($isEdit) {
            return static::configureEditForm($schema);
        }

        return static::configureCreateForm($schema);
    }

    /**
     * Tampilan ubah pengguna bergaya halaman Profil lengkap (Hero Banner, Cover Photo/GIF, Role, Keamanan, dan Gaya Badge).
     */
    protected static function configureEditForm(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(false)
            ->columns(1)
            ->components([
                // 1. Full-Width Modern Hero Profile Card (Cover Photo + Avatar + Identity + Live Table Pill Preview)
                Section::make()
                    ->extraAttributes(['class' => 'pkt-profile-hero-card overflow-hidden !p-0'])
                    ->schema([
                        TextEntry::make('user_profile_hero_banner')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->state(function (?User $record, Get $get): HtmlString {
                                if (! $record) {
                                    return new HtmlString('');
                                }

                                $isEditorDeveloper = (bool) Auth::user()?->hasRole('Developer');

                                return UserProfileFormSupport::renderHeroBanner(
                                    user: $record,
                                    get: $get,
                                    allowAnyStylePreview: $isEditorDeveloper,
                                );
                            }),
                    ]),

                // 2. Full-Width Tabbed Panel (Profil, Role & Keamanan + Gaya Badge)
                Tabs::make('EditUserProfile')
                    ->contained()
                    ->persistTabInQueryString('tab')
                    ->extraAttributes(['class' => 'pkt-profile-tabs'])
                    ->tabs([
                        Tab::make('Profil, Role & Keamanan')
                            ->id('profil-keamanan')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 12])
                                    ->extraAttributes(['class' => 'gap-6 items-start'])
                                    ->schema([
                                        FileUpload::make('avatar_url')
                                            ->label(__('user.avatar'))
                                            ->helperText('Rasio 1:1 (JPG, PNG, atau WEBP).')
                                            ->image()
                                            ->avatar()
                                            ->imageAspectRatio('1:1')
                                            ->automaticallyCropImagesToAspectRatio()
                                            ->automaticallyResizeImagesMode('cover')
                                            ->automaticallyResizeImagesToWidth('1000')
                                            ->automaticallyResizeImagesToHeight('1000')
                                            ->automaticallyUpscaleImagesWhenResizing(false)
                                            ->imageEditor()
                                            ->imageEditorMode(1)
                                            ->imageEditorViewportWidth(1000)
                                            ->imageEditorViewportHeight(1000)
                                            ->imageEditorAspectRatioOptions(['1:1'])
                                            ->disk('public')
                                            ->directory('avatar_upload')
                                            ->visibility('public')
                                            ->live()
                                            ->alignCenter()
                                            ->columnSpan(['default' => 1, 'md' => 4]),

                                        FileUpload::make('cover_photo_url')
                                            ->label('Foto Sampul (Cover Photo / GIF)')
                                            ->helperText('Mendukung JPG, PNG, WEBP, dan GIF bergerak (maks. 10 MB, disarankan lebar 16:5). Untuk GIF bergerak, langsung simpan tanpa memakai tombol crop agar animasi tetap aktif.')
                                            ->acceptedFileTypes([
                                                'image/jpeg',
                                                'image/png',
                                                'image/webp',
                                                'image/gif',
                                            ])
                                            ->maxSize(10240)
                                            ->imageEditor()
                                            ->imageEditorAspectRatioOptions(['16:5', '3:1', '16:9'])
                                            ->imagePreviewHeight('150')
                                            ->disk('public')
                                            ->directory('cover_upload')
                                            ->visibility('public')
                                            ->live()
                                            ->columnSpan(['default' => 1, 'md' => 8]),
                                    ]),

                                Grid::make(['default' => 1, 'md' => 3])
                                    ->extraAttributes(['class' => 'gap-5 pt-5 border-t border-black/[0.06] dark:border-white/[0.08]'])
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('user.name'))
                                            ->placeholder(__('user.name_placeholder'))
                                            ->prefixIcon('heroicon-o-user')
                                            ->required()
                                            ->minLength(3)
                                            ->maxLength(255)
                                            ->live(onBlur: true),

                                        TextInput::make('npk')
                                            ->label('NPK')
                                            ->placeholder('Masukkan NPK')
                                            ->prefixIcon('heroicon-o-identification')
                                            ->maxLength(50)
                                            ->live(onBlur: true),

                                        TextInput::make('email')
                                            ->label(__('user.email'))
                                            ->placeholder(__('user.email_placeholder'))
                                            ->prefixIcon('heroicon-o-envelope')
                                            ->email()
                                            ->required()
                                            ->minLength(3)
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->live(onBlur: true),
                                    ]),

                                Grid::make(['default' => 1, 'md' => 12])
                                    ->extraAttributes(['class' => 'gap-5 pt-5 border-t border-black/[0.06] dark:border-white/[0.08] items-start'])
                                    ->schema([
                                        Select::make('roles')
                                            ->label(__('user.role'))
                                            ->placeholder(__('user.select_role'))
                                            ->relationship('roles', 'name')
                                            ->native(false)
                                            ->preload()
                                            ->multiple()
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->columnSpan(['default' => 1, 'md' => 6]),

                                        Grid::make(['default' => 1, 'sm' => 2])
                                            ->extraAttributes(['class' => 'gap-4'])
                                            ->columnSpan(['default' => 1, 'md' => 6])
                                            ->schema([
                                                Toggle::make('is_active')
                                                    ->label('Akun Aktif')
                                                    ->helperText('Nonaktifkan agar pengguna tidak dapat login.')
                                                    ->default(true)
                                                    ->live()
                                                    ->disabled(fn (?User $record): bool => (int) $record?->id === (int) Auth::id()),

                                                Toggle::make('has_exclusive_badge')
                                                    ->label('Hak Badge Eksklusif')
                                                    ->helperText('Buka penuh 6 badge Eksklusif & 3 badge Tier Tertinggi tanpa syarat misi.')
                                                    ->onColor('warning')
                                                    ->live()
                                                    ->visible(fn (): bool => (bool) Auth::user()?->hasRole('Developer'))
                                                    ->disabled(fn (?User $record): bool => (bool) $record?->hasRole('Developer'))
                                                    ->afterStateHydrated(function (Toggle $component, ?User $record): void {
                                                        $component->state($record ? $record->canCustomizeAnimatedBadge() : false);
                                                    })
                                                    ->dehydrated(fn (?User $record): bool => (bool) Auth::user()?->hasRole('Developer') && ! (bool) $record?->hasRole('Developer')),
                                            ]),
                                    ]),

                                TextEntry::make('password_section_header')
                                    ->hiddenLabel()
                                    ->state(new HtmlString(
                                        '<div class="pt-5 border-t border-black/[0.06] dark:border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-2">'
                                        .'<div class="flex items-center gap-2">'
                                        .'<span class="w-7 h-7 rounded-xl bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#83A2DB] inline-flex items-center justify-center shrink-0">'
                                        .'<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>'
                                        .'</span>'
                                        .'<span class="text-sm font-bold text-[#10141A] dark:text-white">Ubah Kata Sandi</span>'
                                        .'</div>'
                                        .'<span class="text-xs text-slate-500 dark:text-slate-400">Kosongkan jika tidak ingin mengubah kata sandi pengguna ini.</span>'
                                        .'</div>'
                                    )),

                                Grid::make(['default' => 1, 'sm' => 2])
                                    ->extraAttributes(['class' => 'gap-5'])
                                    ->schema([
                                        TextInput::make('password')
                                            ->label(__('user.password_edit'))
                                            ->placeholder(__('user.password_optional'))
                                            ->prefixIcon('heroicon-o-key')
                                            ->password()
                                            ->revealable(filament()->arePasswordsRevealable())
                                            ->rule(Password::default())
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn ($state): bool => filled($state))
                                            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                                            ->live(debounce: 500)
                                            ->same('passwordConfirmation'),

                                        TextInput::make('passwordConfirmation')
                                            ->label(__('user.password_confirm'))
                                            ->placeholder(__('user.password_confirm_placeholder'))
                                            ->prefixIcon('heroicon-o-shield-check')
                                            ->password()
                                            ->revealable(filament()->arePasswordsRevealable())
                                            ->required()
                                            ->visible(fn (Get $get): bool => filled($get('password')))
                                            ->dehydrated(false),
                                    ]),

                                TextEntry::make('user_timestamps_meta')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->state(function (?User $record): HtmlString {
                                        if (! $record) {
                                            return new HtmlString('');
                                        }

                                        $created = $record->created_at?->diffForHumans() ?? '-';
                                        $updated = $record->updated_at?->diffForHumans() ?? '-';

                                        return new HtmlString(
                                            '<div class="pt-4 mt-2 border-t border-black/[0.06] dark:border-white/[0.08] flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-slate-500 dark:text-slate-400">'
                                            .'<span>Dibuat: <strong class="font-semibold text-slate-700 dark:text-slate-200">'.e($created).'</strong></span>'
                                            .'<span>Terakhir diperbarui: <strong class="font-semibold text-slate-700 dark:text-slate-200">'.e($updated).'</strong></span>'
                                            .'</div>'
                                        );
                                    }),
                            ]),

                        Tab::make('Gaya Badge')
                            ->id('gaya-badge')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                TextEntry::make('user_badge_mission_progress')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->state(function (?User $record, Get $get): HtmlString {
                                        if (! $record) {
                                            return new HtmlString('');
                                        }

                                        $fullAccessState = $get('has_exclusive_badge');
                                        $effectiveFullAccess = is_bool($fullAccessState)
                                            ? ($record->hasRole('Developer') || $fullAccessState)
                                            : $record->hasFullExclusiveBadgeAccess();

                                        return UserProfileFormSupport::renderMissionProgressHtml(
                                            user: $record,
                                            allowClaimButtons: false,
                                            hasFullAccessOverride: $effectiveFullAccess,
                                        );
                                    }),

                                Radio::make('developer_badge_style')
                                    ->label('Pilih Gaya Badge Profil & Tabel Pengguna')
                                    ->helperText(function (?User $record): string {
                                        if ((bool) Auth::user()?->hasRole('Developer')) {
                                            return 'Sebagai Developer, Anda dapat langsung memilih tema Classic Clean, 6 badge Eksklusif, atau 3 badge Tier Tertinggi untuk pengguna ini. Perubahan langsung terlihat pada pratinjau di atas.';
                                        }

                                        $unit = $record ? app(BadgeMilestoneService::class)->resolveMissionProfile($record)['unit_label'] : 'Penerimaan DO';

                                        return "Pilih tema Classic Clean atau badge yang sudah terbuka melalui misi kelipatan 5.000 {$unit} / 50.000 {$unit}.";
                                    })
                                    ->default(fn (?User $record): string => $record?->resolvedDeveloperBadgeStyle() ?? User::DEVELOPER_BADGE_NONE)
                                    ->live()
                                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                                    ->extraAttributes(['class' => 'pkt-profile-radio-grid'])
                                    ->disableOptionWhen(function (string $value, ?User $record, Get $get): bool {
                                        if ((bool) Auth::user()?->hasRole('Developer')) {
                                            return false;
                                        }

                                        $fullAccessState = $get('has_exclusive_badge');
                                        if (is_bool($fullAccessState) && $fullAccessState) {
                                            return false;
                                        }

                                        return ! (bool) $record?->canUseBadgeStyle($value);
                                    })
                                    ->options(function (?User $record, Get $get): array {
                                        $isEditorDeveloper = (bool) Auth::user()?->hasRole('Developer');
                                        $fullAccessState = $get('has_exclusive_badge');
                                        $effectiveFullAccess = is_bool($fullAccessState)
                                            ? ((bool) $record?->hasRole('Developer') || $fullAccessState)
                                            : (bool) $record?->hasFullExclusiveBadgeAccess();

                                        return UserProfileFormSupport::badgeStyleRadioOptions(
                                            user: $record,
                                            unlockAllForEditor: $isEditorDeveloper,
                                            hasFullAccessOverride: $effectiveFullAccess,
                                        );
                                    })
                                    ->in(function (?User $record, Get $get): array {
                                        if ((bool) Auth::user()?->hasRole('Developer')) {
                                            return array_keys(User::developerBadgeOptions());
                                        }

                                        $fullAccessState = $get('has_exclusive_badge');
                                        if (is_bool($fullAccessState) && $fullAccessState) {
                                            return array_keys(User::developerBadgeOptions());
                                        }

                                        return array_keys($record?->allowedBadgeOptions() ?? User::classicCleanBadgeOptions());
                                    }),
                            ]),
                    ]),
            ]);
    }

    /**
     * Tampilan tambah pengguna baru.
     */
    protected static function configureCreateForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label(__('user.avatar'))
                                    ->helperText(__('user.avatar_helper'))
                                    ->image()
                                    ->imageAspectRatio('1:1')
                                    ->automaticallyCropImagesToAspectRatio()
                                    ->automaticallyResizeImagesMode('cover')
                                    ->automaticallyResizeImagesToWidth('1000')
                                    ->automaticallyResizeImagesToHeight('1000')
                                    ->automaticallyUpscaleImagesWhenResizing(false)
                                    ->imageEditor()
                                    ->imageEditorMode(1)
                                    ->imageEditorViewportWidth(1000)
                                    ->imageEditorViewportHeight(1000)
                                    ->imageEditorAspectRatioOptions([
                                        '1:1',
                                    ])
                                    ->disk('public')
                                    ->directory('avatar_upload')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                            ]),
                        Section::make()
                            ->schema([
                                Select::make('roles')
                                    ->label(__('user.role'))
                                    ->placeholder(__('user.select_role'))
                                    ->relationship('roles', 'name')
                                    ->native(false)
                                    ->preload()
                                    ->multiple()
                                    ->columnSpanFull()
                                    ->searchable()
                                    ->required(),
                            ]),
                        Section::make('Status Akun')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Akun Aktif')
                                    ->helperText('Jika dinonaktifkan, user tidak dapat login dan tidak muncul di daftar pilihan.')
                                    ->default(true)
                                    ->required(),

                                Toggle::make('has_exclusive_badge')
                                    ->label('Hak Badge Eksklusif')
                                    ->helperText('Izinkan pengguna ini memakai 6 badge Eksklusif dan 3 badge Tier Tertinggi tanpa syarat misi.')
                                    ->onColor('warning')
                                    ->visible(fn (): bool => (bool) Auth::user()?->hasRole('Developer')),
                            ]),
                    ])
                    ->columnSpan([
                        'default' => 3,
                        'sm' => 3,
                        'md' => 3,
                        'lg' => 4,
                        'xl' => 1,
                        '2xl' => 1,
                    ])
                    ->columns(1),

                Section::make(__('user.personal_info'))
                    ->description(__('user.user_information_desc'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('user.name'))
                            ->placeholder(__('user.name_placeholder'))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('npk')
                            ->label('NPK')
                            ->placeholder('Masukkan NPK')
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->maxLength(50),

                        TextInput::make('email')
                            ->label(__('user.email'))
                            ->placeholder(__('user.email_placeholder'))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->email()
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->label(__('user.password'))
                            ->placeholder(__('user.password_placeholder'))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation')
                            ->required(),

                        TextInput::make('passwordConfirmation')
                            ->label(__('user.password_confirm'))
                            ->placeholder(__('user.password_confirm_placeholder'))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->visible(fn (Get $get): bool => filled($get('password')))
                            ->dehydrated(false),

                        FileUpload::make('cover_photo_url')
                            ->label('Foto Sampul (Cover Photo / GIF)')
                            ->helperText('Opsional. Mendukung JPG, PNG, WEBP, dan GIF bergerak (maks. 10 MB).')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                            ])
                            ->maxSize(10240)
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions(['16:5', '3:1', '16:9'])
                            ->imagePreviewHeight('140')
                            ->disk('public')
                            ->directory('cover_upload')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columnSpan([
                        'default' => 3,
                        'sm' => 2,
                        'md' => 3,
                        'lg' => 4,
                        'xl' => 3,
                        '2xl' => 3,
                    ])
                    ->columns(2),
            ])
            ->columns(4);
    }
}
