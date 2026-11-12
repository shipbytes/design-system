{{--
    ds::split. Its props are the constructor of
    Shipbytes\BladeUi\View\Components\Split (orientation, name, label,
    stackBelow), which also hands its panes the orientation: see that class
    for why this component is not anonymous.

    A flex row or column whose panes are sized by CSS variables PHP writes,
    so the first paint is the final one and a host with no script gets fixed
    panes at those sizes. dsSplit makes the dividers move.
--}}
@php
    $vertical = $orientation === 'vertical';

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsSplit', ['name' => $name]);

    // Literal per breakpoint, so a consumer's Tailwind can see each one.
    $stack = [
        'sm' => 'max-sm:flex-col',
        'md' => 'max-md:flex-col',
        'lg' => 'max-lg:flex-col',
        'xl' => 'max-xl:flex-col',
    ][$stackBelow] ?? '';
@endphp

<div
    x-data="{{ $state }}"
    data-ds-split
    data-orientation="{{ $orientation }}"
    {{ $attributes->merge(['class' => trim('flex size-full min-h-0 min-w-0 data-dragging:select-none '.($vertical ? 'flex-col' : 'flex-row').' '.$stack)]) }}
>
    {{-- Over the whole window while a divider is dragged, so a pane holding
         an iframe cannot take the pointer away mid-drag, and the cursor is
         the divider's wherever the pointer goes. First, so the last pane and
         its divider are the last two children, which their classes count on. --}}
    <div data-ds-split-shield hidden aria-hidden="true" @class(['fixed inset-0 z-50', $vertical ? 'cursor-row-resize' : 'cursor-col-resize'])></div>
    {{ $slot }}
</div>
