@aware([
    /*
     * The tree's selection mode and field name, from the ds::tree class: a
     * key no other component has, for the reason given in that class.
     */
    'dsTree' => null,
])

@props([
    /** The item's words. */
    'label',
    /** A Heroicons name, drawn before the words. */
    'icon' => null,
    /** Makes the item a link: a navigation tree. Every item in a selectable tree is not one. */
    'href' => null,
    /**
     * Whether its children show. Left out, it is open exactly when it holds
     * the current page (a navigation tree) or the selected item (single
     * selection), so the reader lands with that item in view.
     */
    'open' => null,
    /**
     * Selected, in a selectable tree; checked, with its leaves, in a checkbox
     * tree; the current page, in a navigation tree (aria-current="page").
     */
    'selected' => false,
    /** Cannot be selected, checked or followed. It can still be focused and opened, so what is under it stays reachable. */
    'disabled' => false,
    /** What the item posts when selected, and what its `expand` and `collapse` events carry. Left out: the label. */
    'value' => null,
    /** A number after the words: how many things are in it. */
    'count' => null,
    /**
     * Drawn as a folder, chevron and all, with nothing in it yet: for
     * children a host loads itself when it hears `expand`.
     */
    'expandable' => false,
])

@php
    use Shipbytes\BladeUi\Support\Tree;

    $tree = is_array($dsTree) ? $dsTree : ['select' => 'none', 'name' => null];
    $mode = $tree['select'];
    $field = $tree['name'];

    $link = $href !== null;
    if ($link && $mode !== 'none') {
        throw new \InvalidArgumentException(
            "ds::tree-item \"{$label}\" has an `href` in a tree that is selectable. A tree is navigation (links, "
            .'the current page marked) or a choice (posted with a form), not both.'
        );
    }

    $value = (string) ($value ?? $label);
    $children = (string) $slot;
    $hasChildren = str_contains($children, 'data-ds-tree-item');
    $parent = $hasChildren || $expandable;

    // A checked parent checks what is under it, before it reads its own box
    // off those leaves.
    if ($mode === 'multiple' && $hasChildren && $selected && ! $disabled) {
        $children = Tree::checkAll($children);
    }

    // A parent's box as its own leaves have it; the tree's root settles it
    // again for the whole tree (Support\Tree::derive), after any checked
    // ancestor has had its say.
    $checked = null;
    $leaf = false;
    if ($mode === 'multiple') {
        $checked = $hasChildren ? Tree::checked($children) : null;
        $leaf = $checked === null;
        $checked ??= $selected ? 'true' : 'false';
    }

    $current = $link && $selected;
    if ($parent) {
        $open = $open === null
            ? str_contains($children, 'aria-current="page"') || ($mode === 'single' && str_contains($children, 'aria-selected="true"'))
            : (bool) $open;
    }

    if ($hasChildren) {
        $children = Tree::nest($children);
    }

    /*
     * The row's attributes as one string in a fixed order, because the
     * parent, the tree and Support\Tree find a row by them: the value, then
     * level, setsize and posinset together, then the selection state. Every
     * row is level 1, one of one, here; its parent renumbers it.
     */
    $row = 'role="treeitem" data-ds-tree-item data-ds-tree-value="'.e($value).'" aria-level="1" aria-setsize="1" aria-posinset="1"';
    if ($mode === 'single') {
        $row .= ' aria-selected="'.($selected && ! $disabled ? 'true' : 'false').'"';
    } elseif ($mode === 'multiple') {
        $row .= ' data-ds-tree-check="'.($leaf ? ($disabled ? 'leaf-disabled' : 'leaf') : 'parent').'" aria-checked="'.$checked.'"';
    }
    if ($parent) {
        $row .= ' aria-expanded="'.($open ? 'true' : 'false').'"';
    }
    if ($link) {
        $row .= $disabled ? '' : ' href="'.e($href).'"';
        $row .= $current ? ' aria-current="page"' : '';
    } else {
        // One tab stop for the tree, set by the root before the script and
        // kept by it after. Links are left alone: without the script they
        // are each reachable by Tab, which is the safe way to fail.
        $row .= ' tabindex="-1"';
    }
    if ($disabled) {
        $row .= ' aria-disabled="true"';
    }

    $tag = $link && ! $disabled ? 'a' : 'div';

    $classes = implode(' ', [
        'group/item flex min-h-8 w-full items-center gap-1.5 rounded-control px-1.5 py-1 text-start text-body text-fg-body select-none',
        'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring',
        // Selected and current share a look, and say it with weight as well
        // as colour.
        'aria-selected:bg-accent-tint aria-selected:font-medium aria-selected:text-on-accent-tint',
        'aria-[current=page]:bg-accent-tint aria-[current=page]:font-medium aria-[current=page]:text-on-accent-tint',
        $disabled ? 'cursor-not-allowed text-fg-subtle' : 'cursor-pointer hover:bg-fg/5 hover:text-fg',
    ]);

    // Turned by aria-expanded on the row, so the PHP value and the script's
    // draw the same chevron. Pointing along the line when closed, and the
    // other way on a right-to-left page.
    $chevron = '-rotate-90 rtl:rotate-90 group-aria-expanded/item:rotate-0 motion-safe:transition-transform motion-safe:duration-popover-enter motion-safe:ease-enter';
