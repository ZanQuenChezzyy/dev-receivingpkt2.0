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

    <!-- Font Google: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
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
      class="bg-slate-50 dark:bg-[#031525] text-slate-800 dark:text-slate-200 antialiased overflow-x-hidden transition-colors duration-500 flex flex-col min-h-screen">

    <x-frontend.navbar />

    <!-- Ambient PKT Geometric Background (Fixed, Theme Responsive) -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden select-none">
        <div x-show="!isDark" class="absolute inset-0 bg-[radial-gradient(#0A4F86_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.05]"></div>
        <div x-show="isDark" style="display: none;" class="absolute inset-0 bg-[radial-gradient(#F47920_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.04]"></div>

        <!-- Minimal PKT Corporate Watermark Top-Right -->
        <div class="absolute -top-16 -right-16 w-[420px] md:w-[600px] h-[520px] md:h-[720px] pointer-events-none overflow-hidden">
            <img :src="isDark ? '{{ asset('images/pattern/pkt-pattern-white.png') }}' : '{{ asset('images/pattern/pkt-pattern-dark.png') }}'" 
                alt="Pupuk Kalimantan Timur Pattern" 
                class="w-full h-full object-contain opacity-[0.08] dark:opacity-[0.05] transition-opacity duration-500">
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