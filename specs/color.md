# Colour

Every token names a **job**, not a colour. That is what lets the same markup
work in both themes and a rebrand touch one file.

## Choosing a token

| You are styling | Use |
|---|---|
| The card content sits on | `surface` |
| The ground behind cards and the sidebar | `surface-sunken` |
| A table head, or a row on hover | `surface-subtle` |
| A primary button, the mobile FAB | `surface-inverse` + `on-inverse` |
| A heading | `fg` |
| Running copy in a row or cell | `fg-body` |
| A label, subtitle, timestamp, or count | `fg-muted` |
| An icon that repeats an adjacent label | `fg-subtle` |
| A link, or informational emphasis | `accent` |
| Any hairline | `border` |
| A card edge that must read as raised | `border-strong` |
| A badge or alert | `<tone>-tint` + `on-<tone>-tint` |
| A control that needs a third hue, meaning nothing | `violet-tint` + `on-violet-tint` |
| A filled destructive button | `danger` + `on-danger` |

Never reach past these into `color.zinc.500` and friends. The palette exists to
feed the semantic layer; using it directly is how the app ended up with two
neutral ramps, two success ramps and nine colour families doing five jobs.

## Accessibility rules

These are enforced by `npm test`, which checks every pair below in both themes.

**`fg-subtle` is not for text a user must read.** It sits at 2.63:1 on
`surface` in light, below the 3:1 needed for a meaningful UI mark. It is
allowed only where the information is *also* conveyed in words next to it — the
icon beside a nav label, the chevron beside "View all". That is the WCAG
redundant-information exception.

> **Migration rule:** the source dashboard used `text-zinc-400` for timestamps.
> Timestamps are not redundant — nobody else on the row says "3 days ago". They
> move to `fg-muted` (4.83:1). This is a real fix, not a restyle.

## `violet-tint` is the one tint that is not a tone

Every other tint belongs to a tone: `success-tint` means it worked,
`warning-tint` means look at this, `danger-tint` means it did not. Each comes
with a base colour, a wash and a place in `Badge`, because each is a *meaning*
the system can state.

`violet-tint` is not. It was added for the gate terminal's action row, where
three equally-weighted controls sit side by side and the guard reaches for one
without reading it — so the hue is a **targeting aid**, the way the colour of a
lever in a cab is. Success and accent were already the first two; warning and
danger were unavailable, because both already mean something on that screen and
a button must not borrow a status word.

So it ships as a pair and nothing else: no `violet` base, no `violet-wash`, and
no tone on `Badge`. There is no filled violet button and no violet alert strip,
because there is nothing for either of them to say. If you find yourself
wanting one, what you actually want is a tone — and the right move is to argue
for the meaning first, not to reach for the colour.

**Tints are computed differently per theme, on purpose.** Light uses the fixed
100/700 pair. Dark uses **15% alpha of the base colour over the surface**, so
there is one rule instead of a second hand-tuned ramp that can drift.

**`on-danger` flips in dark.** `danger` lightens to red-400 there, and white on
red-400 is 2.87:1 — a fail. Dark uses a near-black label instead, mirroring how
`on-accent` already worked.

**The input outline is a known deviation.** `border` sits at ~1.1:1, inherited
from the Catalyst recipe where the affordance comes from the white fill and
shadow rather than the hairline. Kept deliberately; mitigated by a focus ring
that does pass 3:1 on every surface. Recorded here so it stays a decision.

## What changed from the app, and why

| Before | After | Reason |
|---|---|---|
| `zinc` and `gray` both used as neutrals | `zinc` only | Two ramps, one job, different hue temperature |
| `emerald` + `green` + `lime` for success | `success` | Three families, one meaning |
| `blue` and `indigo` both used for links | `accent` = blue | blue-500 was already the focus-ring colour; indigo would put two blues on every focused field |
| `pink` for negative deltas | `danger-tint` | Stray family |
| Every family spanning 9–11 shades | One shade per role | Nothing enforced which shade meant what |
