<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use App\Services\BadgeMilestoneService;
use App\Services\UserBadgeRenderer;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UserProfileFormSupport
{
    /**
     * Merender Hero Profile Card (Cover Photo + Avatar + Identitas + Live Table Pill Preview)
     * yang digunakan secara konsisten pada halaman EditProfile dan EditUser.
     */
    public static function renderHeroBanner(
        User $user,
        Get $get,
        bool $allowAnyStylePreview = false,
        ?bool $isActiveOverride = null,
    ): HtmlString {
        $roleLabel = static::resolvePreviewRoleLabel($user, $get('roles'));

        $rawSelected = (string) ($get('developer_badge_style') ?: $user->resolvedDeveloperBadgeStyle());
        $hasFullAccessState = $get('has_exclusive_badge');
        $effectiveFullAccess = is_bool($hasFullAccessState)
            ? ($user->hasRole('Developer') || $hasFullAccessState)
            : $user->hasFullExclusiveBadgeAccess();

        $canPreviewSelected = $allowAnyStylePreview
            || $effectiveFullAccess
            || $user->canUseBadgeStyle($rawSelected);

        $selected = $canPreviewSelected
            ? $rawSelected
            : $user->resolvedDeveloperBadgeStyle();

        $isExclusiveSelected = User::isExclusiveBadgeStyle($selected);

        $displayName = trim((string) ($get('name') ?: ($user->name ?? 'Pengguna')));
        $displayNpk = trim((string) ($get('npk') ?: ($user->npk ?? '-')));
        $displayEmail = trim((string) ($get('email') ?: ($user->email ?? '-')));

        $avatarUrl = static::resolvePreviewAvatarUrl($get('avatar_url'), $user, $displayName);
        $coverUrl = static::resolvePreviewCoverUrl($get('cover_photo_url'), $user);

        $avatarHtml = DeveloperBadgeRenderer::renderAvatar(
            avatarUrl: $avatarUrl,
            alt: $displayName,
            style: $selected,
            imgClasses: 'w-24 h-24',
            size: 'lg',
            fallbackRingClasses: 'ring-4 ring-white dark:ring-[#151B24] rounded-full shadow-lg',
        );

        $roleBadgeHtml = DeveloperBadgeRenderer::render($selected, $roleLabel, 'md');

        $previewUser = clone $user;
        $previewUser->name = $displayName;
        $previewUser->npk = $displayNpk !== '-' ? $displayNpk : null;
        $userPillHtml = UserBadgeRenderer::render($previewUser, $roleLabel, $selected, clickable: false);

        $isActive = $isActiveOverride ?? (bool) ($get('is_active') ?? $user->is_active);
        $statusBadge = $isActive
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
            .'<div class="relative w-full h-36 sm:h-48 overflow-hidden bg-slate-900">'
            .$coverHtml
            .'</div>'
            .'<div class="px-5 sm:px-7 pt-4 pb-6 flex flex-col md:flex-row md:items-center justify-between gap-5">'
            .'<div class="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-5 min-w-0">'
            .'<div class="relative shrink-0 -mt-14 sm:-mt-16 z-10">'
            .$avatarHtml
            .'<span class="absolute bottom-2 right-2 z-20 flex h-3.5 w-3.5 items-center justify-center">'
            .'<span class="animate-ping absolute inline-flex h-full w-full rounded-full '.($isActive ? 'bg-emerald-400' : 'bg-rose-400').' opacity-75"></span>'
            .'<span class="relative inline-flex rounded-full h-3.5 w-3.5 '.($isActive ? 'bg-emerald-500' : 'bg-rose-500').' ring-2 ring-white dark:ring-[#151B24]"></span>'
            .'</span>'
            .'</div>'
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
            .'<div class="flex flex-col items-center md:items-end gap-1.5 shrink-0 px-3.5 py-2.5 rounded-2xl bg-slate-100/80 dark:bg-white/[0.03] border border-black/[0.05] dark:border-white/[0.07]">'
            .'<span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tampilan di Tabel Sistem</span>'
            .$userPillHtml
            .'</div>'
            .'</div>'
            .'</div>'
        );
    }

    /**
     * Merender kartu progres misi sesuai role pengguna (dengan tombol klaim opsional pada halaman EditProfile).
     */
    public static function renderMissionProgressHtml(
        User $user,
        bool $allowClaimButtons = true,
        ?bool $hasFullAccessOverride = null,
    ): HtmlString {
        $progress = app(BadgeMilestoneService::class)->getProgress($user);
        $hasFullAccess = $hasFullAccessOverride ?? $progress['has_full_access'];

        if ($hasFullAccess) {
            return new HtmlString(
                '<div class="rounded-2xl px-4 py-3 bg-[#0054A6]/8 dark:bg-[#83A2DB]/10 border border-[#0054A6]/20 dark:border-[#83A2DB]/20 flex flex-wrap items-center justify-between gap-2">'
                .'<div class="flex items-center gap-2.5">'
                .'<span class="w-8 h-8 rounded-xl bg-[#F47920]/15 text-[#F47920] inline-flex items-center justify-center shrink-0">'
                .'<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1.5l2.35 6.15L20.5 10l-6.15 2.35L12 18.5l-2.35-6.15L3.5 10l6.15-2.35L12 1.5z" /></svg>'
                .'</span>'
                .'<div>'
                .'<div class="text-xs font-bold text-[#10141A] dark:text-white">Hak Badge Eksklusif Aktif</div>'
                .'<div class="text-[11px] text-slate-500 dark:text-slate-400">Seluruh 6 badge Eksklusif dan 3 badge Tier Tertinggi (Solar Aurora, Emerald Sovereign, Crimson Eclipse) terbuka penuh tanpa syarat misi.</div>'
                .'</div>'
                .'</div>'
                .'<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/12 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25">Akses Penuh</span>'
                .'</div>'
            );
        }

        $giftBanner = '';

        if ($allowClaimButtons && $progress['has_any_pending_gift']) {
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
                    User::DEVELOPER_BADGE_EMERALD_SOVEREIGN => 'from-[#065F46] via-[#0D9488] to-[#10B981]',
                    User::DEVELOPER_BADGE_CRIMSON_ECLIPSE => 'from-[#881337] via-[#DC2626] to-[#FB7185]',
                    default => 'from-[#0054A6] via-[#0B67C2] to-[#F47920]',
                };
                $buttons[] = '<button type="button" wire:click="claimBadgeGift(\''.$mythicKey.'\')" wire:loading.attr="disabled" '
                    .'class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-gradient-to-r '.$btnGradient.' text-white shadow-md hover:opacity-95 transition-opacity cursor-pointer">'
                    .'<svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1.5l2.35 6.15L20.5 10l-6.15 2.35L12 18.5l-2.35-6.15L3.5 10l6.15-2.35L12 1.5z" /></svg>'
                    .'Ambil '.e($mythicName)
                    .'</button>';
            }

            $slotRemaining = $progress['pending_choice_slots'];
            $giftSubtitle = $slotRemaining > 0 && $progress['claimable_mythic_badges'] === []
                ? "Anda memiliki {$slotRemaining} slot hadiah badge Eksklusif (misi 1.000 lalu kelipatan 5.000 {$progress['unit_label']}). Pilih badge yang ingin dibuka:"
                : "Target misi {$progress['unit_label']} telah tercapai. Klik tombol di bawah untuk mengklaim hadiah badge Anda:";

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
                default => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200/75 dark:bg-white/[0.08] text-slate-600 dark:text-slate-300 tabular-nums">'.number_format(min($progress['mission_count'], $milestone['target']), 0, ',', '.').' / '.number_format($milestone['target'], 0, ',', '.').'</span>',
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

        $breakdownBadge = filled($progress['breakdown_text'])
            ? '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-300 tabular-nums">'.e($progress['breakdown_text']).'</span>'
            : '';

        return new HtmlString(
            '<div class="space-y-3">'
            .$giftBanner
            .'<div class="rounded-2xl p-4 bg-white dark:bg-[#111722] border border-black/[0.06] dark:border-white/[0.08]">'
            .'<div class="flex flex-wrap items-center justify-between gap-2 mb-3">'
            .'<div>'
            .'<div class="text-xs font-bold text-[#10141A] dark:text-white">'.e($progress['mission_title']).'</div>'
            .'<div class="text-[11px] text-slate-500 dark:text-slate-400">'.e($progress['mission_description']).'</div>'
            .'</div>'
            .'<div class="flex flex-wrap items-center gap-1.5">'
            .$breakdownBadge
            .'<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] tabular-nums">Total: '.number_format($progress['mission_count'], 0, ',', '.').' '.e($progress['unit_label']).'</span>'
            .'</div>'
            .'</div>'
            .'<div class="grid grid-cols-1 md:grid-cols-3 gap-3">'.implode('', $cards).'</div>'
            .'</div>'
            .'</div>'
        );
    }

    /**
     * Membangun daftar opsi kartu Radio untuk 6 tema Classic Clean, 6 badge Eksklusif, dan 3 badge Tier Tertinggi.
     *
     * @return array<string, HtmlString>
     */
    public static function badgeStyleRadioOptions(
        ?User $user,
        bool $unlockAllForEditor = false,
        ?bool $hasFullAccessOverride = null,
    ): array {
        $hasFullAccess = $hasFullAccessOverride ?? (bool) $user?->hasFullExclusiveBadgeAccess();
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

        $unitLabel = $user
            ? app(BadgeMilestoneService::class)->resolveMissionProfile($user)['unit_label']
            : 'Penerimaan DO';
        $exclusiveHint = "Misi 1.000 lalu kelipatan 5.000 {$unitLabel} (1 badge/tahap) atau hak eksklusif Developer";
        $mythicHint = "Misi 50.000 {$unitLabel} atau hak eksklusif Developer";

        $exclusiveDefinitions = [
            User::DEVELOPER_BADGE_ELECTRIC => [
                'title' => 'Sparkling Lightning',
                'desc' => 'Sengatan busur petir biru-cyan menyambar dengan percikan listrik.',
                'tier_chip' => 'Eksklusif',
                'unlock_hint' => $exclusiveHint,
            ],
            User::DEVELOPER_BADGE_FLAME => [
                'title' => 'Blazing Ember',
                'desc' => 'Lidah api oranye berkobar dengan percikan bara naik berulang.',
                'tier_chip' => 'Eksklusif',
                'unlock_hint' => $exclusiveHint,
            ],
            User::DEVELOPER_BADGE_OBSIDIAN_GOLD => [
                'title' => 'Royal Obsidian',
                'desc' => 'Gradasi perunggu & emas 24K cair dengan kilatan berlian empat sudut.',
                'tier_chip' => 'Eksklusif',
                'unlock_hint' => $exclusiveHint,
            ],
            User::DEVELOPER_BADGE_ARCTIC_PLATINUM => [
                'title' => 'Arctic Platinum',
                'desc' => 'Gradasi biru gletser & perak beku dengan taburan kristal salju turun.',
                'tier_chip' => 'Eksklusif',
                'unlock_hint' => $exclusiveHint,
            ],
            User::DEVELOPER_BADGE_AMETHYST_CROWN => [
                'title' => 'Amethyst Crown',
                'desc' => 'Gradasi indigo & ungu ametis dengan distorsi gelombang warp dan kilau kristal.',
                'tier_chip' => 'Eksklusif',
                'unlock_hint' => $exclusiveHint,
            ],
            User::DEVELOPER_BADGE_ROSE_TITANIUM => [
                'title' => 'Rose Titanium',
                'desc' => 'Gradasi rose gold & sampanye dengan gelombang sutra dan kelopak kilau melayang.',
                'tier_chip' => 'Eksklusif',
                'unlock_hint' => $exclusiveHint,
            ],
            User::DEVELOPER_BADGE_PKT_COMBO => [
                'title' => 'Solar Aurora',
                'desc' => 'Aurora biru & oranye PKT dengan jilatan korona surya dan bintang pijar yang memancar keluar badge.',
                'tier_chip' => 'Tier Tertinggi',
                'unlock_hint' => $mythicHint,
            ],
            User::DEVELOPER_BADGE_EMERALD_SOVEREIGN => [
                'title' => 'Emerald Sovereign',
                'desc' => 'Zamrud & giok teal dengan gelombang perisai energi mengembang dan kristal melayang di luar badge.',
                'tier_chip' => 'Tier Tertinggi',
                'unlock_hint' => $mythicHint,
            ],
            User::DEVELOPER_BADGE_CRIMSON_ECLIPSE => [
                'title' => 'Crimson Eclipse',
                'desc' => 'Merah rubi gerhana dengan distorsi glitch intens, scanline kromatik, gelombang kejut keluar badge, hingga efek glitch pada foto profil.',
                'tier_chip' => 'Tier Tertinggi',
                'unlock_hint' => $mythicHint,
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
            $userUnlocked = $hasFullAccess || (bool) $user?->canUseBadgeStyle($key);
            $isSelectable = $unlockAllForEditor || $userUnlocked;
            $isMythic = User::isMythicBadgeStyle($key);

            if ($userUnlocked) {
                $chipColor = match ($key) {
                    User::DEVELOPER_BADGE_EMERALD_SOVEREIGN => 'bg-gradient-to-r from-[#065F46] via-[#0D9488] to-[#10B981] text-emerald-50 shadow-xs',
                    User::DEVELOPER_BADGE_CRIMSON_ECLIPSE => 'bg-gradient-to-r from-[#881337] via-[#DC2626] to-[#FB7185] text-rose-50 shadow-xs',
                    User::DEVELOPER_BADGE_PKT_COMBO => 'bg-gradient-to-r from-[#0054A6] via-[#0B67C2] to-[#F47920] text-white shadow-xs',
                    default => 'bg-[#F47920]/15 text-[#F47920] dark:text-orange-300',
                };
                $chipText = $hasFullAccess ? $meta['tier_chip'] : ($isMythic ? 'Tier Tertinggi • Terbuka' : 'Terbuka via Misi');
                $statusChip = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold '.$chipColor.'">'.$chipText.'</span>';
                $lockedNote = '';
            } elseif ($isSelectable) {
                $chipColor = $isMythic
                    ? 'bg-gradient-to-r from-[#0054A6] via-[#0B67C2] to-[#F47920] text-white shadow-xs'
                    : 'bg-amber-500/15 text-amber-700 dark:text-amber-300';
                $statusChip = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold '.$chipColor.'">'.$meta['tier_chip'].' • Pilih Developer</span>';
                $lockedNote = '<span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">Memilih badge ini otomatis membukanya untuk pengguna.</span>';
            } else {
                $statusChip = '<span class="pkt-badge-locked-chip inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200/90 dark:bg-white/[0.1] text-slate-600 dark:text-slate-300" title="'.e($meta['unlock_hint']).'">'.$lockIcon.'Terkunci</span>';
                $lockedNote = '<span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">'.$lockIcon.e($meta['unlock_hint']).'</span>';
            }

            $previewLabel = $isSelectable ? $roleLabel : ($isMythic ? 'Tier Tertinggi' : 'Eksklusif');

            $options[$key] = new HtmlString(
                '<div class="flex flex-col gap-2 py-0.5">'
                .'<div class="flex items-center justify-between gap-2">'
                .'<span class="font-bold text-sm text-[#10141A] dark:text-white">'.e($meta['title']).'</span>'
                .$statusChip
                .'</div>'
                .'<span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">'.e($meta['desc']).'</span>'
                .'<div class="'.($isMythic ? 'py-2.5 px-2' : 'pt-1').' flex items-center gap-2 overflow-visible">'.DeveloperBadgeRenderer::render($key, $previewLabel).'</div>'
                .$lockedNote
                .'</div>'
            );
        }

        return $options;
    }

    public static function resolvePreviewCoverUrl(mixed $coverState, ?User $user): ?string
    {
        if (is_array($coverState)) {
            if ($coverState === []) {
                return null;
            }

            $coverState = reset($coverState);
        }

        if ($coverState instanceof TemporaryUploadedFile) {
            try {
                return $coverState->temporaryUrl();
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

        return $user?->getCoverPhotoUrl();
    }

    public static function resolvePreviewAvatarUrl(mixed $avatarState, ?User $user, string $displayName): string
    {
        if (is_array($avatarState)) {
            $avatarState = $avatarState === [] ? null : reset($avatarState);
        }

        if ($avatarState instanceof TemporaryUploadedFile) {
            try {
                return $avatarState->temporaryUrl();
            } catch (\Throwable) {
                // Fall back to persisted avatar
            }
        }

        if (is_string($avatarState) && trim($avatarState) !== '') {
            if (str_starts_with($avatarState, 'http://') || str_starts_with($avatarState, 'https://')) {
                return $avatarState;
            }

            return Storage::disk('public')->url($avatarState);
        }

        return $user?->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&color=FFFFFF&background=F47920';
    }

    protected static function resolvePreviewRoleLabel(User $user, mixed $rolesState): string
    {
        if (is_array($rolesState) && $rolesState !== []) {
            $roleIds = array_values(array_filter(array_map(
                fn ($id): int => is_numeric($id) ? (int) $id : 0,
                $rolesState
            )));

            if ($roleIds !== []) {
                try {
                    $firstRole = Role::query()->whereIn('id', $roleIds)->value('name');
                    if (is_string($firstRole) && $firstRole !== '') {
                        return $firstRole;
                    }
                } catch (\Throwable) {
                    // Fallback to loaded user roles
                }
            }
        }

        return $user->primaryAnimatedBadgeRole()
            ?? ($user->roles->first()?->name ?? 'User');
    }
}
