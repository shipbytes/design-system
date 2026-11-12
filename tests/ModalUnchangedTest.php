<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * In 2.0 (batch 12) ds::modal's layer, its focus trap, focus handling,
 * backdrop and panel transitions, moved into a recipe
 * (resources/views/partials/modal.php) so ds::command-palette could open in
 * the same layer rather than a second dialog. Every modal must render exactly
 * as it did before the move: byte for byte, whitespace included.
 *
 * The fixture was written from the view before the recipe existed: the
 * specimen, plus one with a footer and an autofocus field, and one opened
 * through a nested reference.
 *
 * It has been rewritten once, deliberately, in batch 19, when the modal took
 * its place in the overlay stack (the recipe's `focus`, `escape`, `trap`,
 * `backdrop` and `covered`): that commit's diff of the fixture is exactly
 * what the stack added to a modal, and nothing else. A modal with nothing
 * stacked on it behaves as it did.
 */
final class ModalUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        return [
            'specimen' => Specimens::all()['modal/all'],
            'form with autofocus' => <<<'BLADE'
                <x-ds::modal open="show.rename" title="Rename" size="sm" class="mt-2">
                    <input autofocus name="name">
                    <x-slot:footer><button type="button">Save</button></x-slot:footer>
                </x-ds::modal>
                BLADE,
        ];
    }

    #[Test]
    public function every_modal_renders_exactly_as_it_did(): void
    {
        $fixture = __DIR__.'/Fixtures/modal-1x.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".Blade::render($blade)."\n";
        }

        $flat = fn (string $html) => preg_replace('/\s+/', ' ', $html);

        $this->assertSame($flat(file_get_contents($fixture)), $flat($rendered));
    }
}
