<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The geometry and the sentences of the charts that are not drawn along one
 * value axis: the funnel's stages and the rates between them, the radar's
 * polygons, markers and labels, the treemap's squarified tiles.
 *
 * A sibling of Support\Chart rather than more of it: Chart was past 1,300
 * lines, and these three share nothing with the axes, ticks and paths it
 * holds beyond the helpers they call on it (polar(), format(), n()). The same
 * rules hold here: computed once, in PHP; exact strings, tested
 * (ChartShapesTest); x right, y DOWN; angles in degrees clockwise from twelve
 * o'clock.
 */
final class ChartShapes
{
    /** The point markers, in series order: four, because a radar draws four series at most. */
    public const MARKERS = ['circle', 'square', 'triangle', 'diamond'];

    /** The line each series is drawn with, in the same order: solid, dashed, dotted, dash-dot. */
    public const DASHES = ['', '6 4', '0.5 4', '10 4 0.5 4'];

    /**
     * Rectangles that fill a box, one per value, each with an area in
     * proportion to its value: the squarified layout of Bruls, Huizing and
     * van Wijk (2000), which keeps each tile as near to square as it can, so
     * areas can be compared at all.
     *
     * Largest first: values are sorted descending, equal values keeping the
     * order they were given in, and laid out in rows along the shorter side
     * of the space that is left; a value joins the row while it makes the
     * row's worst aspect ratio no worse. The last tile of every row, and the
     * last row, run exactly to the edge, so the tiles cover the box with no
     * sliver of rounding left over.
     *
     * A value that is not a number above zero has no area, and is dropped:
     * it is not in the result, and not in the total the others share.
     *
     * Keys are kept, and the result is in layout order (largest first).
     *
     * @param  array<int|string, mixed>  $values
     * @return array<int|string, array{x: float, y: float, width: float, height: float}>
     */
    public static function squarify(array $values, float $width, float $height, float $x = 0.0, float $y = 0.0): array
    {
        $items = [];
        $order = 0;
        foreach ($values as $key => $value) {
            if (is_numeric($value) && (float) $value > 0) {
                $items[] = ['key' => $key, 'value' => (float) $value, 'order' => $order];
            }
            $order++;
        }

        if ($items === [] || $width <= 0 || $height <= 0) {
            return [];
        }

        usort($items, fn ($a, $b) => $b['value'] <=> $a['value'] ?: $a['order'] <=> $b['order']);

        $scale = $width * $height / array_sum(array_column($items, 'value'));
        foreach ($items as &$item) {
            $item['area'] = $item['value'] * $scale;
        }
        unset($item);

        $free = ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
        $out = [];
        $row = [];
        $i = 0;
        $count = count($items);

        while ($i < $count) {
            $side = min($free['width'], $free['height']);
            $with = array_merge($row, [$items[$i]]);

            if ($row === [] || self::worst($with, $side) <= self::worst($row, $side)) {
                $row = $with;
                $i++;

                continue;
            }

            $free = self::place($row, $free, $out, false);
            $row = [];
        }

        self::place($row, $free, $out, true);

        return $out;
    }

    /**
     * The top of a radar's scale: the smallest multiple of `rings` nice steps
     * (1, 2, 2.5 or 5 times a power of ten) that reaches the highest value,
     * so every ring sits on a round number. Nothing at all reaches 1.
     */
    public static function radarMax(float $highest, int $rings): float
    {
        $rings = max(1, $rings);

        if ($highest <= 0) {
            return (float) $rings;
        }

        $raw = $highest / $rings;
        $power = 10 ** floor(log10($raw));
        foreach ([1, 2, 2.5, 5, 10] as $nice) {
            // A hair of tolerance, or 0.3 / 3 lands on 0.10000000000000002.
            if ($nice * $power >= $raw - 1e-9 * $power) {
                return round($nice * $power * $rings, 10);
            }
        }

        return 10 * $power * $rings;
    }

    /**
     * Points round a centre, one per value, as an SVG polygon's `points`:
     * the first at twelve o'clock, the rest clockwise at equal angles.
     *
     * @param  list<float>  $radii
     */
    public static function polygon(array $radii, float $cx, float $cy): string
    {
        $n = count($radii);

        return implode(' ', array_map(function ($r, $k) use ($n, $cx, $cy) {
            [$x, $y] = Chart::polar($cx, $cy, $r, $k * 360 / $n);

            return Chart::n($x).','.Chart::n($y);
        }, array_values($radii), array_keys(array_values($radii))));
    }

