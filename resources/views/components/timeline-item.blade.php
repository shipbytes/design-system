@props([
    /** When it happened: a DateTimeInterface, an ISO 8601 string, or words ("Yesterday") shown as written. */
    'time' => null,
    /** The machine-readable moment for a `time` given in words, written to the time element's datetime. */
    'datetime' => null,
    /** For a date: "relative" ("3 hours ago", the full date as its title), or a PHP date format. Default: the locale's medium date and time. */
    'timeFormat' => null,
    /** What happened, in one line: "Ada approved the invoice". */
    'title' => null,
    /** Makes the title a link to the thing it is about. */
    'href' => null,
    /** A Heroicon name for the marker. */
    'icon' => null,
    /** neutral | accent | success | warning | danger — the marker's colour. The title still says what happened. */
    'tone' => 'neutral',
    /** A person as the marker: their name, or ds::avatar's props as an array (['name' => …, 'src' => …]). */
    'avatar' => null,
])

@use('Illuminate\Support\Carbon')

@php
    $tone = in_array($tone, ['neutral', 'accent', 'success', 'warning', 'danger'], true) ? $tone : 'neutral';

    /*
     * A moment is a time element: the words a reader sees, and the value a
     * machine reads. A date object or an ISO 8601 string is a moment and is
     * formatted here; anything else is the host's own words, shown as
     * written, a moment only when `datetime` says which.
     */
    $moment = null;
    if ($time instanceof \DateTimeInterface) {
        $moment = Carbon::instance($time);
    } elseif (is_string($time) && preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?)?$/', trim($time))) {
        $moment = Carbon::parse(trim($time));
    }

    $when = null;
    if ($moment) {
        $dateOnly = is_string($time) && strlen(trim($time)) === 10;
        $full = $dateOnly ? $moment->isoFormat('LL') : $moment->isoFormat('LLL');
        $when = [
            'datetime' => $dateOnly ? $moment->format('Y-m-d') : $moment->toAtomString(),
            'text' => match (true) {
                $timeFormat === 'relative' => $moment->diffForHumans(),
                is_string($timeFormat) && $timeFormat !== '' => $moment->translatedFormat($timeFormat),
                default => $full,
            },
            // A relative time goes stale on a page left open; the full one
            // is a hover away.
            'title' => $timeFormat === 'relative' ? $full : null,
        ];
    } elseif ($time !== null && trim((string) $time) !== '') {
        $when = [
            'datetime' => $datetime instanceof \DateTimeInterface ? Carbon::instance($datetime)->toAtomString() : ($datetime !== null ? (string) $datetime : null),
            'text' => trim((string) $time),
            'title' => null,
        ];
    }

    $person = is_array($avatar) ? $avatar : ($avatar !== null && trim((string) $avatar) !== '' ? ['name' => (string) $avatar] : null);

    // Literal, for the scanner. The marker is a tint with its icon, or a dot.
    $tints = [
        'neutral' => 'bg-neutral-tint text-on-neutral-tint',
        'accent' => 'bg-accent-tint text-on-accent-tint',
        'success' => 'bg-success-tint text-on-success-tint',
        'warning' => 'bg-warning-tint text-on-warning-tint',
        'danger' => 'bg-danger-tint text-on-danger-tint',
    ];
    $dots = [
        'neutral' => 'bg-fg-muted',
        'accent' => 'bg-accent',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
    ];

    $hasBody = trim((string) $slot) !== '';
@endphp

{{--
    The marker column is 32px (24px dense). Each item draws its own piece of
    the rail, top to bottom of its box, behind its marker: from its marker
    down for the first, up to its marker for the last, none for one alone.
    So the rail runs unbroken from the first marker to the last and never
    past it, and an entry of any length stretches it: the body wraps in its
    column and the rail goes on beside it. A marker is cut out of the rail by
    a ring of the background, the same --ds-timeline-bg a group's heading uses.
--}}
<li {{ $attributes->class(['group/item relative flex gap-3 pb-6 last:pb-0 group-data-dense/timeline:gap-2.5 group-data-dense/timeline:pb-4 group-data-dense/timeline:last:pb-0']) }}>
    <span aria-hidden="true" class="absolute bottom-0 left-4 top-0 -ml-px w-px bg-border group-first/item:top-4 group-last/item:bottom-auto group-last/item:h-4 group-only/item:hidden group-data-dense/timeline:left-3 group-data-dense/timeline:group-first/item:top-3 group-data-dense/timeline:group-last/item:h-3"></span>

    <span aria-hidden="true" @class([
        'relative flex size-8 shrink-0 items-center justify-center group-data-dense/timeline:size-6',
        'rounded-full bg-(--ds-timeline-bg,var(--color-surface)) ring-4 ring-(--ds-timeline-bg,var(--color-surface))' => $person || $icon,
    ])>
        @if ($person)
            <x-ds::avatar :src="$person['src'] ?? null" :name="$person['name'] ?? null" size="sm" :square="(bool) ($person['square'] ?? false)" :decorative="true" class="group-data-dense/timeline:size-6 group-data-dense/timeline:text-[0.625rem]" />
        @elseif ($icon)
            <span class="flex size-full items-center justify-center rounded-full {{ $tints[$tone] }}">
                <x-ds::icon :name="$icon" :size="4" class="group-data-dense/timeline:size-3.5" />
            </span>
        @else
            <span class="size-2.5 rounded-full ring-4 ring-(--ds-timeline-bg,var(--color-surface)) {{ $dots[$tone] }} group-data-dense/timeline:size-2"></span>
        @endif
    </span>

    <div class="min-w-0 flex-1 pt-1.5 group-data-dense/timeline:pt-0.5">
        @if ($title !== null && trim((string) $title) !== '' || $when)
            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                @if ($title !== null && trim((string) $title) !== '')
                    <p class="min-w-0 text-body font-medium text-fg wrap-break-word">
                        @if ($href)
                            <a href="{{ $href }}" class="rounded-chip underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">{{ $title }}</a>
                        @else
                            {{ $title }}
                        @endif
                    </p>
                @endif
                @if ($when)
                    @if ($when['datetime'] !== null && $when['datetime'] !== '')
                        <time datetime="{{ $when['datetime'] }}" @if ($when['title']) title="{{ $when['title'] }}" @endif class="shrink-0 text-meta tabular-nums text-fg-muted">{{ $when['text'] }}</time>
                    @else
                        <span class="shrink-0 text-meta tabular-nums text-fg-muted">{{ $when['text'] }}</span>
                    @endif
                @endif
            </div>
        @endif

        @if ($hasBody)
            <div class="mt-1 text-body text-fg-muted wrap-break-word">{{ $slot }}</div>
        @endif

        @isset($actions)
            <div class="mt-2 flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</li>
