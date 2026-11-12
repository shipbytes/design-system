/*
 * The caret in a text field: where it is drawn (caretBox, for dsMention's
 * list), and keeping it in place while a format rewrites the field around
 * it (dsMoneyInput groups digits and dsPhoneInput applies a mask, and both
 * put in characters the reader never typed). Registers nothing.
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

/*
 * Where the caret is drawn, as { x, y, height } from the field's top-left
 * corner, for x-ds-anchor's `at`. A field cannot be asked, so it is copied:
 * a hidden element given the field's text styles and inner width, holding
 * the field's text with the character after the caret in a span, so it
 * wraps exactly as the field wraps it. The span starts at the caret: its
 * left edge, or its right in a right-to-left field. Then the
 * field's border and scroll are allowed for, and the answer kept inside the
 * field, so a list follows a caret scrolled out of sight only to the edge.
 */
const COPIED = ['direction', 'fontFamily', 'fontSize', 'fontStyle', 'fontVariant', 'fontWeight', 'fontStretch', 'fontFeatureSettings', 'fontVariationSettings',
    'lineHeight', 'letterSpacing', 'wordSpacing', 'textTransform', 'textIndent', 'textAlign', 'tabSize', 'wordBreak', 'overflowWrap',
    'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'];

export function caretBox(field, at = field.selectionStart ?? 0) {
    const cs = getComputedStyle(field);
    const mirror = document.createElement('div');
    for (const key of COPIED) mirror.style[key] = cs[key];
    const lined = field.tagName === 'TEXTAREA';
    Object.assign(mirror.style, {
        position: 'absolute', top: '0', left: '-9999px', visibility: 'hidden', overflow: 'hidden', border: '0', boxSizing: 'content-box',
        whiteSpace: lined ? 'pre-wrap' : 'pre',
        // The field's inner width less its padding: clientWidth leaves out
        // the scrollbar, which narrows the lines.
        width: lined ? `${field.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight)}px` : 'auto',
    });
    mirror.textContent = field.value.slice(0, at);
    // The one character after the caret in a span of its own, then the rest
    // as text: a span holding all of it would, in mixed-direction text, be
    // split into runs whose first box is not the one beside the caret.
    const after = field.value.slice(at);
    const rest = document.createElement('span');
    rest.textContent = after[0] && after[0] !== '\n' ? after[0] : '.';
    mirror.append(rest, after.slice(rest.textContent === '.' ? 0 : 1));
    document.body.append(mirror);
    const box = mirror.getBoundingClientRect();
    const line = rest.getClientRects()[0] ?? rest.getBoundingClientRect();
    const x = (cs.direction === 'rtl' ? line.right : line.left) - box.left + field.clientLeft - field.scrollLeft;
    const y = line.top - box.top + field.clientTop - field.scrollTop;
    mirror.remove();
    const keep = (n, most) => Math.max(0, Math.min(n, most));
    return { x: keep(x, field.offsetWidth), y: keep(y, field.offsetHeight - line.height), width: 0, height: line.height };
}
