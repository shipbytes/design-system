<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The panel ds::dropdown and ds::context-menu are drawn with, from
 * resources/views/partials/menu.php: its classes, its transition and the
 * arrow-key handler.
 *
 * In that file rather than here for the reason Support\Field gives: a class
 * written in src/ compiles in this repo's tests and is missing in every app.
 */
final class Menu
{
    /** @var array<string, mixed>|null */
    private static ?array $recipe = null;

    /**
     * @return array{
     *     panel: string,
     *     transition: array<string, string>,
     *     move: string,
     * }
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/menu.php';
    }
}
