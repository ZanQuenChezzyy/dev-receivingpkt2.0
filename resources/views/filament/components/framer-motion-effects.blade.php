<script>
    (function () {
        // =========================================================
        // 1. Apple 3D Parallax Tilt with Mouse Spotlight on Stats Cards
        // =========================================================
        function initStatsSpringPhysics() {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            if (window.matchMedia && !window.matchMedia('(pointer: fine)').matches) return;

            const cards = document.querySelectorAll('.fi-wi-stats-overview-stat');
            cards.forEach(card => {
                if (card.dataset.springInit) return;
                card.dataset.springInit = 'true';

                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);

                    // Subtle Apple 3D Tilt
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * -4;
                    const rotateY = ((x - centerX) / centerX) * 4;

                    card.style.transform = `perspective(800px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-4px) scale(1.012)`;
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = '';
                });
            });
        }

        // =========================================================
        // 2. Persistent Cache & Smart Counter (Only on First Load or Actual Value Change)
        // =========================================================
        window.__statValuesCache = window.__statValuesCache || {};
        window.__statPageLoaded = window.__statPageLoaded || false;

        function markPageLoaded() {
            if (window.__statPageLoaded) return;
            setTimeout(() => {
                window.__statPageLoaded = true;
                document.body.classList.add('stats-already-loaded');
            }, 1200);
        }

        function animateValue(container) {
            const numEl = container.querySelector('.fi-stat-num') || container;
            const statKey = numEl.dataset.statKey || container.closest('.fi-wi-stats-overview-stat')?.querySelector('.fi-wi-stats-overview-stat-label')?.textContent?.trim() || container.textContent.trim();
            if (!statKey) return;

            const originalText = numEl.textContent.trim();
            if (!originalText) return;

            let targetNum = numEl.dataset.rawValue ? parseFloat(numEl.dataset.rawValue) : null;
            let prefix = '';
            let suffix = '';

            if (targetNum === null || isNaN(targetNum)) {
                const match = originalText.match(/^([^\d]*)([\d.,]+)(.*)$/);
                if (!match) return;
                prefix = match[1];
                const numStr = match[2];
                suffix = match[3];
                targetNum = parseFloat(numStr.replace(/\./g, '').replace(/,/g, '.'));
            }

            if (isNaN(targetNum) || targetNum < 0) return;

            const prevVal = window.__statValuesCache[statKey];

            // Kasus 1: Pertama kali halaman dimuat (animate from 0)
            if (prevVal === undefined) {
                window.__statValuesCache[statKey] = targetNum;
                if (targetNum === 0) return;
                runCounter(numEl, 0, targetNum, prefix, suffix, originalText);
                return;
            }

            // Kasus 2: Livewire polling rutin (misal tiap 5 detik)
            // Jika nilainya SAMA PERSIS dengan sebelumnya, JANGAN MAINKAN ANIMASI
            if (prevVal === targetNum) {
                return;
            }

            // Kasus 3: Jika data di database BENAR-BENAR BERUBAH
            const oldVal = prevVal;
            window.__statValuesCache[statKey] = targetNum;
            runCounter(numEl, oldVal, targetNum, prefix, suffix, originalText);
        }

        function runCounter(el, fromVal, toVal, prefix, suffix, finalFormattedText) {
            if (el.dataset.animating === 'true') return;
            el.dataset.animating = 'true';

            const duration = 800;
            const startTime = performance.now();

            function formatNumber(val) {
                return Math.round(val).toLocaleString('id-ID');
            }

            function update(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3.2);
                const currentVal = fromVal + (toVal - fromVal) * easeProgress;

                el.textContent = `${prefix}${formatNumber(currentVal)}${suffix}`;

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = finalFormattedText;
                    el.dataset.animating = 'false';
                }
            }

            requestAnimationFrame(update);
        }

        function initStatsCounter() {
            const values = document.querySelectorAll('.fi-wi-stats-overview-stat-value');
            values.forEach(v => animateValue(v));
        }

        // =========================================================
        // 3. Universal Modal Focus & Stacking Watchdog
        // =========================================================
        function updateModalActiveState() {
            const hasOpenModal = !!document.querySelector('.fi-modal.fi-modal-open, .fi-modal-open');
            document.body.classList.toggle('has-filament-modal-open', hasOpenModal);
            document.documentElement.classList.toggle('has-filament-modal-open', hasOpenModal);
        }

        const modalObserver = new MutationObserver(() => {
            updateModalActiveState();
        });

        if (document.body) {
            modalObserver.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }

        window.addEventListener('open-modal', () => setTimeout(updateModalActiveState, 20));
        window.addEventListener('close-modal', () => setTimeout(updateModalActiveState, 20));
        window.addEventListener('close-modal-quietly', () => setTimeout(updateModalActiveState, 20));

        // =========================================================
        // 4. Anti-Clipping Viewport-Fixed Positioning for Table Dropdowns
        //    Prevents .fi-dropdown-panel inside overflow-x:auto tables
        //    from being sliced off on bottom rows (Mobile & Desktop)
        // =========================================================
        const activeFixedPanels = new Set();
        let isRepositioningDropdowns = false;

        function resetFixedPanel(panel) {
            if (!panel) return;
            if (!panel.classList.contains('fi-dropdown-viewport-fixed') && !panel.dataset.pktLastPos) {
                activeFixedPanels.delete(panel);
                return;
            }
            isRepositioningDropdowns = true;
            try {
                panel.classList.remove('fi-dropdown-viewport-fixed');
                panel.style.removeProperty('position');
                panel.style.removeProperty('top');
                panel.style.removeProperty('left');
                panel.style.removeProperty('right');
                panel.style.removeProperty('bottom');
                panel.style.removeProperty('transform');
                panel.style.removeProperty('max-height');
                delete panel.dataset.pktLastPos;
                activeFixedPanels.delete(panel);
            } finally {
                isRepositioningDropdowns = false;
            }
        }

        function positionTableDropdownPanel(panel) {
            if (!panel || !panel.isConnected) {
                activeFixedPanels.delete(panel);
                return;
            }

            const isHidden = panel.style.display === 'none' || panel.hasAttribute('x-cloak') || window.getComputedStyle(panel).display === 'none';
            if (isHidden) {
                resetFixedPanel(panel);
                return;
            }

            const dropdown = panel.closest('.fi-dropdown');
            const trigger = dropdown ? dropdown.querySelector('.fi-dropdown-trigger') : null;
            if (!trigger) return;

            const triggerRect = trigger.getBoundingClientRect();
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const edgePad = 12;
            const gap = 8;

            // Jika trigger sudah tergulir keluar viewport, tutup panel agar tidak melayang di tempat kosong
            if (triggerRect.bottom < 0 || triggerRect.top > vh || triggerRect.right < 0 || triggerRect.left > vw) {
                resetFixedPanel(panel);
                return;
            }

            isRepositioningDropdowns = true;
            try {
                if (!panel.classList.contains('fi-dropdown-viewport-fixed')) {
                    panel.classList.add('fi-dropdown-viewport-fixed');
                }
                panel.style.setProperty('position', 'fixed', 'important');
                panel.style.setProperty('right', 'auto', 'important');
                panel.style.setProperty('bottom', 'auto', 'important');
                panel.style.setProperty('transform', 'none', 'important');

                const panelRect = panel.getBoundingClientRect();
                const panelW = Math.min(panelRect.width || 220, vw - edgePad * 2);
                const panelH = Math.min(panelRect.height || 200, Math.max(160, vh - 96));

                const spaceBelow = vh - triggerRect.bottom - edgePad;
                const spaceAbove = triggerRect.top - edgePad;

                let top;
                let availH;
                if (spaceBelow >= Math.min(panelH, 180) || spaceBelow >= spaceAbove) {
                    top = triggerRect.bottom + gap;
                    availH = Math.max(120, vh - top - edgePad);
                } else {
                    availH = Math.max(120, spaceAbove - gap);
                    const actualH = Math.min(panelH, availH);
                    top = Math.max(edgePad, triggerRect.top - actualH - gap);
                }

                // Sejajarkan ke tepi kanan tombol (cocok untuk kolom aksi di sisi kanan) lalu clamp agar aman di mobile
                let left = triggerRect.right - panelW;
                if (left < edgePad) {
                    left = Math.max(edgePad, triggerRect.left);
                }
                if (left + panelW > vw - edgePad) {
                    left = Math.max(edgePad, vw - panelW - edgePad);
                }

                const rTop = Math.round(top);
                const rLeft = Math.round(left);
                const rMaxH = Math.round(Math.min(384, availH));
                const posKey = `${rTop},${rLeft},${rMaxH}`;

                if (panel.dataset.pktLastPos !== posKey) {
                    panel.style.setProperty('top', `${rTop}px`, 'important');
                    panel.style.setProperty('left', `${rLeft}px`, 'important');
                    panel.style.setProperty('max-height', `${rMaxH}px`, 'important');
                    panel.dataset.pktLastPos = posKey;
                }

                activeFixedPanels.add(panel);
            } finally {
                isRepositioningDropdowns = false;
            }
        }

        function syncAllTableDropdowns() {
            if (isRepositioningDropdowns) return;
            const panels = document.querySelectorAll('.fi-ta-ctn .fi-dropdown-panel');
            panels.forEach((panel) => {
                const isVisible = panel.style.display !== 'none' && !panel.hasAttribute('x-cloak') && window.getComputedStyle(panel).display !== 'none';
                if (isVisible) {
                    positionTableDropdownPanel(panel);
                } else {
                    resetFixedPanel(panel);
                }
            });
        }

        if (!window.__pktTableDropdownWatcherInit) {
            window.__pktTableDropdownWatcherInit = true;

            const dropdownObserver = new MutationObserver((mutations) => {
                if (isRepositioningDropdowns) return;
                let needsSync = false;
                for (const m of mutations) {
                    if (m.target instanceof Element && m.target.classList.contains('fi-dropdown-panel') && m.target.closest('.fi-ta-ctn')) {
                        needsSync = true;
                        break;
                    }
                }
                if (needsSync) {
                    requestAnimationFrame(syncAllTableDropdowns);
                }
            });

            if (document.body) {
                dropdownObserver.observe(document.body, {
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['style', 'class', 'x-cloak'],
                });
            }

            document.addEventListener('click', (e) => {
                if (e.target instanceof Element && e.target.closest('.fi-ta-ctn .fi-dropdown-trigger')) {
                    requestAnimationFrame(() => {
                        syncAllTableDropdowns();
                        setTimeout(syncAllTableDropdowns, 60);
                    });
                }
            }, true);

            document.addEventListener('livewire:navigated', () => {
                Array.from(activeFixedPanels).forEach((panel) => resetFixedPanel(panel));
                activeFixedPanels.clear();
            });

            const repositionActive = () => {
                if (activeFixedPanels.size === 0 || isRepositioningDropdowns) return;
                activeFixedPanels.forEach((panel) => positionTableDropdownPanel(panel));
            };

            window.addEventListener('scroll', repositionActive, { passive: true, capture: true });
            window.addEventListener('resize', repositionActive, { passive: true });
        }

        function runAll() {
            initStatsSpringPhysics();
            initStatsCounter();
            updateModalActiveState();
            markPageLoaded();
        }

        // Lifecycle Attachments
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runAll);
        } else {
            runAll();
        }

        document.addEventListener('livewire:navigated', () => {
            window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
            window.__statPageLoaded = false;
            document.body.classList.remove('stats-already-loaded');
            setTimeout(runAll, 60);
        });

        document.addEventListener('livewire:init', () => {
            if (window.Livewire && window.Livewire.hook) {
                window.Livewire.hook('morph.updated', () => {
                    setTimeout(runAll, 60);
                });
            }
        });

        // Periodic check for stats cards rendered dynamically
        let checkCount = 0;
        const interval = setInterval(() => {
            runAll();
            checkCount++;
            if (checkCount > 10) clearInterval(interval);
        }, 300);
    })();
</script>
