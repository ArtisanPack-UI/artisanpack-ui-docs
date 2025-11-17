<?php

namespace Modules\Packages\Livewire\Admin;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class Packages extends Component
{
    public array $headers = [];

    public function mount() {
        $this->headers = [
            [
                'key' => 'name',
                'label' => 'Name',
            ],
            [
                'key' => 'version',
                'label' => 'Version',
            ]
        ];
    }

    #[Computed]
    public function packages() {
        return Package::all();
    }

    public function delete(Package $package) {
        $package->delete();
    }

    public function render()
    {
        return view('packages::livewire.admin.packages');
    }
}
