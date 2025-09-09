<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Profile;

class ProfileSchemaGenerator extends Component
{


    /**
     * Create a new component instance.
     */
    public function __construct(public Profile $profile, public $settings) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.profile-schema-generator');
    }
}
