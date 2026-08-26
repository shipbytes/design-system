# Avatar

A person or a thing, at a glance. An image when there is one, initials when
there is not.

## Anatomy

```
 ╭────╮
 │ AL │   ← initials, or the image, cropped to the shape
 ╰────╯
```

| Part | Token | Why |
|---|---|---|
| Fill | `neutral-tint` + `on-neutral-tint` | The badge tint pair, already contrast-checked in both themes by `npm test`. |
| Shape | `radius-full`, or `radius-control` when `square` | See below. |
| Image | `object-cover` | A portrait cropped to a circle is the point. `contain` letterboxes it against the tint. |

## Sizes

`xs` (24px) | `sm` (32px) | `md` (40px) | `lg` (48px)

A closed set of literal classes, not `size-{$size}` — see [icon.md](icon.md#size-and-the-bug-that-shipped).

`sm` matches the 32px `control-sm` height, so an avatar sits level with a small
button in a table row without a nudge. `md` matches the sheet's row tile.

## Round or square

Round by default. **`square` is for a company, a project or a file** — a circle
reads as a person, and a logo in a circle gets its corners eaten.

That is the only rule; there is no "brand" variant and no size-dependent shape.

## Initials

Taken from the **first and last** word of `name`, not the first two — "Ada King
Lovelace" is AL, because the middle name is the part nobody uses.

Everything goes through `mb_*`. `strtoupper` corrupts a multi-byte first letter,
and a name is the single most likely place in an interface to meet one; `mb_substr`
also takes the first *grapheme*, so an accented letter or an emoji survives
instead of being cut in half.

With neither `src` nor `name` the component renders a placeholder mark rather
than an empty circle. A blank avatar is a bug the reader cannot report.

## Accessibility

- With `src`, the image's `alt` is `name`.
- **`decorative` hides the whole thing** — `aria-hidden`, and `alt=""` on the
  image. Use it whenever the name is written beside the avatar, which is most of
  the time: a row that announces "Ada Lovelace, Ada Lovelace" is worse than one
  that announces it once.
- No `name` and no `decorative` still hides it, because an avatar with nothing to
  announce has nothing to announce.
- The initials carry `select-none`, so dragging across a list of people does not
  produce "ALJSMK".

## Do not

- **Do not use it as a button.** Wrap it in one. An avatar with a click handler
  has no focus ring, no hover state and no keyboard access.
- **Do not derive the colour from the name.** A hash-to-hue avatar is nine
  unmanaged colours arriving through the back door — the exact sprawl
  [sheet.md](sheet.md#tone) removed from this system.
- **Do not put a status dot on it here.** Compose one: the dot belongs to the
  layout that knows what "online" means, not to the avatar.
- **Do not stretch it.** It is `shrink-0` for a reason — in a flex row a
  squashed avatar is an ellipse, and nobody notices until it is in production.
