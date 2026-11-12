<?php

/*
 * The chart recipe: the colour a chart mark takes for each tone and each
 * series slot, as literal classes. Read through Support\Chart::colour(),
 * never copied. A plain PHP file under resources/views for the reason
 * partials/field.php is: a consumer's Tailwind scans resources/views and
 * nothing else, so a class written in src/ would compile in this repo and be
 * missing in every app.
 *
 * Every mark draws in currentColor, so one class colours the stroke, the fill
 * and the dot together.
 */

return [
    /*
     * The semantic tones, for a chart of ONE thing whose colour says
     * something: a success rate in success, a cost in neutral. Neutral is
     * fg-muted rather than the progress bar's fg: a chart sits beside its
     * number as the secondary reading, and a near-black line outweighs the
     * figure it is there to explain.
     */
    'tones' => [
        'neutral' => 'text-fg-muted',
        'accent' => 'text-accent',
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
    ],

    /*
     * The identity channel, for a chart that is one of several: series 3
     * means "the third thing", nothing more. specs/chart-series.md: slots in
     * order, never a slot chosen for a business value.
     */
    'series' => [
        1 => 'text-series-1',
        2 => 'text-series-2',
        3 => 'text-series-3',
        4 => 'text-series-4',
        5 => 'text-series-5',
        6 => 'text-series-6',
        7 => 'text-series-7',
        8 => 'text-series-8',
    ],

    /*
     * The hue an activity heatmap derives its five steps from, set as
     * --ds-heat on the table; the heat-0..4 utilities theme.css builds from
     * tokens/chart.json mix it. Same tones and slots as above.
     */
    'heat' => [
        'tones' => [
            'neutral' => '[--ds-heat:var(--color-fg-muted)]',
            'accent' => '[--ds-heat:var(--color-accent)]',
            'success' => '[--ds-heat:var(--color-success)]',
            'warning' => '[--ds-heat:var(--color-warning)]',
            'danger' => '[--ds-heat:var(--color-danger)]',
        ],
        'series' => [
            1 => '[--ds-heat:var(--color-series-1)]',
            2 => '[--ds-heat:var(--color-series-2)]',
            3 => '[--ds-heat:var(--color-series-3)]',
            4 => '[--ds-heat:var(--color-series-4)]',
            5 => '[--ds-heat:var(--color-series-5)]',
            6 => '[--ds-heat:var(--color-series-6)]',
            7 => '[--ds-heat:var(--color-series-7)]',
            8 => '[--ds-heat:var(--color-series-8)]',
        ],
        'steps' => ['heat-0', 'heat-1', 'heat-2', 'heat-3', 'heat-4'],
    ],

    /*
     * The same five steps as fills, for a mark drawn in SVG (a treemap tile,
     * a funnel's stage), which takes `fill` where a cell takes a background.
     * fill-heat-0..4 are built from tokens/chart.json beside heat-0..4.
     */
    'heatFill' => [1 => 'fill-heat-1', 2 => 'fill-heat-2', 3 => 'fill-heat-3', 4 => 'fill-heat-4'],

    /*
     * Text drawn ON a mark (a treemap tile's label, a value inside a funnel
     * stage): whichever of zinc-950 and white reads better on that fill, in
     * each theme, chosen by Support\Ink and held to it by InkTest. Keyed by
     * the hue and the heat step the mark is filled with; step 3 is the hue
     * itself, a plain text-series-N or tone mark. Four classes cover the four
     * answers: dark in both themes, light in both, and the two that swap,
     * which are fg (dark, then light) and on-inverse (light, then dark). The
     * text is drawn in currentColor, so the docs' contrast pass reads it.
     * Light-theme contrast of each pairing is in specs/treemap.md.
     */
    'ink' => [
        'neutral' => [1 => 'text-fg', 2 => 'text-zinc-950', 3 => 'text-on-inverse', 4 => 'text-on-inverse'],
        'accent' => [1 => 'text-fg', 2 => 'text-zinc-950', 3 => 'text-on-inverse', 4 => 'text-on-inverse'],
        'success' => [1 => 'text-fg', 2 => 'text-zinc-950', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
        'warning' => [1 => 'text-fg', 2 => 'text-zinc-950', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
        'danger' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-on-inverse', 4 => 'text-on-inverse'],
        'series-1' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
        'series-2' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
        'series-3' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
        'series-4' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
        'series-5' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
        'series-6' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-white', 4 => 'text-on-inverse'],
        'series-7' => [1 => 'text-fg', 2 => 'text-white', 3 => 'text-on-inverse', 4 => 'text-on-inverse'],
        'series-8' => [1 => 'text-fg', 2 => 'text-fg', 3 => 'text-zinc-950', 4 => 'text-on-inverse'],
    ],

    // What the empty part of a gauge or the unfilled track is drawn in: the
    // progress ring's track, so the two read as one family.
    'track' => 'text-fg/10',

    /*
     * The axes of a bar or line chart. Gridlines in the hairline border
     * colour, so they sit behind the marks; the zero line one step
     * stronger, because a bar's length is read from it. Tick and category
     * labels at the meta size, muted, in figures that line up.
     */
    'grid' => 'text-border',
    'zero' => 'text-border-strong',
    'tick' => 'fill-current text-meta tabular-nums text-fg-muted',
    // A value printed on its bar, and a line's name at its end: the body
    // colour, never the series colour, which is too faint to read as text
    // (specs/chart-series.md: no series token on text).
    'value' => 'fill-current text-meta font-medium tabular-nums text-fg',

    /*
     * The readout x-ds-chart shows beside a mark: the tooltip's surface,
     * class for class, so a chart's readout and a tooltip are one thing.
     * Its swatch takes the series colour from a data-series attribute the
     * script writes, because the script never adds a class.
     */
    'readout' => 'pointer-events-none absolute left-0 top-0 z-50 w-max max-w-56 rounded-control bg-surface-inverse px-2 py-1 text-meta text-on-inverse shadow-float',
    'readoutSwatch' => implode(' ', [
        'hidden size-2 shrink-0 rounded-full bg-current data-series:inline-block',
        'data-[series=1]:text-series-1 data-[series=2]:text-series-2 data-[series=3]:text-series-3 data-[series=4]:text-series-4',
        'data-[series=5]:text-series-5 data-[series=6]:text-series-6 data-[series=7]:text-series-7 data-[series=8]:text-series-8',
        'data-[series=other]:text-fg-subtle',
    ]),

    /*
     * The room beside the plot for the labels there, by the px it needs,
     * rounded up to the next of these. The width of a label is estimated in
     * PHP (Chart::textWidth()), never measured, so the gutter is a literal
     * class a consumer's Tailwind can see.
     */
    'gutters' => [
        'left' => [16 => 'pl-4', 24 => 'pl-6', 32 => 'pl-8', 40 => 'pl-10', 48 => 'pl-12', 56 => 'pl-14', 64 => 'pl-16', 80 => 'pl-20', 96 => 'pl-24', 112 => 'pl-28', 128 => 'pl-32', 144 => 'pl-36', 160 => 'pl-40', 176 => 'pl-44', 192 => 'pl-48'],
        'right' => [0 => 'pr-0', 8 => 'pr-2', 16 => 'pr-4', 24 => 'pr-6', 32 => 'pr-8', 40 => 'pr-10', 48 => 'pr-12', 56 => 'pr-14', 64 => 'pr-16', 80 => 'pr-20', 96 => 'pr-24', 112 => 'pr-28', 128 => 'pr-32', 144 => 'pr-36', 160 => 'pr-40', 176 => 'pr-44', 192 => 'pr-48'],
    ],

    /*
     * An axis label hidden while the chart's box is narrower than the
     * container size named, so a crowded axis thins out as the box narrows
     * with no script and nothing measured (Chart::thinning()). Tailwind's
     * own container sizes, in px.
     */
    'below' => [
        256 => '@max-3xs:hidden', 288 => '@max-2xs:hidden', 320 => '@max-xs:hidden', 384 => '@max-sm:hidden',
        448 => '@max-md:hidden', 512 => '@max-lg:hidden', 576 => '@max-xl:hidden', 672 => '@max-2xl:hidden',
        768 => '@max-3xl:hidden', 896 => '@max-4xl:hidden', 1024 => '@max-5xl:hidden', 1152 => '@max-6xl:hidden',
        1280 => '@max-7xl:hidden',
    ],

    // The exact complement of `below`, for a stand-in shown only while the
    // label it replaces is hidden: a split bar's legend entry for a segment
    // too narrow to carry its name (Chart::above()).
    'above' => [
        256 => '@3xs:hidden', 288 => '@2xs:hidden', 320 => '@xs:hidden', 384 => '@sm:hidden',
        448 => '@md:hidden', 512 => '@lg:hidden', 576 => '@xl:hidden', 672 => '@2xl:hidden',
        768 => '@3xl:hidden', 896 => '@4xl:hidden', 1024 => '@5xl:hidden', 1152 => '@6xl:hidden',
        1280 => '@7xl:hidden',
    ],
];
