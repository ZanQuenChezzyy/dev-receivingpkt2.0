<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\BadgeMilestoneService;
use App\Services\UserBadgeRenderer;
use App\Support\DeveloperBadgeRenderer;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditProfile extends BaseEditProfile
{
    protected Width|string|null $maxWidth = Width::FiveExtraLarge;

    public function mount(): void
    {
        parent::mount();

        /** @var ?User $user */
        $user = filament()->auth()->user();
        if ($user) {
            app(BadgeMilestoneService::class)->syncMilestonesAndNotify($user);
        }
    }

    public function claimBadgeGift(string $badgeStyle): void
    {
        /** @var ?User $user */
        $user = filament()->auth()->user();
        if (! $user) {
            return;
        }

        $claimed = app(BadgeMilestoneService::class)->claimGiftBadge($user, $badgeStyle);

        if (! $claimed) {
            Notification::make()
                ->title('Hadiah belum dapat diklaim')
                ->body('Selesaikan target misi penerimaan DO terlebih dahulu atau hadiah tersebut sudah pernah diklaim.')
                ->warning()
                ->send();

            return;
        }

        $this->data['developer_badge_style'] = $badgeStyle;
        $badgeName = User::exclusiveAnimatedBadgeOptions()[$badgeStyle] ?? $badgeStyle;

        Notification::make()
            ->title('Hadiah Badge Berhasil Diambil')
            ->body("Badge eksklusif {$badgeName} kini terbuka dan langsung aktif pada profil Anda.")
            ->success()
            ->send();
    }

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
                                $roleLabel = $user?->primaryAnimatedBadgeRole()
                                    ?? ($user?->roles->first()?->name ?? 'User');
                                $rawSelected = (string) ($get('developer_badge_style') ?: ($user?->resolvedDeveloperBadgeStyle() ?? User::DEVELOPER_BADGE_NONE));
                                $selected = ($user && $user->canUseBadgeStyle($rawSelected))
                                    ? $rawSelected
                                    : ($user?->resolvedDeveloperBadgeStyle() ?? User::DEVELOPER_BADGE_NONE);

                                $isExclusiveSelected = User::isExclusiveBadgeStyle($selected);

                                $displayName = trim((string) ($get('name') ?: ($user?->name ?? 'Pengguna')));
                                $displayNpk = trim((string) ($get('npk') ?: ($user?->npk ?? '-')));
                                $displayEmail = trim((string) ($get('email') ?: ($user?->email ?? '-')));

                                $avatarUrl = $user?->getFilamentAvatarUrl()
                                    ?? 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&color=FFFFFF&background=F47920';
                                $coverUrl = $this->resolvePreviewCoverUrl($get('cover_photo_url'), $user);

                                $ringClass = $isExclusiveSelected
                                    ? "pkt-dev-avatar-ring pkt-dev-avatar-ring--{$selected}"
                                    : 'ring-4 ring-white dark:ring-[#151B24] rounded-full shadow-lg';

                                $roleBadgeHtml = DeveloperBadgeRenderer::render($selected, $roleLabel, 'md');

                                $previewUser = $user ? clone $user : null;
                                if ($previewUser) {
                                    $previewUser->name = $displayName;
                                    $previewUser->npk = $displayNpk !== '-' ? $displayNpk : null;
                                }
                                $userPillHtml = $previewUser
                                    ? UserBadgeRenderer::render($previewUser, $roleLabel, $selected, clickable: false)
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

                // 2. Clean Full-Width Tabbed Settings Panel (Profil & Keamanan in 1 View + Gaya Badge)
                Tabs::make('EditProfile')
                    ->contained()
                    ->persistTabInQueryString('tab')
                    ->extraAttributes(['class' => 'pkt-profile-tabs'])
                    ->tabs([
                        Tab::make('Profil & Keamanan')
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
                                        .'<span class="text-xs text-slate-500 dark:text-slate-400">Kosongkan jika tidak ingin mengubah kata sandi saat ini.</span>'
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
                            ->id('gaya-badge')
                            ->icon('heroicon-o-swatch')
                            ->badge(function (): ?string {
                                /** @var ?User $user */
                                $user = filament()->auth()->user();
                                if (! $user || $user->hasFullExclusiveBadgeAccess()) {
                                    return null;
                                }

                                $progress = app(BadgeMilestoneService::class)->getProgress($user);

                                return $progress['has_any_pending_gift'] ? 'Hadiah' : null;
                            })
                            ->badgeColor('warning')
                            ->schema([
                                TextEntry::make('badge_mission_progress')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->state(function (): HtmlString {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();
                                        if (! $user) {
                                            return new HtmlString('');
                                        }

                                        return $this->renderMissionProgressHtml($user);
                                    }),

                                Radio::make('developer_badge_style')
                                    ->label('Pilih Gaya Badge Profil & Tabel')
                                    ->helperText(function (): string {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();

                                        return $user?->hasFullExclusiveBadgeAccess()
                                            ? 'Anda memiliki hak eksklusif penuh untuk seluruh tema Classic Clean dan animasi eksklusif. Perubahan langsung terlihat pada pratinjau di atas.'
                                            : 'Pilih tema warna Classic Clean kapan saja. Badge eksklusif bertanda gembok dapat dibuka dengan menyelesaikan misi penerimaan DO (1.000, 5.000, dan 50.000 DO) atau melalui hak eksklusif dari Developer.';
                                    })
                                    ->default(function (): string {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();

                                        return $user?->resolvedDeveloperBadgeStyle() ?? User::DEVELOPER_BADGE_NONE;
                                    })
                                    ->live()
                                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                                    ->extraAttributes(['class' => 'pkt-profile-radio-grid'])
                                    ->disableOptionWhen(function (string $value): bool {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();

                                        return ! (bool) $user?->canUseBadgeStyle($value);
                                    })
                                    ->options(function (): array {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();
                                        $hasFullAccess = (bool) $user?->hasFullExclusiveBadgeAccess();
                                        $roleLabel = $user?->primaryAnimatedBadgeRole()
                                            ?? ($user?->roles->first()?->name ?? 'User');

                                        $lockIcon = '<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
                                            .'<path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />'
                                            .'</svg>';

                                        $classicDefinitions = [
                                            User::DEVELOPER_BADGE_NONE => [
                                                'title' => 'Classic Amber',
                                                'desc' => 'Nuansa oranye-amber hangat bawaan sistem.',
                                            ],
                                            User::CLASSIC_BADGE_BLUE => [
                                                'title' => 'Classic Cobalt',
                                                'desc' => 'Biru korporat PKT yang tegas dan rapi.',
                                            ],
                                            User::CLASSIC_BADGE_EMERALD => [
                                                'title' => 'Classic Emerald',
                                                'desc' => 'Hijau zamrud lembut yang sejuk di mata.',
                                            ],
                                            User::CLASSIC_BADGE_VIOLET => [
                                                'title' => 'Classic Violet',
                                                'desc' => 'Aksen ungu-indigo modern untuk tampilan bersih.',
                                            ],
                                            User::CLASSIC_BADGE_ROSE => [
                                                'title' => 'Classic Rose',
                                                'desc' => 'Sentuhan koral-merah muda yang halus.',
                                            ],
                                            User::CLASSIC_BADGE_SLATE => [
                                                'title' => 'Classic Slate',
                                                'desc' => 'Minimalis monokrom abu-abu netral.',
                                            ],
                                        ];

                                        $exclusiveDefinitions = [
                                            User::DEVELOPER_BADGE_ELECTRIC => [
                                                'title' => 'Sparkling Lightning',
                                                'desc' => 'Sengatan busur petir biru-cyan menyambar dengan percikan listrik.',
                                                'tier_chip' => 'Eksklusif',
                                                'unlock_hint' => 'Misi 1.000 / 5.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_FLAME => [
                                                'title' => 'Blazing Ember',
                                                'desc' => 'Lidah api oranye berkobar dengan percikan bara naik berulang.',
                                                'tier_chip' => 'Eksklusif',
                                                'unlock_hint' => 'Misi 1.000 / 5.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_PKT_COMBO => [
                                                'title' => 'Solar Aurora',
                                                'desc' => 'Aliran aurora biru & oranye PKT dengan sapuan cahaya dan kilau bintang.',
                                                'tier_chip' => 'Tier Tertinggi',
                                                'unlock_hint' => 'Misi 50.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_OBSIDIAN_GOLD => [
                                                'title' => 'Royal Obsidian',
                                                'desc' => 'Gradasi perunggu & emas 24K cair dengan kilatan berlian empat sudut.',
                                                'tier_chip' => 'Tier Tertinggi',
                                                'unlock_hint' => 'Misi 50.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_ARCTIC_PLATINUM => [
                                                'title' => 'Arctic Platinum',
                                                'desc' => 'Gradasi biru gletser & perak beku dengan taburan kristal salju turun.',
                                                'tier_chip' => 'Tier Tertinggi',
                                                'unlock_hint' => 'Misi 50.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_EMERALD_SOVEREIGN => [
                                                'title' => 'Emerald Sovereign',
                                                'desc' => 'Gradasi zamrud & giok teal dengan gelombang pulsa perisai mengembang.',
                                                'tier_chip' => 'Tier Tertinggi',
                                                'unlock_hint' => 'Misi 50.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_CRIMSON_ECLIPSE => [
                                                'title' => 'Crimson Eclipse',
                                                'desc' => 'Gradasi merah rubi & koral gerhana dengan efek glitch dan distorsi kromatik.',
                                                'tier_chip' => 'Tier Tertinggi',
                                                'unlock_hint' => 'Misi 50.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_AMETHYST_CROWN => [
                                                'title' => 'Amethyst Crown',
                                                'desc' => 'Gradasi indigo & ungu ametis dengan distorsi gelombang warp dan kilau kristal.',
                                                'tier_chip' => 'Tier Tertinggi',
                                                'unlock_hint' => 'Misi 50.000 DO atau hak eksklusif Developer',
                                            ],
                                            User::DEVELOPER_BADGE_ROSE_TITANIUM => [
                                                'title' => 'Rose Titanium',
                                                'desc' => 'Gradasi rose gold & sampanye dengan gelombang sutra dan kelopak kilau melayang.',
                                                'tier_chip' => 'Tier Tertinggi',
                                                'unlock_hint' => 'Misi 50.000 DO atau hak eksklusif Developer',
                                            ],
                                        ];

                                        $options = [];

                                        foreach ($classicDefinitions as $key => $meta) {
                                            $options[$key] = new HtmlString(
                                                '<div class="flex flex-col gap-2 py-0.5">'
                                                .'<div class="flex items-center justify-between gap-2">'
                                                .'<span class="font-bold text-sm text-[#10141A] dark:text-white">'.e($meta['title']).'</span>'
                                                .'<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200/70 dark:bg-white/[0.08] text-slate-600 dark:text-slate-300">Classic Clean</span>'
                                                .'</div>'
                                                .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">'.e($meta['desc']).'</span>'
                                                .'<div class="pt-1">'.DeveloperBadgeRenderer::render($key, $roleLabel).'</div>'
                                                .'</div>'
                                            );
                                        }

                                        foreach ($exclusiveDefinitions as $key => $meta) {
                                            $isUnlocked = (bool) $user?->canUseBadgeStyle($key);
                                            $isMythic = User::isMythicBadgeStyle($key);

                                            if ($isUnlocked) {
                                                $chipColor = match ($key) {
                                                    User::DEVELOPER_BADGE_OBSIDIAN_GOLD => 'bg-gradient-to-r from-[#111827] via-[#92400E] to-[#D97706] text-amber-100 shadow-xs',
                                                    User::DEVELOPER_BADGE_ARCTIC_PLATINUM => 'bg-gradient-to-r from-[#0C2D57] via-[#0284C7] to-[#94A3B8] text-white shadow-xs',
                                                    User::DEVELOPER_BADGE_EMERALD_SOVEREIGN => 'bg-gradient-to-r from-[#042F2E] via-[#059669] to-[#34D399] text-emerald-50 shadow-xs',
                                                    User::DEVELOPER_BADGE_CRIMSON_ECLIPSE => 'bg-gradient-to-r from-[#1A080C] via-[#991B1B] to-[#E11D48] text-rose-50 shadow-xs',
                                                    User::DEVELOPER_BADGE_AMETHYST_CROWN => 'bg-gradient-to-r from-[#1E1038] via-[#7E22CE] to-[#C084FC] text-purple-50 shadow-xs',
                                                    User::DEVELOPER_BADGE_ROSE_TITANIUM => 'bg-gradient-to-r from-[#26161C] via-[#BE185D] to-[#F9A8D4] text-pink-50 shadow-xs',
                                                    User::DEVELOPER_BADGE_PKT_COMBO => 'bg-gradient-to-r from-[#0054A6] via-[#0B67C2] to-[#F47920] text-white shadow-xs',
                                                    default => 'bg-[#F47920]/15 text-[#F47920] dark:text-orange-300',
                                                };
                                                $chipText = $hasFullAccess ? $meta['tier_chip'] : ($isMythic ? 'Tier Tertinggi • Terbuka' : 'Terbuka via Misi');
                                                $statusChip = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold '.$chipColor.'">'.$chipText.'</span>';
                                                $lockedNote = '';
                                            } else {
                                                $statusChip = '<span class="pkt-badge-locked-chip inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200/90 dark:bg-white/[0.1] text-slate-600 dark:text-slate-300" title="'.e($meta['unlock_hint']).'">'.$lockIcon.'Terkunci</span>';
                                                $lockedNote = '<span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">'.$lockIcon.e($meta['unlock_hint']).'</span>';
                                            }

                                            $previewLabel = $isUnlocked ? $roleLabel : ($isMythic ? 'Tier Tertinggi' : 'Eksklusif');

                                            $options[$key] = new HtmlString(
                                                '<div class="flex flex-col gap-2 py-0.5">'
                                                .'<div class="flex items-center justify-between gap-2">'
                                                .'<span class="font-bold text-sm text-[#10141A] dark:text-white">'.e($meta['title']).'</span>'
                                                .$statusChip
                                                .'</div>'
                                                .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">'.e($meta['desc']).'</span>'
                                                .'<div class="pt-1 flex items-center gap-2">'.DeveloperBadgeRenderer::render($key, $previewLabel).'</div>'
                                                .$lockedNote
                                                .'</div>'
                                            );
                                        }

                                        return $options;
                                    })
                                    ->in(function (): array {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();

                                        return array_keys($user?->allowedBadgeOptions() ?? User::classicCleanBadgeOptions());
                                    }),
                            ]),
                    ]),
            ]);
    }

    protected function renderMissionProgressHtml(User $user): HtmlString
    {
        $progress = app(BadgeMilestoneService::class)->getProgress($user);

        if ($progress['has_full_access']) {
            return new HtmlString(
                '<div class="rounded-2xl px-4 py-3 bg-[#0054A6]/8 dark:bg-[#83A2DB]/10 border border-[#0054A6]/20 dark:border-[#83A2DB]/20 flex flex-wrap items-center justify-between gap-2">'
                .'<div class="flex items-center gap-2.5">'
                .'<span class="w-8 h-8 rounded-xl bg-[#F47920]/15 text-[#F47920] inline-flex items-center justify-center shrink-0">'
                .'<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1.5l2.35 6.15L20.5 10l-6.15 2.35L12 18.5l-2.35-6.15L3.5 10l6.15-2.35L12 1.5z" /></svg>'
                .'</span>'
                .'<div>'
                .'<div class="text-xs font-bold text-[#10141A] dark:text-white">Hak Badge Eksklusif Aktif</div>'
                .'<div class="text-[11px] text-slate-500 dark:text-slate-400">Seluruh badge animasi eksklusif termasuk tier tertinggi Solar Aurora terbuka penuh tanpa syarat misi DO.</div>'
                .'</div>'
                .'</div>'
                .'<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/12 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25">Akses Penuh</span>'
                .'</div>'
            );
        }

        $giftBanner = '';

        if ($progress['has_any_pending_gift']) {
            $buttons = [];

            foreach ($progress['claimable_choice_badges'] as $badgeKey) {
                $label = User::exclusiveAnimatedBadgeOptions()[$badgeKey] ?? $badgeKey;
                $buttons[] = '<button type="button" wire:click="claimBadgeGift(\''.$badgeKey.'\')" wire:loading.attr="disabled" '
                    .'class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold bg-[#F47920] hover:bg-[#e06512] text-white shadow-sm transition-colors cursor-pointer">'
                    .'<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a2.5 2.5 0 0 0-2.45 2H4.75A1.75 1.75 0 0 0 3 5.75v2.5C3 9.216 3.784 10 4.75 10H9.25V5.5h1.5V10h4.5A1.75 1.75 0 0 0 17 8.25v-2.5A1.75 1.75 0 0 0 15.25 4h-2.8A2.5 2.5 0 0 0 10 2ZM4 11.5v4.75C4 17.216 4.784 18 5.75 18H9.25v-6.5H4Zm6.75 6.5h3.5A1.75 1.75 0 0 0 16 16.25V11.5h-5.25V18Z" /></svg>'
                    .'Ambil '.e($label)
                    .'</button>';
            }

            foreach ($progress['claimable_mythic_badges'] as $mythicKey) {
                $mythicName = User::mythicBadgeOptions()[$mythicKey] ?? $mythicKey;
                $btnGradient = match ($mythicKey) {
                    User::DEVELOPER_BADGE_OBSIDIAN_GOLD => 'from-[#111827] via-[#92400E] to-[#D97706]',
                    User::DEVELOPER_BADGE_ARCTIC_PLATINUM => 'from-[#0C2D57] via-[#0284C7] to-[#64748B]',
                    User::DEVELOPER_BADGE_EMERALD_SOVEREIGN => 'from-[#042F2E] via-[#059669] to-[#10B981]',
                    User::DEVELOPER_BADGE_CRIMSON_ECLIPSE => 'from-[#1A080C] via-[#991B1B] to-[#E11D48]',
                    User::DEVELOPER_BADGE_AMETHYST_CROWN => 'from-[#1E1038] via-[#7E22CE] to-[#A855F7]',
                    User::DEVELOPER_BADGE_ROSE_TITANIUM => 'from-[#26161C] via-[#BE185D] to-[#EC4899]',
                    default => 'from-[#0054A6] via-[#0B67C2] to-[#F47920]',
                };
                $buttons[] = '<button type="button" wire:click="claimBadgeGift(\''.$mythicKey.'\')" wire:loading.attr="disabled" '
                    .'class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-gradient-to-r '.$btnGradient.' text-white shadow-md hover:opacity-95 transition-opacity cursor-pointer">'
                    .'<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1.5l2.35 6.15L20.5 10l-6.15 2.35L12 18.5l-2.35-6.15L3.5 10l6.15-2.35L12 1.5z" /></svg>'
                    .'Ambil '.e($mythicName)
                    .'</button>';
            }

            $giftSubtitle = count($progress['claimable_choice_badges']) === 2 && $progress['claimable_mythic_badges'] === [] && $progress['pending_choice_slots'] === 1
                ? 'Anda menyelesaikan Misi 1.000 Penerimaan DO. Pilih salah satu badge eksklusif di bawah ini untuk langsung dibuka:'
                : 'Target misi penerimaan DO telah tercapai. Klik tombol di bawah untuk mengklaim hadiah badge eksklusif Anda:';

            $giftBanner = '<div class="mb-4 rounded-2xl p-4 bg-gradient-to-r from-[#F47920]/12 via-amber-500/8 to-[#0054A6]/10 border border-[#F47920]/35 dark:border-[#F47920]/40">'
                .'<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">'
                .'<div class="flex items-start gap-3">'
                .'<span class="w-9 h-9 rounded-2xl bg-[#F47920] text-white inline-flex items-center justify-center shrink-0 shadow-sm">'
                .'<svg class="w-4.5 h-4.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a2.5 2.5 0 0 0-2.45 2H4.75A1.75 1.75 0 0 0 3 5.75v2.5C3 9.216 3.784 10 4.75 10H9.25V5.5h1.5V10h4.5A1.75 1.75 0 0 0 17 8.25v-2.5A1.75 1.75 0 0 0 15.25 4h-2.8A2.5 2.5 0 0 0 10 2ZM4 11.5v4.75C4 17.216 4.784 18 5.75 18H9.25v-6.5H4Zm6.75 6.5h3.5A1.75 1.75 0 0 0 16 16.25V11.5h-5.25V18Z" /></svg>'
                .'</span>'
                .'<div>'
                .'<div class="text-sm font-bold text-[#10141A] dark:text-white">Hadiah Misi Badge Siap Diambil</div>'
                .'<div class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">'.e($giftSubtitle).'</div>'
                .'</div>'
                .'</div>'
                .'<div class="flex flex-wrap items-center gap-2 shrink-0">'.implode('', $buttons).'</div>'
                .'</div>'
                .'</div>';
        }

        $cards = [];
        foreach ($progress['milestones'] as $milestone) {
            $statusPill = match (true) {
                $milestone['completed'] => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">Diklaim</span>',
                $milestone['reached'] => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F47920]/15 text-[#F47920] dark:text-orange-300">Siap Diambil</span>',
                default => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200/75 dark:bg-white/[0.08] text-slate-600 dark:text-slate-300 tabular-nums">'.number_format(min($progress['do_count'], $milestone['target']), 0, ',', '.').' / '.number_format($milestone['target'], 0, ',', '.').'</span>',
            };

            $barColor = match (true) {
                $milestone['completed'] => 'bg-emerald-500',
                $milestone['reached'] => 'bg-[#F47920]',
                default => 'bg-[#0054A6] dark:bg-[#60A5FA]',
            };

            $cards[] = '<div class="rounded-2xl p-3.5 bg-slate-50/90 dark:bg-white/[0.03] border border-black/[0.06] dark:border-white/[0.08] flex flex-col gap-2">'
                .'<div class="flex items-center justify-between gap-2">'
                .'<span class="text-xs font-bold text-[#10141A] dark:text-white">'.e($milestone['title']).'</span>'
                .$statusPill
                .'</div>'
                .'<div class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">'.e($milestone['reward_label']).'</div>'
                .'<div class="w-full h-1.5 rounded-full bg-slate-200/80 dark:bg-white/[0.08] overflow-hidden mt-auto">'
                .'<div class="h-full rounded-full '.$barColor.' transition-all duration-300" style="width: '.$milestone['percent'].'%"></div>'
                .'</div>'
                .'</div>';
        }

        return new HtmlString(
            '<div class="space-y-3">'
            .$giftBanner
            .'<div class="rounded-2xl p-4 bg-white dark:bg-[#111722] border border-black/[0.06] dark:border-white/[0.08]">'
            .'<div class="flex flex-wrap items-center justify-between gap-2 mb-3">'
            .'<div>'
            .'<div class="text-xs font-bold text-[#10141A] dark:text-white">Misi Pembukaan Badge Eksklusif</div>'
            .'<div class="text-[11px] text-slate-500 dark:text-slate-400">Selesaikan target penerimaan DO untuk mendapatkan hadiah badge animasi secara bertahap.</div>'
            .'</div>'
            .'<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] tabular-nums">Total DO Anda: '.number_format($progress['do_count'], 0, ',', '.').'</span>'
            .'</div>'
            .'<div class="grid grid-cols-1 md:grid-cols-3 gap-3">'.implode('', $cards).'</div>'
            .'</div>'
            .'</div>'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ?User $user */
        $user = $this->getUser();
        if ($user) {
            $data['developer_badge_style'] = $user->resolvedDeveloperBadgeStyle();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var ?User $user */
        $user = $this->getUser();
        if ($user) {
            $chosen = (string) ($data['developer_badge_style'] ?? User::DEVELOPER_BADGE_NONE);
            if (! $user->canUseBadgeStyle($chosen)) {
                $data['developer_badge_style'] = User::DEVELOPER_BADGE_NONE;
            }
        }

        UserBadgeRenderer::flushCache();

        return $data;
    }

    protected function resolvePreviewCoverUrl(mixed $coverState, ?User $user): ?string
    {
        if (is_array($coverState)) {
            if ($coverState === []) {
                return null;
            }

            $coverState = reset($coverState);
        }

        if ($coverState instanceof TemporaryUploadedFile) {
            try {
                return $coverState->isPreviewable() ? $coverState->temporaryUrl() : $user?->getCoverPhotoUrl();
            } catch (\Throwable) {
                return $user?->getCoverPhotoUrl();
            }
        }

        if (is_string($coverState) && trim($coverState) !== '') {
            if (str_starts_with($coverState, 'http://') || str_starts_with($coverState, 'https://')) {
                return $coverState;
            }

            return Storage::disk('public')->url($coverState);
        }

        return null;
    }

    protected function getRedirectUrl(): ?string
    {
        return filament()->getHomeUrl();
    }
}
