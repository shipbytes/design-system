# Code block

Code to read and copy: a snippet in the docs, an API key's curl example, a
config file. With line numbers, highlighted lines, a scroll limit and a Show
more.

## No highlighter

The package colours nothing. A highlighter is a large dependency with its own
release cycle, and every host that wants one already has an opinion (Shiki,
Phiki, Torchlight, highlight.php). The block is the chrome: the frame, the
header, the copy button, the lines. `:html` takes a highlighter's output and
renders it, unescaped, in the same chrome, with the highlighter's own `pre` and
`code` unwrapped and every tag still open at a line's end closed there and
opened again on the next, so line numbers and highlighted lines still work.

What the package does ship is the colours, as tokens: `--ds-code-fg`, `-bg`,
`-comment`, `-keyword`, `-string`, `-constant`, `-function`, `-parameter`,
`-punctuation`, `-link`, `-highlight` and `-highlight-edge`, on
`[data-ds-code]`, each mapped to a text token already tested for AA contrast on
`surface-subtle` in both themes. A highlighter's CSS-variables theme points at
them, and the code matches the system in light and dark with no second theme.

## Anatomy

```
┌──────────────────────────────────────────┐
│ Invoice.php                   PHP   [⧉]  │ ← figcaption: filename, language, copy
├──────────────────────────────────────────┤
│  1  <?php                                │ ← pre role=region tabindex=0 dir=ltr
│  2                                       │   code: one block per line
│  3  final class Invoice                  │ ← mark: a highlighted line
├──────────────────────────────────────────┤
│ Show more                                │ ← expandable only, when it overflows
└──────────────────────────────────────────┘
```

No filename and no language: no header, and the copy button floats in the
corner, with room kept for it at the end of the lines.

## Content

The slot is escaped and its whitespace kept. **The slot is trimmed** by Blade,
so the first line's indentation is lost and the rest keep theirs: code indented
to match the template renders with every line but the first shifted. Write the
code flush left, or pass `:code` (a string, a heredoc, `file_get_contents`),
which is not trimmed. One trailing newline is dropped; nothing is dedented,
because guessing what indentation is the template's is how code changes
meaning.

## Lines

Each line is a block in a grid, so a highlighted line's ground runs the whole
scroll width. Line numbers are a CSS counter in `::before`, `user-select: none`:
they are never selected or copied, and never read out. An empty line holds a
`<br>`, because a browser copying a selection gives an empty block no line of
its own. `highlight` takes `"3,7-9"` or an array; highlighted lines are `mark`,
with a bar at the start edge as well as the ground.

## Limits

`maxHeight` is in lines. Past it the block scrolls inside itself. `expandable`
adds a "Show more" button (dsClamp, shared with expandable-text) shown only when
the code is taller than the limit; pressing lifts the cap. Without the script,
the block scrolls and no button shows.

## Accessibility

- `<pre><code>`, `dir="ltr"` whatever the page's direction: code reads left to
  right in every language.
- The scroll box is `role="region"`, named by `label`, else the filename, else
  "PHP code" from the language, else "Code", with `tabindex="0"`: a box that scrolls must be
  reachable by keyboard to be scrolled.
- The copy button is named "Copy code" and says "Copied" in a live region
  (ds::copy-button). It copies the code as written, not the rendered text.
- Show more has `aria-expanded` and `aria-controls`; pressing it moves no focus.
- Highlighted lines say nothing to a screen reader beyond the `mark` role;
  if a highlight is the point, say so in the prose.

## Kbd

`ds::kbd` is the inline key: em-sized, so it fits the text it sits in, and pure
CSS. `:keys="['Ctrl', 'K']"` writes the nested form, one `kbd` per key inside an
outer one, as HTML defines a key combination. See [kbd.md](kbd.md).

## Do not

- **Do not put a whole file in a page without `maxHeight`.** Two hundred lines
  push everything after them out of reach.
- **Do not pass user input to `:html`.** It is unescaped by design. Pass it
  through a highlighter that escapes, or use `:code`.
- **Do not use it for one command in a sentence.** That is `<code>` inline.
- **Do not turn off `copy` for something people paste.**
