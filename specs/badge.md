# Badge

A small, round-ended label that **annotates** something. A status, a count, a
category. It is not a control: nothing happens when you press one.

## Anatomy

```
 ●  Active        ← optional dot, then the label
└──────────┘        rounded-full, text-meta, px-2 py-0.5
```

| Part | Token | Why |
|---|---|---|
| Fill | `<tone>-tint` | The tint pair, not the solid. See below. |
| Label | `on-<tone>-tint` | Guaranteed 4.5:1 on its own tint, in both themes, by `npm test`. |
| Shape | `radius-full` | 9999px, not `calc(infinity * 1px)` — same render, but only the finite value survives outside Tailwind. |
| Type | `text-meta` | 12px. A badge that matches body size stops reading as an annotation. |

## Variants

`tint` (default) | `solid` | `outline`

**Tint is the default because a badge annotates, it does not act.** A solid fill
is the loudest thing the system can put on a surface, and a row of eight solid
badges in a table turns the table into the badge column. Solid is for the one
badge that must beat everything around it.

`outline` carries no tone at all — it is `border` + `fg-body`. It is the "this is
a category, not a status" badge, and it is deliberately the quietest of the three.

## Tones

`neutral` (default) | `accent` | `success` | `warning` | `danger`

Five, and no more. Not a free-form colour and not a decorative palette: the sheet
shipped with ten hues used as wayfinding and they were removed, because ten hues
is exactly the sprawl this system exists to prevent. See [sheet.md](sheet.md#tone).

A tone is a **claim about state**. `success` means the thing succeeded, not that
it is green.

> An unknown tone falls back to `neutral` rather than throwing. A typo'd tone
> should degrade to the quiet default, not blank the fill and leave unreadable
> text on an unpainted background.

### How the tints are built

Light uses the fixed 100/700 pair. Dark uses **15% alpha of the base colour over
the surface**, so both themes come from one rule instead of two hand-tuned ramps
that drift apart. `npm test` checks all five `on-<tone>-tint` / `<tone>-tint`
pairs in both themes.

In `solid`, `success` and `warning` take `on-inverse` rather than a matching
`on-*` token — those two fills are light enough that a white label fails, and
`on-inverse` is the near-black the system already uses on light fills.

## The dot

`dot` adds a 6px leading circle in the tone's **base** colour, not its tint — a
tint-on-tint dot is invisible.

It is `aria-hidden`, because **the label already says the state**. A dot is a way
to find the badge faster in a scan, not a second channel of meaning. Which also
means: a badge whose meaning is *only* the dot is a broken badge.

Use it when the badge reports something live — a job that is running, a
connection that is up. Not on a category.

## Accessibility

- A badge is a `<span>`. It has no role, because it is not a control and it is
  not a landmark.
- **It is not a live region.** A badge that changes to report an event needs the
  host to announce it — see the alert's `role` handling in [alert.md](alert.md).
- Colour is never the only carrier: every badge has a label. That is the WCAG 1.4.1
  requirement and it is why there is no icon-only badge variant.
- Contrast for all five tint pairs is enforced by `npm test` in both themes.

## Do not

- **Do not put a click handler on it.** If it does something it is a button; if it
  goes somewhere it is a link. A pressable badge has no focus ring, no hover
  state and no keyboard access, and none of that is visible in the markup.
- **Do not use `solid` as the default.** See above — it is for the exception.
- **Do not add a tone for a new feature.** Tones are states. A feature that wants
  its own colour wants an icon.
- **Do not put a badge in a heading.** `text-meta` inside `text-heading` sets the
  line box by the heading and the badge floats off its baseline.
- **Do not use it for a count that must be read** by a screen reader in context
  without its label — "3" alone announces as "three".
