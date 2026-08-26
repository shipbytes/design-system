@props([
    /**
     * Column headers. Each entry is a label, or an array:
     *   ['label' => 'Resumes', 'align' => 'center', 'width' => 'w-20']
     * An empty label renders a header cell with no text — for an actions column
     * that still needs its width reserved.
     *
     * Omit entirely and supply your own <thead> in the slot.
     */
    'columns' => null,
])

@php
    $normalise = function ($col) {
        $col = is_array($col) ? $col : ['label' => $col];

        return [
            'label' => $col['label'] ?? '',
            'align' => $col['align'] ?? 'left',
            'width' => $col['width'] ?? null,
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
                    @foreach ($columns as $col)
                        @php $c = $normalise($col); @endphp
                        <th
                            scope="col"
                            @class([
                                'px-4 py-3 text-overline uppercase text-fg-muted',
                                'text-left' => $c['align'] === 'left',
                                'text-center' => $c['align'] === 'center',
                                'text-right' => $c['align'] === 'right',
                                $c['width'] => (bool) $c['width'],
                            ])
                        >{{ $c['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="divide-y divide-divider">
            {{ $slot }}
        </tbody>
    </table>
</div>
