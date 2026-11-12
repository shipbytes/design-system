/*
 * A keyboard shortcut as a string, "mod+shift+k", one meaning everywhere:
 * dsCommandPalette's and dsSearch's `shortcut` parse and match it,
 * dsShortcutInput records it, and x-ds-shortcut draws it. Mirrors
 * Support\Shortcut, which draws the same string on the server. Registers
 * nothing.
 *
 * The modifiers come first in a fixed order (mod, ctrl, meta, alt, shift),
 * then one key. "mod" is Cmd on a Mac and Ctrl elsewhere. The key is
 * event.key lower-cased, with " " written "space" and "+" written "plus" so
 * the string still splits on "+".
 */

export const MODIFIERS = ['mod', 'ctrl', 'meta', 'alt', 'shift'];

// Where a shortcut with no Ctrl or Cmd in it is typing, not a shortcut.
export const EDITABLE = 'input, textarea, select, [contenteditable]:not([contenteditable="false"])';

export function isMac() {
    return /mac|iphone|ipad|ipod/i.test(navigator.userAgentData?.platform ?? navigator.platform ?? '');
}

export function keyName(key) {
    const k = String(key).toLowerCase();
    return k === ' ' ? 'space' : k === '+' ? 'plus' : k;
}

const ALIASES = { cmd: 'meta', command: 'meta', control: 'ctrl', option: 'alt' };

/** The parts of a shortcut, modifiers in order and the key last; [] for none. */
export function split(shortcut) {
    const parts = String(shortcut ?? '').toLowerCase().split('+').map((p) => p.trim()).filter(Boolean);
    const key = parts.pop();
    if (!key) return [];
    const mods = new Set(parts.map((p) => ALIASES[p] ?? p).filter((p) => MODIFIERS.includes(p)));
    return [...MODIFIERS.filter((m) => mods.has(m)), key];
}

export function normalise(shortcut) {
    return split(shortcut).join('+');
}

/** Two shortcuts that are the same keys here: "mod+w" is "ctrl+w" off a Mac. */
export function same(a, b, mac = isMac()) {
    const resolve = (s) => normalise(split(s).map((p, i, all) => (p === 'mod' && i < all.length - 1 ? (mac ? 'meta' : 'ctrl') : p)).join('+'));
    return resolve(a) !== '' && resolve(a) === resolve(b);
}

export function parse(shortcut) {
    const parts = split(shortcut);
    const key = parts.pop();
    if (!key) return null;
    const mac = isMac();
    const want = { key, ctrl: false, meta: false, alt: false, shift: false };
    for (const part of parts) want[part === 'mod' ? (mac ? 'meta' : 'ctrl') : part] = true;
    want.chord = want.ctrl || want.meta;
    return want;
}

// Shift is only compared for a letter or a named key: "?" is typed with it.
export function matches(combo, event) {
    if (keyName(event.key) !== combo.key) return false;
    if (event.ctrlKey !== combo.ctrl || event.metaKey !== combo.meta || event.altKey !== combo.alt) return false;
    return combo.key.length === 1 && !/[a-z]/.test(combo.key) ? true : event.shiftKey === combo.shift;
}

// A modifier pressed on its own, which never completes a shortcut.
const LONE = ['shift', 'control', 'alt', 'meta', 'altgraph', 'capslock', 'fn', 'os', 'super', 'hyper', 'dead', 'unidentified', 'process'];

// event.code to the character the key types on a US layout.
const CODES = { minus: '-', equal: '=', bracketleft: '[', bracketright: ']', backslash: '\\', semicolon: ';', quote: "'", comma: ',', period: '.', slash: '/', backquote: '\u0060' };

/*
 * The shortcut a keydown is, or null for a modifier alone. `by: 'code'`
 * reads where the key is rather than what it types, written as the US
 * character, so a layout that moves "/" still records "/". By key, a
 * character Shift typed ("?") does not repeat the Shift.
 */
export function record(event, { portable = true, by = 'key' } = {}) {
    if (LONE.includes(String(event.key).toLowerCase())) return null;
    let key;
    if (by === 'code') {
        const c = String(event.code).toLowerCase();
        key = /^key[a-z]$/.test(c) ? c.slice(3) : /^digit\d$/.test(c) ? c.slice(5) : c === 'space' ? 'space' : CODES[c] ?? c;
    } else {
        key = keyName(event.key);
    }
    if (!key) return null;
    const mac = isMac();
    const held = { ctrl: event.ctrlKey, meta: event.metaKey, alt: event.altKey, shift: event.shiftKey };
    if (by !== 'code' && key.length === 1 && !/[a-z]/.test(key)) held.shift = false;
    const mods = [];
    if (portable && held[mac ? 'meta' : 'ctrl']) {
        mods.push('mod');
        held[mac ? 'meta' : 'ctrl'] = false;
    }
    return [...mods, ...MODIFIERS.slice(1).filter((m) => held[m]), key].join('+');
}

const MAC = { mod: ['⌘', 'Command'], meta: ['⌘', 'Command'], ctrl: ['⌃', 'Control'], alt: ['⌥', 'Option'], shift: ['⇧', 'Shift'] };
const PC = { mod: ['Ctrl', 'Control'], meta: ['Meta', 'Meta'], ctrl: ['Ctrl', 'Control'], alt: ['Alt', 'Alt'], shift: ['Shift', 'Shift'] };
const KEYS = {
    space: ['Space', 'Space'], plus: ['+', 'Plus'], escape: ['Esc', 'Escape'], enter: ['Enter', 'Enter'],
    arrowup: ['↑', 'Up Arrow'], arrowdown: ['↓', 'Down Arrow'], arrowleft: ['←', 'Left Arrow'], arrowright: ['→', 'Right Arrow'],
    pageup: ['Page Up', 'Page Up'], pagedown: ['Page Down', 'Page Down'],
};

/*
 * Each key of a shortcut as drawn, and as said: symbols on a Mac, words
 * elsewhere, and words always for the accessible text. Support\Shortcut
 * writes the same, with mac false, before the script runs.
 */
export function caps(shortcut, mac = isMac()) {
    const parts = split(shortcut);
    return parts.map((part, i) => {
        if (i < parts.length - 1) {
            const [cap, word] = (mac ? MAC : PC)[part];
            return { cap, word };
        }
        const [cap, word] = KEYS[part] ?? (part.length === 1 ? [part.toUpperCase(), part.toUpperCase()] : [part[0].toUpperCase() + part.slice(1), part[0].toUpperCase() + part.slice(1)]);
        return { cap, word };
    });
}
