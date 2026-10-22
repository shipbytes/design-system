<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Tests\Fixtures\SpecimenComponent;

/**
 * Every specimen, rendered inside a Livewire component.
 *
 * Livewire wraps each Blade `@if` and `@foreach` in `<!--[if BLOCK]>` markers
 * and skips one that sits inside an HTML opening tag. It decides that by
 * looking back for the nearest `<` that starts a tag, in the RAW template,
 * comments included. So a comment in an attribute expression that names a tag
 * with angle brackets moves that `<`, the marker lands inside the real tag,
 * and the browser prints the tag's `>` as text: every button read "> Save".
 *
 * Nothing in a plain-Blade render shows it, because the markers only exist
 * while Livewire is rendering. This is the one test here that boots Livewire;
 * the components themselves still assume nothing about it.
 */
final class LivewireMarkersTest extends TestCase
{
    private static ?string $compiled = null;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), LivewireServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // A compiled view is cached by path. Without its own directory this
        // test would reuse the components the other tests compiled before
        // Livewire's precompiler was registered, and could never fail.
        self::$compiled ??= sys_get_temp_dir().'/ds-livewire-views-'.getmypid();
        File::ensureDirectoryExists(self::$compiled);
        $app['config']->set('view.compiled', self::$compiled);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$compiled !== null) {
            File::deleteDirectory(self::$compiled);
        }
    }

    public static function specimens(): array
    {
        return array_map(fn ($blade) => [$blade], Specimens::all());
    }

    #[Test]
    #[DataProvider('specimens')]
    public function no_morph_marker_lands_inside_a_tag(string $blade): void
    {
        $html = $this->renderInLivewire($blade);

        $found = preg_match('/<[a-zA-Z][^<>]*<!--\[if BLOCK\]>/', $html, $match);

        $this->assertSame(0, $found, $found
            ? "a Livewire morph marker landed inside an opening tag:\n  ".substr($match[0], 0, 160)
                ."\nLook for a tag written with angle brackets in a comment of that component."
            : '');
    }

    #[Test]
    public function livewire_injects_markers_here_at_all(): void
    {
        // Without markers the test above passes for the wrong reason. The
        // button has two conditionals, so it always carries some.
        $this->assertStringContainsString(
            '<!--[if BLOCK]>',
            $this->renderInLivewire(Specimens::all()['button/states']),
            'Livewire injected no markers, so the marker check proves nothing',
        );
    }

    private function renderInLivewire(string $blade): string
    {
        return Livewire::test(SpecimenComponent::class, ['blade' => $blade])->html();
    }
}
