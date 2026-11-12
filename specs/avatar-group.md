# Avatar group

An overlapping stack of `ds::avatar`s, cut off at `max` with a "+K" chip.

## Anatomy

```
 ╭──╮╭──╮╭──╮╭──╮
 │AL││GH││AT││+3│   ← avatars on surface backings, then the chip
 ╰──╯╰──╯╰──╯╰──╯
```

| Part | Token | Why |
|---|---|---|
| Avatar | the existing `ds::avatar`, `decorative` | Unchanged; the group passes `size` and nothing else. |
| Backing | `surface`, `ring-2 ring-surface`, shape of the avatar | See Ground. |
| Chip | `neutral-tint` + `on-neutral-tint` | The avatar's own initials pair, so it reads as one more of them. |
| Overlap | `-ml-1` / `-1.5` / `-2` / `-2.5` for xs / sm / md / lg | See Overlap. |

## Data, not a slot

The group takes `avatars`, a list of `name`, `src`, `square`, and renders each
avatar itself. A slot of hand-written avatars was the alternative and fails
both of the group's jobs: the size has to reach every avatar, and Blade cannot
push a prop into rendered slot content (`@aware` would leak any ancestor's
`size`, a modal's included, into every avatar on the page); and `max` would
have to hide finished HTML it cannot count reliably.

## Ground

Each avatar sits on an opaque backing of `surface` ringed in `surface`. The
ring is the ground colour drawn round the face, which is what makes an overlap
read as a cut-out.

- **`surface`, from the token.** A stack nearly always sits on a panel, where
  the ring vanishes into the ground. On the sunken ground it shows as a thin
  light outline, which still separates the faces, in both themes. A literal
  white would glow in dark.
- **The backing is opaque on purpose.** In dark the avatar's `neutral-tint` is
  translucent, and without a backing the overlaps showed through as darker
  lenses, one face visible inside the next.

## Overlap

About a fifth of the avatar's width. A quarter looked tidier and cut the last
letter of every pair of initials, because the ring extends 2px past the
margin. Later avatars sit over earlier ones (document order).

## Counting

`shown = first max of avatars`; `total = max(total, count(avatars))`;
`K = total - shown`. No chip when K is 0. `max` absent shows everything.

## Accessibility

| | |
|---|---|
| Role | `group`, named by `label`; no role without one |
| Content | one visually hidden sentence: "Ada Lovelace, Grace Hopper, and 3 more" |
| Avatars | `decorative` (`aria-hidden`); the sentence speaks for them |
| Chip | `aria-hidden` text; with `href`, a link named "K more", and the sentence drops the count |
| Keyboard | only the linked chip, if any, is focusable |

One sentence instead of N images: a stack is a summary, and reading it as six
separate images and a "+3" is six interruptions to say one thing.

## Do not

- **Do not link each avatar.** Overlapping targets fail the target-size rule.
- **Do not change the avatar to fit the group.** The group adapts to it.
- **Do not use a hard colour for the ring.** It is the ground's colour or it is
  a border.
