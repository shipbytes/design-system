# Donut chart

The parts of one whole, as a ring: up to eight segments, the smallest folded
into "Other" past `maxSegments`, the total or any content in the middle, and
a legend with each part's value and share.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="both" class="relative flex …">   ← x-ds-chart only when `interactive`
    <div class="relative rounded-full size-40 has-focus-visible:outline-2 …">
        <svg data-ds-chart-plot role="img" aria-label="…summary…" viewBox="0 0 100 100">
            <circle r="…" stroke-width="16" stroke-dasharray="dash C" stroke-dashoffset="-start"
                    transform="rotate(-90 50 50)" data-ds-chart-mark aria-label="Direct: 1,200 (45%)"
                    class="text-series-1"/>
            …
        </svg>
        <div class="absolute" style="inset: N%">total, or the `centre` slot</div>
    </div>
    <ul role="list">legend: swatch, name, "1,200 · 45%"</ul>
    <table class="sr-only">Part, Value, Share, and the total</table>
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>
</div>
```

| Part | Token | Why |
|---|---|---|
| Segments | `series-1` … `series-8` by position | specs/chart-series.md |
| Other | the neutral tone | "Other" is not one more thing, and must not look like one. |
| Gap | 1px of the ring left undrawn between segments | The surface shows through, in either theme, and two neighbours never touch. One part alone is the whole ring. |
| Thickness | `sm` 10, `md` 16, `lg` 22 (of 100) | |
| Size | `sm` 128, `md` 160, `lg` 208px | |
| Total | `text-body` / `text-title` / `text-display` by size, `text-fg`, and `totalLabel` in `text-meta` | |

## Arcs by dash

Each segment is the same circle, stroked with one dash as long as its share
of the circumference and offset to where the previous one ended; the circle
is turned so the first starts at twelve o'clock. No arc paths: the geometry
is a length per part, and it is exact at any size because the SVG has a
viewBox and nothing in it is text. The centre is an HTML overlay inset to
the ring's inner edge, so a long figure wraps inside the hole rather than
over the segments.

## Data

| Case | Result |
|---|---|
| `[]` | `ds::empty-state`, bare, with the `empty` title |
| one part | the whole ring, no gap |
| more parts than `maxSegments` (default 6, 2 to 8) | the largest `maxSegments − 1` keep their order and colour; the rest are one "Other" (`otherLabel`) at the end |
| a zero, a negative, or a non-number | **throws** `InvalidArgumentException`, naming the part. A part of nothing has no arc, and a negative one would draw over its neighbour: both are data the host should filter. |

Shares are rounded by **largest remainder**, so the legend's percentages
always add up to exactly 100.

## The summary

`role="img"`, named from the data. With a legend, which already lists every
part beside the ring: "Traffic by source: 4 parts, total 2,650; the largest
is Direct at 45%." With `legend="none"`, every part: "Direct 1,200 (45%),
Search 800 (30%), …; total 2,650." `summary` replaces it; `table` adds a
hidden table of part, value and share, with the total.

## Accessibility

| | Without the script | With it |
|---|---|---|
| Role | `img` | `group`, keeping the name; each segment `img`, "Search: 800 (30%)" |
| Keyboard | none | one Tab stop; any arrow moves to the next / previous segment, Home / End to the ends, Escape puts the readout away |
| Focus ring | — | round the whole donut (`has-focus-visible` on the wrapper), and the focused segment widens: an outline on one segment would be its circle's square box, the same for all |
| Readout | — | on hover, tap and focus; `aria-hidden` |

The legend is never a toggle on a donut: hiding a part changes what the
whole is, and every share with it.

`legend="none"` leaves colour as the only visual tie between a segment
and its name. Use it only where the parts are named beside the chart
already (a stat tile, a table next to it).

## Do not

- **Do not use it to compare values.** Angles and arcs are read far less
  accurately than lengths: two parts within a few percent look the same.
  A bar chart compares.
- **Do not use it for a change over time.** Two donuts side by side cannot be
  compared; a line or a stacked bar can.
- **Do not use it for parts that do not add up to a whole** (survey answers
  where several could be chosen), or for more than a handful of parts.
- **Do not pass zeros or negatives.** It throws, on purpose.
- **Do not draw a pie.** Out of scope: the hole carries the total, and a pie's
  centre is where angles are hardest to read.
