/*
 * dsTeamSwitcher: the list behind ds::team-switcher, in a dsPopover panel.
 *
 *   x-data="dsTeamSwitcher({ submit: true })"
 *
 * The view renders everything from PHP: a fallback (data-ds-team-fallback)
 * of links, or a native select (data-ds-team-select) inside the form when
 * there is one, which it binds hidden to `dsReady`; and, hidden until
 * `dsReady`, a ds::popover whose trigger (data-ds-team-trigger) shows the
 * current team's avatar, name and meta (data-ds-team-avatar, -name, -meta),
 * and whose panel holds an optional search field (data-ds-team-search), a
 * listbox of options (data-ds-team-option, role=option, data-value, the
 * name in data-ds-label and the meta in data-ds-keywords, aria-selected on
 * the current one, each in an item of its own) and an empty row
 * (data-ds-team-empty). Opening, closing, Escape, focus in and back out are
 * the popover's; focus starts on the field, or on the current team.
 *
 *   - the keys, the filter and Enter in the field are the shared picker's
 *     (shared/picker.js); the filter ranks as the command palette ranks
 *     (shared/rank.js): the name, then the meta just below it
 *   - choosing a link follows it. Choosing any other team sets the select
 *     to its id, moves aria-selected and the trigger's face to it, closes
 *     the panel with focus back on the trigger, dispatches `change` with
 *     { id } from the select, bubbling (wire:model and x-model on it hear
 *     it), and with `submit` submits the form
 *
 * The listeners are on the root and read the DOM on every event, so a
 * Livewire morph that re-renders the teams changes nothing here.
 *
 * The scope exposes `dsReady` and nothing else.
 */
import { fold, rank } from '../shared/rank.js';
import { picker } from '../shared/picker.js';

export function teamSwitcher(Alpine) {
    Alpine.data('dsTeamSwitcher', (options = {}) => {
        let teardown = null;

        return {
            dsReady: false,

            init() {
                const root = this.$el;
                const find = (selector) => root.querySelector(selector);
                const all = () => [...root.querySelectorAll('[data-ds-team-option]')];

                const close = () => {
                    const popover = find('[data-ds-popover]');
                    if (popover) Alpine.$data(popover).dsOpen = false;
                };

                function choose(option) {
                    if (option.matches('a[href]')) {
                        close();
                        return;
                    }
                    const id = option.dataset.value;
                    for (const o of all()) {
                        o.setAttribute('aria-selected', o === option ? 'true' : 'false');
                        if (!find('[data-ds-team-search]')) o.toggleAttribute('data-ds-autofocus', o === option);
                    }
                    const face = find('[data-ds-team-trigger]');
                    for (const part of ['name', 'meta']) {
                        const to = face?.querySelector(`[data-ds-team-${part}]`);
                        if (to) to.textContent = option.querySelector(`[data-ds-team-${part}]`)?.textContent ?? '';
                    }
                    face?.querySelector('[data-ds-team-avatar]')?.replaceWith(option.querySelector('[data-ds-team-avatar]').cloneNode(true));
                    close();

                    const select = find('[data-ds-team-select]');
                    if (!select) return;
                    select.value = id;
                    // A plain Event with a detail, not a CustomEvent: x-model
                    // (and wire:model, which is built on it) takes a
                    // CustomEvent's detail AS the value, and would bind
                    // { id } instead of the id the select now holds.
                    const change = new Event('change', { bubbles: true });
                    change.detail = { id };
                    select.dispatchEvent(change);
                    if (options.submit === true) select.form?.requestSubmit();
                }

                const list = picker(root, {
                    option: '[data-ds-team-option]',
                    search: '[data-ds-team-search]',
                    empty: '[data-ds-team-empty]',
                    score: (o, q) => Math.max(rank(fold(o.dataset.dsLabel ?? ''), q) * 2, rank(fold(o.dataset.dsKeywords ?? ''), q) * 2 - 1),
                    choose,
                });

                // Each opening starts from the whole list.
                const onClick = (event) => {
                    if (event.target.closest('[data-ds-team-trigger]')) list.reset();
                };

                root.addEventListener('click', onClick);
                this.dsReady = true;

                teardown = () => {
                    list.teardown();
                    root.removeEventListener('click', onClick);
                };
            },

            destroy() {
                teardown?.();
            },
        };
    });
}
