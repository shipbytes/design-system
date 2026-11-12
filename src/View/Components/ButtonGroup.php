<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * ds::button-group — related buttons joined into one surface. The markup and
 * every class are in resources/views/components/button-group.blade.php; this
 * class exists for one reason, and it is not visual.
 *
 * The buttons inside take their `size` and `variant` from the group through
 * `@aware`. An anonymous parent can only offer `@aware` what its caller wrote
 * on its tag, so the button would have had to read the group's `size` under
 * that plain name, and `@aware` hands a child the nearest ancestor holding a
 * key, whichever component it is. A ds::button in the footer of
 * `ds::modal size="lg"`, or in any host component with a `size` prop, would
 * have turned large. The 1.x buttons never read `@aware`, so that would have
 * been a silent change to every existing page.
 *
 * A class can offer a key nobody writes. `$buttonGroup` is that key: only this
 * component has it, so a button sees group settings inside a group and nothing
 * anywhere else. ButtonGroupTest holds both halves.
 */
final class ButtonGroup extends Component
{
    /**
     * What a ds::button inside reads through `@aware(['buttonGroup'])`:
     * the size and variant to fall back on, and which way the joins run.
     *
     * @var array{size: ?string, variant: string, orientation: string, expanding: bool}
     */
    public array $buttonGroup;

    public function __construct(
        /** What the buttons do together ("Text alignment", "Save options"). The group's accessible name. */
        public string $label,
        /** sm | md | lg, for every button that does not set its own. Left out, each button's default. */
        public ?string $size = null,
        /** primary | secondary | ghost | danger, for every button that does not set its own. */
        public string $variant = 'secondary',
        /** horizontal | vertical */
        public string $orientation = 'horizontal',
        /**
         * Icon buttons whose labels fold away, the hovered, focused, pressed
         * or current one showing its own. Every button needs an `icon`. The
         * group fills the width it is given and the labels open inside it,
         * so nothing beside the group moves. Horizontal only.
         */
        public bool $expanding = false,
    ) {
        $this->orientation = $orientation === 'vertical' ? 'vertical' : 'horizontal';

        // A column whose buttons grow sideways would widen the column, and
        // move whatever sits beside it, which is the one thing this mode
        // promises not to do.
        if ($expanding && $this->orientation === 'vertical') {
            throw new \InvalidArgumentException(
                'ds::button-group cannot be both `expanding` and vertical: a label opening sideways would widen the column. Use a horizontal group, or a vertical one whose buttons show their labels.'
            );
        }

        $this->buttonGroup = [
            'size' => $size,
            'variant' => $variant,
            'orientation' => $this->orientation,
            'expanding' => $expanding,
        ];
    }

    public function render(): View
    {
        return view('ds::components.button-group');
    }
}
