<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

use NumberFormatter;

/**
 * The arithmetic every chart component shares: values to a viewBox, the path
 * strings drawn from them, nice tick values, number formatting, and the
 * sentence a chart is read as.
 *
 * The charts are static. Geometry is computed here, once, in PHP, and the view
 * emits plain SVG or plain elements; nothing lays a chart out in the browser.
 * Every string this returns is exact and unit-tested (ChartTest), so a change
 * to a path is a change somebody chose.
 *
 * Coordinates: x grows to the right, y DOWN, as in SVG. Angles: degrees
 * clockwise from twelve o'clock.
 */
final class Chart
{
    /** The tones a chart mark takes, like every other component's. */
    public const TONES = ['neutral', 'accent', 'success', 'warning', 'danger'];

    /** Compact suffixes. English, and the same in the browser (dsCounter). */
    private const COMPACT = ['', 'K', 'M', 'B', 'T'];

    /** U+2212, not a hyphen: it reads as "minus" and is as wide as a plus. */
    public const MINUS = "\u{2212}";

    /** @var array<string, mixed>|null */
    private static ?array $recipe = null;

    /**
     * The colour classes, from resources/views/partials/chart.php. In that
     * file rather than here for the reason Support\Field gives: a class
     * written in src/ compiles in this repo's tests and is missing in every
     * app.
     *
     * @return array<string, mixed> tones, series, heat, track, and the axes, readout, gutters and container sizes of the larger charts
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/chart.php';
    }

    /**
     * The padding class that leaves at least `$px` beside the plot for its
     * labels: the next size up in the recipe, or the widest there is.
     */
    public static function gutter(float $px, string $side = 'left'): string
    {
        $sizes = self::recipe()['gutters'][$side];

        foreach ($sizes as $at => $class) {
            if ($at >= $px) {
                return $class;
            }
        }

        return end($sizes);
    }

    /**
     * The class that hides an axis label while the chart is narrower than
     * `$width` (from thinning()): none for null, `hidden` for a label that
     * never fits, and otherwise the container size at or just above it.
     */
    public static function below(?float $width): string
    {
        if ($width === null) {
            return '';
        }

        foreach (self::recipe()['below'] as $at => $class) {
            if ($at >= $width) {
                return $class;
            }
        }

        return 'hidden';
    }

    /** A tone the charts have: the one asked for, or `$default`. */
    public static function tone(?string $tone, string $default = 'neutral'): string
    {
        return in_array($tone, self::TONES, true) ? $tone : $default;
    }

    /** A series slot from 1 to 8, or null when none (or nonsense) was given. */
    public static function series(mixed $series): ?int
    {
        return is_numeric($series) && (int) $series >= 1 && (int) $series <= 8 && (float) $series === (float) (int) $series
            ? (int) $series
            : null;
    }

    /**
     * The class a mark is coloured with. A series slot, when one is given,
     * wins over the tone: a chart that is one of several says WHICH one it
     * is before it says anything else.
     */
    public static function colour(?string $tone, mixed $series = null, string $default = 'neutral'): string
    {
        $slot = self::series($series);

        return $slot !== null ? self::recipe()['series'][$slot] : self::recipe()['tones'][self::tone($tone, $default)];
    }

    /** The class that sets a heatmap's hue, by the same rule as colour(). */
    public static function heat(?string $tone, mixed $series = null, string $default = 'accent'): string
    {
        $slot = self::series($series);
        $heat = self::recipe()['heat'];

        return $slot !== null ? $heat['series'][$slot] : $heat['tones'][self::tone($tone, $default)];
    }

    /**
     * A coordinate as path text: at most two decimals, no trailing zeros, and
     * never "-0", which a diff of two otherwise identical paths would show.
     */
    public static function n(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $text === '-0' || $text === '' ? '0' : $text;
    }

    /**
     * The numbers in a list that are numbers: null, '' and anything else that
     * is not numeric is a gap, kept by key so the gap stays where it was.
     *
     * @return array<int|string, float|null>
     */
    public static function values(iterable $values): array
    {
        $out = [];

        foreach ($values as $key => $value) {
            $out[$key] = is_numeric($value) ? (float) $value : null;
        }

        return $out;
    }

