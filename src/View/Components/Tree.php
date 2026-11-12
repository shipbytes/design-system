<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * ds::tree — nested items, after the WAI-ARIA tree pattern. The markup is in
 * resources/views/components/tree.blade.php; this class exists for the reason
 * ButtonGroup does.
 *
 * Every ds::tree-item inside needs the tree's selection mode and field name
 * while it renders, through `@aware`. Under the plain names `selectable` and
 * `name`, a tree item would take the `name` of the nearest ancestor holding
 * one, whatever it is: a ds::accordion-item around the tree has a `name`, and
 * every leaf would have posted under it. `$dsTree` is a key no other
 * component has.
 */
final class Tree extends Component
{
    /**
     * What a ds::tree-item reads through `@aware(['dsTree'])`.
     *
     * @var array{select: string, name: ?string}
     */
    public array $dsTree;

    public function __construct(
        /** What the tree holds ("Files", "Documentation"). Its accessible name. */
        public string $label,
        /** none | single | multiple. multiple draws a checkbox on every item. */
        public string $selectable = 'none',
        /**
         * The field the selection posts as: one hidden input for single, one
         * per checked leaf (`name[]`) for multiple.
         */
        public ?string $name = null,
    ) {
        $this->selectable = in_array($selectable, ['single', 'multiple'], true) ? $selectable : 'none';
        $this->dsTree = [
            'select' => $this->selectable,
            'name' => $name,
        ];
    }

    public function render(): View
    {
        return view('ds::components.tree');
    }
}
