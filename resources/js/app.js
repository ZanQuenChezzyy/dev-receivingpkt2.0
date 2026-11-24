/**
 * Global Frontend Button & Navigation Loading Manager
 *
 * Memberikan indikator loading (spinner) langsung pada tombol/tautan saat
 * halaman dimuat atau aksi Livewire diproses, sekaligus mencegah klik ganda.
 */
(function initFrontendButtonLoader() {
    if (window.__pktFrontendLoaderInitialized) {
        return;
    }
    window.__pktFrontendLoaderInitialized = true;

    const SPINNER_CLASS = 'btn-inline-spinner';
    const LOADING_CLASS = 'is-btn-loading';
    const COMPACT_LOADING_CLASS = 'is-btn-loading-compact';
    const HIDDEN_ICON_CLASS = 'btn-loading-hide';
    const SAFETY_TIMEOUT_MS = 10000;

    /** @type {Set<HTMLElement>} */
    const activeNavElements = new Set();
    /** @type {Set<HTMLElement>} */
    const activeWireElements = new Set();
    /** @type {WeakMap<HTMLElement, number>} */
    const safetyTimers = new WeakMap();

    function createSpinnerSvg() {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', SPINNER_CLASS);
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('aria-hidden', 'true');

        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', '12');
        circle.setAttribute('cy', '12');
        circle.setAttribute('r', '10');
        circle.setAttribute('stroke', 'currentColor');
        circle.setAttribute('stroke-width', '3.5');
        circle.setAttribute('opacity', '0.28');

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('fill', 'currentColor');
        path.setAttribute('opacity', '0.92');
        path.setAttribute(
            'd',
            'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z'
        );

        svg.appendChild(circle);
        svg.appendChild(path);

        return svg;
    }

    function showTopLoadBar() {
        const bar = document.getElementById('pkt-page-load-bar');
        if (!bar) {
            return;
        }
        bar.classList.remove('is-active');
        // Force reflow agar animasi selalu dimulai ulang dengan mulus
        void bar.offsetWidth;
        bar.classList.add('is-active');
    }

    function hideTopLoadBar() {
        const bar = document.getElementById('pkt-page-load-bar');
        if (!bar) {
            return;
        }
        bar.classList.remove('is-active');
    }

    function setElementLoading(el, registrySet) {
        if (!el || el.classList.contains(LOADING_CLASS)) {
            return;
        }

        el.classList.add(LOADING_CLASS);
        el.setAttribute('aria-busy', 'true');
        registrySet.add(el);

        // Jika elemen sudah memiliki ikon SVG langsung (misal panah/ikon aksi),
        // gantikan ikon tersebut dengan spinner agar lebar tombol tidak bergeser.
        const directSvgs = Array.from(el.children).filter(
            (child) => child.tagName && child.tagName.toLowerCase() === 'svg' && !child.classList.contains(SPINNER_CLASS)
        );

        const spinner = createSpinnerSvg();

        if (directSvgs.length > 0) {
            const targetIcon = directSvgs[directSvgs.length - 1];
            targetIcon.classList.add(HIDDEN_ICON_CLASS);
            targetIcon.insertAdjacentElement('afterend', spinner);
        } else if (el.offsetWidth > 0 && el.offsetWidth < 52) {
            // Untuk tombol/pil sangat sempit (mis. tombol halaman 2, 3 atau pengalih ID/EN),
            // tampilkan spinner tepat di tengah agar tidak melebar atau terpotong.
            el.classList.add(COMPACT_LOADING_CLASS);
            el.appendChild(spinner);
        } else {
            // Sisipkan spinner secara halus di sebelah kiri konten teks
            spinner.style.marginRight = '0.45rem';
            el.insertBefore(spinner, el.firstChild);
        }

        // Pengaman agar tombol tidak pernah terkunci selamanya jika request batal/offline
        const timerId = window.setTimeout(() => {
            clearElementLoading(el, registrySet);
        }, SAFETY_TIMEOUT_MS);
        safetyTimers.set(el, timerId);
    }

    function clearElementLoading(el, registrySet) {
        if (!el) {
            return;
        }

        const timerId = safetyTimers.get(el);
        if (timerId) {
            window.clearTimeout(timerId);
            safetyTimers.delete(el);
        }

        el.classList.remove(LOADING_CLASS, COMPACT_LOADING_CLASS);
        el.removeAttribute('aria-busy');

        el.querySelectorAll(`:scope > .${SPINNER_CLASS}`).forEach((node) => node.remove());
        el.querySelectorAll(`:scope > .${HIDDEN_ICON_CLASS}`).forEach((node) => node.classList.remove(HIDDEN_ICON_CLASS));

        if (registrySet) {
            registrySet.delete(el);
        }
    }

    function clearAllInSet(registrySet) {
        Array.from(registrySet).forEach((el) => clearElementLoading(el, registrySet));
    }

    function isSamePageUrl(targetHref) {
        try {
            const target = new URL(targetHref, window.location.origin);
            const current = new URL(window.location.href);

            return (
                target.origin === current.origin &&
                target.pathname === current.pathname &&
                target.search === current.search
            );
        } catch {
            return false;
        }
    }

    function shouldHandleLinkNavigation(link, event) {
        if (!link || link.hasAttribute('data-no-loading')) {
            return false;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return false;
        }

        const href = (link.getAttribute('href') || '').trim();
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return false;
        }

        if (link.getAttribute('target') === '_blank' || link.hasAttribute('download')) {
            return false;
        }

        // Abaikan tautan logo yang hanya berisi gambar tanpa teks
        const hasOnlyImage = link.querySelector('img') !== null && link.textContent.trim() === '';
        if (hasOnlyImage) {
            return false;
        }

        // Abaikan klik pada URL halaman yang sama (kecuali pengalih bahasa)
        const isLocaleSwitch = href.includes('/lang/');
        if (!isLocaleSwitch && isSamePageUrl(link.href)) {
            return false;
        }

        // Hanya tangani tautan internal (same-origin)
        try {
            const parsed = new URL(link.href, window.location.origin);
            if (parsed.origin !== window.location.origin) {
                return false;
            }
        } catch {
            return false;
        }

        return true;
    }

    function hasCustomWireLoading(el) {
        return el.querySelector('[wire\\:loading], [wire\\:loading\\.remove], [wire\\:loading\\.flex], [wire\\:loading\\.inline-flex]') !== null;
    }

    function isInstantLocalWireAction(wireClickValue) {
        const val = (wireClickValue || '').trim();
        if (!val) {
            return true;
        }

        // Abaikan aksi tutup modal / hapus input / toggle lokal cepat
        return (
            val.includes('showConfirmModal') ||
            val.includes('showSuccessMessage') ||
            val.includes('closeModal') ||
            val.includes("searchError") ||
            val.includes("'poNumber', ''") ||
            val.includes("'rdtvComplianceFile', null") ||
            val.includes("'berkasPenagihanFile', null") ||
            val.includes('search_mode')
        );
    }

    // 1. Capture-phase click handler untuk mencegah double-click dan menampilkan spinner instan
    document.addEventListener(
        'click',
        (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            // Cegah klik ganda pada tombol/tautan yang sedang memuat
            const alreadyLoading = target.closest(`.${LOADING_CLASS}`);
            if (alreadyLoading) {
                event.preventDefault();
                event.stopImmediatePropagation();

                return;
            }

            // A. Cek tautan navigasi (wire:navigate maupun halaman penuh seperti Login/Dashboard/Bahasa)
            const link = target.closest('a[href]');
            if (link instanceof HTMLAnchorElement && shouldHandleLinkNavigation(link, event)) {
                setElementLoading(link, activeNavElements);

                if (!link.hasAttribute('wire:navigate')) {
                    showTopLoadBar();
                }

                return;
            }

            // B. Cek tombol aksi Livewire (wire:click) yang belum memiliki spinner khusus
            const wireBtn = target.closest('button[wire\\:click], a[wire\\:click]');
            if (
                wireBtn instanceof HTMLElement &&
                !wireBtn.hasAttribute('data-no-loading') &&
                !wireBtn.hasAttribute('disabled') &&
                !wireBtn.hasAttribute('wire:confirm') &&
                !hasCustomWireLoading(wireBtn) &&
                !isInstantLocalWireAction(wireBtn.getAttribute('wire:click'))
            ) {
                setElementLoading(wireBtn, activeWireElements);
            }
        },
        true
    );

    // 2. Bersihkan loading navigasi saat Livewire SPA selesai atau saat kembali lewat tombol Back (bfcache)
    document.addEventListener('livewire:navigated', () => {
        clearAllInSet(activeNavElements);
        clearAllInSet(activeWireElements);
        hideTopLoadBar();
    });

    window.addEventListener('pageshow', () => {
        clearAllInSet(activeNavElements);
        clearAllInSet(activeWireElements);
        hideTopLoadBar();
    });

    window.addEventListener('popstate', () => {
        clearAllInSet(activeNavElements);
        hideTopLoadBar();
    });

    // 3. Hook Livewire commit untuk membersihkan spinner tombol wire:click tepat saat server merespons
    function registerLivewireCommitHook() {
        if (!window.Livewire || typeof window.Livewire.hook !== 'function' || window.__pktWireHookRegistered) {
            return;
        }
        window.__pktWireHookRegistered = true;

        window.Livewire.hook('commit', ({ succeed, fail }) => {
            const done = () => {
                window.requestAnimationFrame(() => {
                    clearAllInSet(activeWireElements);
                });
            };
            succeed(done);
            fail(done);
        });
    }

    if (window.Livewire) {
        registerLivewireCommitHook();
    } else {
        document.addEventListener('livewire:init', registerLivewireCommitHook);
    }
})();
