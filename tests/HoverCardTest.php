<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What ds::hover-card renders before Alpine: a card that adds nothing to the
 * trigger and can sit inside running text. The behaviour (delays, touch,
 * Escape) is driven in test-behaviour.mjs.
 */
final class HoverCardTest extends TestCase
{
    private function render(string $attributes = ''): string
    {
        return Blade::render(
            "<x-ds::hover-card {$attributes}>\n<x-slot:trigger><a href=\"/people/ada\">Ada</a></x-slot:trigger>\nAnalyst\n</x-ds::hover-card>"
        );
    }

    #[Test]
    public function the_trigger_keeps_its_own_markup(): void
    {
        $this->assertStringContainsString('<a href="/people/ada">Ada</a>', $this->render());
    }

    #[Test]
    public function the_card_claims_no_role_and_describes_nothing(): void
    {
        $html = $this->render();

        // Not tooltip (it can hold links), not dialog (focus never moves in).
        $this->assertStringNotContainsString('role=', $html);
        $this->assertStringNotContainsString('aria-describedby', $html);
        $this->assertStringNotContainsString('aria-expanded', $html);
    }

    #[Test]
    public function it_is_spans_throughout_so_it_can_sit_in_a_paragraph(): void
    {
        $html = $this->render();

        $this->assertDoesNotMatchRegularExpression('/<div\b/', $html, 'a div inside a p ends the paragraph early');
        $this->assertStringContainsString('data-ds-hover-card-panel', $html);
    }

    #[Test]
    public function hidden_until_alpine_and_placed_by_the_anchor(): void
    {
        $html = $this->render('placement="top-end"');

        $this->assertStringContainsString('x-cloak', $html);
        $this->assertStringContainsString("placement: 'top-end'", $html);
        $this->assertStringContainsString('data-side="top"', $html);
    }

    #[Test]
    public function the_default_id_is_stable_across_renders(): void
    {
        preg_match('/id="(ds-hover-card-[0-9a-f]{8})"/', $this->render(), $first);
        preg_match('/id="(ds-hover-card-[0-9a-f]{8})"/', $this->render(), $second);

        $this->assertNotEmpty($first);
        $this->assertSame($first[1], $second[1]);
    }
}
