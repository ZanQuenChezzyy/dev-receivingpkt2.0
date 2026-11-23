<?php

namespace App\Services;

use App\Enums\AgingFollowupStage;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class NonStockAgingMailDispatcher
{
    public function __construct(
        public ZimbraMailService $zimbra,
        public EmailTemplateRenderer $renderer,
        public NonStockAgingService $agingService,
    ) {}

    /**
     * Mengirim email notifikasi awal / reminder tahap 1 / rekap mingguan secara manual ke PIC Unit Peminta.
     *
     * @param  Collection<int, NonStockAgingControl>  $controls
     * @return array{status: string, message: string, sent_count: int, test_mode: bool}
     */
    public function sendPicReminder(
        Collection $controls,
        string $mode = 'reminder_stage_1',
        ?string $additionalNote = null,
        ?string $customTo = null,
        ?int $actorId = null,
    ): array {
        $actionType = match ($mode) {
            'initial_notification' => 'initial_notification',
            'weekly_digest' => 'weekly_digest',
            default => 'reminder_stage_1',
        };

        $subjectPrefix = match ($mode) {
            'initial_notification' => 'Informasi Kedatangan Material Non-Stock / PD Siap Diambil',
            'weekly_digest' => 'Rekap Mingguan Material Non-Stock / PD Belum Diambil',
            default => 'Reminder Pengambilan Material Non-Stock / PD (Tahap 1)',
        };

        $stageAfter = match ($mode) {
            'initial_notification' => AgingFollowupStage::NotifikasiAwal,
            default => AgingFollowupStage::ReminderTahap1,
        };

        return $this->dispatchBatch(
            controls: $controls,
            level: 'PIC',
            actionType: $actionType,
            subjectPrefix: $subjectPrefix,
            stageAfter: $stageAfter,
            additionalNote: $additionalNote,
            customTo: $customTo,
            actorId: $actorId,
        );
    }

    /**
     * Mengirim email permintaan konfirmasi kendala & komitmen tanggal ambil ke AVP Unit Peminta (Warning 31-60H).
     *
     * @param  Collection<int, NonStockAgingControl>  $controls
     * @return array{status: string, message: string, sent_count: int, test_mode: bool}
     */
    public function sendAvpWarningConfirmation(
        Collection $controls,
        ?string $additionalNote = null,
        ?string $customTo = null,
        ?int $actorId = null,
    ): array {
        return $this->dispatchBatch(
            controls: $controls,
            level: 'AVP',
            actionType: 'avp_commitment_warning',
            subjectPrefix: 'Permintaan Konfirmasi & Komitmen Pengambilan Material Aging (31–60 Hari)',
            stageAfter: AgingFollowupStage::KonfirmasiAvp,
            additionalNote: $additionalNote,
            customTo: $customTo,
            actorId: $actorId,
        );
    }

    /**
     * Mengirim email eskalasi formal lintas departemen ke VP Unit Peminta (Critical >60H).
     *
     * @param  Collection<int, NonStockAgingControl>  $controls
     * @return array{status: string, message: string, sent_count: int, test_mode: bool}
     */
    public function sendVpCriticalEscalation(
        Collection $controls,
        ?string $additionalNote = null,
        ?string $customTo = null,
        ?int $actorId = null,
    ): array {
        return $this->dispatchBatch(
            controls: $controls,
            level: 'VP',
            actionType: 'vp_escalation_critical',
            subjectPrefix: 'Eskalasi Formal Material Non-Stock / PD Critical (> 60 Hari)',
            stageAfter: AgingFollowupStage::EskalasiVp,
            additionalNote: $additionalNote,
            customTo: $customTo,
            actorId: $actorId,
        );
    }

    /**
     * Menyusun teks pesan WhatsApp standar (bersih, ringkas, profesional) untuk 1 atau beberapa item.
     *
     * @param  Collection<int, NonStockAgingControl>  $controls
     */
    public function buildWhatsAppMessage(Collection $controls, string $level = 'PIC', ?string $unitName = null): string
    {
        $controls->loadMissing([
            'deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv',
            'deliveryOrderReceiptDetail.locationReceiving',
            'deliveryOrderReceiptDetail.materialIssueDetails',
            'deliveryOrderReceiptDetail.purchaseOrderIssued',
            'purchaseOrderIssued',
            'requestingUnit',
        ]);

        $first = $controls->first();
        $resolvedUnit = $unitName ?? $first?->requestingUnit?->name ?? 'Unit Peminta';

        $greeting = match ($level) {
            'AVP' => "Yth. AVP {$resolvedUnit},",
            'VP' => "Yth. VP {$resolvedUnit},",
            default => "Yth. PIC {$resolvedUnit},",
        };

        $intro = match ($level) {
            'AVP' => 'Mohon konfirmasi kendala dan komitmen jadwal pengambilan (GI/MIR) untuk material Non-Stock/PD berikut yang telah melampaui 30 hari di area Receiving Dept. PP&P:',
            'VP' => 'Mohon arahan dan keputusan penyelesaian atas material Non-Stock/PD kategori Critical (> 60 hari) berikut yang masih tertahan di area Receiving Dept. PP&P:',
            default => 'Menginformasikan bahwa material Non-Stock/PD berikut telah tersedia di area Receiving Dept. PP&P dan menunggu pengambilan (GI/MIR):',
        };

        $lines = [
            $greeting,
            '',
            $intro,
            '',
        ];

        $totalValue = 0.0;

        foreach ($controls->values() as $idx => $control) {
            $num = $idx + 1;
            $days = $control->resolvedAgingDays();
            $zone = $control->resolvedAgingZone()->shortLabel();
            $val = $control->resolvedOutstandingValue();
            $totalValue += $val;
            $valText = $val > 0 ? 'Rp '.number_format($val, 0, ',', '.') : '-';
            $grsDate = $control->resolvedGrsDate()?->format('d M Y') ?? '-';
            $loc = $control->resolvedLocation() ?? '-';
            $remQty = rtrim(rtrim(number_format($control->resolvedRemainingQuantity(), 2, ',', '.'), '0'), ',');
            $uoi = $control->deliveryOrderReceiptDetail?->uoi ?? $control->purchaseOrderIssued?->uoi ?? $control->uoi_snapshot ?? 'EA';

            $lines[] = "{$num}. PO {$control->po_by_item} — {$control->resolvedDescription()}";
            $lines[] = "   Sisa: {$remQty} {$uoi} | Lokasi: {$loc} | Tgl GRS: {$grsDate} | Umur: {$days} hari ({$zone}) | Nilai: {$valText}";
        }

        $lines[] = '';
        $lines[] = 'Total: '.$controls->count().' item | Estimasi Nilai Tertahan: Rp '.number_format($totalValue, 0, ',', '.');
        $lines[] = 'Mohon konfirmasi rencana tanggal pengambilannya agar dapat kami catat pada sistem Monitoring Receiving PKT.';
        $lines[] = '';
        $lines[] = 'Terima kasih.';
        $lines[] = 'Bagian Receiving — Departemen PP&P';
        $lines[] = 'PT Pupuk Kalimantan Timur';

        return implode("\n", $lines);
    }

    public function buildWhatsAppUrl(?string $phone, string $message): ?string
    {
        $waPhone = $this->agingService->normalizePhoneToWa($phone);
        if ($waPhone === null) {
            return null;
        }

        return 'https://wa.me/'.$waPhone.'?text='.rawurlencode($message);
    }

    /**
     * @param  Collection<int, NonStockAgingControl>  $controls
     * @return array{status: string, message: string, sent_count: int, test_mode: bool}
     */
    protected function dispatchBatch(
        Collection $controls,
        string $level,
        string $actionType,
        string $subjectPrefix,
        AgingFollowupStage $stageAfter,
        ?string $additionalNote,
        ?string $customTo,
        ?int $actorId,
    ): array {
        if ($controls->isEmpty()) {
            return [
                'status' => 'skipped',
                'message' => 'Tidak ada item yang dipilih untuk dikirim.',
                'sent_count' => 0,
                'test_mode' => false,
            ];
        }

        if (! $this->zimbra->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'Kredensial Zimbra (ZIMBRA_BASE_URL, ZIMBRA_USERNAME, ZIMBRA_PASSWORD) belum dikonfigurasi di server.',
                'sent_count' => 0,
                'test_mode' => false,
            ];
        }

        $controls->loadMissing([
            'deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv',
            'deliveryOrderReceiptDetail.locationReceiving',
            'deliveryOrderReceiptDetail.materialIssueDetails',
            'deliveryOrderReceiptDetail.purchaseOrderIssued',
            'purchaseOrderIssued',
            'requestingUnit',
        ]);

        $resolvedActorId = $actorId ?? Auth::id();
        $first = $controls->first();
        $unit = $first?->requestingUnit;
        $unitName = $unit?->name ?? 'Unit Peminta';

        [$intendedTo, $intendedCc, $recipientName] = $this->resolveRecipients($controls, $level, $customTo);

        $overrideTo = $this->renderer->resolveEffectiveTestOverrideTo();
        $overrideEmails = $overrideTo !== null
            ? array_values(array_filter(
                Vendor::parseEmails($overrideTo),
                static fn (string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL),
            ))
            : [];

        $isTestMode = $overrideEmails !== [];

        if (! $isTestMode && $intendedTo === []) {
            return [
                'status' => 'skipped',
                'message' => "Alamat email tujuan level {$level} untuk {$unitName} belum diisi atau tidak valid.",
                'sent_count' => 0,
                'test_mode' => false,
            ];
        }

        $actualTo = $isTestMode ? $overrideEmails : $intendedTo;
        $actualCc = $isTestMode ? [] : $intendedCc;

        $subject = "{$subjectPrefix} — {$unitName} ({$controls->count()} Item)";
        $finalSubject = $isTestMode ? '[UJI COBA] '.$subject : $subject;

        $bodyHtml = $this->renderItemsEmailBody($controls, $level, $unitName, $recipientName, $additionalNote);

        $testBannerHtml = null;
        if ($isTestMode) {
            $origToText = $intendedTo !== [] ? e(implode(', ', $intendedTo)) : '<em>(email tujuan kosong)</em>';
            $origCcText = $intendedCc !== [] ? ' | CC asli (ditahan saat uji coba): <code>'.e(implode(', ', $intendedCc)).'</code>' : '';
            $testBannerHtml = '<tr><td style="padding:12px 28px;background-color:#fffbeb;border-bottom:1px solid #fde68a;font-size:12px;color:#92400e;">'
                .'<strong>MODE UJI COBA AKTIF:</strong> Email dialihkan ke <code>'.e(implode(', ', $overrideEmails)).'</code>. '
                .'Tujuan asli ('.$level.'): '.$origToText.$origCcText.'.'
                .'</td></tr>';
        }

        $finalHtml = $this->renderer->wrapInCorporateLayout($bodyHtml, 'GRS', $testBannerHtml);

        try {
            $this->zimbra->sendMessage(
                to: $actualTo,
                subject: $finalSubject,
                htmlBody: $finalHtml,
                attachments: [],
                cc: $actualCc,
                fromName: $this->renderer->getSenderName(),
            );
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => 'error',
                'message' => 'Gagal mengirim email via Zimbra: '.$e->getMessage(),
                'sent_count' => 0,
                'test_mode' => $isTestMode,
            ];
        }

        $contactSummary = implode(', ', $actualTo);
        $now = now();

        foreach ($controls as $control) {
            $control->last_reminded_at = $now;
            $control->reminder_count = ((int) $control->reminder_count) + 1;

            if ($level === 'AVP') {
                $control->avp_escalated_at = $now;
            } elseif ($level === 'VP') {
                $control->vp_escalated_at = $now;
            }

            if ($control->followup_stage !== AgingFollowupStage::TerkonfirmasiJadwalAmbil && $control->followup_stage !== AgingFollowupStage::Selesai) {
                $control->followup_stage = $stageAfter;
            }

            if (filled($additionalNote)) {
                $control->last_followup_note = $additionalNote;
            }

            $control->save();

            NonStockAgingLog::create([
                'nonstock_aging_control_id' => $control->id,
                'action_type' => $actionType,
                'aging_days_snapshot' => $control->resolvedAgingDays(),
                'aging_zone_snapshot' => $control->resolvedAgingZone()->value,
                'outstanding_value_snapshot' => $control->resolvedOutstandingValue(),
                'channel' => 'Zimbra Email',
                'recipient_level' => $level,
                'recipient_name' => $recipientName,
                'recipient_contact' => $contactSummary,
                'email_sent' => true,
                'notes' => ($isTestMode ? '[UJI COBA] ' : '').($additionalNote ?: $subjectPrefix),
                'created_by' => $resolvedActorId !== null ? (int) $resolvedActorId : null,
            ]);
        }

        $ccNote = $actualCc !== [] ? ' | CC: '.implode(', ', $actualCc) : '';
        $modeNote = $isTestMode ? ' [Mode Uji Coba]' : '';

        return [
            'status' => 'sent',
            'message' => "Email berhasil dikirim ke {$contactSummary}{$ccNote} untuk {$controls->count()} item{$modeNote}.",
            'sent_count' => $controls->count(),
            'test_mode' => $isTestMode,
        ];
    }

    /**
     * @param  Collection<int, NonStockAgingControl>  $controls
     * @return array{0: list<string>, 1: list<string>, 2: string}
     */
    protected function resolveRecipients(Collection $controls, string $level, ?string $customTo): array
    {
        $first = $controls->first();
        $unit = $first?->requestingUnit;

        $picEmails = $controls
            ->map(fn (NonStockAgingControl $c): ?string => $c->resolvedPicEmail())
            ->filter(fn (?string $e): bool => filled($e))
            ->flatMap(fn (string $e): array => Vendor::parseEmails($e))
            ->filter(fn (string $e): bool => (bool) filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        $avpEmails = filled($unit?->avp_email)
            ? array_values(array_filter(Vendor::parseEmails($unit->avp_email), static fn (string $e): bool => (bool) filter_var($e, FILTER_VALIDATE_EMAIL)))
            : [];

        $vpEmails = filled($unit?->vp_email)
            ? array_values(array_filter(Vendor::parseEmails($unit->vp_email), static fn (string $e): bool => (bool) filter_var($e, FILTER_VALIDATE_EMAIL)))
            : [];

        $defaultCc = $this->renderer->resolveCcEmails();

        if (filled($customTo)) {
            $to = array_values(array_filter(Vendor::parseEmails($customTo), static fn (string $e): bool => (bool) filter_var($e, FILTER_VALIDATE_EMAIL)));
        } elseif ($level === 'VP') {
            $to = $vpEmails;
        } elseif ($level === 'AVP') {
            $to = $avpEmails;
        } else {
            $to = $picEmails;
        }

        $cc = match ($level) {
            'VP' => array_merge($avpEmails, $picEmails, $defaultCc),
            'AVP' => array_merge($picEmails, $defaultCc),
            default => $defaultCc,
        };

        $cc = array_values(array_diff(array_unique($cc), $to));

        $recipientName = match ($level) {
            'VP' => $unit?->vp_name ?: ($unit?->vp_title ?: 'VP '.($unit?->name ?? 'Unit Peminta')),
            'AVP' => $unit?->avp_name ?: ($unit?->avp_title ?: 'AVP '.($unit?->name ?? 'Unit Peminta')),
            default => $first?->resolvedPicName() ?: 'PIC '.($unit?->name ?? 'Unit Peminta'),
        };

        return [$to, $cc, $recipientName];
    }

    /**
     * @param  Collection<int, NonStockAgingControl>  $controls
     */
    protected function renderItemsEmailBody(
        Collection $controls,
        string $level,
        string $unitName,
        string $recipientName,
        ?string $additionalNote,
    ): string {
        $intro = match ($level) {
            'AVP' => 'Bersama ini kami sampaikan daftar material Non-Stock/PD yang telah melewati umur tunggu 30 hari pasca-GRS di area Receiving Departemen PP&P. Mohon konfirmasi kendala serta komitmen tertulis tanggal pengambilan (Goods Issue / MIR).',
            'VP' => 'Bersama ini kami sampaikan laporan eskalasi formal atas material Non-Stock/PD kategori Critical (> 60 hari pasca-GRS) yang masih tertahan di area Receiving Departemen PP&P. Mohon arahan dan keputusan tindak lanjut penyelesaiannya.',
            default => 'Bersama ini kami informasikan daftar material Non-Stock/PD untuk unit Anda yang telah tersedia di area Receiving Departemen PP&P dan menunggu proses pengambilan (Goods Issue / MIR).',
        };

        $rowsHtml = '';
        $totalValue = 0.0;

        foreach ($controls->values() as $idx => $control) {
            $num = $idx + 1;
            $days = $control->resolvedAgingDays();
            $zone = $control->resolvedAgingZone();
            $value = $control->resolvedOutstandingValue();
            $totalValue += $value;
            $valueText = $value > 0 ? 'Rp '.number_format($value, 0, ',', '.') : '-';
            $grsDate = $control->resolvedGrsDate()?->format('d M Y') ?? '-';
            $loc = $control->resolvedLocation() ?? '-';
            $remQty = rtrim(rtrim(number_format($control->resolvedRemainingQuantity(), 2, ',', '.'), '0'), ',');
            $uoi = $control->deliveryOrderReceiptDetail?->uoi ?? $control->purchaseOrderIssued?->uoi ?? $control->uoi_snapshot ?? 'EA';

            $badgeColor = match ($zone->value) {
                'Critical' => '#dc2626',
                'Warning' => '#d97706',
                'Attention' => '#0284c7',
                default => '#059669',
            };

            $rowsHtml .= '<tr>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;text-align:center;">'.$num.'</td>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;font-family:monospace;font-weight:600;">'.e($control->po_by_item ?? '-').'</td>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;">'.e($control->resolvedDescription()).'</td>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;text-align:center;">'.e("{$remQty} {$uoi}").'</td>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;text-align:center;">'.e($loc).'</td>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;text-align:center;">'.e($grsDate).'</td>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;text-align:center;"><span style="color:'.$badgeColor.';font-weight:700;">'.$days.' Hr ('.e($zone->value).')</span></td>'
                .'<td style="padding:8px;border:1px solid #e2e8f0;text-align:right;white-space:nowrap;">'.e($valueText).'</td>'
                .'</tr>';
        }

        $noteHtml = filled($additionalNote)
            ? '<div style="margin:16px 0;padding:12px 14px;background-color:#f8fafc;border-left:4px solid #F47920;font-size:13px;color:#334155;">'
                .'<strong>Catatan Tim Receiving:</strong><br>'.nl2br(e($additionalNote))
                .'</div>'
            : '';

        return '<p style="margin:0 0 12px 0;">Yth. <strong>'.e($recipientName).'</strong> ('.e($unitName).'),</p>'
            .'<p style="margin:0 0 16px 0;">'.e($intro).'</p>'
            .'<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:12px;margin-bottom:14px;">'
            .'<thead><tr style="background-color:#0f172a;color:#ffffff;">'
            .'<th style="padding:8px;border:1px solid #0f172a;">No</th>'
            .'<th style="padding:8px;border:1px solid #0f172a;">PO-Item</th>'
            .'<th style="padding:8px;border:1px solid #0f172a;text-align:left;">Deskripsi Material</th>'
            .'<th style="padding:8px;border:1px solid #0f172a;">Sisa Qty</th>'
            .'<th style="padding:8px;border:1px solid #0f172a;">Lokasi</th>'
            .'<th style="padding:8px;border:1px solid #0f172a;">Tgl GRS</th>'
            .'<th style="padding:8px;border:1px solid #0f172a;">Umur / Zona</th>'
            .'<th style="padding:8px;border:1px solid #0f172a;text-align:right;">Nilai (IDR)</th>'
            .'</tr></thead>'
            .'<tbody>'.$rowsHtml.'</tbody>'
            .'<tfoot><tr style="background-color:#f1f5f9;font-weight:700;">'
            .'<td colspan="7" style="padding:8px;border:1px solid #e2e8f0;text-align:right;">Total ('.$controls->count().' item)</td>'
            .'<td style="padding:8px;border:1px solid #e2e8f0;text-align:right;white-space:nowrap;">Rp '.number_format($totalValue, 0, ',', '.').'</td>'
            .'</tr></tfoot>'
            .'</table>'
            .$noteHtml
            .'<p style="margin:12px 0 0 0;">Mohon konfirmasi rencana tanggal pengambilan atau kendala lapangan kepada Bagian Receiving Departemen PP&amp;P.</p>'
            .'<p style="margin:14px 0 0 0;">Hormat kami,<br><strong>Bagian Receiving — Departemen PP&amp;P</strong><br>PT Pupuk Kalimantan Timur</p>';
    }
}
