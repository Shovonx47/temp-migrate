<?php

namespace Pest\Livewire;

use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

if (! function_exists('Pest\Livewire\livewire')) {
    function livewire(string $component, array $params = []): Testable
    {
        return Livewire::test($component, $params);
    }
}
