@props([
    /** neutral | accent | success | warning | danger */
    'tone' => 'neutral',
    /** Heroicon name. Defaults to one that matches the tone; `false` for none. */
    'icon' => null,
    /** The region's name, which a screen reader lists among the landmarks. */
    'label' => 'Announcement',
    /**
     * Show a close control. Needs Alpine and the package's script; without
     * them the control is not shown, rather than shown doing nothing.
     */
    'dismissible' => false,
    /**
     * A localStorage key. When set, a dismissal is kept under it and the bar
     * stays hidden on later loads, before the first paint. Implies
     * `dismissible`. A new announcement takes a new key.
     */
    'dismissKey' => null,
    /** A link after the message, for "read more" about it. */
    'href' => null,
    /** The link's words. */
    'actionLabel' => 'Learn more',
    /** Stick to the top of the window as the page scrolls. */
    'sticky' => false,
    /**
     * With tone="danger" only: an assertive alert, announced the moment it
     * appears. For an outage the reader must know about now, never for news.
     */
    'urgent' => false,
    /** The CSP nonce for the pre-paint check `dismissKey` writes. Defaults to Vite's. */
    'nonce' => null,
])

@php
    $tone = in_array($tone, ['neutral', 'accent', 'success', 'warning', 'danger'], true) ? $tone : 'neutral';
    $dismissKey = is_string($dismissKey) && $dismissKey !== '' ? $dismissKey : null;
    $dismissible = $dismissible || $dismissKey !== null;

    /*
     * neutral is the inverse surface: a bar above the chrome has to read as
     * separate from it, and a wash of the page's own grey does not. The
     * others are the alert's washes, at the alert's contrast, with a rule
     * under them in place of the alert's border.
     */
    $tones = [
        'neutral' => ['ground' => 'bg-surface-inverse text-on-inverse', 'icon' => 'megaphone'],
        'accent' => ['ground' => 'border-b border-accent/25 bg-accent-wash text-on-accent-tint', 'icon' => 'information-circle'],
        'success' => ['ground' => 'border-b border-success/25 bg-success-wash text-on-success-tint', 'icon' => 'check-circle'],
        'warning' => ['ground' => 'border-b border-warning/25 bg-warning-wash text-on-warning-tint', 'icon' => 'exclamation-triangle'],
        'danger' => ['ground' => 'border-b border-danger/25 bg-danger-wash text-on-danger-tint', 'icon' => 'exclamation-circle'],
    ];
    $t = $tones[$tone];
    $iconName = $icon === false ? null : ($icon ?? $t['icon']);

    /*
     * A landmark, not a live region: a bar that is there when the page loads
     * is read where it sits, and announcing a trial reminder over whatever a
     * screen reader is saying is rude. The one exception is the outage the
     * host marks urgent.
     */
    $alert = $tone === 'danger' && $urgent;

    /*
     * The collapse is a grid row going from 1fr to 0fr, as the accordion's
     * is: pure CSS, timed by the tokens, nothing under reduced motion.
     * `invisible` with it takes the content out of the tab order and the
     * accessibility tree once the row has closed. Both read data-ds-state,
     * which only dsDismiss writes, so a page without the script never
     * collapses anything.
     */
    $root = $attributes->merge(array_filter([
        'role' => $alert ? 'alert' : 'region',
        'aria-label' => $alert ? null : $label,
        'class' => implode(' ', array_filter([
            'grid w-full grid-rows-[1fr]',
            'motion-safe:transition-[grid-template-rows,visibility] motion-safe:duration-dialog-leave motion-safe:ease-leave',
            'data-[ds-state=dismissed]:invisible data-[ds-state=dismissed]:grid-rows-[0fr]',
            $sticky ? 'sticky top-0 z-40' : null,
        ])),
        'data-ds-announcement' => true,
        'data-ds-dismissible' => $dismissible ? true : null,
        'x-data' => $dismissible
            ? (string) \Shipbytes\BladeUi\Support\Behaviour::data('dsDismiss', array_filter(['key' => $dismissKey]))
            : null,
        'x-bind:data-ds-state' => $dismissible
            ? "typeof \$data.dsDismissed === 'boolean' ? (\$data.dsDismissed ? 'dismissed' : 'shown') : null"
            : null,
    ], fn ($v) => $v !== null));

    $ring = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
@endphp

<div {{ $root }}>
    @if ($dismissKey !== null)
        {!! \Shipbytes\BladeUi\Support\Dismissal::script($dismissKey, $nonce) !!}
    @endif

    {{-- The ground and its rule are inside the clipped row, so a closed
         row leaves no line behind. --}}
    <div class="min-h-0 overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-2.5 {{ $t['ground'] }}">
            @if ($dismissible)
                {{-- As wide as the close control, so the message is centred
                     on the bar and not on what is left of it. --}}
                <span class="w-7 shrink-0" aria-hidden="true"></span>
            @endif

            <div class="flex min-w-0 flex-1 flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center text-body">
                <div class="flex min-w-0 items-start gap-2 text-start">
                    @if ($iconName)
                        <x-ds::icon :name="$iconName" size="4.5" class="mt-px" />
                    @endif
                    <div class="min-w-0">{{ $slot }}</div>
                </div>

                @if ($href)
                    <a href="{{ $href }}" class="shrink-0 rounded-chip font-semibold whitespace-nowrap underline underline-offset-2 hover:no-underline {{ $ring }}">{{ $actionLabel }}<span aria-hidden="true"> &rarr;</span></a>
                @endif

                @isset($action)
                    <div class="flex shrink-0 items-center gap-2">{{ $action }}</div>
                @endisset
            </div>

            @if ($dismissible)
                {{-- Invisible until dsDismiss has started and written
                     data-ds-state: without the script it would look
                     pressable and do nothing, as the alert's once did. --}}
                <button
                    type="button"
                    class="-my-1 shrink-0 rounded-chip p-1.5 opacity-70 invisible in-data-[ds-state]:visible motion-safe:transition-opacity motion-safe:duration-popover-enter motion-safe:ease-enter hover:opacity-100 {{ $ring }}"
                    aria-label="Dismiss"
                    data-ds-dismiss
                >
                    <x-ds::icon name="x-mark" size="4" />
                </button>
            @endif
        </div>
    </div>
</div>
