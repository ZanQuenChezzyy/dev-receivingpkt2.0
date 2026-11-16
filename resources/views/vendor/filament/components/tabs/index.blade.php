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
            canScrollRight: false,
            hasOverflow: false,
            pillLeft: 0,
            pillWidth: 0,
            pillTop: 0,
            pillHeight: 0,
            pillVisible: false,
            isReady: false,
            targetIndex: null,
            lockUntil: 0,

            movePillTo(tab) {
                if (!tab || tab.offsetWidth === 0) return;
                this.pillLeft = tab.offsetLeft;
                this.pillWidth = tab.offsetWidth;
                this.pillTop = tab.offsetTop;
                this.pillHeight = tab.offsetHeight;
                this.pillVisible = true;
            },

            handleTabClick(e) {
                const tab = e.target.closest('.fi-tabs-item');
                if (!tab) return;
                const el = this.$refs.tabNav;
                if (!el) return;

                const tabs = Array.from(el.querySelectorAll('.fi-tabs-item'));
                const index = tabs.indexOf(tab);
                if (index === -1) return;

                // 1. Optimistic slide: move the pill immediately with 0ms latency
                this.targetIndex = index;
                this.lockUntil = Date.now() + 800; // Guard against in-flight resets while Livewire finishes
                this.movePillTo(tab);

                // 2. Optimistic active color on clicked tab
                tabs.forEach((item, i) => {
                    if (i === index) {
                        item.setAttribute('data-optimistic-active', 'true');
                        item.removeAttribute('data-optimistic-inactive');
                    } else {
                        item.removeAttribute('data-optimistic-active');
                        item.setAttribute('data-optimistic-inactive', 'true');
                    }
                });
            },

            updatePill() {
                const el = this.$refs.tabNav;
                if (!el) return;

                const tabs = Array.from(el.querySelectorAll('.fi-tabs-item'));
                if (!tabs.length) {
                    this.pillVisible = false;
                    return;
                }

                // Honor optimistic target if user clicked recently
                if (this.targetIndex !== null && Date.now() < this.lockUntil) {
                    const targetTab = tabs[this.targetIndex];
                    if (targetTab) {
                        // Release lock as soon as Livewire has confirmed this tab as active
                        if (targetTab.classList.contains('fi-active') || targetTab.getAttribute('aria-selected') === 'true') {
                            this.targetIndex = null;
                            this.lockUntil = 0;
                            tabs.forEach(item => {
                                item.removeAttribute('data-optimistic-active');
                                item.removeAttribute('data-optimistic-inactive');
                            });
                        }
                        this.movePillTo(targetTab);
                        return;
                    }
                }

                // Clear any stale optimistic attributes if lock expired
                if (this.targetIndex !== null && Date.now() >= this.lockUntil) {
                    this.targetIndex = null;
                    tabs.forEach(item => {
                        item.removeAttribute('data-optimistic-active');
                        item.removeAttribute('data-optimistic-inactive');
                    });
                }

                // Normal sync with server/Alpine active tab
                const active = el.querySelector('.fi-tabs-item.fi-active') || 
                               el.querySelector('.fi-tabs-item[aria-selected=\'true\']');
                if (!active) {
                    this.pillVisible = false;
                    return;
                }
                this.movePillTo(active);
            },

            checkScroll() {
                const el = this.$refs.tabNav;
                if (!el) return;
                const hasMore = (el.scrollWidth - el.clientWidth) > 24;
                this.hasOverflow = hasMore;
                this.canScrollLeft = hasMore && el.scrollLeft > 6;
                this.canScrollRight = hasMore && Math.ceil(el.scrollLeft + el.clientWidth) < (el.scrollWidth - 6);
            },

            scrollLeft() {
                const el = this.$refs.tabNav;
                if (!el) return;
                const distance = Math.max(el.clientWidth * 0.55, 180);
                el.scrollBy({ left: -distance, behavior: 'smooth' });
                setTimeout(() => {
                    this.checkScroll();
                    this.updatePill();
                }, 250);
            },

            scrollRight() {
                const el = this.$refs.tabNav;
                if (!el) return;
                const distance = Math.max(el.clientWidth * 0.55, 180);
                el.scrollBy({ left: distance, behavior: 'smooth' });
                setTimeout(() => {
                    this.checkScroll();
                    this.updatePill();
                }, 250);
            },

            scrollToActive() {
                const el = this.$refs.tabNav;
                if (!el) return;
                const active = el.querySelector('.fi-tabs-item.fi-active') || el.querySelector('.fi-tabs-item[aria-selected=\'true\']');
                if (!active) return;

                const containerWidth = el.clientWidth;
                if (containerWidth <= 0 || el.scrollWidth <= containerWidth) return;

                const activeLeft = active.offsetLeft;
                const activeWidth = active.offsetWidth;
                const scrollLeft = el.scrollLeft;

                if (activeLeft < scrollLeft || (activeLeft + activeWidth) > (scrollLeft + containerWidth)) {
                    const targetScrollLeft = activeLeft - (containerWidth / 2) + (activeWidth / 2);
                    el.scrollLeft = Math.max(0, targetScrollLeft);
                }
            }
        }"
        x-init="
            const update = () => {
                checkScroll();
                updatePill();
            };

            // First-time sync without animation jump
            update();

            $nextTick(() => {
                update();
                scrollToActive();
                setTimeout(() => {
                    isReady = true;
                }, 50);
            });

            if (window.ResizeObserver && $refs.tabNav) {
                const ro = new ResizeObserver(() => update());
                ro.observe($refs.tabNav);
            }

            if (window.MutationObserver && $refs.tabNav) {
                const mo = new MutationObserver((mutations) => {
                    const isOnlyIndicator = mutations.every(m => m.target.classList && m.target.classList.contains('fi-tabs-spring-indicator'));
                    if (isOnlyIndicator) return;
                    update();
                });
                mo.observe($refs.tabNav, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class', 'aria-selected']
                });
            }
        "
        class="fi-tabs-scroll-outer w-full flex items-center justify-center pt-5 pb-3 sm:pt-6 sm:pb-4"
    >
        <!-- Kontainer Tengah: [ < ] [ Tabs ] [ > ] -->
        <div class="fi-tabs-scroll-inner flex items-center justify-center gap-2 max-w-full min-w-0 px-2">
            <!-- Tombol Panah Kiri (<) -->
            <button
                type="button"
                x-show="hasOverflow"
                x-cloak
                :disabled="!canScrollLeft"
                @click.stop="scrollLeft()"
                aria-label="Geser tab ke kiri"
                class="fi-tabs-arrow-btn fi-tabs-arrow-left group"
            >
                <svg
                    class="h-4 w-4 shrink-0 pointer-events-none"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M12.5 15L7.5 10L12.5 5" />
                </svg>
            </button>

            <!-- Nav Tablist -->
            <nav
                x-ref="tabNav"
                @click="handleTabClick($event)"
                @scroll.passive="checkScroll(); updatePill();"
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
                <!-- Apple Framer Motion Sliding Spring Pill Indicator -->
                <div
                    x-show="pillVisible"
                    x-cloak
                    aria-hidden="true"
                    class="fi-tabs-spring-indicator pointer-events-none absolute"
                    :class="{ 'fi-tabs-spring-indicator-ready': isReady }"
                    :style="`transform: translate3d(${pillLeft}px, ${pillTop}px, 0); width: ${pillWidth}px; height: ${pillHeight}px;`"
                ></div>

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
                class="fi-tabs-arrow-btn fi-tabs-arrow-right group"
            >
                <svg
                    class="h-4 w-4 shrink-0 pointer-events-none"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M7.5 5L12.5 10L7.5 15" />
                </svg>
            </button>
        </div>
    </div>
@endif
