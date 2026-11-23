<?php

namespace App\Console\Commands;

use App\Enums\AgingOperationalStatus;
use App\Enums\AgingZone;
use App\Models\NonStockAgingControl;
use App\Models\User;
use App\Services\NonStockAgingService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckNonStockAgingAlertsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'nonstock:check-aging-alerts {--skip-sync : Lewati sinkronisasi DO Web 2.0 sebelum pengecekan alert}';

    /**
     * @var string
     */
    protected $description = 'Sinkronisasi harian data Aging Non-Stock/PD dan pengiriman notifikasi In-App internal untuk tim Receiving (tanpa pengiriman email otomatis).';

    public function handle(NonStockAgingService $agingService): int
    {
        if (! (bool) $this->option('skip-sync')) {
            $syncStats = $agingService->syncFromDeliveryOrderDetails();
            $this->info("Sinkronisasi DO Web selesai — Baru: {$syncStats['created']}, Diperbarui: {$syncStats['updated']}, Auto-Close MIR: {$syncStats['auto_closed']}.");
        }

        $controls = NonStockAgingControl::query()
            ->with([
                'deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv',
                'deliveryOrderReceiptDetail.materialIssueDetails',
                'purchaseOrderIssued',
            ])
            ->whereIn('operational_status', [
                AgingOperationalStatus::BelumDiambil->value,
                AgingOperationalStatus::ScheduledHold->value,
            ])
            ->get();

        $criticalPendingVp = 0;
        $warningPendingAvp = 0;
        $attentionPendingPic = 0;
        $overdueCommitment = 0;

        foreach ($controls as $control) {
            $zone = $control->resolvedAgingZone();

            if ($control->isOverduePlannedPickup()) {
                $overdueCommitment++;
            }

            if ($zone === AgingZone::Critical && $control->vp_escalated_at === null) {
                $criticalPendingVp++;
            } elseif ($zone === AgingZone::Warning && $control->avp_escalated_at === null) {
                $warningPendingAvp++;
            } elseif ($zone === AgingZone::Attention && (int) $control->reminder_count === 0) {
                $attentionPendingPic++;
            }
        }

        $totalAlerts = $criticalPendingVp + $warningPendingAvp + $attentionPendingPic + $overdueCommitment;

        if ($totalAlerts === 0) {
            $this->info('Tidak ada item aging yang memerlukan notifikasi In-App hari ini.');

            return self::SUCCESS;
        }

        $parts = [];
        if ($criticalPendingVp > 0) {
            $parts[] = "{$criticalPendingVp} item Critical (>60H) belum dieskalasi VP";
        }
        if ($warningPendingAvp > 0) {
            $parts[] = "{$warningPendingAvp} item Warning (31–60H) belum dikonfirmasi AVP";
        }
        if ($attentionPendingPic > 0) {
            $parts[] = "{$attentionPendingPic} item Attention (15–30H) belum di-reminder PIC";
        }
        if ($overdueCommitment > 0) {
            $parts[] = "{$overdueCommitment} item melewati komitmen tanggal ambil";
        }

        $recipients = User::query()->where('is_active', true)->get();

        if ($recipients->isNotEmpty()) {
            Notification::make()
                ->title('Pengingat Harian Aging Material Non-Stock & PD')
                ->body(implode(' • ', $parts).'. Silakan tinjau di menu Monitoring Receiving dan kirim reminder/eskalasi secara manual.')
                ->warning()
                ->sendToDatabase($recipients);
        }

        $this->info("Notifikasi In-App terkirim ke {$recipients->count()} pengguna aktif: ".implode(', ', $parts));

        return self::SUCCESS;
    }
}
