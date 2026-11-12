# Sparkline

A word-sized chart: the shape of a series, with no axes, labels or tooltip.

## Anatomy

```
<span class="w-24 h-6 text-success">        ← or <div> when `table` is on
    <svg role="img" aria-label="…summary…"     ← viewBox 0 0 100 30, preserveAspectRatio="none"
         class="size-full">
        <path d="…area…" fill-opacity=".15"/>  ← area only
        <path d="…line…" vector-effect="non-scaling-stroke"/>
        <path d="M x yh0" stroke-linecap="round"/>   ← the last dot
    </svg>
    <table class="sr-only">…</table>           ← `table` only
</span>
```

| Part | Token | Why |
|---|---|---|
| Colour | `tone` → `text-fg-muted` / `text-accent` / `text-success` / `text-warning` / `text-danger`; `series` → `text-series-N` | `currentColor` throughout, so one class colours line, area and dot. Neutral is `fg-muted`: a trend is secondary to the number beside it. |
| Line | 1.5px, round joins | |
| Area | the line's colour at 15% | A fill strong enough to read the shape, weak enough that the line leads. |
| Bars | the line's colour | A gap of 2 of the 100 units (1 past 24 bars), and a bar never under half its slot. |
| Dot | a zero-length path, round cap, 5px | Round at any stretch: a `circle` would become an ellipse. |
| Baseline | `text-fg/10` (the track) | Drawn for bars with negatives, and as a dashed rule for an empty series. |

## One viewBox, any box

Every sparkline draws into `0 0 100 30` with `preserveAspectRatio="none"`, so
it fills whatever box its `width` and `height` classes (or `full`) give it,
including a stat tile's caption row. Stretching scales strokes unevenly, so
every stroke has `vector-effect: non-scaling-stroke` and keeps its CSS width.
The geometry is computed in PHP by `Support\Chart` and unit-tested with exact
path strings.

## Data

| Case | Drawn | Summary |
|---|---|---|
| `[]` | dashed rule at mid-height | "No data." |
| one value | a dot, centred | "Signups: 5." |
| all equal | a flat line at mid-height | "Flat at 7." |
| negatives | line and area scale to them; bars grow from zero, which is drawn | |
| `null` / non-numeric | a gap: the line restarts, the bar is missing, the area closes | gaps are skipped |
| keyed values | as a list; keys name the table's rows | |

`min` and `max` fix the domain so two sparklines compare; values beyond them
are clamped to the edge rather than drawn outside the box. Bars always include
zero in their domain, because a bar's length from anything else lies.

## The summary

`role="img"` with an `aria-label` written from the data by
`Chart::summary()`: the label, the trend between the first and last values,
and the range. "Signups: up from 12 to 30 (low 8, high 30)." Up and down are
words; colour never carries the trend alone. `summary` replaces it.

`table` adds a visually hidden table of every value, captioned by `label`
(`resources/views/partials/chart-table.blade.php`, shared with later charts).
The root becomes a `div`, because a table may not sit in a `span`.

## Accessibility

| | |
|---|---|
| Role | `img`, on the `svg` |
| Name | the summary |
| Keyboard | none; not focusable |
| Focus | never takes or moves focus |

The SVG's children are presentational. With `table`, the hidden table is the
long description and is read as a table.

## Do not

- **Do not add axes, ticks or a tooltip.** That is a chart; a sparkline is read
  for shape.
- **Do not draw two series in one.** Use two, with `series` and a shared
  `min`/`max`.
- **Do not bridge a gap.** A line across missing data invents values.
- **Do not use a `circle` for the dot.** It stretches with the box.
