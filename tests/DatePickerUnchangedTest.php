<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * ds::date-picker gained a second grid in batch 18 (the one ds::calendar
 * draws, with `months`, `presets`, `minDays`, `maxDays` and `confirm`), and a
 * picker that asks for none of them must render exactly as in 1.x: byte for
 * byte, whitespace included. That keeps it working with Alpine alone and no
 * script, which is the contract every 1.x component holds on next-2.0.
 *
 * The fixture was written from the view before the new props existed.
 * Generated ids are random for an unbound field, so they are normalised;
 * nothing else is.
 */
final class DatePickerUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        return [
            'all' => Specimens::all()['date-picker/all'],
            'bound range' => '<x-ds::date-picker name="period" label="Period" :range="true" :value="[\'2026-02-01\', \'2026-02-29\']" min="2026-01-01" max="2026-12-31" placeholder="Any time" wire:model.live="period" />',
            'explicit id' => '<x-ds::date-picker id="due" name="due" value="2026-09-30" class="mt-4" :weekStartsOn="0" help="Soon." />',
            'carbon' => '<x-ds::date-picker name="when" :value="\Illuminate\Support\Carbon::parse(\'2027-03-10 14:00\')" aria-label="When" />',
        ];
    }

    public static function render(string $blade): string
    {
        return preg_replace('/ds-[0-9a-f]{8}/', 'ds-ID', Blade::render($blade));
    }

    #[Test]
    public function every_date_picker_without_the_new_grid_renders_exactly_as_in_1x(): void
    {
        $fixture = __DIR__.'/Fixtures/date-picker-1x.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".self::render($blade)."\n";
        }

        $this->assertSame(file_get_contents($fixture), $rendered);
    }
}
