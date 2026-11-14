<?php

namespace App\Filament\Resources\MonitoringKarungs\RelationManagers;

use App\Models\MonitoringKarungQc;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QcItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'qcItems';

    protected static ?string $title = 'Tahapan Pengujian QC & SA SAP';

    protected static string|BackedEnum|null $icon = 'heroicon-m-shield-check';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->qcItems()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $hasPendingSa = $ownerRecord->qcItems()->where(function ($q) {
            $q->whereNull('no_sa')->orWhere('no_sa', '')->orWhere('status_qc', 'BLM SA');
        })->exists();

        return $hasPendingSa ? 'warning' : 'success';
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        $pending = $ownerRecord->qcItems()->where(function ($q) {
            $q->whereNull('no_sa')->orWhere('no_sa', '')->orWhere('status_qc', 'BLM SA');
        })->count();

        return $pending > 0 ? "Ada {$pending} tahap pengujian belum memiliki No. SA SAP" : 'Semua tahap sudah memiliki No. SA SAP';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 3])->schema([
                    TextInput::make('tahap_qc')
                        ->label('Tahap Pengujian')
                        ->placeholder('cth: Awal, 1, 2, Uji Ulang')
                        ->required(),

                    TextInput::make('qty_qc')
                        ->label('Kuantitas Diuji (Lembar)')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->suffix('Lembar'),

                    Select::make('status_qc')
                        ->label('Hasil Pengujian QC')
                        ->options([
                            'Diterima' => 'Diterima',
                            'Ditolak' => 'Ditolak',
                            'Istek' => 'Istek',
                            'TUV' => 'TUV',
                            'Diterima dgn Penalti' => 'Diterima dgn Penalti',
                            'BLM SA' => 'BLM SA',
                            'BATAL' => 'BATAL',
                        ])
                        ->default('Diterima')
                        ->required(),
                ]),

                Grid::make(['default' => 1, 'sm' => 2, 'md' => 4])->schema([
                    TextInput::make('no_sik')
                        ->label('Nomor SIK')
                        ->placeholder('cth: 194/SIK/2026'),

                    DatePicker::make('tgl_sik')
                        ->label('Tanggal SIK')
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    TextInput::make('no_surat_qc')
                        ->label('No. Surat Hasil QC')
                        ->placeholder('cth: 30166'),

                    TextInput::make('file_istek')
                        ->label('Referensi File ISTEK')
                        ->placeholder('cth: 30410'),
                ]),

                Grid::make(['default' => 1, 'sm' => 3])->schema([
                    TextInput::make('no_bapp')
                        ->label('Nomor BAPP')
                        ->placeholder('cth: 01886'),

                    TextInput::make('no_sa')
                        ->label('Nomor SA (SAP)')
                        ->placeholder('cth: 1000690323')
                        ->helperText('Kosongkan bila belum terbit di SAP.'),

                    TextInput::make('drafter')
                        ->label('Nama Drafter')
                        ->placeholder('cth: CAMEL, PUGUH, RIZKI')
                        ->datalist(['CAMEL', 'PUGUH', 'RIZKI', 'PUGUH / CAMEL']),
                ]),

                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    TextInput::make('no_dof_daan')
                        ->label('Nomor DOF / DAAN')
                        ->placeholder('cth: 29204'),

                    DatePicker::make('tgl_permintaan')
                        ->label('Tanggal Permintaan')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                ]),

                Textarea::make('keterangan')
                    ->label('Catatan Hasil QC')
                    ->placeholder('Catatan hasil pengujian laboratorium atau keterangan khusus.')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tahap_qc')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('tahap_qc')
                    ->label('Tahap')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'awal' => 'info',
                        'total' => 'primary',
                        default => str_contains(strtolower($state), 'uji ulang') ? 'warning' : 'gray',
                    })
                    ->weight(FontWeight::Bold),

                TextColumn::make('qty_qc')
                    ->label('Qty Diuji')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Lbr')
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->summarize(
                        Sum::make()
                            ->label('Total Diuji')
                            ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' Lbr')
                    ),

                TextColumn::make('status_qc')
                    ->label('Hasil QC')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower($state ?? '')) {
                        'diterima' => 'success',
                        'ditolak', 'batal' => 'danger',
                        'istek' => 'info',
                        'tuv', 'diterima dgn penalti' => 'warning',
                        'blm sa' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('no_sik')
                    ->label('No. SIK')
                    ->icon('heroicon-m-document-text')
                    ->copyable()
                    ->copyMessage('No. SIK disalin')
                    ->placeholder('-'),

                TextColumn::make('tgl_sik')
                    ->label('Tgl SIK')
                    ->date('d/m/Y')
                    ->placeholder('-'),

                TextColumn::make('no_surat_qc')
                    ->label('No. Surat QC')
                    ->placeholder('-'),

                TextColumn::make('no_bapp')
                    ->label('No. BAPP')
                    ->placeholder('-')
                    ->copyable(),

                TextColumn::make('no_sa')
                    ->label('No. SA (SAP)')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ?: 'BLM SA')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->copyable()
                    ->copyMessage('Nomor SA disalin'),

                TextColumn::make('drafter')
                    ->label('Drafter')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                TextColumn::make('no_dof_daan')
                    ->label('No. DOF')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tgl_permintaan')
                    ->label('Tgl Minta')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_sa')
                    ->label('Status SA SAP')
                    ->options([
                        'pending' => 'Belum SA (Pending)',
                        'completed' => 'Sudah SA (Selesai)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pending' => $query->where(fn ($q) => $q->whereNull('no_sa')->orWhere('no_sa', '')->orWhere('status_qc', 'BLM SA')),
                            'completed' => $query->whereNotNull('no_sa')->where('no_sa', '!=', '')->where('status_qc', '!=', 'BLM SA'),
                            default => $query,
                        };
                    }),

                SelectFilter::make('status_qc')
                    ->label('Hasil QC')
                    ->options([
                        'Diterima' => 'Diterima',
                        'Ditolak' => 'Ditolak',
                        'Istek' => 'Istek',
                        'TUV' => 'TUV',
                        'BLM SA' => 'BLM SA',
                    ]),

                SelectFilter::make('drafter')
                    ->label('Drafter')
                    ->options([
                        'CAMEL' => 'CAMEL',
                        'PUGUH' => 'PUGUH',
                        'RIZKI' => 'RIZKI',
                        'PUGUH / CAMEL' => 'PUGUH / CAMEL',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('+ Tambah Tahap QC')
                    ->modalHeading('Tambah Tahap Pengujian QC')
                    ->modalWidth('3xl'),
            ])
            ->recordActions([
                Action::make('quick_sa')
                    ->label('Isi No. SA')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->modalHeading('Isi Nomor SA SAP')
                    ->modalWidth('md')
                    ->visible(fn (MonitoringKarungQc $record): bool => empty($record->no_sa) || strtolower($record->status_qc ?? '') === 'blm sa')
                    ->form([
                        TextInput::make('no_sa')
                            ->label('Nomor SA (SAP)')
                            ->placeholder('cth: 1000690323')
                            ->required()
                            ->maxLength(50)
                            ->helperText('Nomor Service Acceptance SAP 10 digit.'),
                    ])
                    ->action(function (MonitoringKarungQc $record, array $data): void {
                        $updateData = ['no_sa' => $data['no_sa']];
                        if (strtolower($record->status_qc ?? '') === 'blm sa') {
                            $updateData['status_qc'] = 'Diterima';
                        }
                        $record->update($updateData);

                        Notification::make()
                            ->title('Nomor SA SAP berhasil disimpan')
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->modalHeading('Ubah Data Tahap Pengujian QC')
                    ->modalWidth('3xl'),

                DeleteAction::make(),
            ]);
    }
}
