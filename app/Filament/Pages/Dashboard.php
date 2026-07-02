<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected static ?string $title = 'Halaman Utama';

    protected static string $routePath = '/';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Home;

    protected static ?int $navigationSort = -2;

    public function mount(): void
    {
        if (!session()->has('changelog_july_2026_viewed')) {
            session()->put('changelog_july_2026_viewed', true);

            // Redirect ke halaman yang sama namun dengan parameter action bawaan Filament
            // Ini akan memicu pop-up secara native dari server tanpa bergantung pada JS timeout
            redirect()->to(static::getUrl() . '?action=showChangelog');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('showChangelog')
                ->label('Info Update')
                ->icon('heroicon-o-megaphone')
                ->color('info')
                ->modalHeading('Pembaruan Sistem (03 Juli 2026)')
                ->modalDescription(new \Illuminate\Support\HtmlString('
                    <div class="space-y-4 text-sm mt-4">
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2">✅ 1. Bypass QC untuk Chemical dan Bahan Baku</h4>
                            <p class="text-gray-600 dark:text-gray-300">Material chemical/Karung dan Bahan Baku sekarang dapat diproses ke GRS/RDTV langsung tanpa harus melewati proses Pengajuan QC. Jadi jika dokumen sudah ada di penerimaan DO dan sudah melakukan Aksi/Tindakan POST 103, maka sudah bisa untuk upload GRS/RDTV di sistem.</p>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2">✅ 2. Upload Manual MIR</h4>
                            <p class="text-gray-600 dark:text-gray-300">Sekarang Terdapat tombol <strong>"Upload Manual"</strong> di daftar <strong><em>Material Issue</em></strong>. Anda Bisa bisa mengunggah dokumen MIR secara manual dan menautkannya dengan referensi DO (opsional / Jika Ada di Data Penerimaan).</p>
                        </div>
                        <div class="p-4 mt-6 bg-amber-50 dark:bg-amber-900/30 rounded-lg border border-amber-200 dark:border-amber-700/50">
                            <div class="flex items-start gap-3">
                                <div class="text-2xl">👨‍💻</div>
                                <div>
                                    <h4 class="font-bold text-amber-900 dark:text-amber-400 mb-1">Pesan Khusus dari Alex M</h4>
                                    <p class="text-amber-800 dark:text-amber-300 italic text-sm">
                                        "Guys, aku hari ini masuk siang, karena semalam habis jaga kapal 🫡 <br>
                                        Jadi kalau ada keluhan atau mau minta perubahan sistem, ditahan duluuu okehhhhh tunggu aku datang yaaaaa! Nanti siang setelah sholat jumat OTW ke Kantor Receiving menggunakan kekuatan Matahari 🌞🔥 <br><br>
                                        <strong>P.S. Buat Mas Andre dan Richa, semangaaaat kerjanyaaaa!! 💪✨"</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                '))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
            FilterAction::make()
                ->label('Filter')
                ->schema([
                    Section::make('Filter Data Halaman Utama')
                        ->description('Pilih bulan dan tahun untuk menyaring data statistik pada Halaman Utama.')
                        ->schema([
                            Grid::make(12)
                                ->schema([
                                    Select::make('month')
                                        ->label('Bulan')
                                        ->placeholder('Pilih  Bulan')
                                        ->searchable()
                                        ->native(false)
                                        ->options([
                                            '1' => 'Januari',
                                            '2' => 'Februari',
                                            '3' => 'Maret',
                                            '4' => 'April',
                                            '5' => 'Mei',
                                            '6' => 'Juni',
                                            '7' => 'Juli',
                                            '8' => 'Agustus',
                                            '9' => 'September',
                                            '10' => 'Oktober',
                                            '11' => 'November',
                                            '12' => 'Desember',
                                        ])
                                        ->columnSpan(7)
                                        ->default(now()->month),

                                    Select::make('year')
                                        ->label('Tahun')
                                        ->placeholder('Pilih  Tahun')
                                        ->searchable()
                                        ->native(false)
                                        ->options(function () {
                                            $years = [];
                                            $currentYear = now()->year;
                                            for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                                                $years[$i] = $i;
                                            }

                                            return $years;
                                        })
                                        ->columnSpan(5)
                                        ->default(now()->year),
                                ]),
                        ]),
                ]),
        ];
    }
}
