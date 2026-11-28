<?php

namespace App\Filament\Livewire;

use App\Models\User;
use App\Services\LeftRailMenuService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use Livewire\Component;

class LeftIconRail extends Component
{
    public bool $showSettingsModal = false;

    /** @var list<string> */
    public array $selectedKeys = [];

    public string $search = '';

    public string $currentUrl = '';

    public function mount(): void
    {
        $this->currentUrl = url()->current();
        $this->selectedKeys = app(LeftRailMenuService::class)->getSelectedCustomKeys($this->getCurrentUser());
    }

    #[On('open-left-rail-settings')]
    public function openSettings(): void
    {
        $this->selectedKeys = app(LeftRailMenuService::class)->getSelectedCustomKeys($this->getCurrentUser());
        $this->search = '';
        $this->showSettingsModal = true;
    }

    public function closeSettings(): void
    {
        $this->showSettingsModal = false;
    }

    public function toggleShortcut(string $key): void
    {
        $key = trim($key);
        if ($key === '' || $key === LeftRailMenuService::DASHBOARD_KEY) {
            return;
        }

        $service = app(LeftRailMenuService::class);
        $available = $service->availableCustomizableItems();
        if (! isset($available[$key])) {
            return;
        }

        if (in_array($key, $this->selectedKeys, true)) {
            $this->selectedKeys = array_values(array_filter(
                $this->selectedKeys,
                static fn (string $k): bool => $k !== $key,
            ));

            return;
        }

        if (count($this->selectedKeys) >= LeftRailMenuService::MAX_CUSTOM_SHORTCUTS) {
            Notification::make()
                ->title('Batas Maksimal Tercapai')
                ->body('Maksimal '.LeftRailMenuService::MAX_CUSTOM_SHORTCUTS.' menu tambahan di Navigasi Kiri (di luar Dashboard yang terkunci). Hapus salah satu menu terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $this->selectedKeys[] = $key;
    }

    public function moveShortcutUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->selectedKeys)) {
            return;
        }

        $temp = $this->selectedKeys[$index - 1];
        $this->selectedKeys[$index - 1] = $this->selectedKeys[$index];
        $this->selectedKeys[$index] = $temp;
        $this->selectedKeys = array_values($this->selectedKeys);
    }

    public function moveShortcutDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->selectedKeys) - 1) {
            return;
        }

        $temp = $this->selectedKeys[$index + 1];
        $this->selectedKeys[$index + 1] = $this->selectedKeys[$index];
        $this->selectedKeys[$index] = $temp;
        $this->selectedKeys = array_values($this->selectedKeys);
    }

    public function resetToDefaults(): void
    {
        $service = app(LeftRailMenuService::class);
        $availableKeys = array_keys($service->availableCustomizableItems());
        $this->selectedKeys = $service->sanitizeShortcutKeys($service->defaultShortcutKeys(), $availableKeys);
    }

    public function saveSettings(): void
    {
        $user = $this->getCurrentUser();
        if (! $user) {
            return;
        }

        $service = app(LeftRailMenuService::class);
        $sanitized = $service->sanitizeShortcutKeys($this->selectedKeys);

        if (Schema::hasColumn('users', 'left_rail_shortcuts')) {
            $user->forceFill(['left_rail_shortcuts' => $sanitized])->save();
        }

        $this->selectedKeys = $sanitized;
        $this->showSettingsModal = false;
        $this->dispatch('left-rail-shortcuts-updated');

        Notification::make()
            ->title('Navigasi Kiri Diperbarui')
            ->body('Susunan menu pintasan kiri Anda telah disimpan.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        $service = app(LeftRailMenuService::class);
        $user = $this->getCurrentUser();
        $railLinks = $service->resolveRailLinks($user, $this->currentUrl ?: url()->current());
        $availableItems = $service->availableCustomizableItems();
        $groupedItems = $service->availableGroupedByCategory();

        $query = strtolower(trim($this->search));
        if ($query !== '') {
            $filteredGroups = [];
            foreach ($groupedItems as $groupName => $items) {
                $matched = array_values(array_filter(
                    $items,
                    static fn (array $item): bool => str_contains(strtolower($item['label']), $query)
                        || str_contains(strtolower($groupName), $query),
                ));

                if ($matched !== []) {
                    $filteredGroups[$groupName] = $matched;
                }
            }
            $groupedItems = $filteredGroups;
        }

        return view('filament.livewire.left-icon-rail', [
            'railLinks' => $railLinks,
            'dashboardItem' => $service->resolveDashboardItem(),
            'availableItems' => $availableItems,
            'groupedItems' => $groupedItems,
            'maxShortcuts' => LeftRailMenuService::MAX_CUSTOM_SHORTCUTS,
        ]);
    }

    protected function getCurrentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