@endphp

<li role="none" data-ds-tree-node>
    <{{ $tag }} {!! $row !!} class="{{ $classes }}">
        @if ($parent)
            <span data-ds-tree-toggle aria-hidden="true" class="flex size-5 shrink-0 items-center justify-center rounded-chip text-fg-muted hover:bg-fg/5 hover:text-fg">
                <x-ds::icon name="chevron-down" variant="micro" size="4" :class="$chevron" />
            </span>
        @else
            <span class="size-5 shrink-0" aria-hidden="true"></span>
        @endif

        @if ($mode === 'multiple')
            <span aria-hidden="true" @class([
                'grid size-4 shrink-0 place-items-center rounded-chip border text-on-accent',
                'border-border-strong bg-surface group-aria-checked/item:border-accent group-aria-checked/item:bg-accent group-aria-[checked=mixed]/item:border-accent group-aria-[checked=mixed]/item:bg-accent' => ! $disabled,
                'border-border bg-surface-subtle group-aria-checked/item:bg-fg-subtle group-aria-[checked=mixed]/item:bg-fg-subtle' => $disabled,
            ])>
                <svg class="col-start-1 row-start-1 size-3 opacity-0 group-aria-checked/item:opacity-100" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6.5 5 9l4.5-5.5" /></svg>
                <svg class="col-start-1 row-start-1 size-3 opacity-0 group-aria-[checked=mixed]/item:opacity-100" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2.5 6h7" /></svg>
            </span>
        @endif

        @if ($icon)
            <x-ds::icon :name="$icon" size="4" class="shrink-0 text-fg-muted group-aria-selected/item:text-current group-aria-[current=page]/item:text-current" />
        @endif

        <span data-ds-tree-label class="min-w-0 flex-1 truncate">{{ $label }}</span>

        @isset($badge)
            <span class="ms-auto flex shrink-0 items-center">{{ $badge }}</span>
        @elseif ($count !== null)
            <span class="ms-auto shrink-0 text-meta tabular-nums text-fg-muted group-aria-selected/item:text-current group-aria-[current=page]/item:text-current">{{ $count }}</span>
        @endisset
    </{{ $tag }}>

    @if ($mode === 'multiple' && $leaf && ! $disabled && $field)
        {{-- What a checked leaf posts; `disabled` while unchecked, so it does
             not. The script flips it; a checked parent's PHP does too, which
             finds it by exactly this string, so it is written as one. --}}
        {!! '<input type="hidden" name="'.e($field).'[]" value="'.e($value).'" data-ds-tree-input'.($checked === 'true' ? '' : ' disabled').'>' !!}
    @endif

    @if ($parent)
        <ul role="group" data-ds-tree-group class="ms-4 flex flex-col gap-px border-s border-divider ps-1" @if (! $open) hidden @endif>{{ new \Illuminate\Support\HtmlString($children) }}</ul>
    @endif
</li>
