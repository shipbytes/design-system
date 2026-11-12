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

    /** @var array{tones: array<string, string>, series: array<int, string>, track: string}|null */
    private static ?array $recipe = null;

    /**
     * The colour classes, from resources/views/partials/chart.php. In that
     * file rather than here for the reason Support\Field gives: a class
     * written in src/ compiles in this repo's tests and is missing in every
     * app.
     *
     * @return array{tones: array<string, string>, series: array<int, string>, track: string}
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/chart.php';
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
     */
    public static function line(array $points): string
    {
        $path = '';

        foreach (self::runs($points) as $run) {
            $first = array_shift($run);
            $path .= 'M'.self::n($first[0]).' '.self::n($first[1]);

            if ($run === []) {
                $path .= 'h0';
            }

            foreach ($run as [$x, $y]) {
                $path .= 'L'.self::n($x).' '.self::n($y);
            }
        }

        return $path;
    }

    /**
     * The region between the line and a baseline, closed, one shape per run
     * of values. A lone value has no area, so it adds nothing.
     */
    public static function area(array $points, float $baseline): string
    {
        $path = '';
        $base = self::n($baseline);

        foreach (self::runs($points) as $run) {
            if (count($run) < 2) {
                continue;
            }

            $path .= 'M'.self::n($run[0][0]).' '.$base;

            foreach ($run as [$x, $y]) {
                $path .= 'L'.self::n($x).' '.self::n($y);
            }

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
