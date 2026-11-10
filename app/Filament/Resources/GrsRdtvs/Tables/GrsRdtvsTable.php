<?php

namespace App\Filament\Resources\GrsRdtvs\Tables;

use App\Models\GrsRdtv;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class GrsRdtvsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Informasi GRS & RDTV', [
                    TextColumn::make('transaction_date')
                        ->label('Tanggal Transaksi')
                        ->icon('heroicon-m-calendar-days')
                        ->iconColor('primary')
                        ->color('primary')
                        ->weight(FontWeight::Bold)
                        ->date('d F Y')
                        ->description(fn ($record) => Carbon::parse($record->transaction_date)->translatedFormat('l'))
                        ->sortable(),

                    TextColumn::make('category')
                        ->label('Kategori')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'GRS' => 'success',
                            'RDTV' => 'warning',
                            default => 'gray',
                        })
                        ->icon(fn (string $state): string => match ($state) {
                            'GRS' => 'heroicon-m-document-check',
                            'RDTV' => 'heroicon-m-arrow-path',
                            default => 'heroicon-m-document',
                        }),
                ]),

                ColumnGroup::make('Detail Dokumen', [
                    TextColumn::make('po_numbers')
                        ->label('Nomor PO')
                        ->placeholder('Tidak Ada PO Terkait')
                        ->icon('heroicon-m-document-duplicate')
                        ->iconColor('gray')
                        ->getStateUsing(function ($record) {
                            return $record->grsRdtvItems
                                ->map(fn ($item) => $item->deliveryOrderReceipt?->deliveryOrderReceiptDetails?->first()?->purchaseOrderIssued?->purchase_order_no)
                                ->filter()
                                ->unique()
                                ->toArray();
                        })
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->limitList(2)
                        ->expandableLimitedList()
                        ->searchable(query: function (Builder $query, string $search) {
                            $query->whereHas('grsRdtvItems.deliveryOrderReceipt.deliveryOrderReceiptDetails.purchaseOrderIssued', function ($q) use ($search) {
                                $q->where('purchase_order_no', 'like', "%{$search}%");
                            });
                        }),

                    TextColumn::make('grs_rdtv_items_count')
                        ->label('Total File')
                        ->counts('grsRdtvItems')
                        ->badge()
                        ->suffix(' Dokumen')
                        ->color('info')
                        ->icon('heroicon-m-document-duplicate'),

                    TextColumn::make('createdBy.name')
                        ->label('Dibuat Oleh')
                        ->html()
                        ->formatStateUsing(function ($state, ?GrsRdtv $record) {
                            $user = $record?->createdBy;
                            if (! $user) {
                                return '
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-400 dark:text-slate-500 italic">
                                        <svg class="w-3.5 h-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        Belum Ada
                                    </span>
                                ';
                            }

                            $avatarUrl = $user->getFilamentAvatarUrl()
                                ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=FFFFFF&background=F47920';

                            $npkText = ! empty($user->npk)
                                ? '<span class="text-[10px] font-mono font-medium text-amber-700/90 dark:text-amber-400/90 tracking-wide mt-0.5 leading-none">'.e($user->npk).'</span>'
                                : '';

                            $isOnline = method_exists($user, 'isOnline') ? $user->isOnline() : false;

                            $onlineDot = $isOnline
                                ? '<span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 ring-1 ring-white dark:ring-slate-900" title="Online"></span>'
                                : '';

                            return '
                                <div class="inline-flex items-center gap-2.5 pl-1.5 pr-3 py-1 rounded-2xl bg-amber-500/10 dark:bg-amber-400/10 border border-amber-500/25 dark:border-amber-400/20 backdrop-blur-md shadow-xs transition-all duration-200 hover:scale-[1.02] hover:bg-amber-500/15 hover:border-amber-500/40">
                                    <div class="relative shrink-0">
                                        <img
                                            src="'.$avatarUrl.'"
                                            alt="'.e($user->name).'"
                                            class="w-7 h-7 rounded-full object-cover ring-1.5 ring-amber-500/30 dark:ring-amber-400/40 shadow-xs"
                                        />
                                        '.$onlineDot.'
                                    </div>
                                    <div class="flex flex-col text-left leading-none">
                                        <span class="text-xs font-semibold text-slate-900 dark:text-slate-100 tracking-tight whitespace-nowrap">
                                            '.e($user->name).'
                                        </span>
                                        '.$npkText.'
                                    </div>
                                </div>
                            ';
                        })
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('createdBy', function (Builder $q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('npk', 'like', "%{$search}%");
                            });
                        })
                        ->sortable(),
                ]),

                TextColumn::make('created_at')
                    ->label('Waktu Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('transaction_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('id', 'desc'))
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->label('Lanjutkan GRS/RDTV')
                        ->icon('heroicon-o-document-plus')
                        ->color('warning'),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->size(Size::Small)
                    ->color('info')
                    ->outlined()
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
