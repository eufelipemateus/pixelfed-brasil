<?php

namespace App\View\Components;

use App\Models\Profile;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProfileSchemaGenerator extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public Profile $profile, public bool $indexable) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.profile-schema-generator');
    }
}
