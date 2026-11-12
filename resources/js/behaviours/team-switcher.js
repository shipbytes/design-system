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
 *   - ArrowDown and ArrowUp move between the options shown, wrapping, and
 *     Home and End go to the ends; ArrowDown from the field enters the list,
 *     ArrowUp from its first option goes back to the field. Enter or Space
 *     chooses; Enter in the field chooses the first match
 *   - typing in the field filters, ranked as the command palette ranks
 *     (shared/rank.js): the name, then the meta just below it. The options
 *     are ordered by CSS `order`, so the DOM stays as rendered for a morph
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

export function teamSwitcher(Alpine) {
    Alpine.data('dsTeamSwitcher', (options = {}) => {
        let teardown = null;

        return {
            dsReady: false,

            init() {
                const root = this.$el;
                const find = (selector) => root.querySelector(selector);
                const all = () => [...root.querySelectorAll('[data-ds-team-option]')];
                const item = (option) => option.closest('li') ?? option;
                const order = (option) => Number(item(option).style.order || 0);
                const shown = () => all().filter((o) => !item(o).hidden).sort((a, b) => order(a) - order(b));

                const close = () => {
                    const popover = find('[data-ds-popover]');
                    if (popover) Alpine.$data(popover).dsOpen = false;
                };

                function filter() {
                    const field = find('[data-ds-team-search]');
                    const q = fold((field?.value ?? '').trim());
                    const scored = all().map((o, i) => {
                        const name = rank(fold(o.dataset.dsLabel ?? ''), q) * 2;
                        const meta = rank(fold(o.dataset.dsKeywords ?? ''), q) * 2 - 1;
                        return [o, q ? Math.max(name, meta) : 1, i];
                    });
                    scored.sort((a, b) => b[1] - a[1] || a[2] - b[2]);
                    scored.forEach(([o, score], i) => {
                        item(o).hidden = score <= 0;
                        if (q) item(o).style.setProperty('order', String(i + 1));
                        else item(o).style.removeProperty('order');
                    });
                    const empty = find('[data-ds-team-empty]');
                    if (empty) empty.hidden = scored.some(([, score]) => score > 0);
                }

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

                const onClick = (event) => {
                    const option = event.target.closest('[data-ds-team-option]');
                    if (option && root.contains(option)) choose(option);
                    // Each opening starts from the whole list.
                    if (event.target.closest('[data-ds-team-trigger]')) {
                        const field = find('[data-ds-team-search]');
                        if (field) field.value = '';
                        filter();
                    }
                };

                const onKeyDown = (event) => {
                    const field = find('[data-ds-team-search]');
                    const list = shown();
                    if (event.target === field) {
                        if (event.key === 'ArrowDown' && list.length) {
                            event.preventDefault();
                            list[0].focus();
                        } else if (event.key === 'Enter') {
                            event.preventDefault();
                            list[0]?.click();
                        }
                        return;
                    }
                    const at = list.indexOf(event.target);
                    if (at < 0) return;
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        list[at].click();
                        return;
                    }
                    const keys = { Home: 0, End: list.length - 1, ArrowDown: (at + 1) % list.length, ArrowUp: at - 1 };
                    if (!(event.key in keys)) return;
                    event.preventDefault();
                    const to = keys[event.key];
                    (to < 0 ? field ?? list[list.length - 1] : list[to]).focus();
                };

                root.addEventListener('click', onClick);
                root.addEventListener('keydown', onKeyDown);
                root.addEventListener('input', filter);
                this.dsReady = true;

                teardown = () => {
                    root.removeEventListener('click', onClick);
                    root.removeEventListener('keydown', onKeyDown);
                    root.removeEventListener('input', filter);
                };
            },

            destroy() {
                teardown?.();
            },
        };
    });
}
