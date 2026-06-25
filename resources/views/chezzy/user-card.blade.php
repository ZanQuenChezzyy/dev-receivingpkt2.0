@props([
    'user' => Auth::user(),
])

<div class="flex items-center justify-center">
    <div
        class="fi-user-card group grow p-3 transition-all duration-500 hover:-translate-y-0.5">

        <div class="relative z-10 flex items-center gap-4">
            {{-- Avatar --}}
            <x-filament::avatar
                :src="filament()->getUserAvatarUrl($user)"
                :alt="__('filament-panels::layout.avatar.alt', ['name' => filament()->getUserName($user)])"
                :attributes="\Filament\Support\prepare_inherited_attributes($attributes)->class([
                    'fi-user-avatar rounded-xl w-10 h-10',
                ])" />

            {{-- User Info --}}
            <div class="flex flex-col gap-0.5">
                <h3 class="text-gray-800 dark:text-gray-100 font-semibold text-xs tracking-wide">
                    {{ $user->name }}
                </h3>
                <p class="text-gray-500 dark:text-gray-300 text-[10px]">
                    {{ $user->roles->first()->name ?? 'No Role Assigned' }}
                </p>
            </div>
        </div>
    </div>
</div>
