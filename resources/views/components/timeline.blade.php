@props([
    /** Names the list for a screen reader: "Order history". */
    'label' => null,
    /** Tighter: smaller markers and less space between entries, for a side panel or a long log. */
    'dense' => false,
])

{{--
    An ordered list, because a timeline's order is its meaning. The items read
    `dense` from data-dense on this list through the named group, not through
    Blade's aware directive: a Livewire component or a partial that renders the items in its
    own view still sits inside the list in the DOM, where CSS finds it, but
    not inside this component in Blade, where that directive would look.
--}}
<ol {{ $attributes->class(['group/timeline flex flex-col'])->merge(array_filter([
    'aria-label' => $label !== null && trim((string) $label) !== '' ? trim((string) $label) : null,
    'data-dense' => $dense ? '' : null,
], fn ($v) => $v !== null)) }}>
    {{ $slot }}
</ol>
