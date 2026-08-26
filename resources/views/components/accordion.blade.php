@props([
    /** Allow more than one section open at a time. */
    'multiple' => false,
    /**
     * The section open on first paint — an accordion-item's `name`, or an array
     * of them when `multiple`. Null opens nothing.
     */
    'open' => null,
])

@php
    /*
     * The accordion owns which section is open, and nothing else.
     *
     * That is a deliberate exception to "components hold no state": exclusivity
     * is a relationship BETWEEN the items, so no single item can enforce it and
     * pushing it to the host would mean every consumer writing the same toggle
     * logic. The state is one key; it is not application state.
     */
    $initial = $multiple ? json_encode(array_values((array) ($open ?? []))) : json_encode($open);

    /*
     * There is no `multiple` flag in the scope: whether `open` is an ARRAY
     * already carries it, and one source of truth cannot disagree with itself.
     * A PHP bool interpolated into JS is also a trap — it renders as "1" or as
     * nothing at all, and the nothing is a syntax error.
     */
    $scope = <<<JS
        {
            open: {$initial},
            isOpen(key) {
                return Array.isArray(this.open) ? this.open.includes(key) : this.open === key;
            },
            toggle(key) {
                if (Array.isArray(this.open)) {
                    this.open = this.isOpen(key) ? this.open.filter((k) => k !== key) : [...this.open, key];
                } else {
                    this.open = this.isOpen(key) ? null : key;
                }
            },
        }
    JS;
@endphp

<div
    x-data="{{ $scope }}"
    {{ $attributes->merge([
        'class' => 'divide-y divide-divider overflow-hidden rounded-control border border-border bg-surface text-fg-body',
    ]) }}
>
    {{ $slot }}
</div>
