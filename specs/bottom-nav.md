# Bottom nav

The phone's primary navigation. A fixed bar below the `lg` breakpoint, hidden
above it — where the sidebar (`nav-item`) takes over.

Covers `bottom-nav` and `bottom-nav-item`.

## Anatomy

```
┌──────────────────────────────────────────┐
│   ▣        ▢        ▢        ▢        ▢  │  ← h-16, justify-around
│  Home    Projects  Reports  Team   More  │  ← text-meta, mt-1
└──────────────────────────────────────────┘
   ↑ border-t border-strong, bg-surface
   ↑ safe-area-bottom
```

| Part | Token | Why |
|---|---|---|
| Bar | `surface` | It sits over scrolling content and must be opaque. |
| Top edge | `border-strong` | The one hairline in the system that has to separate two *surfaces*, not delimit a card. `border` disappears against content scrolling under it. |
| Height | `h-16` (64px) | 64px holds a 20px icon, a 12px label and a touch target ≥44px. |
| Active | `fg` + `[&_svg]:fill-fg` | Full strength. |
| Inactive label | `fg-muted` | Readable — 4.5:1. |
| Inactive icon | `fg-subtle` | Allowed: the label beside it says the same word. |

**The label and the icon take different inactive weights on purpose.** The icon
carries recognition and can afford to be lighter; the label carries the meaning
and cannot. Active pulls both to full strength.

## `safe-area-bottom` is not decoration

Without it the bar sits under the home indicator on a notched phone and **the
last few pixels of every tab are untappable**. The tabs look completely normal.
The reader presses one and nothing happens.

## The page must reserve room

A fixed bar cannot push content, so the shell pads its content area by the bar's
height. Padding, not margin — margin on the last child collapses and the final
row of every scrolling list ends up behind the bar.

The component cannot do this for you: it does not know what it is fixed over.

## `hidden`

Hides the bar entirely, for a screen that supplies its own action bar in the same
place. Two bars stacked at the bottom of a phone is a screen with no content.

It renders as `display: none`, not as an unmounted component, so the surrounding
layout does not shift when a screen toggles it.

## The fill trap

Active state fills the icon rather than only recolouring its stroke. The fill has
to land on the `<svg>` **itself**, not on a wrapper:

```blade
<span class="flex [&_svg]:fill-fg">…</span>
```

`fill` is an inherited SVG property — but the Heroicons carry
`fill="currentColor"` as a **presentation attribute**, and a presentation
attribute on the element beats a value inherited from an ancestor. So a `fill`
set on the wrapper is ignored, the icon silently takes the link's text colour,
and the active state looks identical to the inactive one. The child selector
reaches past it.

Same family as the icon-size bug: correct-looking markup, no error, wrong pixels.

## Accessibility

- The bar is `<nav aria-label="Primary">`. A page usually has more than one
  navigation landmark — this one and the sidebar — and unlabelled ones are
  indistinguishable in a landmark list.
- The active item carries **`aria-current="page"`**. Colour alone does not tell a
  screen reader which tab you are on.
- An item renders `<a>` when it has `href`, `<button>` otherwise. The "More" tab
  opens a sheet, so it is genuinely a button.
- Each item is at least 44×44 — `flex-1` plus `py-2` inside a 64px bar.
- Labels are always present. There is **no icon-only variant**: five unlabelled
  glyphs is a memory test.

## Do not

- **Do not show it above `lg`.** It is `lg:hidden` and the sidebar owns that
  breakpoint. Both at once gives the reader two disagreeing answers to "where am
  I".
- **Do not exceed five items.** Below five the targets are comfortable; above
  five they are not, and the fifth should be a "More" sheet — which is exactly
  what [sheet.md](sheet.md) is for.
- **Do not put an action in it.** It is navigation. A destructive or creative
  action belongs in the FAB (`button` at `size="fab"`), which overlaps the bar
  rather than sitting in it.
- **Do not rely on the icon alone for the active state.** See the fill trap: when
  that breaks, colour is all that is left, and colour alone fails WCAG 1.4.1.
- **Do not nest it inside a scrolling container.** `fixed` resolves against the
  viewport only until an ancestor has a `transform`, `filter` or `perspective` —
  then it resolves against *that*, and the bar scrolls away with the content.
