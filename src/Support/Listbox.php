<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The popup list ds::combobox and ds::time-picker are drawn with, and the
 * option ds::command-palette lists, from
 * resources/views/partials/listbox.php: its classes, its options and the
 * arrow-key handler.
 *
 * In that file rather than here for the reason Support\Field gives: a class
 * written in src/ compiles in this repo's tests and is missing in every app.
 */
final class Listbox
{
    /** @var array<string, mixed>|null */
    private static ?array $recipe = null;

    /**
     * @return array{
     *     panel: string,
     *     option: string,
 *     activeOption: string,
     *     empty: string,
     *     move: string,
     * }
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/listbox.php';
    }
}
