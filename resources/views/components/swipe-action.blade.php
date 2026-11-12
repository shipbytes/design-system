@props([
    /** The action's words, shown under the icon. */
    'label',
    /** A heroicon name, drawn above the words. */
    'icon' => null,
    /** neutral | accent | danger */
    'tone' => 'neutral',
])

@php
    // Solid blocks, the way a row's swipe actions read: what they do is said
    // by the words and the icon, the tone only marks the destructive one.
    $tones = [
        'neutral' => 'bg-surface-inverse text-on-inverse hover:bg-surface-inverse-hover',
        'accent' => 'bg-accent text-on-accent hover:bg-accent-hover',
        'danger' => 'bg-danger text-on-danger hover:bg-danger-hover',
    ];
    $tone = $tones[$tone] ?? $tones['neutral'];
@endphp

<button
    {{ $attributes->merge([
        'type' => 'button',
        'class' => "flex min-w-18 flex-col items-center justify-center gap-1 px-3 text-meta font-medium {$tone} "
            .'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter '
            .'focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-focus-ring',
    ]) }}
>
    @if ($icon)
        <x-ds::icon :name="$icon" size="5" />
    @endif
    <span>{{ $label }}</span>
</button>
