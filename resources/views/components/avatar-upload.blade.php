@props([
    /** The file field's name. The cropped photo posts under it as a file. */
    'name',
    /** Names the control, shown beside the avatar. */
    'label' => 'Photo',
    /** The current photo's URL. */
    'src' => null,
    /** Who or what is pictured: the photo's alt text, and the initials without one. */
    'alt' => null,
    /** circle | square: the crop's mask and the avatar's shape. Square for a company or a project. */
    'shape' => 'circle',
    /** What may be chosen, as on a file input. Checked in the browser for the reader's sake; validate on the server too. */
    'accept' => 'image/jpeg,image/png,image/webp',
    /** Largest file the reader may choose, in bytes. */
    'maxSize' => null,
    /** The cropped photo's width and height, in pixels. */
    'outputSize' => 512,
    /** image/jpeg | image/png | image/webp */
    'outputType' => 'image/jpeg',
    /** 0 to 1, for jpeg and webp. */
    'quality' => 0.9,
    /** A Rotate button in the crop dialog. */
    'rotate' => true,
    /** A Remove button while there is a photo. */
    'removable' => true,
    /** What Remove posts as 1. Defaults to "{name}_remove". */
    'removeName' => null,
    /** The host's upload, in progress: an overlay on the avatar. */
    'uploading' => false,
    /** With `uploading`: 0 to 100, or null for a spinner. */
    'progress' => null,
    'help' => null,
    'error' => null,
    'disabled' => false,
    /** lg (48px) | xl (64px) */
    'size' => 'xl',
])

@php
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $square = $shape === 'square';
    $size = $size === 'lg' ? 'lg' : 'xl';
    $removeName ??= "{$name}_remove";
    $removable = $removable && ! $disabled;
    $describedBy = trim(($error ? "{$id}-error" : '').' '.($help ? "{$id}-help" : ''));

    // "image/jpeg,image/png,image/webp" reads "JPEG, PNG or WebP".
    $types = collect(explode(',', (string) $accept))->map(fn ($t) => trim($t))->filter()
        ->map(fn ($t) => strtolower(str_starts_with($t, '.') ? ltrim($t, '.') : substr($t, strrpos($t, '/') + 1)))
        ->map(fn ($t) => match ($t) { 'jpg', 'jpeg' => 'JPEG', 'webp' => 'WebP', '*' => 'image', default => strtoupper($t) })
        ->unique()->values();
    $types = $types->count() > 1 ? $types->slice(0, -1)->implode(', ').' or '.$types->last() : $types->implode('');
    $megabytes = $maxSize ? rtrim(rtrim(number_format($maxSize / 1048576, 1), '0'), '.').' MB' : null;

    $words = [
        'type' => $types === 'image' ? 'Choose an image.' : "Choose a {$types} image.",
        'size' => $megabytes ? "Choose an image of {$megabytes} or less." : '',
        'unreadable' => 'That file could not be read as an image.',
    ];

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsAvatarUpload', [
        'accept' => $accept,
        'maxSize' => is_numeric($maxSize) ? (int) $maxSize : null,
        'outputSize' => max(16, (int) $outputSize),
        'outputType' => in_array($outputType, ['image/jpeg', 'image/png', 'image/webp'], true) ? $outputType : 'image/jpeg',
        'quality' => min(1, max(0, (float) $quality)),
        'words' => $words,
    ]);

    // wire:model and x-model belong on the file input, which is what
    // Livewire's upload listens to.
    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    $button = 'inline-flex items-center gap-1.5 rounded-control border border-border bg-surface px-3 py-1.5 text-body font-medium text-fg shadow-raised motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-50';
    $quiet = 'rounded-control px-2 py-1.5 text-body font-medium text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
    $rounding = $square ? 'rounded-control' : 'rounded-full';
@endphp

<div
    x-data="{{ $state }}"
    {{ $rest }}
    data-ds-avatar-upload
    role="group"
    aria-labelledby="{{ $id }}-label"
    {{ $attributes->only('class')->merge(['class' => 'flex items-center gap-4']) }}
