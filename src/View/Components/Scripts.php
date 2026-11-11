<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * ds::scripts — the package's own script, inlined, for a host with no build
 * step. Goes in the head, ABOVE Alpine's script tag (or Livewire's).
 *
 * Inline rather than a URL, on purpose. A URL means either a route this package
 * registers in someone else's app, which then has to survive route caching,
 * a subdirectory install and whatever middleware sits in front of it; or a
 * vendor:publish copy in public/, which goes stale on the next composer update
 * and fails exactly the way this repo's traps fail: nothing errors, the old
 * behaviour just keeps running. Inline has neither failure mode and costs a few
 * kilobytes per page, which is the trade until the script is big enough that
 * caching it across pages is worth a route.
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

    public function __construct(
        /**
         * The CSP nonce for the inline script element. Left out, the nonce
         * Laravel's Vite helper was given is used, so a host that already
         * calls Vite::useCspNonce() has nothing to pass.
         */
        public ?string $nonce = null,
    ) {}

    public function render(): Htmlable
    {
        $nonce = $this->nonce ?? $this->viteNonce();

        $open = $nonce === null || $nonce === ''
            ? '<script data-ds-scripts>'
            : '<script nonce="'.e($nonce).'" data-ds-scripts>';

        return new HtmlString($open."\n".self::source().'</script>');
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
            // alternative is an empty script element and a page of Alpine
            // errors that name everything except the cause.
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
