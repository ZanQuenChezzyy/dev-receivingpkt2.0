@php
    $user = filament()->auth()->user();
    $isAuthRoute = request()->routeIs('filament.*.auth.*');
    $curtainPayload = ($user && ! $isAuthRoute) ? session()->pull('pkt_login_curtain') : null;
@endphp

@if (is_array($curtainPayload) && filled($curtainPayload['token'] ?? null))
    @php
        $token = (string) $curtainPayload['token'];
        $badgeStyle = method_exists($user, 'resolvedDeveloperBadgeStyle')
            ? $user->resolvedDeveloperBadgeStyle()
            : \App\Models\User::DEVELOPER_BADGE_NONE;
        $roleLabel = (method_exists($user, 'primaryAnimatedBadgeRole') ? $user->primaryAnimatedBadgeRole() : null)
            ?? ($user->roles->first()?->name ?? 'Pengguna');
        $avatarUrl = $user->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=FFFFFF&background=F47920';

        $hour = (int) now()->timezone(config('app.timezone', 'Asia/Makassar'))->format('G');
        $greeting = match (true) {
            $hour >= 4 && $hour < 11 => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 19 => 'Selamat Sore',
            default => 'Selamat Malam',
        };
    @endphp

    <div
        id="pkt-login-curtain"
        class="pkt-login-curtain"
        data-curtain-token="{{ e($token) }}"
        role="status"
        aria-live="polite"
        aria-label="Memuat ruang kerja Receiving PKT"
    >
        {{-- Secondary Accent Trail Curtain (PKT gradient + white PKT pattern sweep) --}}
        <div class="pkt-curtain-trail" aria-hidden="true"></div>

        {{-- Primary Architectural Curtain Surface --}}
        <div class="pkt-curtain-panel">
            {{-- Subtle Corporate Radial Aura & Official PKT Pattern Background --}}
            <div class="pkt-curtain-ambient" aria-hidden="true"></div>
            <div class="pkt-curtain-pattern-tile" aria-hidden="true"></div>

            {{-- Architectural Corner PKT Pattern Watermarks --}}
            <div class="pkt-curtain-watermark pkt-curtain-watermark--tr" aria-hidden="true">
                <img
                    src="{{ asset('images/pattern/pkt-pattern-navy.png') }}"
                    alt=""
                    class="w-full h-full object-contain dark:hidden opacity-[0.14]"
                />
                <img
                    src="{{ asset('images/pattern/pkt-pattern-white.png') }}"
                    alt=""
                    class="w-full h-full object-contain hidden dark:block opacity-[0.09]"
                />
            </div>

            <div class="pkt-curtain-watermark pkt-curtain-watermark--bl" aria-hidden="true">
                <img
                    src="{{ asset('images/pattern/pkt-pattern-orange.png') }}"
                    alt=""
                    class="w-full h-full object-contain opacity-[0.15] dark:opacity-[0.11]"
                />
            </div>

            {{-- Centerpiece Content --}}
            <div class="pkt-curtain-content">
                {{-- Brand Mark --}}
                <div class="pkt-curtain-brand">
                    <img
                        src="{{ asset('images/logo/receiving_dark.png') }}"
                        alt="Receiving PKT"
                        class="h-10 sm:h-11 w-auto object-contain dark:hidden"
                    />
                    <img
                        src="{{ asset('images/logo/receiving_white.png') }}"
                        alt="Receiving PKT"
                        class="h-10 sm:h-11 w-auto object-contain hidden dark:block"
                    />
                </div>

                {{-- Personalized User Identity Pill --}}
                <div class="pkt-curtain-identity">
                    <div class="shrink-0">
                        {!! \App\Support\DeveloperBadgeRenderer::renderAvatar(
                            avatarUrl: $avatarUrl,
                            alt: (string) $user->name,
                            style: $badgeStyle,
                            imgClasses: 'w-14 h-14',
                            size: 'md',
                            fallbackRingClasses: 'ring-2 ring-slate-200 dark:ring-white/15 rounded-full shadow-md',
                        ) !!}
                    </div>

                    <div class="flex flex-col items-center text-center gap-1.5 min-w-0">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0054A6] dark:text-[#83A2DB]">
                            {{ $greeting }}
                        </span>
                        <div class="text-lg sm:text-xl font-bold tracking-tight text-[#10141A] dark:text-white leading-tight">
                            {{ $user->name }}
                        </div>
                        <div class="pt-0.5">
                            {!! \App\Support\DeveloperBadgeRenderer::render($badgeStyle, $roleLabel, 'sm') !!}
                        </div>
                    </div>
                </div>

                {{-- Sleek Progress Bar & Status --}}
                <div class="pkt-curtain-progress-wrap">
                    <div class="pkt-curtain-progress-track">
                        <div class="pkt-curtain-progress-bar"></div>
                    </div>
                    <div class="pkt-curtain-status">
                        Menyiapkan ruang kerja Receiving PKT...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const curtain = document.getElementById('pkt-login-curtain');
            if (!curtain) {
                return;
            }

            const token = curtain.getAttribute('data-curtain-token') || '';
            const storageKey = 'pkt_login_curtain_played_' + token;

            try {
                if (token && window.sessionStorage && window.sessionStorage.getItem(storageKey) === '1') {
                    curtain.remove();
                    return;
                }
                if (token && window.sessionStorage) {
                    window.sessionStorage.setItem(storageKey, '1');
                }
            } catch (e) {
                // Abaikan bila sessionStorage dibatasi browser.
            }

            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let dismissed = false;

            document.documentElement.classList.add('pkt-curtain-lock');

            function cleanup() {
                document.documentElement.classList.remove('pkt-curtain-lock');
                if (curtain && curtain.parentNode) {
                    curtain.parentNode.removeChild(curtain);
                }
            }

            function triggerLift() {
                if (dismissed) {
                    return;
                }
                dismissed = true;
                curtain.classList.add('is-lifting');
                document.documentElement.classList.remove('pkt-curtain-lock');
                window.setTimeout(cleanup, prefersReducedMotion ? 320 : 820);
            }

            // Mulai animasi bar segera pada frame berikutnya
            window.requestAnimationFrame(function () {
                curtain.classList.add('is-ready');
            });

            // Klik di mana saja untuk melewati tirai lebih cepat bila diperlukan
            window.setTimeout(function () {
                curtain.addEventListener('click', triggerLift, { once: true });
            }, 220);

            const minDisplayMs = prefersReducedMotion ? 260 : 940;
            const startedAt = performance.now();

            function scheduleLift() {
                const elapsed = performance.now() - startedAt;
                const remaining = Math.max(0, minDisplayMs - elapsed);
                window.setTimeout(triggerLift, remaining);
            }

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                scheduleLift();
            } else {
                window.addEventListener('DOMContentLoaded', scheduleLift, { once: true });
            }

            // Pengaman mutlak agar tirai tidak pernah tertahan di layar
            window.setTimeout(function () {
                triggerLift();
                window.setTimeout(cleanup, 900);
            }, 2200);
        })();
    </script>
@endif
