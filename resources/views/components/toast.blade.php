@props([
    /** success | warning | danger | accent | neutral */
    'tone' => 'neutral',
    /** Bold first line. Omit for the single-line form. */
    'title' => null,
    /** Heroicon. Defaults to one that matches the tone. */
    'icon' => null,
    /** Alpine expression that removes this toast. Renders a close control. */
    'dismiss' => null,
])

@php
    $tones = [
        'neutral' => ['icon' => 'information-circle', 'mark' => 'text-fg-muted'],
        'accent' => ['icon' => 'information-circle', 'mark' => 'text-accent'],
        'success' => ['icon' => 'check-circle', 'mark' => 'text-success'],
        'warning' => ['icon' => 'exclamation-triangle', 'mark' => 'text-warning'],
        'danger' => ['icon' => 'exclamation-circle', 'mark' => 'text-danger'],
    ];

    $t = $tones[$tone] ?? $tones['neutral'];
@endphp

{{--
    A toast is `surface` with a border, NOT a tinted wash like the alert.

    It floats over arbitrary content instead of sitting in the flow, so it has to
    paint an opaque ground and draw its own edge — a wash over an unknown
    background is unreadable at the one moment it matters. The tone lives in the
    icon alone, which is enough at this size and keeps four toasts stacked
    together from turning into a colour chart.
--}}
<div
    {{ $attributes->merge([
        'class' => 'pointer-events-auto flex w-full items-start gap-3 rounded-control border '
            .'border-border bg-surface p-3.5 shadow-float',
    ]) }}
    role="status"
>
    <x-ds::icon :name="$icon ?? $t['icon']" size="5" class="mt-px shrink-0 {{ $t['mark'] }}" />

    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-body font-semibold text-fg">{{ $title }}</p>
            <div class="mt-0.5 text-body text-fg-muted">{{ $slot }}</div>
        @else
            <div class="text-body text-fg-body">{{ $slot }}</div>
        @endif

        @if (isset($action))
            <div class="mt-2 flex items-center gap-2">{{ $action }}</div>
        @endif
    </div>

    @if ($dismiss)
        <button
            type="button"
            @click="{{ $dismiss }}"
            class="-m-1 shrink-0 rounded-chip p-1 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            aria-label="Dismiss"
        >
            <x-ds::icon name="x-mark" size="4" />
        </button>
    @endif
</div>
