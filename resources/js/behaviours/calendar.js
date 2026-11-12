/*
 * dsCalendar: the month grid of ds::calendar and of ds::date-picker's
 * popover, with the picker's `months`, `presets`, `minDays`, `maxDays` and
 * `confirm`.
 *
 *   x-data="dsCalendar()" data-ds-calendar="{ mode, name, min, max, … }"
 *
 * The view draws everything first (partials/calendar-grid.blade.php, from
 * Support\Calendar): six rows of day buttons per month, hidden where the
 * month needs fewer, and the inputs that post (data-ds-cal-input). This
 * redraws those same cells for whichever months are in view and never adds
 * a class: state goes out as data-selected, data-between, data-today,
 * data-outside and aria-disabled, which the view's classes read. The options
 * are an attribute rather than x-data arguments so that a Livewire morph
 * bringing new `events` for the month in view is read again; the inputs are
 * the value, so a morph that writes the server's value is too. Those inputs
 * are native date fields until this runs (the no-script control); it makes
 * them hidden and hides their row.
 *
 * Keys follow the ARIA date grid: one tab stop, arrows by day and week,
 * PageUp/PageDown by month, with Shift by year, Home/End to the ends of the
 * week. A day outside the months in view brings its month into view.
 *
 * Range: the first pick is the start, the second the end, and an earlier
 * second pick swaps them; between the two picks the days under the pointer
 * or focus preview the range, and minDays/maxDays (counted inclusively)
 * disable the ends that would break them. With `confirm` (the picker) picks
 * are a draft until Apply, and Cancel or closing drops them.
 *
 * The scope exposes `dsValue` (Y-m-d or '', [start, end], or a list, for
 * x-modelable), `dsOpen` and `dsSummary` for the picker, and `dsReady`. A
 * value the host sets is drawn, not announced. A reader's change dispatches
 * `input` and `change` (plain Events, bubbling) from the input that holds
 * it, and a new month in view dispatches `month-change` with { year, month }
 * from the root, so a host can load that month's events.
 */
import { key, parts, addDays, addMonths, weekday, weeks, isoWeek, days, presets } from '../shared/dates.js';

