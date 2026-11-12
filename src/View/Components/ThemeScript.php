<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * ds::theme-script — puts the reader's theme on the page before it paints.
 *
 * Goes in the head, BEFORE the stylesheets, so the browser never draws one
 * frame in the wrong theme: a stored "dark" that only Alpine applied would
 * flash light on every load, for as long as the page takes to reach Alpine.
 *
 * A few hundred bytes, inline, and plain: no Alpine, no package script, no
 * request. It does what dsTheme does to the root (one of `dark` and `light`,
 * and color-scheme), from the same localStorage key, with the same reading of
 * a missing or unreadable value: the system's preference. A blocked or absent
 * localStorage is caught, so the page still gets the system's theme.
 *
 * A class for the reason ds::scripts is one: it is not a visual component and
 * has nothing to photograph. The nonce is taken the way ds::scripts takes it.
 */
final class ThemeScript extends Component
{
    public function __construct(
        /** The key ds::theme-switcher stores the choice under. */
        public string $storageKey = 'ds-theme',
        /**
         * The CSP nonce for the script element. Left out, the nonce Laravel's
         * Vite helper was given is used.
         */
        public ?string $nonce = null,
    ) {}

    public function render(): Htmlable
    {
        $nonce = $this->nonce ?? $this->viteNonce();
        $nonceAttribute = $nonce === null || $nonce === '' ? '' : ' nonce="'.e($nonce).'"';

        // JSON_HEX_TAG so a key holding "</script>" cannot end the element.
        $key = json_encode($this->storageKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new HtmlString('<script'.$nonceAttribute.' data-ds-theme-script>'.self::source($key).'</script>');
    }

    /** The snippet, for a key already encoded as a JavaScript string. */
    public static function source(string $key): string
    {
        return '(function(){var c;try{c=localStorage.getItem('.$key.')}catch(e){}'
            .'var d=c==="dark"||(c!=="light"&&!!window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches),'
            .'r=document.documentElement;r.classList.toggle("dark",d);r.classList.toggle("light",!d);'
            .'r.style.colorScheme=d?"dark":"light"})()';
    }

    private function viteNonce(): ?string
    {
        if (! class_exists(Vite::class) || ! app()->bound(Vite::class)) {
            return null;
        }

        return app(Vite::class)->cspNonce();
    }
}
