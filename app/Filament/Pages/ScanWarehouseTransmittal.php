<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\PengirimanGudang\PengirimanGudangCluster;
use App\Models\WarehouseDeliveryBatch;
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

    /**
     * @var ?array{
     *     total_codes: int,
     *     added_items: int,
     *     matched_codes: list<string>,
     *     already_exists_codes: list<string>,
     *     not_found_codes: list<string>
     * }
     */
    public ?array $lastBatchSummary = null;

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
                Grid::make(['default' => 1, 'sm' => 12])->schema([
                    DatePicker::make('tanggal')
                        ->label('Tanggal Transmittal')
                        ->default(now()->toDateString())
                        ->required()
                        ->native(false)
                        ->live()
                        ->columnSpan(['default' => 1, 'sm' => 4])
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
                        ->columnSpan(['default' => 1, 'sm' => 8])
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
            // Jika transmittal saat ini masih cocok dengan tujuan dan tanggal yang sama, pertahankan
            if ($this->transmittalId) {
                $current = WarehouseTransmittal::where('id', $this->transmittalId)
                    ->where('warehouse_destination_id', $destinationId)
                    ->whereDate('tanggal', Carbon::parse($dateStr)->toDateString())
                    ->first();

                if ($current) {
                    $this->transmittal_no = $current->transmittal_no;

                    return;
                }
            }

            $existing = WarehouseTransmittal::where('warehouse_destination_id', $destinationId)
                ->whereDate('tanggal', Carbon::parse($dateStr)->toDateString())
                ->latest('id')
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

    /**
     * Memecah input multi-kode (dipisahkan baris baru, spasi, tab, koma, atau titik koma)
     * menjadi daftar kode unik yang siap diproses sekaligus.
     *
     * @return list<array{raw: string, parsed: string}>
     */
    public static function parseScannedCodes(string $raw): array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [];
        }

        $tokens = preg_split('/[\r\n,;\t\s]+/', $trimmed) ?: [];
        $results = [];
        $seen = [];

        foreach ($tokens as $token) {
            $cleanToken = trim($token);
            if ($cleanToken === '') {
                continue;
            }

            $parsed = static::parseScannedCode($cleanToken);
            if (! $parsed) {
                continue;
            }

            $dedupKey = strtoupper($parsed);
            if (isset($seen[$dedupKey])) {
                continue;
            }

            $seen[$dedupKey] = true;
            $results[] = [
                'raw' => $cleanToken,
                'parsed' => $parsed,
            ];
        }

        return $results;
    }

    public function clearBatchSummary(): void
    {
        $this->lastBatchSummary = null;
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

        $parsedEntries = static::parseScannedCodes($cleanInput);
        if ($parsedEntries === []) {
            $this->dispatch('play-error-sound');
            Notification::make()
                ->title('Kode Barcode Tidak Valid')
                ->body("Hasil scan '{$cleanInput}' tidak dapat dikenali sebagai kode Material Document 105 atau Kode Material.")
                ->danger()
                ->send();

            return;
        }

        // Mode tunggal (1 kode dari scanner tembak / kamera / input manual 1 baris)
        if (count($parsedEntries) === 1) {
            $this->lastBatchSummary = null;
            $entry = $parsedEntries[0];
            $transmittal = null;
            $affectedBatches = [];

            $outcome = $this->processSingleScannedCode(
                rawToken: $entry['raw'],
                parsedCode: $entry['parsed'],
                transmittal: $transmittal,
                affectedBatches: $affectedBatches,
            );

            foreach ($affectedBatches as $batch) {
                $batch->updateProgressStatus();
            }

            if ($outcome['status'] === 'added' && $transmittal) {
                $label = $outcome['matched_by'] === 'material_code'
                    ? "Kode Material [{$entry['raw']}]"
                    : "GRS 105 [{$entry['parsed']}]";

                $this->dispatch('play-success-sound');
                Notification::make()
                    ->title("{$label} Berhasil Discan!")
                    ->body("{$outcome['added_count']} item material dimasukkan ke Transmittal {$transmittal->transmittal_no}.")
                    ->success()
                    ->send();
            } elseif ($outcome['status'] === 'already_exists') {
                $label = $outcome['matched_by'] === 'material_code'
                    ? "Kode Material [{$entry['raw']}]"
                    : "Dokumen 105 [{$entry['parsed']}]";

                $this->dispatch('play-error-sound');
                Notification::make()
                    ->title('Data Sudah Ada di Transmittal')
                    ->body("{$label} sudah ada di daftar transmittal ini.")
                    ->info()
                    ->send();
            } else {
                $this->dispatch('play-error-sound');
                Notification::make()
                    ->title('Data Tidak Ditemukan di Master MB51!')
                    ->body("Kode [{$entry['parsed']}] belum ditemukan pada Master MB51 (baik sebagai MatDoc 105 maupun Kode Material berstatus pending).")
                    ->warning()
                    ->send();
            }

            $this->manual_input = '';
            $this->dispatch('focus-scanner-input');

            return;
        }

        // Mode banyak kode sekaligus (hasil salin-tempel dari Excel / daftar GRS 105 atau Kode Material)
        $transmittal = null;
        $affectedBatches = [];
        $matchedCodes = [];
        $alreadyExistsCodes = [];
        $notFoundCodes = [];
        $totalAddedItems = 0;

        foreach ($parsedEntries as $entry) {
            $outcome = $this->processSingleScannedCode(
                rawToken: $entry['raw'],
                parsedCode: $entry['parsed'],
                transmittal: $transmittal,
                affectedBatches: $affectedBatches,
            );

            $displayCode = $outcome['matched_by'] === 'material_code' ? $entry['raw'] : $entry['parsed'];

            if ($outcome['status'] === 'added') {
                $matchedCodes[] = $displayCode;
                $totalAddedItems += $outcome['added_count'];
            } elseif ($outcome['status'] === 'already_exists') {
                $alreadyExistsCodes[] = $displayCode;
            } else {
                $notFoundCodes[] = $entry['parsed'];
            }
        }

        foreach ($affectedBatches as $batch) {
            $batch->updateProgressStatus();
        }

        $this->lastBatchSummary = [
            'total_codes' => count($parsedEntries),
            'added_items' => $totalAddedItems,
            'matched_codes' => $matchedCodes,
            'already_exists_codes' => $alreadyExistsCodes,
            'not_found_codes' => $notFoundCodes,
        ];

        $parts = [];
        if ($totalAddedItems > 0) {
            $parts[] = count($matchedCodes)." kode berhasil ({$totalAddedItems} item masuk)";
        }
        if ($alreadyExistsCodes !== []) {
            $parts[] = count($alreadyExistsCodes).' kode sudah ada di transmittal';
        }
        if ($notFoundCodes !== []) {
            $parts[] = count($notFoundCodes).' kode tidak ditemukan di Master MB51';
        }

        $summaryText = implode(' • ', $parts);

        if ($totalAddedItems > 0 && $notFoundCodes === []) {
            $this->dispatch('play-success-sound');
            Notification::make()
                ->title('Proses Multi-Kode Selesai')
                ->body($summaryText.($transmittal ? " ke {$transmittal->transmittal_no}." : '.'))
                ->success()
                ->send();
        } elseif ($totalAddedItems > 0) {
            $this->dispatch('play-success-sound');
            Notification::make()
                ->title('Proses Multi-Kode Selesai Sebagian')
                ->body($summaryText.'. Periksa rincian kode yang belum ditemukan di bawah kotak input.')
                ->warning()
                ->send();
        } elseif ($alreadyExistsCodes !== [] && $notFoundCodes === []) {
            $this->dispatch('play-error-sound');
            Notification::make()
                ->title('Seluruh Kode Sudah Ada di Transmittal')
                ->body($summaryText.'.')
                ->info()
                ->send();
        } else {
            $this->dispatch('play-error-sound');
            Notification::make()
                ->title('Tidak Ada Kode yang Berhasil Dimasukkan')
                ->body($summaryText.'.')
                ->warning()
                ->send();
        }

        $this->manual_input = '';
        $this->dispatch('focus-scanner-input');
    }

    /**
     * Memproses 1 kode (MatDoc 105 terlebih dahulu, lalu fallback ke Kode Material berstatus pending).
     *
     * @param  array<int, WarehouseDeliveryBatch>  $affectedBatches
     * @return array{status: 'added'|'already_exists'|'not_found', added_count: int, matched_by: 'mat_doc_105'|'material_code'|null}
     */
    protected function processSingleScannedCode(
        string $rawToken,
        string $parsedCode,
        ?WarehouseTransmittal &$transmittal,
        array &$affectedBatches,
    ): array {
        $matchedBy = 'mat_doc_105';
        $batchItems = WarehouseDeliveryBatchItem::with(['batch', 'deliveryOrderReceiptDetail'])
            ->where('mat_doc_105', $parsedCode)
            ->get();

        // Fallback ke Kode Material bila tidak cocok dengan MatDoc 105 (hanya ambil item pending agar tidak menarik batch lama yang sudah terkirim)
        if ($batchItems->isEmpty()) {
            $matchedBy = 'material_code';
            $batchItems = WarehouseDeliveryBatchItem::with(['batch', 'deliveryOrderReceiptDetail'])
                ->where('material_code', $rawToken)
                ->where('status', 'pending')
                ->get();

            if ($batchItems->isEmpty() && $this->transmittalId) {
                $alreadyInActive = WarehouseDeliveryBatchItem::query()
                    ->where('material_code', $rawToken)
                    ->where('warehouse_transmittal_id', $this->transmittalId)
                    ->exists();

                if ($alreadyInActive) {
                    return [
                        'status' => 'already_exists',
                        'added_count' => 0,
                        'matched_by' => 'material_code',
                    ];
                }
            }
        }

        if ($batchItems->isEmpty()) {
            return [
                'status' => 'not_found',
                'added_count' => 0,
                'matched_by' => null,
            ];
        }

        $transmittal ??= $this->ensureActiveTransmittal();

        $newlyAddedCount = 0;
        $alreadyInThisCount = 0;

        foreach ($batchItems as $batchItem) {
            $existsInCurrent = WarehouseTransmittalItem::where('warehouse_transmittal_id', $transmittal->id)
                ->where(function ($q) use ($batchItem): void {
                    $q->where('warehouse_delivery_batch_item_id', $batchItem->id);
                    if ($batchItem->delivery_order_receipt_detail_id) {
                        $q->orWhere(function ($legacy) use ($batchItem): void {
                            $legacy->whereNull('warehouse_delivery_batch_item_id')
                                ->where('delivery_order_receipt_detail_id', $batchItem->delivery_order_receipt_detail_id);
                        });
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

            if ($batchItem->deliveryOrderReceiptDetail) {
                $batchItem->deliveryOrderReceiptDetail->update([
                    'warehouse_destination_id' => $transmittal->warehouse_destination_id,
                ]);
            }

            if ($batchItem->batch) {
                $affectedBatches[$batchItem->batch->id] = $batchItem->batch;
            }

            $newlyAddedCount++;
        }

        if ($newlyAddedCount > 0) {
            return [
                'status' => 'added',
                'added_count' => $newlyAddedCount,
                'matched_by' => $matchedBy,
            ];
        }

        if ($alreadyInThisCount > 0) {
            return [
                'status' => 'already_exists',
                'added_count' => 0,
                'matched_by' => $matchedBy,
            ];
        }

        return [
            'status' => 'not_found',
            'added_count' => 0,
            'matched_by' => null,
        ];
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
                ->latest('id')
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

            if ($item->batchItem->deliveryOrderReceiptDetail) {
                $item->batchItem->deliveryOrderReceiptDetail->update([
                    'warehouse_destination_id' => null,
                ]);
            }
        }

        if ($item->detail) {
            $item->detail->update([
                'warehouse_destination_id' => null,
            ]);
        }

        $item->delete();

        Notification::make()
            ->title('Item Dihapus')
            ->body('Item telah dikeluarkan dari transmittal dan status dikembalikan ke pending.')
            ->success()
            ->send();
    }

    public function selectTransmittal(int|string $id): void
    {
        $id = (int) $id;
        $transmittal = WarehouseTransmittal::find($id);
        if ($transmittal) {
            $this->transmittalId = $transmittal->id;
            $this->transmittal_no = $transmittal->transmittal_no;
            $this->forceNewTransmittal = false;
            $this->dispatch('focus-scanner-input');

            Notification::make()
                ->title('Beralih ke Transmittal')
                ->body("Transmittal aktif: {$transmittal->transmittal_no}")
                ->info()
                ->send();
        }
    }

    public function getAvailableTransmittalsProperty(): Collection
    {
        $destinationId = $this->data['warehouse_destination_id'] ?? null;
        $dateStr = $this->data['tanggal'] ?? now()->toDateString();

        if (! $destinationId) {
            return collect();
        }

        return WarehouseTransmittal::where('warehouse_destination_id', $destinationId)
            ->whereDate('tanggal', Carbon::parse($dateStr)->toDateString())
            ->withCount('items')
            ->orderByDesc('id')
            ->get();
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
