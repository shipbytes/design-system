<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * Batch 15 added options to four existing components: `icon` on ds::button,
 * `expanding` on ds::button-group, `initials` on ds::avatar and the `menu`
 * variant of ds::theme-switcher. Every one of them that does not ask for the
 * new option must render exactly as before: byte for byte, whitespace
 * included.
 *
 * The fixture was written from the views before any of those existed. The
 * cases are the specimens of all four (the theme switchers given a `name`,
 * because the default one is random), with the group's split button left
 * out: it holds a ds::dropdown, whose arrow keys were widened in the same
 * batch to reach menuitemradio, a deliberate change of its output.
 */
final class ButtonUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        $specimens = Specimens::all();

        return [
            'button variants' => $specimens['button/variants'],
            'button tones' => $specimens['button/tones'],
            'button sizes' => $specimens['button/sizes'],
            'button states' => $specimens['button/states'],
            'groups' => <<<'BLADE'
                <x-ds::button-group label="Alignment">
                    <x-ds::button>Left</x-ds::button>
                    <x-ds::button>Center</x-ds::button>
                    <x-ds::button>Right</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Pages" size="sm" variant="primary">
                    <x-ds::button iconOnly aria-label="Previous"><x-ds::icon name="chevron-left" /></x-ds::button>
                    <x-ds::button iconOnly aria-label="Next"><x-ds::icon name="chevron-right" /></x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Zoom" orientation="vertical" variant="ghost" size="lg">
                    <x-ds::button>In</x-ds::button>
                    <x-ds::button>Out</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Deploy" variant="danger">
                    <x-ds::button>Roll back</x-ds::button>
                    <x-ds::button variant="secondary" tone="danger">Pause</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Text" class="mt-2" id="fmt">
                    <x-ds::button :loading="true">Bold</x-ds::button>
                    <x-ds::button href="/i" aria-pressed="true">Italic</x-ds::button>
                </x-ds::button-group>
                BLADE,
            'avatars' => $specimens['avatar/all'],
            'theme switchers' => <<<'BLADE'
                <x-ds::theme-switcher name="t1" />
                <x-ds::theme-switcher variant="button" name="t2" />
                <x-ds::theme-switcher variant="button" size="sm" label="Appearance" storageKey="app-theme" name="t3" />
                <x-ds::theme-switcher size="lg" :hideLabel="true" name="theme" :transition="false" />
                <x-ds::theme-switcher variant="nonsense" size="huge" name="t4" />
                BLADE,
        ];
    }

    #[Test]
    public function every_one_without_the_new_options_renders_exactly_as_before(): void
    {
        $fixture = __DIR__.'/Fixtures/button-1x.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".Blade::render($blade)."\n";
        }

        $this->assertSame(file_get_contents($fixture), $rendered);
    }
}
