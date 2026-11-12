<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * ds::color-picker's colours on the server: reading what was given, writing
 * what the form posts, the picker's HSV and HSL, and the contrast line.
 *
 * resources/js/shared/colour.js does the same in the browser, and both are
 * held to tests/Fixtures/colour.json, so the first paint is what the script
 * would have drawn. Contrast is Support\Ink's, the arithmetic the chart labels
 * were chosen by, so there is one WCAG formula in the package.
 *
 * A colour is [r, g, b, a]: whole channels 0 to 255 and alpha 0 to 1. sRGB
 * only.
 */
final class Colour
{
    /** The sixteen basic CSS names (and grey); see shared/colour.js for why not all 148. */
    public const NAMED = [
        'black' => '000', 'silver' => 'c0c0c0', 'gray' => '808080', 'grey' => '808080', 'white' => 'fff',
        'maroon' => '800000', 'red' => 'f00', 'purple' => '800080', 'fuchsia' => 'f0f', 'green' => '008000',
        'lime' => '0f0', 'olive' => '808000', 'yellow' => 'ff0', 'navy' => '000080', 'blue' => '00f',
        'teal' => '008080', 'aqua' => '0ff',
    ];

    private const NUMBER = '/^[+-]?(\d+\.?\d*|\.\d+)(%|deg)?$/';

    /**
     * "abc", "#ABC", "#aabbcc80", "rgb(1, 2, 3)", "rgb(1 2 3 / 50%)",
     * "hsl(210deg 50% 40%)", "teal"; null for anything else.
     *
     * @return array{0: int, 1: int, 2: int, 3: float}|null
     */
    public static function parse(?string $text): ?array
    {
        $s = strtolower(trim((string) $text));
        $s = self::NAMED[$s] ?? $s;

        if (preg_match('/^#?([\da-f]{3,4}|[\da-f]{6}|[\da-f]{8})$/', $s, $m)) {
            $h = strlen($m[1]) < 5 ? implode('', array_map(fn ($c) => $c.$c, str_split($m[1]))) : $m[1];
            $n = fn (int $i) => (int) hexdec(substr($h, $i, 2));

            return [$n(0), $n(2), $n(4), strlen($h) === 8 ? $n(6) / 255 : 1.0];
        }

        if (! preg_match('/^(rgba?|hsla?)\((.*)\)$/', $s, $fn)) {
            return null;
        }
        $parts = array_values(array_filter(preg_split('/[\s,\/]+/', $fn[2]), fn ($p) => $p !== ''));
        if ((count($parts) !== 3 && count($parts) !== 4) || array_filter($parts, fn ($p) => ! preg_match(self::NUMBER, $p))) {
            return null;
        }
        $read = array_map(fn ($p) => [(float) $p, str_ends_with($p, '%')], $parts);
        $a = isset($read[3]) ? self::clamp($read[3][1] ? $read[3][0] / 100 : $read[3][0], 0, 1) : 1.0;

        if (str_starts_with($fn[1], 'rgb')) {
            $ch = fn (array $v) => (int) round(self::clamp($v[1] ? $v[0] * 255 / 100 : $v[0], 0, 255));

            return [$ch($read[0]), $ch($read[1]), $ch($read[2]), $a];
        }

        return [...self::fromHsl($read[0][0], self::clamp($read[1][0], 0, 100), self::clamp($read[2][0], 0, 100)), $a];
    }

    /** @param  array{0: int, 1: int, 2: int, 3?: float}  $c  #rrggbb, or #rrggbbaa with $alpha */
    public static function hex(array $c, bool $alpha = false): string
    {
        $pair = fn (float $n) => str_pad(dechex((int) round($n)), 2, '0', STR_PAD_LEFT);

        return '#'.$pair($c[0]).$pair($c[1]).$pair($c[2]).($alpha ? $pair(($c[3] ?? 1.0) * 255) : '');
    }

    /**
     * What the form posts: hex, or rgb() / hsl() in the comma syntax.
     *
     * @param  array{0: int, 1: int, 2: int, 3?: float}  $c
     */
    public static function write(array $c, string $format = 'hex', bool $alpha = false): string
    {
        $a = rtrim(rtrim(number_format(round($c[3] ?? 1.0, 2), 2, '.', ''), '0'), '.');

        if ($format === 'rgb') {
            return $alpha ? "rgba({$c[0]}, {$c[1]}, {$c[2]}, {$a})" : "rgb({$c[0]}, {$c[1]}, {$c[2]})";
        }
        if ($format === 'hsl') {
            [$h, $s, $l] = array_map(fn ($v) => (int) round($v), self::toHsl($c));

            return $alpha ? "hsla({$h}, {$s}%, {$l}%, {$a})" : "hsl({$h}, {$s}%, {$l}%)";
        }

        return self::hex($c, $alpha);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $c
     * @return array{0: float, 1: float, 2: float} hue 0–360, saturation and lightness 0–100
     */
    public static function toHsl(array $c): array
    {
        [$r, $g, $b] = [$c[0] / 255, $c[1] / 255, $c[2] / 255];
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;
        $s = $d == 0 ? 0 : $d / (1 - abs(2 * $l - 1));

        return [self::hue($r, $g, $b, $max, $d), $s * 100, $l * 100];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $c
     * @return array{0: float, 1: float, 2: float} hue 0–360, saturation and brightness 0–100
     */
    public static function toHsv(array $c): array
    {
        [$r, $g, $b] = [$c[0] / 255, $c[1] / 255, $c[2] / 255];
        $max = max($r, $g, $b);
        $d = $max - min($r, $g, $b);

        return [self::hue($r, $g, $b, $max, $d), $max == 0 ? 0.0 : $d / $max * 100, $max * 100];
    }

    /** @return array{0: int, 1: int, 2: int} */
    public static function fromHsl(float $h, float $s, float $l): array
    {
        [$s, $l] = [$s / 100, $l / 100];
        $f = function (int $n) use ($h, $s, $l): int {
            $k = fmod($n + $h / 30, 12);

            return (int) round(($l - $s * min($l, 1 - $l) * max(-1, min($k - 3, 9 - $k, 1))) * 255);
        };

        return [$f(0), $f(8), $f(4)];
    }

    /**
     * WCAG contrast through Support\Ink, a translucent colour laid over
     * `against` first.
     *
     * @param  array{0: int, 1: int, 2: int, 3?: float}  $c
     * @param  array{0: int, 1: int, 2: int, 3?: float}  $against
     */
    public static function contrast(array $c, array $against): float
    {
        $a = $c[3] ?? 1.0;
        $over = array_map(fn (int $i) => (int) round($c[$i] * $a + $against[$i] * (1 - $a)), [0, 1, 2]);

        return Ink::contrast(self::hex($over), self::hex($against));
    }

    /**
     * The ratio as shown: cut to two places, never rounded up past a
     * threshold it fails; the pass marks are read from it. The 1e-4 absorbs
     * Ink's path through OKLab, which puts black on white at 20.9999993.
     */
    public static function ratio(float $n): string
    {
        return number_format(floor($n * 100 + 1e-4) / 100, 2, '.', '');
    }

    private static function hue(float $r, float $g, float $b, float $max, float $d): float
    {
        if ($d == 0) {
            return 0.0;
        }
        $h = match (true) {
            $max === $r => fmod(($g - $b) / $d, 6),
            $max === $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return fmod($h * 60 + 360, 360);
    }

    private static function clamp(float $n, float $lo, float $hi): float
    {
        return max($lo, min($hi, $n));
    }
}
