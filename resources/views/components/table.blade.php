@props([
    /**
     * Column headers. Each entry is a label, or an array:
     *   ['label' => 'Resumes', 'align' => 'center', 'width' => 'w-20']
     * An empty label renders a header cell with no text — for an actions column
     * that still needs its width reserved.
     *
     * A column the reader can sort by adds `sortable` and a `key`, the name
     * the server sorts by:
     *   ['label' => 'Total', 'key' => 'total', 'sortable' => true]
     * The key can also be the entry's own array key:
     *   ['total' => ['label' => 'Total', 'sortable' => true]]
     *
     * Omit entirely and supply your own `thead` in the slot.
     */
    'columns' => null,
    /** The key of the column the rows are sorted by, if any. Presentation only. */
    'sort' => null,
    /** asc | desc: which way that column is sorted. */
    'direction' => 'asc',
    /**
     * Where a sortable header links to, for server-side sorting. A pattern
     * with `{key}` and `{direction}` in it, or a closure taking both and
     * returning the URL. `direction` is the one a press should ask for: the
     * other way for the sorted column, ascending for the rest.
     *
     * Left out, the header links to the current URL with `sort` and
     * `direction` in its query string and `page` dropped, since a new order
     * starts at the first page.
     */
    'sortUrl' => null,
    /**
     * The `wire:click` for a sortable header instead of a link, for a
     * Livewire table: a pattern such as "sortBy('{key}')", or a closure
     * taking the key and direction. Wins over `sortUrl`.
     */
    'sortAction' => null,
])

@php
    $direction = $direction === 'desc' ? 'desc' : 'asc';

    $fill = function ($with, string $key, string $next, bool $url) {
        if ($with instanceof \Closure) {
            return (string) $with($key, $next);
        }

        return strtr((string) $with, [
            '{key}' => $url ? rawurlencode($key) : $key,
            '{direction}' => $next,
        ]);
    };

    $normalise = function ($col, $index = null) use ($sort, $direction, $sortUrl, $sortAction, $fill) {
        $col = is_array($col) ? $col : ['label' => $col];

        $label = $col['label'] ?? '';
        $sortable = (bool) ($col['sortable'] ?? false);
        $key = $col['key'] ?? (is_string($index) ? $index : null);

        if ($sortable && ($key === null || $key === '')) {
            throw new \InvalidArgumentException(
                "ds::table column \"{$label}\" is sortable but has no key.\n\n"
                ."The key is the name the server sorts by, and what `sort` is compared with. Give it one:\n\n"
                ."  ['label' => '{$label}', 'key' => 'total', 'sortable' => true]\n"
            );
        }

        $sorted = $sortable && $sort !== null && (string) $sort === (string) $key;
        // A press on the sorted column turns it round; on any other, it
        // starts ascending.
        $next = $sorted && $direction === 'asc' ? 'desc' : 'asc';

        $control = null;

        if ($sortable) {
            $align = $col['align'] ?? 'left';

            $classes = implode(' ', array_filter([
                'group/sort -mx-1 inline-flex cursor-pointer items-center gap-1 rounded-chip px-1 uppercase hover:text-fg',
                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
                // Right-aligned figures: the arrow goes on the inside, so the
                // label's edge stays on the figures' edge.
                $align === 'right' ? 'flex-row-reverse' : null,
                $sorted ? 'text-fg' : null,
            ]));

            // Escaped here: an attribute bag built in PHP prints values as
            // they are.
            $control = $sortAction !== null
                ? ['tag' => 'button', 'attributes' => new \Illuminate\View\ComponentAttributeBag([
                    'type' => 'button',
                    'wire:click' => e($fill($sortAction, (string) $key, $next, false)),
                    'class' => $classes,
                ])]
                : ['tag' => 'a', 'attributes' => new \Illuminate\View\ComponentAttributeBag([
                    'href' => e($sortUrl !== null
                        ? $fill($sortUrl, (string) $key, $next, true)
                        : request()->fullUrlWithQuery(['sort' => $key, 'direction' => $next, 'page' => null])),
                    'class' => $classes,
                ])];

            $control['icon'] = match (true) {
                $sorted && $direction === 'asc' => 'chevron-up',
                $sorted => 'chevron-down',
                default => 'chevron-up-down',
            };

            // Sorted: the arrow is the state, always shown. Sortable but not
            // sorted: a muted affordance on hover and keyboard focus only,
            // its space kept so the label does not move when it appears.
            $control['iconClass'] = $sorted
                ? 'text-fg'
                : 'text-fg-subtle opacity-0 group-hover/sort:opacity-100 group-focus-visible/sort:opacity-100 motion-safe:transition-opacity motion-safe:duration-hint-enter motion-safe:ease-enter';
        }

        return [
            'label' => $label,
            'align' => $col['align'] ?? 'left',
            'width' => $col['width'] ?? null,
            'control' => $control,
            // Echoed straight onto the th, so a column that is not sorted
            // renders exactly what it did before sorting existed. One of two
            // literals, never input.
            'ariaSort' => $sorted ? ($direction === 'asc' ? ' aria-sort="ascending"' : ' aria-sort="descending"') : '',
        ];
    };
@endphp

{{--
    The scroll container is the point: a wide table scrolls inside its own
    rounded box rather than pushing the page sideways. Without it a single long
    cell makes the whole layout scroll, which on a phone is indistinguishable
    from a broken page.
--}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-control border border-border']) }}>
    <table class="w-full divide-y divide-divider">
        @if ($columns)
            <thead class="bg-surface-subtle">
                <tr>
                    @foreach ($columns as $index => $col)
                        @php $c = $normalise($col, $index); @endphp
                        <th
                            scope="col"{!! $c['ariaSort'] !!}
                            @class([
                                'px-4 py-3 text-overline uppercase text-fg-muted',
                                'text-left' => $c['align'] === 'left',
                                'text-center' => $c['align'] === 'center',
                                'text-right' => $c['align'] === 'right',
                                $c['width'] => (bool) $c['width'],
                            ])
                        >@if ($c['control'])<{{ $c['control']['tag'] }} {{ $c['control']['attributes'] }}><span>{{ $c['label'] }}</span><x-ds::icon :name="$c['control']['icon']" variant="mini" size="4" :class="$c['control']['iconClass']" /></{{ $c['control']['tag'] }}>@else{{ $c['label'] }}@endif</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="divide-y divide-divider">
            {{ $slot }}
        </tbody>
    </table>
</div>
