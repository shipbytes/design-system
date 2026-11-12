<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * ds::theme-script, the PHP half: the element it writes. What the snippet does
 * to a page (stored choice, system preference, blocked storage) is driven in
 * a browser by test-behaviour.mjs.
 */
final class ThemeScriptTest extends TestCase
{
    #[Test]
    public function it_writes_one_inline_classic_script_reading_the_default_key(): void
    {
        $html = Blade::render('<x-ds::theme-script />');

        $this->assertStringStartsWith('<script data-ds-theme-script>(function(){', $html);
        $this->assertStringEndsWith('})()</script>', $html);
        $this->assertStringContainsString('localStorage.getItem("ds-theme")', $html);
        $this->assertSame(1, substr_count($html, '</script'));

        // Run where it stands, before the stylesheet paints: anything that
        // defers it lets the first frame draw the wrong theme.
        $this->assertStringNotContainsString(' src=', $html);
        $this->assertStringNotContainsString('defer', $html);
        $this->assertStringNotContainsString('async', $html);
        $this->assertStringNotContainsString('type="module"', $html);
    }

    #[Test]
    public function the_key_is_a_javascript_string_that_cannot_close_the_element(): void
    {
        $html = Blade::render('<x-ds::theme-script :storageKey="$key" />', ['key' => 'app"</script><b>']);

        $this->assertSame(1, substr_count($html, '</script'), 'the key closed the element early');
        $this->assertStringContainsString('localStorage.getItem("app\"\u003C/script\u003E\u003Cb\u003E")', $html);
    }

    #[Test]
    public function a_nonce_is_written_escaped_and_the_vite_nonce_is_the_default(): void
    {
        $this->assertStringStartsWith(
            '<script nonce="a&quot;b&lt;" data-ds-theme-script>',
            Blade::render('<x-ds::theme-script :nonce="$nonce" />', ['nonce' => 'a"b<']),
        );

        app(Vite::class)->useCspNonce('from-vite');
        try {
            $this->assertStringStartsWith('<script nonce="from-vite" data-ds-theme-script>', Blade::render('<x-ds::theme-script />'));
            $this->assertStringStartsWith('<script nonce="mine" data-ds-theme-script>', Blade::render('<x-ds::theme-script nonce="mine" />'));
        } finally {
            app(Vite::class)->useCspNonce('');
        }
    }

    #[Test]
    public function it_applies_exactly_one_of_dark_and_light_and_the_colour_scheme(): void
    {
        $html = Blade::render('<x-ds::theme-script />');

        $this->assertStringContainsString('classList.toggle("dark",d)', $html);
        $this->assertStringContainsString('classList.toggle("light",!d)', $html);
        $this->assertStringContainsString('colorScheme', $html);
        // A blocked localStorage throws on access; the page must still load.
        $this->assertStringContainsString('try{c=localStorage.getItem(', $html);
    }
}
