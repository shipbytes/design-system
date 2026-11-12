{{--
    One soft button in a tray: what ds::toolbar-button and ds::toggle draw.
    A partial, not a component, for the reason partials/bar is: it carries no
    role or value of its own; the component that includes it decides those
    and passes the finished attribute bag.

    $tag          button or a
    $item         the element's attributes, classes included
    $label        the words, and the accessible name; sr-only with $iconOnly
    $icon         a Heroicons name, or null
    $iconOnly     bool
    $tooltip      null, a string, or true for the label
    $placement    the tooltip's side
    $inside       markup that rides inside the element (a toggle's hidden input), or null
--}}
@php
    $tip = $tooltip === true ? $label : $tooltip;
    $glyph = $iconOnly ? '5' : '4';
@endphp

@if ($tip !== null && $tip !== false && $tip !== '')
    <x-ds::tooltip :text="$tip" :placement="$placement">
        <{{ $tag }} {{ $item }}>
            @if ($icon)
                <x-ds::icon :name="$icon" :size="$glyph" />
            @endif
            @if ($iconOnly)<span class="sr-only">{{ $label }}</span>@else<span>{{ $label }}</span>@endif
            {{ $inside }}
        </{{ $tag }}>
    </x-ds::tooltip>
@else
    <{{ $tag }} {{ $item }}>
        @if ($icon)
            <x-ds::icon :name="$icon" :size="$glyph" />
        @endif
        @if ($iconOnly)<span class="sr-only">{{ $label }}</span>@else<span>{{ $label }}</span>@endif
        {{ $inside }}
    </{{ $tag }}>
@endif
