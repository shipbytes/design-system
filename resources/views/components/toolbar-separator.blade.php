@aware([
    'orientation' => 'horizontal',
])

@php
    $vertical = $orientation === 'vertical';
    $tray = \Shipbytes\BladeUi\Support\Tray::recipe();
@endphp

{{-- Across the toolbar's direction: a vertical rule in a horizontal toolbar.
     Not focusable and not an item, so the arrow keys pass over it. --}}
<div
    role="separator"
    aria-orientation="{{ $vertical ? 'horizontal' : 'vertical' }}"
    {{ $attributes->except('orientation')->merge(['class' => $tray['separator']($vertical)]) }}
></div>
