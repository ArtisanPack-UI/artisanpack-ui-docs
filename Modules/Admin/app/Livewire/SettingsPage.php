<?php

namespace Modules\Admin\Livewire;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Setting;

#[Layout('admin::layouts.admin')]
class SettingsPage extends Component
{
    use Toast;
    public string | null $gitLabToken = '';

    public function mount() {
        $settings = Setting::get();
        $this->gitLabToken = $settings->firstWhere('key', 'gitlab_token')?->value ?? '';
    }

    public function save() {
        $validated = $this->validate([
            'gitLabToken' => 'nullable|string'
        ]);

       if ($validated) {
           Setting::updateOrCreate(
               ['key' => 'gitlab_token'],
               ['value' => $validated['gitLabToken']]
           );
       }

       $this->success('Settings saved successfully', 'success');
    }

    public function render()
    {
        return view('admin::livewire.settings-page');
    }
}