    /**
     * [min, max] of the values, either end overridable. With `zero` the range
     * always includes 0, which a bar chart's baseline needs.
     *
     * @return array{0: float, 1: float}
     */
    public static function domain(iterable $values, ?float $min = null, ?float $max = null, bool $zero = false): array
    {
        $present = array_values(array_filter(self::values($values), fn ($v) => $v !== null));

        $lo = $min ?? ($present ? min($present) : 0.0);
        $hi = $max ?? ($present ? max($present) : 0.0);

        if ($zero) {
            $lo = $min ?? min($lo, 0.0);
            $hi = $max ?? max($hi, 0.0);
        }

        return $lo <= $hi ? [$lo, $hi] : [$hi, $lo];
    }

    /**
     * Where a value sits on a vertical axis `height` tall: max at the top, min
     * at the bottom, clamped into the box. A flat domain (every value equal)
     * puts everything on the middle line rather than dividing by zero.
     */
    public static function y(float $value, float $height, float $min, float $max): float
    {
        if ($max === $min) {
            return $height / 2;
        }

        $y = $height - ($value - $min) / ($max - $min) * $height;

        return max(0.0, min($height, $y));
    }

    /**
     * Each value as an [x, y] point across the box, evenly spaced from the left
     * edge to the right; a gap stays null. One value sits in the middle.
     *
     * @return array<int|string, array{0: float, 1: float}|null>
     */
    public static function points(iterable $values, float $width, float $height, ?float $min = null, ?float $max = null): array
    {
        $values = self::values($values);
        [$lo, $hi] = self::domain($values, $min, $max);
        $count = count($values);

        $points = [];
        $i = 0;

        foreach ($values as $key => $value) {
            $x = $count === 1 ? $width / 2 : $i / ($count - 1) * $width;
            $points[$key] = $value === null ? null : [$x, self::y($value, $height, $lo, $hi)];
            $i++;
        }

        return $points;
    }

    /**
     * A polyline through the points, as a path. A gap lifts the pen, so a
     * missing value is a break in the line and never a straight bridge over
     * it. A point with gaps on both sides is drawn as a zero-length segment,
     * which a round line cap turns into a dot: without it a lone value would
     * not be drawn at all.
     *
     * `smooth` draws each run as a monotone cubic (Fritsch and Carlson, the
     * curve d3 calls monotoneX): it passes through every point and never
     * overshoots one, so a smooth line cannot show a peak or a dip the data
     * does not have, which a Catmull-Rom curve does between steep neighbours.
     */
    public static function line(array $points, bool $smooth = false): string
    {
        $path = '';

        foreach (self::runs($points) as $run) {
            $path .= 'M'.self::n($run[0][0]).' '.self::n($run[0][1]);
            $path .= count($run) === 1 ? 'h0' : self::through($run, $smooth);
        }

        return $path;
    }

    /**
     * The region between the line and a baseline, closed, one shape per run
     * of values. A lone value has no area, so it adds nothing.
     */
    public static function area(array $points, float $baseline, bool $smooth = false): string
    {
        $path = '';
        $base = self::n($baseline);

        foreach (self::runs($points) as $run) {
            if (count($run) < 2) {
                continue;
            }

            $path .= 'M'.self::n($run[0][0]).' '.$base;
            $path .= 'L'.self::n($run[0][0]).' '.self::n($run[0][1]);
            $path .= self::through($run, $smooth);
            $path .= 'L'.self::n(end($run)[0]).' '.$base.'Z';
        }

        return $path;
    }

    /**
     * A bar per value, each in an equal slot with `gap` between neighbours,
     * standing on zero: up for a positive value, down for a negative one.
     * The domain always includes zero, or a bar would stand on the lowest
     * value and a small positive one would read as nothing.
     *
     * @return array<int|string, array{x: float, y: float, width: float, height: float}|null>
     */
    public static function bars(iterable $values, float $width, float $height, ?float $min = null, ?float $max = null, float $gap = 1.0): array
    {
        $values = self::values($values);
        [$lo, $hi] = self::domain($values, $min, $max, zero: true);
        $count = max(1, count($values));

        $slot = $width / $count;
        $bar = max($slot - $gap, $slot / 2);
        $base = self::baseline($height, $lo, $hi);

        $bars = [];
        $i = 0;

        foreach ($values as $key => $value) {
            if ($value === null) {
                $bars[$key] = null;
            } else {
                $y = self::y($value, $height, $lo, $hi);
                $bars[$key] = [
                    'x' => $i * $slot + ($slot - $bar) / 2,
                    'y' => min($y, $base),
                    'width' => $bar,
                    'height' => abs($base - $y),
                ];
            }
            $i++;
        }

        return $bars;
    }

