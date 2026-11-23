<?php

namespace App\Filament\Pages;

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use App\Enums\AgingZone;
use App\Filament\Actions\ImportNonStockAgingAction;
use App\Models\NonStockAgingControl;
use App\Models\NonStockAgingLog;
use App\Models\NonStockAgingSnapshot;
use App\Models\RequestingUnit;
use App\Services\NonStockAgingMailDispatcher;
use App\Services\NonStockAgingReportService;
use App\Services\NonStockAgingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

class NonStockAgingMonitoring extends Page implements HasTable
{
    use HasTabs;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring Receiving';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-clock';

    protected string $view = 'filament.pages.non-stock-aging-monitoring';

    #[Url]
    public string $mrpScope = 'NONSTOCK';

    public int $refreshCounter = 0;

    public static function getNavigationGroup(): ?string
    {
        return __('Monitoring Receiving');
    }

    public static function getNavigationLabel(): string
    {
        return __('Aging Material Non-Stock & PD');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Aging Material Non-Stock & PD');
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'belum_diambil';
    }

    public function setMrpScope(string $scope): void
    {
        $allowed = ['NONSTOCK', 'PD', 'NONSTOCK_PD', 'INVESTASI', 'ALL'];
        if (in_array($scope, $allowed, true)) {
            $this->mrpScope = $scope;
            $this->resetTable();
            $this->refreshCounter++;
        }
    }

    public function updatedActiveTab(): void
    {
        $this->resetTable();
    }

