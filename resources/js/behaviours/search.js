/*
 * dsSearch: the keys behind ds::input's `expanding`.
 *
 *   x-data="dsSearch({ shortcut: '/' })"
 *
 * The view does the expanding in CSS: the field (data-ds-search-input) is
 * wide while it has focus or holds text. This adds the keys:
 *
 *   - Escape in the field empties it, dispatching `input` and `change` as
 *     dsClear does; Escape in an empty field puts focus on the button
 *     (data-ds-search-button), which leaves it collapsed. Stopped only when
 *     it does one of those, so a dialog around it hears the next one
 *   - the button, pressed (a screen reader's browse mode does), focuses the
 *     field; a character typed while it has focus goes to the field
 *   - `shortcut` anywhere on the page focuses the field, parsed and matched
 *     as dsCommandPalette's (shared/shortcut.js): one with no Ctrl or Cmd
 *     is ignored while typing in a field, and its default is prevented only
 *     when it is acted on
 *
 * The scope exposes nothing but Alpine's own init and destroy.
 */
import { EDITABLE, parse, matches } from '../shared/shortcut.js';

export function search(Alpine) {
    Alpine.data('dsSearch', (options = {}) => {
        let teardown = null;

        return {
            init() {
                teardown = wire(this.$el, options);
            },

            destroy() {
                teardown?.();
            },
        };
    });
}

function wire(root, options) {
    const field = root.querySelector('[data-ds-search-input]');
    const button = root.querySelector('[data-ds-search-button]');
    const combo = parse(options.shortcut);

    function onFieldKeyDown(event) {
        if (event.key !== 'Escape' || event.isComposing) return;
        event.preventDefault();
        event.stopPropagation();
        if (field.value !== '') {
            field.value = '';
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            button.focus();
        }
    }

    const onButtonClick = () => field.focus();

    function onButtonKeyDown(event) {
        if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) field.focus();
    }

    function onShortcut(event) {
        if (!combo || field.disabled || event.defaultPrevented || event.isComposing || !matches(combo, event)) return;
        if (!combo.chord && event.target.closest?.(EDITABLE)) return;
        event.preventDefault();
        field.focus();
        field.select();
    }

    field.addEventListener('keydown', onFieldKeyDown);
    button.addEventListener('click', onButtonClick);
    button.addEventListener('keydown', onButtonKeyDown);
    window.addEventListener('keydown', onShortcut);

    return () => {
        field.removeEventListener('keydown', onFieldKeyDown);
        button.removeEventListener('click', onButtonClick);
        button.removeEventListener('keydown', onButtonKeyDown);
        window.removeEventListener('keydown', onShortcut);
    };
}
