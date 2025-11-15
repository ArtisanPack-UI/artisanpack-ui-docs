<?php

namespace Modules\Packages\Livewire\Public;

use Livewire\Component;
use function Livewire\Volt\layout;
use Modules\Packages\Changelog as ChangelogModel;

#[Layout('core::layouts.app')]
class Changelog extends Component
{
    public ChangelogModel $changelog;
    public string $title = '';
    public string $content = '';

    public function mount(ChangelogModel $changelog) {
        $this->title = $changelog->title;
        $this->content = $changelog->content;
    }

    public function render()
    {
        return view('packages::livewire.public.changelog');
    }
}
