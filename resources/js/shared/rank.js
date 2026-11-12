/*
 * Ranking typed text against a label, shared by dsCommandPalette and
 * dsTeamSwitcher so the two filters agree on what matches and what comes
 * first. Registers nothing.
 */

// Lower case, accents off, one space: "Café  Menu" finds "cafe m".
export function fold(text) {
    return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ');
}

/*
 * 4 the text starts with the query, 3 a word in it does, 2 it is somewhere
 * inside, 1 its letters are there in order, 0 no match.
 */
export function rank(text, q) {
    let at = text.indexOf(q);
    if (at === 0) return 4;
    const inside = at > 0;
    while (at > 0) {
        if (!/[\p{L}\p{N}]/u.test(text[at - 1])) return 3;
        at = text.indexOf(q, at + 1);
    }
    if (inside) return 2;
    const letters = q.replace(/ /g, '');
    let i = 0;
    for (const ch of text) if (ch === letters[i] && ++i === letters.length) return 1;
    return 0;
}
