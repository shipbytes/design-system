# Sub-nav

A rail item that holds the pages belonging to it: a disclosure button and,
under it, ordinary `ds::nav-item`s, indented. Toggling needs Alpine (one
inline flag); everything the reader sees before it boots is rendered by PHP.

`specs/nav-item.md` says a nav item has one active state and that "contains
the current page" belongs here. This is that.

## Anatomy

```
┌──────────────────────────┐
│ [icon]  Reports        ⌃ │   ← button[aria-expanded][aria-controls]
└──────────────────────────┘
    │  Revenue                   ← div[role=group], labelled by the button
    │ ┌──────────────────────┐
    │ │ Churn                │   ← a nav-item, aria-current="page"
    │ └──────────────────────┘
    │  Usage
```

- **Root**: `div`, `x-data="{ dsSubNavOpen: <bool> }"`. Attributes passed to
  the component land here, `id` included; the button is `<id>-button` and the
  pages `<id>-pages`. Without an `id` the base is `ds-sub-nav-` and a hash of
  the label and pages, stable across renders but the same for two identical
  sections, so a rail drawn twice on one page (a desktop rail and a phone
  sheet) gives each sub-nav an `id`.
- **Button**: `button[type=button]` with the nav-item's row classes (`w-full
  gap-3 px-2 py-2`, `rounded-control`, `text-section font-medium`), the icon
  slot, the label and a `chevron-down` mini icon that turns 180° when open,
  from `aria-expanded` itself (`group-aria-expanded:rotate-180`), so the PHP
  value and the Alpine one draw the same chevron.
- **Pages**: `div[role=group][aria-labelledby=<button>]`, id named by the
  button's `aria-controls`. `border-s border-divider` is the guide line.
- **Collapsed link** (only with `collapsedWhen`): `a[title]` with the
  nav-item's collapsed classes (`size-8 justify-center p-2`), the icon and a
  `sr-only` label.

## The indent

The guide line sits under the centre of the parent's icon and each page's
label starts where the parent's label starts. The indent says "belongs to"
without a second column of icons.

| Icon | Group |
|---|---|
| default 16px | `ms-4 ps-2.75` |
| chip, 20px | `ms-4.5 ps-3.25` |

Those are measured, not derived: an icon of another size moves the parent's
label and the two stop lining up. The line stays under the rail's icons.

## States

| | Label | Icon | Card |
|---|---|---|---|
| Neither | `fg-body`, `fg` on hover | `fg-subtle` | none |
| Contains the current page | `fg` | `fg-body` | none |
| Is the current page | — | — | the page's own nav-item, raised |

**Containing the current page is the label and icon at full strength, and
nothing more.** The raised card is the one mark of "you are here", and it
stays on the page's own row. A second card, or a tint on the section, would
say the section is the page; a coloured label is what nav-item's spec already
rules out.

The state is read off the rendered children: a section contains the current
page when a child carries `aria-current="page"`. No second flag on the parent
to keep in step with the pages.

The same mark carries into the collapsed rail, where it is the only sign of
which section the page is in.

## Open and closed

`open` left out means **open exactly when the section contains the current
page**: the reader lands with the page they are on already in view, and every
other section folded. `:open="true"` or `:open="false"` overrides it.

That value is rendered, not booted into:

- `aria-expanded="true|false"` on the button, rebound by Alpine
  (`:aria-expanded`) after;
- `style="display: none"` on a closed group, which `x-show` takes over;
- the chevron, which follows `aria-expanded`.

Nothing jumps when Alpine starts, and without it the right section is open.

The open state lives in the component and is not kept across page loads. A
section the reader opened on one page is closed on the next unless it holds
that page. Remembering it is the host's to do, through `open`.

## The collapsed rail

`collapsedWhen` is the same Alpine expression the rail's nav items take, and
means the same: true when the rail is icons only.

**Collapsed, the pages are hidden and the section is a link to its first
page.** The button is hidden (`x-show="!(…)"`) and an `a` with the icon
shows in its place (`x-show="…"`, `x-cloak`). `href` overrides where it
goes.

Why a link and not a flyout:

- A disclosure has nothing to disclose beside an icon: there is no room for
  the pages, and expanding the rail on a click would fight the host's own
  collapse control.
- One press reaches the section from anywhere, the most common reason to
  press it.
- A flyout of the pages (a popover beside the icon) is a menu pattern with its
  own focus and pointer rules, and it would make the sub-nav need the
  package's script. It can come later, over the link, without changing the
  props.

The section's open state survives a collapse: expand the rail and each
section is as it was left.

With `collapsedWhen` and no link inside and no `href`, the component throws:
there is nothing for the collapsed icon to do.

## Choices and why

- **A button, not a link, when expanded.** The brief's disclosure pattern,
  and the only one a screen reader announces as expandable. A section that
  also has a page of its own puts that page first among its children.
- **`role="group"` named by the button.** The pages are announced as a group
  called "Reports", so a screen reader user knows where they are inside the
  rail without the guide line.
- **Inline Alpine.** One flag, one toggle, read by this component only. The
  line in CLAUDE.md for "a behaviour" is ten lines or a second user; this is
  neither.
- **The chevron points down when closed, up when open,** the accordion's
  convention, and sits at the row's end where a badge would.
- **`chip`** for parity with nav-item: the admin rail chips its icons, and a
  section without a chip in a chipped rail would be the odd one out.

## Accessibility

| | |
|---|---|
| Pattern | WAI-ARIA disclosure (show/hide), as in the "disclosure navigation menu" example |
| Role | `button` for the section; `group` for its pages, named by the button |
| State | `aria-expanded` on the button, rendered from PHP; `aria-controls` names the group |
| Current | `aria-current="page"` on the current page's nav-item only, never the section |
| Keyboard | Tab reaches the button; Enter and Space toggle. A closed group's links are `display: none`, so Tab skips them |
| Collapsed | The link has a `title` and an `sr-only` label, the section's name |
| Motion | The chevron's turn, `motion-safe`, `duration-popover-enter`. The pages appear without a transition |

## Do not

- **Do not nest a sub-nav in a sub-nav.** Two levels of indent in a rail
  224px wide leaves no room for a label, and three levels of navigation
  belong on the page, as tabs.
- **Do not set `active` on the sub-nav.** It has no such prop: the section is
  never the current page, one of its children is.
- **Do not use it for a page's own sections.** That is `ds::accordion` or
  `ds::tabs`.
- **Do not open every section by default.** The one that holds the page is
  open; the rest are folded so the rail stays short.