    /** Where zero sits on the axis: the line a bar stands on and an area fills to. */
    public static function baseline(float $height, float $min, float $max): float
    {
        if ($max === $min) {
            return $height;
        }

        return self::y(max($min, min($max, 0.0)), $height, $min, $max);
    }

    /**
     * A circular arc from one angle to another, clockwise, as a path. Degrees
     * from twelve o'clock, so a semicircle over the top runs -90 to 90. A full
     * turn is drawn as two halves, because an SVG arc whose ends meet draws
     * nothing.
     */
    public static function arc(float $cx, float $cy, float $r, float $from, float $to): string
    {
        $sweep = $to - $from;

        if ($sweep >= 360) {
            return self::arc($cx, $cy, $r, $from, $from + 180)
                .self::arcTo($cx, $cy, $r, $from + 360, false);
        }

        [$x, $y] = self::polar($cx, $cy, $r, $from);

        return 'M'.self::n($x).' '.self::n($y).self::arcTo($cx, $cy, $r, $to, $sweep > 180);
    }

    /** The length of an arc of `degrees` on a circle of radius `r`. */
    public static function arcLength(float $r, float $degrees): float
    {
        return $r * deg2rad(min(360.0, max(0.0, $degrees)));
    }

    /** The point at an angle on a circle. @return array{0: float, 1: float} */
    public static function polar(float $cx, float $cy, float $r, float $degrees): array
    {
        $rad = deg2rad($degrees);

        return [$cx + $r * sin($rad), $cy - $r * cos($rad)];
    }

    /**
     * Round values for an axis: about `count` of them, on steps of 1, 2 or 5
     * times a power of ten, covering min to max. Heckbert's nice numbers.
     *
     * @return list<float>
     */
    public static function ticks(float $min, float $max, int $count = 5): array
    {
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        if ($min === $max) {
            return [$min];
        }

        $count = max(2, $count);
        $step = self::nice(self::nice($max - $min, false) / ($count - 1), true);
        $decimals = max(0, (int) -floor(log10($step)));

        $ticks = [];
        for ($v = floor($min / $step) * $step; $v <= ceil($max / $step) * $step + $step / 2; $v += $step) {
            $ticks[] = round($v, $decimals) + 0.0;
        }

        return $ticks;
    }

    /**
     * A number as a reader sees it. `$format` keys, all optional:
     *
     *   decimals  int: exactly this many places. Default: up to 2, trailing
     *             zeros dropped (up to 1 when compact).
     *   grouping  bool, default true: 12,400 rather than 12400.
     *   prefix    string before the number, after any minus sign: "$".
     *   suffix    string after it: "%", " ms".
     *   compact   bool: 12,400 becomes 12.4K. K, M, B, T, in English.
     *   locale    default the app's. Separators come from intl when PHP has
     *             it, and are English otherwise.
     *
     * A negative number takes U+2212 before the prefix: −$1,200. Null is a
     * long dash, the system's "no value".
     */
    public static function format(int|float|string|null $value, array $format = []): string
    {
        if (! is_numeric($value)) {
            return '—';
        }

        $o = self::formatOptions($format);
        $number = (float) $value;
        $unit = 0;

        if ($o['compact']) {
            while (abs($number) >= 1000 && $unit < count(self::COMPACT) - 1) {
                $number /= 1000;
                $unit++;
            }

            // 999,950 is 1,000K once rounded, and should have been 1M.
            if (round(abs($number), $o['maximumFractionDigits']) >= 1000 && $unit < count(self::COMPACT) - 1) {
                $number /= 1000;
                $unit++;
            }
        }

        $rounded = round(abs($number), $o['maximumFractionDigits']);
        $sign = $number < 0 && $rounded > 0 ? self::MINUS : '';

        return $sign.$o['prefix'].self::digits($rounded, $o).self::COMPACT[$unit].$o['suffix'];
    }

