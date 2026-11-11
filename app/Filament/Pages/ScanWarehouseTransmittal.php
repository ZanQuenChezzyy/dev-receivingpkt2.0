<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\PengirimanGudang\PengirimanGudangCluster;
use App\Models\WarehouseDeliveryBatchItem;
use App\Models\WarehouseDestination;
use App\Models\WarehouseTransmittal;
use App\Models\WarehouseTransmittalItem;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ScanWarehouseTransmittal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = PengirimanGudangCluster::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-qr-code';

    protected string $view = 'filament.pages.scan-warehouse-transmittal';

    public ?array $data = [];

    public ?int $transmittalId = null;

    public string $transmittal_no = '';

    public string $manual_input = '';

    public bool $forceNewTransmittal = false;

    public static function getNavigationLabel(): string
    {
        return 'Scan Transmittal Gudang';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Scan Transmittal Pengiriman Gudang';
    }

    public function mount(): void
    {
        $today = now()->toDateString();
        $firstDestination = WarehouseDestination::first();

        $this->form->fill([
            'tanggal' => $today,
            'warehouse_destination_id' => $firstDestination?->id,
        ]);

        $this->syncTransmittalState();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    DatePicker::make('tanggal')
                        ->label('Tanggal Transmittal')
                        ->default(now()->toDateString())
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function () {
                            $this->forceNewTransmittal = false;
                            $this->syncTransmittalState();
                        }),

                    Select::make('warehouse_destination_id')
                        ->label('Gudang Tujuan Pengiriman')
                        ->placeholder('-- Pilih Gudang Tujuan --')
                        ->options(WarehouseDestination::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function () {
                            $this->forceNewTransmittal = false;
                            $this->syncTransmittalState();
                        }),
                ]),
            ]);
    }

    public function syncTransmittalState(): void
    {
        $destinationId = $this->data['warehouse_destination_id'] ?? null;
        $dateStr = $this->data['tanggal'] ?? now()->toDateString();

        if (! $destinationId) {
            $this->transmittalId = null;
            $this->transmittal_no = static::generateTransmittalNo(Carbon::parse($dateStr));

            return;
        }

        if (! $this->forceNewTransmittal) {
            $existing = WarehouseTransmittal::where('warehouse_destination_id', $destinationId)
                ->whereDate('tanggal', Carbon::parse($dateStr)->toDateString())
                ->latest()
                ->first();

            if ($existing) {
                $this->transmittalId = $existing->id;
                $this->transmittal_no = $existing->transmittal_no;

                return;
            }
        }

        $this->transmittalId = null;
        $this->transmittal_no = static::generateTransmittalNo(Carbon::parse($dateStr));
    }

    public function startNewTransmittal(): void
    {
        $dateStr = $this->data['tanggal'] ?? now()->toDateString();
        $this->forceNewTransmittal = true;
        $this->transmittalId = null;
        $this->transmittal_no = static::generateTransmittalNo(Carbon::parse($dateStr));

        Notification::make()
            ->title('Nomor Transmittal Baru Disiapkan')
            ->body("Nomor berikutnya: {$this->transmittal_no}. Transmittal akan otomatis tersimpan saat Anda memindai barcode pertama.")
            ->info()
            ->send();
    }

    public static function generateTransmittalNo(?Carbon $date = null): string
    {
        $date = $date ?: now();
        $prefix = 'TR-WH/'.$date->format('Y').'/'.$date->format('m').'/';

        $lastTransmittal = WarehouseTransmittal::where('transmittal_no', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastTransmittal && preg_match('/\/(\d{4})$/', $lastTransmittal->transmittal_no, $matches)) {
            $sequence = intval($matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function parseScannedCode(string $raw): ?string
    {
        $clean = trim($raw);
        if (empty($clean)) {
            return null;
        }

        // Direct 14 digits match: 10 digit doc + 4 digit year (e.g. 50074772742026)
        if (preg_match('/^(\d{10})\d{4}$/', $clean, $matches)) {
            return $matches[1];
        }

        // Direct 10 digits match
        if (preg_match('/^(\d{10})$/', $clean, $matches)) {
            return $matches[1];
        }

        // 14 digits embedded inside raw string
        if (preg_match('/(\d{10})\d{4}/', $clean, $matches)) {
            return $matches[1];
        }

        // 10 digits embedded inside raw string
        if (preg_match('/(\d{10})/', $clean, $matches)) {
            return $matches[1];
        }

        return $clean;
    }

    public function submitManualScan(): void
    {
        if (empty(trim($this->manual_input))) {
            return;
        }

        $input = $this->manual_input;
        $this->manual_input = '';
        $this->processScan($input);
    }

    public function processScan(string $scannedInput): void
    {
        $cleanInput = trim($scannedInput);
        if (empty($cleanInput)) {
            return;
        }

        if (empty($this->data['warehouse_destination_id'])) {
            $this->dispatch('play-error-sound');
            Notification::make()
                ->title('Pilih Gudang Tujuan!')
                ->body('Silakan pilih Gudang Tujuan terlebih dahulu sebelum memindai barcode.')
                ->danger()
                ->send();

            return;
        }

        $matDoc105 = static::parseScannedCode($cleanInput);
        if (! $matDoc105) {
            $this->dispatch('play-error-sound');
            Notification::make()
                ->title('Kode Barcode Tidak Valid')
                ->body("Hasil scan '{$cleanInput}' tidak dapat dikenali sebagai kode Material Document 105.")
                ->danger()
                ->send();

            return;
        }

        // Search items in WarehouseDeliveryBatchItem by mat_doc_105
        $batchItems = WarehouseDeliveryBatchItem::where('mat_doc_105', $matDoc105)->get();

        if ($batchItems->isEmpty()) {
            $this->dispatch('play-error-sound');
            Notification::make()
                ->title('Data Tidak Ditemukan di Master MB51!')
                ->body("Material Document 105 [{$matDoc105}] belum diunggah di menu Master MB51.")
                ->warning()
                ->send();

            return;
        }

        // Ensure transmittal exists
        $transmittal = $this->ensureActiveTransmittal();

        $newlyAddedCount = 0;
        $alreadyInThisCount = 0;

        foreach ($batchItems as $batchItem) {
            // Check if already in this transmittal
            $existsInCurrent = WarehouseTransmittalItem::where('warehouse_transmittal_id', $transmittal->id)
                ->where(function ($q) use ($batchItem) {
                    $q->where('warehouse_delivery_batch_item_id', $batchItem->id);
                    if ($batchItem->delivery_order_receipt_detail_id) {
                        $q->orWhere('delivery_order_receipt_detail_id', $batchItem->delivery_order_receipt_detail_id);
                    }
                })
                ->exists();

            if ($existsInCurrent) {
                $alreadyInThisCount++;

                continue;
            }

            WarehouseTransmittalItem::create([
                'warehouse_transmittal_id' => $transmittal->id,
                'warehouse_delivery_batch_item_id' => $batchItem->id,
                'delivery_order_receipt_detail_id' => $batchItem->delivery_order_receipt_detail_id,
            ]);

            $batchItem->update([
                'warehouse_transmittal_id' => $transmittal->id,
                'status' => 'delivered',
            ]);

            $batchItem->batch?->updateProgressStatus();
            $newlyAddedCount++;
        }

        if ($newlyAddedCount > 0) {
            $this->dispatch('play-success-sound');
            Notification::make()
                ->title("GRS 105 [{$matDoc105}] Berhasil Discan!")
                ->body("{$newlyAddedCount} item material dimasukkan ke Transmittal {$transmittal->transmittal_no}.")
                ->success()
                ->send();
        } elseif ($alreadyInThisCount > 0) {
            $this->dispatch('play-error-sound');
            Notification::make()
                ->title('GRS Sudah Ada di Transmittal')
                ->body("Dokumen 105 [{$matDoc105}] sudah ada di daftar transmittal ini.")
                ->info()
                ->send();
        }

        $this->manual_input = '';
        $this->dispatch('focus-scanner-input');
    }

    protected function ensureActiveTransmittal(): WarehouseTransmittal
    {
        if ($this->transmittalId) {
            $transmittal = WarehouseTransmittal::find($this->transmittalId);
            if ($transmittal) {
                return $transmittal;
            }
        }

        $destinationId = $this->data['warehouse_destination_id'];
        $dateStr = $this->data['tanggal'] ?? now()->toDateString();

        if (! $this->forceNewTransmittal) {
            $existing = WarehouseTransmittal::where('warehouse_destination_id', $destinationId)
                ->whereDate('tanggal', Carbon::parse($dateStr)->toDateString())
                ->latest()
                ->first();

            if ($existing) {
                $this->transmittalId = $existing->id;
                $this->transmittal_no = $existing->transmittal_no;

                return $existing;
            }
        }

        $transmittalNo = $this->transmittal_no ?: static::generateTransmittalNo(Carbon::parse($dateStr));

        $transmittal = WarehouseTransmittal::create([
            'transmittal_no' => $transmittalNo,
            'warehouse_destination_id' => $destinationId,
            'tanggal' => Carbon::parse($dateStr)->toDateString(),
            'created_by' => Auth::id(),
        ]);

        $this->transmittalId = $transmittal->id;
        $this->transmittal_no = $transmittal->transmittal_no;
        $this->forceNewTransmittal = false;

        return $transmittal;
    }

    public function deleteItem(int $itemId): void
    {
        $item = WarehouseTransmittalItem::find($itemId);
        if (! $item) {
            return;
        }

        if ($item->batchItem) {
            $batch = $item->batchItem->batch;
            $item->batchItem->update([
                'warehouse_transmittal_id' => null,
                'status' => 'pending',
            ]);
            $batch?->updateProgressStatus();
        }

        $item->delete();

        Notification::make()
            ->title('Item Dihapus')
            ->body('Item telah dikeluarkan dari transmittal dan status dikembalikan ke pending.')
            ->success()
            ->send();
    }

    public function getScannedItemsProperty(): Collection
    {
        if (! $this->transmittalId) {
            return collect();
        }

        return WarehouseTransmittalItem::where('warehouse_transmittal_id', $this->transmittalId)
            ->with([
                'batchItem.batch',
                'batchItem.deliveryOrderReceipt',
                'detail.purchaseOrderIssued',
                'detail.deliveryOrderReceipt',
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function getActiveTransmittalProperty(): ?WarehouseTransmittal
    {
        if (! $this->transmittalId) {
            return null;
        }

        return WarehouseTransmittal::with('destination')->find($this->transmittalId);
    }
}
