@props([
    /**
     * The landmark's name. A page usually carries more than one <nav>, and
     * unlabelled ones are indistinguishable in a screen reader's landmark list.
     */
    'label' => 'Breadcrumb',
])

{{--
    An ordered list, because the order IS the meaning — this is a path, not a
    set of links that happen to sit in a row.
--}}
<nav {{ $attributes->merge(['class' => 'min-w-0']) }} aria-label="{{ $label }}">
    {{-- Every item draws a leading separator and the FIRST one's is hidden here,
         rather than each item being told whether it is first. A component that
         has to know its own index cannot be moved, wrapped in an @if, or looped
         over without the caller doing bookkeeping the markup should do itself. --}}
    <ol class="flex flex-wrap items-center gap-1.5 text-meta [&>li:first-child>svg]:hidden">
        {{ $slot }}
    </ol>
</nav>
