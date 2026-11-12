<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The surface ds::popover and ds::hover-card are drawn with, from
 * resources/views/partials/popover.php: its classes, widths, transition and
 * the fallback placements.
 *
 * In that file rather than here for the reason Support\Field gives: a class
 * written in src/ compiles in this repo's tests and is missing in every app.
 * This only finds the file, reads it once per process, and settles a
 * placement and a width to one the recipe has.
 */
final class Popover
{
    /** @var array<string, mixed>|null */
    private static ?array $recipe = null;

    /**
     * @return array{
     *     surface: string,
     *     widths: array<string, string>,
     *     transition: array<string, string>,
     *     placements: array<string, string>,
     * }
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/popover.php';
    }

    /** A placement the recipe has: the one asked for, or bottom-start. */
    public static function placement(?string $placement): string
    {
        return isset(self::recipe()['placements'][$placement]) ? $placement : 'bottom-start';
    }

    /** The side half of a placement, which data-side carries before the script measures. */
    public static function side(string $placement): string
    {
        return explode('-', $placement)[0];
    }

    /** A width the recipe has: the one asked for, or md. */
    public static function width(?string $width): string
    {
        return isset(self::recipe()['widths'][$width]) ? $width : 'md';
    }
}
