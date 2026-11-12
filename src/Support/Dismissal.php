<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

/**
 * The check ds::announcement-bar runs before it paints, for a bar dismissed
 * on an earlier visit.
 *
 * Written as the bar's FIRST child, so it runs the moment the parser reaches
 * it: the bar's element exists, nothing inside it has been parsed, and the
 * browser has not painted it. A dismissed bar is hidden there and then, with
 * no frame of it shown and no layout shift below it. Waiting for Alpine would
 * draw the bar on every load and then pull it away, pushing the page up.
 *
 * The other ways round were worse. Rendering the bar hidden and revealing it
 * once storage says so hides it from every page that never runs a script,
 * and still shifts the page for every reader who has NOT dismissed it, which
 * is most of them. A cookie would let PHP decide, but the package sets no
 * cookies and reads no request.
 *
 * Plain inline JavaScript, a hundred-odd bytes, no Alpine and no package
 * script. Storage that throws is caught and the bar shows. The nonce is taken
 * the way ds::scripts and ds::theme-script take it; under a policy that
 * blocks the script anyway, dsDismiss repeats the check when Alpine starts.
 */
final class Dismissal
{
    public static function script(string $key, ?string $nonce = null): HtmlString
    {
        $nonce ??= self::viteNonce();
        $nonceAttribute = $nonce === null || $nonce === '' ? '' : ' nonce="'.e($nonce).'"';

        // JSON_HEX_TAG so a key holding a closing script tag cannot end the element.
        $encoded = json_encode($key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        // The bar is hidden twice over: the attribute for the accessibility
        // tree, and display for a host whose CSS does not honour [hidden]
        // against the bar's own display class.
        $source = '(function(s){try{if(localStorage.getItem('.$encoded.')!==null){var b=s.parentNode;'
            .'b.hidden=true;b.style.display="none"}}catch(e){}})(document.currentScript)';

        return new HtmlString('<script'.$nonceAttribute.' data-ds-dismiss-check>'.$source.'</script>');
    }

    private static function viteNonce(): ?string
    {
        if (! class_exists(Vite::class) || ! app()->bound(Vite::class)) {
            return null;
        }

        return app(Vite::class)->cspNonce();
    }
}
