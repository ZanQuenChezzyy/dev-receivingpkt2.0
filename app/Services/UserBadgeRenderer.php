<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserBadgeRenderer
{
    /**
     * Render the custom user badge HTML (Avatar + Online Dot + Name + NPK).
     */
    public static function render(?User $user, string $placeholder = 'Belum Ada'): string
    {
        if (! $user) {
            return '
                <div class="inline-flex items-center justify-center gap-1.5 w-36 sm:w-44 max-w-full py-1.5 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs font-medium text-slate-400 dark:text-slate-500 italic select-none">
                    <svg class="w-3.5 h-3.5 opacity-60 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span>'.e($placeholder).'</span>
                </div>
            ';
        }

        $avatarUrl = $user->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=FFFFFF&background=F47920';

        $npkText = ! empty($user->npk)
            ? '<span class="text-[10px] font-mono font-medium text-amber-700/90 dark:text-amber-400/90 tracking-wide mt-0.5 leading-none block truncate" title="'.e($user->npk).'">'.e($user->npk).'</span>'
            : '';

        $isOnline = method_exists($user, 'isOnline') ? $user->isOnline() : false;

        $onlineDot = $isOnline
            ? '<span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 ring-1 ring-white dark:ring-slate-900" title="Online"></span>'
            : '';

        return '
            <div class="inline-flex items-center gap-2 pl-1.5 pr-2.5 py-1 rounded-2xl w-36 sm:w-44 max-w-full bg-amber-500/10 dark:bg-amber-400/10 border border-amber-500/25 dark:border-amber-400/20 backdrop-blur-md shadow-xs transition-all duration-200 hover:scale-[1.02] hover:bg-amber-500/15 hover:border-amber-500/40 overflow-hidden">
                <div class="relative shrink-0">
                    <img
                        src="'.$avatarUrl.'"
                        alt="'.e($user->name).'"
                        class="w-7 h-7 rounded-full object-cover ring-1.5 ring-amber-500/30 dark:ring-amber-400/40 shadow-xs"
                    />
                    '.$onlineDot.'
                </div>
                <div class="flex flex-col text-left leading-none min-w-0 flex-1">
                    <span class="text-xs font-semibold text-slate-900 dark:text-slate-100 tracking-tight block truncate" title="'.e($user->name).'">
                        '.e($user->name).'
                    </span>
                    '.$npkText.'
                </div>
            </div>
        ';
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
