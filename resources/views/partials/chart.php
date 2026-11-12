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

    // What the empty part of a gauge or the unfilled track is drawn in: the
    // progress ring's track, so the two read as one family.
    'track' => 'text-fg/10',
];
