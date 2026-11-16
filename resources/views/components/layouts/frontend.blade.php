@php
    $cookieTheme = request()->cookie('theme');
    $isDark = $cookieTheme === 'dark';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth bg-[#F5F5F7] dark:bg-[#070E18] {{ $isDark ? 'dark' : '' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, interactive-widget=resizes-content">
    <meta name="format-detection" content="telephone=no">
    <title>{{ $title ?? 'Welcome - Receiving Pupuk Kalimantan Timur' }}</title>

    <!-- Zero-FOUC Immediate Theme Hydration Script -->
    <script>
        (function() {
            function checkDark() {
                var stored = localStorage.getItem('theme');
                if (stored === 'dark') return true;
                if (stored === 'light') return false;
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            function syncTheme() {
                var isDark = checkDark();
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.cookie = "theme=dark; path=/; max-age=31536000; SameSite=Lax";
                } else {
                    document.documentElement.classList.remove('dark');
                    document.cookie = "theme=light; path=/; max-age=31536000; SameSite=Lax";
                }
            }

            // 1. Synchronously execute immediately before stylesheets & DOM render
            syncTheme();

            // 2. Guard against Livewire SPA navigation stripping 'dark' before morphing
            document.addEventListener('livewire:navigating', function () {
                if (checkDark()) {
                    document.documentElement.classList.add('dark');
                }
            });

            // 3. After Livewire completes navigation
            document.addEventListener('livewire:navigated', syncTheme);

            // 4. Hook into Livewire morph lifecycle to guarantee 'dark' class is never dropped
            document.addEventListener('livewire:init', function () {
                if (window.Livewire && window.Livewire.hook) {
                    window.Livewire.hook('morph.updating', function (payload) {
                        var el = payload.el;
                        var toEl = payload.toEl;
                        if (el === document.documentElement && toEl) {
                            if (checkDark()) {
                                toEl.classList.add('dark');
                            } else {
                                toEl.classList.remove('dark');
                            }
                        }
                    });
                }
            });
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- 1. Jembatan CSS Filament -->
    @filamentStyles

    <!-- System Font: Apple SF Pro / Inter Fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --livewire-progress-bar-color: #F47920;
        }
        #nprogress .bar {
            background: #F47920 !important;
            height: 3px !important;
            box-shadow: 0 0 10px rgba(244, 121, 32, 0.7) !important;
            z-index: 99999 !important;
        }
        #nprogress .peg {
            box-shadow: 0 0 14px #F47920, 0 0 6px #F47920 !important;
        }
        #nprogress .spinner-icon {
            border-top-color: #F47920 !important;
            border-left-color: #F47920 !important;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "SF Pro", "Inter", system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .nav-desktop { display: none !important; }
        .nav-mobile-btn { display: flex !important; }
        .nav-lang-switcher { display: none !important; }
        
        @media (min-width: 640px) {
            .nav-lang-switcher { display: grid !important; }
        }

        @media (min-width: 1024px) {
            .nav-desktop { display: flex !important; }
            .nav-mobile-btn { display: none !important; }
        }

        /* Prevent iOS Safari / Mobile Browser auto-zoom when tapping inputs */
        @media screen and (max-width: 768px) {
            input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="color"]):not([type="button"]):not([type="submit"]):not([type="reset"]),
            textarea,
            select {
                font-size: 16px !important;
            }
        }
        @supports (-webkit-touch-callout: none) {
            input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="color"]):not([type="button"]):not([type="submit"]):not([type="reset"]),
            textarea,
            select {
                font-size: 16px !important;
            }
        }
        input, textarea, select, button {
            touch-action: manipulation;
        }
    </style>
</head>

<body x-data="{ isDark: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }" 
      x-init="$watch('isDark', val => { 
          localStorage.setItem('theme', val ? 'dark' : 'light'); 
          document.cookie = 'theme=' + (val ? 'dark' : 'light') + '; path=/; max-age=31536000; SameSite=Lax';
          if(val) document.documentElement.classList.add('dark'); 
          else document.documentElement.classList.remove('dark'); 
      })"
      class="bg-[#F5F5F7] dark:bg-[#070E18] text-[#1D1D1F] dark:text-[#F5F5F7] antialiased overflow-x-hidden transition-colors duration-200 flex flex-col min-h-screen selection:bg-[#F47920] selection:text-white">

    <x-frontend.navbar />

    <!-- Ambient Subtle Apple-Style Canvas Background -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden select-none">
        <div x-show="!isDark" {!! $isDark ? 'style="display: none;"' : '' !!} class="absolute inset-0 bg-[radial-gradient(#0A4F86_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.035]"></div>
        <div x-show="isDark" {!! !$isDark ? 'style="display: none;"' : '' !!} class="absolute inset-0 bg-[radial-gradient(#F47920_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.03]"></div>

        <!-- Refined PKT Corporate Watermark Top-Right -->
        <div class="absolute -top-16 -right-16 w-[420px] md:w-[600px] h-[520px] md:h-[720px] pointer-events-none overflow-hidden">
            <img src="{{ $isDark ? asset('images/pattern/pkt-pattern-white.png') : asset('images/pattern/pkt-pattern-dark.png') }}"
                :src="isDark ? '{{ asset('images/pattern/pkt-pattern-white.png') }}' : '{{ asset('images/pattern/pkt-pattern-dark.png') }}'" 
                alt="Pupuk Kalimantan Timur Pattern" 
                class="w-full h-full object-contain opacity-[0.05] dark:opacity-[0.04] transition-opacity duration-300">
        </div>
    </div>

    <main class="flex-grow relative z-10">
        {{ $slot }}
    </main>

    <x-frontend.footer />

    <!-- Chatbot Widget -->
    <livewire:chatbot-widget />

    <!-- 2. Jembatan JavaScript Filament -->
    @filamentScripts
</body>

</html>