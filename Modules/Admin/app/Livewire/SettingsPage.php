<?php

namespace Modules\Admin\Livewire;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Setting;
use Modules\Pages\Page;

#[Layout('admin::layouts.admin')]
class SettingsPage extends Component
{
    use Toast;
    public string | null $gitLabToken = '';

    public string | null $homePage = '';

    public function mount() {
        $settings = Setting::get();
        $this->gitLabToken = $settings->firstWhere('key', 'gitlab_token')?->value ?? '';
        $this->homePage = $settings->firstWhere('key', 'homePage')?->value ?? '';
    }

    public function save() {
        $validated = $this->validate([
            'gitLabToken' => 'nullable|string',
            'homePage' => 'nullable|string',
        ]);

       if ($validated) {
           Setting::updateOrCreate(
               ['key' => 'gitlab_token'],
               ['value' => $validated['gitLabToken']]
           );

           Setting::updateOrCreate(
             ['key' => 'homePage'],
             ['value' => $validated['homePage']]
           );
       }

       $this->success('Settings saved successfully', 'success');
    }

    #[Computed]
    public function pages() {
        return Page::all();
    }

    public function render()
    {
        return view('admin::livewire.settings-page');
    }
}
