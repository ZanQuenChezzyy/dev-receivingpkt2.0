<?php

namespace App\Filament\Resources\CashierTransmittals\Schemas;

use App\Models\CashierTransmittal;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CashierTransmittalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Transmittal Pengiriman Kasir')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('transmittal_no')
                                ->label('Nomor Transmittal')
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->icon('heroicon-m-document-duplicate')
                                ->copyable(),

                            TextEntry::make('sent_date')
                                ->label('Tanggal Kirim Kasir')
                                ->date('d F Y')
                                ->icon('heroicon-m-calendar-days')
                                ->weight(FontWeight::SemiBold),

                            TextEntry::make('status')
                                ->label('Status Batch')
                                ->badge()
                                ->color(fn (?string $state): string => $state === CashierTransmittal::STATUS_LOCKED ? 'success' : 'info')
                                ->icon(fn (?string $state): string => $state === CashierTransmittal::STATUS_LOCKED ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open'),

                            TextEntry::make('invoice_count')
                                ->label('Total Invoice')
                                ->getStateUsing(fn (CashierTransmittal $record): string => $record->invoicingReceipts()->count().' Invoice')
                                ->badge()
                                ->color('info')
                                ->icon('heroicon-m-document-check'),

                            TextEntry::make('total_subtotal')
                                ->label('Total Nilai (Exclude PPN)')
                                ->getStateUsing(function (CashierTransmittal $record): string {
                                    $sum = (float) $record->invoicingReceipts()->sum('subtotal_exclude_ppn');

                                    return 'Rp '.number_format($sum, 0, ',', '.');
                                })
                                ->weight(FontWeight::Bold)
                                ->color('success'),

                            TextEntry::make('involved_pics')
                                ->label('PIC Terlibat')
                                ->getStateUsing(function (CashierTransmittal $record): string {
                                    $names = $record->invoicingReceipts()
                                        ->with('pic:id,name')
                                        ->get()
                                        ->pluck('pic.name')
                                        ->filter()
                                        ->unique()
                                        ->values();

                                    return $names->isNotEmpty() ? $names->implode(', ') : 'Belum Ada PIC';
                                })
                                ->icon('heroicon-m-users'),

                            TextEntry::make('destination')
                                ->label('Tujuan Pengiriman')
                                ->icon('heroicon-m-building-office'),

                            TextEntry::make('createdBy.name')
                                ->label('Dibuat Oleh')
                                ->placeholder('Sistem')
                                ->icon('heroicon-m-user-circle'),

                            TextEntry::make('printed_at')
                                ->label('Terakhir Dicetak')
                                ->dateTime('d M Y H:i')
                                ->placeholder('Belum Dicetak')
                                ->icon('heroicon-m-printer'),
                        ]),
                    ]),
            ]);
    }
}
