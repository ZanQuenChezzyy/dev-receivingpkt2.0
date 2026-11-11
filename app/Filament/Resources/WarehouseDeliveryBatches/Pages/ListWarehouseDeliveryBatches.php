<?php

namespace App\Filament\Resources\WarehouseDeliveryBatches\Pages;

use App\Filament\Resources\WarehouseDeliveryBatches\WarehouseDeliveryBatchResource;
use App\Services\SapMb51ImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListWarehouseDeliveryBatches extends ListRecords
{
    protected static string $resource = WarehouseDeliveryBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_mb51')
                ->label('Upload File MB51 SAP')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Upload Data MB51 SAP untuk Pengiriman Gudang')
                ->modalDescription('Unggah file ekspor SAP MB51 (CSV atau Excel .xlsx) dan tentukan rentang tanggal penerimaan. Seluruh data akan otomatis dibungkus ke dalam 1 batch master pengiriman.')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Mulai Import Data')
                ->form([
                    Grid::make(2)->schema([
                        DatePicker::make('start_date')
                            ->label('Tanggal Awal Penerimaan')
                            ->required()
                            ->default(now()->startOfWeek()->toDateString())
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('batch_title', $this->generateDefaultTitle($state, $get('end_date')))
                            ),

                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir Penerimaan')
                            ->required()
                            ->default(now()->endOfWeek()->toDateString())
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('batch_title', $this->generateDefaultTitle($get('start_date'), $state))
                            ),
                    ]),

                    TextInput::make('batch_title')
                        ->label('Judul / Keterangan Batch')
                        ->required()
                        ->default(fn () => $this->generateDefaultTitle(now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()))
                        ->placeholder('Contoh: Daftar Pengiriman Material Diterima 01 - 07 Juli 2026')
                        ->maxLength(255),

                    FileUpload::make('file')
                        ->label('File MB51 SAP (CSV / Excel .xlsx)')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/octet-stream',
                            '.csv',
                            '.xlsx',
                            '.xls',
                        ])
                        ->disk('local')
                        ->directory('temp-mb51-uploads')
                        ->required()
                        ->helperText('File hasil ekspor transaksi MB51 dari SAP. Baris ringkasan (footer totals) akan otomatis dibersihkan oleh sistem.')
                        ->storeFiles(),

                    Textarea::make('notes')
                        ->label('Catatan Tambahan (Opsional)')
                        ->rows(2)
                        ->placeholder('Catatan atau keterangan batch pengiriman mingguan...'),
                ])
                ->action(function (array $data) {
                    $relativeFilePath = $data['file'];
                    $fullPath = Storage::disk('local')->path($relativeFilePath);
                    $originalFileName = basename($relativeFilePath);

                    try {
                        $service = app(SapMb51ImportService::class);
                        $batch = $service->import([
                            'file_path' => $fullPath,
                            'file_name' => $originalFileName,
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                            'batch_title' => $data['batch_title'],
                            'uploaded_by' => Auth::id() ?? 1,
                        ]);

                        Notification::make()
                            ->title('Upload MB51 Berhasil!')
                            ->body("Berhasil mengimpor {$batch->total_items} data material ke dalam batch '{$batch->batch_title}'.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Mengimpor MB51')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function generateDefaultTitle(?string $start, ?string $end): string
    {
        if (! $start || ! $end) {
            return 'Daftar Pengiriman Material Diterima';
        }

        try {
            $formattedStart = Carbon::parse($start)->format('d M Y');
            $formattedEnd = Carbon::parse($end)->format('d M Y');

            return "Daftar Pengiriman Material Diterima {$formattedStart} - {$formattedEnd}";
        } catch (\Throwable $e) {
            return 'Daftar Pengiriman Material Diterima';
        }
    }
}
