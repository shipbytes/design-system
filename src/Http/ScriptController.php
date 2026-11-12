<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Http;

use Illuminate\Http\Request;
use Shipbytes\BladeUi\View\Components\Scripts;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves dist/blade-ui.global.js at the URL ds::scripts writes.
 *
 * The hash in the URL is the file's content hash, so a URL never changes
 * meaning: a browser may keep it for a year without asking again, and a
 * `composer update` that changes the script changes the URL with it. That is
 * the failure a published copy in public/ has and this does not: there, the
 * old script keeps running after an update and nothing says so.
 *
 * A controller rather than a closure so the route survives `route:cache` on
 * any Laravel version, and registered by the provider outside the `web` group,
 * so no session is started, no cookie is set and no CSRF or auth middleware
 * stands in front of a static file.
 */
final class ScriptController
{
    public function __invoke(Request $request, string $hash): Response
    {
        $current = Scripts::hash();

        $response = new Response(Scripts::source(), 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setEtag($current);

        if (hash_equals($current, $hash)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        } else {
            // A page rendered before the last deploy still names the old hash.
            // It gets today's script, so it works, but marked for revalidation:
            // caching it as immutable would pin the new file to the old URL.
            $response->headers->set('Cache-Control', 'no-cache');
        }

        $response->isNotModified($request);

        return $response;
    }
}
