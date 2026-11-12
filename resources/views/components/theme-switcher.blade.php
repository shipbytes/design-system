@props([
    /**
     * segmented | button.
     *
     * segmented: the three choices side by side, as a ds::segmented-control.
     * All three are visible, the current one is plain to see, and any of them
     * is one press away. For a settings page or a menu with room.
     *
     * button: one icon button that steps light, dark, system and round again.
     * For a header or a toolbar, where three segments do not fit.
     */
    'variant' => 'segmented',
    /**
     * The localStorage key the choice is kept under. Pass the same one to
     * ds::theme-script. Switchers with the same key follow each other, on the
     * page and across tabs.
     */
    'storageKey' => 'ds-theme',
    /** The control's name: the segmented control's legend, the button's label. */
    'label' => 'Theme',
    /** Kept as the accessible name only, for the segmented variant. */
    'hideLabel' => false,
    /** sm | md | lg, as ds::segmented-control and ds::button. */
    'size' => 'md',
    /** The radios' name. Unique per switcher unless given, so two never share a group. */
    'name' => null,
    /**
     * Crossfade the page into the new theme where the browser has
     * document.startViewTransition() and motion is allowed.
     */
    'transition' => true,
])

@php
    $variant = in_array($variant, ['segmented', 'button'], true) ? $variant : 'segmented';
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsTheme', [
        'storageKey' => (string) $storageKey,
        'transition' => (bool) $transition,
    ]);

    $choices = [
        'light' => ['label' => 'Light', 'icon' => 'sun'],
        'dark' => ['label' => 'Dark', 'icon' => 'moon'],
        'system' => ['label' => 'System', 'icon' => 'computer-desktop'],
    ];

    // The server cannot read localStorage, so it draws "system", which is
    // also what a reader who never chose has. The script corrects the control
    // when Alpine starts; the PAGE is already right from ds::theme-script.
    $initial = 'system';

    $name ??= 'ds-theme-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));

    $labels = \Illuminate\Support\Js::from(array_map(fn ($c) => $c['label'], $choices));
    $prefix = \Illuminate\Support\Js::from($label.': ');
@endphp

<div
    x-data="{{ $state }}"
    data-ds-theme-switcher
    data-variant="{{ $variant }}"
    {{ $attributes->merge(['class' => 'inline-block']) }}
>
    @if ($variant === 'segmented')
        <x-ds::segmented-control :name="$name" :label="$label" :hideLabel="$hideLabel" :size="$size" x-model="dsTheme">
            @foreach ($choices as $value => $choice)
                <x-ds::segment :value="$value" :icon="$choice['icon']" :checked="$value === $initial">{{ $choice['label'] }}</x-ds::segment>
            @endforeach
        </x-ds::segmented-control>
    @else
        {{-- The name says what the button is and what it is set to; a
             press moves it on, and the name changes with it. --}}
        <x-ds::button
            variant="ghost"
            :size="$size"
            iconOnly
            aria-label="{{ $label }}: {{ $choices[$initial]['label'] }}"
            x-bind:aria-label="{{ $prefix }} + {{ $labels }}[dsTheme]"
            @click="dsCycle()"
            data-ds-theme-cycle
        >
            @foreach ($choices as $value => $choice)
                <span class="contents" x-show="dsTheme === '{{ $value }}'" @if ($value !== $initial) style="display: none" @endif>
                    <x-ds::icon :name="$choice['icon']" size="5" />
                </span>
            @endforeach
        </x-ds::button>
    @endif
</div>
