<?php

namespace App\Filament\Resources\CalendarNotes\Pages;

use App\Filament\Resources\CalendarNotes\CalendarNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ManageCalendarNotes extends ManageRecords
{
    protected static string $resource = CalendarNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Catatan Kalender')
                ->icon(Heroicon::PlusCircle)
                ->modalHeading('Tambah Catatan Kalender')
                ->modalDescription('Catatan akan tampil pada kalender Halaman Utama untuk seluruh pengguna.')
                ->modalWidth('lg')
                ->mutateDataUsing(function (array $data): array {
                    $data['created_by'] = Auth::id();

                    return $data;
                }),
        ];
    }
}
