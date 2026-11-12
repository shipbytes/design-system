# Funnel chart

How many reached each step of a sequence: visits, sign-ups, trials, paid. A
bar per stage, centred so the funnel reads as one shape narrowing, the
drop from each stage to the next written between them. Suits three to about
eight stages.

## Anatomy

```
<div x-data x-ds-chart data-ds-chart-axis="vertical"
     class="relative flex flex-col gap-3 [direction:ltr] heat-accent">        ← x-ds-chart only when `interactive`
    <div class="@container">
        <div class="pl-… pr-…">                                               ← gutters from the labels' widths
            <svg data-ds-chart-plot role="img" aria-label="…summary…" width="100%" height="…">
                <svg viewBox="0 0 100 H" preserveAspectRatio="none" aria-hidden="true">  ← shape="funnel" only
                    <polygon class="fill-heat-3 opacity-30"/> …                ← the join between two stages
                </svg>
                <rect x="37.5%" width="25%" class="fill-heat-2" data-ds-chart-mark data-ds-at="1"
                      data-ds-label="Sign-ups" data-ds-value="3,100 (25% of Visits)"
                      aria-label="Sign-ups: 3,100, 25% of Visits"/>
                …
                <g aria-hidden="true">names in the gutter, values inside or beside each bar, "−75%" between stages</g>
            </svg>
        </div>
    </div>
    <table class="sr-only">Stage, Value, Of the stage before, Of the first</table>   ← `table` only
    <div data-ds-chart-readout hidden aria-hidden="true">…</div>
</div>
```

| Part | Token | Why |
|---|---|---|
| Bars | one hue (`tone`, accent by default) fading from step 3 (the hue itself) to step 1 (`ChartShapes::ramp()`), as `fill-heat-N` | the stages are one thing getting smaller, not several things: one colour, not a series each |
| A row's own `tone` or `series` | that colour at full strength | for the one stage that needs calling out |
| Joins (`shape="funnel"`) | the upper stage's fill at 30% | read as connective tissue, not data |
| Text inside a bar | `Ink::label()`: the better of `zinc-950` and white on that fill, per theme | contrast table in specs/treemap.md |
| Names, values outside, the drop | `text-meta`, `fg`; the value `font-medium`; the share of the first `fg-muted` beside a bar | never a series colour on text |
| Band (vertical) | `sm` 28, `md` 36, `lg` 44px a stage; 24px between stages with the drop, 12 with joins, 6 otherwise | |
| Plot (horizontal) | `sm` 128, `md` 192, `lg` 256px tall | |

## Layout

The frame of `ds::bar-chart`: no viewBox, so text keeps its size; x in
percent of the plot, y in px.

**Vertical** (the default): a band per stage, top to bottom; the bar
centred, as wide as its share of the **largest** stage. Names in the left
gutter, cut to 120px at most. The value sits inside the bar while the
bar is wide enough for it, and beside it, to the right, once it is not: the
two are swapped by container query (`Chart::below()` / `Chart::above()`),
from the text's width (`Chart::textWidth()`) over the bar's share. The right
gutter is sized for the widest value that ends up outside in the narrowest
box planned for (256px).

**Horizontal**: a slot per stage, left to right; a bar half its slot wide,
centred on the middle line. Values above the plot, names below (thinned by
`Chart::thinning()` when slots are narrow), the drop in the gap between two
bars. Each is hidden by container query once its slot is too narrow; the
readout and the table carry it then.

**A stage larger than the one before** is drawn larger: the scale is the
largest stage, not the first, and the drop reads "+13%". A funnel that
gets wider has something to say.

## Rates

`ChartShapes::rates()`, per stage: the share of the stage before (`step`),
the share of the first (`total`) and the change, signed ("−75%", "+13%",
"±0%"). A stage after a stage of nothing has no step. Percentages keep one
decimal under 10% (`ChartShapes::percent()`: "2.5%", "38%").

| `showRate` | On screen |
|---|---|
| `step` (default) | the change between every two stages |
| `total` | "· 25%" after each value |
| `both` | both |
| `none` | values only |

Every rate is also in each stage's name and in the table, whatever
`showRate` says.

## Data

| Case | Result |
|---|---|
| `['Visits' => 12400, …]`, or rows with `label`, `value`, `tone` / `series` | one stage each, in the order given |
| a value that is not a number, or below zero | **throws**, naming the stage |
| a stage of 0 | drawn as nothing; the next stage has no step rate |
| every stage 0, or `[]` | `ds::empty-state`, bare |

## The summary

`ChartShapes::funnel()`: "Signups this month: 5 stages, from Visits 12,400
to Paid 310 (2.5% of the first); the largest drop is Visits to Sign-ups
(−75%)." When no stage is smaller than the one before: "… no stage is
smaller than the one before it." `summary` replaces it; `table` adds
stage, value and both rates.

## Accessibility

| | Without the script | With it |
|---|---|---|
| Role | `img`, the summary | `group`, keeping the name; each bar `img`, "Trial: 1,200, 34% of Activated, 9.7% of Visits" |
| Keyboard | none | one Tab stop; the arrows along the stages (ArrowDown / ArrowUp when vertical, ArrowRight / ArrowLeft when horizontal), Home / End, Escape |
| Pointer | — | the bar under it |
| Readout | — | the stage, its value and its share of the stage before |

The change between stages is written as text with its sign, so a drop is
never told by colour or by size alone.

## Do not

- **Do not use it for parts of a whole.** Stages are each a subset of the
  one before; parts that add up are a bar chart, a donut or a treemap.
- **Do not reorder the stages by size.** The order is the sequence, and a
  stage that grows is information.
- **Do not give every stage its own colour.** The fade says "the same
  people, fewer of them"; a tone is for the one stage to look at.
