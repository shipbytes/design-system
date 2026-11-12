@props([
    /**
     * Opens it by event: an `open-command-palette` event at the window whose
     * detail.name is this (`close-command-palette` closes it). Left out, it
     * answers the events that name no palette.
     */
    'name' => null,
    /**
     * The keys that open it from anywhere on the page: `mod+k` is Cmd+K on a
     * Mac and Ctrl+K elsewhere. Also `ctrl`, `cmd`, `alt`, `shift`, and a
     * bare key such as `/`, which is ignored while typing in a field. false
     * for none.
     */
    'shortcut' => 'mod+k',
    'placeholder' => 'Type a command or search…',
    /** The dialog's and the field's accessible name. */
    'label' => 'Command palette',
    /** Shown, and announced, when nothing matches. */
    'emptyText' => 'No results',
    /**
     * The host is fetching options: a spinner in the field, the list marked
     * busy, and no empty row meanwhile.
     */
    'loading' => false,
    /**
     * Filter and rank the options in the browser as the reader types. false
     * when the host does the searching (the `search` event) and renders only
     * the options that match.
     */
    'filter' => true,
])

@php
    $id = $attributes->get('id') ?? 'ds-cmd-'.substr(md5($name.'|'.$label), 0, 8);
    $layer = \Shipbytes\BladeUi\Support\Modal::recipe();

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsCommandPalette', [
        'name' => $name,
        'shortcut' => $shortcut === false ? null : $shortcut,
        'filter' => (bool) $filter,
    ]);

    // The modal's own layer, not a second dialog: the same trap, focus in and
    // back out, scroll lock, backdrop and transitions (partials/modal.php).
    $focus = $layer['focus']('dsOpen', '[data-ds-command-panel]', '[data-ds-command-input]');
    $backdropTransition = new \Illuminate\View\ComponentAttributeBag($layer['backdropTransition']);
    $panelTransition = new \Illuminate\View\ComponentAttributeBag($layer['panelTransition']);
@endphp

<div
    x-data="{{ $state }}"
    data-ds-command
    {{ $attributes->except('id') }}
    id="{{ $id }}"
>
    {{-- Near the top rather than centred, so the field stays where it is as
         the list under it grows and shrinks with the query. --}}
    <div
        x-show="dsOpen"
        x-cloak
        x-effect="{{ $focus }}"
        @keydown.escape.window="if (dsOpen) dsOpen = false"
        class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:pt-[12vh]"
    >
        <div x-show="dsOpen" @click="dsOpen = false" {{ $backdropTransition }} class="{{ $layer['backdrop'] }}"></div>

        <div
            x-show="dsOpen"
            {{ $panelTransition }}
            @keydown.tab.prevent="{{ $layer['trap'] }}"
            data-ds-command-panel
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $label }}"
            class="relative flex max-h-full w-full max-w-xl flex-col overflow-hidden rounded-panel bg-surface shadow-overlay outline-hidden"
        >
            {{-- The field keeps focus the whole time the palette is open, so
                 its ring is drawn on its row, inward, where the panel's
                 rounded edge does not cut it. --}}
            <div class="flex shrink-0 items-center gap-3 rounded-t-panel border-b border-divider px-4 has-focus-visible:outline-2 has-focus-visible:-outline-offset-2 has-focus-visible:outline-focus-ring">
                @if ($loading)
                    <svg class="size-5 shrink-0 text-fg-muted motion-safe:animate-spin" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 3a9 9 0 1 0 9 9" />
                    </svg>
                @else
                    <x-ds::icon name="magnifying-glass" size="5" class="shrink-0 text-fg-muted" />
                @endif
                <input
                    type="text"
                    id="{{ $id }}-input"
                    data-ds-command-input
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="{{ $id }}-list"
                    aria-autocomplete="list"
                    aria-label="{{ $label }}"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    placeholder="{{ $placeholder }}"
                    class="min-w-0 flex-1 border-0 bg-transparent py-3.5 text-body text-fg outline-hidden placeholder:text-fg-muted"
                />
            </div>

            <div
                id="{{ $id }}-list"
                data-ds-command-list
                role="listbox"
                aria-label="{{ $label }}"
                aria-busy="{{ $loading ? 'true' : 'false' }}"
                class="flex min-h-0 max-h-80 flex-col overflow-y-auto overscroll-contain py-1.5 empty:hidden"
            >{{ $slot }}</div>

            {{-- Outside the listbox, which may hold only options and groups.
                 A status region, so "No results" is spoken when it appears. --}}
            <div role="status">
                <p data-ds-command-empty hidden class="px-4 py-6 text-center text-body text-fg-muted">{{ $emptyText }}</p>
            </div>

            @isset($footer)
                <div class="flex shrink-0 items-center gap-3 border-t border-divider px-4 py-2 text-meta text-fg-muted">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
