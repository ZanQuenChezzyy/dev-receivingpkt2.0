<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\HtmlString;

class DeveloperBadgeRenderer
{
    /**
     * Merender HTML badge khusus role Developer sesuai gaya animasi pilihan pengguna.
     */
    public static function render(string $style, string $label = 'Developer', string $size = 'sm'): string
    {
        $resolvedStyle = in_array($style, array_keys(User::developerBadgeOptions()), true)
            ? $style
            : User::DEVELOPER_BADGE_ELECTRIC;

        $escapedLabel = e($label);
        $sizeClass = $size === 'md' ? 'pkt-dev-badge--md' : 'pkt-dev-badge--sm';

        if ($resolvedStyle === User::DEVELOPER_BADGE_NONE) {
            return '<span class="pkt-dev-badge pkt-dev-badge--none '.$sizeClass.'">'
                .'<span class="pkt-dev-badge-core">'
                .'<svg class="pkt-dev-badge-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
                .'<path fill-rule="evenodd" d="M6.28 5.22a.75.75 0 0 1 0 1.06L2.56 10l3.72 3.72a.75.75 0 0 1-1.06 1.06L.97 10.53a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Zm7.44 0a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L17.44 10l-3.72-3.72a.75.75 0 0 1 0-1.06ZM11.377 2.011a.75.75 0 0 1 .612.867l-2.5 14.5a.75.75 0 0 1-1.478-.255l2.5-14.5a.75.75 0 0 1 .866-.612Z" clip-rule="evenodd" />'
                .'</svg>'
                .'<span class="pkt-dev-badge-text">'.$escapedLabel.'</span>'
                .'</span>'
                .'</span>';
        }

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
     * Merender daftar seluruh role milik seorang User (role Developer memakai badge animasi pilihan akun tersebut).
     */
    public static function renderUserRoles(User $user, string $size = 'sm'): HtmlString
    {
        $roleNames = $user->roles->pluck('name')->filter()->values();

        if ($roleNames->isEmpty()) {
            return new HtmlString('<span class="text-xs text-slate-400">-</span>');
        }

        $badgeStyle = $user->resolvedDeveloperBadgeStyle();
        $eligibleRoles = array_map('strtolower', User::ANIMATED_BADGE_ROLES);
        $chips = [];

        foreach ($roleNames as $roleName) {
            if (in_array(strtolower((string) $roleName), $eligibleRoles, true)) {
                $chips[] = static::render($badgeStyle, $roleName, $size);
            } else {
                $chips[] = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#0054A6]/10 dark:bg-[#83A2DB]/15 text-[#0054A6] dark:text-[#93C5FD] ring-1 ring-inset ring-[#0054A6]/20 dark:ring-[#83A2DB]/25">'
                    .e($roleName)
                    .'</span>';
            }
        }

        return new HtmlString('<div class="inline-flex flex-wrap items-center gap-1.5">'.implode('', $chips).'</div>');
    }
}
