# Radar chart

One to four items scored on the same 3 to 8 measures, each item a polygon
over a web of spokes: a profile's shape at a glance (strong here, weak
there). It is read roughly; the hidden table, on by default, is how its
values are read exactly.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="horizontal" data-ds-chart-next="ArrowDown" data-ds-chart-toggles
     class="relative flex flex-wrap items-center gap-x-6 gap-y-3 [direction:ltr]">   ← x-ds-chart only when `interactive`
    <svg data-ds-chart-plot role="img" aria-label="…summary…" width="400" height="272" viewBox="0 0 400 272"
         class="block h-auto max-w-full">
        <g aria-hidden="true">rings, spokes, the ring values up the first spoke, the axis names</g>
        <g data-ds-chart-series="0" class="text-series-1 data-ds-off:hidden">
            <polygon class="fill-current [fill-opacity:0.12]" stroke-width="2"/>   ← dashed from the second item on
            <path d="…marker…"/> …                                                ← circle, square, triangle, diamond
            <circle r="9" data-ds-chart-mark data-ds-at="3" data-ds-in="0" data-ds-label="Reliability"
                    data-ds-series="Acme" data-ds-value="7" aria-label="Reliability, Acme: 7"/> …
        </g>
        …
    </svg>
    legend (two items up): a line in the item's dash with its marker, then the name; toggles with the script
    <table class="sr-only">Axis, Acme, Globex, …[, Max]</table>
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>
</div>
```

| Part | Token | Why |
|---|---|---|
| Items | `series-1` … `series-4` by position; a row's `tone` or `series` wins | specs/chart-series.md |
| Told apart without colour | a marker each (circle, square, triangle, diamond) and a dash each (solid, `6 4`, dotted, dash-dot), the same in the legend | `ChartShapes::MARKERS` and `DASHES` |
| Area | the item's colour at 12% (`fill`), or none | overlapping areas still show through |
| Rings and spokes | the gridline colour; the outer ring the zero-line colour | the edge is the scale's top |
| Ring values | `tick` (`text-meta`, `fg-muted`) with a 3px `surface` halo | the grid never strikes through a number |
| Axis names | `text-meta`, `fg` | |
| Size | `sm` 160, `md` 224, `lg` 288px across the grid, plus 88px each side and 24px above and below for the names | |

## Layout

Points from `Chart::polar()`: the first axis at twelve o'clock, then
clockwise at equal angles. Polygons and rings from `ChartShapes::polygon()`.

The radar is a fixed drawing with a viewBox, unlike the other charts: its
geometry is an angle, not a width, so it cannot stretch. It keeps its
natural size and scales down only in a box narrower than it
(`max-w-full h-auto`), text with it: at `md` (400px wide) in a 288px
column the text is drawn at 72%. Use `size="sm"` (336px wide) where that
matters.

**Axis names** sit 10px outside the grid, anchored by angle so each grows
away from the centre (`ChartShapes::anchor()`: at the top and bottom
centred, on the right starting, on the left ending), cut with
`Chart::truncate()` to the 76px beside the grid (176px at the top and
bottom), the full name in a `title`.

**The scale.** With one `max` (or none), every axis shares it: given, or the
round number above the data (`ChartShapes::radarMax()`: rings that step by
1, 2, 2.5 or 5 of a power of ten). The rings are numbered up the first
axis only, under the data. With a `max` per axis (axes in different units),
the rings are shares of each axis's top and carry no numbers, the readout
says "4.5 of 5", and the table gains a Max column. A value past its top is
drawn at the edge and said as it is.

## Data

| Case | Result |
|---|---|
| `axes` of 3 to 8 names | one spoke each |
| fewer than 3 or more than 8 axes | **throws**, pointing at a bar chart |
| rows with `label`, `values` (in axis order, or keyed by axis name), `tone` / `series`; or `['Acme' => [8, 6, 9], …]` | one item each |
| more than 4 items | **throws**, pointing at a grouped bar chart or several radars |
| a missing, non-number or negative value | **throws**, naming the item and axis |
| a per-axis `max` that is missing or not above zero | **throws** |
| no items | `ds::empty-state`, bare |

## The summary

`Chart::ranked()` per item, highest and lowest: "Vendor scores. Acme:
highest Features at 9, lowest Ease of use at 5. Globex: highest Support at 9,
lowest Integrations at 4." One item: "Skills: highest Design at 90, lowest
Ops at 40." With a `max` per axis the axes are ranked by their share of
their top, and said with it: "Plans: highest Rating at 4.5 of 5, lowest
Seats at 20 of 50." `summary` replaces it. `table` (on by default) gives axis by
item, every value.

## Accessibility

| | Without the script | With it |
|---|---|---|
| Role | `img`, the summary | `group`, keeping the name; each point `img`, "Reliability, Acme: 7" |
| Keyboard | none | one Tab stop; ArrowRight / ArrowLeft through the points (an item's axes in order, then the next item's), ArrowDown / ArrowUp to the same axis of the next / previous item showing, Home / End, Escape |
| Pointer | — | the point under it (a 9px target round each marker) |
| Legend | names, with each item's marker and dash | toggles (`aria-pressed`): a hidden item leaves the picture and the keyboard order |
| Readout | — | the axis, the item (with its swatch), the value |
| Hidden table | axis by item, and each max when per axis | the same |

Items never rest on colour: each has its own marker and dash, in the plot
and in the legend.

## Do not

- **Do not use a radar to compare exact scores.** Values are read off
  angled spokes against rings; a grouped bar chart puts them on one
  baseline and is read exactly. Choose the radar only for the shape of a
  profile.
- **Do not rely on the area.** It depends on the order of the axes, and
  reordering them changes it; the points are the data.
- **Do not mix units on a shared scale.** Give `max` per axis.
- **Do not draw more than four items.** It throws.
