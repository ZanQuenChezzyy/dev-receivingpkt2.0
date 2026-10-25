<?php

namespace App\Livewire\Frontend;

use App\Models\Setting;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Welcome - Receiving Pupuk Kalimantan Timur v2.0')]
class Home extends Component
{
    public string $quickSearch = '';

    public function searchMaterial()
    {
        $search = trim($this->quickSearch);
        if ($search !== '') {
            return $this->redirect(route('frontend.list-material', ['search' => $search]), navigate: true);
        }

        return $this->redirect(route('frontend.list-material'), navigate: true);
    }

    public function render()
    {
        $aiSettingActive = false;
        try {
            $setting = Setting::where('key', 'ai_system_active')->first();
            $aiSettingActive = $setting ? $setting->value === '1' : false;
        } catch (\Throwable $e) {
            $aiSettingActive = false;
        }

        return view('livewire.frontend.home', [
            'aiSettingActive' => $aiSettingActive,
        ])->layout('components.layouts.frontend');
    }
}
