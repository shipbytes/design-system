{{--
    The readout x-ds-chart shows beside the mark under the pointer or focus,
    rendered hidden and empty: the script fills its four parts from the
    mark's data attributes and places it. Hidden from assistive technology,
    because the focused mark's own name already says the same.
--}}
@php $recipe = \Shipbytes\BladeUi\Support\Chart::recipe(); @endphp
<div data-ds-chart-readout hidden aria-hidden="true" class="{{ $recipe['readout'] }}">
    <div data-ds-chart-readout-label class="font-medium empty:hidden"></div>
    <div class="flex items-center gap-1.5">
        <span data-ds-chart-readout-swatch class="{{ $recipe['readoutSwatch'] }}"></span>
        <span data-ds-chart-readout-series class="empty:hidden"></span>
        <span data-ds-chart-readout-value class="font-semibold tabular-nums"></span>
    </div>
</div>
