<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The class strings the toolbar, the toggle group and their soft buttons are
 * drawn with, from resources/views/partials/tray.php.
 *
 * In that file rather than here for the reason Support\Field gives: a class
 * written in src/ compiles in this repo's tests and is missing in every app.
 * This only finds the file and reads it once per process.
 */
final class Tray
{
    /** @var array<string, mixed>|null */
    private static ?array $recipe = null;

    /**
     * @return array{
     *     tray: \Closure(string, bool): string,
     *     group: string,
     *     highlight: string,
     *     item: \Closure(string, bool): string,
     *     separator: \Closure(bool): string,
     * }
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/tray.php';
    }
}
