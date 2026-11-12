<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * `collapsible` was added to ds::panel in 2.0 (batch 10), and every panel that
 * does not ask for it must render exactly as it did before: byte for byte,
 * whitespace included.
 *
 * The fixture was written from the view before `collapsible`, `open` or `name`
 * existed. The cases are every way a panel was declared in the specimens, the
 * gallery and the docs at that point: a list panel with an action, a feature
 * panel, a header slot, a bare box, and `collapsible` and `open` passed as
 * false, which must change nothing. (`name` is not in that case: before it
 * was a prop it landed on the root as an attribute.)
 */
final class PanelUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        return [
            'specimen' => Specimens::all()['panel/all'],
            'header slot' => <<<'BLADE'
                <x-ds::panel>
                    <x-slot:header>
                        <h2 class="text-section font-semibold text-fg">Filters</h2>
                    </x-slot:header>
                    <x-ds::panel-row href="/a">A</x-ds::panel-row>
                </x-ds::panel>
                BLADE,
            'bare box' => '<x-ds::panel class="mt-4" body="plain">Just a box.</x-ds::panel>',
            'action with wire:navigate' => '<x-ds::panel title="Recent" action="View all" actionHref="/all" wire:navigate>Rows</x-ds::panel>',
            'collapsible passed as false' => '<x-ds::panel title="Billing" :collapsible="false" :open="false" body="plain">Body</x-ds::panel>',
        ];
    }

    #[Test]
    public function every_panel_without_collapsible_renders_exactly_as_before(): void
    {
        $fixture = __DIR__.'/Fixtures/panel-1x.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".Blade::render($blade)."\n";
        }

        $this->assertSame(file_get_contents($fixture), $rendered);
    }
}
