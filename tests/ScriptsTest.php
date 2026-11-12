<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Behaviour;
use Shipbytes\BladeUi\View\Components\Scripts;

/**
 * ds::scripts, the route it links, and the x-data helper the components use to
 * ask for what it registers.
 *
 * What the script DOES, and that it runs before Alpine in each host setup, is
 * driven in a browser by test-behaviour.mjs; what it contains is checked by
 * test-js.mjs. These cover the PHP half: the element the include writes, and
 * that the route answers the way a year of caching needs it to, under the
 * conditions a real host puts it in.
 */
final class ScriptsTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(__DIR__.'/../dist/blade-ui.global.js');
    }

    private function path(): string
    {
        return '/blade-ui/blade-ui.'.Scripts::hash().'.js';
    }

    #[Test]
    public function it_links_a_url_carrying_the_content_hash_of_the_committed_build(): void
    {
        $html = Blade::render('<x-ds::scripts />');

        $this->assertSame(
            '<script src="http://localhost/blade-ui/blade-ui.'.substr(hash('xxh128', $this->source()), 0, 16).'.js" data-ds-scripts></script>',
            $html,
        );

        // A classic, parser-blocking script is what runs before a deferred
        // Alpine. Either attribute would let Alpine start first.
        $this->assertStringNotContainsString('defer', $html);
        $this->assertStringNotContainsString('async', $html);
        $this->assertStringNotContainsString('type="module"', $html);
    }

    #[Test]
    public function inline_writes_the_committed_build_into_the_page_verbatim(): void
    {
        $html = Blade::render('<x-ds::scripts inline />');

        $this->assertStringStartsWith('<script data-ds-scripts>', $html);
        $this->assertStringEndsWith('</script>', $html);
        $this->assertStringNotContainsString(' src=', $html);

        // Verbatim: the content goes out through an HtmlString, not through
        // Blade, so `{{` or an `@` in the JavaScript can never be compiled.
        $this->assertStringContainsString($this->source(), $html);
        $this->assertSame(1, substr_count($html, '</script'), 'the script content closes its own element early');
    }

    #[Test]
    public function a_nonce_is_passed_through_escaped_in_both_forms(): void
    {
        $this->assertStringContainsString(
            '.js" nonce="a&quot;b&lt;" data-ds-scripts></script>',
            Blade::render('<x-ds::scripts :nonce="$nonce" />', ['nonce' => 'a"b<']),
        );
        $this->assertStringStartsWith(
            '<script nonce="a&quot;b&lt;" data-ds-scripts>',
            Blade::render('<x-ds::scripts inline :nonce="$nonce" />', ['nonce' => 'a"b<']),
        );
    }

    #[Test]
    public function without_one_it_takes_the_nonce_the_vite_helper_was_given(): void
    {
        // A host with a CSP has already handed Laravel's Vite helper its nonce
        // for its own tags; making them pass it a second time is how one tag
        // ends up without it.
        app(Vite::class)->useCspNonce('from-vite');

        try {
            $this->assertStringContainsString(' nonce="from-vite" ', Blade::render('<x-ds::scripts />'));
            $this->assertStringContainsString(
                ' nonce="explicit" ',
                Blade::render('<x-ds::scripts nonce="explicit" />'),
                'an explicit nonce should win over the Vite one',
            );
            $this->assertStringStartsWith('<script nonce="from-vite" data-ds-scripts>', Blade::render('<x-ds::scripts inline />'));
        } finally {
            app(Vite::class)->useCspNonce('');
        }
    }

    #[Test]
    public function the_route_serves_the_file_as_javascript_cached_for_a_year(): void
    {
        $response = $this->get($this->path());

        $response->assertOk();
        $this->assertSame($this->source(), $response->getContent());
        $this->assertSame('text/javascript; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));

        $cache = $response->headers->get('Cache-Control');
        foreach (['public', 'max-age=31536000', 'immutable'] as $directive) {
            $this->assertStringContainsString($directive, $cache);
        }
        $this->assertSame('"'.Scripts::hash().'"', $response->headers->get('ETag'));
    }

    #[Test]
    public function a_matching_etag_is_answered_with_a_bodiless_304(): void
    {
        $response = $this->get($this->path(), ['If-None-Match' => '"'.Scripts::hash().'"']);

        $response->assertStatus(304);
        $this->assertSame('', $response->getContent());
    }

    #[Test]
    public function an_old_hash_gets_the_current_file_but_is_not_cached_as_immutable(): void
    {
        // A page rendered before a deploy still names the old URL. It must work,
        // and the new file must not be pinned to that URL for a year.
        $response = $this->get('/blade-ui/blade-ui.0123456789abcdef.js');

        $response->assertOk();
        $this->assertSame($this->source(), $response->getContent());
        $this->assertStringNotContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function the_route_runs_no_middleware_and_sets_no_cookie(): void
    {
        $route = app('router')->getRoutes()->getByName('blade-ui.script');

        $this->assertNotNull($route);
        $this->assertSame([], $route->gatherMiddleware(), 'the script route sits behind middleware: a guest on a login page could be refused');
        $this->assertSame([], $this->get($this->path())->headers->getCookies());
    }

    #[Test]
    public function it_survives_route_caching(): void
    {
        // What `php artisan route:cache` does with the routes, and what the
        // framework loads from the cache file on the next boot.
        $compiled = app('router')->getRoutes()->compile();
        $cached = unserialize(serialize($compiled));

        app('router')->setCompiledRoutes($cached);

        $this->assertStringContainsString('/blade-ui/blade-ui.', Blade::render('<x-ds::scripts />'));
        $this->get($this->path())->assertOk()->assertHeader('Content-Type', 'text/javascript; charset=utf-8');
    }

    #[Test]
    public function under_a_subdirectory_install_the_url_carries_the_base_path_and_resolves(): void
    {
        Route::get('/page', fn () => Blade::render('<x-ds::scripts />'));
        app('router')->getRoutes()->refreshNameLookups();

        $kernel = $this->app->make(Kernel::class);
        $server = ['SCRIPT_NAME' => '/app/index.php', 'SCRIPT_FILENAME' => '/srv/app/public/index.php'];

        $page = $kernel->handle(Request::create('http://example.test/app/page', 'GET', server: $server));
        $this->assertStringContainsString('src="http://example.test/app/blade-ui/blade-ui.'.Scripts::hash().'.js"', $page->getContent());

        $script = $kernel->handle(Request::create('http://example.test/app'.$this->path(), 'GET', server: $server));
        $this->assertSame(200, $script->getStatusCode());
        $this->assertSame($this->source(), $script->getContent());
    }

    protected function underAnotherPrefix($app): void
    {
        $app['config']->set('blade-ui.script.prefix', '/assets/ds/');
    }

    #[Test]
    #[DefineEnvironment('underAnotherPrefix')]
    public function the_prefix_comes_from_config(): void
    {
        $this->assertStringContainsString('src="http://localhost/assets/ds/blade-ui.', Blade::render('<x-ds::scripts />'));
        $this->get('/assets/ds/blade-ui.'.Scripts::hash().'.js')->assertOk();
        $this->get($this->path())->assertNotFound();
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
