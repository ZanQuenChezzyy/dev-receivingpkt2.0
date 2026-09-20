<?php

namespace App\Filament\Pages;

use App\Models\DeliveryOrderReceiptDetail;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use UnitEnum;

class LaporanMaterialDiambil extends Page implements HasTable
{
    use HasTabs, InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public static function getNavigationLabel(): string
    {
        return 'Laporan Material Diambil';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Material Diambil (PD & Nonstock)';
    }

    protected string $view = 'filament.pages.laporan-material-diambil';

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    protected function getDeliveryOrderDetailQuery(): Builder
    {
        $query = DeliveryOrderReceiptDetail::query()
            ->with(['deliveryOrderReceipt', 'materialIssueDetails.materialIssue']);

        $query->whereIn('mrp_type', ['PD', 'NONSTOCK']);

        return $query;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge($this->getDeliveryOrderDetailQuery()->count()),
            'pd' => Tab::make('PD')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('mrp_type', 'PD'))
                ->badge($this->getDeliveryOrderDetailQuery()->where('mrp_type', 'PD')->count()),
            'nonstock' => Tab::make('NONSTOCK')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('mrp_type', 'NONSTOCK'))
                ->badge($this->getDeliveryOrderDetailQuery()->where('mrp_type', 'NONSTOCK')->count()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getDeliveryOrderDetailQuery())
            ->modifyQueryUsing(fn(Builder $query) => $this->modifyQueryWithActiveTab($query))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('deliveryOrderReceipt.received_date')
                    ->label('Tgl Penerimaan (DO)')
                    ->date('d M Y')
                    ->sortable(),
                ColumnGroup::make('Informasi Material', [
                    TextColumn::make('material_code')
                        ->label('Material No')
                        ->icon(Heroicon::Cube)
                        ->iconColor('gray')
                        ->weight(FontWeight::SemiBold)
                        ->searchable()
                        ->getStateUsing(fn($record) => $record->material_code ?: str($record->description)->limit(40))
                        ->description(fn($record) => $record->material_code ? str($record->description)->limit(40) : '')
                        ->sortable(),
                    TextColumn::make('mrp_type')
                        ->label('MRP Type')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'PD' => 'info',
                            'NONSTOCK' => 'warning',
                            default => 'gray',
                        })
                        ->sortable(),
                ]),
                ColumnGroup::make('Kuantitas', [
                    TextColumn::make('quantity')
                        ->label('Diterima')
                        ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                        ->suffix(fn($record) => ' ' . $record->uoi)
                        ->sortable(),
                    TextColumn::make('issued_quantity')
                        ->label('Diambil')
                        ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                        ->suffix(fn($record) => ' ' . $record->uoi)
                        ->sortable(),
                ]),
                TextColumn::make('status_pengambilan')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $issued = (float) $record->issued_quantity;
                        $qty = (float) $record->quantity;
                        if ($issued <= 0) {
                            return 'Belum Diambil';
                        }
                        if ($issued < $qty) {
                            return 'Sebagian Diambil';
                        }

                        return 'Sudah Diambil';
                    })
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'Belum Diambil' => 'danger',
                        'Sebagian Diambil' => 'warning',
                        'Sudah Diambil' => 'success',
                        default => 'gray'
                    }),
                TextColumn::make('tanggal_pengambilan')
                    ->label('Tgl Pengambilan Terakhir')
                    ->getStateUsing(function ($record) {
                        $issues = $record->materialIssueDetails;
                        if ($issues->isEmpty()) {
                            return '-';
                        }

                        return $issues->last()->materialIssue?->tanggal?->format('d M Y') ?? '-';
                    }),
                TextColumn::make('peminta_terakhir')
                    ->label('Peminta / Dept (Terakhir)')
                    ->getStateUsing(function ($record) {
                        $issues = $record->materialIssueDetails;
                        if ($issues->isEmpty()) {
                            return '-';
                        }
                        $issue = $issues->last()->materialIssue;
                        if (!$issue) {
                            return '-';
                        }

                        return $issue->diminta_oleh . ' (' . $issue->departemen . ')';
                    }),
            ])
            ->deferFilters(false)
            ->filtersFormColumns(3)
            ->filters([
                DateRangeFilter::make('tanggal_penerimaan')
                    ->label('Tanggal Penerimaan (DO)')
                    ->placeholder('Pilih rentang tanggal')
                    ->defaultThisMonth()
                    ->modifyQueryUsing(
                        fn(Builder $query, ?Carbon $startDate, ?Carbon $endDate, $dateString) => $query->when(
                            !empty($dateString),
                            fn(Builder $query) => $query->whereHas(
                                'deliveryOrderReceipt',
                                fn($q) => $q->whereBetween('received_date', [$startDate, $endDate])
                            )
                        )
                    ),

                SelectFilter::make('status_pengambilan')
                    ->label('Status Pengambilan')
                    ->native(false)
                    ->default('belum')
                    ->options([
                        'belum' => 'Belum Diambil',
                        'sudah' => 'Sudah Diambil (Sebagian/Seluruhnya)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'belum') {
                            return $query->whereDoesntHave('materialIssueDetails');
                        } elseif ($data['value'] === 'sudah') {
                            return $query->whereHas('materialIssueDetails');
                        }

                        return $query;
                    }),
                SelectFilter::make('mrp_type')
                    ->label('MRP Type')
                    ->native(false)
                    ->options([
                        'PD' => 'PD',
                        'NONSTOCK' => 'NONSTOCK',
                    ]),
            ], layout: FiltersLayout::AboveContent);
    }
}
