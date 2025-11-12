<?php

namespace Modules\Admin\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin::layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        return view('admin::livewire.dashboard');
    }
}
