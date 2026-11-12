<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The class strings every text field is drawn with, from
 * resources/views/partials/field.php.
 *
 * The strings live in that file rather than here because a consumer's Tailwind
 * scans resources/views and not src/: a class written in this file would
 * compile in this repo's tests and be missing in every app. This only finds
 * the file and reads it once per process.
 */
final class Field
{
    /** @var array<string, mixed>|null */
    private static ?array $recipe = null;

    /**
     * @return array{
     *     label: string,
     *     shell: \Closure(bool): string,
     *     control: \Closure(bool, bool, bool, string=): string,
     *     padding: \Closure(string): string,
     *     text: string,
     *     border: \Closure(bool, bool): string,
     *     tint: \Closure(bool, bool): string,
     *     error: string,
     *     help: string,
     * }
     */
    public static function recipe(): array
    {
        return self::$recipe ??= require __DIR__.'/../../resources/views/partials/field.php';
    }
}
