/*
 * Calendar arithmetic on Y-m-d strings, for dsCalendar. Registers nothing.
 * Mirrored by Support\Calendar and held to tests/Fixtures/dates.json from
 * both sides, so the month the server draws and the month the script draws
 * after it are the same month.
 *
 * Never a Date for a day: `new Date('2026-03-29')` is UTC midnight, the 28th
 * west of Greenwich. Date.UTC appears only for arithmetic, where the timezone
 * cannot matter, and "today" is always the server's, passed in.
 */

export function key(year, month, day) {
    const at = new Date(Date.UTC(year, month - 1, day));
    return `${String(at.getUTCFullYear()).padStart(4, '0')}-${String(at.getUTCMonth() + 1).padStart(2, '0')}-${String(at.getUTCDate()).padStart(2, '0')}`;
}

export function parts(iso) {
    return [Number(iso.slice(0, 4)), Number(iso.slice(5, 7)), Number(iso.slice(8, 10))];
}

export function addDays(iso, days) {
    const [y, m, d] = parts(iso);
    return key(y, m, d + days);
}

// Held to the last day of a shorter month: 31 January plus a month is the
// end of February, never 2 or 3 March.
export function addMonths(iso, months) {
    const [y, m, d] = parts(iso);
    return key(y, m + months, Math.min(d, monthLength(y, m + months)));
}

export function monthLength(year, month) {
    return new Date(Date.UTC(year, month, 0)).getUTCDate();
}

/** 0 = Sunday … 6 = Saturday. */
export function weekday(iso) {
    const [y, m, d] = parts(iso);
    return new Date(Date.UTC(y, m - 1, d)).getUTCDay();
}

/** The weeks that show a month: rows of seven Y-m-d, outside days included. */
export function weeks(year, month, weekStart) {
    const first = key(year, month, 1);
    let day = addDays(first, -((weekday(first) - weekStart + 7) % 7));
    const rows = [];
    while (rows.length === 0 || day.slice(0, 7) === first.slice(0, 7)) {
        const row = [];
        for (let i = 0; i < 7; i++, day = addDays(day, 1)) row.push(day);
        rows.push(row);
    }
    return rows;
}

/** The ISO 8601 week of the row a day sits in: the week holding its Thursday. */
export function isoWeek(row) {
    const thursday = row.find((iso) => weekday(iso) === 4);
    const [y] = parts(thursday);
    const jan4 = key(y, 1, 4);
    const monday = addDays(jan4, -((weekday(jan4) + 6) % 7));
    return Math.floor(days(monday, thursday) / 7) + 1;
}

/** Whole days from one Y-m-d to another. */
export function days(from, to) {
    const [a, b, c] = parts(from);
    const [x, y, z] = parts(to);
    return Math.round((Date.UTC(x, y - 1, z) - Date.UTC(a, b - 1, c)) / 86400000);
}

/** The built-in ranges, from the server's today. */
export function presets(today) {
    const [y, m] = parts(today);
    const lastMonth = addMonths(key(y, m, 1), -1);
    return {
        today: [today, today],
        yesterday: [addDays(today, -1), addDays(today, -1)],
        last7: [addDays(today, -6), today],
        last30: [addDays(today, -29), today],
        thisMonth: [key(y, m, 1), today],
        lastMonth: [lastMonth, key(y, m, 0)],
        thisYear: [key(y, 1, 1), today],
    };
}
