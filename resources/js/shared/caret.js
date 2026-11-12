/*
 * Formatting a field while someone types in it, without the caret jumping:
 * dsMoneyInput groups digits and dsPhoneInput applies a mask, and both put
 * in characters the reader never typed. Registers nothing.
 *
 * `keep(ch)` says which characters are the reader's (digits, a decimal
 * point); everything else is decoration the format owns.
 */

/*
 * Write `text` into the field and put the caret back after the same number
 * of the reader's characters it was after, however many separators now sit
 * between them.
 */
export function rewrite(field, text, keep) {
    const before = field.value;
    let count = 0;
    for (const ch of before.slice(0, field.selectionStart ?? before.length)) if (keep(ch)) count++;
    if (before !== text) field.value = text;
    if (document.activeElement !== field) return;
    let at = 0;
    while (at < text.length && count > 0) if (keep(text[at++])) count--;
    field.setSelectionRange(at, at);
}

/*
 * Backspace or Delete next to a separator would delete only the separator,
 * which the format puts straight back: the key would do nothing. It deletes
 * the reader's character beyond it instead.
 */
export function skipSeparators(event, field, keep) {
    const back = event.inputType === 'deleteContentBackward';
    if (!back && event.inputType !== 'deleteContentForward') return;
    const { selectionStart: start, selectionEnd: end, value } = field;
    if (start !== end) return;
    let at = back ? start - 1 : start;
    if (at < 0 || at >= value.length || keep(value[at])) return;
    while (at >= 0 && at < value.length && !keep(value[at])) at += back ? -1 : 1;
    if (at < 0 || at >= value.length) return;
    event.preventDefault();
    field.setRangeText('', at, at + 1, 'end');
    field.dispatchEvent(new Event('input', { bubbles: true }));
}
