<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * In 2.0 (batch 19) ds::sheet gained snap points, drag, a keyboard handle, a
 * non-modal form and a place in the overlay stack, all behind new props
 * (`snap`, `initialSnap`, `draggable`, `dismissible`, `handle`, `modal`).
 * Every sheet that passes none of them must render exactly as it did before:
 * byte for byte, whitespace included.
 *
 * The fixture was written from the view before any of those props existed:
 * the specimen as it stood, a sheet with no title, one with a nested
 * reference and a max height, and the rows' other forms.
 */
final class SheetUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        return [
            'specimen' => <<<'BLADE'
                <x-ds::sheet open="sheetOpen" title="More">
                    <x-ds::sheet-item href="/a" label="Projects" description="All of them">
                        <x-slot:icon><x-ds::icon name="document-text" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item href="/b" label="Admin" tone="accent">
                        <x-slot:icon><x-ds::icon name="shield-check" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item label="Sign out" tone="danger" as="button">
                        <x-slot:icon><x-ds::icon name="logout" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item label="Soon" :disabled="true">
                        <x-slot:icon><x-ds::icon name="banknotes" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                </x-ds::sheet>
                BLADE,
            'no title' => '<x-ds::sheet open="open">Body</x-ds::sheet>',
            'nested reference, max height, class' => <<<'BLADE'
                <x-ds::sheet open="panels.more" title="Share" maxHeight="60vh" class="pb-4" id="share-sheet">
                    <x-ds::sheet-item label="Copy link" :chevron="true" />
                </x-ds::sheet>
                BLADE,
        ];
    }

    #[Test]
    public function every_sheet_without_the_new_props_renders_exactly_as_before(): void
    {
        $fixture = __DIR__.'/Fixtures/sheet-1x.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".Blade::render($blade)."\n";
        }

        $this->assertSame(file_get_contents($fixture), $rendered);
    }
}
