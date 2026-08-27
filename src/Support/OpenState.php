<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

use InvalidArgumentException;

/**
 * Guards the `open` prop on modal, drawer and sheet.
 *
 * Those three do not just READ `open` — they ASSIGN to it, from the close
 * button, the backdrop and Escape:
 *
 *     @click="{{ $open }} = false"
 *
 * So `open` has to be an assignable reference. The prop was documented as an
 * "Alpine expression", which is true of how it is read and wrong about how it is
 * written, and a consumer who took that literally drove several drawers from one
 * variable:
 *
 *     <x-ds::drawer open="side === 'right'">
 *
 * That compiles to `side === 'right' = false`. The drawer OPENS — reading the
 * expression is fine — and then the close button, the backdrop and Escape all do
 * nothing. Alpine reports it as an "Invalid left-hand side in assignment" in the
 * browser console and nowhere else: the page is 200, the server log is clean,
 * and what you have is an open dialog with three dead dismiss paths.
 *
 * This turns that into an exception at render time, naming the component and the
 * expression. It is a deliberately SHALLOW check — comparison and logical
 * operators, which is what the realistic mistakes are made of. It is not a
 * JavaScript parser and must not become one: something that almost understands
 * the expression would start rejecting valid ones, and a false positive here is
 * worse than the bug, because the bug at least renders.
 */
final class OpenState
{
    /**
     * Operators that cannot appear in something you can assign to.
     *
     * `=>` is excluded by the ordering below — it is matched and skipped before
     * `>` is considered, so an arrow function in the expression is not reported
     * as a comparison. It is still not assignable, but saying "you used `>`"
     * about `i => i.open` would send the reader looking for the wrong thing.
     */
    private const NOT_ASSIGNABLE = ['=>', '===', '!==', '==', '!=', '<=', '>=', '&&', '||', '<', '>'];

    public static function assertAssignable(?string $open, string $component): void
    {
        if ($open === null || trim($open) === '') {
            return;
        }

        $found = self::firstOperatorIn($open);

        if ($found === null || $found === '=>') {
            return;
        }

        throw new InvalidArgumentException(
            "<x-{$component} open=\"{$open}\"> is not assignable.\n\n"
            ."`open` is a REFERENCE, not a condition: {$component} sets it to false from "
            ."the close button, the backdrop and Escape, so the expression has to be "
            ."something JavaScript can assign to. `{$found}` means this one is not.\n\n"
            ."Without this exception the dialog would open correctly and then ignore "
            ."every way of closing it, with nothing in the server log and only an "
            ."'Invalid left-hand side in assignment' in the browser console.\n\n"
            ."Hold the state in a property instead of deriving it:\n\n"
            ."  <div x-data=\"{ show: { right: false, left: false } }\">\n"
            ."      <x-{$component} open=\"show.right\">\n",
        );
    }

    /**
     * The first disqualifying operator, ignoring anything inside a string.
     *
     * Quoted sections are blanked rather than removed so the operators that
     * remain are the ones the expression actually applies — `panels['a>b']` is a
     * property lookup and assignable, and reporting the `>` in the key would be
     * exactly the false positive this must not produce.
     */
    private static function firstOperatorIn(string $expression): ?string
    {
        $bare = preg_replace('/([\'"])(?:\\\\.|(?!\1).)*\1/s', "''", $expression) ?? $expression;

        $at = null;
        $found = null;

        foreach (self::NOT_ASSIGNABLE as $operator) {
            $position = strpos($bare, $operator);

            // Earliest wins, and the list is ordered longest-first per family so
            // `===` is never reported as `==`.
            if ($position !== false && ($at === null || $position < $at)) {
                $at = $position;
                $found = $operator;
            }
        }

        return $found;
    }
}
