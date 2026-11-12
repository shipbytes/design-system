@props([
    /** What the option says, and what typing is matched against first. */
    'label',
    /** Where it goes. A link: Enter or a press follows it, and the palette closes. */
    'href' => null,
    /**
     * Or the event it dispatches, bubbling from the option to the window,
     * with { value } as its detail; the palette then closes.
     */
    'action' => null,
    /** A ds::icon name, drawn before the label. */
    'icon' => null,
    /**
     * The keys that do this elsewhere in the app, drawn with ds::kbd and
     * nothing more: "Ctrl+Shift+P", or an array of keys. The palette does
     * not bind them.
     */
    'shortcut' => null,
    /** More words it is found by ("bill", "receipt" for Invoices): a string or an array. */
    'keywords' => null,
    'disabled' => false,
    /** Handed back in the action's detail. */
    'value' => null,
])

@php
    $option = \Shipbytes\BladeUi\Support\Listbox::recipe()['activeOption'];

    // From what the option is, so a Livewire re-render of the same option
    // keeps its id and the palette keeps it active.
    $id = $attributes->get('id') ?? 'ds-cmd-'.substr(md5(implode('|', [$label, $value, $href, $action])), 0, 10);
    $keys = is_array($shortcut)
        ? $shortcut
        : ($shortcut !== null && $shortcut !== '' ? array_map('trim', explode('+', (string) $shortcut)) : []);
    $words = is_array($keywords) ? implode(' ', $keywords) : (string) $keywords;
    $link = $href !== null && ! $disabled;

    // A link stays a link, so a middle press or Ctrl+press opens a tab and
    // wire:navigate still works; tabindex -1, because focus never leaves the
    // palette's field. merge() escapes these values; a bag built with new
    // would not.
    $bag = $attributes->except('id')->merge(array_filter([
        'id' => $id,
        'role' => 'option',
        'tabindex' => '-1',
        'aria-selected' => 'false',
        'aria-disabled' => $disabled ? 'true' : null,
        'href' => $link ? $href : null,
        'data-ds-command-item' => true,
        'data-ds-label' => $label,
        'data-ds-keywords' => $words !== '' ? $words : null,
        'data-ds-action' => $action !== null ? $action : null,
        'data-value' => $value !== null ? (string) $value : null,
        'class' => $option,
    ], fn ($v) => $v !== null));
@endphp

<{{ $link ? 'a' : 'div' }} {{ $bag }}>
    @if ($icon)
        <x-ds::icon :name="$icon" size="5" class="shrink-0 text-fg-muted" />
    @endif
    <span class="min-w-0 flex-1">
        <span class="block truncate">{{ $label }}</span>
        @if (trim((string) $slot) !== '')
            <span class="block truncate text-meta text-fg-muted">{{ $slot }}</span>
        @endif
    </span>
    @if ($keys)
        <x-ds::kbd :keys="$keys" separator="" class="shrink-0" />
    @endif
</{{ $link ? 'a' : 'div' }}>
