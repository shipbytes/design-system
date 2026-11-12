<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests\Fixtures\Browser;

use Livewire\Component;

/**
 * The Livewire host the behaviour tests drive: otp-input, tag-input and chip-group
 * bound with wire:model, the way docs/components/otp-input.md and tag-input.md show.
 * Served by scripts/request.php as <livewire:binding-host />.
 */
final class BindingHost extends Component
{
    public string $code = '12';

    /** @var list<string> */
    public array $tags = ['maya@example.com'];

    /** @var list<string> */
    public array $filters = ['bug'];

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-ds::otp-input name="code" id="lw-otp" label="Code" wire:model="code" :value="$code" />
                <x-ds::tag-input name="tags" id="lw-tags" label="Tags" wire:model="tags" :value="$tags" />
                <x-ds::chip-group name="filters" id="lw-chips" label="Filters" :multiple="true" wire:model="filters">
                    <x-ds::chip value="billing" :checked="in_array('billing', $filters)">Billing</x-ds::chip>
                    <x-ds::chip value="bug" :checked="in_array('bug', $filters)">Bug</x-ds::chip>
                </x-ds::chip-group>
            </div>
            BLADE;
    }
}
