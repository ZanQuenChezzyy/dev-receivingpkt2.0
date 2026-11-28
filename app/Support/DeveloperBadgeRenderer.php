<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\HtmlString;

class DeveloperBadgeRenderer
{
    /**
     * Merender HTML badge role sesuai gaya pilihan pengguna (eksklusif animasi atau Classic Clean).
     */
    public static function render(string $style, string $label = 'Developer', string $size = 'sm'): string
    {
        $resolvedStyle = array_key_exists($style, User::developerBadgeOptions())
            ? $style
            : User::DEVELOPER_BADGE_NONE;

        if (! User::isExclusiveBadgeStyle($resolvedStyle)) {
            return static::renderClassicRoleBadge($resolvedStyle, $label, $size);
        }

        $escapedLabel = e($label);
        $sizeClass = $size === 'md' ? 'pkt-dev-badge--md' : 'pkt-dev-badge--sm';

        $iconSvg = match ($resolvedStyle) {
            User::DEVELOPER_BADGE_FLAME => '<svg class="pkt-dev-badge-icon pkt-dev-icon-flame" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
                .'<path fill-rule="evenodd" d="M13.5 4.938a7 7 0 1 1-9.006 1.737c.202-.257.59-.218.793.039.278.352.594.672.943.954.332.269.786-.049.773-.476a5.977 5.977 0 0 1 .572-2.759 6.026 6.026 0 0 1 2.486-2.665c.247-.14.55-.016.677.238A6.967 6.967 0 0 0 13.5 4.938ZM14 12a4 4 0 0 1-4 4c-1.913 0-3.52-1.398-3.91-3.182-.093-.429.44-.643.814-.413a4.043 4.043 0 0 0 1.601.564c.303.038.531-.24.51-.544a5.975 5.975 0 0 1 1.315-4.192.447.447 0 0 1 .431-.16A4.001 4.001 0 0 1 14 12Z" clip-rule="evenodd" />'
                .'</svg>',
            User::DEVELOPER_BADGE_ELECTRIC => '<svg class="pkt-dev-badge-icon pkt-dev-icon-bolt" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
                .'<path d="M11.983 1.907a.75.75 0 0 0-1.292-.657l-8.5 9.5A.75.75 0 0 0 2.75 12h6.572l-1.305 6.093a.75.75 0 0 0 1.292.657l8.5-9.5A.75.75 0 0 0 17.25 8h-6.572l1.305-6.093Z" />'
                .'</svg>',
            default => '<svg class="pkt-dev-badge-icon pkt-dev-icon-combo" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
                .'<path d="M11.983 1.907a.75.75 0 0 0-1.292-.657l-8.5 9.5A.75.75 0 0 0 2.75 12h6.572l-1.305 6.093a.75.75 0 0 0 1.292.657l8.5-9.5A.75.75 0 0 0 17.25 8h-6.572l1.305-6.093Z" />'
                .'</svg>',
        };

        $particles = $resolvedStyle === User::DEVELOPER_BADGE_FLAME
            ? '<span class="pkt-dev-embers" aria-hidden="true"><i></i><i></i><i></i></span>'
            : '<span class="pkt-dev-sheen" aria-hidden="true"></span>';

        return '<span class="pkt-dev-badge pkt-dev-badge--'.$resolvedStyle.' '.$sizeClass.'">'
            .'<span class="pkt-dev-badge-ring" aria-hidden="true"></span>'
            .$particles
            .'<span class="pkt-dev-badge-core">'
            .$iconSvg
            .'<span class="pkt-dev-badge-text">'.$escapedLabel.'</span>'
            .'</span>'
            .'</span>';
    }

