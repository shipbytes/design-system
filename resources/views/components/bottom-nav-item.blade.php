@props([
    'label',
    'href' => null,
    'active' => false,
])

@php
    // The label and the icon take DIFFERENT inactive weights on purpose: the
    // icon carries the recognition and can afford to be lighter, the label
    // carries the meaning and cannot. Active pulls both to full strength.
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="button" @endif
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex flex-1 flex-col items-center justify-center py-2',
        'text-fg' => $active,
        'text-fg-muted' => ! $active,
    ]) }}
>
    {{-- The fill has to land on the <svg> ITSELF, not this wrapper. `fill` is an
         inherited SVG property, but the icons carry fill="currentColor" as a
         presentation attribute, and that beats inheritance — so a fill set here
         is ignored and the icon silently takes the link's text colour instead.
         The child selector reaches past that. --}}
    <span class="flex {{ $active ? '[&_svg]:fill-fg' : '[&_svg]:fill-fg-subtle' }}">{{ $icon ?? '' }}</span>
    <span class="mt-1 text-meta font-medium">{{ $label }}</span>
</{{ $tag }}>
