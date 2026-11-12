<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Chart;
use Shipbytes\BladeUi\Support\Ink;

/**
 * The colour of text drawn on a chart's mark. The recipe's `ink` table is
 * literal classes, so a consumer's Tailwind can see them; this recomputes
 * every entry from the tokens that ship (dist/tokens.json) and fails when
 * one is no longer the better of the two inks, in either theme.
 */
final class InkTest extends TestCase
{
    /** The class for each answer: [light theme, dark theme]. */
    private const CLASSES = [
        'dark/dark' => 'text-zinc-950',
        'light/light' => 'text-white',
        'dark/light' => 'text-fg',
        'light/dark' => 'text-on-inverse',
    ];

    private function tokens(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../dist/tokens.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function contrast_is_wcags(): void
    {
        $this->assertEqualsWithDelta(21.0, Ink::contrast('#000000', '#ffffff'), 0.01);
        $this->assertEqualsWithDelta(1.0, Ink::contrast('#2a78d6', '#2a78d6'), 1e-9);
        // An oklch value reads as the same colour as its hex.
        $this->assertEqualsWithDelta(Ink::luminance('#ffffff'), Ink::luminance('oklch(100% 0 0)'), 1e-6);
        $this->assertEqualsWithDelta(4.6, Ink::contrast('#2a78d6', Ink::DARK), 0.1);
        $this->assertSame('dark', Ink::pick('#eda100'));
        $this->assertSame('light', Ink::pick('#4a3aa7'));
    }

    #[Test]
    public function every_ink_in_the_recipe_is_the_better_of_the_two_in_both_themes(): void
    {
        $tokens = $this->tokens();
        $semantic = $tokens['semantic'];
        $heat = $tokens['chart']['heat'];
        $hues = ['neutral' => 'fg-muted', 'accent' => 'accent', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger'];
        foreach (range(1, 8) as $n) {
            $hues['series-'.$n] = 'series-'.$n;
        }

        $ink = Chart::recipe()['ink'];
        $this->assertSame(array_keys($hues), array_keys($ink));

        $wrong = [];
        foreach ($hues as $key => $token) {
            foreach ([1, 2, 3, 4] as $step) {
                $answer = [];
                foreach (['light', 'dark'] as $theme) {
                    $fill = Ink::heat($semantic[$token][$theme], (float) $heat[$step]['hue'], (float) $heat[$step]['fg'], $semantic['fg'][$theme], $semantic['surface'][$theme]);
                    $answer[] = Ink::pick($fill);
                }
                $want = self::CLASSES[implode('/', $answer)];
                if ($ink[$key][$step] !== $want) {
                    $wrong[] = "{$key} step {$step}: {$ink[$key][$step]}, should be {$want}";
                }
            }
        }

        $this->assertSame([], $wrong);
    }

    #[Test]
    public function every_pairing_reads_at_aa_for_body_text(): void
    {
        $tokens = $this->tokens();
        $semantic = $tokens['semantic'];
        $heat = $tokens['chart']['heat'];

        foreach (array_keys(Chart::recipe()['ink']) as $key) {
            $token = ['neutral' => 'fg-muted'][$key] ?? $key;
            foreach ([1, 2, 3, 4] as $step) {
                foreach (['light', 'dark'] as $theme) {
                    $fill = Ink::heat($semantic[$token][$theme], (float) $heat[$step]['hue'], (float) $heat[$step]['fg'], $semantic['fg'][$theme], $semantic['surface'][$theme]);
                    $ratio = Ink::contrast($fill, Ink::pick($fill) === 'dark' ? Ink::DARK : Ink::LIGHT);
                    $this->assertGreaterThanOrEqual(4.5, round($ratio, 2), "{$key} step {$step} in {$theme}");
                }
            }
        }
    }

    #[Test]
    public function the_view_asks_by_tone_or_slot_and_step(): void
    {
        $this->assertSame('text-zinc-950', Ink::label(null, 4));
        $this->assertSame('text-white', Ink::label('danger', 6));
        $this->assertSame('text-on-inverse', Ink::label('accent'));
        $this->assertSame('text-fg', Ink::label('accent', null, 1));
        $this->assertSame('text-on-inverse', Ink::label('nonsense', null, 9));
    }
}
