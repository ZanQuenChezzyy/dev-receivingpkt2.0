<?php

namespace App\Services;

use App\Enums\AgingOperationalStatus;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingSnapshot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class NonStockAgingReportService
{
    public function __construct(
        protected NonStockAgingService $agingService,
    ) {}

    /**
     * Menyimpan snapshot KPI pada tanggal tertentu (mis. Baseline W1, Mid-Review W5, Final W8).
     */
    public function captureSnapshot(
        string $label,
        string $mrpScope = 'NONSTOCK',
        bool $pilotOnly = false,
        ?Carbon $asOf = null,
        ?int $actorId = null,
    ): NonStockAgingSnapshot {
        $reference = ($asOf ?? now())->copy()->startOfDay();
        $scopeUpper = strtoupper(trim($mrpScope));
        $summary = $this->agingService->buildSummaryMetrics($scopeUpper, $reference, $pilotOnly);
        $zones = $summary['zones'];

        return NonStockAgingSnapshot::create([
            'label' => trim($label),
            'snapshot_date' => $reference->toDateString(),
            'scope' => $pilotOnly ? 'pilot_only' : 'all',
            'mrp_scope' => $scopeUpper,
            'total_outstanding_items' => $summary['outstanding_items'],
            'total_outstanding_po' => $summary['outstanding_po'],
            'total_outstanding_value' => $summary['outstanding_value'],
            'on_check_items' => $summary['on_check_items'],
            'on_check_value' => $summary['on_check_value'],
            'normal_count' => $zones['Normal']['items'],
            'normal_value' => $zones['Normal']['value'],
            'attention_count' => $zones['Attention']['items'],
            'attention_value' => $zones['Attention']['value'],
            'warning_count' => $zones['Warning']['items'],
            'warning_value' => $zones['Warning']['value'],
            'critical_count' => $zones['Critical']['items'],
            'critical_value' => $zones['Critical']['value'],
            'scheduled_hold_count' => $summary['scheduled_hold_items'],
            'scheduled_hold_value' => $summary['scheduled_hold_value'],
            'unattended_critical_count' => $summary['unattended_critical_items'],
            'unattended_critical_value' => $summary['unattended_critical_value'],
            'pic_mapped_percentage' => $summary['pic_mapped_percentage'],
            'warning_critical_followed_up_percentage' => $summary['warning_critical_followed_up_percentage'],
            'unit_breakdown' => $summary['pareto'],
            'created_by' => $actorId ?? Auth::id(),
        ]);
    }

    /**
     * Menghitung perbandingan KPI Before vs. After antara Snapshot Baseline dan Snapshot Evaluasi.
     *
     * @return array{
     *     baseline: NonStockAgingSnapshot,
     *     evaluation: NonStockAgingSnapshot,
     *     item_delta: int,
     *     item_reduction_pct: float,
     *     item_target_met: bool,
     *     po_delta: int,
     *     po_reduction_pct: float,
     *     value_delta: float,
     *     value_reduction_pct: float,
     *     value_target_met: bool,
     *     on_check_item_delta: int,
     *     on_check_value_delta: float,
     *     critical_item_delta: int,
     *     critical_value_delta: float,
     *     followup_pct_delta: float,
     *     followup_target_met: bool,
     *     unit_deltas: list<array{unit_name: string, is_pilot: bool, before_items: int, after_items: int, before_value: float, after_value: float, value_reduction_pct: float}>
     * }
     */
    public function buildBeforeAfterComparison(NonStockAgingSnapshot $baseline, NonStockAgingSnapshot $evaluation): array
    {
        $itemDelta = $evaluation->total_outstanding_items - $baseline->total_outstanding_items;
        $itemRedPct = $baseline->total_outstanding_items > 0
            ? round((($baseline->total_outstanding_items - $evaluation->total_outstanding_items) / $baseline->total_outstanding_items) * 100, 1)
            : 0.0;

        $poDelta = $evaluation->total_outstanding_po - $baseline->total_outstanding_po;
        $poRedPct = $baseline->total_outstanding_po > 0
            ? round((($baseline->total_outstanding_po - $evaluation->total_outstanding_po) / $baseline->total_outstanding_po) * 100, 1)
            : 0.0;

        $valueDelta = round($evaluation->total_outstanding_value - $baseline->total_outstanding_value, 2);
        $valueRedPct = $baseline->total_outstanding_value > 0
            ? round((($baseline->total_outstanding_value - $evaluation->total_outstanding_value) / $baseline->total_outstanding_value) * 100, 1)
            : 0.0;

        $beforeUnits = [];
        foreach ((array) ($baseline->unit_breakdown ?? []) as $u) {
            $name = (string) ($u['unit_name'] ?? 'Belum Dipetakan');
            $beforeUnits[$name] = $u;
        }

        $afterUnits = [];
        foreach ((array) ($evaluation->unit_breakdown ?? []) as $u) {
            $name = (string) ($u['unit_name'] ?? 'Belum Dipetakan');
            $afterUnits[$name] = $u;
        }

        $allUnitNames = array_values(array_unique(array_merge(array_keys($beforeUnits), array_keys($afterUnits))));
        $unitDeltas = [];

        foreach ($allUnitNames as $name) {
            $b = $beforeUnits[$name] ?? null;
            $a = $afterUnits[$name] ?? null;
            $bVal = (float) ($b['value'] ?? 0);
            $aVal = (float) ($a['value'] ?? 0);
            $uRedPct = $bVal > 0 ? round((($bVal - $aVal) / $bVal) * 100, 1) : 0.0;

            $unitDeltas[] = [
                'unit_name' => $name,
                'is_pilot' => (bool) ($a['is_pilot'] ?? $b['is_pilot'] ?? false),
                'before_items' => (int) ($b['items'] ?? 0),
                'after_items' => (int) ($a['items'] ?? 0),
                'before_value' => round($bVal, 2),
                'after_value' => round($aVal, 2),
                'value_reduction_pct' => $uRedPct,
            ];
        }

        usort($unitDeltas, fn (array $x, array $y): int => $y['before_value'] <=> $x['before_value']);

        return [
            'baseline' => $baseline,
            'evaluation' => $evaluation,
            'item_delta' => $itemDelta,
            'item_reduction_pct' => $itemRedPct,
            'item_target_met' => $itemRedPct >= 25.0,
            'po_delta' => $poDelta,
            'po_reduction_pct' => $poRedPct,
            'value_delta' => $valueDelta,
            'value_reduction_pct' => $valueRedPct,
            'value_target_met' => $valueRedPct >= 20.0,
            'on_check_item_delta' => $evaluation->on_check_items - $baseline->on_check_items,
            'on_check_value_delta' => round($evaluation->on_check_value - $baseline->on_check_value, 2),
            'critical_item_delta' => $evaluation->critical_count - $baseline->critical_count,
            'critical_value_delta' => round($evaluation->critical_value - $baseline->critical_value, 2),
            'followup_pct_delta' => round($evaluation->warning_critical_followed_up_percentage - $baseline->warning_critical_followed_up_percentage, 1),
            'followup_target_met' => $evaluation->warning_critical_followed_up_percentage >= 100.0,
            'unit_deltas' => $unitDeltas,
        ];
    }

    /**
     * Mengekspor Register Monitoring Aging dalam format CSV UTF-8 BOM (siap buka di Excel).
     */
    public function exportRegisterCsv(string $mrpScope = 'NONSTOCK', ?string $activeTab = null, bool $pilotOnly = false): StreamedResponse
    {
        $controls = $this->queryControlsForExport($mrpScope, $activeTab, $pilotOnly);
        $filename = 'register-aging-'.strtolower($mrpScope).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($controls): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM agar Excel langsung membaca karakter Indonesia dengan benar
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->registerHeaders());

            foreach ($controls->values() as $idx => $control) {
                fputcsv($handle, $this->formatControlRow($control, $idx + 1));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Mengekspor Register Monitoring Aging sebagai workbook Excel (.xlsx) 2 worksheet:
     * Sheet 1: Ringkasan KPI & Pareto 80/20
     * Sheet 2: Register Monitoring Terstandar
     */
    public function exportRegisterXlsx(string $mrpScope = 'NONSTOCK', ?string $activeTab = null, bool $pilotOnly = false): BinaryFileResponse
    {
        $filePath = $this->generateRegisterXlsxFile($mrpScope, $activeTab, $pilotOnly);
        $filename = 'register-aging-'.strtolower($mrpScope).'-'.now()->format('Ymd-His').'.xlsx';

        return response()
            ->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Membuat file .xlsx sementara (native ZipArchive tanpa dependensi berat) dan mengembalikan path-nya.
     */
    public function generateRegisterXlsxFile(string $mrpScope = 'NONSTOCK', ?string $activeTab = null, bool $pilotOnly = false): string
    {
        $summary = $this->agingService->buildSummaryMetrics($mrpScope, null, $pilotOnly);
        $controls = $this->queryControlsForExport($mrpScope, $activeTab, $pilotOnly);

        $summaryRows = [
            ['LAPORAN RINGKASAN AGING MATERIAL NON-STOCK & PD — DEPARTEMEN PP&P'],
            ['Tanggal Cetak', now()->format('d M Y H:i'), 'Cakupan MRP', $summary['mrp_scope'], 'Filter Tab', $activeTab ?? 'belum_diambil'],
            [],
            ['METRIK KPI LEAD 2026', 'JUMLAH ITEM', 'JUMLAH PO', 'NILAI TERTAHAN (IDR)', 'KETERANGAN'],
            ['Outstanding Belum Diambil (+ Hold)', $summary['outstanding_items'], $summary['outstanding_po'], $summary['outstanding_value'], 'Target 8 Minggu: Turun >= 25% item & >= 20% nilai'],
            ['Status On Check (Cek Fisik)', $summary['on_check_items'], $summary['on_check_po'], $summary['on_check_value'], 'Menunggu verifikasi cek fisik user'],
            ['Scheduled Hold (Terjadwal Ambil)', $summary['scheduled_hold_items'], '-', $summary['scheduled_hold_value'], 'Sudah memiliki komitmen tanggal ambil'],
            ['Unattended Critical (> 60 Hari)', $summary['unattended_critical_items'], '-', $summary['unattended_critical_value'], 'Prioritas eskalasi formal antar-VP'],
            ['Kepatuhan Follow-Up Warning & Critical (%)', $summary['warning_critical_followed_up'], $summary['warning_critical_total'], $summary['warning_critical_followed_up_percentage'], 'Target: 100%'],
            ['Pemetaan Unit & PIC (%)', $summary['pic_mapped_count'], $summary['outstanding_items'], $summary['pic_mapped_percentage'], 'Target: 100%'],
            [],
            ['DISTRIBUSI 4 ZONA AGING', 'JUMLAH ITEM', 'JUMLAH PO', 'NILAI TERTAHAN (IDR)', 'PENANGGUNG JAWAB'],
            ['Normal (0–14 Hari)', $summary['zones']['Normal']['items'], $summary['zones']['Normal']['po_count'], $summary['zones']['Normal']['value'], 'Admin Receiving -> PIC Unit'],
            ['Attention (15–30 Hari)', $summary['zones']['Attention']['items'], $summary['zones']['Attention']['po_count'], $summary['zones']['Attention']['value'], 'Koordinator Receiving -> PIC Unit'],
            ['Warning (31–60 Hari)', $summary['zones']['Warning']['items'], $summary['zones']['Warning']['po_count'], $summary['zones']['Warning']['value'], 'AVP Receiving -> AVP Unit Peminta'],
            ['Critical (> 60 Hari)', $summary['zones']['Critical']['items'], $summary['zones']['Critical']['po_count'], $summary['zones']['Critical']['value'], 'VP PP&P -> VP Unit Peminta'],
            [],
            ['ANALISA PARETO 80/20 PER UNIT PEMINTA', 'PILOT', 'JUMLAH ITEM', 'JUMLAH PO', 'NILAI TERTAHAN (IDR)', 'KONTRIBUSI (%)', 'KUMULATIF (%)'],
        ];

        foreach ($summary['pareto'] as $row) {
            $summaryRows[] = [
                $row['unit_name'],
                $row['is_pilot'] ? 'YA' : 'TIDAK',
                $row['items'],
                $row['po_count'],
                $row['value'],
                $row['share_pct'],
                $row['cumulative_pct'],
            ];
        }

        $registerRows = [$this->registerHeaders()];
        foreach ($controls->values() as $idx => $control) {
            $registerRows[] = $this->formatControlRow($control, $idx + 1);
        }

        return $this->writeMultiSheetXlsx([
            'Ringkasan KPI & Pareto' => $summaryRows,
            'Register Monitoring' => $registerRows,
        ]);
    }

    /**
     * @return Collection<int, NonStockAgingControl>
     */
    protected function queryControlsForExport(string $mrpScope, ?string $activeTab, bool $pilotOnly): Collection
    {
        $scopeUpper = strtoupper(trim($mrpScope));
        $today = Carbon::today();

        $query = NonStockAgingControl::query()
            ->with([
                'deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv',
                'deliveryOrderReceiptDetail.locationReceiving',
                'deliveryOrderReceiptDetail.materialIssueDetails',
                'deliveryOrderReceiptDetail.purchaseOrderIssued',
                'purchaseOrderIssued',
                'requestingUnit',
            ]);

        if ($scopeUpper === 'NONSTOCK_PD') {
            $query->whereIn('mrp_type_snapshot', NonStockAgingService::TRACKED_MRP_TYPES);
        } elseif ($scopeUpper !== 'ALL') {
            $query->where('mrp_type_snapshot', $scopeUpper);
        }

        if ($pilotOnly) {
            $query->whereHas('requestingUnit', fn (Builder $sub): Builder => $sub->where('is_pilot', true));
        }

        $this->applyTabFilter($query, $activeTab, $today);

        return $query->orderBy('grs_posting_date')->orderBy('po_by_item')->get();
    }

    protected function applyTabFilter(Builder $query, ?string $activeTab, Carbon $today): void
    {
        $d14 = $today->copy()->subDays(14)->toDateString();
        $d15 = $today->copy()->subDays(15)->toDateString();
        $d30 = $today->copy()->subDays(30)->toDateString();
        $d31 = $today->copy()->subDays(31)->toDateString();
        $d60 = $today->copy()->subDays(60)->toDateString();
        $d61 = $today->copy()->subDays(61)->toDateString();

        match ($activeTab) {
            'critical' => $query
                ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                ->whereDate('grs_posting_date', '<=', $d61),
            'warning' => $query
                ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                ->whereDate('grs_posting_date', '<=', $d31)
                ->whereDate('grs_posting_date', '>=', $d60),
            'attention' => $query
                ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                ->whereDate('grs_posting_date', '<=', $d15)
                ->whereDate('grs_posting_date', '>=', $d30),
            'normal' => $query
                ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                ->where(fn (Builder $sub): Builder => $sub->whereDate('grs_posting_date', '>=', $d14)->orWhereNull('grs_posting_date')),
            'on_check' => $query->where('operational_status', AgingOperationalStatus::OnCheck->value),
            'scheduled_hold' => $query->where('operational_status', AgingOperationalStatus::ScheduledHold->value),
            'pilot' => $query
                ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                ->whereHas('requestingUnit', fn (Builder $sub): Builder => $sub->where('is_pilot', true)),
            'selesai_dipindah' => $query->whereIn('operational_status', [
                AgingOperationalStatus::Dipindah->value,
                AgingOperationalStatus::SelesaiDiambil->value,
            ]),
            'all' => $query,
            default => $query->whereIn('operational_status', [
                AgingOperationalStatus::BelumDiambil->value,
                AgingOperationalStatus::ScheduledHold->value,
                AgingOperationalStatus::OnCheck->value,
            ]),
        };
    }

    /**
     * @return list<string>
     */
    protected function registerHeaders(): array
    {
        return [
            'No',
            'PO by Item',
            'No. PO',
            'MRP Type',
            'Kode Material',
            'Deskripsi Material',
            'Vendor',
            'Lokasi Fisik',
            'Requisitioner (SAP)',
            'Unit Peminta',
            'Unit Pilot',
            'PIC Unit',
            'No. HP PIC',
            'Tanggal GRS (T0)',
            'Umur (Hari)',
            'Zona Aging',
            'Qty Terima',
            'Qty Diambil',
            'Sisa Qty',
            'Satuan',
            'Nilai Tertahan (IDR)',
            'Cek Fisik',
            'Status Operasional',
            'Tahap Follow-Up',
            'Komitmen Tgl Ambil',
            'Kategori Kendala',
            'Alasan Hold / Catatan Terakhir',
            'Jumlah Reminder',
            'Eskalasi AVP',
            'Eskalasi VP',
        ];
    }

    /**
     * @return list<string|int|float>
     */
    protected function formatControlRow(NonStockAgingControl $control, int $index): array
    {
        $uoi = $control->deliveryOrderReceiptDetail?->uoi
            ?? $control->purchaseOrderIssued?->uoi
            ?? $control->uoi_snapshot
            ?? 'EA';

        return [
            $index,
            (string) ($control->po_by_item ?? '-'),
            $control->resolvedPoNumber(),
            $control->resolvedMrpType(),
            (string) ($control->resolvedMaterialCode() ?? '-'),
            $control->resolvedDescription(),
            (string) ($control->resolvedVendorName() ?? '-'),
            (string) ($control->resolvedLocation() ?? '-'),
            (string) ($control->resolvedRequisitioner() ?? '-'),
            (string) ($control->requestingUnit?->name ?? 'Belum Dipetakan'),
            $control->requestingUnit?->is_pilot ? 'YA' : 'TIDAK',
            (string) ($control->resolvedPicName() ?? '-'),
            (string) ($control->resolvedPicPhone() ?? '-'),
            (string) ($control->resolvedGrsDate()?->format('Y-m-d') ?? '-'),
            $control->resolvedAgingDays(),
            $control->resolvedAgingZone()->value,
            $control->resolvedReceivedQuantity(),
            $control->resolvedIssuedQuantity(),
            $control->resolvedRemainingQuantity(),
            (string) $uoi,
            $control->resolvedOutstandingValue(),
            $control->physical_checked ? 'YA' : 'BELUM',
            $control->operational_status?->label() ?? '-',
            $control->followup_stage?->label() ?? '-',
            (string) ($control->planned_pickup_date?->format('Y-m-d') ?? '-'),
            (string) ($control->delay_reason_category ?? '-'),
            (string) ($control->scheduled_hold_reason ?? $control->last_followup_note ?? '-'),
            (int) $control->reminder_count,
            (string) ($control->avp_escalated_at?->format('Y-m-d H:i') ?? '-'),
            (string) ($control->vp_escalated_at?->format('Y-m-d H:i') ?? '-'),
        ];
    }

    /**
     * Menulis file XLSX multi-sheet standar OOXML menggunakan ZipArchive native PHP.
     *
     * @param  array<string, list<list<string|int|float>>>  $sheets
     */
    protected function writeMultiSheetXlsx(array $sheets): string
    {
        $tempBase = tempnam(sys_get_temp_dir(), 'aging_export_');
        $xlsxPath = $tempBase.'.xlsx';
        if ($tempBase !== false && is_file($tempBase)) {
            @unlink($tempBase);
        }

        $zip = new ZipArchive;
        $zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $sharedStrings = [];
        $sharedIndex = [];
        $intern = function (string $value) use (&$sharedStrings, &$sharedIndex): int {
            if (! array_key_exists($value, $sharedIndex)) {
                $sharedIndex[$value] = count($sharedStrings);
                $sharedStrings[] = $value;
            }

            return $sharedIndex[$value];
        };

        $sheetEntries = [];
        $relEntries = [];
        $contentTypes = [];
        $sheetNo = 1;

        foreach ($sheets as $sheetName => $rows) {
            $safeName = substr(preg_replace('/[\\\\\/?\*\[\]:]/', ' ', $sheetName) ?? 'Sheet', 0, 31);
            $rid = 'rId'.$sheetNo;
            $sheetPath = "xl/worksheets/sheet{$sheetNo}.xml";

            $sheetEntries[] = '<sheet name="'.htmlspecialchars($safeName, ENT_XML1).'" sheetId="'.$sheetNo.'" r:id="'.$rid.'"/>';
            $relEntries[] = '<Relationship Id="'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$sheetNo.'.xml"/>';
            $contentTypes[] = '<Override PartName="/'.$sheetPath.'" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';

            $rowsXml = '';
            foreach ($rows as $rIdx => $cols) {
                $rowNum = $rIdx + 1;
                if ($cols === []) {
                    continue;
                }
                $cellsXml = '';
                foreach ($cols as $cIdx => $val) {
                    $colLetter = $this->columnIndexToLetter($cIdx + 1);
                    $ref = $colLetter.$rowNum;
                    if (is_int($val) || is_float($val)) {
                        $cellsXml .= '<c r="'.$ref.'"><v>'.$val.'</v></c>';
                    } else {
                        $ssIdx = $intern((string) $val);
                        $cellsXml .= '<c r="'.$ref.'" t="s"><v>'.$ssIdx.'</v></c>';
                    }
                }
                $rowsXml .= '<row r="'.$rowNum.'">'.$cellsXml.'</row>';
            }

            $zip->addFromString(
                $sheetPath,
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<sheetData>'.$rowsXml.'</sheetData></worksheet>'
            );

            $sheetNo++;
        }

        $ssItems = '';
        foreach ($sharedStrings as $str) {
            $ssItems .= '<si><t xml:space="preserve">'.htmlspecialchars($str, ENT_XML1).'</t></si>';
        }

        $zip->addFromString(
            'xl/sharedStrings.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">'
            .$ssItems.'</sst>'
        );

        $relEntries[] = '<Relationship Id="rId'.$sheetNo.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';

        $zip->addFromString(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.implode('', $sheetEntries).'</sheets></workbook>'
        );

        $zip->addFromString(
            'xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .implode('', $relEntries).'</Relationships>'
        );

        $zip->addFromString(
            '_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>'
        );

        $zip->addFromString(
            '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            .implode('', $contentTypes).'</Types>'
        );

        $zip->close();

        return $xlsxPath;
    }

    protected function columnIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $rem = ($index - 1) % 26;
            $letter = chr(65 + $rem).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }
}
