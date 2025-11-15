<?php

namespace Modules\Packages\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Documentation as DocumentationModel;

#[Layout('core::layouts.app')]
class Documentation extends Component
{
    public DocumentationModel $documentation;
    public string $title = '';
    public string $content = '';

    public function mount(DocumentationModel $documentation) {
        $this->documentation = $documentation;
        $this->title = $documentation->title;
        $this->content = $documentation->content;
    }

    public function render()
    {
        return view('packages::livewire.public.documentation');
    }
}