    /**
     * The format, resolved: what format() uses, and what a component hands to
     * the browser so a number formatted there (dsCounter) matches one
     * formatted here. Its keys are Intl.NumberFormat's own.
     *
     * @return array{locale: string, minimumFractionDigits: int, maximumFractionDigits: int, useGrouping: bool, prefix: string, suffix: string, compact: bool}
     */
    public static function formatOptions(array $format = []): array
    {
        $compact = (bool) ($format['compact'] ?? false);
        $decimals = isset($format['decimals']) && is_numeric($format['decimals'])
            ? max(0, min(6, (int) $format['decimals']))
            : null;

        $locale = (string) ($format['locale'] ?? (function_exists('app') && app()->bound('translator') ? app()->getLocale() : 'en'));

        return [
            // Without intl the separators are English, so the browser is told
            // English too, or the two would disagree on the first roll.
            'locale' => extension_loaded('intl') ? str_replace('_', '-', $locale ?: 'en') : 'en',
            'minimumFractionDigits' => $decimals ?? 0,
            'maximumFractionDigits' => $decimals ?? ($compact ? 1 : 2),
            'useGrouping' => (bool) ($format['grouping'] ?? true),
            'prefix' => (string) ($format['prefix'] ?? ''),
            'suffix' => (string) ($format['suffix'] ?? ''),
            'compact' => $compact,
        ];
    }

    /**
     * The lowest count that reaches each level above zero, ascending.
     *
     * `$levels` as a number (2 to 5, counting the zero level) splits the
     * non-zero counts at quantiles: the first threshold is the smallest
     * non-zero count, so every day with something is at least level 1, and
     * each next one a further equal share of the days up. Equal counts can
     * merge two thresholds, and then fewer levels are used; nothing is
     * invented to fill them. As a list, it is the thresholds, given.
     *
     * @return list<float>
     */
    public static function thresholds(iterable $counts, int|array $levels = 5): array
    {
        if (is_array($levels)) {
            $given = array_values(array_unique(array_map('floatval', array_filter($levels, 'is_numeric'))));
            sort($given);

            return array_slice(array_values(array_filter($given, fn ($t) => $t > 0)), 0, 4);
        }

        $levels = max(2, min(5, $levels));
        $present = array_values(array_filter(self::values($counts), fn ($v) => $v !== null && $v > 0));
        sort($present);

        if ($present === []) {
            return [];
        }

        // Level k (1 up) starts (k - 1) / (levels - 1) of the way through the
        // sorted counts, so with five levels each of 1 to 4 holds about a
        // quarter of the days that had anything.
        $thresholds = [];

        for ($k = 1; $k < $levels; $k++) {
            $thresholds[] = $present[min(count($present) - 1, (int) floor(($k - 1) / ($levels - 1) * count($present)))];
        }

        return array_values(array_unique($thresholds, SORT_REGULAR));
    }

    /** The level a count reaches: 0 for nothing, else the highest threshold it meets. */
    public static function level(int|float|null $count, array $thresholds): int
    {
        $level = 0;

        foreach (array_values($thresholds) as $i => $threshold) {
            if ($count !== null && $count > 0 && $count >= $threshold) {
                $level = $i + 1;
            }
        }

        return $level;
    }

    /**
     * Which of the five colour steps (0 to 4) each level draws with. Five
     * levels take all five; fewer are spread across them so the lightest and
     * the darkest are always the ends.
     *
     * @return list<int>
     */
    public static function steps(int $levels): array
    {
        $levels = max(2, min(5, $levels));

        return array_map(fn ($k) => (int) round($k * 4 / ($levels - 1)), range(0, $levels - 1));
    }

