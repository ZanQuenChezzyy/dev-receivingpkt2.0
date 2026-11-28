<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\UserBadgeRenderer;
use App\Support\DeveloperBadgeRenderer;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    protected Width|string|null $maxWidth = Width::SixExtraLarge;

    public function getTitle(): string|Htmlable
    {
        return __('Pengaturan Profil');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Kelola informasi akun, keamanan kata sandi, dan tampilan identitas Anda.');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 12,
                ])
                    ->extraAttributes(['class' => 'items-start gap-6'])
                    ->schema([
                        // Left Column: Live Profile Identity Card
                        Group::make([
                            Section::make()
                                ->extraAttributes(['class' => 'pkt-profile-identity-card overflow-hidden !p-0'])
                                ->schema([
                                    TextEntry::make('profile_identity_card')
                                        ->hiddenLabel()
                                        ->state(function (Get $get): HtmlString {
                                            /** @var ?User $user */
                                            $user = filament()->auth()->user();
                                            $canCustomize = (bool) $user?->canCustomizeAnimatedBadge();
                                            $roleLabel = $user?->primaryAnimatedBadgeRole()
                                                ?? ($user?->roles->first()?->name ?? 'User');
                                            $selected = $canCustomize
                                                ? (string) ($get('developer_badge_style') ?: ($user?->resolvedDeveloperBadgeStyle() ?? User::DEVELOPER_BADGE_ELECTRIC))
                                                : User::DEVELOPER_BADGE_NONE;

                                            $displayName = trim((string) ($get('name') ?: ($user?->name ?? 'Pengguna')));
                                            $displayNpk = trim((string) ($get('npk') ?: ($user?->npk ?? '-')));
                                            $displayEmail = trim((string) ($get('email') ?: ($user?->email ?? '-')));

                                            $avatarUrl = $user?->getFilamentAvatarUrl()
                                                ?? 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&color=FFFFFF&background=F47920';

                                            $ringClass = $canCustomize && $selected !== User::DEVELOPER_BADGE_NONE
                                                ? "pkt-dev-avatar-ring pkt-dev-avatar-ring--{$selected}"
                                                : 'ring-4 ring-white dark:ring-[#151B24] rounded-full shadow-md';

                                            $roleBadgeHtml = $canCustomize
                                                ? DeveloperBadgeRenderer::render($selected, $roleLabel, 'md')
                                                : '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] ring-1 ring-inset ring-[#0054A6]/20 dark:ring-[#83A2DB]/25">'
                                                    .e($roleLabel)
                                                    .'</span>';

                                            $previewUser = $user ? clone $user : null;
                                            if ($previewUser) {
                                                $previewUser->name = $displayName;
                                                $previewUser->npk = $displayNpk !== '-' ? $displayNpk : null;
                                            }
                                            $userPillHtml = $previewUser
                                                ? UserBadgeRenderer::render($previewUser, $roleLabel, $canCustomize ? $selected : User::DEVELOPER_BADGE_NONE)
                                                : '';

                                            $statusBadge = $user?->is_active
                                                ? '<span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Aktif</span>'
                                                : '<span class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400"><span class="w-2 h-2 rounded-full bg-rose-500"></span>Nonaktif</span>';

                                            return new HtmlString(
                                                '<div class="flex flex-col">'
                                                // Top Brand Banner
                                                .'<div class="relative h-24 w-full rounded-t-[20px] bg-gradient-to-r from-[#0054A6] via-[#0A62B8] to-[#F47920] overflow-hidden">'
                                                .'<div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 50%, #ffffff 1px, transparent 1px), radial-gradient(circle at 80% 30%, #ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>'
                                                .'</div>'
                                                // Avatar & Core Info
                                                .'<div class="px-5 pb-5 -mt-11 flex flex-col items-center text-center">'
                                                .'<div class="relative inline-flex items-center justify-center '.$ringClass.'">'
                                                .'<img src="'.e($avatarUrl).'" alt="'.e($displayName).'" class="w-20 h-20 rounded-full object-cover relative z-10 bg-white dark:bg-[#151B24]" />'
                                                .'<span class="absolute bottom-1 right-1 z-20 flex h-3.5 w-3.5 items-center justify-center">'
                                                .'<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>'
                                                .'<span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 ring-2 ring-white dark:ring-[#151B24]"></span>'
                                                .'</span>'
                                                .'</div>'
                                                .'<h2 class="mt-3 text-base font-bold text-[#10141A] dark:text-white tracking-tight leading-snug">'.e($displayName).'</h2>'
                                                .'<p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 break-all">'.e($displayEmail).'</p>'
                                                .'<div class="mt-3">'.$roleBadgeHtml.'</div>'
                                                // Live Table Badge Preview
                                                .'<div class="w-full mt-5 pt-4 border-t border-black/[0.06] dark:border-white/[0.08] flex flex-col items-center gap-2">'
                                                .'<span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Tampilan di Tabel Sistem</span>'
                                                .$userPillHtml
                                                .'</div>'
                                                // Metadata Footer
                                                .'<div class="w-full mt-4 pt-4 border-t border-black/[0.06] dark:border-white/[0.08] grid grid-cols-2 gap-3 text-left">'
                                                .'<div class="p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-black/[0.04] dark:border-white/[0.06]">'
                                                .'<div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">NPK</div>'
                                                .'<div class="mt-0.5 text-xs font-mono font-bold text-slate-800 dark:text-slate-100 truncate">'.e($displayNpk).'</div>'
                                                .'</div>'
                                                .'<div class="p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-black/[0.04] dark:border-white/[0.06]">'
                                                .'<div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</div>'
                                                .'<div class="mt-0.5">'.$statusBadge.'</div>'
                                                .'</div>'
                                                .'</div>'
                                                .'</div>'
                                                .'</div>'
                                            );
                                        }),
                                ]),
                        ])
                            ->columnSpan(['default' => 1, 'lg' => 4]),

                        // Right Column: Spacious Tabbed Settings Panel
                        Group::make([
                            Tabs::make('EditProfile')
                                ->contained()
                                ->tabs([
                                    Tab::make('Informasi Pribadi')
                                        ->icon('heroicon-o-user')
                                        ->schema([
                                            FileUpload::make('avatar_url')
                                                ->label(__('user.avatar'))
                                                ->helperText(__('user.avatar_helper'))
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
                                                ->imageEditorAspectRatioOptions([
                                                    '1:1',
                                                ])
                                                ->disk('public')
                                                ->directory('avatar_upload')
                                                ->visibility('public')
                                                ->alignCenter()
                                                ->columnSpanFull(),

                                            Grid::make(['default' => 1, 'sm' => 2])
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label(__('filament-panels::pages/auth/edit-profile.form.name.label'))
                                                        ->placeholder(__('filament-panels::pages/auth/edit-profile.form.name.placeholder'))
                                                        ->prefixIcon('heroicon-o-user')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->live(onBlur: true)
                                                        ->columnSpanFull(),

                                                    TextInput::make('npk')
                                                        ->label('NPK')
                                                        ->placeholder('Masukkan NPK Anda')
                                                        ->prefixIcon('heroicon-o-identification')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->live(onBlur: true),

                                                    TextInput::make('email')
                                                        ->label(__('filament-panels::pages/auth/edit-profile.form.email.label'))
                                                        ->placeholder(__('filament-panels::pages/auth/edit-profile.form.email.placeholder'))
                                                        ->prefixIcon('heroicon-o-envelope')
                                                        ->email()
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->unique(ignoreRecord: true)
                                                        ->live(onBlur: true),
                                                ]),
                                        ]),

                                    Tab::make('Kata Sandi')
                                        ->icon('heroicon-o-lock-closed')
                                        ->schema([
                                            TextEntry::make('password_notice')
                                                ->hiddenLabel()
                                                ->state(new HtmlString(
                                                    '<div class="flex items-start gap-3 p-3.5 rounded-2xl bg-[#0054A6]/[0.06] dark:bg-[#83A2DB]/[0.08] border border-[#0054A6]/15 dark:border-[#83A2DB]/20 text-xs text-slate-600 dark:text-slate-300">'
                                                    .'<svg class="w-4 h-4 text-[#0054A6] dark:text-[#83A2DB] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                                                    .'<span>Kosongkan kolom kata sandi jika Anda tidak ingin mengubah kata sandi akun saat ini.</span>'
                                                    .'</div>'
                                                )),

                                            Grid::make(['default' => 1, 'sm' => 2])
                                                ->schema([
                                                    TextInput::make('password')
                                                        ->label(__('filament-panels::pages/auth/edit-profile.form.password.label'))
                                                        ->placeholder(__('filament-panels::pages/auth/edit-profile.form.password.placeholder'))
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
                                                        ->label(__('filament-panels::pages/auth/edit-profile.form.password_confirmation.label'))
                                                        ->placeholder(__('filament-panels::pages/auth/edit-profile.form.password_confirmation.placeholder'))
                                                        ->prefixIcon('heroicon-o-shield-check')
                                                        ->password()
                                                        ->revealable(filament()->arePasswordsRevealable())
                                                        ->required()
                                                        ->visible(fn (Get $get): bool => filled($get('password')))
                                                        ->dehydrated(false),
                                                ]),
                                        ]),

                                    Tab::make('Gaya Badge')
                                        ->icon('heroicon-o-bolt')
                                        ->visible(fn (): bool => (bool) filament()->auth()->user()?->canCustomizeAnimatedBadge())
                                        ->schema([
                                            Radio::make('developer_badge_style')
                                                ->label('Pilih Gaya Animasi Badge')
                                                ->helperText('Perubahan gaya langsung terlihat pada kartu identitas di sebelah kiri dan berlaku pada seluruh tabel, cincin avatar, serta menu profil.')
                                                ->default(User::DEVELOPER_BADGE_ELECTRIC)
                                                ->live()
                                                ->columns(['default' => 1, 'sm' => 2])
                                                ->extraAttributes(['class' => 'pkt-profile-radio-grid'])
                                                ->options(function (): array {
                                                    /** @var ?User $user */
                                                    $user = filament()->auth()->user();
                                                    $roleLabel = $user?->primaryAnimatedBadgeRole() ?? 'Developer';

                                                    return [
                                                        User::DEVELOPER_BADGE_ELECTRIC => new HtmlString(
                                                            '<div class="flex flex-col gap-2 py-0.5">'
                                                            .'<div class="flex items-center justify-between gap-2">'
                                                            .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Listrik (Energi Biru PKT)</span>'
                                                            .'</div>'
                                                            .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Garis energi biru-cyan berputar dengan kilatan petir berulang.</span>'
                                                            .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_ELECTRIC, $roleLabel).'</div>'
                                                            .'</div>'
                                                        ),
                                                        User::DEVELOPER_BADGE_FLAME => new HtmlString(
                                                            '<div class="flex flex-col gap-2 py-0.5">'
                                                            .'<div class="flex items-center justify-between gap-2">'
                                                            .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Api (Bara Oranye PKT)</span>'
                                                            .'</div>'
                                                            .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Ikon api berkobar dengan pendaran hangat dan percikan bara naik.</span>'
                                                            .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_FLAME, $roleLabel).'</div>'
                                                            .'</div>'
                                                        ),
                                                        User::DEVELOPER_BADGE_PKT_COMBO => new HtmlString(
                                                            '<div class="flex flex-col gap-2 py-0.5">'
                                                            .'<div class="flex items-center justify-between gap-2">'
                                                            .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Kombinasi PKT (Biru &amp; Oranye)</span>'
                                                            .'</div>'
                                                            .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Cincin gradasi biru-oranye PKT berputar dengan sapuan kilau.</span>'
                                                            .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_PKT_COMBO, $roleLabel).'</div>'
                                                            .'</div>'
                                                        ),
                                                        User::DEVELOPER_BADGE_NONE => new HtmlString(
                                                            '<div class="flex flex-col gap-2 py-0.5">'
                                                            .'<div class="flex items-center justify-between gap-2">'
                                                            .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Standar (Tanpa Animasi)</span>'
                                                            .'</div>'
                                                            .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Tampilan badge statis rapi tanpa efek gerak.</span>'
                                                            .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_NONE, $roleLabel).'</div>'
                                                            .'</div>'
                                                        ),
                                                    ];
                                                })
                                                ->in(array_keys(User::developerBadgeOptions())),
                                        ]),
                                ]),
                        ])
                            ->columnSpan(['default' => 1, 'lg' => 8]),
                    ]),
            ]);
    }

    protected function getRedirectUrl(): ?string
    {
        return filament()->getHomeUrl();
    }
}
