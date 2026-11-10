<?php

namespace App\Filament\Imports;

use App\Services\InvoicingCsvImportService;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class InvoicingImportAction extends ImportAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $parentSchema = $this->schema;

        $this->schema(function (ImportAction $action) use ($parentSchema): array {
            /** @var array<int, mixed> $components */
            $components = $this->evaluate($parentSchema, ['action' => $action]) ?? [];

            if (($components[0] ?? null) instanceof FileUpload) {
                $components[0]
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/x-csv',
                        'application/csv',
                        'application/x-csv',
                        'text/comma-separated-values',
                        'text/x-comma-separated-values',
                        'text/plain',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/octet-stream',
                    ])
                    ->helperText('Anda dapat langsung mengunggah file Excel (.xlsx multi-sheet) maupun CSV (.csv) asli hasil ekspor Mitraku tanpa perlu Save As CSV.');
            }

            return $components;
        });
    }

    /**
     * @return array<mixed>
     */
    public function getFileValidationRules(): array
    {
        $rules = parent::getFileValidationRules();

        return array_map(
            fn (mixed $rule): mixed => $rule === 'extensions:csv,txt' ? 'extensions:csv,txt,xlsx,xls' : $rule,
            $rules,
        );
    }

    /**
     * @return resource | false
     */
    public function getUploadedFileStream(TemporaryUploadedFile $file)
    {
        if (filled($realPath = $file->getRealPath())) {
            app(InvoicingCsvImportService::class)->normalizeMitrakuCsvFileInPlace($realPath);
        }

        return parent::getUploadedFileStream($file);
    }
}
