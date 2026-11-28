<?php

namespace App\Services;

use App\Filament\Pages\EditProfile;
use App\Models\DeliveryOrderReceipt;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class BadgeMilestoneService
{
    public const MILESTONE_TIER_1 = 1000;

    public const MILESTONE_TIER_2 = 5000;

    public const MILESTONE_TIER_3 = 50000;

    /**
     * Badge pilihan untuk hadiah Misi 1.000 DO (pilih 1) dan Misi 5.000 DO (ambil sisanya).
     *
     * @var list<string>
     */
    public const CHOICE_EXCLUSIVE_BADGES = [
        User::DEVELOPER_BADGE_ELECTRIC,
        User::DEVELOPER_BADGE_FLAME,
    ];

    /**
     * Menghitung total penerimaan DO yang diselesaikan oleh seorang pengguna (sebagai pembuat atau penerima).
     */
    public function countUserDeliveryOrderReceipts(User $user): int
    {
        $userId = (int) $user->getKey();
        if ($userId <= 0) {
            return 0;
        }

        try {
            if (! Schema::hasTable('delivery_order_receipts')) {
                return 0;
            }

            return DeliveryOrderReceipt::query()
                ->where(fn ($query) => $query->where('created_by', $userId)->orWhere('received_by', $userId))
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Mengembalikan status progres misi, daftar hadiah yang siap diklaim, serta detail 3 tier misi.
     *
     * @return array{
     *     do_count: int,
     *     has_full_access: bool,
     *     claimed_badges: list<string>,
     *     pending_choice_slots: int,
     *     claimable_choice_badges: list<string>,
     *     can_claim_solar_aurora: bool,
     *     has_any_pending_gift: bool,
     *     milestones: list<array{
     *         key: string,
     *         target: int,
     *         title: string,
     *         reward_label: string,
     *         reached: bool,
     *         completed: bool,
     *         percent: float
     *     }>
     * }
     */
    public function getProgress(User $user, ?int $doCountOverride = null): array
    {
        $doCount = $doCountOverride ?? $this->countUserDeliveryOrderReceipts($user);
        $hasFullAccess = $user->hasFullExclusiveBadgeAccess();
        $claimedBadges = $user->claimedExclusiveBadges();

        $claimedChoiceBadges = array_values(array_intersect($claimedBadges, self::CHOICE_EXCLUSIVE_BADGES));
        $earnedChoiceSlots = match (true) {
            $doCount >= self::MILESTONE_TIER_2 => 2,
            $doCount >= self::MILESTONE_TIER_1 => 1,
            default => 0,
        };

        $pendingChoiceSlots = $hasFullAccess
            ? 0
            : max(0, $earnedChoiceSlots - count($claimedChoiceBadges));

        $claimableChoiceBadges = $pendingChoiceSlots > 0
            ? array_values(array_diff(self::CHOICE_EXCLUSIVE_BADGES, $claimedChoiceBadges))
            : [];

        $hasSolarAurora = in_array(User::DEVELOPER_BADGE_PKT_COMBO, $claimedBadges, true);
        $canClaimSolarAurora = ! $hasFullAccess
            && $doCount >= self::MILESTONE_TIER_3
            && ! $hasSolarAurora;

        $milestones = [
            [
                'key' => (string) self::MILESTONE_TIER_1,
                'target' => self::MILESTONE_TIER_1,
                'title' => 'Misi 1.000 Penerimaan DO',
                'reward_label' => 'Hadiah Pilih 1: Sparkling Lightning atau Blazing Ember',
                'reached' => $hasFullAccess || $doCount >= self::MILESTONE_TIER_1,
                'completed' => $hasFullAccess || count($claimedChoiceBadges) >= 1,
                'percent' => $hasFullAccess ? 100.0 : min(100.0, round(($doCount / self::MILESTONE_TIER_1) * 100, 1)),
            ],
            [
                'key' => (string) self::MILESTONE_TIER_2,
                'target' => self::MILESTONE_TIER_2,
                'title' => 'Misi 5.000 Penerimaan DO',
                'reward_label' => 'Hadiah Sisa: Buka kedua badge (Sparkling Lightning & Blazing Ember)',
                'reached' => $hasFullAccess || $doCount >= self::MILESTONE_TIER_2,
                'completed' => $hasFullAccess || count($claimedChoiceBadges) >= 2,
                'percent' => $hasFullAccess ? 100.0 : min(100.0, round(($doCount / self::MILESTONE_TIER_2) * 100, 1)),
            ],
            [
                'key' => (string) self::MILESTONE_TIER_3,
                'target' => self::MILESTONE_TIER_3,
                'title' => 'Misi Tertinggi 50.000 Penerimaan DO',
                'reward_label' => 'Hadiah Tier Tertinggi: Solar Aurora',
                'reached' => $hasFullAccess || $doCount >= self::MILESTONE_TIER_3,
                'completed' => $hasFullAccess || $hasSolarAurora,
                'percent' => $hasFullAccess ? 100.0 : min(100.0, round(($doCount / self::MILESTONE_TIER_3) * 100, 1)),
            ],
        ];

        return [
            'do_count' => $doCount,
            'has_full_access' => $hasFullAccess,
            'claimed_badges' => $claimedBadges,
            'pending_choice_slots' => $pendingChoiceSlots,
            'claimable_choice_badges' => $claimableChoiceBadges,
            'can_claim_solar_aurora' => $canClaimSolarAurora,
            'has_any_pending_gift' => $claimableChoiceBadges !== [] || $canClaimSolarAurora,
            'milestones' => $milestones,
        ];
    }

    /**
     * Memeriksa pencapaian misi untuk daftar user ID dan mengirim notifikasi hadiah jika baru tercapai.
     *
     * @param  list<int|string|null>  $userIds
     */
    public function syncMilestonesForUsers(array $userIds): void
    {
        $uniqueIds = array_values(array_unique(array_filter(array_map(
            fn ($id): int => is_numeric($id) ? (int) $id : 0,
            $userIds
        ))));

        if ($uniqueIds === []) {
            return;
        }

        try {
            $users = User::query()->with('roles')->whereIn('id', $uniqueIds)->get();
            foreach ($users as $user) {
                $this->syncMilestonesAndNotify($user);
            }
        } catch (\Throwable) {
            // Jangan sampai pengecekan hadiah mengganggu penyimpanan DO utama.
        }
    }

    /**
     * Memeriksa apakah user baru mencapai milestone 1.000 / 5.000 / 50.000 DO dan mengirim Gift Notification sekali per tier.
     *
     * @return list<string> Daftar key milestone yang baru dikirim notifikasinya
     */
    public function syncMilestonesAndNotify(User $user, ?int $doCountOverride = null): array
    {
        if ($user->hasFullExclusiveBadgeAccess()) {
            return [];
        }

        $progress = $this->getProgress($user, $doCountOverride);
        $doCount = $progress['do_count'];
        $alreadyNotified = is_array($user->badge_milestone_notified)
            ? array_values(array_unique(array_map('strval', $user->badge_milestone_notified)))
            : [];

        $newlyNotified = [];
        $profileUrl = $this->resolveProfileUrl();

        // Tier 1: 1.000 Penerimaan DO
        $tier1Key = (string) self::MILESTONE_TIER_1;
        if ($doCount >= self::MILESTONE_TIER_1 && ! in_array($tier1Key, $alreadyNotified, true)) {
            $alreadyNotified[] = $tier1Key;
            $newlyNotified[] = $tier1Key;
            $this->sendGiftNotification(
                user: $user,
                title: 'Hadiah Misi 1.000 Penerimaan DO',
                body: 'Selamat '.$user->name.'! Anda telah menyelesaikan '.number_format($doCount, 0, ',', '.').' penerimaan DO. Ambil hadiah Anda di Pengaturan Profil untuk memilih badge Sparkling Lightning atau Blazing Ember.',
                profileUrl: $profileUrl,
            );
        }

        // Tier 2: 5.000 Penerimaan DO
        $tier2Key = (string) self::MILESTONE_TIER_2;
        if ($doCount >= self::MILESTONE_TIER_2 && ! in_array($tier2Key, $alreadyNotified, true)) {
            $alreadyNotified[] = $tier2Key;
            $newlyNotified[] = $tier2Key;

            $remainingNames = array_map(
                fn (string $key): string => User::exclusiveAnimatedBadgeOptions()[$key] ?? $key,
                $progress['claimable_choice_badges']
            );
            $remainingText = $remainingNames !== []
                ? implode(' & ', $remainingNames)
                : 'badge eksklusif tersisa';

            $this->sendGiftNotification(
                user: $user,
                title: 'Hadiah Misi 5.000 Penerimaan DO',
                body: 'Selamat '.$user->name.'! Pencapaian '.number_format($doCount, 0, ',', '.').' penerimaan DO membuka hadiah tersisa: '.$remainingText.'. Silakan klaim di Pengaturan Profil.',
                profileUrl: $profileUrl,
            );
        }

        // Tier 3: 50.000 Penerimaan DO (Tier Tertinggi: Solar Aurora)
        $tier3Key = (string) self::MILESTONE_TIER_3;
        if ($doCount >= self::MILESTONE_TIER_3 && ! in_array($tier3Key, $alreadyNotified, true)) {
            $alreadyNotified[] = $tier3Key;
            $newlyNotified[] = $tier3Key;
            $this->sendGiftNotification(
                user: $user,
                title: 'Hadiah Tier Tertinggi: 50.000 Penerimaan DO',
                body: 'Pencapaian luar biasa, '.$user->name.'! Anda telah menyelesaikan '.number_format($doCount, 0, ',', '.').' penerimaan DO dan berhak mengambil hadiah badge tier tertinggi: Solar Aurora.',
                profileUrl: $profileUrl,
            );
        }

        if ($newlyNotified !== []) {
            try {
                if (Schema::hasColumn('users', 'badge_milestone_notified')) {
                    $user->forceFill(['badge_milestone_notified' => array_values(array_unique($alreadyNotified))])->save();
                }
            } catch (\Throwable) {
                // Abaikan jika skema parsial pada test lama belum punya kolom ini.
            }
        }

        return $newlyNotified;
    }

    /**
     * Mengklaim hadiah badge eksklusif berdasarkan pencapaian misi DO pengguna.
     */
    public function claimGiftBadge(User $user, string $badgeStyle, ?int $doCountOverride = null): bool
    {
        if (! User::isExclusiveBadgeStyle($badgeStyle)) {
            return false;
        }

        if ($user->hasFullExclusiveBadgeAccess()) {
            return false;
        }

        $progress = $this->getProgress($user, $doCountOverride);

        $isEligible = match ($badgeStyle) {
            User::DEVELOPER_BADGE_ELECTRIC, User::DEVELOPER_BADGE_FLAME => in_array($badgeStyle, $progress['claimable_choice_badges'], true),
            User::DEVELOPER_BADGE_PKT_COMBO => $progress['can_claim_solar_aurora'],
            default => false,
        };

        if (! $isEligible) {
            return false;
        }

        $updatedUnlocked = array_values(array_unique([
            ...$progress['claimed_badges'],
            $badgeStyle,
        ]));

        $user->forceFill([
            'unlocked_exclusive_badges' => $updatedUnlocked,
            'developer_badge_style' => $badgeStyle,
        ])->save();

        UserBadgeRenderer::flushCache();

        return true;
    }

    protected function sendGiftNotification(User $user, string $title, string $body, string $profileUrl): void
    {
        try {
            if (! Schema::hasTable('notifications')) {
                return;
            }

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-gift')
                ->iconColor('warning')
                ->actions([
                    Action::make('claim_badge_gift')
                        ->label('Ambil Hadiah Badge')
                        ->button()
                        ->color('warning')
                        ->url($profileUrl),
                ])
                ->sendToDatabase($user);
        } catch (\Throwable) {
            // Pastikan pengiriman notifikasi tidak pernah memblokir transaksi utama.
        }
    }

    protected function resolveProfileUrl(): string
    {
        try {
            $base = filament()->getProfileUrl() ?? EditProfile::getUrl();
        } catch (\Throwable) {
            $base = url('/admin/profile');
        }

        return $base.(str_contains($base, '?') ? '&' : '?').'tab=gaya-badge';
    }
}
