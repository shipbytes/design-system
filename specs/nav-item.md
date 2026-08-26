# Nav item

Twenty-nine of these across two sidebar files that are otherwise a copy-paste
fork of each other. Every shell fix currently has to be made twice, and several
have not been.

## Anatomy

```
expanded                      collapsed
┌──────────────────────────┐  ┌────┐
│ [icon]  Resumes      12  │  │[ic]│
└──────────────────────────┘  └────┘
  w-full gap-3 px-2 py-2        size-8 p-2
```

Both states are the same element. The rail animates its width and the item
follows; it does not swap between two components.

## Active state

**A raised white card, never a colour.** `border-strong` + `surface` +
`shadow-raised`, against a transparent border and no fill when inactive.

Colour would have to compete with the icon and the label for the same signal,
and it has to survive both themes. A change of *elevation* reads instantly and
means the same thing in dark.

The active item also carries `aria-current="page"`. The hand-written version
did not, so the only signal that a nav item was current was visual — a screen
reader user had no way to tell where they were.

## Type

`section` size at `medium` weight: 14/20, not the body 14/24. A nav rail is a
list of short labels, and the looser leading makes every row four pixels taller
for nothing.

## Collapsed state

The collapse condition is **passed in**, not assumed:

```blade
collapsed-when="(studio || ($store.sidebar.collapsed && !hovered))"   {{-- user --}}
collapsed-when="$store.sidebar.collapsed"                             {{-- admin --}}
```

The two shells genuinely differ — one has hover-to-peek and a studio mode that
locks the rail shut, the other has neither. A presentation component should not
know which store either of them uses, and hard-coding one would make the
component unusable in the other place. The label's `x-show` is derived from the
same expression, so the two can never disagree.

## Icon

A slot, not a name, for now — the app inlines its SVGs and swapping ninety-one
of them for named Heroicons is its own change with its own diff.

The icon takes its colour from the item's state (`fg-body` active,
`fg-subtle` otherwise) — **except** when chipped.

## The chip

The admin rail wraps each icon in a tinted well. When chipped, the icon keeps
**one** colour regardless of state: the chip is already a container, and making
it change colour too gives the active item three simultaneous signals (card,
chip, icon) for a single fact.

## Do not

- Style the active state by colouring the label. It reads as "this link is
  special", not "you are here", and it collides with the accent colour.
- Add a second active-ish state for "parent of the current page". If a section
  needs to show it contains the current page, that is what the sub-nav is for.
