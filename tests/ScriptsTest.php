<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Behaviour;

/**
 * ds::scripts, and the x-data helper the components use to ask for what it
 * registers.
 *
 * What the script DOES is driven in a browser by test-behaviour.mjs; what it
 * contains is checked by test-js.mjs. These cover the PHP half: that the include
 * emits the committed file, untouched, in a script element a CSP will run.
 */
final class ScriptsTest extends TestCase
{
    #[Test]
    public function it_inlines_the_committed_classic_build_verbatim(): void
    {
        $html = Blade::render('<x-ds::scripts />');
        $source = file_get_contents(__DIR__.'/../dist/blade-ui.global.js');

        $this->assertStringStartsWith('<script data-ds-scripts>', $html);
        $this->assertStringEndsWith('</script>', $html);

        // Verbatim: the content goes out through an HtmlString, not through
        // Blade, so `{{` or an `@` in the JavaScript can never be compiled.
        $this->assertStringContainsString($source, $html);
        $this->assertSame(1, substr_count($html, '</script'), 'the script content closes its own element early');
    }

    #[Test]
    public function a_nonce_is_passed_through_escaped(): void
    {
        $html = Blade::render('<x-ds::scripts :nonce="$nonce" />', ['nonce' => 'a"b<']);

        $this->assertStringStartsWith('<script nonce="a&quot;b&lt;" data-ds-scripts>', $html);
    }

    #[Test]
    public function without_one_it_takes_the_nonce_the_vite_helper_was_given(): void
    {
        // A host with a CSP has already handed Laravel's Vite helper its nonce
        // for its own tags; making them pass it a second time is how one tag
        // ends up without it.
        app(Vite::class)->useCspNonce('from-vite');

        try {
            $this->assertStringStartsWith(
                '<script nonce="from-vite" data-ds-scripts>',
                Blade::render('<x-ds::scripts />'),
            );
            $this->assertStringStartsWith(
                '<script nonce="explicit" data-ds-scripts>',
                Blade::render('<x-ds::scripts nonce="explicit" />'),
                'an explicit nonce should win over the Vite one',
            );
        } finally {
            app(Vite::class)->useCspNonce('');
        }
    }

    #[Test]
    public function the_helper_guards_the_name_and_says_what_is_missing(): void
    {
        $expression = Behaviour::data('dsClipboard', ['text' => 'sk_live_42']);

        $this->assertStringStartsWith("typeof dsClipboard === 'function' ? dsClipboard({\"text\":\"sk_live_42\"})", $expression);
        $this->assertStringContainsString('<x-ds::scripts />', $expression, 'the error does not name the include');

        $this->assertStringContainsString('dsClipboard()', Behaviour::data('dsClipboard'));
        $this->assertStringContainsString('dsClipboard(options)', Behaviour::data('dsClipboard', 'options'));

        // An empty PHP array is a JSON array; the behaviour expects an object.
        $this->assertStringContainsString('dsClipboard({})', Behaviour::data('dsClipboard', []));
    }

    #[Test]
    public function the_helper_refuses_a_name_without_the_prefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Behaviour::data('clipboard');
    }

    #[Test]
    public function the_helper_output_survives_an_attribute_echo(): void
    {
        // Every component echoes it with {{ }}, so the angle brackets and quotes
        // in the message must come out as entities, not as markup.
        $html = Blade::render('<div x-data="{{ $expr }}"></div>', ['expr' => Behaviour::data('dsClipboard')]);

        $this->assertStringNotContainsString('<x-ds::', $html);
        $this->assertSame(1, preg_match('/^<div x-data="[^"]*"><\/div>$/', $html), $html);
    }
}
