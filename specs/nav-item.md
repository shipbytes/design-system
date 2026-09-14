# Nav item

Twenty-nine of these across two sidebar files that are otherwise a copy-paste
fork of each other. Every shell fix currently has to be made twice, and several
have not been.

## Anatomy

```
expanded                      collapsed
┌──────────────────────────┐  ┌────⑫
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

## The count

`count` is a number the **component** draws; `badge` is a slot that renders
whatever it is handed. Both exist, and the split is the point: a count put
through the slot is styled by each shell separately, and three sidebars using it
end up with three different pills.

It is **the same pill a tab count draws** — `neutral-tint` / `on-neutral-tint`,
inverting to `surface-inverse` / `on-inverse` on the active row, `tabular-nums`
so the rail does not reflow as the number changes width. A rail count and a tab
count are the same fact in two places; a reader should not have to learn two
shapes for it.

**Zero draws nothing at all, never a `0`.** The whole value of a badge is that it
reaches zero and goes away. A rail permanently showing `0` against four entries
teaches people to stop reading the numbers, after which the one that matters is
invisible too. Anything that is not a positive finite number is treated the same
way, so a count still loading is an absent pill and never a `NaN`.

**Capped, and rendered `99+`.** Past a hundred, "a lot" is the information; the
exact figure is one click away on the screen the entry opens. Four digits also do
not fit the collapsed pip.

**Badge only what reaches zero.** A personal queue — what is waiting on you, what
you are waiting on — earns a number because its absence is meaningful. A plant
volume that is never nought does not: a count that is always on stops being read,
and takes the ones beside it with it.

### Collapsed, it becomes a pip

Icon only, with the count as a small pill on the glyph's top-right corner.

This is the **one** thing that survives the rail shutting, and it is a deliberate
exception to the rule below rather than a hole in it. That rule is about a count
sitting *inline* where a label used to be, which reads as a number belonging to
nothing. A pip anchored to the glyph is the opposite shape — visibly attached to
the thing it counts — and it is the only reason a shut rail can still say that
something needs you.

The pip is **inverse in both states**. The expanded pill can afford the neutral
tint because it sits in a wide row; at pip size on a light chip it disappears,
and a badge that cannot be seen is not a badge.

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
