<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

use InvalidArgumentException;

/**
 * The x-data expression for a behaviour from the package's script.
 *
 *     x-data="{{ \Shipbytes\BladeUi\Support\Behaviour::data('dsClipboard', ['text' => $value]) }}"
 *
 * Written through here rather than as `x-data="dsClipboard(…)"` because of what
 * happens in a host that never loaded the script. Alpine's own report is
 * "dsClipboard is not defined", which names the behaviour and not the cause,
 * and reads like a bug in the package. This checks first, and when the name is
 * missing it throws an error that says which include is missing and where it
 * goes. Alpine prints that message as the expression error, and the browser
 * reports it as an uncaught error, so an error tracker sees it too.
 *
 * `typeof` is the one test that does not itself throw on a name that was never
 * registered.
 */
final class Behaviour
{
    /**
     * @param  array<string, mixed>|string|null  $arguments  An array is passed as a JSON object; a
     *                                                       string is a JavaScript expression,
     *                                                       inserted as written.
     */
    public static function data(string $name, array|string|null $arguments = null): string
    {
        if (! preg_match('/^ds[A-Z][A-Za-z0-9]*$/', $name)) {
            throw new InvalidArgumentException(
                "[{$name}] is not a behaviour name. The script registers its Alpine.data under a `ds` "
                .'prefix and a capital, e.g. dsClipboard.'
            );
        }

        $call = match (true) {
            $arguments === null => "{$name}()",
            is_array($arguments) => $name.'('.json_encode((object) $arguments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).')',
            default => "{$name}({$arguments})",
        };

        $message = json_encode(
            "{$name} is not registered: add <x-ds::scripts /> above the Alpine script, "
            .'or call Alpine.plugin(bladeUi) where Alpine is bundled.',
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        return "typeof {$name} === 'function' ? {$call} : (() => { throw new Error({$message}) })()";
    }
}