    /**
     * A chart's data in one shape, whichever of the shapes it was given in:
     *
     *   ['Jan' => 12, 'Feb' => 18]                         one series
     *   ['Jan' => ['Revenue' => 12, 'Costs' => 8], …]      several, by name
     *   ['Jan' => [12, 8], …] with $series ['Revenue', 'Costs']
     *
     * `$series` names them: a list, or one string for a single series. Names
     * not given are taken from the rows' keys in the order first met, or
     * numbered. A list of values (no keys) has its categories numbered from
     * 1. A value that is not a number is a gap, as everywhere here.
     *
     * Eight series at most: the series tokens stop there on purpose
     * (specs/chart-series.md), and a ninth colour would be invented.
     *
     * @return array{labels: list<string>, series: list<string>, rows: list<list<float|null>>}
     */
    public static function dataset(iterable $data, mixed $series = null): array
    {
        $data = is_array($data) ? $data : iterator_to_array($data);
        $names = match (true) {
            is_string($series) && trim($series) !== '' => [trim($series)],
            is_iterable($series) => array_values(array_map('strval', is_array($series) ? $series : iterator_to_array($series))),
            default => [],
        };

        $labels = [];
        $i = 0;
        foreach (array_keys($data) as $key) {
            $i++;
            $labels[] = array_is_list($data) ? (string) $i : (string) $key;
        }

        $multi = $data !== [] && collect($data)->contains(fn ($row) => is_iterable($row));

        if (! $multi) {
            return ['labels' => $labels, 'series' => [$names[0] ?? ''], 'rows' => [array_values(self::values($data))]];
        }

        $rows = array_map(fn ($row) => is_iterable($row) ? (is_array($row) ? $row : iterator_to_array($row)) : [$row], array_values($data));

        if ($names === []) {
            foreach ($rows as $row) {
                foreach (array_keys($row) as $key) {
                    if (is_string($key) && ! in_array($key, $names, true)) {
                        $names[] = $key;
                    }
                }
            }
            $widest = max(array_map('count', $rows));
            for ($k = count($names); $k < $widest; $k++) {
                $names[] = 'Series '.($k + 1);
            }
        }

        if (count($names) > 8) {
            throw new \InvalidArgumentException(
                'A chart draws at most 8 series, one per series colour, and was given '.count($names).'. '
                .'Fold the smallest into an "Other" series, or draw several smaller charts (specs/chart-series.md).'
            );
        }

        $out = [];
        foreach ($names as $k => $name) {
            $out[] = array_map(function (array $row) use ($name, $k) {
                $value = array_key_exists($name, $row) ? $row[$name] : (array_is_list($row) ? ($row[$k] ?? null) : null);

                return is_numeric($value) ? (float) $value : null;
            }, $rows);
        }

        return ['labels' => $labels, 'series' => $names, 'rows' => $out];
    }

    /**
     * The value axis: a domain that starts and ends on a round tick, and the
     * ticks. `$ticks` is roughly how many (default 5), or the tick values
     * themselves, which then also widen the domain to include them. With
     * `zero` the domain always includes 0, as a bar's baseline needs.
     *
     * A domain of one value is widened, so a chart of equal values draws a
     * line through the middle rather than dividing by nothing: by a tenth
     * either side, or to 0..1 when every value is 0.
     *
     * @return array{min: float, max: float, ticks: list<float>}
     */
    public static function scale(iterable $values, bool $zero = false, int|array|null $ticks = null): array
    {
        $values = array_values(array_filter(self::values($values), fn ($v) => $v !== null));

        if (is_array($ticks)) {
            $given = array_values(array_unique(array_map('floatval', array_filter($ticks, 'is_numeric'))));
            sort($given);

            if ($given !== []) {
                [$lo, $hi] = self::domain(array_merge($values, $given), zero: $zero);

                return ['min' => $lo, 'max' => $hi === $lo ? $lo + 1 : $hi, 'ticks' => $given];
            }
        }

        [$lo, $hi] = self::domain($values, zero: $zero);

        if ($lo === $hi && $lo == 0) {
            return ['min' => 0.0, 'max' => 1.0, 'ticks' => [0.0, 1.0]];
        }

        if ($lo === $hi) {
            // With `zero` this cannot happen for a non-zero value: the domain
            // already runs from it to 0.
            [$lo, $hi] = [$lo - abs($lo) / 10, $hi + abs($hi) / 10];
        }

        $list = self::ticks($lo, $hi, is_int($ticks) ? max(2, $ticks) : 5);

        return ['min' => min($list[0], $lo), 'max' => max(end($list), $hi), 'ticks' => $list];
    }

    /** Where a value falls between min and max, from 0 to 1, clamped. */
    public static function fraction(float $value, float $min, float $max): float
    {
        return $max === $min ? 0.5 : max(0.0, min(1.0, ($value - $min) / ($max - $min)));
    }

