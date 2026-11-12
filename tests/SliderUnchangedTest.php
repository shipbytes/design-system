<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * `histogram` was added to ds::slider in batch 17, and a slider that does not
 * ask for one must render exactly as it did before: byte for byte,
 * whitespace included.
 *
 * The fixture was written from the view before the prop existed. Generated
 * ids are random for an unbound field, so they are normalised; nothing else
 * is.
 */
final class SliderUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        return [
            'single' => Specimens::all()['slider/single'],
            'range' => Specimens::all()['slider/range'],
            'bound range' => '<x-ds::slider name="price" label="Price" :range="true" :value="[100, 400]" :max="500" lowLabel="Minimum price" highLabel="Maximum price" :format="[\'prefix\' => \'$\']" showValue="inline" wire:model.live="price" />',
            'explicit id' => '<x-ds::slider id="vol" name="volume" label="Volume" :value="40" class="mt-4" />',
        ];
    }

    public static function render(string $blade): string
    {
        return preg_replace('/ds-[0-9a-f]{8}/', 'ds-ID', Blade::render($blade));
    }

    #[Test]
    public function every_slider_without_a_histogram_renders_exactly_as_before(): void
    {
        $fixture = __DIR__.'/Fixtures/slider-before-histogram.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".self::render($blade)."\n";
        }

        $this->assertSame(file_get_contents($fixture), $rendered);
    }
}
