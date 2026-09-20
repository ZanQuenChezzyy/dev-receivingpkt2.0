<?php

namespace App\Filament\Pages;

use App\Models\DeliveryOrderReceiptDetail;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class LaporanBulananReceiving extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Laporan Bulanan';
    }

    protected string $view = 'filament.pages.laporan-bulanan-receiving';

    public ?string $tahun = null;

    public function mount()
    {
        $this->tahun = date('Y');
        $this->form->fill([
            'tahun' => $this->tahun,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $years = [];
        $currentYear = date('Y');
        for ($i = $currentYear - 5; $i <= $currentYear + 2; $i++) {
            $years[$i] = $i;
        }

        return $schema
            ->schema([
                Select::make('tahun')
                    ->label('Tahun')
                    ->options($years)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->tahun = $state),
            ]);
    }

    public function getReportDataProperty()
    {
        $year = $this->tahun ?? date('Y');
        $months = range(1, 12);

        // Inisialisasi struktur data
        $data = [
            'lead_time' => [
                'sparepart' => $this->initLeadTimeMonths(),
                'npk' => $this->initLeadTimeMonths(),
                'chemical' => $this->initLeadTimeMonths(),
            ],
            'rdtv' => $this->initBaseMonths(),
            'mrp' => $this->initBaseMonths(),
        ];

        // 1. Ambil semua DO Detail untuk tahun tersebut (berdasarkan received_date DO)
        $details = DeliveryOrderReceiptDetail::with(['deliveryOrderReceipt.grsRdtvItems.grsRdtv', 'deliveryOrderReceipt.qcHistories'])
            ->whereHas('deliveryOrderReceipt', function ($q) use ($year) {
                $q->whereYear('received_date', $year);
            })
            ->get();

        foreach ($details as $detail) {
            $do = $detail->deliveryOrderReceipt;
            if (! $do || ! $do->received_date) {
                continue;
            }

            $month = (int) Carbon::parse($do->received_date)->format('n');

            // --- Cek Kategori Material ---
            $matType = strtoupper($detail->material_type ?? '');
            $category = 'sparepart'; // Default
            if (in_array($matType, ['ZFP', 'ZRM'])) {
                $category = 'npk';
            } elseif (in_array($matType, ['ZSM', 'ZPM'])) {
                $category = 'chemical';
            } elseif ($matType === 'ZSP') {
                $category = 'sparepart';
            }

            // --- Laporan Barang Berdasarkan MRP Type ---
            $mrp = strtoupper($detail->mrp_type ?? '');
            if (! isset($data['mrp'][$month][$mrp])) {
                $data['mrp'][$month][$mrp] = 0;
            }
            $data['mrp'][$month][$mrp]++;
            $data['mrp'][$month]['total']++;

            // --- Laporan Barang Ditolak (RDTV) ---
            if ($do->status === 'RDTV') {
                $data['rdtv'][$month]['total']++;
            }

            // --- Laporan Lead Time (Hanya yang GRS) ---
            if ($do->status === 'GRS') {
                // Cari transaksi GRS
                $grsDate = null;
                if ($do->grsRdtvItems) {
                    foreach ($do->grsRdtvItems as $grsItem) {
                        if ($grsItem->grsRdtv && strtoupper($grsItem->grsRdtv->category ?? '') === 'GRS') {
                            $grsDate = Carbon::parse($grsItem->grsRdtv->transaction_date);
                            break;
                        }
                    }
                }

                if ($grsDate) {
                    $receivedDate = Carbon::parse($do->received_date)->startOfDay();
                    $grsDate = Carbon::parse($grsItem->grsRdtv->transaction_date)->startOfDay();
                    $rawLeadTime = max(0, (int) $receivedDate->diffInDays($grsDate));

                    // Hitung Waktu QC
                    $qcTime = 0;
                    $kirimDate = null;
                    $qcHistories = $do->qcHistories->sortBy('created_at');
                    foreach ($qcHistories as $qc) {
                        if ($qc->status === 'Kirim') {
                            $kirimDate = $qc->created_at->copy()->startOfDay();
                        } elseif ($qc->status === 'Kembali' && $kirimDate) {
                            $kembaliDate = $qc->created_at->copy()->startOfDay();
                            $qcTime += max(0, (int) $kirimDate->diffInDays($kembaliDate));
                            $kirimDate = null;
                        }
                    }

                    // Net Lead Time
                    $netLeadTime = max(1, (int) ($rawLeadTime - $qcTime)); // minimal 1 hari (asumsi hari yang sama dihitung 1)

                    $bucket = $netLeadTime > 8 ? '>8' : (string) $netLeadTime;

                    $data['lead_time'][$category][$month]['days'][$bucket]++;
                    $data['lead_time'][$category][$month]['total']++;
                }
            }
        }

        // Kalkulasi Persentase Lead Time
        foreach (['sparepart', 'npk', 'chemical'] as $cat) {
            foreach ($months as $m) {
                $total = $data['lead_time'][$cat][$m]['total'];
                if ($total > 0) {
                    foreach ($data['lead_time'][$cat][$m]['days'] as $day => $count) {
                        $data['lead_time'][$cat][$m]['percentage'][$day] = round(($count / $total) * 100, 2);
                    }
                }
            }
        }

        // Kalkulasi Total Item untuk Persentase RDTV (terhadap seluruh penerimaan di bulan tersebut)
        foreach ($months as $m) {
            $totalAll = $data['mrp'][$m]['total'];
            if ($totalAll > 0) {
                $data['rdtv'][$m]['percentage'] = round(($data['rdtv'][$m]['total'] / $totalAll) * 100, 2);
            }
        }

        return $data;
    }

    private function initLeadTimeMonths()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = [
                'days' => ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0, '6' => 0, '7' => 0, '8' => 0, '>8' => 0],
                'percentage' => ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0, '6' => 0, '7' => 0, '8' => 0, '>8' => 0],
                'total' => 0,
            ];
        }

        return $months;
    }

    private function initBaseMonths()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = [
                'total' => 0,
                'percentage' => 0,
                'INVESTASI' => 0,
                'V1' => 0,
                'PD' => 0,
                'NONSTOCK' => 0,
            ];
        }

        return $months;
    }
}
