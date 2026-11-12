@props([
    /** What is being used up. The accessible name, and the visible heading. */
    'label',
    /** How much has been used. Can exceed `limit`. With segments, defaults to their sum. */
    'used' => null,
    /** The allowance. */
    'limit',
    /** The noun after the numbers: "credits" gives "42 of 50 credits". */
    'unit' => null,
    /** Percent of the limit at which the bar turns warning. */
    'warnAt' => 80,
    /** Percent of the limit at which it turns danger. */
    'dangerAt' => 100,
    /**
     * What fills the allowance, as a stacked bar with a legend: a list of
     * ['label' => …, 'value' => …, 'tone' => …]. Values are in the same unit as
     * `used`. With segments, each one keeps its own tone and the thresholds
     * colour only the over-limit message.
     */
    'segments' => null,
])

@php
    $tones = ['neutral', 'accent', 'success', 'warning', 'danger'];

    $limit = max(0.0, (float) $limit);

    $segments = collect($segments ?? [])
        ->map(fn ($segment) => [
            'label' => (string) data_get($segment, 'label', ''),
            'value' => max(0.0, (float) data_get($segment, 'value', 0)),
            'tone' => in_array(data_get($segment, 'tone'), $tones, true) ? data_get($segment, 'tone') : 'neutral',
        ])
        ->values();

    // Given segments and no `used`, the segments ARE the usage.
    $used = max(0.0, (float) ($used ?? $segments->sum('value')));

    /*
     * A limit of zero is data, not a mistake (a plan with no allowance of
     * something), so it renders rather than throwing: nothing used is empty,
     * anything used is over.
     */
    $percent = $limit > 0 ? $used / $limit * 100 : ($used > 0 ? INF : 0.0);
    $over = $used > $limit;

    $tone = match (true) {
        $percent >= (float) $dangerAt || $over => 'danger',
        $percent >= (float) $warnAt => 'warning',
        default => 'neutral',
    };

    // 1200 prints as "1,200" and 1.50 as "1.5". The numbers are read as well as
    // seen, and a trailing ".00" is noise in both.
    $number = fn (float $n): string => rtrim(rtrim(number_format($n, 2), '0'), '.');
    // Attributes take a bare number: aria-valuenow="1,200" is not one.
    $plain = fn (float $n): string => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

    $of = $number($used).' of '.$number($limit).($unit !== null && $unit !== '' ? ' '.$unit : '');
    $overBy = $number($used - $limit).($unit !== null && $unit !== '' ? ' '.$unit : '');

    /*
     * The bar never overflows. Over the limit, a single bar fills the track;
     * segments are scaled down together so they still add up to the whole and
     * keep their proportions to each other.
     */
    $scale = $limit > 0 ? max($used, $limit, $segments->sum('value')) : max($used, 1.0);
    $fills = $segments->isNotEmpty()
        ? $segments
            ->filter(fn ($s) => $s['value'] > 0)
            ->map(fn ($s) => ['width' => round($s['value'] / $scale * 100, 3).'%', 'tone' => $s['tone']])
            ->values()
            ->all()
        : [['width' => round(min($used, $scale) / $scale * 100, 3).'%', 'tone' => $tone]];

    $dots = [
        'neutral' => 'bg-surface-inverse',
        'accent' => 'bg-accent',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
    ];

    /*
     * role=meter, not progressbar: an allowance is a measurement inside a known
     * range, not a task moving towards done. aria-valuenow has to sit inside
     * min and max, so it stops at the limit, and the words carry the rest:
     * aria-valuetext is what is read, over the limit or not.
     */
    $meter = [
        'role' => 'meter',
        'aria-label' => $label,
        'aria-valuemin' => '0',
        'aria-valuemax' => $plain($limit),
        'aria-valuenow' => $plain(min($used, $limit)),
        'aria-valuetext' => $over ? $of.', '.$overBy.' over the limit' : $of,
    ];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="mb-1.5 flex items-baseline gap-3 text-body" aria-hidden="true">
        <span class="min-w-0 truncate font-medium text-fg">{{ $label }}</span>
        <span @class([
            'ml-auto shrink-0 tabular-nums',
            'font-medium text-danger' => $over,
            'text-fg-muted' => ! $over,
        ])>{{ $of }}</span>
    </div>

    <div {{ new \Illuminate\View\ComponentAttributeBag($meter) }}>
        @include('ds::partials.bar', ['size' => 'md', 'fills' => $fills])
    </div>

    @if ($over)
        {{-- Said in words as well as red: colour alone is not a state (WCAG 1.4.1). --}}
        <p class="mt-1.5 flex items-center gap-1.5 text-meta font-medium text-danger">
            <x-ds::icon name="exclamation-circle" variant="mini" size="4" />
            <span>{{ $overBy }} over the limit</span>
        </p>
    @endif

    @if ($segments->isNotEmpty())
        <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 text-meta">
            @foreach ($segments as $segment)
                <li class="flex items-center gap-1.5">
                    <span class="size-2 shrink-0 rounded-full {{ $dots[$segment['tone']] }}" aria-hidden="true"></span>
                    <span class="text-fg-body">{{ $segment['label'] }}</span>
                    <span class="tabular-nums text-fg-muted">{{ $number($segment['value']) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
