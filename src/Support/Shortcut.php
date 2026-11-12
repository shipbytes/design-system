<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * A keyboard shortcut as a string, "mod+shift+k", drawn on the server the
 * way resources/js/shared/shortcut.js draws it in the browser: the
 * modifiers in a fixed order (mod, ctrl, meta, alt, shift), then one key.
 * "mod" is Cmd on a Mac and Ctrl elsewhere.
 *
 *   Shortcut::normalise('Shift+Mod+K')   "mod+shift+k"
 *   Shortcut::keys('mod+shift+k')        ['Ctrl', 'Shift', 'K']   for ds::kbd's `keys`
 *   Shortcut::keys('mod+shift+k', true)  ['⌘', '⇧', 'K']
 *   Shortcut::words('mod+shift+k')       "Control+Shift+K"        what is said
 *
 * The server cannot know the reader's platform, so it writes the words
 * form, with mod as Ctrl, unless told; ds::kbd's `shortcut` prop redraws it
 * for a Mac once Alpine runs (x-ds-shortcut).
 */
final class Shortcut
{
    public const MODIFIERS = ['mod', 'ctrl', 'meta', 'alt', 'shift'];

    /** What a browser could not hand back to the page anyway, or would act on first. */
    public const RESERVED = ['mod+w', 'mod+t', 'mod+n', 'mod+q', 'mod+shift+t', 'mod+shift+n', 'mod+shift+w'];

    private const ALIASES = ['cmd' => 'meta', 'command' => 'meta', 'control' => 'ctrl', 'option' => 'alt'];

    private const MAC = ['mod' => ['⌘', 'Command'], 'meta' => ['⌘', 'Command'], 'ctrl' => ['⌃', 'Control'], 'alt' => ['⌥', 'Option'], 'shift' => ['⇧', 'Shift']];

    private const PC = ['mod' => ['Ctrl', 'Control'], 'meta' => ['Meta', 'Meta'], 'ctrl' => ['Ctrl', 'Control'], 'alt' => ['Alt', 'Alt'], 'shift' => ['Shift', 'Shift']];

    private const KEYS = [
        'space' => ['Space', 'Space'], 'plus' => ['+', 'Plus'], 'escape' => ['Esc', 'Escape'], 'enter' => ['Enter', 'Enter'],
        'arrowup' => ['↑', 'Up Arrow'], 'arrowdown' => ['↓', 'Down Arrow'], 'arrowleft' => ['←', 'Left Arrow'], 'arrowright' => ['→', 'Right Arrow'],
        'pageup' => ['Page Up', 'Page Up'], 'pagedown' => ['Page Down', 'Page Down'],
    ];

    /** @return list<string> the modifiers in order, then the key; [] for none */
    public static function split(?string $shortcut): array
    {
        $parts = array_values(array_filter(array_map('trim', explode('+', mb_strtolower((string) $shortcut))), fn ($p) => $p !== ''));
        $key = array_pop($parts);
        if ($key === null) {
            return [];
        }
        $held = array_map(fn ($p) => self::ALIASES[$p] ?? $p, $parts);

        return [...array_values(array_filter(self::MODIFIERS, fn ($m) => in_array($m, $held, true))), $key];
    }

    public static function normalise(?string $shortcut): string
    {
        return implode('+', self::split($shortcut));
    }

    /** @return list<array{cap: string, word: string}> */
    public static function caps(?string $shortcut, bool $mac = false): array
    {
        $parts = self::split($shortcut);
        $last = count($parts) - 1;

        return array_map(function (string $part, int $i) use ($last, $mac) {
            if ($i < $last) {
                [$cap, $word] = ($mac ? self::MAC : self::PC)[$part];
            } else {
                $name = mb_strlen($part) === 1 ? mb_strtoupper($part) : mb_strtoupper(mb_substr($part, 0, 1)).mb_substr($part, 1);
                [$cap, $word] = self::KEYS[$part] ?? [$name, $name];
            }

            return ['cap' => $cap, 'word' => $word];
        }, $parts, array_keys($parts));
    }

    /** @return list<string> */
    public static function keys(?string $shortcut, bool $mac = false): array
    {
        return array_column(self::caps($shortcut, $mac), 'cap');
    }

    public static function words(?string $shortcut, bool $mac = false): string
    {
        return implode('+', array_column(self::caps($shortcut, $mac), 'word'));
    }
}