    /**
     * How a label beside a point at this angle is anchored so it grows AWAY
     * from the centre: text-anchor (start on the right half, end on the left,
     * middle near the top and bottom) and dominant-baseline (auto above the
     * centre, hanging below it, central at the sides).
     *
     * @return array{anchor: string, baseline: string}
     */
    public static function anchor(float $degrees): array
    {
        $rad = deg2rad($degrees);
        $across = sin($rad);
        $up = cos($rad);

        return [
            'anchor' => $across > 0.2 ? 'start' : ($across < -0.2 ? 'end' : 'middle'),
            'baseline' => $up > 0.5 ? 'auto' : ($up < -0.5 ? 'hanging' : 'central'),
        ];
    }

    /**
     * A point marker as a path centred on (x, y), `size` across: so a series
     * is told apart by its shape as well as its colour. A circle is two arcs;
     * the triangle and the diamond are drawn a little larger, to look the
     * same size as the square beside them.
     */
    public static function marker(string $shape, float $x, float $y, float $size): string
    {
        $h = $size / 2;
        $n = fn (float $v) => Chart::n($v);

        return match ($shape) {
            'square' => 'M'.$n($x - $h).' '.$n($y - $h).'h'.$n($size).'v'.$n($size).'h'.$n(-$size).'Z',
            'triangle' => 'M'.$n($x).' '.$n($y - $h * 1.25).'L'.$n($x + $h * 1.25).' '.$n($y + $h).'L'.$n($x - $h * 1.25).' '.$n($y + $h).'Z',
            'diamond' => 'M'.$n($x).' '.$n($y - $h * 1.35).'L'.$n($x + $h * 1.35).' '.$n($y).'L'.$n($x).' '.$n($y + $h * 1.35).'L'.$n($x - $h * 1.35).' '.$n($y).'Z',
            default => 'M'.$n($x - $h).' '.$n($y).'a'.$n($h).' '.$n($h).' 0 1 0 '.$n($size).' 0a'.$n($h).' '.$n($h).' 0 1 0 '.$n(-$size).' 0Z',
        };
    }

    /**
     * A ratio as a percentage a reader sees: whole above 10%, one decimal
     * below it (2.5%), because a funnel's last stage is often a few percent
     * and "3%" hides the difference between 2.5 and 3.4.
     */
    public static function percent(float $ratio): string
    {
        $p = $ratio * 100;
        $decimals = abs($p) < 10 && round($p, 1) != round($p) ? 1 : 0;

        return Chart::format($p, ['decimals' => $decimals, 'suffix' => '%']);
    }

    /**
     * Each stage's conversion: `step` against the stage before it (null for
     * the first, and after a stage of nothing), `total` against the first
     * (null when the first is nothing), and `change`, the step as a signed
     * difference: "−62%" for a drop, "+15%" for a stage larger than the one
     * before it, which is legal data and said as such.
     *
     * @param  list<float>  $values
     * @return list<array{step: float|null, total: float|null, change: string|null}>
     */
    public static function rates(array $values): array
    {
        $values = array_values(array_map('floatval', $values));
        $out = [];

        foreach ($values as $i => $value) {
            $previous = $values[$i - 1] ?? null;
            $step = $previous !== null && $previous > 0 ? $value / $previous : null;
            $out[] = [
                'step' => $step,
                'total' => $values[0] > 0 ? $value / $values[0] : null,
                'change' => $step === null ? null : self::changeOf($step),
            ];
        }

        return $out;
    }

    /**
     * Which heat step (1 to 4) each of `count` stages is filled with, from
     * `from` at the first to `to` at the last, evenly: one colour that fades
     * along the funnel, rather than a series colour per stage, which would
     * say the stages are different things.
     *
     * @return list<int>
     */
    public static function ramp(int $count, int $from = 3, int $to = 1): array
    {
        if ($count <= 1) {
            return array_fill(0, max(0, $count), $from);
        }

        return array_map(fn ($i) => (int) round($from + ($to - $from) * $i / ($count - 1)), range(0, $count - 1));
    }

