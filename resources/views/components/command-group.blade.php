@props([
    /** The heading over its options, and the group's accessible name. */
    'label',
])

@php
    $id = $attributes->get('id') ?? 'ds-cmd-group-'.substr(md5($label), 0, 8);
@endphp

{{-- A flex column so the palette can rank the options inside by CSS order;
     the heading is first in the DOM at order 0, so it stays on top. --}}
<div {{ $attributes->except('id')->merge(['class' => 'flex flex-col pb-1']) }} id="{{ $id }}" role="group" aria-labelledby="{{ $id }}-label" data-ds-command-group>
    <div id="{{ $id }}-label" role="presentation" class="px-4 pt-2.5 pb-1 text-meta font-medium text-fg-muted">{{ $label }}</div>
    {{ $slot }}
</div>
