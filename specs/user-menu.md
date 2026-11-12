# User menu

The app shell's account menu: a composition of ds::dropdown, ds::avatar,
ds::dropdown-item, ds::badge and, in its slot, ds::theme-switcher.

## Anatomy

```
┌─────────────────────────┐
│ (AL) Ada Lovelace    ⌄  │   ← trigger: button[data-ds-user-menu-trigger]
│      ada@example.com    │      avatar (decorative), name, meta, icon-swap
└─────────────────────────┘
   ┌───────────────────────┐
   │ (AL) Ada Lovelace  Pro│   ← header [id=…-who]: avatar, name, email, badge
   ├───────────────────────┤
   │ Profile               │   ← default slot; adjacent role=group children
   │ Settings              │      parted by a hairline
   ├───────────────────────┤
   │ Theme     [☀][☾][▣]   │   ← `theme` slot: theme-switcher variant=menu
   ├───────────────────────┤
   │ ⇥ Sign out            │   ← form[method=POST][data-ds-user-menu-sign-out]
   └───────────────────────┘      > dropdown-item as=button (type=submit)
```

- **Trigger**: a plain `button`, the dropdown's trigger. Full: `w-full`,
  `p-1.5`, a hover fill, an `sm` avatar, name and `meta ?? email` truncated,
  and a `chevron-down` ds::icon-swap (`rotate`) that turns on the trigger's
  `aria-expanded`. Compact: the avatar alone, `rounded-full`,
  `aria-label` = `name`.
- **Panel**: one `div[role=group]` labelled by the header, `w-64`, capped at
  `100vw - 1rem`.
- **Groups**: `*:[[role=group]+[role=group]]:` adds `mt-1 pt-1 border-t` to a
  group that follows a group; the host writes `role="group"` and a name.
- **Sign-out**: a real POST form. `_token` from `csrf_token()` (wrapped in
  `rescue()`, so a host with no session renders none), or the host's `csrf`
  slot instead.

## States

| State | Treatment |
|---|---|
| closed, open | The dropdown's: `x-show`, the menu transition, the chevron turned |
| hover, focus-visible | Trigger: `bg-fg/5`; the ring with `outline-offset-2`. Items: the dropdown's |
| no `signOutAction` | No form, no sign-out item |

## Phones

The brief asked for a ds::sheet below `sm`, rendered once. ds::dropdown and
ds::sheet are separate overlays with their own markup, state and focus code;
switching one set of items between them by breakpoint would mean rendering the
items twice, and the items hold a form, which must exist once. So it stays a
dropdown on phones, its panel capped to the viewport and placed by x-ds-anchor,
which flips and shifts it on screen.

## Accessibility

| | |
|---|---|
| Trigger | `button`, `aria-haspopup="menu"`, `aria-expanded` (the dropdown's) |
| Name | Full: its text, the name and line (the avatar is aria-hidden). Compact: `aria-label` = `name` |
| Menu | `role=menu`; items `menuitem`; the theme row `menuitemradio` + `aria-checked` |
| Group | Named by the header (`aria-labelledby`): name, email, plan |
| Keyboard | The dropdown's: arrows over every `[role^=menuitem]` not `aria-disabled`, wrapping; Home, End; Escape back to the trigger; Tab closes |
| Sign-out | A submit button in a POST form |

## Do not

- **Do not sign out with a link.** A GET is followed by prefetchers and
  crawlers.
- **Do not render the items twice** for a second presentation.
- **Do not nest menus.**
- **Do not put fields in it.** Sign-out is a form with no fields; a dropdown
  discards what someone typed on Escape.
