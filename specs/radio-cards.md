# Radio cards

Card-shaped options over visible native radios. Plain Blade and CSS: no
behaviour.

## Anatomy

```
Plan                                      ← legend
Change or cancel at any time.             ← help
┌───────────────────────────┐ ┌────────── ← grid, 1 to 4 columns
│ ◉  [icon]  Pro   $12 a mo │ │ ○  Free …
│            For a small…   │ │
│            Ten seats…     │ │
└───────────────────────────┘ └──────────
```

- **Group**: `fieldset[role=radiogroup]`, `aria-labelledby` the legend,
  `aria-describedby` the help or error, then `div.grid.gap-3` of cards, then
  the error.
- **Card**: `label[for]` holding the radio cell, the optional icon slot, and
  a text column: a title row (title, meta pushed to the end), the
  description, the body slot.
- **Radio**: ds::radio's styled native control (`appearance-none`, the dot
  stacked over it), with its own focus outline removed because the card
  draws it.

## How the group reaches the cards

The slot renders before the group's view. `name` reaches the cards through
`@aware`. Each radio carries a `data-ds-radio-card-input` hook, which the
group replaces with the host's `wire:model*` / `x-model*` attributes and
`required`, so they land on every radio, and the hook never reaches the page.

## Ids

`ds-card-` and the first 8 hex of `md5(name|value)`, or the card's `id`.
The title, meta, description and body take `-title`, `-meta`,
`-description`, `-body` after it. They are stable across renders, which is
what Livewire's morph matches on.

## States

| State | Treatment |
|---|---|
| resting | `border-border bg-surface` |
| hover | `border-fg/20` |
| checked | `border-accent ring-1 ring-accent bg-accent-wash`; radio filled with the dot |
| focus-visible | `outline-2 outline-offset-2 outline-focus-ring` on the card, via `has-focus-visible:` |
| disabled | `bg-surface-subtle`, `cursor-not-allowed`, text `fg-muted`; checked and disabled `border-border-strong` and a grey radio |
| error | the Field recipe's error line under the grid; `aria-invalid` on the group |

The colour change is `motion-safe:transition-colors` with the
`popover-enter` tokens. No ring moves between cards: that would be
decoration, and the cards are already far enough apart to read.

## Columns

| `columns` | Classes |
|---|---|
| 1 | `grid-cols-1` |
| 2 | `grid-cols-1 sm:grid-cols-2` |
| 3 | `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3` |
| 4 | `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4` |

Written out in full so a consumer's Tailwind scans them. Out of 1 to 4 throws.

## Accessibility

| | |
|---|---|
| Role | `radiogroup` (a fieldset) of `radio` |
| Name | Group: the legend. Radio: title and meta, by `aria-labelledby` |
| Description | Radio: description and body, by `aria-describedby` |
| State | Native `checked`, `disabled`, `required`; `aria-invalid`, `aria-required` on the group |
| Keyboard | Tab into the group; arrows move and choose, skipping disabled |
| Focus | Outline on the card |
| Not colour alone | The visible radio and its dot |

## Do not

- **Do not nest interactive content in a card.** It is a label.
- **Do not hide the radio.** It is the cue that this is one-of.
- **Do not animate a ring between cards.**
