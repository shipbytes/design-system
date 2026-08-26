@props([
    /** Makes the row navigate. A row that goes somewhere should be an <a>. */
    'href' => null,
])

@php
    // Rows carry their own padding rather than the panel padding the body:
    // a hover state that stops short of the panel edge looks like a mistake.
    $classes = implode(' ', [
        // Its own foreground, even though the panel now sets one too: a row is
        // the sub-component most likely to be used somewhere else, and an
        // inherited text colour is only correct until it is not.
        'flex items-center gap-3 px-4 py-3 text-body text-fg-body',
        $href ? 'transition-colors hover:bg-surface-subtle' : '',
    ]);

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes, 'href' => $href])->filter(fn ($v) => $v !== null) }}>
    {{ $slot }}
</{{ $tag }}>
