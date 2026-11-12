# Treemap

Parts of a whole as rectangles whose areas are their values: storage by
folder, spend by team. One level of nesting at most (a group and its
parts). Suits a dozen to a few dozen tiles where the big ones matter and the
small ones only need to be findable.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="both" [data-ds-chart-links]
     class="relative flex flex-col gap-3 [direction:ltr]">                ← x-ds-chart only when `interactive`
    <div class="@container">
        <svg data-ds-chart-plot role="img" aria-label="…summary…" width="100%" height="256">   ← role="group" when a tile links
            <g data-ds-chart-mark data-ds-at="0" data-ds-label="Salaries" data-ds-series="Engineering"
               data-ds-value="$420K (44%)" aria-label="Salaries, Engineering: $420K (44%)">      ← <a href> for a linked tile
                <svg x="0%" y="0" width="41%" height="256" class="overflow-hidden">    ← the tile clips its own text
                    <rect width="100%" height="100%" class="fill-current text-series-1"/>
                    <rect data-ds-ring …/> <rect data-ds-ring …/>                         ← the focus ring, transparent until focused
                    <rect width="100%" height="100%" fill="none" stroke-width="2" class="stroke-surface"/>   ← the gap
                    <text aria-hidden="true" class="text-zinc-950 @max-[…]:hidden"><tspan>Salaries</tspan><tspan>$420K</tspan></text>
                </svg>
            </g>
            …
        </svg>
    </div>
    legend: the groups and their totals (nested), or Less ▢▢▢▢ More (colourBy="value")
    <table class="sr-only">Part, Group, Value, Share; the parts not drawn; Total</table>
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>
</div>
```

| Part | Token | Why |
|---|---|---|
| Tile, `colourBy="series"` | `series-1` … `series-8` by the top-level part's position as given (a group's children share its colour); a row's `tone` or `series` wins | specs/chart-series.md; position, not size, so a part keeps its colour when another drops out |
| Tile, `colourBy="value"` | the `tone` hue (accent by default) in the heat steps 1–4, by value quartile, as `fill-heat-N` | the activity heatmap's ramp, one hue |
| Gaps | a 2px `surface` stroke on every tile | tiles touch; the gap is the page showing through |
| Label | `text-meta`, the name `font-medium`, the value or share below; ink from `Ink::label()` | see *Text on a tile* |
| Height | `sm` 192, `md` 256, `lg` 320px | |
| Focus | drawn **inside** the tile as strokes: from the edge in, the 1px gap, 2px of `focus-ring`, 2px of `surface` | a neighbour touches every side and the plot clips the edge; a CSS outline on an svg group is placed by its stroke box, so it lands on the neighbour |

## Layout

Squarified (Bruls, Huizing and van Wijk, 2000) in `Support\ChartShapes::squarify()`,
a sibling of `Support\Chart` because that class is already past 1,300 lines:
values sorted largest first (equal values keep their given order, so the
same data always lays out the same way), rows added to the shorter side while
they make the worst aspect ratio in the row no worse, and the last tile and
the last row run exactly to the edge, so the box is filled to the pixel.
Unit tested (`tests/ChartShapesTest.php`): the paper's own example, areas
proportional to values, no overlaps, the box filled, equal values stable,
dropped values.

The layout is computed for a box `ratio` times as wide as `height`, then
drawn with x in percent and y in px, no viewBox: text keeps its size and the
treemap takes its container's width. In a box wider or narrower than `ratio`
assumes, every tile stretches the same way, so areas stay proportional and
nothing overlaps; set `ratio` near the space the chart usually gets.

With groups, the groups are laid out first, then each group's children
inside its rectangle.

## Text on a tile

A tile's label is its name (cut with `Chart::truncate()` to the tile's
width, the full name in a `title`) and, from 40px tall, its value or share.
None at all on a tile narrower or shorter than `minLabel` (40px) at the shape
the layout was made for, or shorter than 24px, or where less than two
letters of the name would fit. The rest are hidden by container query
(`Chart::below()`) while the chart is too narrow for them. Every tile is
its own nested `<svg>`, which clips, so text never reaches a neighbour.

**Ink.** Text on a fill is whichever of `zinc-950` and white has the higher
contrast, per fill and per theme. `Support\Ink` computes it from the shipped
tokens (the same OKLab mix the heat utilities use); the recipe keeps the
answers as literal classes so a consumer's Tailwind sees them:
`text-zinc-950` (dark in both themes), `text-white` (light in both),
`text-fg` (dark, then light) and `text-on-inverse` (light, then dark).
`tests/InkTest.php` recomputes every entry from `dist/tokens.json` and fails
when one is no longer the better ink, or when any pairing falls under 4.5:1.
`ds::funnel-chart` uses the same table.

Light-mode contrast of every fill and ink (D dark, L light). Step 3 is the
hue itself, the colour a `series` tile is drawn in; steps 1 to 4 are the
`colourBy="value"` ramp and the funnel's fade.

| Hue | Step 1 | Step 2 | Step 3 | Step 4 |
|---|---|---|---|---|
| neutral (fg-muted) | D 10.58 | D 6.48 | L 4.83 | L 8.92 |
| accent | D 10.38 | D 6.21 | L 5.26 | L 9.38 |
| success | D 11.63 | D 7.74 | D 5.43 | L 7.50 |
| warning | D 12.36 | D 8.62 | D 6.23 | L 6.73 |
| danger | D 10.51 | D 6.43 | L 4.76 | L 9.01 |
| series-1 | D 11.00 | D 6.94 | D 4.50 | L 8.39 |
| series-2 | D 12.36 | D 8.63 | D 6.22 | L 6.72 |
| series-3 | D 12.95 | D 9.42 | D 7.07 | L 6.12 |
| series-4 | D 14.39 | D 11.35 | D 9.19 | L 5.02 |
| series-5 | D 13.17 | D 9.72 | D 7.39 | L 5.92 |
| series-6 | D 10.51 | D 6.39 | L 4.95 | L 9.05 |
| series-7 | D 8.65 | L 4.54 | L 8.56 | L 12.62 |
| series-8 | D 11.41 | D 7.45 | D 5.03 | L 7.79 |

The lowest is series-1 at step 3, 4.50:1; in the dark theme the lowest is
4.59 (danger, step 2). A tile being read is drawn at 85% opacity, which can
take a dark-theme label just under 4.5 for the moment it is pointed at
(4.34 on the docs' Tools tile); the readout says the same text beside it.

## Data

| Case | Result |
|---|---|
| `['Photos' => 150, …]`, or rows with `label`, `value`, `tone` / `series`, `href` | one tile each |
| a row with `children` (rows of the same shape) | a group: its value is the sum of its children's, its children tiled inside it |
| a child with `children` | **throws**: one level of nesting |
| a value that is not a number | **throws**, naming the row (`Group › Part` for a child) |
| a value of zero or less | **dropped** from the picture and from the total shares are taken of; listed in the table as "not drawn", counted in the summary |
| more than eight top-level parts with `colourBy="series"` | **throws**, pointing at grouping, an "Other", or `colourBy="value"` |
| nothing above zero | `ds::empty-state`, bare |

Shares are largest-remainder percentages of the drawn total, so they add to
100; a share that rounds to 0 reads "<1%".

## The summary

`Chart::whole()` over the top-level parts: "Storage: 3 parts, total 306 GB;
the largest is Photos at 49%." With groups it adds the tiles and the largest
of them: "… 3 tiles; the largest is Salaries in Engineering at 70%." Dropped
parts add "1 of zero or less not drawn." `summary` replaces it. `table` is
**on by default**: small tiles carry no label, and the table is where every
one of them is read.

## Accessibility

| | Without the script | With it |
|---|---|---|
| Role | `img` with the summary; with any `href`, `group` with the summary, each linked tile a link and each other tile `img`, each named "Salaries, Engineering: $420K (44%)" | `group`, each tile `img` named as above |
| Keyboard | Tab to each link, when there are links | one Tab stop; the arrows move tile to tile in layout order (largest first), Home / End, Escape. **With links, nothing roves**: each link keeps its own Tab stop, and focus on one shows its readout |
| Pointer | links are links | the tile under the pointer |
| Readout | — | the tile's name, its group (with the group's swatch) and its value and share |
| Hidden table | every tile, the ones too small to label included, then the parts not drawn and the total | the same |

A focused tile's ring is drawn inside it, so it is never covered by a
neighbour or cut by the plot's edge, with or without the script. The
surface band inside the ring keeps it legible on a tile of its own hue. The
focus sweep in `test-behaviour.mjs` reads these strokes (`data-ds-ring`) as
the ring.

## Do not

- **Do not use a treemap to compare similar values.** Areas are read
  roughly; a bar chart reads lengths exactly.
- **Do not nest more than one level.** It throws; a second level would need
  a second colour channel the tiles do not have.
- **Do not zoom.** There is no drill-down; link a tile to a page for its
  part instead.
- **Do not feed it negatives to mean something.** They are dropped; a
  treemap only draws amounts.
