@props([
    /** What the bar filters, as its landmark's name and the summary's word. */
    'label' => 'Filters',
    /** How many filters are applied: shown beside the summary, and 0 hides the reset. */
    'active' => null,
    /** A link that resets every filter: usually the page with no query. */
    'resetHref' => null,
    /** A reset button that dispatches `reset`, bubbling, for a host that clears the filters itself. */
    'reset' => false,
    'resetLabel' => 'Clear all',
    /** The applied-filter row's accessible name. */
    'appliedLabel' => 'Applied filters',
    /**
     * Below `md` the controls fold behind a "Filters" button into a popover,
     * so a phone sees the search and one button. Needs Alpine and the
     * package's script below `md`; at `md` and up the controls are in the row
     * either way.
     */
    'collapsible' => false,
])

@php
    $count = is_numeric($active) ? max(0, (int) $active) : null;
    $hasControls = trim((string) $slot) !== '';
    $hasApplied = isset($applied) && trim((string) $applied) !== '';

    // No count is "not counted", which still offers the reset; a count of 0
    // has nothing to reset.
    $resettable = ($resetHref !== null || $reset) && $count !== 0;
    $resetClass = 'shrink-0 rounded-chip text-meta font-medium text-accent hover:text-accent-hover hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';

    // Hashed from the label and controls rather than random, so a Livewire
    // morph patches the panel instead of replacing it.
    $base = $attributes->get('id') ?? 'ds-filter-bar-'.substr(md5($label.$slot), 0, 8);

    $summary = 'flex shrink-0 items-center gap-1.5 text-meta font-medium text-fg-muted';
    $controls = 'flex flex-wrap items-center gap-2';

    if ($collapsible && $hasControls) {
        $recipe = \Shipbytes\BladeUi\Support\Popover::recipe();
        $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsPopover', ['dismissible' => true]);

        /*
         * The controls are rendered ONCE, in one element that is a popover
         * panel below md and a plain row at md and up. Twice, once in the row
         * and once in a popover, would post every field twice in a GET form
         * and leave two copies of the reader's choices to disagree.
         *
         * Below md: hidden from the server (max-md:hidden), so a phone never
         * paints the controls before the script folds them; Alpine's object
         * :class takes that class off as it starts, and x-show is in charge
         * from there, with dsPopover's open, close, focus and Escape and
         * x-ds-anchor's placement. The max-md: classes are the popover's
         * surface, written out because a class must be a literal the scanner
         * can see (the recipe's are unprefixed).
         *
         * At md and up: `md:flex!` outranks x-show's inline display: none,
         * and `md:static!` and the max-height and overflow ones outrank what
         * x-ds-anchor writes while it is open, so a window widened with the
         * popover open still shows a plain row. The trigger is md:hidden, so
         * nothing can open it there.
         *
         * role=group named by `label`, not role=dialog: at md and up this is
         * the bar's row of controls, and a dialog it is not.
         */
        $panel = new \Illuminate\View\ComponentAttributeBag([
            'id' => e($base).'-controls',
            'role' => 'group',
            'aria-label' => e($label),
            'data-ds-popover-panel' => true,
            'x-show' => 'dsOpen',
            'x-bind:class' => "{ 'max-md:hidden': false }",
            'x-ds-anchor' => '{ open: dsOpen, reference: $el.previousElementSibling, placement: \'bottom-start\' }',
            ...$recipe['transition'],
            'class' => implode(' ', [
                $controls,
                'max-md:hidden max-md:absolute max-md:top-full max-md:left-0 max-md:z-50 max-md:mt-2',
                'max-md:w-72 max-md:max-w-[calc(100vw-1rem)] max-md:flex-col max-md:items-stretch max-md:origin-top',
                'max-md:rounded-panel max-md:border max-md:border-border max-md:bg-surface max-md:p-4 max-md:text-start max-md:text-fg-body max-md:shadow-float max-md:outline-hidden',
                'md:flex! md:static! md:max-h-none! md:overflow-visible!',
            ]),
        ]);
    }
@endphp

<div
    role="search"
    aria-label="{{ $label }}"
    data-ds-filter-bar
    {{ $attributes->merge(['class' => 'flex min-w-0 flex-col gap-3']) }}
>
    <div class="flex flex-wrap items-center gap-2">
        @isset($search)
            <div class="min-w-0 flex-1 basis-56 md:max-w-xs">{{ $search }}</div>
        @endisset

        @if ($collapsible && $hasControls)
            {{-- The summary is the trigger below md and a label above it. --}}
            @if ($count !== null)
                <span class="{{ $summary }} max-md:hidden">
                    <x-ds::icon name="funnel" variant="mini" size="4" />
                    {{ $label }}
                    @if ($count > 0)<x-ds::badge tone="accent">{{ $count }}<span class="sr-only"> applied</span></x-ds::badge>@endif
                </span>
            @endif
            <div x-data="{{ $state }}" data-ds-popover class="relative inline-block h-fit md:contents">
                <div class="contents" data-ds-popover-trigger>
                    <x-ds::button variant="secondary" class="md:hidden">
                        <x-ds::icon name="funnel" variant="mini" size="4" />
                        {{ $label }}
                        @if ($count)
                            <x-ds::badge tone="accent">{{ $count }}<span class="sr-only"> applied</span></x-ds::badge>
                        @endif
                    </x-ds::button>
                </div>
                <div {{ $panel }}>{{ $slot }}</div>
            </div>
        @elseif ($hasControls)
            @if ($count !== null)
                <span class="{{ $summary }}">
                    <x-ds::icon name="funnel" variant="mini" size="4" />
                    {{ $label }}
                    @if ($count > 0)<x-ds::badge tone="accent">{{ $count }}<span class="sr-only"> applied</span></x-ds::badge>@endif
                </span>
            @endif
            <div class="{{ $controls }}">{{ $slot }}</div>
        @endif

        @if ($resettable && ! $hasApplied)
            @if ($resetHref !== null)
                <a href="{{ $resetHref }}" class="{{ $resetClass }}">{{ $resetLabel }}</a>
            @else
                <button type="button" class="{{ $resetClass }}" onclick="this.dispatchEvent(new CustomEvent('reset', { bubbles: true }))">{{ $resetLabel }}</button>
            @endif
        @endif

        @isset($actions)
            <div class="ms-auto flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @if ($hasApplied)
        <div role="group" aria-label="{{ $appliedLabel }}" class="flex flex-wrap items-center gap-2">
            {{ $applied }}
            @if ($resettable)
                @if ($resetHref !== null)
                    <a href="{{ $resetHref }}" class="{{ $resetClass }} ms-1">{{ $resetLabel }}</a>
                @else
                    <button type="button" class="{{ $resetClass }} ms-1" onclick="this.dispatchEvent(new CustomEvent('reset', { bubbles: true }))">{{ $resetLabel }}</button>
                @endif
            @endif
        </div>
    @endif
</div>
