<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EditProfile;
use App\Filament\Pages\MemberProfile;
use App\Models\User;
use App\Services\BadgeMilestoneService;
use App\Services\LeftRailMenuService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DashboardWelcomeBentoWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-welcome-bento-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 8,
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var ?User $user */
        $user = Auth::user();
        $tz = config('app.timezone', 'Asia/Makassar');
        $now = now()->timezone($tz);
        $hour = (int) $now->format('G');

        $greeting = match (true) {
            $hour >= 4 && $hour < 11 => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 19 => 'Selamat Sore',
            default => 'Selamat Malam',
        };

        $badgeStyle = $user ? $user->resolvedDeveloperBadgeStyle() : User::DEVELOPER_BADGE_NONE;
        $roleLabel = $user
            ? ($user->primaryAnimatedBadgeRole() ?? ($user->roles->first()?->name ?? 'Pengguna'))
            : 'Pengguna';
        $avatarUrl = $user?->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($user?->name ?? 'User').'&color=FFFFFF&background=F47920';

        $shortcuts = [];
        try {
            $menuService = app(LeftRailMenuService::class);
            $catalog = $menuService->catalog();
            $railLinks = $menuService->resolveRailLinks($user, url()->current());
            $filtered = array_values(array_filter(
                $railLinks,
                static fn (array $item): bool => ($item['key'] ?? '') !== LeftRailMenuService::DASHBOARD_KEY,
            ));

            foreach (array_slice($filtered, 0, 6) as $item) {
                $key = (string) ($item['key'] ?? '');
                $item['group'] = (string) ($catalog[$key]['group'] ?? 'Pintasan');
                $shortcuts[] = $item;
            }
        } catch (\Throwable) {
            $shortcuts = [];
        }

        $missionSummary = null;
        if ($user) {
            try {
                $progress = app(BadgeMilestoneService::class)->getProgress($user);
                $missionSummary = [
                    'has_full_access' => $progress['has_full_access'],
                    'has_pending_gift' => $progress['has_any_pending_gift'],
                    'mission_count' => $progress['mission_count'],
                    'unit_label' => $progress['unit_label'],
                ];
            } catch (\Throwable) {
                $missionSummary = null;
            }
        }

        $memberProfileUrl = null;
        $badgeTabUrl = null;
        try {
            if ($user) {
                $memberProfileUrl = MemberProfile::getUrl(['user' => $user->getKey()]);
            }
            $baseProfile = filament()->getProfileUrl() ?? EditProfile::getUrl();
            $badgeTabUrl = $baseProfile.(str_contains($baseProfile, '?') ? '&' : '?').'tab=gaya-badge';
        } catch (\Throwable) {
            $memberProfileUrl = null;
            $badgeTabUrl = null;
        }

        return [
            'user' => $user,
            'greeting' => $greeting,
            'badgeStyle' => $badgeStyle,
            'roleLabel' => $roleLabel,
            'avatarUrl' => $avatarUrl,
            'shortcuts' => $shortcuts,
            'missionSummary' => $missionSummary,
            'memberProfileUrl' => $memberProfileUrl,
            'badgeTabUrl' => $badgeTabUrl,
        ];
    }
}
