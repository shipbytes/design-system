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
    /**
     * An icon to render when `name` does not exist, instead of throwing. Off by
     * default: a wrong name typed into a template is a developer error and
     * should fail loud. Pass it only where the name comes from data or config,
     * where one bad entry would otherwise take down every page that shows it.
     */
    'fallback' => null,
])

@php
    $size ??= config('blade-ui.icon_size', 4);

    // Aliases and the variant prefix resolve in Support\Icons, shared with
    // Icons::exists(), so the check and the render cannot disagree.
    $component = \Shipbytes\BladeUi\Support\Icons::component($name, $variant);

    if ($fallback !== null && ! \Shipbytes\BladeUi\Support\Icons::exists($name, $variant)) {
        \Shipbytes\BladeUi\Support\Icons::reportMissing($name, $fallback);
        $component = \Shipbytes\BladeUi\Support\Icons::component($fallback, $variant);
    }

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
