<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests\Fixtures\Browser;

use Livewire\Component;

/**
 * The Livewire host the behaviour tests drive: otp-input, tag-input, chip-group,
 * radio-cards, segmented-control, toggle-group, slider, time-picker,
 * inline-edit, team-switcher, money-input, phone-input, shortcut-input, an
 * expanding search, mention-input, color-picker and a slider with a histogram
 * and number inputs bound with wire:model, the way docs/components/otp-input.md and tag-input.md show.
 * Served by scripts/request.php as <livewire:binding-host />.
 */
final class BindingHost extends Component
{
    public string $code = '12';

    /** @var list<string> */
    public array $tags = ['maya@example.com'];

    /** @var list<string> */
    public array $filters = ['bug'];

    public string $plan = 'free';

    public string $view = 'list';

    /** @var list<string> */
    public array $marks = ['bold'];

    public int|float $volume = 40;

    public string $opens = '09:30';

    public string $title = 'Q3 roadmap';

    public string $team = '2';

    public string $price = '19.99';

    public string $phone = '+447911123456';

    public string $keys = 'mod+k';

    public string $q = '';

    public string $comment = 'Hi';

    public string $brand = '#336699';

    /** @var list<int> */
    public array $nights = [60, 180];

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
                <x-ds::radio-cards name="plan" id="lw-plan" label="Plan" wire:model="plan">
                    <x-ds::radio-card value="free" title="Free" :checked="$plan === 'free'" />
                    <x-ds::radio-card value="pro" title="Pro" :checked="$plan === 'pro'" />
                </x-ds::radio-cards>
                <x-ds::segmented-control name="view" id="lw-view" label="View" wire:model="view">
                    <x-ds::segment value="list" :checked="$view === 'list'">List</x-ds::segment>
                    <x-ds::segment value="board" :checked="$view === 'board'">Board</x-ds::segment>
                </x-ds::segmented-control>
                <x-ds::toggle-group name="marks" id="lw-marks" label="Marks" type="multiple" wire:model="marks">
                    <x-ds::toggle value="bold" label="Bold" :pressed="in_array('bold', $marks)" />
                    <x-ds::toggle value="italic" label="Italic" :pressed="in_array('italic', $marks)" />
                </x-ds::toggle-group>
                <x-ds::slider name="volume" id="lw-slider" label="Volume" wire:model="volume" :value="$volume" />
                <x-ds::time-picker name="opens" id="lw-time" label="Opens" wire:model="opens" :value="$opens" />
                <x-ds::inline-edit name="title" id="lw-title" label="Title" wire:model="title" :value="$title" />
                <x-ds::team-switcher id="lw-team" name="team" wire:model="team" :current="$team"
                    :teams="[['id' => 1, 'name' => 'Acme Inc'], ['id' => 2, 'name' => 'Globex'], ['id' => 3, 'name' => 'Initech']]" />
                <x-ds::money-input id="lw-price" name="price" label="Price" wire:model="price" :value="$price" />
                <x-ds::phone-input id="lw-phone" name="phone" label="Phone" wire:model="phone" :value="$phone" :countries="['GB', 'US', 'IN']" />
                <x-ds::shortcut-input id="lw-keys" name="keys" label="Keys" wire:model="keys" :value="$keys" />
                <x-ds::input id="lw-q" name="q" label="Search" :expanding="true" wire:model="q" />
                <x-ds::mention-input id="lw-comment" name="comment" label="Comment" wire:model="comment" :value="$comment"
                    :items="[['id' => 7, 'label' => 'Maya Patel'], ['id' => 8, 'label' => 'Omar Malik']]" />
                <x-ds::color-picker id="lw-brand" name="brand" label="Brand" wire:model="brand" :value="$brand" />
                <x-ds::slider id="lw-nights" name="nights" label="Nights" :range="true" wire:model="nights" :value="$nights" :max="400" step="10"
                    lowLabel="From" highLabel="To" :histogram="[1, 4, 9, 6, 3, 2, 1, 1]" :inputs="true" />
            </div>
            BLADE;
    }
}
