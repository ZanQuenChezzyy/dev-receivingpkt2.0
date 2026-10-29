<div class="pointer-events-none absolute inset-0 overflow-hidden select-none z-0">
    {{-- Top-Right PKT Corporate Watermark --}}
    <div class="absolute -top-16 -right-16 w-64 sm:w-80 h-64 sm:h-80 flex items-start justify-end overflow-hidden">
        <img
            src="{{ asset('images/pattern/pkt-pattern-dark.png') }}"
            alt="Pupuk Kalimantan Timur Pattern"
            class="fi-sidebar-pattern-light w-full h-full object-contain opacity-[0.10]"
        >
        <img
            src="{{ asset('images/pattern/pkt-pattern-white.png') }}"
            alt="Pupuk Kalimantan Timur Pattern"
            class="fi-sidebar-pattern-dark w-full h-full object-contain opacity-[0.05]"
        >
    </div>

    {{-- Bottom-Center PKT Pattern (Matching Sidebar Pattern) --}}
    <div class="absolute inset-x-0 bottom-0 flex justify-center items-end overflow-hidden">
        <div class="relative w-full h-72 sm:h-80 flex items-end justify-center overflow-hidden">
            <img
                src="{{ asset('images/pattern/pkt-pattern-dark.png') }}"
                alt="Pupuk Kalimantan Timur Pattern"
                class="fi-sidebar-pattern-light w-full max-w-[260px] sm:max-w-[300px] h-full object-contain object-bottom opacity-[0.15]"
            >
            <img
                src="{{ asset('images/pattern/pkt-pattern-white.png') }}"
                alt="Pupuk Kalimantan Timur Pattern"
                class="fi-sidebar-pattern-dark w-full max-w-[260px] sm:max-w-[300px] h-full object-contain object-bottom opacity-[0.06]"
            >
        </div>
    </div>
</div>

