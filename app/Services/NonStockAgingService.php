<?php

namespace App\Services;

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Enums\AgingZone;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\MaterialIssue;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use App\Models\NonStockAgingSnapshot;
use App\Models\RequestingUnit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NonStockAgingService
{
    /**
     * @var list<string>
     */
    public const array TRACKED_MRP_TYPES = ['NONSTOCK', 'PD'];

    /**
     * Normalisasi kunci PO-Item ke format standar "53000xxxxx-10".
     */
    public function normalizePoByItem(?string $poNumber, int|string|null $itemNo = null, ?string $poByItem = null): ?string
    {
        $rawKey = trim((string) $poByItem);

        if ($rawKey !== '' && preg_match('/^(\d+)\s*-\s*(\d+)$/', $rawKey, $matches) === 1) {
            return $matches[1].'-'.((int) $matches[2]);
        }

        $poClean = preg_replace('/\.0+$/', '', trim((string) $poNumber));
        if (is_string($poClean) && is_numeric($poClean) && str_contains(strtoupper($poClean), 'E')) {
            $poClean = sprintf('%.0f', (float) $poClean);
        }

        $itemInt = (int) ((float) trim((string) $itemNo));
        if (is_string($poClean) && $poClean !== '' && $itemInt > 0) {
            return $poClean.'-'.$itemInt;
        }

        return null;
    }

    /**
     * Normalisasi nomor telepon Indonesia menjadi format WhatsApp "628xxxx".
     */
    public function normalizePhoneToWa(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (! is_string($digits) || strlen($digits) < 9) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Memisahkan nama dan nomor HP (08xx / +628xx) dari teks kontak bebas di spreadsheet.
     *
     * @return array{name: ?string, phone: ?string}
     */
    public function extractNameAndPhone(?string $rawContact): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', (string) $rawContact) ?? '');
        if ($clean === '' || $clean === '-') {
            return ['name' => null, 'phone' => null];
        }

        $phone = null;
        if (preg_match('/(\+?62|0)[\s\-]?8[\d\s\-]{7,15}/', $clean, $matches) === 1) {
            $digits = preg_replace('/\D+/', '', $matches[0]);
            if (is_string($digits) && strlen($digits) >= 9) {
                if (str_starts_with($digits, '62')) {
                    $phone = '0'.substr($digits, 2);
                } else {
                    $phone = $digits;
                }
            }
            $clean = trim(str_replace($matches[0], '', $clean), " \t\n\r\0\x0B-/(),");
        }

        return [
            'name' => $clean !== '' ? $clean : null,
            'phone' => $phone,
        ];
    }

    /**
     * Mencocokkan teks requisitioner SAP ke Master RequestingUnit menggunakan prioritas alias terpanjang.
     *
     * @param  Collection<int, RequestingUnit>|null  $units
     */
    public function matchRequestingUnit(?string $requisitioner, ?Collection $units = null): ?RequestingUnit
    {
        $needle = strtoupper(trim((string) $requisitioner));
        if ($needle === '') {
            return null;
        }

        $activeUnits = $units ?? RequestingUnit::query()->where('is_active', true)->get();
        $bestUnit = null;
        $bestLength = 0;

        foreach ($activeUnits as $unit) {
            $candidates = $unit->normalizedAliases();
            $unitName = strtoupper(trim($unit->name));
            if ($unitName !== '') {
                $candidates[] = $unitName;
            }

            foreach ($candidates as $alias) {
                if ($alias === '') {
                    continue;
                }

                $matched = $needle === $alias || str_contains($needle, $alias);

                if ($matched && strlen($alias) > $bestLength) {
                    $bestLength = strlen($alias);
                    $bestUnit = $unit;
                }
            }
        }

        return $bestUnit;
    }

    /**
     * Menyinkronkan data DeliveryOrderReceiptDetail (NONSTOCK & PD, bukan RDTV) ke nonstock_aging_controls.
     *
     * @return array{created: int, updated: int, auto_closed: int, mapped: int}
     */
    public function syncFromDeliveryOrderDetails(): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'auto_closed' => 0,
            'mapped' => 0,
        ];

        $units = RequestingUnit::query()->where('is_active', true)->get();

        $details = DeliveryOrderReceiptDetail::query()
            ->with([
                'deliveryOrderReceipt.grsRdtvItems.grsRdtv',
                'purchaseOrderIssued',
                'locationReceiving',
                'materialIssueDetails',
                'nonStockAgingControl',
            ])
            ->whereIn('mrp_type', self::TRACKED_MRP_TYPES)
            ->whereHas('deliveryOrderReceipt', function ($query): void {
                $query->where(function ($sub): void {
                    $sub->whereNull('status')
                        ->orWhere('status', '!=', 'RDTV');
                });
            })
            ->get();

        foreach ($details as $detail) {
            $do = $detail->deliveryOrderReceipt;
            $po = $detail->purchaseOrderIssued;
            $poByItem = $this->normalizePoByItem(
                $po?->purchase_order_no,
                $detail->item_no ?? $po?->item_no,
                $po?->purchase_order_and_item
            );

            $issuedQty = (float) $detail->materialIssueDetails->sum('diserahkan');
            $isFullyIssued = (float) $detail->quantity > 0 && $issuedQty >= (float) $detail->quantity;

            $grsDate = null;
            if ($do !== null) {
                $grsDate = $do->grsRdtvItems
                    ->filter(fn ($item): bool => strtoupper((string) $item->grsRdtv?->category) === 'GRS' && $item->grsRdtv?->transaction_date !== null)
                    ->map(fn ($item): Carbon => $item->grsRdtv->transaction_date)
                    ->sort()
                    ->first();
            }

            $isGrsDone = $grsDate !== null || strtoupper((string) $do?->status) === 'GRS';
            $t0Date = $grsDate ?? $do?->physical_received_date ?? $do?->received_date;

            $control = $detail->nonStockAgingControl;

            if ($control === null && $poByItem !== null) {
                // Jika sebelumnya sudah ada record impor historis dengan PO-Item sama tanpa DO Detail, tautkan.
                $control = NonStockAgingControl::query()
                    ->whereNull('delivery_order_receipt_detail_id')
                    ->where('po_by_item', $poByItem)
                    ->first();
            }

            $matchedUnit = $this->matchRequestingUnit(
                $detail->requisitioner ?? $po?->requisitioner,
                $units
            );

            if ($control === null) {
                $defaultStatus = $isFullyIssued
                    ? AgingOperationalStatus::SelesaiDiambil
                    : ($isGrsDone ? AgingOperationalStatus::BelumDiambil : AgingOperationalStatus::OnCheck);

                $control = NonStockAgingControl::create([
                    'delivery_order_receipt_detail_id' => $detail->id,
                    'purchase_order_issued_id' => $detail->purchase_order_issued_id,
                    'po_by_item' => $poByItem,
                    'data_source' => 'web_do',
                    'mrp_type_snapshot' => strtoupper((string) $detail->mrp_type),
                    'material_type_snapshot' => $detail->material_type,
                    'material_code_snapshot' => $detail->material_code,
                    'description_snapshot' => $detail->description,
                    'vendor_name_snapshot' => $po?->vendor_name,
                    'uoi_snapshot' => $detail->uoi,
                    'requisitioner_raw' => $detail->requisitioner ?? $po?->requisitioner,
                    'grs_posting_date' => $t0Date?->toDateString(),
                    'initial_qty_gr' => (float) $detail->quantity,
                    'manual_qty_issued' => $issuedQty,
                    'initial_value_lc' => (float) ($detail->total_amount_snapshot ?? 0),
                    'location_text' => $detail->locationReceiving?->name,
                    'requesting_unit_id' => $matchedUnit?->id,
                    'operational_status' => $defaultStatus,
                    'followup_stage' => $isFullyIssued ? AgingFollowupStage::Selesai : AgingFollowupStage::BelumDitindaklanjuti,
                    'physical_checked' => $isGrsDone || $isFullyIssued,
                ]);

                $stats['created']++;
                if ($matchedUnit !== null) {
                    $stats['mapped']++;
                }

                continue;
            }

            $dirty = false;

            if ($control->delivery_order_receipt_detail_id === null) {
                $control->delivery_order_receipt_detail_id = $detail->id;
                $dirty = true;
            }

            if ($control->purchase_order_issued_id === null && $detail->purchase_order_issued_id !== null) {
                $control->purchase_order_issued_id = $detail->purchase_order_issued_id;
                $dirty = true;
            }

            if ($grsDate !== null && ($control->grs_posting_date === null || $control->grs_posting_date->toDateString() !== $grsDate->toDateString())) {
                $control->grs_posting_date = $grsDate;
                $dirty = true;
            }

            if ($control->requesting_unit_id === null && $matchedUnit !== null) {
                $control->requesting_unit_id = $matchedUnit->id;
                $stats['mapped']++;
                $dirty = true;
            }

            if (blank($control->location_text) && filled($detail->locationReceiving?->name)) {
                $control->location_text = $detail->locationReceiving->name;
                $dirty = true;
            }

            if ($isFullyIssued && $control->operational_status !== AgingOperationalStatus::SelesaiDiambil) {
                $control->operational_status = AgingOperationalStatus::SelesaiDiambil;
                $control->followup_stage = AgingFollowupStage::Selesai;
                $control->manual_qty_issued = max((float) $control->manual_qty_issued, $issuedQty);
                $control->save();

                NonStockAgingLog::create([
                    'nonstock_aging_control_id' => $control->id,
                    'action_type' => 'auto_closed_mir',
                    'aging_days_snapshot' => $control->resolvedAgingDays(),
                    'aging_zone_snapshot' => $control->resolvedAgingZone()->value,
                    'outstanding_value_snapshot' => 0,
                    'channel' => 'Sistem',
                    'recipient_level' => 'Internal',
                    'notes' => 'Otomatis ditutup karena total pengambilan MIR sudah memenuhi kuantitas penerimaan.',
                ]);

                $stats['auto_closed']++;

                continue;
            }

            if ($dirty) {
                $control->save();
                $stats['updated']++;
            }
        }

        return $stats;
    }

    /**
     * Memetakan ulang item aging ke Master RequestingUnit berdasarkan alias requisitioner.
     */
    public function syncRequestingUnitMappings(bool $onlyUnmapped = true): int
    {
        $units = RequestingUnit::query()->where('is_active', true)->get();
        if ($units->isEmpty()) {
            return 0;
        }

        $query = NonStockAgingControl::query()
            ->with(['deliveryOrderReceiptDetail', 'purchaseOrderIssued']);

        if ($onlyUnmapped) {
            $query->whereNull('requesting_unit_id');
        }

        $mappedCount = 0;

        foreach ($query->get() as $control) {
            $matched = $this->matchRequestingUnit($control->resolvedRequisitioner(), $units);
            if ($matched !== null && $control->requesting_unit_id !== $matched->id) {
                $control->requesting_unit_id = $matched->id;
                $control->save();
                $mappedCount++;
            }
        }

        return $mappedCount;
    }

    /**
     * Menarik kandidat kontak PIC (departemen, nama, no HP) dari riwayat transaksi MIR.
     *
     * @return list<array{departemen: string, bagian: string, diminta_oleh: string, no_hp: string, total_mir: int}>
     */
    public function extractContactsFromMirHistory(int $limit = 50): array
    {
        return MaterialIssue::query()
            ->selectRaw('COALESCE(departemen, "-") as departemen, COALESCE(bagian, "-") as bagian, COALESCE(diminta_oleh, "-") as diminta_oleh, COALESCE(no_hp, "-") as no_hp, COUNT(*) as total_mir')
            ->where(function ($query): void {
                $query->whereNotNull('diminta_oleh')
                    ->where('diminta_oleh', '!=', '');
            })
            ->groupBy('departemen', 'bagian', 'diminta_oleh', 'no_hp')
            ->orderByDesc('total_mir')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'departemen' => (string) $row->departemen,
                'bagian' => (string) $row->bagian,
                'diminta_oleh' => (string) $row->diminta_oleh,
                'no_hp' => (string) $row->no_hp,
                'total_mir' => (int) $row->total_mir,
            ])
            ->values()
            ->all();
    }

    /**
     * Menghitung ringkasan KPI LEAD 2026, distribusi 4 zona aging, analisa Pareto 80/20, dan perbandingan Baseline.
     *
     * @return array{
     *     mrp_scope: string,
     *     pilot_only: bool,
     *     as_of: string,
     *     outstanding_items: int,
     *     outstanding_po: int,
     *     outstanding_value: float,
     *     on_check_items: int,
     *     on_check_po: int,
     *     on_check_value: float,
     *     scheduled_hold_items: int,
     *     scheduled_hold_value: float,
     *     unattended_critical_items: int,
     *     unattended_critical_value: float,
     *     pic_mapped_count: int,
     *     pic_mapped_percentage: float,
     *     warning_critical_total: int,
     *     warning_critical_followed_up: int,
     *     warning_critical_followed_up_percentage: float,
     *     zones: array<string, array{items: int, po_count: int, value: float}>,
     *     pareto: list<array{unit_id: ?int, unit_name: string, is_pilot: bool, items: int, po_count: int, value: float, share_pct: float, cumulative_pct: float}>,
     *     baseline_comparison: ?array{baseline_label: string, baseline_date: string, baseline_items: int, baseline_value: float, item_reduction_pct: float, value_reduction_pct: float, item_target_met: bool, value_target_met: bool}
     * }
     */
    public function buildSummaryMetrics(string $mrpScope = 'NONSTOCK', ?Carbon $asOf = null, bool $pilotOnly = false): array
    {
        $reference = ($asOf ?? now())->copy()->startOfDay();
        $scopeUpper = strtoupper(trim($mrpScope));

        $controls = NonStockAgingControl::query()
            ->with([
                'deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv',
                'deliveryOrderReceiptDetail.materialIssueDetails',
                'deliveryOrderReceiptDetail.purchaseOrderIssued',
                'purchaseOrderIssued',
                'requestingUnit',
            ])
            ->whereIn('operational_status', [
                AgingOperationalStatus::BelumDiambil->value,
                AgingOperationalStatus::ScheduledHold->value,
                AgingOperationalStatus::OnCheck->value,
            ])
            ->when($pilotOnly, function ($query): void {
                $query->whereHas('requestingUnit', fn ($sub) => $sub->where('is_pilot', true));
            })
            ->get()
            ->filter(function (NonStockAgingControl $control) use ($scopeUpper): bool {
                $mrp = $control->resolvedMrpType();

                if ($scopeUpper === 'ALL') {
                    return true;
                }

                if ($scopeUpper === 'NONSTOCK_PD') {
                    return in_array($mrp, self::TRACKED_MRP_TYPES, true);
                }

                return $mrp === $scopeUpper;
            });

        $zones = [];
        $zonePos = [];
        foreach (AgingZone::cases() as $zone) {
            $zones[$zone->value] = ['items' => 0, 'po_count' => 0, 'value' => 0.0];
            $zonePos[$zone->value] = [];
        }

        $outstandingItems = 0;
        $outstandingPos = [];
        $outstandingValue = 0.0;

        $onCheckItems = 0;
        $onCheckPos = [];
        $onCheckValue = 0.0;

        $scheduledHoldItems = 0;
        $scheduledHoldValue = 0.0;
        $unattendedCriticalItems = 0;
        $unattendedCriticalValue = 0.0;

        $picMappedCount = 0;
        $warningCriticalTotal = 0;
        $warningCriticalFollowedUp = 0;

        $unitAccumulator = [];

        foreach ($controls as $control) {
            $value = $control->resolvedOutstandingValue();
            $poNo = $control->resolvedPoNumber();

            if ($control->operational_status === AgingOperationalStatus::OnCheck) {
                $onCheckItems++;
                $onCheckPos[$poNo] = true;
                $onCheckValue += $value;

                continue;
            }

            $zone = $control->resolvedAgingZone($reference);

            $outstandingItems++;
            $outstandingPos[$poNo] = true;
            $outstandingValue += $value;

            $zones[$zone->value]['items']++;
            $zones[$zone->value]['value'] += $value;
            $zonePos[$zone->value][$poNo] = true;

            if ($control->operational_status === AgingOperationalStatus::ScheduledHold) {
                $scheduledHoldItems++;
                $scheduledHoldValue += $value;
            } elseif ($zone === AgingZone::Critical) {
                $unattendedCriticalItems++;
                $unattendedCriticalValue += $value;
            }

            if ($control->hasMappedPic()) {
                $picMappedCount++;
            }

            if (in_array($zone, [AgingZone::Warning, AgingZone::Critical], true)) {
                $warningCriticalTotal++;
                if ($control->followup_stage instanceof AgingFollowupStage && $control->followup_stage->isFollowedUp()) {
                    $warningCriticalFollowedUp++;
                }
            }

            $unitKey = $control->requesting_unit_id !== null ? 'unit_'.$control->requesting_unit_id : 'unmapped';
            if (! isset($unitAccumulator[$unitKey])) {
                $unitAccumulator[$unitKey] = [
                    'unit_id' => $control->requesting_unit_id,
                    'unit_name' => $control->requestingUnit?->name ?? 'Belum Dipetakan',
                    'is_pilot' => (bool) ($control->requestingUnit?->is_pilot ?? false),
                    'items' => 0,
                    'pos' => [],
                    'value' => 0.0,
                ];
            }
            $unitAccumulator[$unitKey]['items']++;
            $unitAccumulator[$unitKey]['pos'][$poNo] = true;
            $unitAccumulator[$unitKey]['value'] += $value;
        }

        foreach (AgingZone::cases() as $zone) {
            $zones[$zone->value]['po_count'] = count($zonePos[$zone->value]);
            $zones[$zone->value]['value'] = round($zones[$zone->value]['value'], 2);
        }

        uasort($unitAccumulator, function (array $a, array $b): int {
            if ($b['value'] === $a['value']) {
                return $b['items'] <=> $a['items'];
            }

            return $b['value'] <=> $a['value'];
        });

        $pareto = [];
        $cumulative = 0.0;

        foreach ($unitAccumulator as $row) {
            $share = $outstandingValue > 0 ? ($row['value'] / $outstandingValue) * 100 : 0.0;
            $cumulative += $share;

            $pareto[] = [
                'unit_id' => $row['unit_id'],
                'unit_name' => $row['unit_name'],
                'is_pilot' => $row['is_pilot'],
                'items' => $row['items'],
                'po_count' => count($row['pos']),
                'value' => round($row['value'], 2),
                'share_pct' => round($share, 1),
                'cumulative_pct' => round(min(100.0, $cumulative), 1),
            ];
        }

        $picMappedPct = $outstandingItems > 0 ? round(($picMappedCount / $outstandingItems) * 100, 1) : 100.0;
        $wcFollowupPct = $warningCriticalTotal > 0 ? round(($warningCriticalFollowedUp / $warningCriticalTotal) * 100, 1) : 100.0;

        $baseline = NonStockAgingSnapshot::query()
            ->where('mrp_scope', $scopeUpper)
            ->where('scope', $pilotOnly ? 'pilot_only' : 'all')
            ->orderBy('snapshot_date')
            ->orderBy('id')
            ->first();

        $baselineComparison = null;
        if ($baseline !== null) {
            $itemRed = $baseline->total_outstanding_items > 0
                ? round((($baseline->total_outstanding_items - $outstandingItems) / $baseline->total_outstanding_items) * 100, 1)
                : 0.0;
            $valRed = $baseline->total_outstanding_value > 0
                ? round((($baseline->total_outstanding_value - $outstandingValue) / $baseline->total_outstanding_value) * 100, 1)
                : 0.0;

            $baselineComparison = [
                'baseline_label' => $baseline->label,
                'baseline_date' => $baseline->snapshot_date->format('d M Y'),
                'baseline_items' => $baseline->total_outstanding_items,
                'baseline_value' => round($baseline->total_outstanding_value, 2),
                'item_reduction_pct' => $itemRed,
                'value_reduction_pct' => $valRed,
                'item_target_met' => $itemRed >= 25.0,
                'value_target_met' => $valRed >= 20.0,
            ];
        }

        return [
            'mrp_scope' => $scopeUpper,
            'pilot_only' => $pilotOnly,
            'as_of' => $reference->toDateString(),
            'outstanding_items' => $outstandingItems,
            'outstanding_po' => count($outstandingPos),
            'outstanding_value' => round($outstandingValue, 2),
            'on_check_items' => $onCheckItems,
            'on_check_po' => count($onCheckPos),
            'on_check_value' => round($onCheckValue, 2),
            'scheduled_hold_items' => $scheduledHoldItems,
            'scheduled_hold_value' => round($scheduledHoldValue, 2),
            'unattended_critical_items' => $unattendedCriticalItems,
            'unattended_critical_value' => round($unattendedCriticalValue, 2),
            'pic_mapped_count' => $picMappedCount,
            'pic_mapped_percentage' => $picMappedPct,
            'warning_critical_total' => $warningCriticalTotal,
            'warning_critical_followed_up' => $warningCriticalFollowedUp,
            'warning_critical_followed_up_percentage' => $wcFollowupPct,
            'zones' => $zones,
            'pareto' => $pareto,
            'baseline_comparison' => $baselineComparison,
        ];
    }
}
