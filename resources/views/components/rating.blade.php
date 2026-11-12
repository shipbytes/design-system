@props([
    /** Submitted field name. Required unless `readonly`. */
    'name' => null,
    /** What is being rated. The group's legend; shown above a readonly rating when given. */
    'label' => null,
    /** How many stars. 1 to 10. */
    'max' => 5,
    /** The rating. A whole number to choose; any number to display, shown to the nearest half. */
    'value' => null,
    /** Display only: an image of the rating, named "3.5 out of 5". No field, no script. */
    'readonly' => false,
    /** sm | md | lg */
    'size' => 'md',
    /** Guidance under the stars. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    'disabled' => false,
])

@php
    $max = (int) $max;
    if ($max < 1 || $max > 10) {
        throw new \InvalidArgumentException("ds::rating: max=\"{$max}\" is out of range. It takes 1 to 10 stars.");
    }

    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    // Heroicons' star outline, written out rather than through ds::icon: the
    // fill has to come from the label's CSS, and the icon sets its own.
    $star = 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z';

    $glyph = [
        'sm' => 'size-4',
        'md' => 'size-5',
        'lg' => 'size-6',
    ][$size];

    if ($readonly) {
        // To the nearest half, and the name says what the picture shows.
        $shown = $value === null ? null : max(0, min($max, round(((float) $value) * 2) / 2));
        $spoken = $shown === null
            ? 'Not rated'
            : rtrim(rtrim(number_format($shown, 1, '.', ''), '0'), '.')." out of {$max}";
    } else {
        if ($name === null || $name === '') {
            throw new \InvalidArgumentException('ds::rating needs a `name` to post as, unless it is `readonly`.');
        }

        $chosen = $value === null || $value === '' ? null : max(0, min($max, (int) $value));
        $chosen = $chosen === 0 ? null : $chosen;

        // Stable under wire:model, random otherwise: see Support\ControlId.
        $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
        $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

        // A required rating has no "no rating": the browser refuses to submit
        // until a star is chosen, and there is nothing to go back to.
        $required = (bool) $attributes->get('required');

        // The binding reaches every radio; class and the rest go on the group.
        $isModel = fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
        $model = $attributes->filter($isModel);
        $group = $attributes->filter(fn ($value, $key) => ! $isModel($value, $key) && $key !== 'required');

        /*
         * A star is lit in four cases, and nothing ever has to unlight one, so
         * no two rules fight over the cascade:
         *
         *   - it, or a star after it, is checked, while the pointer is not on
         *     the stars (not-group-hover also holds on a device with no hover);
         *   - it, or a star after it, is under the pointer: the preview of
         *     "up to here", which replaces the checked state while it lasts;
         *   - it, or a star after it, has keyboard focus, which before a first
         *     choice is where Space would put the rating.
         *
         * The fill is the warning tone; the outline of a lit star is the
         * darker on-warning-tint, because amber alone is under 3:1 on a light
         * page and the star's edge is what has to hold against it.
         *
         * `~ *` looks only at later siblings, which is why the stars sit in a
         * row of their own and "no rating" outside it.
         */
        $lit = $disabled
            ? 'has-checked:fill-fg-subtle has-[~_*_:checked]:fill-fg-subtle'
            : implode(' ', [
                'not-group-hover/stars:has-checked:fill-warning not-group-hover/stars:has-checked:text-on-warning-tint',
                'not-group-hover/stars:has-[~_*_:checked]:fill-warning not-group-hover/stars:has-[~_*_:checked]:text-on-warning-tint',
                'hover:fill-warning hover:text-on-warning-tint',
                'has-[~_*:hover]:fill-warning has-[~_*:hover]:text-on-warning-tint',
                'not-group-hover/stars:has-focus-visible:fill-warning not-group-hover/stars:has-focus-visible:text-on-warning-tint',
                'not-group-hover/stars:has-[~_*_:focus-visible]:fill-warning not-group-hover/stars:has-[~_*_:focus-visible]:text-on-warning-tint',
            ]);

        $pad = ['sm' => 'p-1', 'md' => 'p-1.5', 'lg' => 'p-2'][$size];

        $starLabel = implode(' ', [
            'relative grid place-items-center rounded-chip fill-transparent',
            $pad,
            'has-focus-visible:outline-2 has-focus-visible:outline-offset-0 has-focus-visible:outline-focus-ring',
            'motion-safe:transition-colors motion-safe:duration-hint-enter motion-safe:ease-enter',
            $disabled ? 'cursor-not-allowed text-fg-subtle' : 'cursor-pointer text-fg-muted',
            $lit,
        ]);

        // Out of the way until there is a rating to clear: transparent while it
        // is itself the checked one, back when it has focus, since an
        // invisible radio could not take it.
        $clearLabel = implode(' ', [
            'grid place-items-center rounded-chip text-fg-muted',
            $pad,
            'has-checked:opacity-0 has-focus-visible:opacity-100',
            'has-focus-visible:outline-2 has-focus-visible:outline-offset-0 has-focus-visible:outline-focus-ring',
            $disabled ? 'cursor-not-allowed' : 'cursor-pointer hover:text-fg',
        ]);

        $radio = fn (string $value, bool $checked) => $model->merge(array_filter([
            'type' => 'radio',
            'name' => $name,
            'value' => $value,
            'class' => 'sr-only',
            'checked' => $checked ? true : null,
            'disabled' => $disabled ? true : null,
            'required' => $required && $value !== '' ? true : null,
        ], fn ($v) => $v !== null));

        $fieldset = $group->merge(array_filter([
            'class' => 'min-w-0',
            'role' => 'radiogroup',
            'aria-labelledby' => $label ? "{$id}-label" : null,
            'aria-describedby' => $describedBy,
            'aria-invalid' => $error ? 'true' : null,
            'aria-required' => $required ? 'true' : null,
        ], fn ($v) => $v !== null));
    }
@endphp

@if ($readonly)
    <div {{ $attributes->merge(['class' => 'inline-flex flex-col gap-1']) }}>
        @if ($label)
            <span class="text-meta font-medium text-fg-muted">{{ $label }}</span>
        @endif
        <span role="img" aria-label="{{ $spoken }}" class="inline-flex items-center gap-0.5">
            @for ($i = 1; $i <= $max; $i++)
                @php
                    $part = $shown === null ? 0 : max(0, min(1, $shown - ($i - 1)));
                @endphp
                {{-- A half is the full star clipped to its first half, over the
                     outline, so the point of the cut is exact at any size. --}}
                <span @class(['relative block shrink-0', $glyph, 'text-on-warning-tint fill-warning' => $part >= 1, 'text-fg-muted fill-transparent' => $part < 1])>
                    <svg class="block {{ $glyph }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="{{ $star }}" /></svg>
                    @if ($part > 0 && $part < 1)
                        <span class="absolute inset-y-0 start-0 w-1/2 overflow-hidden text-on-warning-tint fill-warning">
                            <svg class="block {{ $glyph }} max-w-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="{{ $star }}" /></svg>
                        </span>
                    @endif
                </span>
            @endfor
        </span>
    </div>
@else
    {{-- A fieldset whose legend is the question, like ds::radio-group, given
         the radiogroup role so aria-invalid and aria-required are valid on it. --}}
    <fieldset {{ $fieldset }}>
        @if ($label)
            <legend id="{{ $id }}-label" class="{{ $field['label'] }}">{{ $label }}</legend>
        @endif

        <div @class(['flex items-center', 'mt-1' => (bool) $label])>
            <div class="group/stars flex">
                @for ($i = 1; $i <= $max; $i++)
                    <label class="{{ $starLabel }}">
                        <input {{ $radio((string) $i, $chosen === $i) }} />
                        <svg class="{{ $glyph }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="{{ $star }}" /></svg>
                        <span class="sr-only">{{ $i === 1 ? '1 star' : "{$i} stars" }}</span>
                    </label>
                @endfor
            </div>

            @if (! $required)
                {{-- Last in the group, so the arrow keys reach it from the last
                     star and, wrapping, from the first: the way to take a
                     rating back, which a radio group otherwise never allows. --}}
                <label class="{{ $clearLabel }}">
                    <input {{ $radio('', $chosen === null) }} />
                    <x-ds::icon name="x-mark" variant="micro" size="4" />
                    <span class="sr-only">No rating</span>
                </label>
            @endif
        </div>

        @if ($error)
            <p id="{{ $id }}-error" class="{{ $field['error'] }}">
                <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
                <span>{{ $error }}</span>
            </p>
        @elseif ($help)
            <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
        @endif
    </fieldset>
@endif
