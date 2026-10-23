<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory;
use Illuminate\Support\Facades\Log;

/**
 * How an icon name becomes a blade-heroicons component, in ONE place.
 *
 * ds::icon renders through it and `exists()` checks through it, so the two can
 * never disagree about an alias or a variant prefix. They did not share it at
 * first, and a check that resolves names differently from the renderer is worse
 * than none: it passes a name the page then throws on.
 */
final class Icons
{
    /** The component tag ds::icon hands to x-dynamic-component, e.g. heroicon-o-x-mark. */
    public static function component(string $name, string $variant = 'outline'): string
    {
        // The source app was built against Heroicons v1 and picked up v2 icons
        // later, so both generations of name appear in it. v2 renamed 33 of the
        // names in use; the alias map lets an old one resolve. See
        // icons/icons.json for the full mapping.
        $resolved = config('blade-ui.icon_aliases', [])[$name] ?? $name;

        // blade-heroicons prefixes: o- outline (24), s- solid (24),
        // m- mini (20), c- micro (16).
        $prefix = match ($variant) {
            'solid' => 's',
            'mini' => 'm',
            'micro' => 'c',
            default => 'o',
        };

        return "heroicon-{$prefix}-{$resolved}";
    }

    /**
     * Whether ds::icon would find this icon, asked of blade-icons' own factory
     * rather than of a directory, so a host's extra icon paths count too.
     */
    public static function exists(string $name, string $variant = 'outline'): bool
    {
        $component = self::component($name, $variant);

        try {
            $svg = app(Factory::class)->svg($component);
        } catch (SvgNotFound) {
            return false;
        }

        // A host can configure blade-icons' OWN fallback, and then svg() returns
        // that icon instead of throwing. The component renderer has no such
        // fallback and would still throw, so a substituted svg means "missing".
        return 'heroicon-'.$svg->name() === $component;
    }

    /**
     * Logs a missing icon once per request per name. An icon in a nav renders on
     * every page, often several times on one; a line each time buries the log.
     */
    public static function reportMissing(string $name, string $fallback): void
    {
        $seen = app('blade-ui.missing-icons');

        if (isset($seen[$name])) {
            return;
        }

        $seen[$name] = true;

        Log::warning("blade-ui: icon \"{$name}\" not found, rendered \"{$fallback}\"");
    }
}
