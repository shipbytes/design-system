<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * What colour text drawn ON a chart's mark should be: a treemap tile's label,
 * a value inside a funnel's bar. A mark is filled with a series slot, a tone
 * or a step of the heat ramp, any of which can be light or dark, and in
 * either theme; text in the body colour would vanish on half of them.
 *
 * The answer is a literal class in the chart recipe (`ink`, one per hue and
 * heat step), because a class worked out at render time is invisible to a
 * consumer's Tailwind. This is the arithmetic that chose them: WCAG contrast
 * against each of the two inks, in each theme, and the better one wins.
 * InkTest recomputes every entry from dist/tokens.json, so a token that moves
 * fails the test instead of leaving a label at 2:1.
 *
 * Colours are #rrggbb or oklch(L% C H), the two forms dist/tokens.json holds.
 */
final class Ink
{
    /** The two inks, the same in both themes: zinc-950 and white. */
    public const DARK = 'oklch(14.1% 0.005 285.823)';

    public const LIGHT = '#ffffff';

    /**
     * The class for text on a mark, from the recipe: the hue is a tone or a
     * series slot (the slot winning, as in Chart::colour()), the step the
     * heat step it is filled with (3 is the hue itself).
     */
    public static function label(?string $tone, mixed $series = null, int $step = 3, string $default = 'neutral'): string
    {
        $slot = Chart::series($series);
        $key = $slot !== null ? 'series-'.$slot : Chart::tone($tone, $default);

        return Chart::recipe()['ink'][$key][max(1, min(4, $step))];
    }

    /**
     * Which ink reads better on a fill: 'dark' or 'light'. A tie goes to
     * dark, the body text's own colour in the light theme.
     *
     * @param  string|array{0: float, 1: float, 2: float}  $fill  a colour, or OKLab
     */
    public static function pick(string|array $fill): string
    {
        return self::contrast($fill, self::DARK) >= self::contrast($fill, self::LIGHT) ? 'dark' : 'light';
    }

    /**
     * The WCAG contrast ratio of two opaque colours, 1 to 21.
     *
     * @param  string|array{0: float, 1: float, 2: float}  $a
     * @param  string|array{0: float, 1: float, 2: float}  $b
     */
    public static function contrast(string|array $a, string|array $b): float
    {
        [$hi, $lo] = [self::luminance($a), self::luminance($b)];
        if ($lo > $hi) {
            [$hi, $lo] = [$lo, $hi];
        }

        return ($hi + 0.05) / ($lo + 0.05);
    }

    /**
     * Relative luminance, from linear sRGB clamped into gamut the way a
     * screen shows an out-of-gamut colour.
     *
     * @param  string|array{0: float, 1: float, 2: float}  $colour
     */
    public static function luminance(string|array $colour): float
    {
        [$r, $g, $b] = array_map(fn ($c) => max(0.0, min(1.0, $c)), self::linear(is_array($colour) ? $colour : self::lab($colour)));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * A colour in OKLab, [L, a, b].
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public static function lab(string $css): array
    {
        $css = trim(strtolower($css));

        if (preg_match('/^#([0-9a-f]{6})$/', $css, $m)) {
            $rgb = array_map(function ($i) use ($m) {
                $c = hexdec(substr($m[1], $i, 2)) / 255;

                return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, [0, 2, 4]);

            return self::fromLinear($rgb);
        }

        if (preg_match('/^oklch\(\s*([\d.]+)(%?)\s+([\d.]+)\s+([\d.]+)/', $css, $m)) {
            $l = (float) $m[1] / ($m[2] === '%' ? 100 : 1);
            $h = deg2rad((float) $m[4]);

            return [$l, (float) $m[3] * cos($h), (float) $m[3] * sin($h)];
        }

        throw new \InvalidArgumentException("Ink reads #rrggbb and oklch() colours, and was given [{$css}].");
    }

    /**
     * A step of the heat ramp, mixed as theme.css mixes it: in OKLab, the
     * hue's share and fg's share, the rest the surface.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public static function heat(string $hue, float $hueShare, float $fgShare, string $fg, string $surface): array
    {
        [$h, $f, $s] = [self::lab($hue), self::lab($fg), self::lab($surface)];
        $rest = 1 - $hueShare - $fgShare;

        return [
            $h[0] * $hueShare + $f[0] * $fgShare + $s[0] * $rest,
            $h[1] * $hueShare + $f[1] * $fgShare + $s[1] * $rest,
            $h[2] * $hueShare + $f[2] * $fgShare + $s[2] * $rest,
        ];
    }

    /** @return array{0: float, 1: float, 2: float} linear sRGB to OKLab */
    private static function fromLinear(array $rgb): array
    {
        [$r, $g, $b] = $rgb;
        $l = (0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b) ** (1 / 3);
        $m = (0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b) ** (1 / 3);
        $s = (0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b) ** (1 / 3);

        return [
            0.2104542553 * $l + 0.793617785 * $m - 0.0040720468 * $s,
            1.9779984951 * $l - 2.428592205 * $m + 0.4505937099 * $s,
            0.0259040371 * $l + 0.7827717662 * $m - 0.808675766 * $s,
        ];
    }

    /** @return array{0: float, 1: float, 2: float} OKLab to linear sRGB */
    private static function linear(array $lab): array
    {
        [$L, $a, $b] = $lab;
        $l = ($L + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m = ($L - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s = ($L - 0.0894841775 * $a - 1.291485548 * $b) ** 3;

        return [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.707614701 * $s,
        ];
    }
}
