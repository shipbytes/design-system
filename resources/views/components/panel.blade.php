@props([
    /** Panel heading. Omit for a bare bordered container. */
    'title' => null,
    /** Second line under the title. Only meaningful with an icon — see `variant`. */
    'subtitle' => null,
    /** Heroicon name for the leading tile. Presence of this switches to the feature look. */
    'icon' => null,
    /** Tone of the icon tile: accent | success | warning | danger | neutral. */
    'iconTone' => 'accent',
    /** Trailing header link, e.g. "View all". */
    'action' => null,
    'actionHref' => null,
    /**
     * How the body is laid out.
     *   rows  — children are separated by dividers and manage their own padding
     *   plain — a single padded region, for free-form content
     */
    'body' => 'rows',
])

@php
    $feature = (bool) $icon;

    $iconTones = [
        'accent' => 'bg-accent-tint text-on-accent-tint',
        'success' => 'bg-success-tint text-on-success-tint',
        'warning' => 'bg-warning-tint text-on-warning-tint',
        'danger' => 'bg-danger-tint text-on-danger-tint',
        'neutral' => 'bg-neutral-tint text-on-neutral-tint',
    ];

    // A feature panel sits on the page in its own right, so it gets the larger
    // radius and the solid edge. A list panel sits in a column of siblings and
    // stays quiet.
    // Both variants paint their own surface. The hand-written version relied on
    // sitting on a white parent, which is fine until it does not — on the app's
    // sunken ground, or inside a dark theme, an unpainted panel shows whatever is
    // behind it. Explicit costs nothing where the parent is already white.
    // A component that paints its own BACKGROUND must set its own FOREGROUND.
    //
    // The panel painted the surface and left the text colour to be inherited, so
    // a row or a plain body rendered at the browser's default black — 1.4:1 on
    // the dark surface, effectively invisible. It survived because a host
    // usually sets a text colour somewhere above it, which is exactly the
    // "fine until it does not" the background comment below is about. The two
    // halves have to travel together.
    $shell = $feature
        ? 'rounded-panel border border-border-strong bg-surface text-fg-body overflow-hidden'
        : 'rounded-control border border-border bg-surface text-fg-body';
@endphp

<div {{ $attributes->merge(['class' => $shell]) }}>
    @if ($title || $action || isset($header))
        <div @class([
            'flex items-center justify-between gap-3',
            'px-5 pt-5 sm:px-6 sm:pt-6' => $feature,
            'border-b border-border px-4 py-3' => ! $feature,
        ])>
            @isset($header)
                {{ $header }}
            @else
                <div class="flex min-w-0 items-center gap-2.5">
                    @if ($feature)
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-control {{ $iconTones[$iconTone] ?? $iconTones['accent'] }}">
                            <x-ds::icon :name="$icon" />
                        </span>
                    @endif
                    <div class="min-w-0">
                        {{-- The title is content: it wraps rather than truncating.
                             `balance` keeps a two-line wrap from leaving one orphan
                             word, which is what made the hand-written version look
                             ragged in a narrow column. --}}
                        <h2 class="text-section text-balance text-fg">{{ $title }}</h2>
                        @if ($subtitle)
                            {{-- fg-MUTED, not fg-subtle. The system allows subtle
                                 only where the same information is also written
                                 next to it — an icon beside its own label. A
                                 subtitle is the only place its content appears,
                                 so it is text a reader must be able to read, and
                                 subtle is 3.67:1 against the panel. Same
                                 correction color.md already applies to
                                 timestamps. --}}
                            <p class="truncate text-meta text-fg-muted">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
            @endisset

            @if ($action)
                {{-- Secondary by design: the panel's content is the point, and a
                     "View all" that competes with it pulls the eye off the data. --}}
                <a
                    @if ($actionHref) href="{{ $actionHref }}" @endif
                    {{ $attributes->only('wire:navigate') }}
                    class="shrink-0 whitespace-nowrap text-meta font-medium text-fg-muted transition-colors hover:text-fg"
                >{{ $action }}</a>
            @endif
        </div>
    @endif

    <div @class([
        'divide-y divide-divider' => $body === 'rows',
        'px-5 py-5 sm:px-6 sm:py-6' => $body === 'plain',
        'pt-4' => $body === 'plain' && $feature,
    ])>
        {{ $slot }}
    </div>
</div>
