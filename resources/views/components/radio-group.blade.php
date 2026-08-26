@props([
    /** What the group of options is choosing. Becomes the legend. */
    'label',
    /** Guidance for the group as a whole. */
    'help' => null,
    /** Validation message for the group. Errors belong here, not on one radio. */
    'error' => null,
])

@php
    $id = 'ds-group-'.substr(md5($label.uniqid()), 0, 8);
    $describedBy = trim(($error ? "{$id}-error " : '').($help ? "{$id}-help" : ''));
@endphp

{{--
    A fieldset with a legend, and not a <div> with a heading.

    A radio on its own announces its own label and nothing else, so "Monthly" is
    read without ever saying what is being chosen. The legend is what a screen
    reader repeats as the group is entered, and it is the only thing that makes
    a set of radios a question rather than four unrelated options.
--}}
<fieldset
    {{ $attributes->merge(['class' => 'min-w-0']) }}
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    @if ($error) aria-invalid="true" @endif
>
    <legend class="text-body font-medium text-fg">{{ $label }}</legend>

    @if ($help && ! $error)
        <p id="{{ $id }}-help" class="mt-0.5 text-meta text-fg-muted">{{ $help }}</p>
    @endif

    <div class="mt-2.5 flex flex-col gap-2.5">
        {{ $slot }}
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-2 flex items-start gap-1.5 text-meta text-danger">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @endif
</fieldset>
