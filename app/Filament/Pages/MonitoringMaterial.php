<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderIssued;
use UnitEnum;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Filament\Resources\Concerns\HasTabs;
use Illuminate\Contracts\Support\Htmlable;

class MonitoringMaterial extends Page implements HasTable
{
    use InteractsWithTable, HasTabs;

    protected static string|UnitEnum|null $navigationGroup = 'Penerimaan Receiving';
    protected static ?int $navigationSort = 10;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';
    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-chart-pie';

    public static function getNavigationLabel(): string
    {
        return 'Monitoring Material';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Monitoring Material';
    }

    protected string $view = 'filament.pages.monitoring-material';

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function getTabs(): array
    {
        return [
            'v1' => Tab::make('V1')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('delivery_order_receipt_details.mrp_type', 'V1'))
                ->badge(DeliveryOrderReceiptDetail::where('mrp_type', 'V1')->count()),
            'pd' => Tab::make('PD')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('delivery_order_receipt_details.mrp_type', 'PD'))
                ->badge(DeliveryOrderReceiptDetail::where('mrp_type', 'PD')->count()),
            'nonstock' => Tab::make('NONSTOCK')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('delivery_order_receipt_details.mrp_type', 'NONSTOCK'))
                ->badge(DeliveryOrderReceiptDetail::where('mrp_type', 'NONSTOCK')->count()),
            'investasi' => Tab::make('INVESTASI')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('delivery_order_receipt_details.mrp_type', 'INVESTASI'))
                ->badge(DeliveryOrderReceiptDetail::where('mrp_type', 'INVESTASI')->count()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DeliveryOrderReceiptDetail::query()
                    ->leftJoin('delivery_order_receipts', 'delivery_order_receipt_details.delivery_order_receipt_id', '=', 'delivery_order_receipts.id')
                    ->select('delivery_order_receipt_details.*')
            )
            ->modifyQueryUsing(fn (Builder $query) => $this->modifyQueryWithActiveTab($query))
            ->defaultSort(function (Builder $query) {
                // Di urutkan berdasarkan data yang masih berjalan atau belum GRS/RDTV paling atas
                $query->orderByRaw("CASE WHEN delivery_order_receipts.status IN ('GRS', 'RDTV') THEN 1 ELSE 0 END")
                    ->orderBy('delivery_order_receipts.created_at', 'desc');
            })
            ->columns([
                ColumnGroup::make('Informasi PO & DO', [
                    TextColumn::make('po_and_do')
                        ->label('Nomor PO & DO')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->getStateUsing(fn($record) => $record->purchaseOrderIssued?->purchase_order_no ?? 'Tanpa PO')
                        ->description(fn($record) => 'DO: ' . ($record->deliveryOrderReceipt?->delivery_order_no ?? '-'))
                        ->searchable(query: function (Builder $query, string $search) {
                            $query->whereHas('purchaseOrderIssued', function ($q) use ($search) {
                                $q->where('purchase_order_no', 'like', "%{$search}%");
                            })
                                ->orWhereHas('deliveryOrderReceipt', function ($q) use ($search) {
                                    $q->where('document_code', 'like', "%{$search}%")
                                        ->orWhere('delivery_order_no', 'like', "%{$search}%");
                                });
                        })
                        ->sortable(query: function (Builder $query, string $direction) {
                            return $query->orderBy(
                                PurchaseOrderIssued::select('purchase_order_no')
                                    ->whereColumn('purchase_order_issueds.id', 'delivery_order_receipt_details.purchase_order_issued_id'),
                                $direction
                            );
                        }),
                ]),
                ColumnGroup::make('Detail Material', [
                    TextColumn::make('material_code')
                        ->label('Material No')
                        ->icon(Heroicon::Cube)
                        ->iconColor('gray')
                        ->weight(FontWeight::SemiBold)
                        ->searchable()
                        ->description(fn($record) => str($record->description)->limit(40))
                        ->sortable(),
                ]),
                ColumnGroup::make('Kuantitas', [
                    TextColumn::make('quantity')
                        ->label('Masuk')
                        ->numeric(
                            decimalPlaces: 2,
                            decimalSeparator: ',',
                            thousandsSeparator: '.',
                        )
                        ->suffix(fn($record) => ' ' . $record->uoi)
                        ->sortable(),
                    TextColumn::make('diambil')
                        ->label('Diambil')
                        ->getStateUsing(fn($record) => $record->issued_quantity)
                        ->numeric(
                            decimalPlaces: 2,
                            decimalSeparator: ',',
                            thousandsSeparator: '.',
                        )
                        ->suffix(fn($record) => ' ' . $record->uoi),
                    TextColumn::make('sisa')
                        ->label('Sisa')
                        ->getStateUsing(fn($record) => max(0, $record->quantity - $record->issued_quantity))
                        ->numeric(
                            decimalPlaces: 2,
                            decimalSeparator: ',',
                            thousandsSeparator: '.',
                        )
                        ->color(fn($state) => $state == 0 ? 'success' : 'warning')
                        ->suffix(fn($record) => ' ' . $record->uoi),
                ]),
                ColumnGroup::make('Status', [
                    TextColumn::make('status_grs')
                        ->label('Status GRS')
                        ->getStateUsing(fn($record) => $record->deliveryOrderReceipt?->status ?? 'Menunggu')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'GRS' => 'success',
                            'RDTV' => 'warning',
                            'Menunggu' => 'gray',
                            default => 'primary',
                        }),
                    TextColumn::make('lokasi')
                        ->label('Lokasi Terkini')
                        ->getStateUsing(function ($record) {
                            if ($record->warehouseDestination) {
                                return $record->warehouseDestination->name;
                            }
                            return $record->locationReceiving?->name ?? '-';
                        })
                        ->icon('heroicon-m-map-pin'),
                ]),
            ])
            ->filters([
                DateRangeFilter::make('delivery_order_receipts.received_date')
                    ->label('Rentang Tanggal DO')
                    ->placeholder('Pilih rentang tanggal'),

                SelectFilter::make('status_grs')
                    ->label('Status GRS / RDTV')
                    ->placeholder('Pilih Salah Satu Opsi')
                    ->native(false)
                    ->options([
                        'GRS' => 'GRS',
                        'RDTV' => 'RDTV',
                        'Draft' => 'Draft / Menunggu',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            if ($data['value'] === 'Draft') {
                                $query->where(function ($q) {
                                    $q->whereNull('delivery_order_receipts.status')
                                        ->orWhereNotIn('delivery_order_receipts.status', ['GRS', 'RDTV']);
                                });
                            } else {
                                $query->where('delivery_order_receipts.status', $data['value']);
                            }
                        }
                    }),

                SelectFilter::make('sisa_material')
                    ->label('Ketersediaan Material')
                    ->options([
                        'ada' => 'Ada Sisa (Belum Habis)',
                        'habis' => 'Habis (Full Diambil)',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $subquery = '(SELECT COALESCE(SUM(diserahkan), 0) FROM material_issue_details WHERE material_issue_details.delivery_order_receipt_detail_id = delivery_order_receipt_details.id)';
                            if ($data['value'] === 'ada') {
                                $query->whereRaw("delivery_order_receipt_details.quantity > {$subquery}");
                            } elseif ($data['value'] === 'habis') {
                                $query->whereRaw("delivery_order_receipt_details.quantity <= {$subquery}");
                            }
                        }
                    }),

                SelectFilter::make('is_physically_received')
                    ->label('Status Fisik Barang')
                    ->options([
                        '1' => 'Fisik Sudah Diterima',
                        '0' => 'Belum (Hanya Dokumen)',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value']) && $data['value'] !== '') {
                            $query->whereHas('deliveryOrderReceipt', function ($q) use ($data) {
                                $q->where('is_physically_received', (bool) $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('delay_status')
                    ->label('Kendala Penerimaan')
                    ->options([
                        'pending' => 'Ada Kendala / Ditunda',
                        'lancar' => 'Lancar',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('deliveryOrderReceipt', function ($q) use ($data) {
                                if ($data['value'] === 'pending') {
                                    $q->whereNotNull('delay_reason')->where('delay_reason', '!=', '');
                                } else {
                                    $q->where(function ($query) {
                                        $query->whereNull('delay_reason')->orWhere('delay_reason', '');
                                    });
                                }
                            });
                        }
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_do')
                        ->label('Detail')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->url(fn($record) => $record->delivery_order_receipt_id ? DeliveryOrderReceiptResource::getUrl('view', ['record' => $record->delivery_order_receipt_id]) : null)
                        ->visible(fn($record) => $record->delivery_order_receipt_id !== null),

                    Action::make('cetak_mir')
                        ->label('Cetak MIR')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(function ($record) {
                            $latestIssue = $record->materialIssueDetails()->latest()->first();
                            if ($latestIssue && $latestIssue->material_issue_id) {
                                return route('filament.admin.resources.material-issues.print', $latestIssue->material_issue_id);
                            }
                            return null;
                        })
                        ->openUrlInNewTab()
                        ->visible(fn($record) => $record->issued_quantity > 0),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ]);
    }
}
