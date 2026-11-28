<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\DeveloperBadgeRenderer;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('EditProfile')
                    ->tabs([
                        Tab::make('Informasi Pribadi')
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

                                TextInput::make('name')
                                    ->label(__('filament-panels::pages/auth/edit-profile.form.name.label'))
                                    ->placeholder(__('filament-panels::pages/auth/edit-profile.form.name.placeholder'))
                                    ->inlineLabel()
                                    ->required()
                                    ->maxLength(255)
                                    ->autofocus(),

                                TextInput::make('npk')
                                    ->label('NPK')
                                    ->placeholder('Masukkan NPK Anda')
                                    ->inlineLabel()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label(__('filament-panels::pages/auth/edit-profile.form.email.label'))
                                    ->placeholder(__('filament-panels::pages/auth/edit-profile.form.email.placeholder'))
                                    ->inlineLabel()
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                            ]),

                        Tab::make('Kata Sandi')
                            ->schema([
                                TextInput::make('password')
                                    ->label(__('filament-panels::pages/auth/edit-profile.form.password.label'))
                                    ->placeholder(__('filament-panels::pages/auth/edit-profile.form.password.placeholder'))
                                    ->password()
                                    ->revealable(filament()->arePasswordsRevealable())
                                    ->rule(Password::default())
                                    ->autocomplete('new-password')
                                    ->dehydrated(fn ($state): bool => filled($state))
                                    ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                                    ->live(debounce: 500)
                                    ->same('passwordConfirmation'),

                                TextInput::make('passwordConfirmation')
                                    ->label(__('filament-panels::pages/auth/edit-profile.form.password_confirmation.label'))
                                    ->placeholder(__('filament-panels::pages/auth/edit-profile.form.password_confirmation.placeholder'))
                                    ->password()
                                    ->revealable(filament()->arePasswordsRevealable())
                                    ->required()
                                    ->visible(fn (Get $get): bool => filled($get('password')))
                                    ->dehydrated(false),
                            ]),

                        Tab::make('Badge Developer')
                            ->icon('heroicon-o-bolt')
                            ->visible(fn (): bool => (bool) filament()->auth()->user()?->hasRole('Developer'))
                            ->schema([
                                TextEntry::make('developer_badge_preview')
                                    ->label('Pratinjau Langsung')
                                    ->state(function (Get $get): HtmlString {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();
                                        $selected = (string) ($get('developer_badge_style') ?: ($user?->resolvedDeveloperBadgeStyle() ?? User::DEVELOPER_BADGE_ELECTRIC));
                                        $avatarUrl = $user?->getFilamentAvatarUrl()
                                            ?? 'https://ui-avatars.com/api/?name='.urlencode($user?->name ?? 'Developer').'&color=FFFFFF&background=F47920';
                                        $ringClass = $selected !== User::DEVELOPER_BADGE_NONE
                                            ? "pkt-dev-avatar-ring pkt-dev-avatar-ring--{$selected}"
                                            : '';
                                        $badgeHtml = DeveloperBadgeRenderer::render($selected, 'Developer', 'md');

                                        return new HtmlString(
                                            '<div class="flex flex-wrap items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-white/[0.04] border border-black/[0.06] dark:border-white/[0.08]">'
                                            .'<div class="relative inline-flex items-center justify-center '.$ringClass.'">'
                                            .'<img src="'.e($avatarUrl).'" alt="'.e($user?->name ?? 'Developer').'" class="w-12 h-12 rounded-full object-cover relative z-10" />'
                                            .'</div>'
                                            .'<div class="flex flex-col gap-1.5 min-w-0">'
                                            .'<div class="text-sm font-bold text-slate-900 dark:text-white truncate">'.e($user?->name ?? 'Developer').'</div>'
                                            .'<div>'.$badgeHtml.'</div>'
                                            .'</div>'
                                            .'</div>'
                                        );
                                    }),

                                Radio::make('developer_badge_style')
                                    ->label('Pilih Gaya Animasi Badge')
                                    ->helperText('Pilihan ini berlaku pada menu profil topbar, cincin avatar, tabel pengguna, dan halaman detail pengguna.')
                                    ->default(User::DEVELOPER_BADGE_ELECTRIC)
                                    ->live()
                                    ->options([
                                        User::DEVELOPER_BADGE_ELECTRIC => new HtmlString(
                                            '<div class="flex items-center justify-between gap-3 py-0.5">'
                                            .'<div><div class="font-semibold text-sm">Listrik (Energi Biru PKT)</div><div class="text-xs text-slate-500 dark:text-slate-400">Garis energi biru-cyan berputar dengan kilatan petir berulang</div></div>'
                                            .DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_ELECTRIC)
                                            .'</div>'
                                        ),
                                        User::DEVELOPER_BADGE_FLAME => new HtmlString(
                                            '<div class="flex items-center justify-between gap-3 py-0.5">'
                                            .'<div><div class="font-semibold text-sm">Api (Bara Oranye PKT)</div><div class="text-xs text-slate-500 dark:text-slate-400">Ikon api berkobar dengan pendaran hangat dan percikan bara naik</div></div>'
                                            .DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_FLAME)
                                            .'</div>'
                                        ),
                                        User::DEVELOPER_BADGE_PKT_COMBO => new HtmlString(
                                            '<div class="flex items-center justify-between gap-3 py-0.5">'
                                            .'<div><div class="font-semibold text-sm">Kombinasi PKT (Biru &amp; Oranye)</div><div class="text-xs text-slate-500 dark:text-slate-400">Cincin gradasi biru-oranye PKT berputar dengan sapuan kilau</div></div>'
                                            .DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_PKT_COMBO)
                                            .'</div>'
                                        ),
                                        User::DEVELOPER_BADGE_NONE => new HtmlString(
                                            '<div class="flex items-center justify-between gap-3 py-0.5">'
                                            .'<div><div class="font-semibold text-sm">Standar (Tanpa Animasi)</div><div class="text-xs text-slate-500 dark:text-slate-400">Badge statis rapi tanpa efek gerak</div></div>'
                                            .DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_NONE)
                                            .'</div>'
                                        ),
                                    ])
                                    ->in(array_keys(User::developerBadgeOptions())),
                            ]),
                    ]),
            ]);
    }

    protected function getRedirectUrl(): ?string
    {
        return filament()->getHomeUrl();
    }
}