    /**
     * Each series stacked on the ones before it, per category: positive values
     * upward from zero, negative ones downward, so a negative never eats into
     * the positive stack. A gap adds nothing and is null.
     *
     * @param  list<list<float|null>>  $rows  a row of values per series
     * @return array{segments: list<list<array{0: float, 1: float}|null>>, totals: list<float>}
     *                                         [from, to] per series and category,
     *                                         and every stack's two ends, for the scale
     */
    public static function stack(array $rows): array
    {
        // The running top of each category's stack, one for each direction.
        $ends = ['up' => [], 'down' => []];
        $segments = [];

        foreach ($rows as $s => $row) {
            foreach ($row as $i => $value) {
                if ($value === null) {
                    $segments[$s][$i] = null;
                    continue;
                }
                $side = $value >= 0 ? 'up' : 'down';
                $from = $ends[$side][$i] ?? 0.0;
                $ends[$side][$i] = $from + $value;
                $segments[$s][$i] = [$from, $from + $value];
            }
        }

        return ['segments' => $segments, 'totals' => array_values(array_merge($ends['up'], $ends['down']))];
    }

    /**
     * Whole percentages that add up to 100, by the largest remainder: 33, 33
     * and 34 for three equal shares, never 33, 33 and 33. Every value must be
     * positive; the donut refuses anything else before it gets here.
     *
     * @return list<int>
     */
    public static function percentages(array $values): array
    {
        $values = array_values(array_map('floatval', $values));
        $total = array_sum($values);

        if ($total <= 0) {
            return array_fill(0, count($values), 0);
        }

        $exact = array_map(fn ($v) => $v / $total * 100, $values);
        $whole = array_map(fn ($v) => (int) floor($v), $exact);
        $left = 100 - array_sum($whole);

        $order = array_keys($exact);
        usort($order, fn ($a, $b) => ($exact[$b] - $whole[$b]) <=> ($exact[$a] - $whole[$a]) ?: $a <=> $b);

        foreach (array_slice($order, 0, $left) as $i) {
            $whole[$i]++;
        }

        return $whole;
    }

    /**
     * How wide a line of text is at the chart's type size (text-meta, 12px),
     * estimated from the characters, in px. The charts lay out without
     * measuring in the browser, so the gutter beside an axis and the point a
     * label is cut short are worked out here, generously: an estimate that
     * runs wide leaves a little air, one that runs narrow overprints.
     */
    public static function textWidth(string $text, float $size = 12.0): float
    {
        $em = 0.0;

        foreach (mb_str_split($text) as $char) {
            $em += match (true) {
                $char === ' ' => 0.28,
                str_contains('.,:;\'|!il', $char) => 0.3,
                str_contains('fjrt()[]', $char) => 0.4,
                str_contains('mwMW', $char) => 0.88,
                ctype_digit($char) => 0.62,
                $char !== mb_strtolower($char) => 0.7,
                default => 0.56,
            };
        }

        return $em * $size;
    }

    /**
     * Text cut to fit a width, ending in an ellipsis. Unchanged when it fits.
     * The view keeps the whole text in a title, so it is never lost.
     */
    public static function truncate(string $text, float $width, float $size = 12.0): string
    {
        if (self::textWidth($text, $size) <= $width) {
            return $text;
        }

        $chars = mb_str_split($text);
        while ($chars !== [] && self::textWidth(rtrim(implode('', $chars)).'…', $size) > $width) {
            array_pop($chars);
        }

        return $chars === [] ? '…' : rtrim(implode('', $chars)).'…';
    }

    /**
     * Which axis labels to drop when the chart is too narrow for all of them,
     * as the narrowest container width at which each label is shown: null for
     * one that always is, INF for one that never fits.
     *
     * The chart's width is not known here, and is not measured: the view
     * turns each width into a container query, so the browser hides labels
     * as the box narrows, with no script. Labels are dropped in halves:
     * every second one first, then every fourth, so what is left is always
     * evenly spaced, and the first is always kept.
     *
     *   $count      how many labels
     *   $need       px each one needs: its estimated width and a gap
     *   $reserved   px of the container that is not the axis (the gutter)
     *   $intervals  how many label-to-label spaces the axis is cut into: the
     *               number of bands for bars, one fewer for points on a line
     *   $narrowest  the narrowest container worth planning for
     *
     * @return list<float|null>
     */
    public static function thinning(int $count, float $need, float $reserved, int $intervals, float $narrowest = 256.0): array
    {
        $intervals = max(1, $intervals);
        // The container width at which labels `stride` apart have room.
        $width = fn (int $stride) => $reserved + $intervals * $need / $stride;

        // The widest stride needed: at it, even the narrowest box fits.
        $widest = 1;
        while ($width($widest) > $narrowest && $widest < $count) {
            $widest *= 2;
        }

        $out = [];
        for ($i = 0; $i < $count; $i++) {
            // The largest power of two dividing i, up to the widest stride:
            // a label survives every halving that keeps a multiple of it.
            $stride = 1;
            while ($stride < $widest && $i % ($stride * 2) === 0) {
                $stride *= 2;
            }

            $at = $width($stride);
            $out[] = $stride >= $widest || $at <= $narrowest ? null : $at;
        }

        return $out;
    }

