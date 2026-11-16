<script>
    (function () {
        // =========================================================
        // 1. Apple 3D Parallax Tilt with Mouse Spotlight on Stats Cards
        // =========================================================
        function initStatsSpringPhysics() {
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
        // 2. Spring Number Counter (Ticker) on Stat Values
        // =========================================================
        function animateValue(el) {
            if (el.dataset.animating === 'true') return;
            const originalText = el.textContent.trim();
            if (!originalText || el.dataset.animatedValue === originalText) return;

            // Match currency/numbers with prefix & suffix (e.g., "Rp 1.250.000", "263.600 Lbr", "15 Item")
            const match = originalText.match(/^([^\d]*)([\d.,]+)(.*)$/);
            if (!match) return;

            const prefix = match[1];
            const numStr = match[2];
            const suffix = match[3];

            // Normalize dots & commas
            const cleanNum = parseFloat(numStr.replace(/\./g, '').replace(/,/g, '.'));
            if (isNaN(cleanNum) || cleanNum <= 0) return;

            el.dataset.animating = 'true';
            el.dataset.animatedValue = originalText;

            const duration = 750; // ms
            const startTime = performance.now();

            function formatNumber(val) {
                return Math.round(val).toLocaleString('id-ID');
            }

            function update(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Apple spring ease-out curve
                const easeProgress = 1 - Math.pow(1 - progress, 3.5);
                const currentVal = cleanNum * easeProgress;

                el.textContent = `${prefix}${formatNumber(currentVal)}${suffix}`;

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = originalText;
                    el.dataset.animating = 'false';
                }
            }

            requestAnimationFrame(update);
        }

        function initStatsCounter() {
            const values = document.querySelectorAll('.fi-wi-stats-overview-stat-value');
            values.forEach(v => animateValue(v));
        }

        function runAll() {
            initStatsSpringPhysics();
            initStatsCounter();
        }

        // Lifecycle Attachments
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runAll);
        } else {
            runAll();
        }

        document.addEventListener('livewire:navigated', () => {
            window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
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
