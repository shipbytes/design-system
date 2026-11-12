# Bar chart

Values compared across categories: one series or up to eight, side by side
or stacked, vertical or horizontal. Drawn in PHP as an SVG; the package's
script adds a readout and keyboard access on top.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="horizontal"   ← root; the x-ds-chart attributes only when `interactive`
     data-ds-chart-next="ArrowDown" class="relative flex flex-col gap-3">
    <div class="@container pl-…">                       ← the gutter the value labels need, in px
        <svg data-ds-chart-plot role="img" aria-label="…summary…"
             width="100%" height="…px" class="overflow-visible">
            <g>gridlines, ticks: x in %, y in px</g>
            <g data-ds-chart-series="1">
                <rect data-ds-chart-mark data-ds-at="0" data-ds-in="0"
                      data-ds-label="Jan" data-ds-value="$12K" data-ds-series="Revenue" data-ds-slot="1"
                      aria-label="Jan, Revenue: $12K" class="fill-current text-series-1"/>
                …
            </g>
            <text>category labels, truncated, each with its full name as a title</text>
        </svg>
    </div>
    <ul role="list">legend, from two series</ul>
    <table class="sr-only">…</table>                    ← `table` only
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>   ← `interactive` only
</div>
```

| Part | Token | Why |
|---|---|---|
| Bars | `series-1` … `series-8` in order; `tone` for one series whose colour means something | specs/chart-series.md. A semantic tone across several series would say they are all "good". |
| Stack separator | a 1px `stroke-surface` | Two segments of a stack never touch, in either theme. |
| Gridlines | `text-border`, 1px | Behind the bars, light enough to read past. |
| Zero line | `text-border-strong` | Where a bar starts; drawn over the grid, and the line negatives hang from. |
| Tick labels | `text-meta`, `text-fg-muted`, tabular | SVG `text` on the type scale, never scaled. |
| Values (`showValues`) | `text-meta`, medium, `text-fg` | Text, so never a series colour (chart-series.md). |
| Readout | the tooltip's surface | Same recipe as `ds::tooltip`, so a reader meets one floating label. |

## Responsive, with text that does not scale

The SVG has **no viewBox**. Every x is a percentage of the box and every y is
in px, so the bars stretch with the container while the text stays at the
type scale's sizes: 12px on a phone, 12px on a wall display. A viewBox would
scale the labels with the box, to 5px in a sidebar and 20px across a screen.

What gives way as the box narrows is decided in PHP and applied by **container
queries** on the chart's own box, never the viewport:

- **Category labels** drop in halves (every 2nd, every 4th…) below the width
  at which they would collide; the first is always kept. `Chart::thinning()`
  works out the widths from the labels' measured lengths.
- **Value labels** on grouped bars hide together below the width at which a
  bar is narrower than its number, rather than overprinting.
- **Value ticks** on a horizontal chart thin the same way as category labels.
- The **gutter** is computed from the longest tick or category label, so a
  label never leaves the box and the plot never under-uses it.

Long category names truncate to 96px (vertical) or 120px (horizontal) with
an ellipsis, and carry the full name as an SVG `title`; the readout, the
summary and the table always have it in full. Many categories or long
names are the cue for `orientation="horizontal"`: a band per category,
and the names read across, as text does.

## Data

| Case | Drawn | Summary |
|---|---|---|
| `[]`, or nothing numeric | `ds::empty-state`, bare, with the `empty` title | — |
| one bar | one bar, its band centred | "Revenue: Jan at 12." |
| all zero | the zero line and the axis 0 to 1; no bars | "Signups: all 6 at 0." |
| negatives | bars hang below the zero line, which is drawn | ranked as given |
| `null` / non-numeric | no bar in that slot; the others keep their place | skipped |
| a ninth series | throws `InvalidArgumentException` | — |

The value axis always includes zero: a bar's length from anything else lies.
`yTicks` takes a count (round numbers, about that many) or the values
themselves.

## The summary

`role="img"` on the svg, named by `Chart::ranked()`: the label, then the
highest and lowest category per series. "Revenue by month. Revenue: highest
May at $26K, lowest Jan at $12K. Costs: …". `summary` replaces it, and should
whenever the point of the chart is something else ("Costs grew faster than
revenue"). `table` adds every value as a visually hidden table, captioned by
`label`, a column per series.

## Accessibility

| | Without the script | With it (`interactive`, the default) |
|---|---|---|
| Role | `img` on the svg; its children presentational | the svg becomes `group`, keeping the name; each bar `img`, named "Jan, Revenue: $12K" |
| Keyboard | none: not focusable | one Tab stop per chart (roving tabindex), then the keys below |
| Readout | none | on hover, tap and focus; `aria-hidden`, because the focused bar's own name says the same |
| Legend | a list of names | each entry a toggle button, `aria-pressed` |

| Key | Vertical | Horizontal |
|---|---|---|
| Along a series | ArrowLeft / ArrowRight (mirrored in RTL) | ArrowUp / ArrowDown |
| Next / previous series, same category | grouped: ArrowDown / ArrowUp; stacked: ArrowUp / ArrowDown (up the stack) | ArrowRight / ArrowLeft |
| First / last bar | Home / End | Home / End |
| Put the readout away | Escape (stops there only while a readout is shown) | Escape |
| Toggle a series | Enter or Space on its legend entry | same |

A hidden series' bars leave the tab order and the arrows skip them. The
last visible series cannot be hidden. Hiding re-scales nothing: a bar
keeps its length, so the reader keeps their bearings.

Focus draws the system ring on the bar itself, `outline-focus-ring`.

## Do not

- **Do not start the axis above zero.** The chart always includes it.
- **Do not colour bars by tone across several series.** `tone` is for one.
- **Do not rotate labels.** Truncate, thin, or go horizontal.
- **Do not put text in a series colour.** Values are `text-fg`.
- **Do not draw a ninth series.** Fold the tail into "Other", or facet.
- **Do not re-scale on a legend toggle.** It moves every bar under the reader.
