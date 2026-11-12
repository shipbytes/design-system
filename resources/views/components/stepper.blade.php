@props([
    /** horizontal | vertical */
    'orientation' => 'horizontal',
    /** Names the list for a screen reader: "Checkout progress". */
    'label' => null,
])

@php
    $vertical = $orientation === 'vertical';

    /*
     * Below 40rem a horizontal stepper prints "Step 2 of 4" and the current
     * title under its row of markers. That needs the count and the position,
     * which only the steps know, and they have rendered by the time this runs:
     * the slot is finished HTML. So they are read back from the data attributes
     * each step writes on its list item. Only ours carry them.
     */
    $summary = null;

    if (! $vertical) {
        preg_match_all('/<li\b[^>]*\bdata-ds-step="([a-z]+)"[^>]*>/', (string) $slot, $items);

        $steps = collect($items[0])->map(function (string $tag, int $i) use ($items) {
            preg_match('/\bdata-ds-step-title="([^"]*)"/', $tag, $title);

            return [
                'status' => $items[1][$i],
                'title' => html_entity_decode($title[1] ?? '', ENT_QUOTES | ENT_HTML5),
            ];
        });

        $total = $steps->count();
        $at = $steps->search(fn ($s) => $s['status'] === 'current');
        $at = $at === false ? $steps->search(fn ($s) => $s['status'] === 'error') : $at;

        if ($total > 0) {
            $done = $steps->where('status', 'complete')->count();

            $summary = $at !== false
                ? ['line' => 'Step '.($at + 1).' of '.$total, 'title' => $steps[$at]['title']]
                : ['line' => $done === $total ? "All {$total} steps complete" : "{$done} of {$total} steps complete", 'title' => null];
        }
    }

    $listAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'aria-label' => $label,
        'class' => $vertical
            ? 'flex flex-col [counter-reset:ds-step]'
            : 'flex items-start gap-3 [counter-reset:ds-step]',
    ]));
@endphp

{{--
    A container, so the collapse below 40rem follows the space the stepper is
    given rather than the viewport: a four-step flow inside a narrow modal on a
    desktop collapses too, and one in a wide panel on a tablet does not.
--}}
<div {{ $attributes->class(['@container w-full']) }}>
    <ol {{ $listAttributes }}>
        {{ $slot }}
    </ol>

    @if ($summary)
        {{-- What the row of markers no longer says in words. Hidden from
             assistive technology, which still has every title in the list. --}}
        <div class="mt-3 @min-[40rem]:hidden" aria-hidden="true">
            <p class="text-meta text-fg-muted">{{ $summary['line'] }}</p>
            @if ($summary['title'] !== null)
                <p class="text-section text-fg">{{ $summary['title'] }}</p>
            @endif
        </div>
    @endif
</div>
