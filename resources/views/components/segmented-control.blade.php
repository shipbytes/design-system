@props([
    /** Submitted field name, shared by every segment. Not needed for link segments. */
    'name' => null,
    /** What the segments choose between. The group's name; shown above the control unless `hideLabel`. */
    'label',
    /** Kept as the accessible name, not shown: for a control whose meaning is plain from what is around it. */
    'hideLabel' => false,
    /** sm | md | lg — the control's height, that of a button of the same size. */
    'size' => 'md',
    /** Stretch to the container's width, the segments sharing it equally. */
    'fullWidth' => false,
])

@php
    /*
     * The segments have rendered by the time this runs (a component's slot
     * renders before the component), so, as with chip-group, the control
     * learns what they are from the markers they left, and puts the host's
     * binding on each input through the hook they carry.
     */
    $html = (string) $slot;
    $inputs = str_contains($html, 'data-ds-segment="input"');
    $links = str_contains($html, 'data-ds-segment="link"');

    if ($inputs && $links) {
        throw new \InvalidArgumentException(
            "ds::segmented-control \"{$label}\" mixes link segments (`href`) with segments that choose a value. "
            .'A control is one or the other: links switch the view by moving to a URL, inputs post with the form.'
        );
    }

    $isModel = fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);

    if ($model->getAttributes() !== [] && $links) {
        throw new \InvalidArgumentException("ds::segmented-control \"{$label}\": link segments have no value to bind; drop the wire:model or x-model.");
    }

    $segments = new \Illuminate\Support\HtmlString(str_replace('data-ds-segment-input', trim((string) $model), $html));

    /*
     * A host that sets the value through its binding checks a radio without
     * firing an event, so the glide is also handed the bound expression to
     * watch. wire:model's is a Livewire property, which Alpine reads as
     * $wire.<name>.
     */
    $bound = collect($model->getAttributes())->map(
        fn ($value, $key) => str_starts_with($key, 'wire:model') ? '$wire.'.$value : $value
    )->first();

    $sizes = [
        'sm' => 'h-control-sm',
        // As ds::button's md: 44px of touch target below sm, 36px above.
        'md' => 'h-control-lg sm:h-control-md',
        'lg' => 'h-control-lg',
    ];

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $labelId = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name).'-label';
    $legend = $hideLabel ? 'sr-only' : 'mb-2 block text-meta font-medium text-fg-muted';

    $track = implode(' ', [
        'group/glide relative max-w-full items-stretch rounded-control border border-border bg-surface-sunken p-0.5',
        $fullWidth ? 'flex w-full' : 'inline-flex',
        $sizes[$size] ?? $sizes['md'],
    ]);

    /*
     * The highlight that glides to the chosen segment, once the script has
     * placed it. Until then (and for good, without the script) the chosen
     * segment draws the same face itself, from has-checked or aria-current,
     * so nothing is ever unselected-looking. Its radius is the track's less
     * the 2px of padding, which is the chip radius, so the corners nest.
     */
    $highlight = implode(' ', [
        'pointer-events-none absolute top-0 left-0 rounded-chip bg-surface shadow-raised',
        'h-(--ds-glide-h) w-(--ds-glide-w) translate-x-(--ds-glide-x) translate-y-(--ds-glide-y)',
        'motion-safe:transition-[translate,width,height] motion-safe:duration-popover-enter motion-safe:ease-enter',
        'motion-safe:data-[instant]:transition-none',
    ]);
@endphp

@if ($links)
    {{-- Links that switch the view are navigation: a nav named by the label,
         the current view marked aria-current="page" by its segment. --}}
    <nav {{ $attributes->merge(['aria-labelledby' => $labelId, 'class' => 'min-w-0']) }}>
        <span id="{{ $labelId }}" class="{{ $legend }}">{{ $label }}</span>
        <div x-data x-ds-glide data-ds-glide class="{{ $track }}">
            <span data-ds-glide-highlight hidden aria-hidden="true" class="{{ $highlight }}"></span>
            {{ $segments }}
        </div>
    </nav>
@else
    {{-- A fieldset of real radios: they post, take the arrow keys and
         validate natively, with or without any script. --}}
    <fieldset {{ $attributes->filter(fn ($value, $key) => ! $isModel($value, $key))->merge(['class' => 'min-w-0']) }}>
        <legend class="{{ $legend }}">{{ $label }}</legend>
        <div x-data x-ds-glide="{{ $bound }}" data-ds-glide class="{{ $track }}">
            <span data-ds-glide-highlight hidden aria-hidden="true" class="{{ $highlight }}"></span>
            {{ $segments }}
        </div>
    </fieldset>
@endif
