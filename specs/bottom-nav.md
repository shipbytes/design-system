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
| Count pill | `accent` fill, `on-accent` text, `ring-2 ring-surface` | Informational, not an error, so `accent` rather than `danger`. The pair is the one `test:tokens` already holds at 4.5:1 in both themes (white on blue-600, zinc-950 on blue-400). The ring is the bar's own colour and cuts the pill out of the glyph it overlaps. |

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

## The count

`badge` puts a count on the icon, for the tab whose screen has something waiting:
unread tickets, new messages.

```
     ▢⁽³⁾        ▢⁽⁹⁺⁾
    Inbox       Support
```

| Part | Value | Why |
|---|---|---|
| Anchor | the icon wrapper, which is `relative` | Against the whole item the pill would sit in the corner of a tab a fifth of the screen wide, attached to nothing. |
| Position | `-top-1.5`, `start-full -ms-2.5` | Its start edge sits 10px inside the icon's end edge, so it overlaps the corner and a wider "9+" grows *outward*, away from the glyph. Logical, so it mirrors in RTL. |
| Size | `h-4 min-w-4 px-1`, `text-meta` semibold, `tabular-nums` | A 16px circle for one digit, a short capsule for "9+". `meta` is the smallest type in the system; nothing smaller is legible at a glance on a phone. |

**The cap is the component's job.** Pass the raw count. 1–9 draw as themselves;
anything over 9 draws as **"9+"**. The pill has room for two characters, and the
difference between 14 and 140 unread is not something a tab bar can usefully
say: it means "go and look", which "9+" says.

**Nothing to count draws nothing.** `null`, `0`, `'0'`, a negative number and
`''` render no pill and no spoken text, so `:badge="$count"` needs no guard. An
empty pill, or a "0", is a tab that says "look here" and has nothing there.

**A non-numeric string is drawn as given**, uncapped, and spoken as given:
`badge="new"` draws "new". Keep it to one short word: the pill grows outward, and
a phrase runs into the next tab. A numeric string (`"3"`) counts as a number.

The pill is not `ds::badge`. That component is an inline annotation in a row of
text; this is a positioned overlay with its own cap and its own spoken text, and
building it from the badge would mean overriding every one of its classes.

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
- **The count is spoken as part of the label.** The visible pill is
  `aria-hidden="true"`, and the label carries an `sr-only` suffix, so the item's
  accessible name is **"Support, 3 new"**. The spoken count is the REAL one:
  "Support, 12 new" where the pill shows "9+", because "nine plus" is not a
  number anyone should have to hear. A word badge is spoken as given:
  "Changes, new". The suffix sits inside the label's own span, not beside it, so
  the name reads as one phrase rather than "Support , 3 new".
- The pill is `accent` on `on-accent`, the pair `test:tokens` holds at 4.5:1 in
  both themes, and its fill is 4.5:1 against the bar's `surface` (the same pair
  as a link). It never carries the meaning alone: the spoken name carries it too.

## Do not

- **Do not show it above `lg`.** It is `lg:hidden` and the sidebar owns that
  breakpoint. Both at once gives the reader two disagreeing answers to "where am
  I".
- **Do not exceed five items.** Below five the targets are comfortable; above
  five they are not, and the fifth should be a "More" sheet — which is exactly
  what [sheet.md](sheet.md) is for.
- **Do not cap the count yourself.** Pass `$count`, not `min($count, 9)` or
  `'9+'`. The component draws "9+" and speaks the real number; a pre-capped
  value makes a screen reader say "nine" for 140, and `'9+'` is drawn as a word
  and spoken as "9+".
- **Do not use the count for status.** "Offline" or "Error" is not something
  waiting to be read; that belongs on the screen, in an `alert`.
- **Do not put an action in it.** It is navigation. A destructive or creative
  action belongs in the FAB (`button` at `size="fab"`), which overlaps the bar
  rather than sitting in it.
- **Do not rely on the icon alone for the active state.** See the fill trap: when
  that breaks, colour is all that is left, and colour alone fails WCAG 1.4.1.
- **Do not nest it inside a scrolling container.** `fixed` resolves against the
  viewport only until an ancestor has a `transform`, `filter` or `perspective` —
  then it resolves against *that*, and the bar scrolls away with the content.