export function calendar(Alpine) {
    Alpine.data('dsCalendar', () => {
        let control = null;

        return {
            dsValue: null,
            dsOpen: false,
            dsSummary: '',
            dsReady: false,

            init() {
                control = wire(this.$el, (value, summary) => {
                    this.dsValue = value;
                    this.dsSummary = summary;
                }, () => {
                    this.dsOpen = false;
                });
                this.$watch('dsValue', (value) => control.set(value));
                this.$watch('dsOpen', (open) => control.opened(open));
                this.dsReady = true;
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

const month = (iso) => iso.slice(0, 7) + '-01';

function wire(root, report, close) {
    let o = {};
    let observer = null;
    const q = (selector) => [...root.querySelectorAll(selector)];
    const inputs = () => q('[data-ds-cal-input]');
    const blocks = () => q('[data-ds-cal-month]');
    const trigger = () => root.querySelector('[data-ds-date-trigger]');
    const range = () => o.mode === 'range';

    // Every day of the grid in a Y-m-d list: [] or [day] for one, the
    // start and end low to high for a range, sorted for multiple.
    function fromInputs() {
        const values = inputs().map((input) => input.value).filter(Boolean);
        return range() ? [...new Set(values)].sort() : o.mode === 'single' ? values.slice(0, 1) : [...new Set(values)].sort();
    }

    function read() {
        o = JSON.parse(root.dataset.dsCalendar || '{}');
        o.disabledDates = new Set(o.disabledDates ?? []);
        o.events = o.events ?? {};
        // The empty field the no-script calendar adds a day with.
        if (o.mode === 'multiple') inputs().filter((input) => !input.value).forEach((input) => input.remove());
        // The no-script fields become the hidden inputs that post.
        for (const input of inputs()) input.type = 'hidden';
        const fallback = root.querySelector('[data-ds-cal-fallback]');
        if (fallback) fallback.hidden = true;
    }

    read();
    let value = fromInputs();
    let draft = value.slice();
    let hovered = null;
    let active = null;
    let cursor = month(blocks()[0]?.dataset.dsCalMonth + '-01');

    const shownCount = () => blocks().filter((b) => b.getClientRects().length).length || blocks().length;
    const inView = (iso) => iso >= cursor && iso < addMonths(cursor, shownCount());

    function off(iso) {
        if ((o.min && iso < o.min) || (o.max && iso > o.max)) return true;
        if (o.disabledDates.has(iso) || (o.disabledDays ?? []).includes(weekday(iso))) return true;
        // The end of a half-made range is held to minDays and maxDays.
        if (range() && draft.length === 1) {
            const span = Math.abs(days(draft[0], iso)) + 1;
            if ((o.minDays && span < o.minDays) || (o.maxDays && span > o.maxDays)) return true;
        }
        return false;
    }

    function label(iso) {
        const [y, m, d] = parts(iso);
        const text = `${o.names.long[weekday(iso)]} ${d} ${o.names.months[m - 1]} ${y}`;
        const mark = o.events[iso];
        if (typeof mark === 'number') return `${text}, ${o.words[mark === 1 ? 'event' : 'events'].replace(':count', mark)}`;
        return mark ? `${text}, ${o.words.marked}` : text;
    }

    // The tab stop: a chosen day in view, else today, else the first day
    // of the first month, as Support\Calendar::grid() picks it.
    function stop() {
        if (active && inView(active)) return active;
        return [...draft, o.today].find((iso) => iso && inView(iso)) ?? cursor;
    }

    function draw() {
        const [lo, hi] = range() && draft.length ? [draft[0], draft[1] ?? (hovered && hovered !== draft[0] ? hovered : draft[0])].sort() : [null, null];
        active = stop();

        blocks().forEach((block, i) => {
            const first = addMonths(cursor, i);
            const [y, m] = parts(first);
            const name = `${o.names.months[m - 1]} ${y}`;
            block.dataset.dsCalMonth = first.slice(0, 7);
            block.querySelector('[data-ds-cal-grid]').setAttribute('aria-label', name);
            const heading = block.querySelector('[data-ds-cal-label]');
            if (heading) heading.textContent = name;
            for (const select of block.querySelectorAll('[data-ds-cal-jump]')) {
                const want = String(select.dataset.dsCalJump === 'year' ? y : m);
                if (![...select.options].some((option) => option.value === want)) select.add(new Option(want, want));
                select.value = want;
            }

            const rows = weeks(y, m, o.weekStart);
            while (rows.length < 6) rows.push(rows[rows.length - 1].map((iso) => addDays(iso, 7)));
            block.querySelectorAll('[data-ds-cal-row]').forEach((row, r) => {
                row.hidden = r >= weeks(y, m, o.weekStart).length;
                const week = row.querySelector('[data-ds-cal-week]');
                if (week) week.textContent = isoWeek(rows[r]);
                row.querySelectorAll('[data-ds-cal-day]').forEach((button, c) => {
                    const iso = rows[r][c];
                    const outside = iso.slice(0, 7) !== first.slice(0, 7);
                    const selected = draft.includes(iso);
                    const mark = o.events[iso];
                    const dot = button.querySelector('[data-ds-cal-mark]');
                    button.dataset.day = iso;
                    button.querySelector('[data-ds-cal-num]').textContent = parts(iso)[2];
                    button.setAttribute('aria-label', label(iso));
                    button.hidden = outside && !o.outside;
                    button.tabIndex = iso === active && !outside ? 0 : -1;
                    button.toggleAttribute('data-outside', outside);
                    button.toggleAttribute('data-selected', selected);
                    button.toggleAttribute('data-between', lo !== null && iso > lo && iso < hi);
                    button.toggleAttribute('data-today', iso === o.today);
                    if (iso === o.today) button.setAttribute('aria-current', 'date');
                    else button.removeAttribute('aria-current');
                    if (off(iso)) button.setAttribute('aria-disabled', 'true');
                    else button.removeAttribute('aria-disabled');
                    button.parentElement.setAttribute('aria-selected', String(selected));
                    dot.hidden = !mark;
                    dot.textContent = typeof mark === 'number' ? mark : '';
                    if (typeof mark === 'string') dot.dataset.tone = mark;
                    else delete dot.dataset.tone;
                });
            });
        });

        for (const preset of q('[data-ds-cal-preset]')) {
            const [from, to] = presetRange(preset);
            preset.setAttribute('aria-pressed', String(draft.length === 2 && draft[0] === from && draft[1] === to));
        }
        observer?.takeRecords();
    }

    // A built-in preset is worked out here, from the server's today, as
    // Support\Calendar::presets() works it out there; a host's carries its dates.
    function presetRange(preset) {
        const named = preset.dataset.dsCalPreset;
        return named ? presets(o.today)[named] : [preset.dataset.from, preset.dataset.to];
    }

    function show(next, announce = true) {
        next = month(next);
        if (next === cursor) return;
        cursor = next;
        draw();
        if (!announce) return;
        const live = root.querySelector('[data-ds-cal-live]');
        const names = blocks().slice(0, shownCount()).map((_, i) => {
            const [y, m] = parts(addMonths(cursor, i));
            return `${o.names.months[m - 1]} ${y}`;
        });
        if (live) live.textContent = names.join(' – ');
        const [year, m] = parts(cursor);
        root.dispatchEvent(new CustomEvent('month-change', { bubbles: true, detail: { year, month: m } }));
    }

    function focusDay(iso) {
        active = iso;
        if (!inView(iso)) show(iso < cursor ? iso : addMonths(month(iso), 1 - shownCount()));
        else draw();
        root.querySelector(`[data-ds-cal-day][data-day="${iso}"]:not([data-outside])`)?.focus();
    }

    function summary(list) {
        const display = (iso) => {
            const [y, m, d] = parts(iso);
            return new Date(y, m - 1, d).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
        };
        if (!list.length) return '';
        return range() ? `${display(list[0])} – ${list[1] ? display(list[1]) : '…'}` : list.map(display).join(', ');
    }

    function shape(list) {
        if (o.mode === 'single') return list[0] ?? '';
        return range() ? [list[0] ?? '', list[1] ?? ''] : list.slice();
    }

    // What posts, from a list of days. Multiple keeps one hidden input a day.
    function write(list) {
        let fields = inputs();
        if (o.mode === 'multiple') {
            const holder = root.querySelector('[data-ds-cal-fallback]');
            fields.forEach((field) => field.remove());
            fields = list.map((day) => {
                const field = document.createElement('input');
                field.type = 'hidden';
                if (o.name !== null) field.name = `${o.name}[]`;
                field.dataset.dsCalInput = '';
                field.value = day;
                holder.append(field);
                return field;
            });
            return fields[fields.length - 1] ?? root;
        }
        fields.forEach((field, i) => {
            field.value = list[i] ?? '';
        });
        return fields[list.length > 1 ? 1 : 0] ?? root;
    }

    function commit(announce = true) {
        value = draft.slice();
        const from = write(value);
        report(shape(value), summary(value));
        observer?.takeRecords();
        if (!announce) return;
        from.dispatchEvent(new Event('input', { bubbles: true }));
        from.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function finish(complete) {
        hovered = null;
        draw();
        if (o.confirm) return;
        commit();
        if (complete && o.popover) {
            close();
            trigger()?.focus();
        }
    }

    function choose(iso) {
        if (off(iso)) return;
        active = iso;
        if (o.mode === 'single') {
            draft = [iso];
            return finish(true);
        }
        if (o.mode === 'multiple') {
            draft = draft.includes(iso) ? draft.filter((d) => d !== iso) : [...draft, iso].sort();
            return finish(false);
        }
        draft = draft.length === 1 ? [draft[0], iso].sort() : [iso];
        finish(draft.length === 2);
    }

    function onClick(event) {
        const day = event.target.closest('[data-ds-cal-day]');
        if (day) return choose(day.dataset.day);
        if (event.target.closest('[data-ds-cal-prev]')) return show(addMonths(cursor, -1));
        if (event.target.closest('[data-ds-cal-next]')) return show(addMonths(cursor, 1));
        const preset = event.target.closest('[data-ds-cal-preset]');
        if (preset) {
            draft = presetRange(preset).slice();
            // The end in view, and the start too when they fit together.
            const last = addMonths(month(draft[1]), 1 - shownCount());
            show(last > draft[0] ? last : draft[0]);
            return finish(true);
        }
        if (event.target.closest('[data-ds-cal-clear]')) {
            draft = [];
            return finish(false);
        }
        if (event.target.closest('[data-ds-cal-apply]')) {
            commit();
            close();
            return trigger()?.focus();
        }
        if (event.target.closest('[data-ds-cal-cancel]')) {
            close();
            trigger()?.focus();
        }
    }

    function onKey(event) {
        const day = event.target.closest('[data-ds-cal-day]')?.dataset.day;
        if (!day) return;
        const back = getComputedStyle(root).direction === 'rtl' ? 1 : -1;
        const lead = (weekday(day) - o.weekStart + 7) % 7;
        const by = {
            ArrowLeft: () => addDays(day, back),
            ArrowRight: () => addDays(day, -back),
            ArrowUp: () => addDays(day, -7),
            ArrowDown: () => addDays(day, 7),
            PageUp: () => addMonths(day, event.shiftKey ? -12 : -1),
            PageDown: () => addMonths(day, event.shiftKey ? 12 : 1),
            Home: () => addDays(day, -lead),
            End: () => addDays(day, 6 - lead),
        }[event.key];
        if (!by) return;
        event.preventDefault();
        focusDay(by());
    }

    // The preview of a half-made range follows the pointer and the focus.
    function onOver(event) {
        const day = event.target.closest?.('[data-ds-cal-day]')?.dataset.day ?? null;
        if (!range() || draft.length !== 1 || day === hovered) return;
        hovered = day;
        draw();
    }

    function onJump(event) {
        const select = event.target.closest('[data-ds-cal-jump]');
        if (!select) return;
        const [y, m] = parts(cursor);
        show(select.dataset.dsCalJump === 'year' ? key(Number(select.value), m, 1) : key(y, Number(select.value), 1));
    }

    const grids = root.querySelector('[data-ds-cal-months]');
    root.addEventListener('click', onClick);
    grids.addEventListener('keydown', onKey);
    grids.addEventListener('mouseover', onOver);
    grids.addEventListener('focusin', onOver);
    grids.addEventListener('mouseleave', onOver);
    grids.addEventListener('change', onJump);

    // A morph: new options (the month's events), or the server's value.
    observer = new MutationObserver(() => {
        read();
        const now = fromInputs();
        if (now.join() !== value.join()) {
            value = now;
            draft = now.slice();
            report(shape(value), summary(value));
        }
        draw();
    });

    draw();
    report(shape(value), summary(value));
    observer.observe(root, { subtree: true, childList: true, attributes: true, characterData: true });

    return {
        set(next) {
            const list = (Array.isArray(next) ? next : [next]).filter((d) => typeof d === 'string' && /^\d{4}-\d{2}-\d{2}/.test(d)).map((d) => d.slice(0, 10));
            const normal = o.mode === 'single' ? list.slice(0, 1) : [...new Set(list)].sort();
            if (normal.join() === value.join()) return;
            draft = normal;
            commit(false);
            if (normal.length && !inView(normal[0])) show(normal[0], false);
            else draw();
        },

        opened(open) {
            if (open) return;
            draft = value.slice();
            hovered = null;
            draw();
        },

        teardown() {
            observer.disconnect();
            root.removeEventListener('click', onClick);
            grids.removeEventListener('keydown', onKey);
            grids.removeEventListener('mouseover', onOver);
            grids.removeEventListener('focusin', onOver);
            grids.removeEventListener('mouseleave', onOver);
            grids.removeEventListener('change', onJump);
        },
    };
}
