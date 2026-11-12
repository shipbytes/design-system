<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The modal layer ds::modal and ds::command-palette are drawn with, from
 * resources/views/partials/modal.php: the backdrop, the transitions, the
 * focus trap and the focus handling.
 *
 * In that file rather than here for the reason Support\Field gives: a class
 * written in src/ compiles in this repo's tests and is missing in every app.
 */
final class Modal
{
    /** @var array<string, mixed>|null */
    private static ?array $recipe = null;

    /**
     * @return array{
     *     backdrop: string,
     *     backdropTransition: array<string, string>,
     *     panelTransition: array<string, string>,
     *     trap: string,
     *     focus: \Closure(string, string=, string=): string,
     * }
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/modal.php';
    }
}
