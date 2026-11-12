/*
 * dsClipboard: copy a string, and say so for a moment.
 *
 *   x-data="dsClipboard({ text: 'sk_live_42' })"   then   @click="copy()"
 *   x-data="dsClipboard({ target: 'api-key' })"    then   @click="copy()"
 *   x-data="dsClipboard"                           then   @click="copy($refs.key.value)"
 *
 * `target` is an element id, or a CSS selector, read at the moment of the
 * press: a field's value, or any other element's text with the whitespace
 * around it trimmed. So a field the reader has edited copies what it holds
 * now, not what the page was rendered with. A target that is not on the page
 * is a failed copy, never an empty one.
 *
 * No UI. `copied` is true for COPIED_FOR ms after a successful copy, and the
 * component that uses this decides what that looks like. `copy()` resolves to
 * true or false, so a caller can tell a failed copy from a slow one.
 *
 * The scope exposes `copied` and `copy` and nothing else. Everything an
 * Alpine.data object returns is visible to the markup inside it AND shadows the
 * host's own variables of the same name, so the option and the timer live in
 * the closure: a host with its own `text` keeps it.
 */

import { timedFlag } from '../shared/flag.js';

// Long enough to read "Copied" at a glance, short enough that a second copy a
// moment later does not look like it is still showing the first one.
const COPIED_FOR = 1500;

export function clipboard(Alpine) {
    Alpine.data('dsClipboard', (options = {}) => {
        const fallback = options.text ?? '';
        const target = options.target ?? null;
        const showFor = options.for ?? COPIED_FOR;
        let flag = null;

        return {
            copied: false,

            async copy(value) {
                const text = value !== undefined ? String(value ?? '') : target !== null ? read(target) : fallback;
                const ok = text !== null && (await write(text));

                // Restarted rather than stacked (shared/flag.js): two quick
                // copies keep the flag up for the full time after the SECOND.
                flag ??= timedFlag((on) => {
                    this.copied = on;
                }, showFor);
                flag.set(ok);

                return ok;
            },

            destroy() {
                flag?.stop();
            },
        };
    });
}

function read(target) {
    let el = document.getElementById(target);
    if (!el) {
        try {
            el = document.querySelector(target);
        } catch {
            // Not a valid selector either: no such element.
            el = null;
        }
    }
    if (!el) return null;

    const field = el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement;
    return field ? el.value : el.textContent.trim();
}

async function write(text) {
    // The async API exists only in a secure context, so a plain-http intranet
    // page has no navigator.clipboard at all. It also rejects when the document
    // is not focused. Both fall through to the old path rather than reporting a
    // failure the browser could have avoided.
    if (window.isSecureContext && navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // fall through
        }
    }

    return legacyCopy(text);
}

function legacyCopy(text) {
    const area = document.createElement('textarea');
    const focused = document.activeElement;

    area.value = text;
    // Off screen rather than display:none, which cannot hold a selection.
    // readonly keeps a phone from raising its keyboard for one frame.
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.top = '-1000px';
    area.style.opacity = '0';

    document.body.appendChild(area);
    area.select();

    let ok = false;
    try {
        ok = document.execCommand('copy');
    } catch {
        ok = false;
    }

    area.remove();
    // Selecting the textarea moved focus into it. Give it back, or the button
    // that was pressed loses its focus ring and a keyboard user loses their
    // place.
    focused?.focus?.();

    return ok;
}
