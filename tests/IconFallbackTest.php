<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Icons;
use Throwable;

/**
 * ds::icon's opt-in `fallback`, and the existence check behind it.
 *
 * A consumer fed icon names from config into ds::icon. One entry carried a
 * Heroicons v1 name with no alias, and every page that rendered the nav was a
 * 500 — including the page where the entry could be fixed. A name in a template
 * is the developer's to get right and still throws; a name from data can now
 * degrade to a stand-in and say so in the log.
 */
final class IconFallbackTest extends TestCase
{
    /** The first path of a blade-heroicons svg, which is what identifies the icon in rendered markup. */
    private function path(string $file): string
    {
        $svg = file_get_contents(__DIR__.'/../vendor/blade-ui-kit/blade-heroicons/resources/svg/'.$file.'.svg');

        preg_match('/ d="([^"]+)"/', $svg, $m);

        return $m[1];
    }

    #[Test]
    public function an_unknown_name_without_a_fallback_still_throws(): void
    {
        $message = null;

        try {
            Blade::render('<x-ds::icon name="grid" />');
        } catch (Throwable $e) {
            // Caught outside the assertions: PHPUnit fails by throwing, and an
            // assertion inside the catch would swallow its own report.
            $message = $e->getMessage();
        }

        $this->assertNotNull($message, 'an unknown icon name rendered without a fallback');
        $this->assertStringContainsString('heroicon-o-grid', $message);
    }

    #[Test]
    public function an_unknown_name_with_a_fallback_renders_the_fallback_and_logs_once(): void
    {
        Log::spy();

        // Twice in one request, as a nav renders the same item in the rail and
        // the mobile drawer: one log line, not one per render.
        $html = Blade::render(
            '<x-ds::icon name="grid" fallback="question-mark-circle" />'
            .'<x-ds::icon name="grid" fallback="question-mark-circle" />'
        );

        $this->assertSame(2, substr_count($html, $this->path('o-question-mark-circle')));
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('blade-ui: icon "grid" not found, rendered "question-mark-circle"');
    }

    #[Test]
    public function the_fallback_takes_the_same_variant(): void
    {
        $html = Blade::render('<x-ds::icon name="grid" variant="solid" fallback="question-mark-circle" />');

        $this->assertStringContainsString($this->path('s-question-mark-circle'), $html);
    }

    #[Test]
    public function an_alias_with_a_fallback_renders_the_aliased_icon(): void
    {
        Log::spy();

        // `search` is a v1 name aliased to magnifying-glass. A check that skipped
        // the alias map would call it missing and swap in the question mark.
        $html = Blade::render('<x-ds::icon name="search" fallback="question-mark-circle" />');

        $this->assertStringContainsString($this->path('o-magnifying-glass'), $html);
        $this->assertStringNotContainsString($this->path('o-question-mark-circle'), $html);
        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function a_real_name_with_a_fallback_renders_itself(): void
    {
        $html = Blade::render('<x-ds::icon name="squares-2x2" fallback="question-mark-circle" />');

        $this->assertStringContainsString($this->path('o-squares-2x2'), $html);
        $this->assertStringNotContainsString($this->path('o-question-mark-circle'), $html);
    }

    public static function names(): array
    {
        return [
            'a v2 name' => ['squares-2x2', 'outline', true],
            'a v1 alias' => ['search', 'outline', true],
            'a bogus name' => ['grid', 'outline', false],
            'a solid name' => ['check-circle', 'solid', true],
            'a micro name' => ['check-circle', 'micro', true],
            'a bogus solid name' => ['grid', 'solid', false],
        ];
    }

    #[Test]
    #[DataProvider('names')]
    public function exists_resolves_the_way_the_component_does(string $name, string $variant, bool $exists): void
    {
        $this->assertSame($exists, Icons::exists($name, $variant));
    }

    #[Test]
    public function a_host_configured_blade_icons_fallback_does_not_make_a_name_exist(): void
    {
        // blade-icons' own fallback makes its factory return a stand-in instead
        // of throwing. The component path has no such fallback and would still
        // throw, so exists() must still say no.
        config()->set('blade-icons.fallback', 'heroicon-o-question-mark-circle');
        $this->app->forgetInstance(\BladeUI\Icons\Factory::class);

        $this->assertFalse(Icons::exists('grid'));
        $this->assertTrue(Icons::exists('squares-2x2'));
    }
}
