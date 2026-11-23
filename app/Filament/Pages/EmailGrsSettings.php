<?php

namespace App\Filament\Pages;

use App\Services\EmailTemplateRenderer;
use App\Services\ZimbraMailService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Throwable;
use UnitEnum;

class EmailGrsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|UnitEnum|null $navigationGroup = 'Penerimaan Jasa / Invoice';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Envelope;

    protected string $view = 'filament.pages.email-grs-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Penerimaan Jasa / Invoice');
    }

    public static function getNavigationLabel(): string
    {
        return __('Template Email GRS/RDTV');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Template & Pengaturan Email GRS/RDTV');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Developer', 'Super Admin', 'Admin', 'Staff Invoice'])
            || $user->can('ViewAny:InvoicingReceipt');
    }

    public function mount(EmailTemplateRenderer $renderer): void
    {
        $this->form->fill($renderer->getSettingsFormData());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Pengaturan Pengiriman Otomatis & Mode Uji Coba')
                    ->description('Email dikirim via Zimbra (Receiving Jasa) secara otomatis begitu berkas Verifikasi GRS/RDTV diunggah dan kedua berkas lampiran lengkap.')
                    ->schema([
                        Toggle::make('auto_send_enabled')
                            ->label('Aktifkan Pengiriman Email Otomatis saat Upload Verifikasi GRS/RDTV')
                            ->helperText('Wajib 2 lampiran lengkap (Dokumen GRS/RDTV + Dokumen Verifikasi GRS/RDTV). Jika salah satu berkas belum tersedia, pengiriman otomatis ditahan hingga lengkap.')
                            ->onColor('primary'),

                        Toggle::make('test_mode_enabled')
                            ->label('Aktifkan Mode Uji Coba (Alihkan Semua Email ke Email Tes)')
                            ->helperText('Jika aktif, seluruh pengiriman otomatis maupun kirim ulang dialihkan ke alamat email uji coba di bawah dan TIDAK terkirim ke vendor asli. Nonaktifkan bila siap mengirim langsung ke vendor.')
                            ->onColor('warning')
                            ->live(),

                        TextInput::make('test_mode_email')
                            ->label('Alamat Email Tujuan Uji Coba')
                            ->placeholder('contoh: andereyan@gmail.com')
                            ->visible(fn (Get $get): bool => (bool) $get('test_mode_enabled'))
                            ->required(fn (Get $get): bool => (bool) $get('test_mode_enabled'))
                            ->maxLength(255)
                            ->live(debounce: 300)
                            ->helperText('Pisahkan dengan koma bila ingin mengirim salinan uji coba ke lebih dari satu alamat email.'),

                        TextInput::make('cc_emails')
                            ->label('Alamat Email CC (Opsional)')
                            ->placeholder('contoh: receiving.barang@pupukkaltim.com')
                            ->maxLength(500)
                            ->helperText('Bawaan: receiving.barang@pupukkaltim.com. Pisahkan dengan koma atau spasi jika lebih dari satu. Kosongkan jika tidak ingin menyertakan CC. Selama Mode Uji Coba aktif, CC asli ditahan agar tidak mengganggu inbox tim.'),
                    ]),

                Tabs::make('TemplateEmailTabs')
                    ->contained(true)
                    ->tabs([
                        Tab::make('Template Email GRS (105)')
                            ->icon(Heroicon::OutlinedCheckBadge)
                            ->schema([
                                TextInput::make('grs_subject')
                                    ->label('Subjek Email GRS')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Mendukung variabel dinamis, misalnya {no_grs}, {no_po}, dan {vendor_name}.'),

                                RichEditor::make('grs_body')
                                    ->label('Isi Surat Email GRS')
                                    ->required()
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'underline', 'strike', 'link'],
                                        ['bulletList', 'orderedList'],
                                        ['undo', 'redo'],
                                    ])
                                    ->helperText('Ketik isi surat pemberitahuan GRS yang akan dikirimkan ke vendor. Gunakan variabel pada panduan di bawah agar nomor PO, GRS, dan nama vendor terisi otomatis.'),
                            ]),

                        Tab::make('Template Email RDTV (124)')
                            ->icon(Heroicon::OutlinedExclamationTriangle)
                            ->schema([
                                TextInput::make('rdtv_subject')
                                    ->label('Subjek Email RDTV')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Mendukung variabel dinamis, misalnya {no_rdtv}, {no_po}, dan {vendor_name}.'),

                                RichEditor::make('rdtv_body')
                                    ->label('Isi Surat Email RDTV')
                                    ->required()
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'underline', 'strike', 'link'],
                                        ['bulletList', 'orderedList'],
                                        ['undo', 'redo'],
                                    ])
                                    ->helperText('Ketik isi surat pemberitahuan penolakan barang (RDTV 124). Anda dapat menyertakan variabel {no_rdtv} dan {keterangan}.'),
                            ]),

                        Tab::make('Header, Pengirim & Footer')
                            ->icon(Heroicon::OutlinedPaintBrush)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('sender_name')
                                            ->label('Nama Pengirim Email (From Name)')
                                            ->required()
                                            ->maxLength(120)
                                            ->placeholder('receiving jasa')
                                            ->helperText('Nama yang tampil sebagai pengirim di kotak masuk vendor.'),

                                        TextInput::make('header_title')
                                            ->label('Judul Header di Dalam Email')
                                            ->required()
                                            ->maxLength(160)
                                            ->placeholder('Receiving Jasa - PT. Pupuk Kalimantan Timur')
                                            ->helperText('Judul utama yang tampil pada bagian atas kotak email.'),

                                        TextInput::make('header_badge_grs')
                                            ->label('Label Badge Header GRS')
                                            ->required()
                                            ->maxLength(120)
                                            ->placeholder('VERIFIKASI PENERIMAAN BARANG (GRS)'),

                                        TextInput::make('header_badge_rdtv')
                                            ->label('Label Badge Header RDTV')
                                            ->required()
                                            ->maxLength(120)
                                            ->placeholder('VERIFIKASI PENOLAKAN BARANG (RDTV)'),

                                        ColorPicker::make('accent_color_grs')
                                            ->label('Warna Aksen Header GRS')
                                            ->required()
                                            ->helperText('Warna garis atas dan label badge untuk email GRS (default #F47920).'),

                                        ColorPicker::make('accent_color_rdtv')
                                            ->label('Warna Aksen Header RDTV')
                                            ->required()
                                            ->helperText('Warna garis atas dan label badge untuk email RDTV (default #DC2626).'),
                                    ]),

                                Textarea::make('footer_text')
                                    ->label('Teks Footer Email')
                                    ->rows(3)
                                    ->required()
                                    ->maxLength(600)
                                    ->helperText('Catatan kaki di bagian bawah kotak email yang diterima vendor.'),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewTemplate')
                ->label('Pratinjau Email')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->modalHeading('Pratinjau Tampilan Email Vendor')
                ->modalDescription('Simulasi hasil render subjek, isi surat, dan 2 lampiran PDF menggunakan contoh data.')
                ->modalWidth('4xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function (EmailTemplateRenderer $renderer, ZimbraMailService $zimbra) {
                    $state = $this->form->getRawState();
                    $layoutOverrides = $this->extractLayoutOverrides($state);

                    $grsPreview = $renderer->renderPreview(
                        'GRS',
                        (string) ($state['grs_subject'] ?? EmailTemplateRenderer::defaultGrsSubject()),
                        $renderer->normalizeRichContentToHtml($state['grs_body'] ?? null, EmailTemplateRenderer::defaultGrsBody()),
                        $layoutOverrides,
                    );

                    $rdtvPreview = $renderer->renderPreview(
                        'RDTV',
                        (string) ($state['rdtv_subject'] ?? EmailTemplateRenderer::defaultRdtvSubject()),
                        $renderer->normalizeRichContentToHtml($state['rdtv_body'] ?? null, EmailTemplateRenderer::defaultRdtvBody()),
                        $layoutOverrides,
                    );

                    return view('filament.pages.partials.email-template-preview', [
                        'grsPreview' => $grsPreview,
                        'rdtvPreview' => $rdtvPreview,
                        'ccEmails' => trim((string) ($state['cc_emails'] ?? EmailTemplateRenderer::DEFAULT_CC_EMAILS)),
                        'senderName' => $layoutOverrides['sender_name'] ?: $renderer->getSenderName(),
                        'senderAddress' => $zimbra->username(),
                    ]);
                }),

            Action::make('sendTestEmail')
                ->label('Kirim Email Tes')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('info')
                ->modalHeading('Uji Koneksi & Kirim Email Tes Zimbra')
                ->modalDescription('Mengirim 1 email uji coba dengan 2 berkas PDF contoh menggunakan template yang sedang aktif.')
                ->schema([
                    TextInput::make('test_email')
                        ->label('Alamat Email Tujuan Uji Coba')
                        ->email()
                        ->required()
                        ->default(fn (EmailTemplateRenderer $renderer): ?string => $renderer->getTestModeEmail() ?: Auth::user()?->email),

                    Radio::make('test_jenis')
                        ->label('Template yang Diuji')
                        ->options([
                            'GRS' => 'Template GRS (105)',
                            'RDTV' => 'Template RDTV (124)',
                        ])
                        ->default('GRS')
                        ->inline()
                        ->required(),
                ])
                ->action(function (array $data, ZimbraMailService $zimbra, EmailTemplateRenderer $renderer): void {
                    if (! $zimbra->isConfigured()) {
                        Notification::make()
                            ->title('Konfigurasi Zimbra Belum Lengkap')
                            ->body('Pastikan ZIMBRA_BASE_URL, ZIMBRA_USERNAME, dan ZIMBRA_PASSWORD telah diisi di .env server.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $state = $this->form->getRawState();
                    $layoutOverrides = $this->extractLayoutOverrides($state);
                    $jenis = strtoupper((string) ($data['test_jenis'] ?? 'GRS')) === 'RDTV' ? 'RDTV' : 'GRS';
                    $subjectTpl = $jenis === 'RDTV'
                        ? (string) ($state['rdtv_subject'] ?? EmailTemplateRenderer::defaultRdtvSubject())
                        : (string) ($state['grs_subject'] ?? EmailTemplateRenderer::defaultGrsSubject());
                    $bodyTpl = $jenis === 'RDTV'
                        ? $renderer->normalizeRichContentToHtml($state['rdtv_body'] ?? null, EmailTemplateRenderer::defaultRdtvBody())
                        : $renderer->normalizeRichContentToHtml($state['grs_body'] ?? null, EmailTemplateRenderer::defaultGrsBody());

                    $preview = $renderer->renderPreview($jenis, $subjectTpl, $bodyTpl, $layoutOverrides);

                    $tempFiles = [];

                    try {
                        $docCode = $jenis === 'RDTV' ? '5007798765' : '5007712345';
                        $file1 = $this->createSamplePdf("Dokumen {$jenis} Contoh ({$docCode})");
                        $file2 = $this->createSamplePdf("Surat Verifikasi {$jenis} Contoh ({$docCode})");
                        $tempFiles = [$file1, $file2];

                        $result = $zimbra->sendMessage(
                            to: [(string) $data['test_email']],
                            subject: '[TES TEMPLATE] '.$preview['subject'],
                            htmlBody: $preview['wrapped_html'],
                            attachments: [
                                ['path' => $file1, 'filename' => "{$jenis}_{$docCode}.pdf"],
                                ['path' => $file2, 'filename' => "Verifikasi_{$jenis}_{$docCode}.pdf"],
                            ],
                            fromName: $layoutOverrides['sender_name'] ?: $renderer->getSenderName(),
                        );

                        Notification::make()
                            ->title('Email Tes Berhasil Dikirim')
                            ->body("Terkirim ke {$data['test_email']} (2 lampiran PDF, Message ID: ".($result['message_id'] ?? '-').').')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Gagal Mengirim Email Tes')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        foreach ($tempFiles as $file) {
                            if (is_file($file)) {
                                @unlink($file);
                            }
                        }
                    }
                }),

            Action::make('resetDefaults')
                ->label('Reset Bawaan')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kembalikan Template Bawaan?')
                ->modalDescription('Teks subjek dan isi surat untuk GRS (105) serta RDTV (124) akan dikembalikan ke format bawaan korporat PKT.')
                ->action(function (): void {
                    $current = $this->form->getRawState();

                    $this->form->fill([
                        'auto_send_enabled' => (bool) ($current['auto_send_enabled'] ?? true),
                        'test_mode_enabled' => (bool) ($current['test_mode_enabled'] ?? false),
                        'test_mode_email' => (string) ($current['test_mode_email'] ?? ''),
                        'cc_emails' => (string) ($current['cc_emails'] ?? EmailTemplateRenderer::DEFAULT_CC_EMAILS),
                        'sender_name' => EmailTemplateRenderer::defaultSenderName(),
                        'header_title' => EmailTemplateRenderer::defaultHeaderTitle(),
                        'header_badge_grs' => EmailTemplateRenderer::defaultHeaderBadgeGrs(),
                        'header_badge_rdtv' => EmailTemplateRenderer::defaultHeaderBadgeRdtv(),
                        'footer_text' => EmailTemplateRenderer::defaultFooterText(),
                        'accent_color_grs' => EmailTemplateRenderer::defaultAccentColorGrs(),
                        'accent_color_rdtv' => EmailTemplateRenderer::defaultAccentColorRdtv(),
                        'grs_subject' => EmailTemplateRenderer::defaultGrsSubject(),
                        'grs_body' => EmailTemplateRenderer::defaultGrsBody(),
                        'rdtv_subject' => EmailTemplateRenderer::defaultRdtvSubject(),
                        'rdtv_body' => EmailTemplateRenderer::defaultRdtvBody(),
                    ]);

                    Notification::make()
                        ->title('Template Dikembalikan ke Bawaan')
                        ->body('Klik "Simpan Template" untuk menyimpan perubahan.')
                        ->info()
                        ->send();
                }),
        ];
    }

    public function save(EmailTemplateRenderer $renderer): void
    {
        $validated = $this->form->getState();
        $renderer->saveSettingsFormData($validated);

        Notification::make()
            ->title('Template & Pengaturan Tersimpan')
            ->body('Perubahan template email GRS/RDTV akan langsung berlaku pada pengiriman berikutnya.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    public function getPlaceholders(): array
    {
        return EmailTemplateRenderer::availablePlaceholders();
    }

    /**
     * @return array{
     *     configured: bool,
     *     enabled: bool,
     *     base_url: string,
     *     username: string,
     *     test_mode_enabled: bool,
     *     test_override_to: ?string,
     *     test_mode_unsaved: bool
     * }
     */
    public function getZimbraStatus(): array
    {
        $zimbra = app(ZimbraMailService::class);
        $renderer = app(EmailTemplateRenderer::class);

        $liveTestEnabled = is_array($this->data) && array_key_exists('test_mode_enabled', $this->data)
            ? (bool) $this->data['test_mode_enabled']
            : $renderer->isTestModeEnabled();

        $liveTestEmail = is_array($this->data) && array_key_exists('test_mode_email', $this->data)
            ? trim((string) ($this->data['test_mode_email'] ?? ''))
            : $renderer->getTestModeEmail();

        $savedTestEnabled = $renderer->isTestModeEnabled();
        $savedTestEmail = $renderer->getTestModeEmail();

        $unsaved = ($liveTestEnabled !== $savedTestEnabled)
            || ($liveTestEnabled && $liveTestEmail !== $savedTestEmail);

        return [
            'configured' => $zimbra->isConfigured(),
            'enabled' => (bool) config('services.zimbra.enabled', false),
            'base_url' => $zimbra->baseUrl(),
            'username' => $zimbra->username(),
            'test_mode_enabled' => $liveTestEnabled,
            'test_override_to' => $liveTestEnabled ? ($liveTestEmail !== '' ? $liveTestEmail : null) : null,
            'test_mode_unsaved' => $unsaved,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, string>
     */
    private function extractLayoutOverrides(array $state): array
    {
        return [
            'sender_name' => trim((string) ($state['sender_name'] ?? '')),
            'header_title' => trim((string) ($state['header_title'] ?? '')),
            'header_badge_grs' => trim((string) ($state['header_badge_grs'] ?? '')),
            'header_badge_rdtv' => trim((string) ($state['header_badge_rdtv'] ?? '')),
            'footer_text' => trim((string) ($state['footer_text'] ?? '')),
            'accent_color_grs' => trim((string) ($state['accent_color_grs'] ?? '')),
            'accent_color_rdtv' => trim((string) ($state['accent_color_rdtv'] ?? '')),
        ];
    }

    private function createSamplePdf(string $label): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $label);
        $stream = "BT /F1 12 Tf 48 720 Td ({$escaped}) Tj ET";
        $len = strlen($stream);

        $pdf = "%PDF-1.4\n"
            ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
            ."4 0 obj << /Length {$len} >> stream\n{$stream}\nendstream endobj\n"
            ."5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."0000000009 00000 n \n"
            ."0000000058 00000 n \n"
            ."0000000115 00000 n \n"
            ."0000000241 00000 n \n"
            ."0000000360 00000 n \n"
            ."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n430\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'zimbra_tpl_test_').'.pdf';
        file_put_contents($path, $pdf);

        return $path;
    }
}
