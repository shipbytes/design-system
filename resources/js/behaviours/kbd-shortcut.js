/*
 * x-ds-shortcut: ds::kbd's `shortcut`, drawn for the reader's platform.
 *
 *   x-data x-ds-shortcut data-ds-shortcut="mod+shift+k"
 *
 * The server draws Ctrl + Shift + K, which is right off a Mac. On a Mac
 * this redraws the caps as ⌘⇧K, with no separator as macOS writes them, and
 * the words as Command+Shift+K. The kbd keeps its caps (data-ds-kbd-caps,
 * aria-hidden) and its words (data-ds-kbd-words, sr-only). A change to
 * data-ds-shortcut, from x-bind or a script, is drawn again. An enhancement:
 * with no script the server's drawing stands.
 */
import { caps, isMac } from '../shared/shortcut.js';

export function kbdShortcut(Alpine) {
    Alpine.directive('ds-shortcut', (el, _directive, { cleanup }) => {
        const shown = el.querySelector('[data-ds-kbd-caps]');
        const said = el.querySelector('[data-ds-kbd-words]');
        if (!shown || !said) return;
        const mac = isMac();
        const key = shown.querySelector('kbd')?.className ?? el.dataset.dsKey ?? '';
        let drawn = null;

        function draw() {
            const value = el.dataset.dsShortcut ?? '';
            if (value === drawn) return;
            drawn = value;
            const parts = caps(value, mac);
            const between = mac ? '' : el.dataset.dsSeparator ?? '+';
            const nodes = [];
            parts.forEach((part, i) => {
                const cap = document.createElement('kbd');
                cap.className = key;
                cap.textContent = part.cap;
                nodes.push(cap);
                if (between && i < parts.length - 1) {
                    const sep = document.createElement('span');
                    sep.className = 'text-[0.8125em]';
                    sep.textContent = between;
                    nodes.push(sep);
                }
            });
            shown.replaceChildren(...nodes);
            said.textContent = parts.map((part) => part.word).join('+');
            el.hidden = !parts.length;
        }

        // Off a Mac the server's drawing is already right.
        if (mac) draw();
        else drawn = el.dataset.dsShortcut ?? '';

        const observer = new MutationObserver(draw);
        observer.observe(el, { attributes: true, attributeFilter: ['data-ds-shortcut'] });
        cleanup(() => observer.disconnect());
    });
}
