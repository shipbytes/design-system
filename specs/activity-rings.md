# Activity rings

Up to four concentric rings, each progress toward a goal of its own: calories,
minutes and hours on one day, or each region against its target. SVG circles
with a round-capped dash, each on a faint track of its own colour, outermost
first.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="both" class="relative flex … [direction:ltr]">   ← x-ds-chart only when `interactive`
    <div class="relative rounded-full size-40 has-focus-visible:outline-2 …">
        <svg data-ds-chart-plot role="img" aria-label="…summary…" viewBox="0 0 100 100">
            <g class="text-series-1">
                <circle r="44.17" stroke-width="9.67" class="opacity-20"            ← the track, and the mark
                        data-ds-chart-mark aria-label="Move: 420 of 600 (70%)"/>
                <circle r="44.17" stroke-linecap="round" stroke-dasharray="dash C"
                        stroke-dashoffset="-w/2" transform="rotate(-90 50 50)"/>    ← the progress
            </g>
            …
        </svg>
        <div class="absolute" style="inset: N%">the `centre` slot</div>
    </div>
    <ul role="list">legend: swatch, name, "420 / 600 · 70%"</ul>
    <table class="sr-only">Ring, Value, Goal, Share</table>
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>
</div>
```

| Part | Token | Why |
|---|---|---|
| Rings | `series-1` … `series-4` by position; a row's `tone` or `series` | specs/chart-series.md |
| Track | the ring's own colour at 20%; 40% while it is being read | One ring reads as one thing, done and still to do. |
| Size | `sm` 128, `md` 160, `lg` 208px | as the donut |
| Thickness | `sm` 7, `md` 10, `lg` 13 (of 100), 2 between rings | narrowed by `Chart::rings()` when the rings would leave a hole under 16 |

## Geometry

`Chart::rings()` gives the radii, outermost first, and one width for all;
`Chart::ring()` gives each the dash. A round cap reaches half the stroke's
width past each end of a dash, so the dash is shortened by one width and
started half a width in: the drawn ring runs from twelve o'clock to exactly
its share. A sliver shorter than its caps is a dot. Zero draws no progress,
only the track.

**Past the goal, the ring is drawn full and no further.** A second lap would
need a darker overlap to be seen, a cue only colour gives; so the real
figure is said instead, in the legend ("14 / 12 · 117%"), the readout, the
summary and the table. This is the simpler of the two honest options and is
documented as such.

There is no entry animation: optional in the brief, and a ring that sweeps in
says nothing the static one does not.

## Data

| Case | Result |
|---|---|
| rows with `label`, `value`, `max` (default 100), `tone` / `series`, `format` | one ring each; a row's `format` is merged over the chart's |
| `['North' => 82, …]` | value against 100 |
| a negative or non-numeric value | **throws**, naming the ring |
| a `max` of zero or less | **throws** |
| more than four rings | **throws**: past four each is too thin to read and the middle has no room |
| `[]` | `ds::empty-state`, bare |

## The summary

Every ring, since there are at most four and they are the point: "Today: Move
420 kcal of 600 kcal (70%), Exercise 25 of 30 (83%), Stand 14 of 12 (117%)."
`summary` replaces it; `table` adds ring, value, goal and share.

## Accessibility

| | Without the script | With it |
|---|---|---|
| Role | `img` | `group`, keeping the name; each ring's track `img`, "Stand: 14 of 12 (117%)" |
| Keyboard | none | one Tab stop; any arrow moves ring to ring, Home / End, Escape |
| Focus ring | — | round the whole set (`has-focus-visible`), as on the donut; the read ring's track brightens |
| Readout | — | on hover anywhere on a ring, tap and focus |

The track is the mark, so a ring at zero is still reachable and the pointer
reads a ring anywhere on it.

The `centre` slot is not hidden: put in it only what the summary or legend
also says, or it is read twice.

## Do not

- **Do not compare rings with each other.** Each is against its own goal;
  the outer ring is longer at the same share.
- **Do not use it for parts of a whole.** That is a donut.
- **Do not use it for one reading in a range.** That is `ds::gauge`; one
  task's completion is `ds::progress`.
