<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>{{ $title ?? 'Welcome - Receiving Pupuk Kalimantan Timur' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- 1. Jembatan CSS Filament -->
    @filamentStyles

    <!-- System Font: Apple SF Pro / Inter Fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "SF Pro", "Inter", system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .nav-desktop { display: none !important; }
        .nav-mobile-btn { display: flex !important; }
        
        @media (min-width: 1024px) {
            .nav-desktop { display: flex !important; }
            .nav-mobile-btn { display: none !important; }
        }
    </style>
    <script>
        function applyTheme() {
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        applyTheme();
        document.addEventListener('livewire:navigated', applyTheme);
    </script>
</head>

<body x-data="{ isDark: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }" 
      x-init="$watch('isDark', val => { localStorage.setItem('theme', val ? 'dark' : 'light'); if(val) document.documentElement.classList.add('dark'); else document.documentElement.classList.remove('dark'); })"
      class="bg-[#F5F5F7] dark:bg-[#070E18] text-[#1D1D1F] dark:text-[#F5F5F7] antialiased overflow-x-hidden transition-colors duration-500 flex flex-col min-h-screen selection:bg-[#F47920] selection:text-white">

    <x-frontend.navbar />

    <!-- Ambient Subtle Apple-Style Canvas Background -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden select-none">
        <div x-show="!isDark" class="absolute inset-0 bg-[radial-gradient(#0A4F86_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.035]"></div>
        <div x-show="isDark" style="display: none;" class="absolute inset-0 bg-[radial-gradient(#F47920_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.03]"></div>

        <!-- Refined PKT Corporate Watermark Top-Right -->
        <div class="absolute -top-16 -right-16 w-[420px] md:w-[600px] h-[520px] md:h-[720px] pointer-events-none overflow-hidden">
            <img :src="isDark ? '{{ asset('images/pattern/pkt-pattern-white.png') }}' : '{{ asset('images/pattern/pkt-pattern-dark.png') }}'" 
                alt="Pupuk Kalimantan Timur Pattern" 
                class="w-full h-full object-contain opacity-[0.05] dark:opacity-[0.04] transition-opacity duration-500">
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