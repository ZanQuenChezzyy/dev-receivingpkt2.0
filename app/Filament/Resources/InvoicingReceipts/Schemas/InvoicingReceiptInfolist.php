<?php

namespace App\Filament\Resources\InvoicingReceipts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class InvoicingReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dokumen GRS / RDTV')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->schema([
                        TextEntry::make('tanggal_terima_grs')
                            ->label('Tanggal Terima GRS')
                            ->date('d F Y')
                            ->icon('heroicon-m-calendar')
                            ->placeholder('-'),

                        TextEntry::make('jenis')
                            ->label('Jenis Dokumen')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'GRS' => 'success',
                                'RDTV' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('no_grs')
                            ->label('No. GRS')
                            ->copyable()
                            ->placeholder('-'),

                        TextEntry::make('no_rdtv')
                            ->label('No. RDTV')
                            ->copyable()
                            ->placeholder('-'),

                        TextEntry::make('tanggal_info_grs')
                            ->label('Tanggal Info GRS ke Vendor')
                            ->date('d F Y')
                            ->placeholder('-'),
                    ])->columns(2),

                Section::make('Referensi PO & DO')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        TextEntry::make('no_po')
                            ->label('Nomor PO')
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->icon('heroicon-m-document-duplicate')
                            ->copyable(),

                        TextEntry::make('no_do')
                            ->label('Nomor DO')
                            ->icon('heroicon-m-truck')
                            ->copyable()
                            ->placeholder('-'),

                        TextEntry::make('material_type')
                            ->label('Tipe Material')
                            ->badge()
                            ->color('info')
                            ->placeholder('-'),

                        TextEntry::make('pic.name')
                            ->label('PIC Ditugaskan')
                            ->placeholder('Bebas ke Siapapun (Pool)')
                            ->badge()
                            ->color(fn ($record) => $record->pic_id ? 'primary' : 'warning')
                            ->icon(fn ($record) => $record->pic_id ? 'heroicon-m-user' : 'heroicon-m-user-group'),
                    ])->columns(2),

                Section::make('Informasi Vendor')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        TextEntry::make('vendor_name')
                            ->label('Nama Vendor')
                            ->weight(FontWeight::Bold)
                            ->placeholder('-'),

                        TextEntry::make('email_vendor')
                            ->label('Email Vendor')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->placeholder('-'),

                        TextEntry::make('deskripsi')
                            ->label('Deskripsi Pekerjaan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])->columns(2),

                Section::make('Rincian Penagihan & Mitraku')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        TextEntry::make('no_invoice')
                            ->label('Nomor Invoice')
                            ->copyable()
                            ->placeholder('-'),

                        TextEntry::make('faktur_pajak')
                            ->label('Nomor Faktur Pajak')
                            ->copyable()
                            ->placeholder('-'),

                        TextEntry::make('nama_barang')
                            ->label('Nama Barang')
                            ->columnSpanFull()
                            ->placeholder('-'),

                        TextEntry::make('qty_penagihan')
                            ->label('Quantity Penagihan')
                            ->formatStateUsing(fn ($record) => ($record->qty_penagihan ?? '-').' '.($record->uoi ?? '')),

                        TextEntry::make('qty_po')
                            ->label('Quantity di PO')
                            ->formatStateUsing(fn ($record) => ($record->qty_po ?? '-').' '.($record->uoi ?? '')),

                        TextEntry::make('subtotal_exclude_ppn')
                            ->label('Sub Total (Exclude PPN)')
                            ->money('IDR', locale: 'id')
                            ->copyable()
                            ->placeholder('-'),

                        TextEntry::make('subtotal_include_ppn')
                            ->label('Sub Total (Include PPN 11%)')
                            ->money('IDR', locale: 'id')
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->copyable()
                            ->placeholder('-'),
                    ])->columns(2),

                Section::make('Timeline & Verifikasi Dokumen')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        TextEntry::make('tanggal_terima_koreksi_invoice')
                            ->label('Tanggal Terima/Koreksi')
                            ->date('d F Y')
                            ->placeholder('-'),

                        TextEntry::make('tanggal_kirim_kasir')
                            ->label('Tanggal Kirim Kasir')
                            ->date('d F Y')
                            ->placeholder('Belum Dikirim'),

                        TextEntry::make('lama_pekerjaan')
                            ->label('Lama Pekerjaan')
                            ->badge()
                            ->suffix(' Hari')
                            ->color(fn ($state) => $state > 3 ? 'danger' : 'info')
                            ->placeholder('-'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Menunggu Verifikasi' => 'warning',
                                'Terverifikasi' => 'info',
                                'Terkirim Kasir' => 'success',
                                default => 'gray',
                            }),

                        TextEntry::make('dokumen_invoice')
                            ->label('Berkas Invoice Vendor')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Berkas Invoice' : 'Belum Ada Berkas')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                            ->url(fn ($record) => $record->dokumen_invoice ? Storage::url($record->dokumen_invoice) : null)
                            ->openUrlInNewTab()
                            ->placeholder('-'),

                        TextEntry::make('dokumen_verifikasi_grs')
                            ->label('File Verifikasi GRS')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Dokumen' : 'Belum Ada Berkas')
                            ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                            ->url(fn ($record) => $record->dokumen_verifikasi_grs ? Storage::url($record->dokumen_verifikasi_grs) : null)
                            ->openUrlInNewTab()
                            ->placeholder('-'),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])->columns(2),
            ]);
    }
}
