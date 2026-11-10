@props([
    'contained' => false,
    'label' => null,
    'vertical' => false,
])

@if ($vertical)
    <nav
        {{
            $attributes
                ->merge([
                    'aria-label' => $label,
                    'role' => 'tablist',
                ])
                ->class([
                    'fi-tabs',
                    'fi-contained' => $contained,
                    'fi-vertical' => true,
                ])
        }}
    >
        {{ $slot }}
    </nav>
@else
    <div
        x-data="{
            canScrollLeft: false,
            canScrollRight: true,
            hasOverflow: true,
            checkScroll() {
                const el = this.$refs.tabNav;
                if (!el) return;
                this.hasOverflow = el.scrollWidth > (el.clientWidth + 4);
                this.canScrollLeft = el.scrollLeft > 4;
                this.canScrollRight = Math.ceil(el.scrollLeft + el.clientWidth) < (el.scrollWidth - 4);
            },
            scrollLeft() {
                const el = this.$refs.tabNav;
                if (!el) return;
                const distance = Math.max(el.clientWidth * 0.55, 180);
                el.scrollBy({ left: -distance, behavior: 'smooth' });
                setTimeout(() => this.checkScroll(), 250);
            },
            scrollRight() {
                const el = this.$refs.tabNav;
                if (!el) return;
                const distance = Math.max(el.clientWidth * 0.55, 180);
                el.scrollBy({ left: distance, behavior: 'smooth' });
                setTimeout(() => this.checkScroll(), 250);
            },
            scrollToActive() {
                const el = this.$refs.tabNav;
                if (!el) return;
                const active = el.querySelector('.fi-tabs-item.fi-active');
                if (active) {
                    active.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }
        }"
        x-init="
            const update = () => {
                checkScroll();
            };

            // Jalankan sinkronisasi pertama kali
            update();

            $nextTick(() => {
                update();
                scrollToActive();
            });

            setTimeout(update, 80);
            setTimeout(update, 250);
            setTimeout(update, 500);

            if (window.ResizeObserver && $refs.tabNav) {
                const ro = new ResizeObserver(() => update());
                ro.observe($refs.tabNav);
                Array.from($refs.tabNav.children).forEach(child => ro.observe(child));
            }

            if (window.MutationObserver && $refs.tabNav) {
                const mo = new MutationObserver(() => update());
                mo.observe($refs.tabNav, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class', 'aria-selected', 'style']
                });
            }
        "
        class="fi-tabs-scroll-outer w-full flex items-center justify-center my-2"
    >
        <!-- Kontainer Tengah: [ < ] [ Tabs ] [ > ] -->
        <div class="fi-tabs-scroll-inner flex items-center justify-center gap-2 max-w-full min-w-0 px-1">
            <!-- Tombol Panah Kiri (<) -->
            <button
                type="button"
                x-show="hasOverflow"
                x-cloak
                :disabled="!canScrollLeft"
                @click.stop="scrollLeft()"
                aria-label="Geser tab ke kiri"
                class="fi-tabs-arrow-btn fi-tabs-arrow-left"
            >
                <x-filament::icon
                    icon="heroicon-m-chevron-left"
                    class="h-4.5 w-4.5"
                />
            </button>

            <!-- Nav Tablist di Tengah -->
            <nav
                x-ref="tabNav"
                @scroll.passive="checkScroll()"
                {{
                    $attributes
                        ->merge([
                            'aria-label' => $label,
                            'role' => 'tablist',
                        ])
                        ->class([
                            'fi-tabs',
                            'fi-contained' => $contained,
                            'fi-vertical' => false,
                        ])
                }}
            >
                {{ $slot }}
            </nav>

            <!-- Tombol Panah Kanan (>) -->
            <button
                type="button"
                x-show="hasOverflow"
                x-cloak
                :disabled="!canScrollRight"
                @click.stop="scrollRight()"
                aria-label="Geser tab ke kanan"
                class="fi-tabs-arrow-btn fi-tabs-arrow-right"
            >
                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4.5 w-4.5"
                />
            </button>
        </div>
    </div>
@endif
