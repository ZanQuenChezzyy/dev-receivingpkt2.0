<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @if ($getState())
        <div class="mt-1">
            <img src="{{ $getState() }}" class="fi-wi-widget max-h-24 w-auto object-contain rounded-xl p-2" alt="Signature" />
        </div>
    @else
        <span class="text-sm text-gray-500 italic">Tidak ada</span>
    @endif
</x-dynamic-component>
