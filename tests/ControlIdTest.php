<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * A control bound with wire:model keeps its id from one render to the next.
 *
 * Livewire's morph matches elements by id. A fresh random id on every render
 * made it REPLACE a bound field instead of updating it, and a replaced field
 * loses focus: a live search dropped every keystroke after its first response.
 * An unbound control keeps a random id, so two look-alike fields in a plain
 * Blade page still get two ids.
 */
final class ControlIdTest extends TestCase
{
    private function idOf(string $blade): string
    {
        preg_match('/<(?:input|textarea|select)\b[^>]*\bid="([^"]+)"/', Blade::render($blade), $m);

        $this->assertNotEmpty($m, "no control rendered by: {$blade}");

        return $m[1];
    }

    #[Test]
    public function a_bound_control_renders_the_same_id_every_time(): void
    {
        foreach ([
            '<x-ds::input wire:model.live.debounce.300ms="search" type="search" aria-label="Search" />',
            '<x-ds::input as="textarea" wire:model="body" label="Body" />',
            '<x-ds::checkbox wire:model.live="selected" value="7" label="Select row 7" />',
            '<x-ds::switch wire:model="published" label="Published" />',
            '<x-ds::radio wire:model="plan" value="pro" label="Pro" />',
        ] as $blade) {
            $this->assertSame($this->idOf($blade), $this->idOf($blade), $blade);
        }
    }

    #[Test]
    public function bound_rows_of_one_array_are_told_apart_by_value(): void
    {
        $this->assertNotSame(
            $this->idOf('<x-ds::checkbox wire:model.live="selected" value="7" label="Select" />'),
            $this->idOf('<x-ds::checkbox wire:model.live="selected" value="8" label="Select" />'),
        );
    }

    #[Test]
    public function an_unbound_control_still_gets_a_fresh_id(): void
    {
        $blade = '<x-ds::input name="email" label="Email" />';

        $this->assertNotSame($this->idOf($blade), $this->idOf($blade));
    }

    #[Test]
    public function an_explicit_id_wins(): void
    {
        $this->assertSame('mine', $this->idOf('<x-ds::input id="mine" wire:model="q" label="Q" />'));
    }
}
