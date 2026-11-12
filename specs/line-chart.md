# Line chart

Values in order, usually over time: up to eight series, straight or smooth,
with an optional area, gaps for missing values, and each line named at its
end. Drawn in PHP as an SVG; the package's script adds a readout and
keyboard access on top.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="horizontal"       ← x-ds-chart attributes only when `interactive`
     data-ds-chart-next="ArrowDown" data-ds-chart-nearest
     class="relative flex flex-col gap-3">
    <div class="@container"><div class="pl-… pr-…">          ← gutters: the tick labels, the end labels
        <svg data-ds-chart-plot role="img" aria-label="…summary…" width="100%" height="…px">
            <g aria-hidden="true">gridlines and ticks; x labels, thinned</g>
            <g data-ds-chart-series="0" class="text-series-1">
                <svg viewBox="0 0 100 H" preserveAspectRatio="none">  ← the plot, stretched
                    <path area fill-opacity=".12"/>
                    <path line vector-effect="non-scaling-stroke"/>
                </svg>
                <circle data-ds-chart-mark cx="…%" cy="…" r="3.5" …/>  ← a point per value
            </g>
            <g aria-hidden="true"><text>end labels</text></g>
        </svg>
    </div></div>
    <ul role="list">legend</ul>
    <table class="sr-only">…</table>
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>
</div>
```

| Part | Token | Why |
|---|---|---|
| Lines | 2px, round joins, `series-1` … `series-8`; `tone` for one series | specs/chart-series.md |
| Area | the line's colour at 12% | Shows the volume without hiding a second line under it. |
| Points | 3.5px radius, a 1.5px `stroke-surface` ring | Reads as a point on the line, not a blot. Without `points` they are drawn invisible, shown only while read, and always shown for a value with a gap on both sides, which has no line. |
| Gridlines, ticks | as bar-chart | |
| End labels | `text-meta`, medium, `text-fg` | Text, so never in the series colour. |

## One plot, any width

The outer SVG has no viewBox: x in %, y in px, so text never scales (see
bar-chart). The lines are drawn into a nested SVG whose viewBox is `0 0 100 H`
with `preserveAspectRatio="none"`, so a path in the 0–100 x space stretches
to the width; `vector-effect: non-scaling-stroke` keeps its 2px. Points and
labels sit in the outer SVG at the same percentages, so they stay round and
on the line at every width.

x labels thin by container query (`Chart::thinning()`), dropping in halves
and always keeping the first; `xLabels` takes a number to keep every nth
instead, or `none`. End labels are pushed apart when two lines finish close
together, 14px at least.

## Smooth

`curve="smooth"` is a **monotone** cubic (Fritsch–Carlson, as d3's
`curveMonotoneX`), computed in PHP. Between two values it never rises above
the higher or dips below the lower, so the curve never shows a peak or a
trough the data does not have. Catmull-Rom and other splines do.

## Data

| Case | Drawn | Summary |
|---|---|---|
| `[]`, or nothing numeric | `ds::empty-state`, bare, with the `empty` title | — |
| one value | a point, centred | "Visits: 5." |
| all equal | a flat line in a band ±10% round it | "flat at 5" |
| `null` / non-numeric | a gap: the line and the area stop and restart; never bridged | skipped |
| a value alone between gaps | its point, always | |
| a ninth series | throws `InvalidArgumentException` | — |

The value axis fits the data, and includes zero when `area` is on (an
area from anything else lies about the volume).

## Series identity

specs/chart-series.md: a legend from two series up, and at four or fewer
each line also named at its end. `legend="auto"` (the default) does both.
`legend="end"` names the lines at their ends **instead** of the legend, for
a small chart that has no room for one; past four series it falls back to
`auto`. `legend="bottom"` is the legend alone.

## The summary

`role="img"`, named by `Chart::summary()` per series: the trend between the
first and last values and the range. "Visits: up from 320 to 810 (low 320,
high 810)." With several series, the label, then one sentence each.
`summary` replaces it; `table` adds every value as a visually hidden table,
a row per point and a column per series.

## Accessibility

| | Without the script | With it (`interactive`, the default) |
|---|---|---|
| Role | `img` on the svg | `group`, keeping the name; each point `img`, named "8 Nov, New: 700" |
| Keyboard | none | one Tab stop per chart, then the keys below |
| Pointer | none | the nearest point across, then the nearest series there: points are small |
| Readout | none | on hover, tap and focus; `aria-hidden` |
| Legend | a list | toggle buttons, `aria-pressed` |

| Key | |
|---|---|
| ArrowRight / ArrowLeft | the next / previous point along the line (mirrored in RTL) |
| ArrowDown / ArrowUp | the same point in the next / previous series that is shown |
| Home / End | the first / last point |
| Escape | puts the readout away (stops there only while a readout is shown) |
| Enter / Space on a legend entry | hides or shows that series |

A hidden series leaves the tab order; nothing is re-scaled. The last series
shown stays.

## Do not

- **Do not bridge a gap.** A line across missing data invents values.
- **Do not use a spline that overshoots.** `smooth` is monotone for that reason.
- **Do not drop the end labels at four series or fewer** without the legend.
- **Do not put two units on one axis.** Dual axes are out of scope; draw two charts.
- **Do not stack areas.** Out of scope: a stacked area reads as a total, and each band's own trend is lost.
