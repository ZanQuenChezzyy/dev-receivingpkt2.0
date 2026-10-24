<?php

namespace App\Filament\Resources\InvoicingReceipts\Pages;

use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Models\InvoicingReceipt;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListInvoicingReceipts extends ListRecords
{
    protected static string $resource = InvoicingReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Tagihan Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Dokumen')
                ->icon('heroicon-m-document-text')
                ->badge(InvoicingReceipt::count()),

            'my_tasks' => Tab::make('Tugas Saya')
                ->icon('heroicon-m-user')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('pic_id', Auth::id()))
                ->badge(InvoicingReceipt::where('pic_id', Auth::id())->count())
                ->badgeColor('primary'),

            'free_pool' => Tab::make('Bebas (Belum Ada PIC)')
                ->icon('heroicon-m-user-group')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('pic_id'))
                ->badge(InvoicingReceipt::whereNull('pic_id')->count())
                ->badgeColor('warning'),

            'waiting_verification' => Tab::make('Menunggu Verifikasi')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Menunggu Verifikasi'))
                ->badge(InvoicingReceipt::where('status', 'Menunggu Verifikasi')->count())
                ->badgeColor('danger'),

            'verified' => Tab::make('Terverifikasi')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Terverifikasi'))
                ->badge(InvoicingReceipt::where('status', 'Terverifikasi')->count())
                ->badgeColor('info'),

            'sent_cashier' => Tab::make('Terkirim Kasir')
                ->icon('heroicon-m-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Terkirim Kasir'))
                ->badge(InvoicingReceipt::where('status', 'Terkirim Kasir')->count())
                ->badgeColor('success'),
        ];
    }
}
