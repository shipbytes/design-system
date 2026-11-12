{{--
    ds::panel with `collapsible`: the header is a disclosure button, the body
    the region it shows and hides. A partial, not a second component, because
    it is the panel; kept out of panel.blade.php so that a panel without the
    prop compiles to exactly the markup it always has (PanelUnchangedTest).

    One flag, read by this panel only, so the Alpine is inline, as sub-nav's
    is. Everything a reader sees before Alpine boots is rendered here from
    `open`: aria-expanded, the chevron, and the body's grid row and
    visibility. The body closes with the accordion's grid-rows trick, CSS
    only and motion-safe, and is `invisible` when closed, which takes its
    links out of the tab order and the accessibility tree.
--}}
@php
    if ($title === null || $title === '') {
        throw new \InvalidArgumentException(
            'ds::panel with `collapsible` needs a `title`: it is the words on the button that opens and closes the panel.'
        );
    }
    if (isset($header)) {
        throw new \InvalidArgumentException(
            "ds::panel \"{$title}\" is `collapsible` and has a `header` slot. The header of a collapsible panel is its button, "
            .'and a button cannot hold the controls a custom header usually carries. Use `title`, `subtitle`, `icon` and `action`.'
        );
    }

    $open = (bool) $open;
    $key = $name !== null && $name !== '' ? (string) $name : substr(md5($title.$subtitle), 0, 8);
    $base = $attributes->get('id') ?? 'ds-panel-'.\Illuminate\Support\Str::slug($key);
    $detail = \Illuminate\Support\Js::from(['name' => $name ?? $key]);

    $button = implode(' ', [
        'group flex w-full min-w-0 flex-1 items-center gap-2.5 text-start',
        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring',
        $feature ? 'rounded-panel p-5 sm:p-6' : 'rounded-control px-4 py-3',
        'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-fg/5',
    ]);
@endphp

<div
    x-data="{ dsPanelOpen: @js($open) }"
    {{ $attributes->merge(['class' => $shell]) }}
>
    <div class="flex items-center gap-3">
        <h2 class="flex min-w-0 flex-1">
            <button
                type="button"
                id="{{ $base }}-button"
                aria-expanded="{{ $open ? 'true' : 'false' }}"
                :aria-expanded="dsPanelOpen ? 'true' : 'false'"
                aria-controls="{{ $base }}-body"
                @click="dsPanelOpen = ! dsPanelOpen; $dispatch(dsPanelOpen ? 'expand' : 'collapse', {{ $detail }})"
                class="{{ $button }}"
            >
                @if ($feature)
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-control {{ $iconTones[$iconTone] ?? $iconTones['accent'] }}">
                        <x-ds::icon :name="$icon" />
                    </span>
                @endif
                <span class="min-w-0 flex-1">
                    <span class="block text-section text-balance text-fg">{{ $title }}</span>
                    @if ($subtitle)
                        <span class="block truncate text-meta font-normal text-fg-muted">{{ $subtitle }}</span>
                    @endif
                </span>
                <x-ds::icon
                    name="chevron-down"
                    variant="mini"
                    size="5"
                    class="shrink-0 text-fg-muted group-aria-expanded:rotate-180 motion-safe:transition-transform motion-safe:duration-popover-enter motion-safe:ease-enter"
                />
            </button>
        </h2>

        @if ($action)
            {{-- Beside the button, never inside it: a link in a button is two
                 controls in one, and neither works. --}}
            <a
                @if ($actionHref) href="{{ $actionHref }}" @endif
                {{ $attributes->only('wire:navigate') }}
                @class([
                    'shrink-0 whitespace-nowrap text-meta font-medium text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:text-fg rounded-chip focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
                    'me-5 sm:me-6 mt-5 sm:mt-6 self-start' => $feature,
                    'me-4' => ! $feature,
                ])
            >{{ $action }}</a>
        @endif
    </div>

    <div
        id="{{ $base }}-body"
        role="region"
        aria-labelledby="{{ $base }}-button"
        @class([
            'grid motion-safe:transition-all motion-safe:duration-dialog-enter motion-safe:ease-enter',
            'grid-rows-[1fr] visible' => $open,
            'grid-rows-[0fr] invisible' => ! $open,
        ])
        :class="{ 'grid-rows-[1fr] visible': dsPanelOpen, 'grid-rows-[0fr] invisible': ! dsPanelOpen }"
    >
        <div class="min-h-0 overflow-hidden">
            {{-- The list look's divider is the top of the body, not the
                 bottom of the header, so it folds away with the body and a
                 closed panel is not a header with two lines under it. --}}
            <div @class([
                'border-t border-border' => ! $feature,
                'divide-y divide-divider' => $body === 'rows',
                'px-5 py-5 sm:px-6 sm:py-6' => $body === 'plain' && ! $feature,
                'px-5 pb-5 sm:px-6 sm:pb-6' => $body === 'plain' && $feature,
            ])>
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
