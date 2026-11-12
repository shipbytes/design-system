<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The two faces ds::copy-button and ds::swap-button swap between, from
 * resources/views/partials/swap.php.
 *
 * In that file rather than here for the reason Support\Field gives: a class
 * written in src/ compiles in this repo's tests and is missing in every app.
 */
final class Swap
{
    /** @var array<string, string>|null */
    private static ?array $recipe = null;

    /** @return array{stack: string, rest: string, swapped: string} */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/swap.php';
    }
}
