# Kbd

A key on the keyboard, inline in text: "Press <kbd>⌘</kbd> <kbd>K</kbd> to
search."

## Sized by the text around it

Every size is in `em`: the key's padding, its minimum width, its font size
(0.8125em, monospace) and its line height. It sits in a heading, a body
paragraph or a tooltip and is the right size in each, so there is no `size`
prop. The bottom edge is an inset shadow on `border-strong`, which reads as a
key without a second colour.

## One key or a combination

The slot is one key. `:keys="['Ctrl', 'Shift', 'P']"` writes a combination the
way HTML defines it: an outer `kbd` holding one `kbd` per key, with a separator
(`+` by default, `separator=""` for none) between them, which a screen reader
reads as written: "Ctrl plus Shift plus P".

## Accessibility

- `<kbd>`, which a screen reader may announce as keyboard input.
- Symbols read badly: "⌘" may be said as "place of interest sign". Pair a
  symbol with its name in the prose, or use the word ("Cmd").
- Contrast: `fg-body` on `surface-subtle`, tested in both themes.

## Do not

- **Do not use it for buttons on screen.** "Press **Save**" is a button's name
  in bold, not a key.
- **Do not write shortcuts only one platform has** without the other.
