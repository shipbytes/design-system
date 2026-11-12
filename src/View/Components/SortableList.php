<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * ds::sortable-list — rows the reader puts in order, by drag or by keyboard.
 * The markup is in resources/views/components/sortable-list.blade.php; this
 * class exists for the reason ButtonGroup does.
 *
 * Every ds::sortable-item needs the list's field name, whether it is
 * disabled and the words its buttons speak while it renders, through
 * `@aware`. Under the plain names `name` and `disabled` an item would take
 * them from the nearest ancestor holding one, whatever it is: a form
 * component with a `name` around the list, and every row would post under
 * it. `$dsSortable` is a key no other component has.
 */
final class SortableList extends Component
{
    /**
     * What a ds::sortable-item reads through `@aware(['dsSortable'])`.
     *
     * @var array{name: ?string, disabled: bool, handle: bool, id: string, up: string, down: string, grab: string}
     */
    public array $dsSortable;

    public string $id;

    public function __construct(
        /** What the rows are ("Checklist", "Invoice lines"). The list's accessible name. */
        public ?string $label = null,
        /** Posts the order as `name[]`, one hidden input per row, in the order shown. */
        public ?string $name = null,
        /**
         * Drag only by the handle (the default). false: a mouse or pen drags
         * from anywhere on the row that is not a control; touch still uses the
         * handle, so the page scrolls under a finger everywhere else.
         */
        public bool $handle = true,
        public bool $disabled = false,
        /** Read with each handle: how to move a row from the keyboard. */
        public string $instructions = 'Press Space or Enter to pick it up, the arrow keys to move it, Space or Enter to drop it, Escape to cancel.',
        /** Announced; :item, :position and :total are filled in. */
        public string $pickedText = 'Picked up :item, position :position of :total.',
        public string $movedText = 'Moved to position :position of :total.',
        public string $droppedText = 'Dropped :item at position :position of :total.',
        public string $cancelledText = 'Cancelled. :item is back at position :position of :total.',
        /** The handle's name; :item is the row's label. */
        public string $grabText = 'Reorder :item',
        /** The row's buttons, for a reader who cannot drag; :item is its label. */
        public string $moveUpText = 'Move :item up',
        public string $moveDownText = 'Move :item down',
    ) {
        $this->id = 'ds-sortable-'.substr(md5($label.'|'.$name), 0, 8);
        $this->dsSortable = [
            'name' => $name,
            'disabled' => $disabled,
            'handle' => $handle,
            'id' => $this->id,
            'grab' => $grabText,
            'up' => $moveUpText,
            'down' => $moveDownText,
        ];
    }

    public function render(): View
    {
        return view('ds::components.sortable-list');
    }
}
