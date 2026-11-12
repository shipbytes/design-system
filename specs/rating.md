# Rating

Stars over native radios, to choose a score; or one image of a score, to
show it. Plain Blade and CSS: no behaviour.

## Anatomy

```
How was your visit?            ← legend
☆ ☆ ☆ ☆ ☆  ✕                   ← star labels in a row of their own; "No rating" after
Shown to the host after…       ← help, or the error in its place
```

- **Group**: `fieldset[role=radiogroup]`, `aria-labelledby` the legend,
  `aria-describedby` the help or error. Host attributes land here, except the
  model and `required`.
- **Star**: a `label` holding an `sr-only` radio (`value` 1 to `max`), the
  Heroicons star outline drawn inline, and an `sr-only` name ("3 stars"). The
  inline path lets the label's `fill` reach the glyph; ds::icon sets its own.
- **Stars row**: `div.group/stars` around the stars only, so the sibling
  selectors that light them see stars and nothing else.
- **No rating**: a label with the radio `value=""`, after the row. Omitted
  when `required`.
- **Readonly**: a `div` with an optional label line, then `span[role=img]`
  of `max` glyphs. A half star is the filled glyph clipped to its start half
  (`w-1/2 overflow-hidden`) over the outline.

## States

| State | Treatment |
|---|---|
| unlit | `fill-transparent`, outline `text-fg-muted` |
| lit | `fill-warning`, outline `text-on-warning-tint` (amber alone is under 3:1 on a light page) |
| chosen | that star and those before it lit, while the pointer is off the row |
| hover preview | the hovered star and those before it lit; the chosen state yields while the pointer is on the row |
| keyboard focus | the focused star and those before it lit, and an outline on the focused star |
| no rating chosen | nothing lit; the cross `opacity-0` (back to full when focused) |
| disabled | radios disabled, `cursor-not-allowed`, unlit `text-fg-subtle`, chosen `fill-fg-subtle` |
| error | the Field recipe's error line; `aria-invalid` on the group |

"Lit" is only ever added, by six variant pairs on each star:
`has-checked` and `has-[~_*_:checked]` (under `not-group-hover/stars`),
`hover` and `has-[~_*:hover]`, `has-focus-visible` and
`has-[~_*_:focus-visible]` (under `not-group-hover/stars`). No rule unlights,
so none fight. `not-group-hover` also holds where there is no hover, so a
touch screen shows the choice. The colour change is
`motion-safe:transition-colors` with the hint-enter tokens.

## Choices and why

- **Clearing is a radio, not a button.** Keyboard, form reset and posting
  all stay native, and the cross is reached the same way as any star. It sits
  last so the arrow order runs 1, 2, … `max`, No rating, 1.
- **No rating is hidden while it is the choice**, by opacity, so a fresh form
  reads as plain stars. Focus brings it back, since an invisible radio with
  focus would be a lost focus ring.
- **`required` removes it** rather than leaving a choice the form would refuse.
  `required` goes on the star radios only, which is what the browser checks.
- **Readonly rounds to the nearest half** for both the picture and the name,
  so they agree; the exact number is the host's to print beside it.
- **Readonly takes any number; choosing takes whole ones.** A chosen value is
  clamped to 1 to `max`; 0 or empty is "no rating".

## Accessibility

| | |
|---|---|
| Role | `radiogroup` (a fieldset); readonly `img` |
| Name | The legend; each radio "n stars"; readonly "3.5 out of 5" or "Not rated" |
| State | Native `checked`; `aria-invalid`, `aria-required` on the group |
| Keyboard | Tab into the group; arrows move and choose, wrapping through No rating |
| Focus | Outline (`focus-ring`) on the label of the focused radio |
| Contrast | Unlit outline `fg-muted`, lit outline `on-warning-tint`: 3:1 or better on surface and sunken surface in both themes |
| Motion | Colour transition only, `motion-safe` |

## Do not

- **Do not light with script.** The radios are the state.
- **Do not show the half stars as choosable.** Halves are display only.
- **Do not hide the number behind a readonly picture.** The host prints it.