    public function deleteSnapshot(int $snapshotId): void
    {
        $snapshot = NonStockAgingSnapshot::query()->find($snapshotId);

        if ($snapshot === null) {
            Notification::make()
                ->title('Snapshot tidak ditemukan')
                ->warning()
                ->send();

            return;
        }

        $label = $snapshot->label;
        $snapshot->delete();
        $this->refreshCounter++;

        Notification::make()
            ->title('Snapshot dihapus')
            ->body("Snapshot \"{$label}\" berhasil dihapus.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ImportNonStockAgingAction::make()
                    ->after(function (): void {
                        $this->refreshCounter++;
                    }),

                Action::make('syncWebDo')
                    ->label('Sinkronkan DO & MIR')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (NonStockAgingService $service): void {
                        $stats = $service->syncFromDeliveryOrderDetails();
                        $this->refreshCounter++;

                        Notification::make()
                            ->title('Sinkronisasi Web 2.0 selesai')
                            ->body("Baru: {$stats['created']} | Diperbarui: {$stats['updated']} | Auto-Close MIR: {$stats['auto_closed']} | Unit Terpetakan: {$stats['mapped']}.")
                            ->success()
                            ->send();
                    }),

                Action::make('sendWeeklyDigestManual')
                    ->label('Kirim Rekap Mingguan (Email)')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->modalHeading('Kirim Rekap Mingguan per Unit Peminta (Manual)')
                    ->modalDescription('Pilih Unit Peminta untuk mengirimkan rekap daftar material Non-Stock/PD berstatus Belum Diambil melalui Zimbra Email.')
                    ->modalSubmitActionLabel('Kirim Email Sekarang')
                    ->schema([
                        Select::make('requesting_unit_id')
                            ->label('Unit Peminta')
                            ->options(fn (): array => RequestingUnit::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('custom_to')
                            ->label('Email Tujuan Tambahan / Pengganti (Opsional)')
                            ->placeholder('Kosongkan untuk memakai email PIC pada Master Unit Peminta')
                            ->helperText('Pisahkan dengan koma jika lebih dari satu alamat email.'),
                        Textarea::make('additional_note')
                            ->label('Catatan Pengantar (Opsional)')
                            ->rows(3)
                            ->placeholder('Contoh: Rekap mingguan hari Senin, mohon penjadwalan pengambilan di area Receiving.'),
                    ])
                    ->action(function (array $data, NonStockAgingMailDispatcher $dispatcher): void {
                        $controls = $this->applyMrpScope(NonStockAgingControl::query())
                            ->where('requesting_unit_id', (int) $data['requesting_unit_id'])
                            ->whereIn('operational_status', [
                                AgingOperationalStatus::BelumDiambil->value,
                                AgingOperationalStatus::ScheduledHold->value,
                            ])
                            ->get();

                        if ($controls->isEmpty()) {
                            Notification::make()
                                ->title('Tidak ada item outstanding')
                                ->body('Unit terpilih tidak memiliki item Belum Diambil pada cakupan MRP saat ini.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $res = $dispatcher->sendPicReminder(
                            controls: $controls,
                            mode: 'weekly_digest',
                            additionalNote: $data['additional_note'] ?? null,
                            customTo: $data['custom_to'] ?? null,
                            actorId: Auth::id() !== null ? (int) Auth::id() : null,
                        );

                        $this->refreshCounter++;

                        Notification::make()
                            ->title($res['status'] === 'sent' ? 'Rekap Mingguan Terkirim' : 'Pengiriman Rekap Ditahan')
                            ->body($res['message'])
                            ->status($res['status'] === 'sent' ? 'success' : ($res['status'] === 'skipped' ? 'warning' : 'danger'))
                            ->persistent()
                            ->send();
                    }),
            ])
                ->label('Kelola Data')
                ->icon('heroicon-o-squares-plus')
                ->color('primary')
                ->button(),

            ActionGroup::make([
                Action::make('exportExcel')
                    ->label('Ekspor Excel (.xlsx)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn (NonStockAgingReportService $reportService) => $reportService->exportRegisterXlsx(
                        mrpScope: $this->mrpScope,
                        activeTab: $this->activeTab,
                    )),
                Action::make('exportCsv')
                    ->label('Ekspor CSV (.csv)')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn (NonStockAgingReportService $reportService) => $reportService->exportRegisterCsv(
                        mrpScope: $this->mrpScope,
                        activeTab: $this->activeTab,
                    )),
                Action::make('captureSnapshot')
                    ->label('Ambil Snapshot')
                    ->icon('heroicon-o-camera')
                    ->modalHeading('Simpan Snapshot')
                    ->modalDescription('Simpan rekap jumlah item, nilai tertahan, dan rincian unit saat ini.')
                    ->modalSubmitActionLabel('Simpan Snapshot')
                    ->schema([
                        TextInput::make('label')
                            ->label('Label Snapshot')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('Contoh: Snapshot November 2026'),
                        Toggle::make('pilot_only')
                            ->label('Khusus Unit Prioritas')
                            ->default(false),
                    ])
                    ->action(function (array $data, NonStockAgingReportService $reportService): void {
                        $snap = $reportService->captureSnapshot(
                            label: (string) $data['label'],
                            mrpScope: $this->mrpScope,
                            pilotOnly: (bool) ($data['pilot_only'] ?? false),
                            actorId: Auth::id() !== null ? (int) Auth::id() : null,
                        );

                        $this->refreshCounter++;

                        Notification::make()
                            ->title('Snapshot tersimpan')
                            ->body("{$snap->label}: {$snap->total_outstanding_items} item ({$snap->total_outstanding_po} PO) senilai Rp ".number_format($snap->total_outstanding_value, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
                Action::make('viewSnapshots')
                    ->label('Riwayat & Perbandingan Snapshot')
                    ->icon('heroicon-o-chart-bar-square')
                    ->modalHeading('Riwayat & Perbandingan Snapshot')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (NonStockAgingReportService $reportService): View {
                        $snapshots = NonStockAgingSnapshot::query()
                            ->where('mrp_scope', strtoupper($this->mrpScope))
                            ->orderBy('snapshot_date')
                            ->orderBy('id')
                            ->get();

                        $allScopeSnapshots = $snapshots->where('scope', 'all')->values();
                        $comparison = $allScopeSnapshots->count() >= 2
                            ? $reportService->buildBeforeAfterComparison($allScopeSnapshots->first(), $allScopeSnapshots->last())
                            : null;

                        return view('filament.pages.non-stock-aging-snapshots', [
                            'mrpScope' => strtoupper($this->mrpScope),
                            'snapshots' => $snapshots,
                            'comparison' => $comparison,
                        ]);
                    }),
            ])
                ->label('Laporan & Snapshot')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->button(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $today = Carbon::today();
        $d14 = $today->copy()->subDays(14)->toDateString();
        $d15 = $today->copy()->subDays(15)->toDateString();
        $d30 = $today->copy()->subDays(30)->toDateString();
        $d31 = $today->copy()->subDays(31)->toDateString();
        $d60 = $today->copy()->subDays(60)->toDateString();
        $d61 = $today->copy()->subDays(61)->toDateString();

        $baseActive = fn (): Builder => $this->applyMrpScope(NonStockAgingControl::query())
            ->whereIn('operational_status', [
                AgingOperationalStatus::BelumDiambil->value,
                AgingOperationalStatus::ScheduledHold->value,
            ]);

        return [
            'belum_diambil' => Tab::make('Belum Diambil')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('operational_status', [
                    AgingOperationalStatus::BelumDiambil->value,
                    AgingOperationalStatus::ScheduledHold->value,
                ]))
                ->badge($baseActive()->count()),

            'critical' => Tab::make('Critical (>60H)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                    ->whereDate('grs_posting_date', '<=', $d61))
                ->badge($baseActive()->whereDate('grs_posting_date', '<=', $d61)->count())
                ->badgeColor('danger'),

            'warning' => Tab::make('Warning (31–60H)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                    ->whereDate('grs_posting_date', '<=', $d31)
                    ->whereDate('grs_posting_date', '>=', $d60))
                ->badge($baseActive()->whereDate('grs_posting_date', '<=', $d31)->whereDate('grs_posting_date', '>=', $d60)->count())
                ->badgeColor('warning'),

            'attention' => Tab::make('Attention (15–30H)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                    ->whereDate('grs_posting_date', '<=', $d15)
                    ->whereDate('grs_posting_date', '>=', $d30))
                ->badge($baseActive()->whereDate('grs_posting_date', '<=', $d15)->whereDate('grs_posting_date', '>=', $d30)->count())
                ->badgeColor('info'),

            'normal' => Tab::make('Normal (0–14H)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                    ->where(function (Builder $sub) use ($d14): void {
                        $sub->whereDate('grs_posting_date', '>=', $d14)
                            ->orWhereNull('grs_posting_date');
                    }))
                ->badge($baseActive()->where(function (Builder $sub) use ($d14): void {
                    $sub->whereDate('grs_posting_date', '>=', $d14)->orWhereNull('grs_posting_date');
                })->count())
                ->badgeColor('success'),

            'on_check' => Tab::make('On Check')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('operational_status', AgingOperationalStatus::OnCheck->value))
                ->badge($this->applyMrpScope(NonStockAgingControl::query())->where('operational_status', AgingOperationalStatus::OnCheck->value)->count())
                ->badgeColor('info'),

            'scheduled_hold' => Tab::make('Scheduled Hold')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('operational_status', AgingOperationalStatus::ScheduledHold->value))
                ->badge($this->applyMrpScope(NonStockAgingControl::query())->where('operational_status', AgingOperationalStatus::ScheduledHold->value)->count())
                ->badgeColor('primary'),

