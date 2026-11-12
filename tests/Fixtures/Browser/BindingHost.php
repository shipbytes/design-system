<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests\Fixtures\Browser;

use Livewire\Component;

/**
 * The Livewire host the behaviour tests drive: otp-input and tag-input bound
 * with wire:model, the way docs/components/otp-input.md and tag-input.md show.
 * Served by scripts/request.php as <livewire:binding-host />.
 */
final class BindingHost extends Component
{
    public string $code = '12';

    /** @var list<string> */
    public array $tags = ['maya@example.com'];

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-ds::otp-input name="code" id="lw-otp" label="Code" wire:model="code" :value="$code" />
                <x-ds::tag-input name="tags" id="lw-tags" label="Tags" wire:model="tags" :value="$tags" />
            </div>
            BLADE;
    }
}
