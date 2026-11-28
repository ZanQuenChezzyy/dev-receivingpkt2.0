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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    protected Width|string|null $maxWidth = Width::FiveExtraLarge;

    public function getTitle(): string|Htmlable
    {
        return __('Pengaturan Profil');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Kelola foto sampul, informasi akun, kata sandi, dan tampilan identitas Anda.');
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(false)
            ->model($this->getUser())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(false)
            ->schema([
                // 1. Full-Width Modern Hero Profile Card (Cover Photo + Avatar + Identity + Live Table Pill Preview)
                Section::make()
                    ->extraAttributes(['class' => 'pkt-profile-hero-card overflow-hidden !p-0'])
                    ->schema([
                        TextEntry::make('profile_hero_banner')
                            ->hiddenLabel()
                            ->columnSpanFull()
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
                                $coverUrl = $user?->getCoverPhotoUrl();

                                $ringClass = $canCustomize && $selected !== User::DEVELOPER_BADGE_NONE
                                    ? "pkt-dev-avatar-ring pkt-dev-avatar-ring--{$selected}"
                                    : 'ring-4 ring-white dark:ring-[#151B24] rounded-full shadow-lg';

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
                                    ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/12 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Aktif</span>'
                                    : '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-500/12 text-rose-600 dark:text-rose-400 border border-rose-500/25"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Nonaktif</span>';

                                $coverHtml = $coverUrl
                                    ? '<img src="'.e($coverUrl).'" alt="Foto Sampul" class="w-full h-full object-cover" />'
                                        .'<div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-slate-950/15 to-transparent"></div>'
                                    : '<div class="w-full h-full bg-gradient-to-r from-[#0054A6] via-[#0B67C2] to-[#F47920]"></div>'
                                        .'<div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 50%, #ffffff 1px, transparent 1px), radial-gradient(circle at 80% 30%, #ffffff 1px, transparent 1px); background-size: 22px 22px;"></div>'
                                        .'<div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-transparent"></div>';

                                return new HtmlString(
                                    '<div class="w-full flex flex-col">'
                                    // Edge-to-Edge Cover Photo Banner
                                    .'<div class="relative w-full h-36 sm:h-48 overflow-hidden bg-slate-900">'
                                    .$coverHtml
                                    .'</div>'
                                    // Profile Identity Strip (Generous Vertical Breathing Room, Avatar Overlaps Cleanly)
                                    .'<div class="px-5 sm:px-7 pt-4 pb-6 flex flex-col md:flex-row md:items-center justify-between gap-5">'
                                    .'<div class="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-5 min-w-0">'
                                    // Outer relative wrapper so online indicator is NEVER clipped by animated avatar ring
                                    .'<div class="relative shrink-0 -mt-14 sm:-mt-16 z-10">'
                                    .'<div class="relative inline-flex items-center justify-center '.$ringClass.' shadow-xl">'
                                    .'<img src="'.e($avatarUrl).'" alt="'.e($displayName).'" class="w-24 h-24 rounded-full object-cover relative z-10 bg-white dark:bg-[#151B24]" />'
                                    .'</div>'
                                    .'<span class="absolute bottom-2 right-2 z-20 flex h-3.5 w-3.5 items-center justify-center">'
                                    .'<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>'
                                    .'<span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 ring-2 ring-white dark:ring-[#151B24]"></span>'
                                    .'</span>'
                                    .'</div>'
                                    // User Name, Role Badge, NPK, Email, Status
                                    .'<div class="flex flex-col items-center sm:items-start text-center sm:text-left min-w-0 gap-1.5">'
                                    .'<div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">'
                                    .'<h2 class="text-lg sm:text-xl font-bold text-[#10141A] dark:text-white tracking-tight leading-tight">'.e($displayName).'</h2>'
                                    .$roleBadgeHtml
                                    .'</div>'
                                    .'<div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-2.5 gap-y-1 text-xs text-slate-500 dark:text-slate-400">'
                                    .'<span class="font-mono font-semibold text-slate-700 dark:text-slate-300">NPK '.e($displayNpk).'</span>'
                                    .'<span class="text-slate-300 dark:text-slate-600">&bull;</span>'
                                    .'<span class="break-all">'.e($displayEmail).'</span>'
                                    .'<span class="text-slate-300 dark:text-slate-600">&bull;</span>'
                                    .$statusBadge
                                    .'</div>'
                                    .'</div>'
                                    .'</div>'
                                    // Framed Live Table Pill Preview (Right Side)
                                    .'<div class="flex flex-col items-center md:items-end gap-1.5 shrink-0 px-3.5 py-2.5 rounded-2xl bg-slate-100/80 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07]">'
                                    .'<span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tampilan di Tabel Sistem</span>'
                                    .$userPillHtml
                                    .'</div>'
                                    .'</div>'
                                    .'</div>'
                                );
                            }),
                    ]),

                // 2. Clean Full-Width Tabbed Settings Panel (No Inline-Label Gaps)
                Tabs::make('EditProfile')
                    ->contained()
                    ->extraAttributes(['class' => 'pkt-profile-tabs'])
                    ->tabs([
                        Tab::make('Informasi Pribadi')
                            ->icon('heroicon-o-user')
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
                                            ->alignCenter()
                                            ->columnSpan(['default' => 1, 'md' => 4]),

                                        FileUpload::make('cover_photo_url')
                                            ->label('Foto Sampul (Cover Photo)')
                                            ->helperText('Disarankan gambar lebar (rasio 16:5 atau 3:1). Kosongkan untuk memakai latar bawaan PKT.')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatioOptions(['16:5', '3:1', '16:9'])
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('16:5')
                                            ->imageResizeTargetWidth('1600')
                                            ->imageResizeTargetHeight('500')
                                            ->imagePreviewHeight('150')
                                            ->disk('public')
                                            ->directory('cover_upload')
                                            ->visibility('public')
                                            ->columnSpan(['default' => 1, 'md' => 8]),
                                    ]),

                                Grid::make(['default' => 1, 'md' => 3])
                                    ->extraAttributes(['class' => 'gap-5 pt-4 border-t border-black/[0.06] dark:border-white/[0.08]'])
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('filament-panels::pages/auth/edit-profile.form.name.label'))
                                            ->placeholder(__('filament-panels::pages/auth/edit-profile.form.name.placeholder'))
                                            ->prefixIcon('heroicon-o-user')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true),

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
                                    ->extraAttributes(['class' => 'gap-5'])
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
                                    ->helperText('Perubahan gaya langsung terlihat pada kartu profil di atas dan berlaku pada seluruh tabel, cincin avatar, serta menu profil.')
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
                                                .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Sparkling Lightning</span>'
                                                .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Arus energi biru-cyan berputar dengan kilatan petir berulang.</span>'
                                                .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_ELECTRIC, $roleLabel).'</div>'
                                                .'</div>'
                                            ),
                                            User::DEVELOPER_BADGE_FLAME => new HtmlString(
                                                '<div class="flex flex-col gap-2 py-0.5">'
                                                .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Blazing Ember</span>'
                                                .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Bara api oranye menyala dengan percikan cahaya yang naik berulang.</span>'
                                                .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_FLAME, $roleLabel).'</div>'
                                                .'</div>'
                                            ),
                                            User::DEVELOPER_BADGE_PKT_COMBO => new HtmlString(
                                                '<div class="flex flex-col gap-2 py-0.5">'
                                                .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Solar Aurora</span>'
                                                .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Cincin gradasi biru dan oranye berputar dengan sapuan kilau.</span>'
                                                .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_PKT_COMBO, $roleLabel).'</div>'
                                                .'</div>'
                                            ),
                                            User::DEVELOPER_BADGE_NONE => new HtmlString(
                                                '<div class="flex flex-col gap-2 py-0.5">'
                                                .'<span class="font-bold text-sm text-[#10141A] dark:text-white">Classic Clean</span>'
                                                .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Tampilan statis sederhana tanpa efek gerak.</span>'
                                                .'<div class="pt-1">'.DeveloperBadgeRenderer::render(User::DEVELOPER_BADGE_NONE, $roleLabel).'</div>'
                                                .'</div>'
                                            ),
                                        ];
                                    })
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