    /**
     * The sentence a funnel is read as: how many stages, first to last with
     * the conversion overall, and the largest drop between two stages.
     *
     *   Signups: 4 stages, from Visits 12,400 to Paid 310 (2.5% of the first); the largest drop is Visits to Sign-ups (−75%).
     *   Signups: Visits 12,400.
     *
     * @param  list<array{label: string, value: float}>  $stages
     */
    public static function funnel(array $stages, array $format = [], ?string $label = null): string
    {
        $lead = $label !== null && trim($label) !== '' ? trim($label).': ' : '';

        if ($stages === []) {
            return $lead === '' ? 'No data.' : $lead.'no data.';
        }

        $f = fn (float $v) => Chart::format($v, $format);
        $first = $stages[0];
        $last = end($stages);

        if (count($stages) === 1) {
            $text = $first['label'].' '.$f($first['value']).'.';

            return $lead === '' ? ucfirst($text) : $lead.$text;
        }

        $rates = self::rates(array_column($stages, 'value'));
        $text = count($stages).' stages, from '.$first['label'].' '.$f($first['value']).' to '.$last['label'].' '.$f($last['value']);
        if (end($rates)['total'] !== null) {
            $text .= ' ('.self::percent(end($rates)['total']).' of the first)';
        }

        // The largest drop, by the share lost; the first of equals.
        $worst = null;
        foreach ($rates as $i => $rate) {
            if ($rate['step'] !== null && $rate['step'] < 1 && ($worst === null || $rate['step'] < $rates[$worst]['step'])) {
                $worst = $i;
            }
        }
        $text .= $worst === null
            ? '; no stage is smaller than the one before it.'
            : '; the largest drop is '.$stages[$worst - 1]['label'].' to '.$stages[$worst]['label'].' ('.$rates[$worst]['change'].').';

        return $lead === '' ? ucfirst($text) : $lead.$text;
    }

    /** A step's ratio as the change it is: −62%, +15%, ±0%. */
    private static function changeOf(float $step): string
    {
        $text = self::percent(abs($step - 1));

        return match (true) {
            $text === '0%' => '±0%',
            $step < 1 => Chart::MINUS.$text,
            default => '+'.$text,
        };
    }

    /** The worst (largest) aspect ratio of a row of areas laid along `side`. */
    private static function worst(array $row, float $side): float
    {
        $areas = array_column($row, 'area');
        $sum = array_sum($areas);
        $side2 = $side * $side;
        $sum2 = $sum * $sum;

        return max($side2 * max($areas) / $sum2, $sum2 / ($side2 * min($areas)));
    }

    /**
     * Lays a row into the free space: a column down its left edge when the
     * space is wider than tall, else a strip along its top. Returns what is
     * left. `last` makes the row take the whole of what is left.
     *
     * @param  array{x: float, y: float, width: float, height: float}  $free
     * @return array{x: float, y: float, width: float, height: float}
     */
    private static function place(array $row, array $free, array &$out, bool $last): array
    {
        $sum = array_sum(array_column($row, 'area'));
        $n = count($row);

        if ($free['width'] >= $free['height']) {
            $thick = $last ? $free['width'] : $sum / $free['height'];
            $at = $free['y'];
            foreach ($row as $k => $item) {
                $length = $k === $n - 1 ? $free['y'] + $free['height'] - $at : $item['area'] / $thick;
                $out[$item['key']] = ['x' => $free['x'], 'y' => $at, 'width' => $thick, 'height' => $length];
                $at += $length;
            }

            return ['x' => $free['x'] + $thick, 'y' => $free['y'], 'width' => $free['width'] - $thick, 'height' => $free['height']];
        }

        $thick = $last ? $free['height'] : $sum / $free['width'];
        $at = $free['x'];
        foreach ($row as $k => $item) {
            $length = $k === $n - 1 ? $free['x'] + $free['width'] - $at : $item['area'] / $thick;
            $out[$item['key']] = ['x' => $at, 'y' => $free['y'], 'width' => $length, 'height' => $thick];
            $at += $length;
        }

        return ['x' => $free['x'], 'y' => $free['y'] + $thick, 'width' => $free['width'], 'height' => $free['height'] - $thick];
    }
}
