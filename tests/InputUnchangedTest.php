<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * `reveal` was added to ds::input in 2.0, and every input that does not ask for
 * it must render exactly as it did before: byte for byte, whitespace included.
 *
 * The fixture was written from the 1.x view, before `reveal` or the shared
 * field recipe existed. Generated ids are random for an unbound field, so they
 * are normalised; nothing else is.
 */
final class InputUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        return [
            'specimen' => Specimens::all()['input/all'],
            'password' => '<x-ds::input type="password" name="password" label="Password" help="At least 8 characters." />',
            'password, invalid' => '<x-ds::input type="password" name="password" label="Password" error="Too short." autocomplete="new-password" />',
            'password, disabled' => '<x-ds::input type="password" name="password" label="Password" :disabled="true" />',
            'bound' => '<x-ds::input wire:model.live="search" name="q" label="Search" icon="magnifying-glass" />',
            'explicit id' => '<x-ds::input id="email" type="email" name="email" label="Email" value="ada@example.com" class="mt-4" />',
        ];
    }

    public static function render(string $blade): string
    {
        return preg_replace('/ds-[0-9a-f]{8}/', 'ds-ID', Blade::render($blade));
    }

    #[Test]
    public function every_input_without_reveal_renders_exactly_as_before(): void
    {
        $fixture = __DIR__.'/Fixtures/input-1x.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".self::render($blade)."\n";
        }

        $this->assertSame(file_get_contents($fixture), $rendered);
    }
}
