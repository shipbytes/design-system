# Icon

A thin wrapper over [blade-heroicons](https://github.com/blade-ui-kit/blade-heroicons).
It exists for three reasons the raw package does not cover: a size prop that is
actually generated, a v1→v2 name bridge, and an accessibility default that is
right far more often than the package's.

## Evidence

`icons/icons.json` is derived by matching the source dashboard's inlined SVG path
data against the heroicons package. What it found:

| | |
|---|---|
| Distinct SVG paths inlined in the app | 270 |
| Resolved to a Heroicon | 159 |
| Distinct Heroicons in use | 105 |
| Paths that are custom artwork, not Heroicons | 111 |

Three styles appeared: `24/outline` (the overwhelming majority), `20/solid`, and
`16/solid`. That is the whole basis for the `variant` prop — those three plus
`24/solid`, and nothing else.

The most-used icons are `x` (48), `plus` (46), `chevron-right` (40), `check` (40)
and `chevron-down` (32) — navigation and dismissal, not decoration. It matters
because it means most icons in this system sit *next to a label that already says
the same thing*, which is what sets the default below.

## Props

| Prop | Values | Default |
|---|---|---|
| `name` | any Heroicon v2 name, or a v1 name in the alias map | — |
| `variant` | `outline` \| `solid` \| `mini` \| `micro` | `outline` |
| `size` | any Tailwind size step | `config('blade-ui.icon_size')`, 4 |
| `label` | a string | none — see below |

`variant` maps onto blade-heroicons' prefixes: `o-` (24 outline), `s-` (24 solid),
`m-` (20 mini), `c-` (16 micro). The names `mini` and `micro` are Heroicons' own,
kept rather than renamed to `sm`/`xs` so a reader can look them up.

## Size, and the bug that shipped

`size` composes the class `size-{$size}` **at runtime**, and Tailwind's scanner
reads source *text*. `size="4.5"` in a Blade tag is not the string `size-4.5`, so
no rule is generated and the SVG expands to fill its container.

This shipped. Every `<x-ds::alert>` rendered a checkmark the height of its panel,
in any app that did not already use `size-4.5` for something else. Nothing threw;
"unused" is not an error.

The fix is the `@source inline("size-{…}")` list in `theme.css`, **generated** by
`build-tokens.mjs` from two sources:

1. every size the components actually ask for, scanned out of the Blade files, and
2. a base range, so a *consumer* writing `<x-ds::icon size="7" />` in their own
   view also works — their source says `size="7"`, which is not `size-7` either.

Hand-written, that list shipped `3.5` but not `4.5`. `npm test` now fails if a
component asks for a size the list misses.

> **Any new component that builds a class from a prop needs the same treatment.**
> Where the set of values is *finite*, prefer a lookup map of literal class
> strings instead — see [modal.md](modal.md#sizes). Only a genuinely open-ended
> prop needs the inline list.

Default is `4` (16px), which matched the dashboard's dominant `size-4`.

## Names: why an alias map exists

The source app was built against Heroicons v1 and picked up v2 icons later, so it
runs a mix. **v2 renamed 33 of the names in use.** A renamed name resolves to no
component at all and renders nothing — silently.

`config('blade-ui.icon_aliases')` maps the names most likely to be typed from
memory (`x`, `search`, `mail`, `cog`, `menu`, `logout`, …) onto their v2
spellings. `icons/icons.json` carries the full mapping.

The aliases are a **migration aid, not an API**. New code writes v2 names.

## Accessibility

**Icons are `aria-hidden` by default.** With 105 distinct icons mostly sitting
beside labels, announcing both is noise — a screen reader reading "trash Delete"
is worse than reading "Delete".

Pass `label` only when the icon is the **only** thing carrying the meaning: an
icon-only button, a status mark with no text. That flips it to
`role="img"` + `aria-label`, and `aria-hidden="false"`.

> blade-heroicons stamps `aria-hidden="true"` on every icon it renders. Left
> alone, that cancels the role and label — a screen reader skips the element
> entirely and the accessible name is never announced. Duplicate attributes
> resolve to the *first* occurrence and merged attributes come first, so the
> component sets it explicitly to win. Removing that line breaks every labelled
> icon in the system, and nothing reports it.

Icons inherit `currentColor`, so they take the colour of the text around them.
An icon that repeats an adjacent label may use `fg-subtle`; one that does not,
may not. See [color.md](color.md).

## The attribute-bag trap

The component builds its attribute bag in `@php` and passes it as `:attributes`.

That is not style. **Blade's component-tag parser gives up on a tag containing a
multi-line attribute-bag expression and emits the tag as literal text** — the
page shows `<x-dynamic-component …>` as words. `build-gallery.mjs` fails the
build when it sees that in rendered output, because it is the signature.

## Do not

- **Do not pass a size the base range does not cover** without checking the
  generated list. `npm test` will tell you.
- **Do not label a decorative icon.** Every redundant label is one more thing
  read aloud before the reader reaches the thing they wanted.
- **Do not use a v1 name in new code.** The alias map is for what already exists.
- **Do not set a colour on a wrapper and expect the SVG to take it** when the icon
  carries `fill="currentColor"` as a presentation attribute — that beats
  inheritance. `bottom-nav-item` reaches past it with `[&_svg]:fill-*`; see
  [bottom-nav.md](bottom-nav.md).
