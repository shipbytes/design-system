{{--
    The track and fill that ds::progress and ds::usage-meter both draw.

    A partial rather than a component on purpose: it is not something a page
    should use on its own, because it carries no role, name or value. Each
    caller owns its semantics (progressbar, meter) and passes this the shapes.

        size           sm | md
        fills          list of [width => CSS length or calc(), tone => one of the five]
        indeterminate  bool, with tone
        tone           the tone of the indeterminate sweep

    Every class is a literal in this file, so a consumer's scanner sees it.
--}}
@php
    $tracks = ['sm' => 'h-1', 'md' => 'h-2'];

    $fillTones = [
        // surface-inverse, not fg: it is the primary button's fill, so the
        // default bar reads as the same weight as the system's main action.
        'neutral' => 'bg-surface-inverse',
        'accent' => 'bg-accent',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
    ];

    // The stripes are drawn in currentColor, so the tone is a text colour.
    $stripeTones = [
        'neutral' => 'text-fg',
        'accent' => 'text-accent',
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
    ];

    $size = array_key_exists($size ?? 'md', $tracks) ? $size : 'md';
    $tone = array_key_exists($tone ?? 'neutral', $fillTones) ? $tone : 'neutral';
    $fills = $fills ?? [];
    $single = count($fills) === 1;
@endphp
{{-- fg/10 for the track, as the skeleton does: one translucent value reads on
     the panel, on the sunken ground behind it, and in dark. --}}
<div class="relative w-full overflow-hidden rounded-full bg-fg/10 {{ $tracks[$size] }}" aria-hidden="true">
    @if ($indeterminate ?? false)
        {{-- Reduced motion gets stripes across the whole track: it says "working,
             amount unknown" without moving. A static partial bar would read as a
             real value, which is the one thing an indeterminate bar must not say. --}}
        <div class="absolute inset-0 opacity-40 motion-safe:hidden bg-[repeating-linear-gradient(-45deg,currentColor_0_4px,transparent_4px_8px)] {{ $stripeTones[$tone] }}"></div>
        <div class="hidden h-full w-2/5 rounded-full motion-safe:block motion-safe:progress-indeterminate {{ $fillTones[$tone] }}"></div>
    @else
        <div class="flex h-full gap-0.5">
            @foreach ($fills as $fill)
                @php $fillTone = array_key_exists($fill['tone'] ?? 'neutral', $fillTones) ? $fill['tone'] : 'neutral'; @endphp
                {{-- Width from inline style, never a class: a class built from a
                     number is invisible to Tailwind's scanner (see CLAUDE.md). --}}
                <div
                    @class([
                        'h-full shrink-0',
                        $fillTones[$fillTone],
                        'rounded-full motion-safe:transition-[width] motion-safe:duration-slide-enter motion-safe:ease-enter' => $single,
                    ])
                    style="width: {{ $fill['width'] }}"
                ></div>
            @endforeach
        </div>
    @endif
</div>
