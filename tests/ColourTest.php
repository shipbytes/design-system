<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Colour;
use Shipbytes\BladeUi\Support\Ink;

/**
 * ds::color-picker's arithmetic on the server. Every case is read from
 * tests/Fixtures/colour.json, which test-js.mjs holds shared/colour.js to as
 * well: the two sides cannot drift without one of them going red.
 */
final class ColourTest extends TestCase
{
    private function fixture(string $key): array
    {
        return json_decode(file_get_contents(__DIR__.'/Fixtures/colour.json'), true, flags: JSON_THROW_ON_ERROR)[$key];
    }

    #[Test]
    public function it_reads_what_people_type_and_nothing_else(): void
    {
        foreach ($this->fixture('parse') as [$text, $want]) {
            $c = Colour::parse($text);
            $this->assertSame($want, $c === null ? null : Colour::hex($c, $c[3] < 1), "parse({$text})");
        }
    }

    #[Test]
    public function hsl_and_hsv_go_there_and_back(): void
    {
        foreach ($this->fixture('convert') as [$hex, $rgb, $hsl, $hsv]) {
            $c = Colour::parse($hex);
            $this->assertSame($rgb, array_slice($c, 0, 3), "{$hex} as rgb");
            $this->assertSame($hsl, array_map(fn ($v) => (int) round($v), Colour::toHsl($c)), "{$hex} as hsl");
            $this->assertSame($hsv, array_map(fn ($v) => (int) round($v), Colour::toHsv($c)), "{$hex} as hsv");
            // Back from the unrounded HSL, which is what parsing an hsl() does.
            $this->assertSame($hex, Colour::hex(Colour::fromHsl(...Colour::toHsl($c))), "{$hex} round trip");
        }
    }

    #[Test]
    public function it_writes_what_the_form_posts(): void
    {
        foreach ($this->fixture('write') as [$hex, $format, $alpha, $want]) {
            $this->assertSame($want, Colour::write(Colour::parse($hex), $format, $alpha), "{$hex} as {$format}");
        }
    }

    #[Test]
    public function contrast_is_inks_and_reads_as_the_browser_shows_it(): void
    {
        foreach ($this->fixture('contrast') as [$a, $b, $want]) {
            $this->assertSame($want, Colour::ratio(Colour::contrast(Colour::parse($a), Colour::parse($b))), "{$a} on {$b}");
        }
        // Opaque, it is Ink's number exactly: one formula in the package.
        $this->assertSame(Ink::contrast('#336699', '#ffffff'), Colour::contrast(Colour::parse('#336699'), Colour::parse('#fff')));
    }
}
