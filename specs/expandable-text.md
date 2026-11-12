# Expandable text

Text held to a few lines, with a "Read more" that lets the rest out: a long
description in a card, a review, a ticket's first message.

## Only when it overflows

The button shows only when the text really is longer than `lines` (default 3),
**measured**: the box's height against that many of its own line heights. A
"Read more" that reveals nothing is a lie the reader learns to ignore. It is
measured again when the box changes width (ResizeObserver, with the window's
resize as the fallback), when its content changes (a Livewire morph), and when
the fonts arrive.

The state is written to the root as `data-ds-state`: `fits`, `clamped` or
`expanded`, for the classes to read: the fade shows only while clamped.

## It fails open

The clamp is the script's: an inline `max-height` it sets after measuring.
Without the script the text is all there and no button shows. Clamping in CSS
(`line-clamp`) with a button that needs the script would hide the end of the
text from everyone the script never reached.

The fade is a mask on the box, not an overlay: it fades the text itself, on any
background, in both themes.

## Accessibility

- The button is a real `<button>` after the text, with `aria-expanded` and
  `aria-controls` naming the box. Its words change (Read more / Show less);
  `moreLabel` and `lessLabel` set them.
- Pressing moves no focus. Closing again can move the button a long way up the
  page, so once the box has closed the button is scrolled back into view if it
  left it.
- A screen reader reads the clamped text in full: the clamp is visual, the
  text is never removed. That is intended: the button is for sighted readers
  scanning a page, and hiding text from a screen reader would need a second
  copy of it.
- The height animates only under `prefers-reduced-motion: no-preference`, with
  the dialog-enter duration and easing.

## Do not

- **Do not clamp something the reader must read to act.** Terms, a warning, an
  error: show them whole.
- **Do not clamp to one line.** One line is a truncation with a tooltip, or a
  link to the full text.
- **Do not put interactive things in the hidden part.** A link below the clamp
  is still in the Tab order, and focus lands on something the reader cannot
  see.
