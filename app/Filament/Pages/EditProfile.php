<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\BadgeMilestoneService;
use App\Services\UserBadgeRenderer;
use App\Support\UserProfileFormSupport;
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
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

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
                ->body('Selesaikan target misi sesuai role Anda terlebih dahulu atau hadiah tersebut sudah pernah diklaim.')
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
                                if (! $user) {
                                    return new HtmlString('');
                                }

                                return UserProfileFormSupport::renderHeroBanner($user, $get);
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

                                        return UserProfileFormSupport::renderMissionProgressHtml($user, allowClaimButtons: true);
                                    }),

                                Radio::make('developer_badge_style')
                                    ->label('Pilih Gaya Badge Profil & Tabel')
                                    ->helperText(function (): string {
                                        /** @var ?User $user */
                                        $user = filament()->auth()->user();
                                        if ($user?->hasFullExclusiveBadgeAccess()) {
                                            return 'Anda memiliki hak eksklusif penuh untuk seluruh tema Classic Clean, 6 badge Eksklusif, dan 3 badge Tier Tertinggi. Perubahan langsung terlihat pada pratinjau di atas.';
                                        }

                                        $unit = $user ? app(BadgeMilestoneService::class)->resolveMissionProfile($user)['unit_label'] : 'Penerimaan DO';

                                        return "Pilih tema warna Classic Clean kapan saja. Setiap kelipatan 5.000 {$unit} membuka 1 dari 6 badge Eksklusif (hingga 30.000), sedangkan 3 badge Tier Tertinggi terbuka di misi 50.000 {$unit} atau melalui hak eksklusif Developer.";
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

                                        return UserProfileFormSupport::badgeStyleRadioOptions($user);
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
}
