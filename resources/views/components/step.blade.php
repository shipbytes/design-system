@aware(['orientation' => 'horizontal'])

@props([
    /** complete | current | upcoming | error */
    'status' => 'upcoming',
    /** The step's name. */
    'title',
    /** One line under the title. */
    'description' => null,
    /** Lets a finished step be revisited. Ignored on the current step, which is where the reader already is. */
    'href' => null,
])

@php
    $status = in_array($status, ['complete', 'current', 'upcoming', 'error'], true) ? $status : 'upcoming';
    $vertical = $orientation === 'vertical';
    $href = $status === 'current' ? null : $href;

    /*
     * Said in words for a screen reader, because the only other carriers are a
     * colour and a glyph. The current step also gets aria-current="step", which
     * not every reader announces on a list item, so it is spelled out as well.
     */
    $spoken = [
        'complete' => 'completed',
        'current' => 'current step',
        'upcoming' => 'not started',
        'error' => 'needs attention',
    ][$status];

    // The marker. The NUMBER comes from a CSS counter on the list, so steps
    // number themselves in order without being told their position.
    $markers = [
        'complete' => 'bg-surface-inverse text-on-inverse',
        'current' => 'border-2 border-fg bg-surface text-fg',
        'upcoming' => 'border border-border-strong bg-surface text-fg-muted',
        'error' => 'bg-danger text-on-danger',
    ];

    // A finished step's connector is drawn solid: the line behind the reader is
    // the part already travelled.
    $line = $status === 'complete' ? 'bg-fg' : 'bg-border-strong';

    $titleClasses = [
        'complete' => 'font-medium text-fg',
        'current' => 'font-semibold text-fg',
        'upcoming' => 'font-medium text-fg-muted',
        'error' => 'font-medium text-fg',
    ][$status];

    /*
     * Horizontal and narrower than 40rem (the sm breakpoint, measured on the
     * stepper rather than the viewport): the titles leave the screen but not the
     * accessibility tree, and the stepper prints "Step 2 of 4" and the current
     * title under the row instead. sr-only rather than hidden, so the list still
     * reads in full.
     */
    $textClasses = $vertical
        ? 'flex min-w-0 flex-col pt-0.5'
        : 'sr-only @min-[40rem]:not-sr-only @min-[40rem]:flex @min-[40rem]:min-w-0 @min-[40rem]:flex-col @min-[40rem]:pt-0.5';

    $itemAttributes = $attributes
        ->class([
            'group/step relative [counter-increment:ds-step]',
            'flex min-w-0 flex-1 items-start gap-3 last:flex-none' => ! $vertical,
            'pb-6 last:pb-0' => $vertical,
        ])
        ->merge(array_filter([
            'data-ds-step' => $status,
            'data-ds-step-title' => $title,
            'aria-current' => $status === 'current' ? 'step' : null,
        ], fn ($v) => $v !== null));

    /*
     * The marker and the text sit in ONE element, so a step with an href is one
     * link the size of both, and below 40rem, when the text is visually gone,
     * the link and its focus ring are still the marker everyone can see.
     *
     * Always an anchor: without href it is a plain inline element with no role
     * and no tab stop, which is exactly a non-link. Choosing the tag in the
     * template would put an @if inside the opening tag, which Livewire's markers
     * get wrong (see CLAUDE.md), so the attributes are built here instead.
     */
    $linkAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'href' => $href,
        'class' => implode(' ', array_filter([
            'flex min-w-0 items-start gap-3 rounded-control',
            $vertical ? null : 'shrink-0 @min-[40rem]:shrink',
            $href ? 'group/link outline-offset-2 focus-visible:outline-2 focus-visible:outline-focus-ring' : null,
        ])),
    ]));
@endphp

<li {{ $itemAttributes }}>
    @if ($vertical)
        {{-- The connector runs from under this marker to the next one. Absolute,
             so a long description stretches it rather than the other way round. --}}
        <span class="absolute top-9 bottom-1 left-3.5 w-px -translate-x-1/2 group-last/step:hidden {{ $line }}" aria-hidden="true"></span>
    @endif

    <a {{ $linkAttributes }}>
        <span @class([
            'flex size-7 shrink-0 items-center justify-center rounded-full text-meta font-semibold tabular-nums',
            $markers[$status],
            'before:content-[counter(ds-step)]' => in_array($status, ['current', 'upcoming'], true),
        ]) aria-hidden="true">
            @if ($status === 'complete')
                <x-ds::icon name="check" variant="mini" size="4" />
            @elseif ($status === 'error')
                <span>!</span>
            @endif
        </span>

        <span class="{{ $textClasses }}">
            <span @class([$titleClasses, 'text-body', 'group-hover/link:underline' => $href])>{{ $title }}<span class="sr-only">, {{ $spoken }}</span></span>
            @if ($description)
                <span class="text-meta text-fg-muted">{{ $description }}</span>
            @endif
        </span>
    </a>

    @unless ($vertical)
        {{-- mt-3.5 is half the 28px marker, so the line meets it at its centre
             however many lines the title and description run to. --}}
        <span class="mt-3.5 h-px min-w-4 flex-1 group-last/step:hidden {{ $line }}" aria-hidden="true"></span>
    @endunless
</li>
