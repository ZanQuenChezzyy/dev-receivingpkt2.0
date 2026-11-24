<?php

namespace App\Filament\Pages;

use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use App\Services\EmailTemplateRenderer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CombinedGrsVerificationUpload extends Page
{
    protected static ?string $slug = 'upload-gabungan-grs-verifikasi';

    protected static string|UnitEnum|null $navigationGroup = 'Penerimaan Jasa / Invoice';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::DocumentArrowUp;

    protected string $view = 'filament.pages.combined-grs-verification-upload';

    public static function getNavigationGroup(): ?string
    {
        return __('Penerimaan Jasa / Invoice');
    }

    public static function getNavigationLabel(): string
    {
        return __('Upload Gabungan GRS/RDTV');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Upload Gabungan GRS/RDTV & Verifikasi');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Unggah Slip GRS/RDTV dan Surat Verifikasi sekaligus dalam satu sesi. Sistem mengelompokkan berkas, memetakan pasangan dokumen, dan memeriksa kelayakan DO sebelum disimpan.');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Developer', 'Super Admin', 'Admin', 'Staff Invoice'])
            || $user->can('Create:GrsRdtv')
            || $user->can('ViewAny:InvoicingReceipt');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openGrsList')
                ->label('Buka GRS & RDTV')
                ->icon(Heroicon::OutlinedDocumentCurrencyDollar)
                ->color('gray')
                ->url(fn (): string => GrsRdtvResource::getUrl('index')),

            Action::make('openInvoicingList')
                ->label('Buka Invoice Barang')
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->color('gray')
                ->url(fn (): string => InvoicingReceiptResource::getUrl('index')),
        ];
    }

    /**
     * @return array{
     *     inspectUrl: string,
     *     precheckUrl: string,
     *     commitUrl: string,
     *     defaultDate: string,
     *     emailAutoSendEnabled: bool,
     *     emailTestModeEnabled: bool,
     *     emailTestModeEmail: string,
     *     grsListUrl: string,
     *     invoicingListUrl: string,
     *     csrfToken: string
     * }
     */
    public function getPageConfig(): array
    {
        $renderer = app(EmailTemplateRenderer::class);

        return [
            'inspectUrl' => route('filament.admin.grs-rdtv.combined_upload.inspect', absolute: false),
            'precheckUrl' => route('filament.admin.grs-rdtv.combined_upload.precheck', absolute: false),
            'commitUrl' => route('filament.admin.grs-rdtv.combined_upload.commit', absolute: false),
            'resendEmailUrl' => route('filament.admin.grs-rdtv.combined_upload.resend_email', absolute: false),
            'defaultDate' => now()->toDateString(),
            'emailAutoSendEnabled' => $renderer->isAutoSendEnabled(),
            'emailTestModeEnabled' => $renderer->isTestModeEnabled(),
            'emailTestModeEmail' => $renderer->getTestModeEmail(),
            'grsListUrl' => GrsRdtvResource::getUrl('index'),
            'invoicingListUrl' => InvoicingReceiptResource::getUrl('index'),
            'csrfToken' => csrf_token(),
        ];
    }
}
