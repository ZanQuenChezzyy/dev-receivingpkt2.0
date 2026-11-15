<?php

namespace App\Filament\Resources\MonitoringKarungs\Schemas;

use App\Models\DeliveryOrderReceipt;
use App\Models\MonitoringKarung;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class MonitoringKarungInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. Apple-Style Executive KPI Hero Cards
                TextEntry::make('hero_kpi')
                    ->label('')
                    ->getStateUsing(fn (MonitoringKarung $record): HtmlString => self::renderHeroKpi($record))
                    ->columnSpanFull(),

                // 2. Kontrak & Master PO Card
                Section::make(__('Informasi Kontrak & Master PO'))
                    ->icon('heroicon-m-document-text')
                    ->collapsible()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('no_po')
                                ->label(__('Nomor Purchase Order'))
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->icon('heroicon-m-document-duplicate')
                                ->copyable()
                                ->copyMessage(__('Nomor PO disalin')),

                            TextEntry::make('vendor_name')
                                ->label(__('Nama Vendor'))
                                ->weight(FontWeight::SemiBold)
                                ->icon('heroicon-m-building-office-2'),

                            TextEntry::make('kategori_karung')
                                ->label(__('Kategori Karung'))
                                ->badge()
                                ->color('info')
                                ->placeholder('-'),

                            TextEntry::make('lokasi_pabrik')
                                ->label(__('Lokasi Pabrik / Gudang'))
                                ->badge()
                                ->color('gray')
                                ->placeholder('-'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('qty_po')
                                ->label(__('Alokasi Total PO'))
                                ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' '.__('Lembar'))
                                ->weight(FontWeight::Bold),

                            TextEntry::make('status')
                                ->label(__('Status Monitoring'))
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => __($state))
                                ->color(fn (string $state): string => match ($state) {
                                    'Selesai' => 'success',
                                    'Terkendala' => 'danger',
                                    default => 'primary',
                                }),

                            TextEntry::make('created_at')
                                ->label(__('Terdaftar Sejak'))
                                ->dateTime('d F Y, H:i')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('po_terbit_info')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->getStateUsing(function (MonitoringKarung $record): HtmlString {
                                $po = $record->purchaseOrderIssued;
                                $latestDo = self::getLatestDeliveryOrderReceipt($record);
                                $linkedDos = $record->deliveryItems()->whereNotNull('delivery_order_receipt_id')->count();

                                $poUrl = $po ? route('filament.admin.resources.purchase-order-terbit.view', ['record' => $po->id]) : null;
                                $doUrl = $latestDo ? route('filament.admin.resources.delivery-order-receipts.view', ['record' => $latestDo->id]) : (! empty($record->no_po) ? route('filament.admin.resources.delivery-order-receipts.index', ['tableSearch' => $record->no_po]) : null);

                                $desc = $po ? htmlspecialchars($po->description ?: __('PO Terbit SAP')) : __('PO Belum Terdaftar di SAP');

                                $poButton = $poUrl
                                    ? "<a href='{$poUrl}' class='inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-500/15 hover:bg-amber-500/25 text-amber-500 border border-amber-500/30 transition-all'>
                                        <svg class='w-3.5 h-3.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/></svg>
                                        PO SAP #{$po->purchase_order_no}
                                    </a>"
                                    : "<span class='text-xs text-slate-400'>".__('PO Terbit SAP belum tersambung').'</span>';

                                $doButton = '';
                                if ($latestDo) {
                                    $tglDo = $latestDo->physical_received_date?->format('d/m/Y') ?? $latestDo->received_date?->format('d/m/Y') ?? '';
                                    $tglLabel = $tglDo ? ' ('.__('Tgl').': '.$tglDo.')' : '';
                                    $doLatestTitle = __('Buka dokumen DO Receipt terakhir');
                                    $doLatestLabel = __('DO Receipt Terakhir');
                                    $doButton = "<a href='{$doUrl}' class='inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-sky-500/20 hover:bg-sky-500/30 text-sky-400 border border-sky-500/40 transition-all ring-1 ring-sky-500/30 shadow-xs' title='{$doLatestTitle}'>
                                        <svg class='w-3.5 h-3.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'/></svg>
                                        {$doLatestLabel}: #{$latestDo->delivery_order_no}{$tglLabel}
                                    </a>";
                                } elseif ($doUrl) {
                                    $cariDoLabel = __('Cari di Penerimaan DO');
                                    $doButton = "<a href='{$doUrl}' class='inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-all'>
                                        <svg class='w-3.5 h-3.5 text-slate-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/></svg>
                                        {$cariDoLabel}
                                    </a>";
                                }

                                $doConnectedLabel = __('DO Terhubung');
                                $badgeDo = "<span class='inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'>
                                    <svg class='w-3.5 h-3.5 text-emerald-500' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'/></svg>
                                    {$linkedDos} {$doConnectedLabel}
                                </span>";

                                $headerTitle = __('Integrasi PO Terbit & DO Receipt');
                                $headerHint = __('Klik untuk melihat data DO Receipt');
                                $headerLink = $doUrl ? "<a href='{$doUrl}' class='flex items-center gap-2 group text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-sky-400 transition-colors' title='{$headerHint}'>
                                    <div class='p-1.5 rounded-lg bg-sky-500/20 text-sky-400 group-hover:scale-105 transition-transform'>
                                        <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 10V3L4 14h7v7l9-11h-7z'/></svg>
                                    </div>
                                    <span>{$headerTitle}</span>
                                    <svg class='w-3.5 h-3.5 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'/></svg>
                                </a>" : "<div class='flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-300'>
                                    <div class='p-1.5 rounded-lg bg-sky-500/20 text-sky-400'>
                                        <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 10V3L4 14h7v7l9-11h-7z'/></svg>
                                    </div>
                                    <span>{$headerTitle}</span>
                                </div>";

                                return new HtmlString("
                                    <div class='w-full rounded-2xl bg-slate-900/60 dark:bg-white/[0.03] border border-slate-800 dark:border-white/10 p-3.5 shadow-xs'>
                                        <div class='flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3'>
                                            <div class='flex flex-wrap items-center gap-3'>
                                                {$headerLink}
                                                <div class='h-4 w-px bg-slate-700/60 dark:border-white/10 hidden sm:block'></div>
                                                <div class='flex flex-wrap items-center gap-2'>
                                                    {$doButton}
                                                    {$poButton}
                                                    {$badgeDo}
                                                </div>
                                            </div>
                                            <div class='text-xs font-mono text-slate-400 dark:text-slate-300 truncate max-w-[420px]'>
                                                {$desc}
                                            </div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        TextEntry::make('catatan')
                            ->label(__('Catatan PO'))
                            ->placeholder(__('Tidak ada catatan khusus.'))
                            ->columnSpanFull(),
                    ]),

                // 3. Tabbed Multi-Perspective Logs
                Tabs::make(__('Rincian Log Monitoring & Tahapan'))
                    ->tabs([
                        Tab::make(__('Data Pengujian QC & SA SAP'))
                            ->icon('heroicon-m-shield-check')
                            ->badge(fn (MonitoringKarung $record): int => $record->qcItems()->count())
                            ->schema([
                                TextEntry::make('table_qc')
                                    ->label('')
                                    ->getStateUsing(fn (MonitoringKarung $record): HtmlString => self::renderQcTable($record))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Data Pengiriman & Penagihan DO'))
                            ->icon('heroicon-m-truck')
                            ->badge(fn (MonitoringKarung $record): int => $record->deliveryItems()->count())
                            ->schema([
                                TextEntry::make('table_delivery')
                                    ->label('')
                                    ->getStateUsing(fn (MonitoringKarung $record): HtmlString => self::renderDeliveryTable($record))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Rekapitulasi Bulanan SIK & Estimasi Jasa'))
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                TextEntry::make('rekap_bulanan')
                                    ->label('')
                                    ->getStateUsing(fn (MonitoringKarung $record): HtmlString => self::renderMonthlyQcSummary($record))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Rekonsiliasi & Analisis (QC vs Fisik)'))
                            ->icon('heroicon-m-arrows-right-left')
                            ->schema([
                                TextEntry::make('rekonsiliasi')
                                    ->label('')
                                    ->getStateUsing(fn (MonitoringKarung $record): HtmlString => self::renderReconciliationSummary($record))
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    /**
     * Render Apple-style elevated KPI summary hero cards.
     */
    public static function renderHeroKpi(MonitoringKarung $record): HtmlString
    {
        $poQtyFormatted = number_format($record->qty_po, 0, ',', '.');
        $qcQtyFormatted = number_format($record->total_qty_qc, 0, ',', '.');
        $kirimQtyFormatted = number_format($record->total_qty_kirim, 0, ',', '.');
        $sisaQtyFormatted = number_format($record->sisa_qty_po, 0, ',', '.');

        $qcPercent = $record->persentase_qc;
        $kirimPercent = $record->persentase_kirim;

        $qcCount = $record->qcItems()->count();
        $deliveryCount = $record->deliveryItems()->count();

        $pendingSa = $record->pending_sa_count;
        $completedSa = $record->completed_sa_count;

        $belumsSaText = __('Belum SA');
        $semuaSaLengkapText = __('Semua SA Lengkap');
        $lbrText = __('Lbr');
        $alokasiPoText = __('Alokasi Total PO');
        $realisasiQcText = __('Realisasi Uji QC');
        $progressQcText = __('Progress QC');
        $tahapUjiText = __('Tahap Uji');
        $realisasiKirimText = __('Realisasi Kirim DO');
        $sisaText = __('Sisa');
        $pengirimanText = __('Pengiriman');
        $statusSaText = __('Status SA SAP');
        $selesaiText = __('Selesai');
        $drafterText = __('Drafter Karung');
        $timDrafterText = __('Tim Drafter PKT');
        $statusTranslated = __($record->status);

        $saBadge = $pendingSa > 0
            ? "<span class='inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 ring-1 ring-rose-500/20 animate-pulse'>
                <svg class='w-3.5 h-3.5' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z' clip-rule='evenodd'/></svg>
                {$pendingSa} {$belumsSaText}
               </span>"
            : "<span class='inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'>
                <svg class='w-3.5 h-3.5' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'/></svg>
                {$semuaSaLengkapText}
               </span>";

        $statusColor = match ($record->status) {
            'Selesai' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
            'Terkendala' => 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
            default => 'bg-orange-500/15 text-orange-600 dark:text-orange-400 border-orange-500/30',
        };

        return new HtmlString("
            <div class='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4'>
                <!-- Card 1: Total Alokasi PO -->
                <div class='relative overflow-hidden p-5 rounded-2xl bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] shadow-sm hover:shadow-md transition-all duration-300'>
                    <div class='flex items-center justify-between mb-3'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400'>{$alokasiPoText}</span>
                        <span class='px-2 py-0.5 rounded-full text-[10.5px] font-bold border {$statusColor}'>{$statusTranslated}</span>
                    </div>
                    <div class='text-2xl font-black font-mono text-slate-900 dark:text-white tracking-tight'>
                        {$poQtyFormatted} <span class='text-xs font-semibold text-slate-400'>{$lbrText}</span>
                    </div>
                    <div class='mt-3 pt-3 border-t border-black/[0.04] dark:border-white/[0.06] flex items-center justify-between text-xs text-slate-500 dark:text-slate-400'>
                        <span class='truncate max-w-[140px]' title='{$record->vendor_name}'>{$record->vendor_name}</span>
                        <span class='font-semibold text-slate-700 dark:text-slate-300'>PO #{$record->no_po}</span>
                    </div>
                </div>

                <!-- Card 2: Realisasi QC -->
                <div class='relative overflow-hidden p-5 rounded-2xl bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] shadow-sm hover:shadow-md transition-all duration-300'>
                    <div class='flex items-center justify-between mb-3'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400'>{$realisasiQcText}</span>
                        <span class='text-xs font-extrabold font-mono text-emerald-600 dark:text-emerald-400'>{$qcPercent}%</span>
                    </div>
                    <div class='text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 tracking-tight'>
                        {$qcQtyFormatted} <span class='text-xs font-semibold text-slate-400'>{$lbrText}</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class='w-full bg-slate-100 dark:bg-white/10 rounded-full h-2 mt-3 overflow-hidden'>
                        <div class='bg-emerald-500 h-2 rounded-full transition-all duration-500' style='width: {$qcPercent}%'></div>
                    </div>
                    <div class='mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400'>
                        <span>{$progressQcText}</span>
                        <span class='font-semibold text-slate-700 dark:text-slate-300'>{$qcCount} {$tahapUjiText}</span>
                    </div>
                </div>

                <!-- Card 3: Realisasi Kirim DO -->
                <div class='relative overflow-hidden p-5 rounded-2xl bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] shadow-sm hover:shadow-md transition-all duration-300'>
                    <div class='flex items-center justify-between mb-3'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400'>{$realisasiKirimText}</span>
                        <span class='text-xs font-extrabold font-mono text-[#F47920]'>{$kirimPercent}%</span>
                    </div>
                    <div class='text-2xl font-black font-mono text-[#F47920] tracking-tight'>
                        {$kirimQtyFormatted} <span class='text-xs font-semibold text-slate-400'>{$lbrText}</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class='w-full bg-slate-100 dark:bg-white/10 rounded-full h-2 mt-3 overflow-hidden'>
                        <div class='bg-[#F47920] h-2 rounded-full transition-all duration-500' style='width: {$kirimPercent}%'></div>
                    </div>
                    <div class='mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400'>
                        <span>{$sisaText}: <strong class='text-slate-700 dark:text-slate-300'>{$sisaQtyFormatted} {$lbrText}</strong></span>
                        <span class='font-semibold text-slate-700 dark:text-slate-300'>{$deliveryCount} {$pengirimanText}</span>
                    </div>
                </div>

                <!-- Card 4: Administrasi SA SAP -->
                <div class='relative overflow-hidden p-5 rounded-2xl bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] shadow-sm hover:shadow-md transition-all duration-300'>
                    <div class='flex items-center justify-between mb-3'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400'>{$statusSaText}</span>
                        {$saBadge}
                    </div>
                    <div class='text-2xl font-black font-mono text-slate-900 dark:text-white tracking-tight'>
                        {$completedSa} <span class='text-sm font-semibold text-slate-400'>/ {$qcCount} {$selesaiText}</span>
                    </div>
                    <div class='mt-3 pt-3 border-t border-black/[0.04] dark:border-white/[0.06] flex items-center justify-between text-xs text-slate-500 dark:text-slate-400'>
                        <span>{$drafterText}</span>
                        <span class='font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[120px]'>{$timDrafterText}</span>
                    </div>
                </div>
            </div>
        ");
    }

    /**
     * Render the high-contrast Apple-style QC Table.
     */
    public static function renderQcTable(MonitoringKarung $record): HtmlString
    {
        $items = $record->qcItems;

        if ($items->isEmpty()) {
            $emptyTitle = __('Belum ada log tahapan pengujian QC.');
            $emptyDesc = __('Klik tombol Ubah atau gunakan tab Tambah Tahap QC untuk mencatat data baru.');

            return new HtmlString("
                <div class='p-8 text-center rounded-2xl bg-slate-50/50 dark:bg-white/5 border border-dashed border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400'>
                    <svg class='w-12 h-12 mx-auto mb-2 text-slate-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/></svg>
                    <p class='font-semibold'>{$emptyTitle}</p>
                    <p class='text-xs mt-1'>{$emptyDesc}</p>
                </div>
            ");
        }

        $rows = '';
        $idx = 1;
        $totalQty = 0;
        $totalSaFilled = 0;
        $lbrText = __('Lbr');
        $blmSaText = __('BLM SA');

        foreach ($items as $item) {
            $totalQty += (int) $item->qty_qc;
            $qtyFormatted = number_format($item->qty_qc, 0, ',', '.');

            $statusQcClass = match (strtolower($item->status_qc ?? '')) {
                'diterima' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                'ditolak', 'batal' => 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
                'istek' => 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30',
                'tuv', 'diterima dgn penalti' => 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
                'blm sa' => 'bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/40 ring-1 ring-rose-500/30 font-bold',
                default => 'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/30',
            };

            $isSaDone = ! empty($item->no_sa) && strtolower($item->status_qc ?? '') !== 'blm sa';
            if ($isSaDone) {
                $totalSaFilled++;
                $saBadge = "<span class='inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'>
                    <svg class='w-3 h-3 text-emerald-500' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'/></svg>
                    {$item->no_sa}
                </span>";
            } else {
                $saBadge = "<span class='inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 animate-pulse'>
                    <svg class='w-3 h-3 text-rose-500' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'/></svg>
                    {$blmSaText}
                </span>";
            }

            $tglSikFormatted = $item->tgl_sik ? date('d/m/Y', strtotime((string) $item->tgl_sik)) : '-';
            $tglPermintaanFormatted = $item->tgl_permintaan ? date('d/m/Y', strtotime((string) $item->tgl_permintaan)) : '-';

            $noSik = $item->no_sik ?: '-';
            $noSuratQc = $item->no_surat_qc ?: '-';
            $noBapp = $item->no_bapp ?: '-';
            $drafter = $item->drafter ?: '-';
            $noDof = $item->no_dof_daan ?: '-';

            $tahapBadgeColor = strtolower($item->tahap_qc) === 'awal'
                ? 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30'
                : 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30';

            $rows .= "
                <tr class='border-b border-slate-100 dark:border-white/5 hover:bg-slate-50/70 dark:hover:bg-white/[0.04] transition-colors'>
                    <td class='px-3.5 py-3 text-center font-mono text-slate-400 text-[11px]'>{$idx}</td>
                    <td class='px-3.5 py-3 font-semibold'>
                        <span class='inline-block px-2.5 py-0.5 rounded-lg text-xs font-bold border {$tahapBadgeColor}'>{$item->tahap_qc}</span>
                    </td>
                    <td class='px-3.5 py-3 text-right font-mono font-bold text-slate-900 dark:text-white text-xs'>{$qtyFormatted} <span class='text-[10px] text-slate-400'>{$lbrText}</span></td>
                    <td class='px-3.5 py-3 text-center'>
                        <span class='inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {$statusQcClass}'>{$item->status_qc}</span>
                    </td>
                    <td class='px-3.5 py-3 font-mono text-xs font-medium text-slate-700 dark:text-slate-300'>{$noSik}</td>
                    <td class='px-3.5 py-3 text-center font-mono text-xs text-slate-500 dark:text-slate-400'>{$tglSikFormatted}</td>
                    <td class='px-3.5 py-3 font-mono text-xs text-slate-600 dark:text-slate-400'>{$noSuratQc}</td>
                    <td class='px-3.5 py-3 font-mono text-xs text-slate-700 dark:text-slate-300'>{$noBapp}</td>
                    <td class='px-3.5 py-3 text-center'>{$saBadge}</td>
                    <td class='px-3.5 py-3 text-center'>
                        <span class='inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300'>{$drafter}</span>
                    </td>
                    <td class='px-3.5 py-3 text-xs text-slate-500 dark:text-slate-400 font-mono'>{$noDof}</td>
                </tr>
            ";
            $idx++;
        }

        $totalQtyFormatted = number_format($totalQty, 0, ',', '.');
        $itemCount = $items->count();

        $thTahap = __('Tahap');
        $thQtyDiuji = __('Qty Diuji');
        $thHasilQc = __('Hasil QC');
        $thNomorSik = __('Nomor SIK');
        $thTglSik = __('Tgl SIK');
        $thNoSuratQc = __('No. Surat QC');
        $thNoBapp = __('No. BAPP');
        $thNomorSa = __('Nomor SA (SAP)');
        $thDrafter = __('Drafter');
        $thNoDof = __('No. DOF');
        $tfootTotal = __('TOTAL (:count Tahap)', ['count' => $itemCount]);
        $rekapSaLabel = __('Rekap SA SAP:');
        $terbitLabel = __('Terbit');

        return new HtmlString("
            <div class='overflow-x-auto rounded-2xl border border-slate-200 dark:border-white/10 shadow-xs bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-md'>
                <table class='w-full text-left text-xs'>
                    <thead class='bg-slate-50/80 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 text-[11px] uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400'>
                        <tr>
                            <th class='px-3.5 py-3 text-center w-10'>#</th>
                            <th class='px-3.5 py-3'>{$thTahap}</th>
                            <th class='px-3.5 py-3 text-right'>{$thQtyDiuji}</th>
                            <th class='px-3.5 py-3 text-center'>{$thHasilQc}</th>
                            <th class='px-3.5 py-3'>{$thNomorSik}</th>
                            <th class='px-3.5 py-3 text-center'>{$thTglSik}</th>
                            <th class='px-3.5 py-3'>{$thNoSuratQc}</th>
                            <th class='px-3.5 py-3'>{$thNoBapp}</th>
                            <th class='px-3.5 py-3 text-center'>{$thNomorSa}</th>
                            <th class='px-3.5 py-3 text-center'>{$thDrafter}</th>
                            <th class='px-3.5 py-3'>{$thNoDof}</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-slate-100 dark:divide-white/5'>
                        {$rows}
                    </tbody>
                    <tfoot class='bg-slate-50/90 dark:bg-white/5 border-t-2 border-slate-200 dark:border-white/10 font-bold text-xs'>
                        <tr>
                            <td colspan='2' class='px-3.5 py-3.5 text-slate-900 dark:text-white uppercase font-black'>{$tfootTotal}</td>
                            <td class='px-3.5 py-3.5 text-right font-mono text-emerald-600 dark:text-emerald-400 font-black text-sm'>{$totalQtyFormatted} {$lbrText}</td>
                            <td colspan='5' class='px-3.5 py-3.5 text-right text-slate-500 dark:text-slate-400 font-medium'>{$rekapSaLabel}</td>
                            <td class='px-3.5 py-3.5 text-center font-mono font-bold text-slate-900 dark:text-white'>{$totalSaFilled} / {$itemCount} {$terbitLabel}</td>
                            <td colspan='2'></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        ");
    }

    /**
     * Render the high-contrast Apple-style Delivery Table.
     */
    public static function renderDeliveryTable(MonitoringKarung $record): HtmlString
    {
        $items = $record->deliveryItems;

        if ($items->isEmpty()) {
            $emptyTitle = __('Belum ada log pengiriman fisik / DO.');
            $emptyDesc = __('Klik tombol Ubah atau gunakan tab Tambah Tahap Pengiriman untuk mencatat surat jalan.');

            return new HtmlString("
                <div class='p-8 text-center rounded-2xl bg-slate-50/50 dark:bg-white/5 border border-dashed border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400'>
                    <svg class='w-12 h-12 mx-auto mb-2 text-slate-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'/></svg>
                    <p class='font-semibold'>{$emptyTitle}</p>
                    <p class='text-xs mt-1'>{$emptyDesc}</p>
                </div>
            ");
        }

        $rows = '';
        $idx = 1;
        $totalQtyKirim = 0;
        $totalSupply = 0;
        $totalSurat = 0;
        $lbrText = __('Lbr');
        $kirimPrefix = __('Kirim');
        $bukaDoTitle = __('Buka Detail Dokumen DO Receipt');

        foreach ($items as $item) {
            $totalQtyKirim += (int) $item->qty_kirim;
            $totalSupply += (int) $item->qty_supply;
            $totalSurat += (int) $item->qty_surat;

            $qtyFormatted = number_format($item->qty_kirim, 0, ',', '.');
            $supplyFormatted = $item->qty_supply ? number_format($item->qty_supply, 0, ',', '.') : '-';
            $suratFormatted = $item->qty_surat ? number_format($item->qty_surat, 0, ',', '.') : '-';

            $tglTerima = $item->tanggal_penerimaan ? date('d/m/Y', strtotime((string) $item->tanggal_penerimaan)) : '-';

            $dorUrl = $item->delivery_order_receipt_id
                ? route('filament.admin.resources.delivery-order-receipts.view', ['record' => $item->delivery_order_receipt_id])
                : null;

            if ($item->no_do) {
                if ($dorUrl) {
                    $noDo = "<a href='{$dorUrl}' class='inline-flex items-center gap-1 font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline' title='{$bukaDoTitle}'>
                        <svg class='w-3.5 h-3.5 text-sky-500 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'/></svg>
                        {$item->no_do}
                    </a>";
                } else {
                    $noDo = "<span class='font-mono font-semibold text-slate-800 dark:text-slate-200'>{$item->no_do}</span>";
                }
            } else {
                $noDo = "<span class='text-slate-400'>-</span>";
            }

            $noInvoice = $item->no_invoice
                ? "<span class='inline-block px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'>{$item->no_invoice}</span>"
                : "<span class='text-slate-400'>-</span>";

            $noSurat = $item->no_surat ?: '-';
            $catatan = $item->keterangan ?: '-';

            $rows .= "
                <tr class='border-b border-slate-100 dark:border-white/5 hover:bg-slate-50/70 dark:hover:bg-white/[0.04] transition-colors'>
                    <td class='px-3.5 py-3 text-center font-mono text-slate-400 text-[11px]'>{$idx}</td>
                    <td class='px-3.5 py-3 font-semibold'>
                        <span class='inline-block px-2.5 py-0.5 rounded-lg text-xs font-bold bg-orange-500/15 text-orange-600 dark:text-orange-400 border border-orange-500/30'>{$kirimPrefix} #{$item->tahap_kirim}</span>
                    </td>
                    <td class='px-3.5 py-3 text-right font-mono font-bold text-[#F47920] text-xs'>{$qtyFormatted} <span class='text-[10px] text-slate-400'>{$lbrText}</span></td>
                    <td class='px-3.5 py-3'>{$noDo}</td>
                    <td class='px-3.5 py-3'>{$noInvoice}</td>
                    <td class='px-3.5 py-3 text-center font-mono text-xs text-slate-500 dark:text-slate-400'>{$tglTerima}</td>
                    <td class='px-3.5 py-3 text-xs text-slate-600 dark:text-slate-400 font-mono'>{$noSurat}</td>
                    <td class='px-3.5 py-3 text-right font-mono text-xs text-slate-700 dark:text-slate-300'>{$supplyFormatted}</td>
                    <td class='px-3.5 py-3 text-right font-mono text-xs text-slate-700 dark:text-slate-300'>{$suratFormatted}</td>
                    <td class='px-3.5 py-3 text-xs text-slate-500 dark:text-slate-400'>{$catatan}</td>
                </tr>
            ";
            $idx++;
        }

        $totalQtyFormatted = number_format($totalQtyKirim, 0, ',', '.');
        $totalSupplyFormatted = number_format($totalSupply, 0, ',', '.');
        $totalSuratFormatted = number_format($totalSurat, 0, ',', '.');
        $itemCount = $items->count();

        $thTahap = __('Tahap');
        $thQtyKirim = __('Qty Kirim');
        $thNoDo = __('No. Surat Jalan (DO)');
        $thNoInvoice = __('No. Invoice');
        $thTglTerima = __('Tgl Terima Fisik');
        $thNoSurat = __('No. Surat Pengantar');
        $thQtySupply = __('Qty Supply');
        $thQtySurat = __('Qty Surat');
        $thCatatan = __('Catatan');
        $tfootKirimTotal = __('TOTAL (:count Pengiriman)', ['count' => $itemCount]);

        return new HtmlString("
            <div class='overflow-x-auto rounded-2xl border border-slate-200 dark:border-white/10 shadow-xs bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-md'>
                <table class='w-full text-left text-xs'>
                    <thead class='bg-slate-50/80 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 text-[11px] uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400'>
                        <tr>
                            <th class='px-3.5 py-3 text-center w-10'>#</th>
                            <th class='px-3.5 py-3'>{$thTahap}</th>
                            <th class='px-3.5 py-3 text-right'>{$thQtyKirim}</th>
                            <th class='px-3.5 py-3'>{$thNoDo}</th>
                            <th class='px-3.5 py-3'>{$thNoInvoice}</th>
                            <th class='px-3.5 py-3 text-center'>{$thTglTerima}</th>
                            <th class='px-3.5 py-3'>{$thNoSurat}</th>
                            <th class='px-3.5 py-3 text-right'>{$thQtySupply}</th>
                            <th class='px-3.5 py-3 text-right'>{$thQtySurat}</th>
                            <th class='px-3.5 py-3'>{$thCatatan}</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-slate-100 dark:divide-white/5'>
                        {$rows}
                    </tbody>
                    <tfoot class='bg-slate-50/90 dark:bg-white/5 border-t-2 border-slate-200 dark:border-white/10 font-bold text-xs'>
                        <tr>
                            <td colspan='2' class='px-3.5 py-3.5 text-slate-900 dark:text-white uppercase font-black'>{$tfootKirimTotal}</td>
                            <td class='px-3.5 py-3.5 text-right font-mono text-[#F47920] font-black text-sm'>{$totalQtyFormatted} {$lbrText}</td>
                            <td colspan='4'></td>
                            <td class='px-3.5 py-3.5 text-right font-mono text-slate-700 dark:text-slate-300'>{$totalSupplyFormatted}</td>
                            <td class='px-3.5 py-3.5 text-right font-mono text-slate-700 dark:text-slate-300'>{$totalSuratFormatted}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        ");
    }

    /**
     * Render the 12-month QC matrix and estimated service costs.
     */
    public static function renderMonthlyQcSummary(MonitoringKarung $record): HtmlString
    {
        $months = [
            1 => __('Januari'), 2 => __('Februari'), 3 => __('Maret'), 4 => __('April'),
            5 => __('Mei'), 6 => __('Juni'), 7 => __('Juli'), 8 => __('Agustus'),
            9 => __('September'), 10 => __('Oktober'), 11 => __('November'), 12 => __('Desember'),
        ];

        $qcItems = $record->qcItems;

        $rowsHtml = '';
        $grandTotalSik = 0;
        $grandTotalAwal = 0;
        $grandTotalAnnual = 0;
        $grandTotalLembar = 0;

        foreach ($months as $num => $monthName) {
            $monthItems = $qcItems->filter(function ($it) use ($num) {
                $date = $it->tgl_sik ?: ($it->tgl_permintaan ?: $it->created_at);

                return $date ? (int) date('n', strtotime((string) $date)) === $num : false;
            });

            $sikCount = $monthItems->whereNotNull('no_sik')->count();
            $awalQty = (int) $monthItems->filter(fn ($it) => strtolower($it->tahap_qc ?? '') === 'awal')->sum('qty_qc');
            $annualQty = (int) $monthItems->filter(fn ($it) => strtolower($it->tahap_qc ?? '') !== 'awal')->sum('qty_qc');
            $totalLembar = $awalQty + $annualQty;

            $cost25 = $totalLembar * 25;
            $cost50 = $totalLembar * 50;

            $grandTotalSik += $sikCount;
            $grandTotalAwal += $awalQty;
            $grandTotalAnnual += $annualQty;
            $grandTotalLembar += $totalLembar;

            $sikDisplay = $sikCount > 0 ? "<span class='font-bold text-slate-900 dark:text-white'>{$sikCount}</span>" : "<span class='text-slate-400'>-</span>";
            $awalDisplay = $awalQty > 0 ? number_format($awalQty, 0, ',', '.') : '-';
            $annualDisplay = $annualQty > 0 ? number_format($annualQty, 0, ',', '.') : '-';
            $lembarDisplay = $totalLembar > 0 ? number_format($totalLembar, 0, ',', '.') : '-';
            $cost25Display = $cost25 > 0 ? 'Rp '.number_format($cost25, 0, ',', '.') : '-';
            $cost50Display = $cost50 > 0 ? 'Rp '.number_format($cost50, 0, ',', '.') : '-';

            $rowsHtml .= "
                <tr class='border-b border-slate-100 dark:border-white/5 hover:bg-slate-50/70 dark:hover:bg-white/[0.04] text-xs transition-colors'>
                    <td class='px-4 py-2.5 font-semibold text-slate-800 dark:text-slate-200'>{$monthName}</td>
                    <td class='px-4 py-2.5 text-center font-mono'>{$sikDisplay}</td>
                    <td class='px-4 py-2.5 text-right font-mono text-slate-600 dark:text-slate-400'>{$awalDisplay}</td>
                    <td class='px-4 py-2.5 text-right font-mono text-slate-700 dark:text-slate-300 font-semibold'>{$annualDisplay}</td>
                    <td class='px-4 py-2.5 text-right font-mono font-bold text-orange-600 dark:text-orange-400'>{$lembarDisplay}</td>
                    <td class='px-4 py-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400'>{$cost25Display}</td>
                    <td class='px-4 py-2.5 text-right font-mono text-sky-600 dark:text-sky-400'>{$cost50Display}</td>
                </tr>
            ";
        }

        $totalCost25 = 'Rp '.number_format($grandTotalLembar * 25, 0, ',', '.');
        $totalCost50 = 'Rp '.number_format($grandTotalLembar * 50, 0, ',', '.');
        $grandTotalAwalStr = number_format($grandTotalAwal, 0, ',', '.');
        $grandTotalAnnualStr = number_format($grandTotalAnnual, 0, ',', '.');
        $grandTotalLembarStr = number_format($grandTotalLembar, 0, ',', '.');

        $cardTotalSik = __('Total SIK Terbit');
        $cardTotalLembar = __('Total Lembar Diuji');
        $cardEst25 = __('Estimasi Jasa (@ Rp 25/Lbr)');
        $cardEst50 = __('Estimasi Jasa (@ Rp 50/Lbr)');
        $thBulan = __('Bulan');
        $thJmlSik = __('Jml SIK');
        $thSampelAwal = __('Sampel Awal (Lbr)');
        $thProduksiAnnual = __('Produksi Annual (Lbr)');
        $thTotalLembar = __('Total Lembar');
        $thBiaya25 = __('Biaya (@ Rp 25)');
        $thBiaya50 = __('Biaya (@ Rp 50)');
        $totalSetahun = __('TOTAL SETAHUN');
        $lbrText = __('Lbr');

        return new HtmlString("
            <div class='space-y-4'>
                <div class='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4'>
                    <div class='p-4 rounded-2xl bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-md border border-slate-200 dark:border-white/10 shadow-xs'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400'>{$cardTotalSik}</span>
                        <div class='text-2xl font-black font-mono text-slate-900 dark:text-white mt-1'>{$grandTotalSik} <span class='text-xs font-semibold text-slate-400'>SIK</span></div>
                    </div>
                    <div class='p-4 rounded-2xl bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-md border border-slate-200 dark:border-white/10 shadow-xs'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400'>{$cardTotalLembar}</span>
                        <div class='text-2xl font-black font-mono text-[#F47920] mt-1'>{$grandTotalLembarStr} <span class='text-xs font-semibold text-slate-400'>{$lbrText}</span></div>
                    </div>
                    <div class='p-4 rounded-2xl bg-emerald-500/10 dark:bg-emerald-950/30 backdrop-blur-md border border-emerald-500/25 shadow-xs'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400'>{$cardEst25}</span>
                        <div class='text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1'>{$totalCost25}</div>
                    </div>
                    <div class='p-4 rounded-2xl bg-sky-500/10 dark:bg-sky-950/30 backdrop-blur-md border border-sky-500/25 shadow-xs'>
                        <span class='text-[11px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400'>{$cardEst50}</span>
                        <div class='text-2xl font-black font-mono text-sky-600 dark:text-sky-400 mt-1'>{$totalCost50}</div>
                    </div>
                </div>

                <div class='overflow-x-auto rounded-2xl border border-slate-200 dark:border-white/10 shadow-xs bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-md'>
                    <table class='w-full text-left'>
                        <thead class='bg-slate-50/80 dark:bg-white/5 text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-white/10'>
                            <tr>
                                <th class='px-4 py-3'>{$thBulan}</th>
                                <th class='px-4 py-3 text-center'>{$thJmlSik}</th>
                                <th class='px-4 py-3 text-right'>{$thSampelAwal}</th>
                                <th class='px-4 py-3 text-right'>{$thProduksiAnnual}</th>
                                <th class='px-4 py-3 text-right font-bold'>{$thTotalLembar}</th>
                                <th class='px-4 py-3 text-right'>{$thBiaya25}</th>
                                <th class='px-4 py-3 text-right'>{$thBiaya50}</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-slate-100 dark:divide-white/5'>
                            {$rowsHtml}
                        </tbody>
                        <tfoot class='bg-slate-50/90 dark:bg-white/5 font-bold text-xs border-t-2 border-slate-200 dark:border-white/10'>
                            <tr>
                                <td class='px-4 py-3.5 text-slate-900 dark:text-white uppercase font-black'>{$totalSetahun}</td>
                                <td class='px-4 py-3.5 text-center font-mono font-bold text-slate-900 dark:text-white'>{$grandTotalSik} SIK</td>
                                <td class='px-4 py-3.5 text-right font-mono text-slate-600 dark:text-slate-400'>{$grandTotalAwalStr}</td>
                                <td class='px-4 py-3.5 text-right font-mono text-slate-800 dark:text-slate-200'>{$grandTotalAnnualStr}</td>
                                <td class='px-4 py-3.5 text-right font-mono text-[#F47920] font-black text-sm'>{$grandTotalLembarStr} {$lbrText}</td>
                                <td class='px-4 py-3.5 text-right font-mono text-emerald-600 dark:text-emerald-400 font-bold'>{$totalCost25}</td>
                                <td class='px-4 py-3.5 text-right font-mono text-sky-600 dark:text-sky-400 font-bold'>{$totalCost50}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        ");
    }

    /**
     * Render the side-by-side reconciliation between QC results and physical deliveries.
     */
    public static function renderReconciliationSummary(MonitoringKarung $record): HtmlString
    {
        $poQty = $record->qty_po;
        $qcQty = $record->total_qty_qc;
        $kirimQty = $record->total_qty_kirim;
        $selisihQcKirim = $qcQty - $kirimQty;

        $selisihLabel = $selisihQcKirim > 0
            ? "<span class='text-amber-600 dark:text-amber-400 font-bold'>+".__(':qty Lbr Lulus QC Belum Terkirim', ['qty' => number_format($selisihQcKirim, 0, ',', '.')]).'</span>'
            : ($selisihQcKirim < 0
                ? "<span class='text-rose-600 dark:text-rose-400 font-bold'>".__('Pengiriman melebihi QC (:qty Lbr)', ['qty' => number_format(abs($selisihQcKirim), 0, ',', '.')]).'</span>'
                : "<span class='text-emerald-600 dark:text-emerald-400 font-bold'>".__('Kuantitas QC dan Pengiriman Seimbang (100% Sinkron)').'</span>');

        $cardAnalysisTitle = __('Analisis Sinkronisasi Fisik Gudang vs Uji Mutu QC');
        $card1Title = __('1. Total Alokasi Kontrak PO');
        $card1Desc = __('Target lembar karung yang dipesan dari vendor :vendor.', ['vendor' => $record->vendor_name]);
        $card2Title = __('2. Realisasi Pengujian QC');
        $card2Desc = __('Total sampel karung yang telah diuji oleh laboratorium mutu QC PKT.');
        $card3Title = __('3. Realisasi Pengiriman Fisik DO');
        $card3Desc = __('Total karung yang telah diterima secara fisik di gudang penyimpanan.');
        $statusSelisihLabel = __('Status Selisih (QC vs Fisik):');
        $lembarText = __('Lembar');

        return new HtmlString("
            <div class='space-y-5'>
                <!-- Summary Card -->
                <div class='p-6 rounded-2xl bg-white/70 dark:bg-[#0b1626]/75 backdrop-blur-xl border border-black/[0.06] dark:border-white/[0.08] shadow-sm'>
                    <h3 class='text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2'>
                        <svg class='w-5 h-5 text-primary-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'/></svg>
                        {$cardAnalysisTitle}
                    </h3>

                    <div class='grid grid-cols-1 md:grid-cols-3 gap-6'>
                        <div class='p-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10'>
                            <span class='text-xs font-semibold text-slate-500 dark:text-slate-400'>{$card1Title}</span>
                            <div class='text-xl font-black font-mono text-slate-900 dark:text-white mt-1'>".number_format($poQty, 0, ',', '.')." {$lembarText}</div>
                            <p class='text-xs text-slate-400 mt-2'>{$card1Desc}</p>
                        </div>

                        <div class='p-4 rounded-xl bg-emerald-500/10 dark:bg-emerald-950/20 border border-emerald-500/20'>
                            <span class='text-xs font-semibold text-emerald-600 dark:text-emerald-400'>{$card2Title}</span>
                            <div class='text-xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1'>".number_format($qcQty, 0, ',', '.')." {$lembarText} ({$record->persentase_qc}%)</div>
                            <p class='text-xs text-slate-400 mt-2'>{$card2Desc}</p>
                        </div>

                        <div class='p-4 rounded-xl bg-orange-500/10 dark:bg-orange-950/20 border border-orange-500/20'>
                            <span class='text-xs font-semibold text-orange-600 dark:text-orange-400'>{$card3Title}</span>
                            <div class='text-xl font-black font-mono text-[#F47920] mt-1'>".number_format($kirimQty, 0, ',', '.')." {$lembarText} ({$record->persentase_kirim}%)</div>
                            <p class='text-xs text-slate-400 mt-2'>{$card3Desc}</p>
                        </div>
                    </div>

                    <div class='mt-6 p-4 rounded-xl bg-slate-100/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm'>
                        <span class='font-medium text-slate-700 dark:text-slate-300'>{$statusSelisihLabel}</span>
                        <div>{$selisihLabel}</div>
                    </div>
                </div>
            </div>
        ");
    }

    /**
     * Resolve the latest DeliveryOrderReceipt associated with a MonitoringKarung.
     */
    public static function getLatestDeliveryOrderReceipt(MonitoringKarung $record): ?DeliveryOrderReceipt
    {
        $latestItem = $record->deliveryItems()
            ->whereNotNull('delivery_order_receipt_id')
            ->reorder()
            ->latest('id')
            ->first();

        if ($latestItem && $latestItem->deliveryOrderReceipt) {
            return $latestItem->deliveryOrderReceipt;
        }

        $directDo = $record->deliveryOrderReceipts()->reorder()->latest('id')->first();
        if ($directDo) {
            return $directDo;
        }

        if ($record->purchase_order_issued_id) {
            $poDo = DeliveryOrderReceipt::whereHas('deliveryOrderReceiptDetails', function ($q) use ($record) {
                $q->where('purchase_order_issued_id', $record->purchase_order_issued_id);
            })->reorder()->latest('id')->first();

            if ($poDo) {
                return $poDo;
            }
        }

        if (! empty($record->no_po)) {
            return DeliveryOrderReceipt::whereHas('deliveryOrderReceiptDetails.purchaseOrderIssued', function ($q) use ($record) {
                $q->where('purchase_order_no', trim($record->no_po));
            })->reorder()->latest('id')->first();
        }

        return null;
    }
}