>
    {{-- The drop target. A photo dropped here, or pasted while anything in
         the component has focus, goes to the crop dialog as a chosen one does. --}}
    <div class="relative shrink-0 {{ $rounding }} data-ds-dragging:outline-2 data-ds-dragging:outline-offset-2 data-ds-dragging:outline-focus-ring" data-ds-avatar-drop>
        <x-ds::avatar :src="$src" :name="$alt" :square="$square" :size="$size" data-ds-avatar-current :hidden="! $src" />
        <x-ds::avatar :name="$alt" :square="$square" :size="$size" data-ds-avatar-initials :hidden="(bool) $src" />
        {{-- The cropped photo, shown at once; what posts is the file input's. --}}
        <img data-ds-avatar-preview hidden alt="{{ $alt ?? '' }}" class="absolute inset-0 size-full object-cover {{ $rounding }}" />

        @if ($uploading)
            <span class="absolute inset-0 grid place-items-center bg-surface/80 {{ $rounding }}">
                <x-ds::progress :value="$progress" variant="circle" size="sm" label="Uploading {{ $label }}" :hideLabel="true" />
            </span>
        @endif
    </div>

    <div class="flex min-w-0 flex-col gap-1.5">
        <span id="{{ $id }}-label" class="{{ $field['label'] }}">{{ $label }}</span>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Without the script, this file input is the control: the
                 original photo posts and the server crops it. With it, the
                 input is hidden and only ever holds the cropped photo. --}}
            <input
                type="file"
                id="{{ $id }}"
                name="{{ $name }}"
                accept="{{ $accept }}"
                data-ds-avatar-input
                {{ $model }}
                x-bind:hidden="$data.dsReady"
                @disabled($disabled)
                @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
                @if ($error) aria-invalid="true" @endif
                class="max-w-full text-body text-fg-muted file:me-3 file:rounded-control file:border file:border-border file:bg-surface file:px-3 file:py-1.5 file:font-medium file:text-fg hover:file:bg-surface-subtle focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            />
            {{-- Chooses a photo for the crop dialog; it has no name, so it never posts. --}}
            <input type="file" accept="{{ $accept }}" hidden tabindex="-1" aria-hidden="true" data-ds-avatar-picker />

            <button type="button" hidden x-bind:hidden="! $data.dsReady" data-ds-avatar-change class="{{ $button }}" @disabled($disabled)
                @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
                <x-ds::icon name="camera" variant="mini" size="4" class="text-fg-muted" />
                <span>Change</span><span class="sr-only"> {{ $label }}</span>
            </button>

            @if ($removable)
                <button type="button" hidden x-bind:hidden="! $data.dsReady || ! $data.dsHasPhoto" data-ds-avatar-remove class="{{ $quiet }}">
                    Remove<span class="sr-only"> {{ $label }}</span>
                </button>
                {{-- Remove posts {name}_remove=1. Without the script, the
                     reader ticks it; with it, the Remove button does. --}}
                <label class="inline-flex items-center gap-1.5 text-body text-fg-muted" x-bind:hidden="$data.dsReady" @if (! $src) hidden @endif>
                    <input type="checkbox" name="{{ $removeName }}" value="1" data-ds-avatar-removed class="size-4 rounded-sm accent-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring" />
                    Remove photo
                </label>
            @endif
        </div>

        <p id="{{ $id }}-error" class="{{ $field['error'] }}" data-ds-avatar-error aria-live="polite" @if (! $error) hidden @endif>
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span data-ds-avatar-error-text>{{ $error }}</span>
        </p>
        @if ($help)
            <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
        @endif
    </div>

    {{-- The crop dialog: ds::modal, the package's one modal layer, opened
         by the script's dsCropping. --}}
    <x-ds::modal open="dsCropping" title="Crop {{ mb_strtolower($label) }}" size="sm">
        <div class="flex flex-col gap-4" data-ds-avatar-crop>
            {{-- Everything here works without dragging: the arrow keys move
                 the photo, + and − zoom, and the slider zooms too. --}}
            <div
                role="group"
                tabindex="0"
                aria-label="Crop area"
                aria-describedby="{{ $id }}-how"
                data-ds-avatar-stage
                class="relative mx-auto aspect-square w-full max-w-80 touch-none overflow-hidden rounded-control bg-surface-sunken select-none cursor-grab active:cursor-grabbing focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
                <img data-ds-avatar-image alt="" draggable="false" class="pointer-events-none absolute top-1/2 left-1/2 max-w-none origin-center" />
                {{-- The mask: everything outside the crop dimmed, its edge drawn. --}}
                <div aria-hidden="true" class="pointer-events-none absolute inset-[10%] {{ $rounding }} shadow-[0_0_0_100vmax_var(--color-scrim)] ring-2 ring-white/90"></div>
            </div>
            <p id="{{ $id }}-how" class="text-meta text-fg-muted">Drag the photo, or use the arrow keys, to move it. Zoom with the slider, the wheel, a pinch, or the + and − keys.</p>

            <div class="flex items-end gap-3">
                <x-ds::slider name="" label="Zoom" :min="100" :max="400" :value="100" :format="['suffix' => '%']" showValue="inline" class="min-w-0 flex-1" data-ds-avatar-zoom />
                @if ($rotate)
                    <button type="button" data-ds-avatar-rotate class="{{ $button }}">
                        <x-ds::icon name="arrow-path" variant="mini" size="4" class="text-fg-muted" />
                        Rotate
                    </button>
                @endif
            </div>
        </div>

        <x-slot:footer>
            <button type="button" data-ds-avatar-cancel class="{{ $quiet }}">Cancel</button>
            <button type="button" data-ds-avatar-apply class="inline-flex items-center rounded-control bg-surface-inverse px-3 py-1.5 text-body font-medium text-on-inverse motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-inverse-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Apply</button>
        </x-slot:footer>
    </x-ds::modal>
</div>
