@props([
    /** What the filter is on ("Status"). Left out, the chip shows the value alone. */
    'label' => null,
    /** What it is set to ("Open"). The chip's words. */
    'value',
    /**
     * Where the ✕ goes: the same view with this filter taken out of the
     * query. Left out, the ✕ is a button that dispatches `remove`, bubbling,
     * with `{ label, value }`, for a host that clears it in Alpine or Livewire.
     */
    'removeHref' => null,
    /** The ✕'s accessible name, before the filter's own words. */
    'removeLabel' => 'Remove filter',
])

@php
    $words = $label !== null && $label !== '' ? "{$label}: {$value}" : (string) $value;
    $name = "{$removeLabel} {$words}";

    // A plain onclick, not Alpine's: the event is the whole of it, so the ✕
    // works in a page with no Alpine, for a host listening with
    // addEventListener as much as with @remove.
    $detail = json_encode(['label' => $label, 'value' => $value], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    // 24px: the smallest target WCAG 2.2 allows without spacing around it.
    $remove = 'flex size-6 shrink-0 items-center justify-center rounded-full text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-fg/5 hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring';
@endphp

<span data-ds-filter-chip {{ $attributes->merge(['class' => 'inline-flex h-7 max-w-full min-w-0 items-center gap-0.5 rounded-full border border-border bg-surface ps-2.5 pe-0.5 text-meta text-fg-body']) }}>
    <span class="truncate">
        @if ($label !== null && $label !== '')
            <span class="text-fg-muted">{{ $label }}:</span>
        @endif
        <span class="font-medium text-fg">{{ $value }}</span>
    </span>
    @if ($removeHref !== null)
        <a href="{{ $removeHref }}" class="{{ $remove }}" aria-label="{{ $name }}">
            <x-ds::icon name="x-mark" variant="micro" size="3.5" />
        </a>
    @else
        <button type="button" class="{{ $remove }}" aria-label="{{ $name }}" onclick="this.dispatchEvent(new CustomEvent('remove', { bubbles: true, detail: {{ $detail }} }))">
            <x-ds::icon name="x-mark" variant="micro" size="3.5" />
        </button>
    @endif
</span>