    /**
     * Merender badge role bergaya Classic Clean (statis rapi dengan palet warna pilihan pengguna).
     */
    public static function renderClassicRoleBadge(string $style, string $label, string $size = 'sm'): string
    {
        $palette = static::classicRolePalette($style);
        $sizeClasses = $size === 'md' ? 'px-3 py-1 text-xs gap-1.5' : 'px-2.5 py-0.5 text-xs gap-1.5';
        $safeStyle = array_key_exists($style, User::classicCleanBadgeOptions()) ? $style : User::DEVELOPER_BADGE_NONE;

        return '<span class="pkt-classic-badge pkt-classic-badge--'.$safeStyle.' inline-flex items-center rounded-full font-semibold ring-1 ring-inset '.$sizeClasses.' '.$palette['badge'].'">'
            .'<span class="w-1.5 h-1.5 rounded-full shrink-0 '.$palette['dot'].'" aria-hidden="true"></span>'
            .'<span>'.e($label).'</span>'
            .'</span>';
    }

    /**
     * @return array{badge: string, dot: string, avatar_ring: string}
     */
    public static function classicRolePalette(string $style): array
    {
        return match ($style) {
            User::CLASSIC_BADGE_BLUE => [
                'badge' => 'bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] ring-[#0054A6]/25 dark:ring-[#83A2DB]/30',
                'dot' => 'bg-[#0054A6] dark:bg-[#60A5FA]',
                'avatar_ring' => 'ring-2 ring-[#0054A6]/45 dark:ring-[#60A5FA]/50',
            ],
            User::CLASSIC_BADGE_EMERALD => [
                'badge' => 'bg-emerald-500/10 dark:bg-emerald-400/15 text-emerald-700 dark:text-emerald-300 ring-emerald-500/25 dark:ring-emerald-400/30',
                'dot' => 'bg-emerald-500 dark:bg-emerald-400',
                'avatar_ring' => 'ring-2 ring-emerald-500/45 dark:ring-emerald-400/50',
            ],
            User::CLASSIC_BADGE_VIOLET => [
                'badge' => 'bg-violet-500/10 dark:bg-violet-400/15 text-violet-700 dark:text-violet-300 ring-violet-500/25 dark:ring-violet-400/30',
                'dot' => 'bg-violet-500 dark:bg-violet-400',
                'avatar_ring' => 'ring-2 ring-violet-500/45 dark:ring-violet-400/50',
            ],
            User::CLASSIC_BADGE_ROSE => [
                'badge' => 'bg-rose-500/10 dark:bg-rose-400/15 text-rose-700 dark:text-rose-300 ring-rose-500/25 dark:ring-rose-400/30',
                'dot' => 'bg-rose-500 dark:bg-rose-400',
                'avatar_ring' => 'ring-2 ring-rose-500/45 dark:ring-rose-400/50',
            ],
            User::CLASSIC_BADGE_SLATE => [
                'badge' => 'bg-slate-500/10 dark:bg-slate-400/15 text-slate-700 dark:text-slate-200 ring-slate-500/25 dark:ring-slate-400/30',
                'dot' => 'bg-slate-500 dark:bg-slate-400',
                'avatar_ring' => 'ring-2 ring-slate-400/50 dark:ring-slate-400/45',
            ],
            default => [
                'badge' => 'bg-amber-500/12 dark:bg-amber-400/15 text-amber-800 dark:text-amber-300 ring-amber-500/25 dark:ring-amber-400/30',
                'dot' => 'bg-[#F47920] dark:bg-amber-400',
                'avatar_ring' => 'ring-2 ring-amber-500/45 dark:ring-amber-400/45',
            ],
        };
    }

    /**
     * Merender daftar seluruh role milik seorang User (memakai animasi eksklusif untuk Developer/AVP
     * atau palet Classic Clean pilihan pengguna).
     */
    public static function renderUserRoles(User $user, string $size = 'sm'): HtmlString
    {
        $roleNames = $user->roles->pluck('name')->filter()->values();

        if ($roleNames->isEmpty()) {
            return new HtmlString('<span class="text-xs text-slate-400">-</span>');
        }

        $badgeStyle = $user->resolvedDeveloperBadgeStyle();
        $chips = [];

        foreach ($roleNames as $roleName) {
            $chips[] = static::render($badgeStyle, (string) $roleName, $size);
        }

        return new HtmlString('<div class="inline-flex flex-wrap items-center gap-1.5">'.implode('', $chips).'</div>');
    }
}
