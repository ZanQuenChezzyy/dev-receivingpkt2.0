<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class AiSystemToggle extends Component
{
    public bool $isActive = false;

    public function mount()
    {
        $setting = Setting::firstOrCreate(
            ['key' => 'ai_system_active'],
            ['value' => '0']
        );
        $this->isActive = $setting->value === '1';
    }

    public function toggle()
    {
        if (!Auth::user()?->hasRole('Developer')) {
            return;
        }

        $this->isActive = !$this->isActive;
        
        Setting::updateOrCreate(
            ['key' => 'ai_system_active'],
            ['value' => $this->isActive ? '1' : '0']
        );
    }

    public function render()
    {
        return view('livewire.ai-system-toggle');
    }
}