            'pilot' => Tab::make('Unit Pilot')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                    ->whereHas('requestingUnit', fn (Builder $sub): Builder => $sub->where('is_pilot', true)))
                ->badge($baseActive()->whereHas('requestingUnit', fn (Builder $sub): Builder => $sub->where('is_pilot', true))->count()),

            'selesai_dipindah' => Tab::make('Dipindah / Selesai')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('operational_status', [
                    AgingOperationalStatus::Dipindah->value,
                    AgingOperationalStatus::SelesaiDiambil->value,
                ]))
                ->badge($this->applyMrpScope(NonStockAgingControl::query())->whereIn('operational_status', [
                    AgingOperationalStatus::Dipindah->value,
                    AgingOperationalStatus::SelesaiDiambil->value,
                ])->count())
                ->badgeColor('gray'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->buildTableQuery())
            ->columns([
                TextColumn::make('po_by_item')
                    ->label('PO-Item')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->fontFamily('mono')
                    ->description(fn (NonStockAgingControl $record): string => $record->resolvedMrpType().' • '.($record->data_source === 'web_do' ? 'Web 2.0' : 'Impor')),

                TextColumn::make('description_snapshot')
                    ->label('Material')
                    ->state(fn (NonStockAgingControl $record): string => $record->resolvedDescription())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $sub) use ($search): void {
                        $sub->where('description_snapshot', 'like', "%{$search}%")
                            ->orWhere('material_code_snapshot', 'like', "%{$search}%")
                            ->orWhere('requisitioner_raw', 'like', "%{$search}%");
                    }))
                    ->limit(42)
                    ->tooltip(fn (NonStockAgingControl $record): string => $record->resolvedDescription())
                    ->description(fn (NonStockAgingControl $record): string => collect([
                        $record->resolvedMaterialCode(),
                        'Sisa: '.rtrim(rtrim(number_format($record->resolvedRemainingQuantity(), 2, ',', '.'), '0'), ',').' '.($record->uoi_snapshot ?? 'EA'),
                    ])->filter()->implode(' • ')),

                TextColumn::make('requestingUnit.name')
                    ->label('Unit Peminta & PIC')
                    ->placeholder('Belum dipetakan')
                    ->searchable()
                    ->sortable()
                    ->description(fn (NonStockAgingControl $record): string => collect([
                        $record->resolvedPicName() ? 'PIC: '.$record->resolvedPicName() : null,
                        $record->resolvedRequisitioner() ? Str::limit($record->resolvedRequisitioner(), 34) : null,
                    ])->filter()->implode(' | ')),

                TextColumn::make('location_text')
                    ->label('Lokasi')
                    ->state(fn (NonStockAgingControl $record): string => $record->resolvedLocation() ?? '-')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('grs_posting_date')
                    ->label('Umur / Zona')
                    ->sortable()
                    ->state(function (NonStockAgingControl $record): string {
                        $days = $record->resolvedAgingDays();
                        $zone = $record->resolvedAgingZone();

                        return "{$days} Hr ({$zone->value})";
                    })
                    ->badge()
                    ->color(fn (NonStockAgingControl $record): string => $record->resolvedAgingZone()->color())
                    ->description(fn (NonStockAgingControl $record): string => 'T0: '.($record->resolvedGrsDate()?->format('d M Y') ?? 'Belum ada tgl GRS')),

                TextColumn::make('initial_value_lc')
                    ->label('Nilai Tertahan (IDR)')
                    ->sortable()
                    ->alignEnd()
                    ->state(fn (NonStockAgingControl $record): float => $record->resolvedOutstandingValue())
                    ->formatStateUsing(fn (float $state): string => $state > 0 ? 'Rp '.number_format($state, 0, ',', '.') : '-')
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('operational_status')
                    ->label('Status & Follow-Up')
                    ->badge()
                    ->formatStateUsing(fn (AgingOperationalStatus|string|null $state): string => $state instanceof AgingOperationalStatus ? $state->label() : (string) $state)
                    ->color(fn (AgingOperationalStatus|string|null $state): string => $state instanceof AgingOperationalStatus ? $state->color() : 'gray')
                    ->description(fn (NonStockAgingControl $record): string => collect([
                        $record->followup_stage?->label(),
                        $record->planned_pickup_date ? 'Rencana: '.$record->planned_pickup_date->format('d M Y') : null,
                    ])->filter()->implode(' • ')),

                IconColumn::make('physical_checked')
                    ->label('Cek Fisik')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('grs_posting_date', 'asc')
            ->filters([
                SelectFilter::make('requesting_unit_id')
                    ->label('Unit Peminta')
                    ->relationship('requestingUnit', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('followup_stage')
                    ->label('Tahap Follow-Up')
                    ->options(AgingFollowupStage::options()),
                TernaryFilter::make('physical_checked')
                    ->label('Cek Fisik'),
                SelectFilter::make('data_source')
                    ->label('Sumber Data')
                    ->options([
                        'web_do' => 'Web 2.0 (DO)',
                        'import_monitoring' => 'Impor Monitoring',
                        'import_mb51' => 'Impor SAP MB51/GRS',
                    ]),
                Filter::make('overdue_pickup')
                    ->label('Lewat Komitmen Tanggal Ambil')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('operational_status', [AgingOperationalStatus::BelumDiambil->value, AgingOperationalStatus::ScheduledHold->value])
                        ->whereNotNull('planned_pickup_date')
                        ->whereDate('planned_pickup_date', '<', Carbon::today()->toDateString())),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    Action::make('validatePhysical')
                        ->label('Validasi Fisik & Lokasi')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->fillForm(fn (NonStockAgingControl $record): array => [
                            'location_text' => $record->resolvedLocation(),
                            'requesting_unit_id' => $record->requesting_unit_id,
                            'pic_name_override' => $record->pic_name_override,
                            'pic_email_override' => $record->pic_email_override,
                            'pic_phone_override' => $record->pic_phone_override,
                            'warehouse_validation_notes' => $record->warehouse_validation_notes,
                            'mark_ready' => $record->operational_status === AgingOperationalStatus::OnCheck,
                        ])
                        ->schema([
                            TextInput::make('location_text')
                                ->label('Lokasi Fisik (Rak / Floor)')
                                ->required()
                                ->maxLength(120),
                            Select::make('requesting_unit_id')
                                ->label('Unit Peminta')
                                ->options(fn (): array => RequestingUnit::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload(),
                            TextInput::make('pic_name_override')
                                ->label('Override Nama PIC (Opsional)')
                                ->maxLength(255),
                            TextInput::make('pic_email_override')
                                ->label('Override Email PIC (Opsional)')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('pic_phone_override')
                                ->label('Override No. HP/WA PIC (Opsional)')
                                ->maxLength(40),
                            Toggle::make('mark_ready')
                                ->label('Ubah status menjadi Belum Diambil (Cek Fisik OK)')
                                ->default(true),
                            Textarea::make('warehouse_validation_notes')
                                ->label('Catatan Validasi Gudang')
                                ->rows(2),
                        ])
                        ->action(function (NonStockAgingControl $record, array $data): void {
                            $record->location_text = $data['location_text'];
                            $record->requesting_unit_id = $data['requesting_unit_id'] ?? $record->requesting_unit_id;
                            $record->pic_name_override = $data['pic_name_override'] ?: null;
                            $record->pic_email_override = $data['pic_email_override'] ?: null;
                            $record->pic_phone_override = $data['pic_phone_override'] ?: null;
                            $record->physical_checked = true;
                            $record->warehouse_validated_at = now();
                            $record->warehouse_validated_by = Auth::id();
                            $record->warehouse_validation_notes = $data['warehouse_validation_notes'] ?: null;

                            if (($data['mark_ready'] ?? false) && $record->operational_status === AgingOperationalStatus::OnCheck) {
                                $record->operational_status = AgingOperationalStatus::BelumDiambil;
                            }

                            $record->save();

                            NonStockAgingLog::create([
                                'nonstock_aging_control_id' => $record->id,
                                'action_type' => 'physical_check',
                                'aging_days_snapshot' => $record->resolvedAgingDays(),
                                'aging_zone_snapshot' => $record->resolvedAgingZone()->value,
                                'outstanding_value_snapshot' => $record->resolvedOutstandingValue(),
                                'channel' => 'Sistem',
                                'recipient_level' => 'Internal',
                                'notes' => 'Validasi fisik & lokasi ('.$record->location_text.'). '.($data['warehouse_validation_notes'] ?? ''),
                                'created_by' => Auth::id(),
                            ]);

                            $this->refreshCounter++;

                            Notification::make()
                                ->title('Validasi fisik tersimpan')
                                ->success()
                                ->send();
                        }),

                    Action::make('sendPicReminder')
                        ->label('Reminder PIC (Email Zimbra)')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->schema([
                            Select::make('mode')
                                ->label('Jenis Pesan')
                                ->options([
                                    'initial_notification' => 'Notifikasi Awal Kedatangan (0–14 Hari)',
                                    'reminder_stage_1' => 'Reminder Tahap 1 (15–30 Hari)',
                                ])
                                ->default(fn (NonStockAgingControl $record): string => $record->resolvedAgingZone() === AgingZone::Normal ? 'initial_notification' : 'reminder_stage_1')
                                ->required(),
                            TextInput::make('custom_to')
                                ->label('Email Tujuan (Kosongkan untuk default PIC)')
                                ->placeholder(fn (NonStockAgingControl $record): string => $record->resolvedPicEmail() ?: 'Belum ada email PIC'),
                            Textarea::make('additional_note')
                                ->label('Catatan Tambahan')
                                ->rows(3),
                        ])
                        ->action(function (NonStockAgingControl $record, array $data, NonStockAgingMailDispatcher $dispatcher): void {
                            $res = $dispatcher->sendPicReminder(
                                controls: new Collection([$record]),
                                mode: (string) $data['mode'],
                                additionalNote: $data['additional_note'] ?? null,
                                customTo: $data['custom_to'] ?? null,
                                actorId: Auth::id() !== null ? (int) Auth::id() : null,
                            );

                            $this->refreshCounter++;

                            Notification::make()
                                ->title($res['status'] === 'sent' ? 'Reminder PIC Terkirim' : 'Reminder Tidak Terkirim')
                                ->body($res['message'])
                                ->status($res['status'] === 'sent' ? 'success' : ($res['status'] === 'skipped' ? 'warning' : 'danger'))
                                ->send();
                        }),

                    Action::make('whatsappTemplate')
                        ->label('Template WhatsApp (wa.me)')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->modalHeading('Pesan WhatsApp Siap Kirim')
                        ->modalSubmitActionLabel('Catat Log Follow-Up WA')
                        ->modalContent(function (NonStockAgingControl $record, NonStockAgingMailDispatcher $dispatcher): View {
                            $level = match ($record->resolvedAgingZone()) {
                                AgingZone::Critical => 'VP',
                                AgingZone::Warning => 'AVP',
                                default => 'PIC',
                            };
                            $phone = match ($level) {
                                'VP' => $record->requestingUnit?->vp_phone ?: $record->resolvedPicPhone(),
                                'AVP' => $record->requestingUnit?->avp_phone ?: $record->resolvedPicPhone(),
                                default => $record->resolvedPicPhone(),
                            };
                            $recipientName = match ($level) {
                                'VP' => $record->requestingUnit?->vp_name ?: $record->resolvedPicName(),
                                'AVP' => $record->requestingUnit?->avp_name ?: $record->resolvedPicName(),
                                default => $record->resolvedPicName(),
                            };
                            $msg = $dispatcher->buildWhatsAppMessage(new Collection([$record]), $level);

                            return view('filament.pages.non-stock-aging-whatsapp-preview', [
                                'recipientName' => $recipientName,
                                'phone' => $phone,
                                'message' => $msg,
                                'waUrl' => $dispatcher->buildWhatsAppUrl($phone, $msg),
                            ]);
                        })
                        ->action(function (NonStockAgingControl $record): void {
                            $record->last_reminded_at = now();
                            $record->reminder_count = ((int) $record->reminder_count) + 1;
                            if ($record->followup_stage === AgingFollowupStage::BelumDitindaklanjuti) {
                                $record->followup_stage = AgingFollowupStage::ReminderTahap1;
                            }
                            $record->save();

                            NonStockAgingLog::create([
                                'nonstock_aging_control_id' => $record->id,
                                'action_type' => 'reminder_stage_1',
                                'aging_days_snapshot' => $record->resolvedAgingDays(),
                                'aging_zone_snapshot' => $record->resolvedAgingZone()->value,
                                'outstanding_value_snapshot' => $record->resolvedOutstandingValue(),
                                'channel' => 'WhatsApp',
                                'recipient_level' => 'PIC',
                                'recipient_name' => $record->resolvedPicName(),
                                'recipient_contact' => $record->resolvedPicPhone(),
                                'email_sent' => false,
                                'notes' => 'Follow-up dikirim melalui pesan WhatsApp.',
                                'created_by' => Auth::id(),
                            ]);

                            $this->refreshCounter++;

                            Notification::make()
                                ->title('Log WhatsApp tercatat')
                                ->success()
                                ->send();
                        }),

                    Action::make('avpCommitmentAndHold')
                        ->label('Komitmen Jadwal / Hold / Konfirmasi AVP')
                        ->icon('heroicon-o-calendar-days')
                        ->color('warning')
                        ->fillForm(fn (NonStockAgingControl $record): array => [
                            'planned_pickup_date' => $record->planned_pickup_date?->toDateString(),
                            'delay_reason_category' => $record->delay_reason_category,
                            'scheduled_hold_reason' => $record->scheduled_hold_reason,
                            'set_scheduled_hold' => $record->operational_status === AgingOperationalStatus::ScheduledHold,
                            'send_avp_email' => false,
                        ])
                        ->schema([
                            DatePicker::make('planned_pickup_date')
                                ->label('Komitmen Tanggal Ambil (GI / MIR)')
                                ->required(),
                            Select::make('delay_reason_category')
                                ->label('Kategori Kendala / Alasan')
                                ->options([
                                    'Menunggu Jadwal TA/Maintenance' => 'Menunggu Jadwal TA/Maintenance',
                                    'Menunggu Izin Kerja' => 'Menunggu Izin Kerja',
                                    'Cek Fisik / Verifikasi QC User' => 'Cek Fisik / Verifikasi QC User',
                                    'Kendala Transportasi / Alat Berat' => 'Kendala Transportasi / Alat Berat',
                                    'PIC Belum Terkonfirmasi' => 'PIC Belum Terkonfirmasi',
                                    'Lainnya' => 'Lainnya',
                                ])
                                ->required(),
                            Textarea::make('scheduled_hold_reason')
                                ->label('Keterangan Komitmen / Alasan Hold')
                                ->required()
                                ->rows(2),
                            Toggle::make('set_scheduled_hold')
                                ->label('Tandai sebagai Scheduled Hold')
                                ->default(true),
                            Toggle::make('send_avp_email')
                                ->label('Sekaligus kirim email konfirmasi ke AVP Unit Peminta (Zimbra)')
                                ->default(false),
                            TextInput::make('custom_avp_email')
                                ->label('Email AVP Pengganti (Opsional)')
                                ->placeholder(fn (NonStockAgingControl $record): string => $record->requestingUnit?->avp_email ?: 'Belum ada email AVP')
                                ->visible(fn ($get): bool => (bool) $get('send_avp_email')),
                        ])
                        ->action(function (NonStockAgingControl $record, array $data, NonStockAgingMailDispatcher $dispatcher): void {
                            $isHold = (bool) ($data['set_scheduled_hold'] ?? false);

                            $record->planned_pickup_date = $data['planned_pickup_date'];
                            $record->delay_reason_category = $data['delay_reason_category'];
                            $record->scheduled_hold_reason = $data['scheduled_hold_reason'];
                            $record->last_followup_note = $data['scheduled_hold_reason'];
                            $record->followup_stage = AgingFollowupStage::TerkonfirmasiJadwalAmbil;
                            if ($isHold) {
                                $record->operational_status = AgingOperationalStatus::ScheduledHold;
                            }
                            $record->save();

                            NonStockAgingLog::create([
                                'nonstock_aging_control_id' => $record->id,
                                'action_type' => $isHold ? 'scheduled_hold' : 'pickup_confirmed',
                                'aging_days_snapshot' => $record->resolvedAgingDays(),
                                'aging_zone_snapshot' => $record->resolvedAgingZone()->value,
                                'outstanding_value_snapshot' => $record->resolvedOutstandingValue(),
                                'channel' => 'Sistem',
                                'recipient_level' => 'AVP',
                                'recipient_name' => $record->requestingUnit?->avp_name ?? $record->resolvedPicName(),
                                'notes' => "Komitmen ambil {$data['planned_pickup_date']} ({$data['delay_reason_category']}): {$data['scheduled_hold_reason']}",
                                'created_by' => Auth::id(),
                            ]);

                            if ((bool) ($data['send_avp_email'] ?? false)) {
                                $res = $dispatcher->sendAvpWarningConfirmation(
                                    controls: new Collection([$record]),
                                    additionalNote: $data['scheduled_hold_reason'],
                                    customTo: $data['custom_avp_email'] ?? null,
                                    actorId: Auth::id() !== null ? (int) Auth::id() : null,
                                );

                                Notification::make()
                                    ->title($res['status'] === 'sent' ? 'Komitmen Tersimpan & Email AVP Terkirim' : 'Komitmen Tersimpan (Email AVP Ditahan)')
                                    ->body($res['message'])
                                    ->status($res['status'] === 'sent' ? 'success' : 'warning')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Komitmen tanggal ambil berhasil disimpan')
                                    ->success()
                                    ->send();
                            }

                            $this->refreshCounter++;
                        }),

                    Action::make('escalateVp')
                        ->label('Eskalasi Formal VP (>60H)')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->schema([
                            TextInput::make('custom_to')
                                ->label('Email VP Tujuan (Kosongkan untuk default Master Unit)')
                                ->placeholder(fn (NonStockAgingControl $record): string => $record->requestingUnit?->vp_email ?: 'Belum ada email VP'),
                            Textarea::make('additional_note')
                                ->label('Ringkasan Eskalasi / Keputusan yang Dibutuhkan')
                                ->required()
                                ->rows(3)
                                ->placeholder('Contoh: Material telah melampaui 60 hari pasca-GRS, mohon arahan percepatan GI atau keputusan teknis.'),
                        ])
                        ->action(function (NonStockAgingControl $record, array $data, NonStockAgingMailDispatcher $dispatcher): void {
                            $res = $dispatcher->sendVpCriticalEscalation(
                                controls: new Collection([$record]),
                                additionalNote: $data['additional_note'],
                                customTo: $data['custom_to'] ?? null,
                                actorId: Auth::id() !== null ? (int) Auth::id() : null,
                            );

                            $this->refreshCounter++;

                            Notification::make()
                                ->title($res['status'] === 'sent' ? 'Eskalasi Formal VP Terkirim' : 'Eskalasi VP Ditahan')
                                ->body($res['message'])
                                ->status($res['status'] === 'sent' ? 'success' : ($res['status'] === 'skipped' ? 'warning' : 'danger'))
                                ->persistent()
                                ->send();
                        }),

                    Action::make('markMovedOrDone')
                        ->label('Tandai Dipindah / Selesai Diambil')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('gray')
                        ->schema([
                            Select::make('target_status')
                                ->label('Status Akhir')
                                ->options([
                                    AgingOperationalStatus::Dipindah->value => 'Dipindah ke Gudang Lain (mis. Gudang 1)',
                                    AgingOperationalStatus::SelesaiDiambil->value => 'Selesai Diambil (GI / MIR Selesai)',
                                ])
                                ->required(),
                            TextInput::make('moved_to_location')
                                ->label('Tujuan Pemindahan / Referensi MIR')
                                ->placeholder('Contoh: CONFIRMED BY GD 1 atau No. MIR')
                                ->required(),
                        ])
                        ->action(function (NonStockAgingControl $record, array $data): void {
                            $target = AgingOperationalStatus::from((string) $data['target_status']);
                            $record->operational_status = $target;
                            $record->followup_stage = AgingFollowupStage::Selesai;
                            $record->last_followup_note = $data['moved_to_location'];

                            if ($target === AgingOperationalStatus::Dipindah) {
                                $record->moved_to_location = $data['moved_to_location'];
                                $record->moved_at = now();
                            }

                            $record->save();

                            NonStockAgingLog::create([
                                'nonstock_aging_control_id' => $record->id,
                                'action_type' => $target === AgingOperationalStatus::Dipindah ? 'moved_warehouse' : 'note',
                                'aging_days_snapshot' => $record->resolvedAgingDays(),
                                'aging_zone_snapshot' => $record->resolvedAgingZone()->value,
                                'outstanding_value_snapshot' => 0,
                                'channel' => 'Sistem',
                                'recipient_level' => 'Internal',
                                'notes' => $target->label().': '.$data['moved_to_location'],
                                'created_by' => Auth::id(),
                            ]);

                            $this->refreshCounter++;

                            Notification::make()
                                ->title('Status material diperbarui')
                                ->success()
                                ->send();
                        }),

                    Action::make('viewTimeline')
                        ->label('Riwayat Log Audit')
                        ->icon('heroicon-o-clock')
                        ->color('gray')
                        ->modalHeading(fn (NonStockAgingControl $record): string => 'Log Audit — '.$record->po_by_item)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (NonStockAgingControl $record): View => view('filament.pages.non-stock-aging-timeline', [
                            'logs' => $record->logs()->with('createdBy')->get(),
                        ])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkSendEmail')
                        ->label('Kirim Email Massal per Unit (Zimbra)')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->schema([
                            Select::make('level')
                                ->label('Level Penerima Email')
                                ->options([
                                    'PIC' => 'PIC Unit Peminta (Reminder Tahap 1 / Rekap)',
                                    'AVP' => 'AVP Unit Peminta (Konfirmasi Warning 31–60H)',
                                    'VP' => 'VP Unit Peminta (Eskalasi Critical >60H)',
                                ])
                                ->default('PIC')
                                ->required(),
                            Textarea::make('additional_note')
                                ->label('Catatan Pengantar (Opsional)')
                                ->rows(2),
                        ])
                        ->action(function (Collection $records, array $data, NonStockAgingMailDispatcher $dispatcher): void {
                            $grouped = $records->groupBy(fn (NonStockAgingControl $c): string => (string) ($c->requesting_unit_id ?? 'unmapped'));
                            $sentUnits = 0;
                            $skippedUnits = 0;

                            foreach ($grouped as $unitRecords) {
                                $res = match ($data['level']) {
                                    'VP' => $dispatcher->sendVpCriticalEscalation($unitRecords, $data['additional_note'] ?? null, null, Auth::id() !== null ? (int) Auth::id() : null),
                                    'AVP' => $dispatcher->sendAvpWarningConfirmation($unitRecords, $data['additional_note'] ?? null, null, Auth::id() !== null ? (int) Auth::id() : null),
                                    default => $dispatcher->sendPicReminder($unitRecords, 'reminder_stage_1', $data['additional_note'] ?? null, null, Auth::id() !== null ? (int) Auth::id() : null),
                                };

                                if ($res['status'] === 'sent') {
                                    $sentUnits++;
                                } else {
                                    $skippedUnits++;
                                }
                            }

                            $this->refreshCounter++;

                            Notification::make()
                                ->title('Pengiriman massal selesai')
                                ->body("Berhasil dikirim ke {$sentUnits} kelompok unit, ditahan/gagal: {$skippedUnits} kelompok unit.")
                                ->status($sentUnits > 0 ? 'success' : 'warning')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulkAssignUnit')
                        ->label('Tetapkan Unit Peminta Massal')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Select::make('requesting_unit_id')
                                ->label('Unit Peminta')
                                ->options(fn (): array => RequestingUnit::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $unitId = (int) $data['requesting_unit_id'];
                            foreach ($records as $record) {
                                $record->update(['requesting_unit_id' => $unitId]);
                            }
                            $this->refreshCounter++;

                            Notification::make()
                                ->title("{$records->count()} item berhasil dipetakan")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected function buildTableQuery(): Builder
    {
        $query = $this->applyMrpScope(
            NonStockAgingControl::query()->with([
                'deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv',
                'deliveryOrderReceiptDetail.locationReceiving',
                'deliveryOrderReceiptDetail.materialIssueDetails',
                'deliveryOrderReceiptDetail.purchaseOrderIssued',
                'purchaseOrderIssued',
                'requestingUnit',
            ])
        );

        return $this->modifyQueryWithActiveTab($query);
    }

    protected function applyMrpScope(Builder $query): Builder
    {
        $scope = strtoupper(trim($this->mrpScope));

        if ($scope === 'ALL') {
            return $query;
        }

        if ($scope === 'NONSTOCK_PD') {
            return $query->whereIn('mrp_type_snapshot', NonStockAgingService::TRACKED_MRP_TYPES);
        }

        return $query->where('mrp_type_snapshot', $scope);
    }
}
