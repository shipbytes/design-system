{{--
    A chart's legend: each series' colour beside its name in words, so the
    colour is never the only thing saying which is which.

    $items    list of ['name' => …, 'colour' => class, 'series' => index or
              null, 'detail' => text after the name or null, 'title' => the
              full name when `name` was cut short, or null, 'class' => classes
              for the entry, a container query that hides it, or none]
    $toggles  the entries are hooks x-ds-chart turns into show-and-hide
              buttons (role, tabindex and aria-pressed are the script's to
              write: without it they are a plain list, which is right)
    $column   one entry per line, for a legend beside a donut

    The look of an entry that is off travels in aria-pressed, which only
    the script writes, read by the classes below.
--}}
<ul role="list" @class([
    'flex text-meta text-fg-muted',
    'flex-col gap-1.5' => $column ?? false,
    'flex-wrap gap-x-4 gap-y-1' => ! ($column ?? false),
])>
    @foreach ($items as $item)
        <li class="{{ trim('min-w-0 '.($item['class'] ?? '')) }}">
            <span
                @if (($toggles ?? false) && $item['series'] !== null) data-ds-chart-key="{{ $item['series'] }}" @endif
                @if ($item['title'] ?? null) title="{{ $item['title'] }}" @endif
                class="group/key inline-flex max-w-full items-center gap-1.5 rounded-chip aria-pressed:cursor-pointer aria-[pressed=false]:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
                <span aria-hidden="true" class="size-2.5 shrink-0 rounded-full bg-current ring-current ring-inset group-aria-[pressed=false]/key:bg-transparent group-aria-[pressed=false]/key:ring-2 {{ $item['colour'] }}"></span>
                <span class="truncate text-fg group-aria-[pressed=false]/key:text-fg-muted group-aria-[pressed=false]/key:line-through">{{ $item['name'] }}</span>
                @if (($item['detail'] ?? null) !== null)
                    <span class="ms-auto ps-2 whitespace-nowrap tabular-nums">{{ $item['detail'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ul>
