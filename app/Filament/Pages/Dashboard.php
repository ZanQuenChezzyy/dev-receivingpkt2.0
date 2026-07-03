<?php

namespace App\Filament\Pages;

use BackedEnum;
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

    protected function getHeaderActions(): array
    {
        return [
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
