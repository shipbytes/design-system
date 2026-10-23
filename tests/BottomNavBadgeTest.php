<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The bottom-nav item's count pill: what it draws, and what it says.
 *
 * The consumer passes the RAW count, so the cap and the "nothing to show"
 * cases are this component's job. Get them wrong and every consumer either
 * shows "0" on a quiet tab or "137" in a pill with room for two characters.
 */
final class BottomNavBadgeTest extends TestCase
{
    private function item(string $badge): string
    {
        return Blade::render(
            '<x-ds::bottom-nav-item href="/support" label="Support" '.$badge.'>'
            .'<x-slot:icon><x-ds::icon name="lifebuoy" size="6" /></x-slot:icon>'
            .'</x-ds::bottom-nav-item>'
        );
    }

    /** The visible pill's text, or null when no pill rendered. */
    private function pill(string $html): ?string
    {
        return preg_match('/<span\s+aria-hidden="true"\s+class="absolute[^"]*"\s*>([^<]*)<\/span>/', $html, $m)
            ? $m[1]
            : null;
    }

    /** The label span's text with markup stripped: what a screen reader names the item. */
    private function spokenLabel(string $html): string
    {
        preg_match('/<span class="mt-1[^"]*">(.*?)<\/span>\s*<\/a>/s', $html, $m);

        $this->assertNotEmpty($m, 'no label span rendered');

        return trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('/<!--.*?-->/s', '', $m[1]))));
    }

    public static function counts(): array
    {
        return [
            'one' => [':badge="1"', '1', 'Support, 1 new'],
            'nine' => [':badge="9"', '9', 'Support, 9 new'],
            'ten caps' => [':badge="10"', '9+', 'Support, 10 new'],
            'large caps' => [':badge="137"', '9+', 'Support, 137 new'],
            'numeric string' => ['badge="3"', '3', 'Support, 3 new'],
            'numeric string caps' => ['badge="12"', '9+', 'Support, 12 new'],
            'word as given' => ['badge="new"', 'new', 'Support, new'],
        ];
    }

    #[Test]
    #[DataProvider('counts')]
    public function it_draws_a_capped_pill_and_speaks_the_real_count(string $badge, string $drawn, string $spoken): void
    {
        $html = $this->item($badge);

        $this->assertSame($drawn, $this->pill($html));
        $this->assertSame($spoken, $this->spokenLabel($html));
    }

    public static function nothing(): array
    {
        return [
            'no prop' => [''],
            'null' => [':badge="null"'],
            'zero' => [':badge="0"'],
            'zero string' => ['badge="0"'],
            'empty string' => ['badge=""'],
            'negative' => [':badge="-2"'],
        ];
    }

    #[Test]
    #[DataProvider('nothing')]
    public function it_draws_nothing_when_there_is_nothing_to_count(string $badge): void
    {
        $html = $this->item($badge);

        $this->assertNull($this->pill($html));
        $this->assertStringNotContainsString('sr-only', $html);
        $this->assertSame('Support', $this->spokenLabel($html));
    }

    #[Test]
    public function the_pill_positions_against_the_icon_not_the_item(): void
    {
        $html = $this->item(':badge="3"');

        $this->assertMatchesRegularExpression('/<span class="relative flex [^"]*">\s*<svg/', $html);
    }
}