    /**
     * The sentence a chart of categories is read as, where order may mean
     * nothing (regions, plans): the highest and the lowest, by name.
     *
     *   Revenue: highest Europe at 30, lowest Asia at 8.
     *   Revenue: all 3 at 7.
     *   Revenue: Europe at 30.
     *   Revenue: no data.
     *
     * @param  list<string>  $labels  the category of each value, by position
     */
    public static function ranked(iterable $values, array $labels, array $format = [], ?string $label = null): string
    {
        $values = array_values(self::values($values));
        $present = array_filter($values, fn ($v) => $v !== null);
        $f = fn (float $v) => self::format($v, $format);
        $name = fn (int $i) => (string) ($labels[$i] ?? $i + 1);
        $lead = $label !== null && trim($label) !== '' ? trim($label).': ' : '';

        if ($present === []) {
            return $lead === '' ? 'No data.' : $lead.'no data.';
        }

        $hi = array_search(max($present), $present, true);
        $lo = array_search(min($present), $present, true);

        $text = match (true) {
            count($present) === 1 => $name($hi).' at '.$f($present[$hi]),
            $present[$hi] === $present[$lo] => 'all '.count($present).' at '.$f($present[$hi]),
            default => 'highest '.$name($hi).' at '.$f($present[$hi]).', lowest '.$name($lo).' at '.$f($present[$lo]),
        };

        return $lead === '' ? ucfirst($text).'.' : $lead.$text.'.';
    }

    /**
     * Which way the values went, first present value to last: up, down, or
     * level. 'none' when there are fewer than two.
     */
    public static function trend(iterable $values): string
    {
        $present = array_values(array_filter(self::values($values), fn ($v) => $v !== null));

        if (count($present) < 2) {
            return 'none';
        }

        return match (end($present) <=> $present[0]) {
            1 => 'up',
            -1 => 'down',
            default => 'level',
        };
    }

    /**
     * The sentence a chart is read as, for its text alternative: the trend,
     * where it started and ended (the latest value), and the range.
     *
     *   Signups: up from 12 to 30 (low 8, high 30).
     *   Signups: flat at 7.
     *   Signups: no data.
     */
    public static function summary(iterable $values, array $format = [], ?string $label = null): string
    {
        $present = array_values(array_filter(self::values($values), fn ($v) => $v !== null));
        $f = fn (float $v) => self::format($v, $format);
        $lead = $label !== null && trim($label) !== '' ? trim($label).': ' : '';

        if ($present === []) {
            return $lead === '' ? 'No data.' : $lead.'no data.';
        }

        $first = $present[0];
        $last = end($present);
        $lo = min($present);
        $hi = max($present);

        $text = match (true) {
            count($present) === 1 => $f($last),
            $lo === $hi => 'flat at '.$f($last),
            default => match (self::trend($present)) {
                'up' => 'up from '.$f($first).' to '.$f($last),
                'down' => 'down from '.$f($first).' to '.$f($last),
                default => 'level at '.$f($last),
            }.' (low '.$f($lo).', high '.$f($hi).')',
        };

        return $lead === '' ? ucfirst($text).'.' : $lead.$text.'.';
    }

    /** @return list<list<array{0: float, 1: float}>> the points between gaps */
    private static function runs(array $points): array
    {
        $runs = [];
        $run = [];

        foreach ($points as $point) {
            if ($point === null) {
                $run === [] || $runs[] = $run;
                $run = [];
            } else {
                $run[] = $point;
            }
        }

        $run === [] || $runs[] = $run;

        return $runs;
    }

