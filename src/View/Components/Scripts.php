<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * ds::scripts — the package's own script, for a host with no build step. Goes
 * in the head, ABOVE Alpine's script tag (or Livewire's).
 *
 * By default a script element pointing at a route the package registers, whose
 * URL carries the content hash of the built file: cached by the browser for a
 * year, and a new URL the moment `composer update` changes the file. Until
 * batch 03 it was inlined, which could not be cached and grew with every
 * behaviour; the route was the alternative that had to survive route caching,
 * subdirectory installs and the host's middleware, and ScriptController and
 * ScriptsTest are where it does.
 *
 * A classic script, with neither `defer` nor `async`, on purpose. It blocks the
 * parser where it stands, so it has run before a deferred Alpine and before
 * Livewire's script at the end of the body, which is what lets it register on
 * `alpine:init`. `defer` here would race Alpine's own `defer` and lose whenever
 * Alpine was written first.
 *
 * `inline` writes the file into the page instead, for a page that cannot reach
 * the route: a static export, a mail preview, a host whose routing will not
 * take a package's.
 *
 * A class rather than an anonymous view because it is not a visual component:
 * no specimen to photograph, no gallery section, no prop that resolves to a
 * class. The tests that hold every view in resources/views/components to the
 * seven-artifact rule would rightly fail it, and exempting it there would
 * teach them to exempt things.
 */
final class Scripts extends Component
{
    /** Read once per process; the file only changes on composer update. */
    private static ?string $source = null;

    private static ?string $hash = null;

    public function __construct(
        /**
         * The CSP nonce for the script element. Left out, the nonce Laravel's
         * Vite helper was given is used, so a host that already calls
         * Vite::useCspNonce() has nothing to pass.
         */
        public ?string $nonce = null,
        /** Write the script into the page rather than linking the route. */
        public bool $inline = false,
    ) {}

    public function render(): Htmlable
    {
        $nonce = $this->nonce ?? $this->viteNonce();
        $nonceAttribute = $nonce === null || $nonce === '' ? '' : ' nonce="'.e($nonce).'"';

        if ($this->inline) {
            return new HtmlString('<script'.$nonceAttribute." data-ds-scripts>\n".self::source().'</script>');
        }

        return new HtmlString('<script src="'.e(self::url()).'"'.$nonceAttribute.' data-ds-scripts></script>');
    }

    /** The versioned URL of the script, through the host's own URL generator. */
    public static function url(): string
    {
        // Absolute, as route() makes it: a relative path drops the base path
        // of an app installed in a subdirectory.
        return route('blade-ui.script', ['hash' => self::hash()]);
    }

    /** Sixteen hex characters of the file's content hash. */
    public static function hash(): string
    {
        return self::$hash ??= substr(hash('xxh128', self::source()), 0, 16);
    }

    public static function source(): string
    {
        if (self::$source !== null) {
            return self::$source;
        }

        $path = __DIR__.'/../../../dist/blade-ui.global.js';

        if (! is_file($path)) {
            // Only reachable from a checkout where `npm run build` never ran;
            // the composer package ships the file. Said loudly, because the
            // alternative is an empty script and a page of Alpine errors that
            // name everything except the cause.
            throw new \RuntimeException(
                'ds::scripts could not find dist/blade-ui.global.js. Run `npm run build` in the shipbytes/blade-ui checkout.'
            );
        }

        return self::$source = (string) file_get_contents($path);
    }

    private function viteNonce(): ?string
    {
        if (! class_exists(Vite::class) || ! app()->bound(Vite::class)) {
            return null;
        }

        return app(Vite::class)->cspNonce();
    }
}
