@props([
    /** input | select | textarea */
    'as' => 'input',
    'type' => 'text',
    /** Label text. Omit only when an adjacent visible label already names the field. */
    'label' => null,
    /** Guidance under the field. Replaced by `error` — never stacked with it. */
    'help' => null,
    /** Validation message. Its presence styles the control and sets aria-invalid. */
    'error' => null,
    /** Heroicon name rendered inside the control, before the value. */
    'icon' => null,
    /** Rows for a textarea. */
    'rows' => 3,
    'disabled' => false,
])

@php
    $id = $attributes->get('id') ?: 'ds-' . substr(md5($attributes->get('name', '') . $label . uniqid()), 0, 8);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    /*
     * The Catalyst two-layer construction, kept exactly.
     *
     *   ::before  inset by 1px — carries the fill and the shadow, at a radius
     *             one pixel tighter so it nests inside the outline
     *   ::after   at inset 0 — carries the focus ring, drawn INWARD
     *
     * Drawing the ring inward is the whole point: the box never changes size on
     * focus, so nothing reflows and the fields below do not shift. A plain
     * `outline` would.
     */
    $shell = implode(' ', [
        'relative block w-full',
        'before:absolute before:inset-px before:rounded-[calc(var(--ds-radius-control)-1px)]',
        'before:bg-surface before:shadow-raised',
        'after:pointer-events-none after:absolute after:inset-0 after:rounded-control',
        'after:ring-transparent after:ring-inset',
        'sm:focus-within:after:ring-2 sm:focus-within:after:ring-focus-ring',
        $disabled ? 'before:bg-surface-subtle before:shadow-none' : '',
    ]);

    $control = implode(' ', [
        'relative block w-full appearance-none rounded-control bg-transparent',
        // calc(step - 1px) absorbs the border so the control lands on the
        // 44px/36px grid rather than 2px over it.
        'px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)]',
        'sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
        'text-body-touch text-fg placeholder:text-fg-muted sm:text-body',
        'border focus:outline-hidden',
        $error ? 'border-danger hover:border-danger' : 'border-border hover:border-fg/20',
        $disabled ? 'text-fg-subtle' : '',
        $icon ? 'pl-9 sm:pl-9' : '',
        $as === 'select' ? 'pr-9 sm:pr-8' : '',
    ]);

    $shared = $attributes->except(['class'])->merge([
        'id' => $id,
        'class' => $control,
        'aria-describedby' => $describedBy,
        'aria-invalid' => $error ? 'true' : null,
    ])->filter(fn ($v) => $v !== null);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-body font-medium text-fg">{{ $label }}</label>
    @endif

    <span @class([$shell, 'mt-1.5' => (bool) $label])>
        @if ($icon)
            <span class="pointer-events-none absolute inset-y-0 left-3 z-10 flex items-center text-fg-muted">
                <x-ds::icon :name="$icon" />
            </span>
        @endif

        @if ($as === 'textarea')
            <textarea {{ $shared }} rows="{{ $rows }}" @disabled($disabled)>{{ $slot }}</textarea>
        @elseif ($as === 'select')
            <select {{ $shared }} @disabled($disabled)>{{ $slot }}</select>
            {{-- pointer-events-none so a click on the caret still opens the select --}}
            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-fg-muted">
                {{-- 20px below sm, 16px above: the caret is a touch target on a
                     phone and a hint on a desktop. Two breakpoints, not two
                     conflicting sizes — size-5 and sm:size-4 do not fight. --}}
                <x-ds::icon name="chevron-down" variant="mini" size="5" class="sm:size-4" />
            </span>
        @else
            <input type="{{ $type }}" {{ $shared }} @disabled($disabled) />
        @endif
    </span>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        {{-- Help is REPLACED by the error, never stacked with it: two lines of
             guidance under one field is one line too many. --}}
        <p id="{{ $id }}-help" class="mt-1.5 text-meta text-fg-muted">{{ $help }}</p>
    @endif
</div>
