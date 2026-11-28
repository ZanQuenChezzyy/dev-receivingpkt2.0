<?php

namespace App\Services;

use App\Filament\Pages\EditProfile;
use App\Models\DeliveryOrderReceipt;
use App\Models\GrsRdtv;
use App\Models\InvoicingReceipt;
use App\Models\User;
use App\Models\WarehouseDeliveryBatch;
use App\Models\WarehouseTransmittal;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class BadgeMilestoneService
{
    public const MILESTONE_EXCLUSIVE_STEP = 5000;

    public const MILESTONE_TIER_1 = 5000;

    public const MILESTONE_TIER_2 = 30000;

    public const MILESTONE_TIER_3 = 50000;

    public const TRACK_OPERATOR_DO = 'operator_do';

    public const TRACK_ADMIN_DO_GRS = 'admin_do_grs';

    public const TRACK_STAFF_INVOICE = 'staff_invoice';

    public const TRACK_SEKRETARIS_DO_WAREHOUSE = 'sekretaris_do_warehouse';

    /**
     * Daftar role dengan misi Penerimaan DO + Penerbitan GRS/RDTV.
     *
     * @var list<string>
     */
    public const ADMIN_DO_GRS_ROLES = [
        'Admin Chemical',
        'Admin Sparepart',
        'Admin Bahan Baku',
        'Staff Chemical',
        'Staff Sparepart',
        'Staff Bahan Baku',
    ];

    /**
     * Menentukan profil misi pengguna berdasarkan role aktifnya.
     *
     * @return array{
     *     track: string,
     *     role_label: string,
     *     mission_title: string,
     *     mission_description: string,
     *     unit_label: string
     * }
     */
    public function resolveMissionProfile(User $user): array
    {
        $roleNames = [];

        try {
            $roleNames = $user->roles()->pluck('name')->map(fn ($name): string => (string) $name)->all();
        } catch (\Throwable) {
            $roleNames = [];
        }

        $lowerMap = [];
        foreach ($roleNames as $roleName) {
            $lowerMap[strtolower($roleName)] = $roleName;
        }

        // 1. Admin Chemical / Admin Sparepart / Admin Bahan Baku (DO + GRS/RDTV)
        foreach (self::ADMIN_DO_GRS_ROLES as $candidate) {
            $key = strtolower($candidate);
            if (array_key_exists($key, $lowerMap)) {
                $displayRole = $lowerMap[$key];

                return [
                    'track' => self::TRACK_ADMIN_DO_GRS,
                    'role_label' => $displayRole,
                    'mission_title' => "Misi {$displayRole} — Penerimaan DO & GRS/RDTV",
                    'mission_description' => 'Selesaikan penerimaan DO dan penerbitan GRS/RDTV sebanyak-banyaknya untuk membuka hadiah badge eksklusif dan tier tertinggi.',
                    'unit_label' => 'DO & GRS/RDTV',
                ];
            }
        }

        // 2. Staff Invoice (Penanganan Invoice)
        if (array_key_exists('staff invoice', $lowerMap)) {
            return [
                'track' => self::TRACK_STAFF_INVOICE,
                'role_label' => $lowerMap['staff invoice'],
                'mission_title' => 'Misi Staff Invoice — Penanganan Invoice',
                'mission_description' => 'Tangani dokumen invoice sebagai PIC sebanyak-banyaknya untuk membuka hadiah badge eksklusif dan tier tertinggi.',
                'unit_label' => 'Invoice Ditangani',
            ];
        }

        // 3. Sekretaris (Penerimaan DO + Pengiriman Gudang)
        if (array_key_exists('sekretaris', $lowerMap)) {
            $displayRole = $lowerMap['sekretaris'];

            return [
                'track' => self::TRACK_SEKRETARIS_DO_WAREHOUSE,
                'role_label' => $displayRole,
                'mission_title' => "Misi {$displayRole} — Penerimaan DO & Pengiriman Gudang",
                'mission_description' => 'Selesaikan penerimaan DO dan pengiriman gudang sebanyak-banyaknya untuk membuka hadiah badge eksklusif dan tier tertinggi.',
                'unit_label' => 'DO & Pengiriman Gudang',
            ];
        }

        // 4. Operator & Role Umum Lainnya (Penerimaan DO)
        $displayRole = $lowerMap['operator'] ?? ($roleNames[0] ?? 'Operator');

        return [
            'track' => self::TRACK_OPERATOR_DO,
            'role_label' => $displayRole,
            'mission_title' => "Misi {$displayRole} — Penerimaan DO",
            'mission_description' => 'Selesaikan penerimaan DO sebanyak-banyaknya untuk membuka hadiah badge eksklusif dan tier tertinggi.',
            'unit_label' => 'Penerimaan DO',
        ];
    }

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
     * Menghitung total GRS/RDTV yang diterbitkan oleh seorang pengguna.
     */
    public function countUserGrsRdtvs(User $user): int
    {
        $userId = (int) $user->getKey();
        if ($userId <= 0) {
            return 0;
        }

        try {
            if (! Schema::hasTable('grs_rdtvs')) {
                return 0;
            }

            return GrsRdtv::query()->where('created_by', $userId)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Menghitung total dokumen invoice yang ditangani pengguna sebagai PIC.
     */
    public function countUserHandledInvoices(User $user): int
    {
        $userId = (int) $user->getKey();
        if ($userId <= 0) {
            return 0;
        }

        try {
            if (! Schema::hasTable('invoicing_receipts')) {
                return 0;
            }

            return InvoicingReceipt::query()->where('pic_id', $userId)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Menghitung total pengiriman gudang yang diproses pengguna (Transmittal Gudang dan Master Pengiriman MB51).
     */
    public function countUserWarehouseDeliveries(User $user): int
    {
        $userId = (int) $user->getKey();
        if ($userId <= 0) {
            return 0;
        }

        $total = 0;

        try {
            if (Schema::hasTable('warehouse_transmittals')) {
                $total += WarehouseTransmittal::query()->where('created_by', $userId)->count();
            }
        } catch (\Throwable) {
            // Abaikan jika tabel belum tersedia pada skema parsial.
        }

        try {
            if (Schema::hasTable('warehouse_delivery_batches')) {
                $total += WarehouseDeliveryBatch::query()->where('uploaded_by', $userId)->count();
            }
        } catch (\Throwable) {
            // Abaikan jika tabel belum tersedia pada skema parsial.
        }

        return $total;
    }

    /**
     * Menghitung skor misi sesuai track role pengguna beserta rincian sumbernya.
     *
     * @return array{total: int, do_count: int, grs_count: int, invoice_count: int, warehouse_count: int, breakdown_text: ?string}
     */
    public function calculateMissionCount(User $user, string $track): array
    {
        if ($track === self::TRACK_STAFF_INVOICE) {
            $invoiceCount = $this->countUserHandledInvoices($user);

            return [
                'total' => $invoiceCount,
                'do_count' => 0,
                'grs_count' => 0,
                'invoice_count' => $invoiceCount,
                'warehouse_count' => 0,
                'breakdown_text' => null,
            ];
        }

        $doCount = $this->countUserDeliveryOrderReceipts($user);

        if ($track === self::TRACK_ADMIN_DO_GRS) {
            $grsCount = $this->countUserGrsRdtvs($user);

            return [
                'total' => $doCount + $grsCount,
                'do_count' => $doCount,
                'grs_count' => $grsCount,
                'invoice_count' => 0,
                'warehouse_count' => 0,
                'breakdown_text' => number_format($doCount, 0, ',', '.').' DO • '.number_format($grsCount, 0, ',', '.').' GRS/RDTV',
            ];
        }

        if ($track === self::TRACK_SEKRETARIS_DO_WAREHOUSE) {
            $warehouseCount = $this->countUserWarehouseDeliveries($user);

            return [
                'total' => $doCount + $warehouseCount,
                'do_count' => $doCount,
                'grs_count' => 0,
                'invoice_count' => 0,
                'warehouse_count' => $warehouseCount,
                'breakdown_text' => number_format($doCount, 0, ',', '.').' DO • '.number_format($warehouseCount, 0, ',', '.').' Pengiriman Gudang',
            ];
        }

        return [
            'total' => $doCount,
            'do_count' => $doCount,
            'grs_count' => 0,
            'invoice_count' => 0,
            'warehouse_count' => 0,
            'breakdown_text' => null,
        ];
    }

    /**
     * Daftar target milestone kelipatan 5.000 untuk seluruh badge Eksklusif (1 badge setiap 5.000).
     *
     * @return list<int>
     */
    public function exclusiveMilestoneTargets(): array
    {
        $count = count(User::standardExclusiveBadgeOptions());
        $targets = [];

        for ($i = 1; $i <= $count; $i++) {
            $targets[] = $i * self::MILESTONE_EXCLUSIVE_STEP;
        }

        return $targets;
    }

    /**
     * Mengembalikan status progres misi sesuai role, daftar hadiah yang siap diklaim, serta detail milestone.
     *
     * @return array{
     *     track: string,
     *     role_label: string,
     *     mission_title: string,
     *     mission_description: string,
     *     unit_label: string,
     *     mission_count: int,
     *     do_count: int,
     *     breakdown_text: ?string,
     *     exclusive_step: int,
     *     earned_standard_slots: int,
     *     total_standard_slots: int,
     *     has_full_access: bool,
     *     claimed_badges: list<string>,
     *     pending_choice_slots: int,
     *     claimable_choice_badges: list<string>,
     *     claimable_mythic_badges: list<string>,
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
        $profile = $this->resolveMissionProfile($user);
        $metrics = $doCountOverride !== null
            ? [
                'total' => $doCountOverride,
                'do_count' => $doCountOverride,
                'grs_count' => 0,
                'invoice_count' => 0,
                'warehouse_count' => 0,
                'breakdown_text' => null,
            ]
            : $this->calculateMissionCount($user, $profile['track']);

        $missionCount = $metrics['total'];
        $hasFullAccess = $user->hasFullExclusiveBadgeAccess();
        $claimedBadges = $user->claimedExclusiveBadges();

        $standardExclusiveKeys = array_keys(User::standardExclusiveBadgeOptions());
        $totalStandardCount = count($standardExclusiveKeys);
        $claimedStandardBadges = array_values(array_intersect($claimedBadges, $standardExclusiveKeys));

        // Setiap kelipatan 5.000 membuka tepat 1 pilihan badge Eksklusif (maksimal 6 badge pada 30.000)
        $earnedStandardSlots = min($totalStandardCount, intdiv(max(0, $missionCount), self::MILESTONE_EXCLUSIVE_STEP));

        $pendingChoiceSlots = $hasFullAccess
            ? 0
            : max(0, $earnedStandardSlots - count($claimedStandardBadges));

        $claimableChoiceBadges = $pendingChoiceSlots > 0
            ? array_values(array_diff($standardExclusiveKeys, $claimedStandardBadges))
            : [];

        // Misi 50.000: buka 3 badge Tier Tertinggi (Solar Aurora, Emerald Sovereign, Crimson Eclipse)
        $mythicKeys = array_keys(User::mythicBadgeOptions());
        $claimedMythicBadges = array_values(array_intersect($claimedBadges, $mythicKeys));
        $claimableMythicBadges = (! $hasFullAccess && $missionCount >= self::MILESTONE_TIER_3)
            ? array_values(array_diff($mythicKeys, $claimedMythicBadges))
            : [];
        $canClaimSolarAurora = in_array(User::DEVELOPER_BADGE_PKT_COMBO, $claimableMythicBadges, true);

        $unit = $profile['unit_label'];
        $mythicNames = implode(', ', User::mythicBadgeOptions());
        $maxExclusiveTarget = $totalStandardCount * self::MILESTONE_EXCLUSIVE_STEP;

        // Kartu 1 menampilkan target kelipatan 5.000 aktif/berikutnya
        $claimedStandardCount = count($claimedStandardBadges);
        $nextSlotNumber = min($totalStandardCount, $claimedStandardCount + 1);
        $nextStepTarget = $nextSlotNumber * self::MILESTONE_EXCLUSIVE_STEP;
        $nextStepReached = $hasFullAccess || $missionCount >= $nextStepTarget;
        $nextStepCompleted = $hasFullAccess || $claimedStandardCount >= $totalStandardCount;

        $milestones = [
            [
                'key' => (string) $nextStepTarget,
                'target' => $nextStepTarget,
                'title' => 'Misi '.number_format($nextStepTarget, 0, ',', '.').' '.$unit,
                'reward_label' => $nextStepCompleted
                    ? "Seluruh {$totalStandardCount} slot badge Eksklusif (1 badge per 5.000 {$unit}) telah diklaim."
                    : "Hadiah Pilih 1 badge Eksklusif (Slot {$nextSlotNumber} dari {$totalStandardCount} — setiap kelipatan 5.000 {$unit} membuka 1 badge).",
                'reached' => $nextStepReached,
                'completed' => $nextStepCompleted,
                'percent' => $hasFullAccess ? 100.0 : min(100.0, round(($missionCount / $nextStepTarget) * 100, 1)),
            ],
            [
                'key' => (string) $maxExclusiveTarget,
                'target' => $maxExclusiveTarget,
                'title' => 'Koleksi '.number_format($maxExclusiveTarget, 0, ',', '.').' '.$unit,
                'reward_label' => "Membuka seluruh {$totalStandardCount} badge Eksklusif ({$claimedStandardCount}/{$totalStandardCount} terbuka, 1 badge setiap 5.000 {$unit}).",
                'reached' => $hasFullAccess || $missionCount >= $maxExclusiveTarget,
                'completed' => $hasFullAccess || $claimedStandardCount >= $totalStandardCount,
                'percent' => $hasFullAccess ? 100.0 : min(100.0, round(($missionCount / $maxExclusiveTarget) * 100, 1)),
            ],
            [
                'key' => (string) self::MILESTONE_TIER_3,
                'target' => self::MILESTONE_TIER_3,
                'title' => 'Misi Tertinggi 50.000 '.$unit,
                'reward_label' => "Hadiah Tier Tertinggi (3 Badge): {$mythicNames}",
                'reached' => $hasFullAccess || $missionCount >= self::MILESTONE_TIER_3,
                'completed' => $hasFullAccess || count($claimedMythicBadges) >= count($mythicKeys),
                'percent' => $hasFullAccess ? 100.0 : min(100.0, round(($missionCount / self::MILESTONE_TIER_3) * 100, 1)),
            ],
        ];

        return [
            'track' => $profile['track'],
            'role_label' => $profile['role_label'],
            'mission_title' => $profile['mission_title'],
            'mission_description' => $profile['mission_description'],
            'unit_label' => $unit,
            'mission_count' => $missionCount,
            'do_count' => $missionCount,
            'breakdown_text' => $metrics['breakdown_text'],
            'exclusive_step' => self::MILESTONE_EXCLUSIVE_STEP,
            'earned_standard_slots' => $earnedStandardSlots,
            'total_standard_slots' => $totalStandardCount,
            'has_full_access' => $hasFullAccess,
            'claimed_badges' => $claimedBadges,
            'pending_choice_slots' => $pendingChoiceSlots,
            'claimable_choice_badges' => $claimableChoiceBadges,
            'claimable_mythic_badges' => $claimableMythicBadges,
            'can_claim_solar_aurora' => $canClaimSolarAurora,
            'has_any_pending_gift' => $claimableChoiceBadges !== [] || $claimableMythicBadges !== [],
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
            // Jangan sampai pengecekan hadiah mengganggu penyimpanan transaksi utama.
        }
    }

    /**
     * Memeriksa apakah user baru mencapai milestone 1.000 / 5.000 / 50.000 sesuai role dan mengirim Gift Notification sekali per tier.
     *
     * @return list<string> Daftar key milestone yang baru dikirim notifikasinya
     */
    public function syncMilestonesAndNotify(User $user, ?int $doCountOverride = null): array
    {
        if ($user->hasFullExclusiveBadgeAccess()) {
            return [];
        }

        $progress = $this->getProgress($user, $doCountOverride);
        $count = $progress['mission_count'];
        $unit = $progress['unit_label'];
        $alreadyNotified = is_array($user->badge_milestone_notified)
            ? array_values(array_unique(array_map('strval', $user->badge_milestone_notified)))
            : [];

        $newlyNotified = [];
        $profileUrl = $this->resolveProfileUrl();

        // Kelipatan 5.000 (5.000, 10.000, 15.000, 20.000, 25.000, 30.000): masing-masing buka 1 badge Eksklusif
        $exclusiveTargets = $this->exclusiveMilestoneTargets();
        $totalExclusiveSlots = count($exclusiveTargets);

        foreach ($exclusiveTargets as $index => $target) {
            $milestoneKey = (string) $target;
            if ($count >= $target && ! in_array($milestoneKey, $alreadyNotified, true)) {
                $alreadyNotified[] = $milestoneKey;
                $newlyNotified[] = $milestoneKey;
                $slotNumber = $index + 1;
                $formattedTarget = number_format($target, 0, ',', '.');

                $this->sendGiftNotification(
                    user: $user,
                    title: "Hadiah Misi {$formattedTarget} {$unit}",
                    body: 'Selamat '.$user->name.'! Anda telah mencapai '.number_format($count, 0, ',', '.')." {$unit}. Ambil hadiah di Pengaturan Profil untuk memilih 1 badge Eksklusif (Slot {$slotNumber} dari {$totalExclusiveSlots}).",
                    profileUrl: $profileUrl,
                );
            }
        }

        // Tier 3: 50.000 (Tier Tertinggi: Solar Aurora, Emerald Sovereign, Crimson Eclipse)
        $tier3Key = (string) self::MILESTONE_TIER_3;
        if ($count >= self::MILESTONE_TIER_3 && ! in_array($tier3Key, $alreadyNotified, true)) {
            $alreadyNotified[] = $tier3Key;
            $newlyNotified[] = $tier3Key;
            $mythicList = implode(', ', User::mythicBadgeOptions());
            $this->sendGiftNotification(
                user: $user,
                title: "Hadiah Tier Tertinggi: 50.000 {$unit}",
                body: 'Pencapaian luar biasa, '.$user->name.'! Anda telah mencapai '.number_format($count, 0, ',', '.')." {$unit} dan berhak mengklaim 3 badge Tier Tertinggi ({$mythicList}).",
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
     * Mengklaim hadiah badge eksklusif atau tier tertinggi berdasarkan pencapaian misi role pengguna.
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

        $isEligible = match (true) {
            User::isStandardExclusiveBadgeStyle($badgeStyle) => in_array($badgeStyle, $progress['claimable_choice_badges'], true),
            User::isMythicBadgeStyle($badgeStyle) => in_array($badgeStyle, $progress['claimable_mythic_badges'], true),
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
