<?php

namespace Modules\Core\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('core::layouts.app')]
class HomePage extends Component
{
    public function render()
    {
        return view('core::livewire.home-page');
    }
}
