@php
    use App\Filament\Pages\CombinedGrsVerificationUpload;
    use App\Filament\Pages\Dashboard;
    use App\Filament\Pages\NonStockAgingMonitoring;
    use App\Filament\Pages\ScanWarehouseTransmittal;
    use App\Filament\Resources\DeliveryOrderReceipts\DeliveryOrderReceiptResource;
    use App\Filament\Resources\GrsRdtvs\GrsRdtvResource;
    use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;

    $currentUrl = rtrim(url()->current(), '/');
    $quickLinks = [
        [
            'label' => __('Beranda'),
            'url' => route('home'),
            'icon' => 'heroicon-o-home-modern',
            'active' => false,
            'spa' => false,
        ],
    ];

    $candidates = [
        [
            'class' => Dashboard::class,
            'label' => __('Dashboard'),
            'icon' => 'heroicon-o-squares-2x2',
            'exact' => true,
        ],
        [
            'class' => DeliveryOrderReceiptResource::class,
            'label' => __('Penerimaan DO'),
            'icon' => 'heroicon-o-truck',
            'exact' => false,
        ],
        [
            'class' => GrsRdtvResource::class,
            'label' => __('GRS & RDTV'),
            'icon' => 'heroicon-o-document-check',
            'exact' => false,
        ],
        [
            'class' => CombinedGrsVerificationUpload::class,
            'label' => __('Upload Gabungan GRS/RDTV'),
            'icon' => 'heroicon-o-arrow-up-tray',
            'exact' => false,
        ],
        [
            'class' => InvoicingReceiptResource::class,
            'label' => __('Invoice Barang'),
            'icon' => 'heroicon-o-receipt-percent',
            'exact' => false,
        ],
        [
            'class' => ScanWarehouseTransmittal::class,
            'label' => __('Scan Transmittal Gudang'),
            'icon' => 'heroicon-o-qr-code',
            'exact' => false,
        ],
        [
            'class' => NonStockAgingMonitoring::class,
            'label' => __('Aging Material Non-Stock & PD'),
            'icon' => 'heroicon-o-clock',
            'exact' => false,
        ],
    ];

    foreach ($candidates as $candidate) {
        $class = $candidate['class'];
        if (! class_exists($class)) {
            continue;
        }

        try {
            if (! $class::canAccess()) {
                continue;
            }

            $url = (string) $class::getUrl();
            $normUrl = rtrim($url, '/');
            $isActive = $candidate['exact']
                ? $currentUrl === $normUrl
                : ($currentUrl === $normUrl || str_starts_with($currentUrl, $normUrl.'/'));

            $quickLinks[] = [
                'label' => $candidate['label'],
                'url' => $url,
                'icon' => $candidate['icon'],
                'active' => $isActive,
                'spa' => true,
            ];
        } catch (\Throwable) {
            continue;
        }
    }
@endphp

@if (filament()->auth()->check())
    <aside
        aria-label="Navigasi Cepat"
        class="pkt-left-rail hidden lg:flex flex-col items-center justify-between"
    >
        <!-- Vertical Stack of Circular Quick-Access Buttons -->
        <div class="flex flex-col items-center gap-2.5">
            @foreach ($quickLinks as $link)
                <a
                    @if ($link['spa'])
                        {{ \Filament\Support\generate_href_html($link['url']) }}
                    @else
                        href="{{ $link['url'] }}"
                    @endif
                    title="{{ $link['label'] }}"
                    aria-label="{{ $link['label'] }}"
                    @class([
                        'pkt-rail-btn group',
                        'pkt-rail-btn-active' => $link['active'],
                    ])
                >
                    <x-filament::icon :icon="$link['icon']" class="w-[18px] h-[18px]" />
                    <span class="pkt-rail-tooltip" role="tooltip">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>

        <!-- Bottom Vertical Dual-Circle Theme Toggle (Matching Reference Design) -->
        <div
            x-data="{
                isDark: document.documentElement.classList.contains('dark'),
                observer: null,
                init() {
                    this.sync();
                    this.observer = new MutationObserver(() => this.sync());
                    this.observer.observe(document.documentElement, {
                        attributes: true,
                        attributeFilter: ['class'],
                    });
                },
                destroy() {
                    if (this.observer) {
                        this.observer.disconnect();
                    }
                },
                sync() {
                    this.isDark = document.documentElement.classList.contains('dark');
                },
                setMode(mode) {
                    localStorage.setItem('theme', mode);
                    document.cookie = 'theme=' + mode + '; path=/; max-age=31536000; SameSite=Lax';
                    this.$dispatch('theme-changed', mode);
                    this.$nextTick(() => this.sync());
                },
            }"
            @theme-changed.window="$nextTick(() => sync())"
            class="pkt-rail-theme-pill"
            role="group"
            aria-label="Pengaturan Tema"
        >
            <button
                type="button"
                @click="setMode('dark')"
                :aria-pressed="isDark ? 'true' : 'false'"
                title="Mode Gelap"
                aria-label="Mode Gelap"
                class="pkt-rail-theme-btn"
                :class="isDark ? 'pkt-rail-theme-btn-active' : ''"
            >
                <x-heroicon-o-moon class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="setMode('light')"
                :aria-pressed="!isDark ? 'true' : 'false'"
                title="Mode Terang"
                aria-label="Mode Terang"
                class="pkt-rail-theme-btn"
                :class="!isDark ? 'pkt-rail-theme-btn-active' : ''"
            >
                <x-heroicon-o-sun class="w-4 h-4" />
            </button>
        </div>
    </aside>
@endif
