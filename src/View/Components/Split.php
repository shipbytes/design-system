<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * ds::split — panes side by side or stacked, with a divider between each
 * pair the reader can drag. The markup is in
 * resources/views/components/split.blade.php; this class exists for the
 * reason ButtonGroup does.
 *
 * Every ds::split-pane draws its size, its limits and the divider after it
 * along the split's axis, so it needs the split's orientation while it
 * renders, through `@aware`. Under the plain name `orientation` a pane would
 * take it from the nearest ancestor holding one: a ds::button-group or a
 * ds::toolbar around a nested split. `$dsSplit` is a key no other component
 * has, and a nested split's panes find their own split's first.
 */
final class Split extends Component
{
    /**
     * What a ds::split-pane reads through `@aware(['dsSplit'])`.
     *
     * @var array{orientation: string, stackBelow: ?string, label: string}
     */
    public array $dsSplit;

    public function __construct(
        /** horizontal: panes side by side, the divider upright. vertical: panes stacked. */
        public string $orientation = 'horizontal',
        /** Handed back in the `resize` and `resized` events, so a host can tell its splits apart. */
        public ?string $name = null,
        /** Each divider's accessible name. */
        public string $label = 'Resize',
        /**
         * sm | md | lg | xl: below that breakpoint the panes stack at their
         * natural height and the dividers go. Left out, never.
         */
        public ?string $stackBelow = null,
    ) {
        $this->orientation = $orientation === 'vertical' ? 'vertical' : 'horizontal';
        $this->stackBelow = in_array($stackBelow, ['sm', 'md', 'lg', 'xl'], true) ? $stackBelow : null;
        $this->dsSplit = [
            'orientation' => $this->orientation,
            'stackBelow' => $this->stackBelow,
            'label' => $label,
        ];
    }

    public function render(): View
    {
        return view('ds::components.split');
    }
}
