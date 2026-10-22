<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests\Fixtures;

use Livewire\Component;

/**
 * A throwaway Livewire component whose view is one specimen. Livewire only
 * injects its morph markers while it is rendering a component of its own, so a
 * plain Blade::render with Livewire installed would show nothing.
 */
final class SpecimenComponent extends Component
{
    public string $blade = '';

    public function render(): string
    {
        // One root element, which Livewire requires of every component.
        return '<div>'.$this->blade.'</div>';
    }
}
