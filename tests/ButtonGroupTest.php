<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * A button group hands its size and variant to the buttons inside it, and to
 * nothing else. The second half is the one that matters: `@aware` takes a key
 * from the nearest ancestor that has it, whatever component that is, so a
 * button reading a plain `size` would have turned large in the footer of
 * every `ds::modal size="lg"`. See View/Components/ButtonGroup.php.
 */
final class ButtonGroupTest extends TestCase
{
    /** @return array<string, string> button id => its class attribute */
    private function buttons(string $blade): array
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.Blade::render($blade));
        libxml_clear_errors();

        $out = [];
        foreach ((new \DOMXPath($document))->query('//button[@id]') as $button) {
            $out[$button->getAttribute('id')] = ' '.$button->getAttribute('class').' ';
        }

        return $out;
    }

    #[Test]
    public function the_group_sets_size_and_variant_and_a_button_can_override_both(): void
    {
        $b = $this->buttons(<<<'BLADE'
            <x-ds::button-group label="Pages" size="sm">
                <x-ds::button id="a">Previous</x-ds::button>
                <x-ds::button id="b" size="lg" variant="primary">Next</x-ds::button>
            </x-ds::button-group>
            BLADE);

        $this->assertStringContainsString(' h-control-sm ', $b['a']);
        $this->assertStringContainsString(' border-border ', $b['a'], 'the group default is secondary');
        $this->assertStringContainsString(' h-control-lg ', $b['b']);
        $this->assertStringContainsString(' bg-surface-inverse ', $b['b']);
        foreach ($b as $classes) {
            $this->assertStringContainsString(' rounded-s-(--ds-join-start) ', $classes, 'a grouped button takes its corners from the group');
            $this->assertStringNotContainsString(' rounded-control ', $classes);
        }
    }

    #[Test]
    public function a_button_outside_a_group_ignores_an_ancestors_size(): void
    {
        $b = $this->buttons(<<<'BLADE'
            <x-ds::modal open="confirm" title="Delete?" size="lg">
                Body
                <x-slot:footer><x-ds::button id="in-modal">Delete</x-ds::button></x-slot:footer>
            </x-ds::modal>
            <x-ds::button id="alone">Save</x-ds::button>
            BLADE);

        foreach (['in-modal', 'alone'] as $id) {
            $this->assertStringContainsString(' sm:h-control-md ', $b[$id], "{$id} is not the default md size");
            $this->assertStringContainsString(' rounded-control ', $b[$id], "{$id} took group corners");
            $this->assertStringContainsString(' bg-surface-inverse ', $b[$id], "{$id} is not the default primary");
        }
    }

    #[Test]
    public function the_split_buttons_trigger_inside_a_dropdown_still_joins_the_group(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ds::button-group label="Save options">
                <x-ds::button id="save">Save</x-ds::button>
                <x-ds::dropdown>
                    <x-slot:trigger>
                        <x-ds::button id="more" iconOnly aria-label="More save options">v</x-ds::button>
                    </x-slot:trigger>
                    <x-ds::dropdown-item href="/draft">Save as draft</x-ds::dropdown-item>
                </x-ds::dropdown>
            </x-ds::button-group>
            BLADE);

        $this->assertStringContainsString('role="group" aria-label="Save options"', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*rounded-e-\(--ds-join-end\)[^>]*id="more"/s', $html);
    }

    #[Test]
    public function vertical_joins_top_and_bottom_and_filled_buttons_part_by_a_hairline(): void
    {
        $vertical = Blade::render('<x-ds::button-group label="Zoom" orientation="vertical"><x-ds::button>In</x-ds::button></x-ds::button-group>');
        $this->assertStringContainsString('flex-col', $vertical);
        $this->assertStringContainsString('*:not-first:-mt-px', $vertical);
        $this->assertStringContainsString('rounded-t-(--ds-join-start)', $vertical);

        $filled = Blade::render('<x-ds::button-group label="Go" variant="primary"><x-ds::button>A</x-ds::button></x-ds::button-group>');
        $this->assertStringContainsString('gap-px', $filled);
        $this->assertStringNotContainsString('-ms-px', $filled);
    }
}
