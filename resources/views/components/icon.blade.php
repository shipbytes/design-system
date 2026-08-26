@props([
    /** Heroicon v2 name. v1 names listed in config('blade-ui.icon_aliases') also resolve. */
    'name',
    /** outline | solid | mini | micro */
    'variant' => 'outline',
    /** Tailwind size step. 4 = 16px, the dashboard's dominant icon size. */
    'size' => null,
    /**
     * Icons are decorative by default — most sit beside a label that already
     * says the same thing, and announcing both is noise. Pass a label only when
     * the icon is the ONLY thing carrying the meaning (an icon-only button).
     */
    'label' => null,
])

@php
    // The source app was built against Heroicons v1 and picked up v2 icons
    // later, so both generations of name appear in it. v2 renamed 33 of the
    // names in use; the alias map means an old one resolves instead of
    // rendering nothing at all. See icons/icons.json for the full mapping.
    $resolved = config('blade-ui.icon_aliases', [])[$name] ?? $name;
    $size ??= config('blade-ui.icon_size', 4);

    // blade-heroicons prefixes: o- outline (24), s- solid (24),
    // m- mini (20), c- micro (16).
    $prefix = match ($variant) {
        'solid' => 's',
        'mini' => 'm',
        'micro' => 'c',
        default => 'o',
    };

    $component = "heroicon-{$prefix}-{$resolved}";

    // Built here rather than inline on the tag: Blade's component-tag parser
    // gives up on a tag containing a multi-line attribute-bag expression and
    // emits the tag as literal text, which fails silently.
    $iconAttributes = $attributes
        ->class(["size-{$size}", 'shrink-0'])
        ->merge([
            // blade-heroicons stamps aria-hidden="true" on every icon. Left
            // alone, that cancels the role/label below — a screen reader skips
            // the element entirely and the accessible name is never announced.
            // Duplicate attributes resolve to the FIRST occurrence, and merged
            // attributes come first, so setting it explicitly wins.
            'aria-hidden' => $label ? 'false' : 'true',
            'role' => $label ? 'img' : null,
            'aria-label' => $label,
        ])
        ->filter(fn ($value) => $value !== null);
@endphp

<x-dynamic-component :component="$component" :attributes="$iconAttributes" />
