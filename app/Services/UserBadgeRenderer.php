<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserBadgeRenderer
{
    /**
     * Cache per-request gaya badge per kombinasi user_id & atribut gaya untuk mencegah query role berulang di tabel.
     *
     * @var array<string, string>
     */
    private static array $developerStyleCache = [];

    /**
     * Render the custom user badge HTML (Avatar + Online Dot + Name + NPK).
     * Untuk user dengan role Developer / AVP Receiving, pill otomatis menggunakan animasi pilihan di Profil.
     * Secara default badge dapat diklik untuk membuka halaman Profil & Log Aktivitas user tersebut.
     */
    public static function render(?User $user, string $placeholder = 'Belum Ada', ?string $overrideDeveloperStyle = null, bool $clickable = true): string
    {
        if (! $user) {
            return '
                <div class="inline-flex items-center justify-center gap-1.5 w-36 sm:w-44 max-w-full h-9 px-2 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs font-medium text-slate-400 dark:text-slate-500 italic select-none overflow-hidden shrink-0">
                    <svg class="w-3.5 h-3.5 opacity-60 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span class="truncate block" title="'.e($placeholder).'">'.e($placeholder).'</span>
                </div>
            ';
        }

        $avatarUrl = $user->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=FFFFFF&background=F47920';

        $isOnline = method_exists($user, 'isOnline') ? $user->isOnline() : false;

        $onlineDot = $isOnline
            ? '<span class="absolute bottom-0 right-0 z-20 w-2 h-2 rounded-full bg-emerald-500 ring-1 ring-white dark:ring-slate-900" title="Online"></span>'
            : '';

        $developerStyle = $overrideDeveloperStyle !== null
            ? $overrideDeveloperStyle
            : static::resolveCachedDeveloperStyle($user);

        if (User::isExclusiveBadgeStyle($developerStyle)) {
            $pillHtml = static::renderAnimatedDeveloperPill($user, $avatarUrl, $onlineDot, $developerStyle);

            return $clickable ? static::wrapWithProfileTrigger($user, $pillHtml) : $pillHtml;
        }

        $classicPalette = static::classicPillPalette($developerStyle);
        $safeStyle = array_key_exists($developerStyle, User::classicCleanBadgeOptions())
            ? $developerStyle
            : User::DEVELOPER_BADGE_NONE;

        $npkText = ! empty($user->npk)
            ? '<span class="text-[10px] font-mono font-medium '.$classicPalette['npk'].' tracking-wide mt-0.5 leading-none block truncate" title="'.e($user->npk).'">'.e($user->npk).'</span>'
            : '';

        $pillHtml = '
            <div class="pkt-classic-pill pkt-classic-pill--'.$safeStyle.' inline-flex items-center gap-2 pl-1.5 pr-2.5 w-36 sm:w-44 max-w-full h-9 rounded-2xl '.$classicPalette['container'].' backdrop-blur-md shadow-xs transition-all duration-200 hover:scale-[1.02] overflow-hidden shrink-0">
                <div class="relative shrink-0">
                    <img
                        src="'.$avatarUrl.'"
                        alt="'.e($user->name).'"
                        class="w-7 h-7 rounded-full object-cover ring-1 '.$classicPalette['ring'].' shadow-xs"
                    />
                    '.$onlineDot.'
                </div>
                <div class="flex flex-col text-left leading-none min-w-0 flex-1 overflow-hidden">
                    <span class="text-xs font-semibold text-slate-900 dark:text-slate-100 tracking-tight block truncate" title="'.e($user->name).'">
                        '.e($user->name).'
                    </span>
                    '.$npkText.'
                </div>
            </div>
        ';

        return $clickable ? static::wrapWithProfileTrigger($user, $pillHtml) : $pillHtml;
    }

    /**
     * Palet warna Classic Clean untuk pill pengguna di tabel sistem.
     *
     * @return array{container: string, ring: string, npk: string}
     */
    protected static function classicPillPalette(string $style): array
    {
        return match ($style) {
            User::CLASSIC_BADGE_BLUE => [
                'container' => 'bg-[#0054A6]/10 dark:bg-[#60A5FA]/10 border border-[#0054A6]/25 dark:border-[#60A5FA]/25 hover:bg-[#0054A6]/15 hover:border-[#0054A6]/40',
                'ring' => 'ring-[#0054A6]/35 dark:ring-[#60A5FA]/40',
                'npk' => 'text-[#0054A6]/90 dark:text-sky-300/90',
            ],
            User::CLASSIC_BADGE_EMERALD => [
                'container' => 'bg-emerald-500/10 dark:bg-emerald-400/10 border border-emerald-500/25 dark:border-emerald-400/20 hover:bg-emerald-500/15 hover:border-emerald-500/40',
                'ring' => 'ring-emerald-500/35 dark:ring-emerald-400/40',
                'npk' => 'text-emerald-700/90 dark:text-emerald-300/90',
            ],
            User::CLASSIC_BADGE_VIOLET => [
                'container' => 'bg-violet-500/10 dark:bg-violet-400/10 border border-violet-500/25 dark:border-violet-400/20 hover:bg-violet-500/15 hover:border-violet-500/40',
                'ring' => 'ring-violet-500/35 dark:ring-violet-400/40',
                'npk' => 'text-violet-700/90 dark:text-violet-300/90',
            ],
            User::CLASSIC_BADGE_ROSE => [
                'container' => 'bg-rose-500/10 dark:bg-rose-400/10 border border-rose-500/25 dark:border-rose-400/20 hover:bg-rose-500/15 hover:border-rose-500/40',
                'ring' => 'ring-rose-500/35 dark:ring-rose-400/40',
                'npk' => 'text-rose-700/90 dark:text-rose-300/90',
            ],
            User::CLASSIC_BADGE_SLATE => [
                'container' => 'bg-slate-500/10 dark:bg-slate-400/10 border border-slate-400/30 dark:border-slate-400/25 hover:bg-slate-500/15 hover:border-slate-500/45',
                'ring' => 'ring-slate-400/40 dark:ring-slate-400/40',
                'npk' => 'text-slate-600/90 dark:text-slate-300/90',
            ],
            default => [
                'container' => 'bg-amber-500/10 dark:bg-amber-400/10 border border-amber-500/25 dark:border-amber-400/20 hover:bg-amber-500/15 hover:border-amber-500/40',
                'ring' => 'ring-amber-500/30 dark:ring-amber-400/40',
                'npk' => 'text-amber-700/90 dark:text-amber-400/90',
            ],
        };
    }

    /**
     * Membungkus badge dengan penanda kelas CSS aman agar tidak terhapus oleh Str::sanitizeHtml() milik Filament TextColumn.
     * Navigasi ke /admin/member-profile/{id} ditangani oleh listener capture-phase global di panel admin.
     */
    protected static function wrapWithProfileTrigger(User $user, string $pillHtml): string
    {
        $userId = (int) $user->getKey();
        if ($userId <= 0) {
            return $pillHtml;
        }

        $tooltip = e('Lihat profil & aktivitas '.$user->name);

        return '<span class="pkt-member-badge-trigger pkt-member-uid-'.$userId.' inline-flex align-middle max-w-full cursor-pointer rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F47920]" title="'.$tooltip.'">'.$pillHtml.'</span>';
    }

    /**
     * Merender kapsul user (Avatar + Nama + NPK) dengan efek animasi looping khusus Developer.
     */
    protected static function renderAnimatedDeveloperPill(User $user, string $avatarUrl, string $onlineDot, string $style): string
    {
        $validStyle = User::isExclusiveBadgeStyle($style) ? $style : User::DEVELOPER_BADGE_ELECTRIC;

        $miniIcon = match ($validStyle) {
            User::DEVELOPER_BADGE_FLAME => '<svg class="pkt-user-pill-icon pkt-dev-icon-flame" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M13.5 4.938a7 7 0 1 1-9.006 1.737c.202-.257.59-.218.793.039.278.352.594.672.943.954.332.269.786-.049.773-.476a5.977 5.977 0 0 1 .572-2.759 6.026 6.026 0 0 1 2.486-2.665c.247-.14.55-.016.677.238A6.967 6.967 0 0 0 13.5 4.938ZM14 12a4 4 0 0 1-4 4c-1.913 0-3.52-1.398-3.91-3.182-.093-.429.44-.643.814-.413a4.043 4.043 0 0 0 1.601.564c.303.038.531-.24.51-.544a5.975 5.975 0 0 1 1.315-4.192.447.447 0 0 1 .431-.16A4.001 4.001 0 0 1 14 12Z" clip-rule="evenodd" /></svg>',
            User::DEVELOPER_BADGE_ELECTRIC => '<svg class="pkt-user-pill-icon pkt-dev-icon-bolt" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M11.983 1.907a.75.75 0 0 0-1.292-.657l-8.5 9.5A.75.75 0 0 0 2.75 12h6.572l-1.305 6.093a.75.75 0 0 0 1.292.657l8.5-9.5A.75.75 0 0 0 17.25 8h-6.572l1.305-6.093Z" /></svg>',
            User::DEVELOPER_BADGE_OBSIDIAN_GOLD => '<svg class="pkt-user-pill-icon pkt-dev-icon-obsidian" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 18h18v2H3v-2zm0-2L1.5 7l5.25 3.5L12 4l5.25 6.5L22.5 7 21 16H3z" /></svg>',
            User::DEVELOPER_BADGE_ARCTIC_PLATINUM => '<svg class="pkt-user-pill-icon pkt-dev-icon-arctic" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2L4.5 9.5 12 22l7.5-12.5L12 2zm0 3.1L16.35 9.5 12 16.9 7.65 9.5 12 5.1z" /></svg>',
            default => '<svg class="pkt-user-pill-icon pkt-dev-icon-aurora" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1.5l2.35 6.15L20.5 10l-6.15 2.35L12 18.5l-2.35-6.15L3.5 10l6.15-2.35L12 1.5zm7 13.5l1.05 2.45L22.5 18.5l-2.45 1.05L19 22l-1.05-2.45L15.5 18.5l2.45-1.05L19 15z" /></svg>',
        };

        $fxLayer = match ($validStyle) {
            User::DEVELOPER_BADGE_FLAME => '<span class="pkt-user-pill-embers" aria-hidden="true"><i></i><i></i><i></i><i></i></span>',
            User::DEVELOPER_BADGE_PKT_COMBO => '<span class="pkt-aurora-sheen" aria-hidden="true"></span><span class="pkt-aurora-stars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>',
            User::DEVELOPER_BADGE_OBSIDIAN_GOLD => '<span class="pkt-obsidian-sheen" aria-hidden="true"></span><span class="pkt-obsidian-stars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>',
            User::DEVELOPER_BADGE_ARCTIC_PLATINUM => '<span class="pkt-arctic-sheen" aria-hidden="true"></span><span class="pkt-arctic-stars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>',
            default => '<span class="pkt-user-pill-sheen" aria-hidden="true"></span>',
        };

        $fallbackRoleLabel = method_exists($user, 'primaryAnimatedBadgeRole')
            ? ($user->primaryAnimatedBadgeRole() ?? 'Developer')
            : 'Developer';
        $npkValue = ! empty($user->npk) ? e($user->npk) : e($fallbackRoleLabel);
        $npkLine = '<span class="pkt-user-pill-sub" title="'.$npkValue.'">'
            .$miniIcon
            .'<span class="truncate">'.$npkValue.'</span>'
            .'</span>';

        return '
            <div class="pkt-user-pill pkt-user-pill--'.$validStyle.' w-36 sm:w-44 max-w-full h-9 shrink-0">
                <span class="pkt-user-pill-border" aria-hidden="true"></span>
                '.$fxLayer.'
                <div class="pkt-user-pill-inner">
                    <div class="relative shrink-0">
                        <img
                            src="'.$avatarUrl.'"
                            alt="'.e($user->name).'"
                            class="w-7 h-7 rounded-full object-cover relative z-10 ring-1 ring-white/50 dark:ring-white/20 shadow-xs"
                        />
                        '.$onlineDot.'
                    </div>
                    <div class="flex flex-col text-left leading-none min-w-0 flex-1 overflow-hidden">
                        <span class="pkt-user-pill-name truncate" title="'.e($user->name).'">
                            '.e($user->name).'
                        </span>
                        '.$npkLine.'
                    </div>
                </div>
            </div>
        ';
    }

    /**
     * Mengambil gaya badge milik user dengan cache per-request agar hemat query.
     */
    protected static function resolveCachedDeveloperStyle(User $user): string
    {
        $userId = (int) $user->getKey();
        $rawStyle = (string) ($user->getAttributes()['developer_badge_style'] ?? '');
        $rawExclusive = array_key_exists('has_exclusive_badge', $user->getAttributes())
            ? var_export($user->getAttributes()['has_exclusive_badge'], true)
            : 'unset';
        $rawUnlocked = (string) ($user->getAttributes()['unlocked_exclusive_badges'] ?? '');
        $cacheKey = "{$userId}|{$rawStyle}|{$rawExclusive}|{$rawUnlocked}";

        if ($userId > 0 && array_key_exists($cacheKey, static::$developerStyleCache)) {
            return static::$developerStyleCache[$cacheKey];
        }

        $style = method_exists($user, 'resolvedDeveloperBadgeStyle')
            ? $user->resolvedDeveloperBadgeStyle()
            : User::DEVELOPER_BADGE_NONE;

        if ($userId > 0) {
            static::$developerStyleCache[$cacheKey] = $style;
        }

        return $style;
    }

    /**
     * Menghapus cache internal (berguna saat pengujian atau setelah user mengubah gaya di Profil).
     */
    public static function flushCache(): void
    {
        static::$developerStyleCache = [];
    }

    /**
     * Helper to perform search queries on relation user name or npk.
     */
    public static function searchQuery(Builder $query, string $relation, string $search): Builder
    {
        return $query->whereHas($relation, function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('npk', 'like', "%{$search}%");
        });
    }
}
