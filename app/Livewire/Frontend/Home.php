<?php

namespace App\Livewire\Frontend;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Welcome - Receiving PKT')]
class Home extends Component
{
    public function render()
    {
        return view('livewire.frontend.home')->layout('components.layouts.frontend');
    }
}