    /**
     * The path on from a run's first point through the rest: straight
     * segments, or the monotone cubic. Two points are a straight line either
     * way; there is no bend to make.
     *
     * @param  list<array{0: float, 1: float}>  $run
     */
    private static function through(array $run, bool $smooth): string
    {
        $n = count($run);

        if (! $smooth || $n < 3) {
            $path = '';
            for ($i = 1; $i < $n; $i++) {
                $path .= 'L'.self::n($run[$i][0]).' '.self::n($run[$i][1]);
            }

            return $path;
        }

        // The tangent at each inner point: zero where the line turns (a
        // peak or a trough stays exactly at the point), otherwise the
        // harmonic-style blend of the slopes either side, limited so the
        // curve cannot leave the band between its neighbours.
        $slope = fn (int $a, int $b) => ($run[$b][1] - $run[$a][1]) / (($run[$b][0] - $run[$a][0]) ?: 1e-9);
        $t = [];
        for ($i = 1; $i < $n - 1; $i++) {
            $s0 = $slope($i - 1, $i);
            $s1 = $slope($i, $i + 1);
            $h0 = $run[$i][0] - $run[$i - 1][0];
            $h1 = $run[$i + 1][0] - $run[$i][0];
            $p = ($s0 * $h1 + $s1 * $h0) / (($h0 + $h1) ?: 1e-9);
            $t[$i] = ((($s0 <=> 0) + ($s1 <=> 0)) * min(abs($s0), abs($s1), 0.5 * abs($p)));
        }
        // The ends lean toward their one neighbour's slope.
        $t[0] = (3 * $slope(0, 1) - $t[1]) / 2;
        $t[$n - 1] = (3 * $slope($n - 2, $n - 1) - $t[$n - 2]) / 2;

        $path = '';
        for ($i = 0; $i < $n - 1; $i++) {
            [$x0, $y0] = $run[$i];
            [$x1, $y1] = $run[$i + 1];
            $dx = ($x1 - $x0) / 3;
            $path .= 'C'.self::n($x0 + $dx).' '.self::n($y0 + $dx * $t[$i])
                .' '.self::n($x1 - $dx).' '.self::n($y1 - $dx * $t[$i + 1])
                .' '.self::n($x1).' '.self::n($y1);
        }

        return $path;
    }

    private static function arcTo(float $cx, float $cy, float $r, float $to, bool $large): string
    {
        [$x, $y] = self::polar($cx, $cy, $r, $to);
        $radius = self::n($r);

        return 'A'.$radius.' '.$radius.' 0 '.($large ? '1' : '0').' 1 '.self::n($x).' '.self::n($y);
    }

    private static function nice(float $x, bool $round): float
    {
        $exponent = floor(log10($x));
        $fraction = $x / 10 ** $exponent;

        $nice = $round
            ? ($fraction < 1.5 ? 1 : ($fraction < 3 ? 2 : ($fraction < 7 ? 5 : 10)))
            : ($fraction <= 1 ? 1 : ($fraction <= 2 ? 2 : ($fraction <= 5 ? 5 : 10)));

        return $nice * 10 ** $exponent;
    }

    /** The absolute, rounded number with the locale's separators. */
    private static function digits(float $number, array $o): string
    {
        if (extension_loaded('intl')) {
            $formatter = new NumberFormatter(str_replace('-', '_', $o['locale']), NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $o['minimumFractionDigits']);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $o['maximumFractionDigits']);
            $formatter->setAttribute(NumberFormatter::GROUPING_USED, $o['useGrouping'] ? 1 : 0);
            // The browser's Intl rounds half away from zero; ICU's default is
            // half-even, which would turn 2.5 into "2" here and "3" there.
            $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);

            $text = $formatter->format($number);

            if ($text !== false) {
                return $text;
            }
        }

        $text = number_format($number, $o['maximumFractionDigits'], '.', $o['useGrouping'] ? ',' : '');

        if ($o['maximumFractionDigits'] > $o['minimumFractionDigits']) {
            [$whole, $fraction] = explode('.', $text) + [1 => ''];
            $fraction = str_pad(rtrim($fraction, '0'), $o['minimumFractionDigits'], '0');
            $text = $fraction === '' ? $whole : $whole.'.'.$fraction;
        }

        return $text;
    }
}
