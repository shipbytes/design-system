@props([
    /** What is being counted. */
    'label',
    /** The number. Passed as-is when not numeric, so "2,847" or "—" both work. */
    'value',
    /** Week-over-week change as a percentage. Null hides the chip entirely. */
    'delta' => null,
    /** Replaces the delta line when there is no meaningful comparison. */
    'caption' => null,
    /** Makes the whole tile a link. A tile that drills down should be an <a>. */
    'href' => null,
    /**
     * Count up from zero on first paint. Skipped automatically when the reader
     * has asked for reduced motion — the app's hand-written version animated
     * regardless, which is the kind of thing that gets missed because it only
     * affects people who are not in the room.
     */
    'countUp' => true,
])

@php
    $numeric = is_numeric(str_replace(',', '', (string) $value));
    $animate = $countUp && $numeric;
    $target = $numeric ? (float) str_replace(',', '', (string) $value) : null;

    // Group thousands server-side. The count-up finishes with toLocaleString,
    // so without this the number silently reformats when the script runs —
    // 2847 before, 2,847 after — and the no-JS render disagrees with the JS one.
    $display = $numeric && floor($target) == $target
        ? number_format($target)
        : $value;

    /*
     * Zero is its own tone, not a small rise.
     *
     * `>= 0` made a flat week render a GREEN +0% — the colour that means "up"
     * and the sign that means "up", on the one reading that means neither. A
     * dashboard of those quietly says everything is growing.
     *
     * The three cases are genuinely three: up, down, and no change. `null` is a
     * fourth and different again — "nothing to compare against yet" — which is
     * why it renders no chip at all rather than a 0%.
     */
    $direction = $delta === null ? null : ((float) $delta <=> 0.0);

    $deltaClasses = match ($direction) {
        1 => 'bg-success-tint text-on-success-tint',
        -1 => 'bg-danger-tint text-on-danger-tint',
        0 => 'bg-neutral-tint text-on-neutral-tint',
        default => '',
    };

    // No `+` on zero either. "+0%" is a claim about direction that 0 does not
    // make, and it is the half of the bug a colour change alone would leave.
    $sign = $direction === 1 ? '+' : '';

    $classes = implode(' ', [
        'group block rounded-control border border-border-strong bg-surface p-4',
        $href ? 'transition-all hover:border-fg/20 hover:shadow-raised' : '',
    ]);

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes, 'href' => $href])->filter(fn ($v) => $v !== null) }}>
    <div @class([
        'text-body font-medium text-fg-muted',
        'transition-colors group-hover:text-fg-body' => (bool) $href,
    ])>{{ $label }}</div>

    <div
        class="mt-2 text-display text-fg tabular-nums"
        @if ($animate)
            data-count-up="{{ $target }}"
        @endif
    >{{ $display }}</div>

    @if ($delta !== null)
        <div class="mt-2 flex items-center gap-2 text-body">
            <span class="inline-flex items-center rounded-chip px-1.5 py-0.5 text-meta font-medium {{ $deltaClasses }}">
                {{ $sign }}{{ $delta }}%
            </span>
            <span class="text-fg-muted">{{ $caption ?? 'from last week' }}</span>
        </div>
    @elseif ($caption)
        {{-- No chip rather than a 0% chip: "no change" and "nothing to compare
             against yet" are different statements, and a 0% reads as the first. --}}
        <div class="mt-2 text-body text-fg-muted">{{ $caption }}</div>
    @endif
</{{ $tag }}>

@once
    @push('scripts')
        <script>
            // Deliberately not Alpine. The component is presentation only, and a
            // count-up should not decide which JS framework the host app runs.
            (function () {
                function run() {
                    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    document.querySelectorAll('[data-count-up]').forEach(function (el) {
                        if (el.dataset.countUpDone) return;
                        el.dataset.countUpDone = '1';

                        var target = parseFloat(el.dataset.countUp);
                        if (isNaN(target)) return;
                        if (reduced) return; // the final value is already in the DOM

                        var start = performance.now();
                        var duration = 600;
                        (function step(now) {
                            var p = Math.min((now - start) / duration, 1);
                            el.textContent = Math.round(p * target).toLocaleString();
                            if (p < 1) requestAnimationFrame(step);
                        })(start);
                    });
                }

                if (document.readyState !== 'loading') run();
                else document.addEventListener('DOMContentLoaded', run);
                // Livewire replaces DOM on navigation; re-arm without assuming it exists.
                document.addEventListener('livewire:navigated', run);
            })();
        </script>
    @endpush
@endonce
