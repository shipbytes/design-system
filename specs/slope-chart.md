# Slope chart

A handful of items at two points in time: two vertical axes, a line per item
between them, a dot at each end, and the item's name and value on both sides,
the right one with its change. Suits up to about ten items.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="horizontal" data-ds-chart-next="ArrowDown"
     data-ds-chart-nearest class="relative flex flex-col gap-3 [direction:ltr]">       ← x-ds-chart only when `interactive`
    <div class="pl-24 pr-28">                                                         ← gutters from the labels' widths
        <svg data-ds-chart-plot role="img" aria-label="…summary…" width="100%" height="…">
            <g aria-hidden="true">column headings, the two axes</g>
            <g aria-hidden="true"><line x1="0" x2="100%" … class="text-series-1"/> …</g>   ← muted lines first
            <g class="text-series-1">
                <circle cx="0" … data-ds-chart-mark aria-label="Oslo, 2024: 120"/>
                <circle cx="100%" … data-ds-chart-mark aria-label="Oslo, 2025: 180 (+60)"/>
            </g>
            …
            <g aria-hidden="true">"Oslo 120" at the left, "180 +60 Oslo" at the right</g>
        </svg>
    </div>
    <table class="sr-only">Item, 2024, 2025, Change</table>
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>
</div>
```

| Part | Token | Why |
|---|---|---|
| Lines and dots | `colourBy="series"`: `series-1` … `series-8` by position. `colourBy="direction"`: `success` up, `danger` down, the neutral tone flat. A row's own `tone` or `series` wins either way. | specs/chart-series.md |
| Muted (not in `highlight`) | `fg-subtle`, a thinner line, labels in `fg-muted` | they step back without leaving |
| Labels | `text-meta`, the body colour; the value `font-medium`; the change `fg-muted` | never a series colour on text |
| Axes | the gridline colour | |
| Height | `sm` 160, `md` 224, `lg` 288px, and at least 16px per item | |

## Layout

The frame of `ds::bar-chart`: no viewBox, so text and dots keep their size;
the axes at 0 and 100% of the plot, the labels in the padding beside it, the
gutters picked from their estimated widths (`Chart::gutter()`, up to 192px).
Each side's names are cut with `Chart::truncate()` to what is left once its
numbers have their room, between 48 and 112px, the full name in a `title`.

**Collisions.** Labels are nudged apart vertically by `Chart::spread()`: a
greedy pass down the page keeping each a line (16px) below the one above,
then back up from the floor so none is pushed out, then down from the top if
the plot is too short for them all. Each label keeps its order; its dot stays
at the true value.

**Direction never rests on colour.** The right label carries the change,
signed, in the chart's format (`Chart::change()`: "+60", "−10", "±0"), and
the summary counts the directions in words.

## Data

| Case | Result |
|---|---|
| rows with `label`, `from`, `to`, `tone` / `series`; or `['Oslo' => [120, 180]]` | one item each |
| a `from` or `to` that is not a number | **throws**: a slope needs both ends |
| `fromLabel` or `toLabel` missing | **throws**: the columns are the axis |
| more than eight items with `colourBy="series"` | **throws**, pointing at `colourBy="direction"` (eight series colours) |
| `[]` | `ds::empty-state`, bare |

The value scale is shared by both columns and runs from the lowest value to
the highest; it does not include zero, because a slope is read as a change,
not as a length.

## The summary

`Chart::slope()`: "Price: 2024 to 2025, 2 up, 1 down and 1 flat; the largest
rise is Oslo (+60), the largest fall Rome (−10)." One item: "Price: Oslo up
from 120 to 180 (+60), 2024 to 2025." `summary` replaces it; `table` adds
item, both values and the change.

## Accessibility

| | Without the script | With it |
|---|---|---|
| Role | `img` | `group`, keeping the name; each dot `img`, "Oslo, 2025: 180 (+60)" |
| Keyboard | none | one Tab stop; ArrowRight / ArrowLeft along the points (an item's first, then its second, then the next item's), ArrowDown / ArrowUp to the same column of the next / previous item **in the order given**, Home / End, Escape |
| Pointer | — | the nearest point, column first, as on the line chart |
| Readout | — | the item, the column as its series (with the item's swatch when coloured by series), the value and, on the right, the change |

## Do not

- **Do not draw more than about ten items.** The labels run out of room
  and the lines become a hairball; filter, or highlight the few that matter.
- **Do not use it for more than two points in time.** That is a line chart.
- **Do not colour by direction when a rise is bad news** (cost, latency,
  churn): `success` would call it good. Give those rows their own `tone`, or
  colour by series.
