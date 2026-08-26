# Specs

Each spec is the **contract** for one component: what it is made of, which
variants and states exist, and what a correct implementation must guarantee.
Not a style guide — the class strings live in the implementations, and the
values live in `tokens/`.

## How to read one

- **Anatomy** — the parts, and which token governs each.
- **Variants / sizes** — the complete set. If it is not listed, it does not
  exist; add it here first.
- **States** — every state a user can reach, including `disabled` and `loading`.
- **Accessibility** — the non-negotiables.
- **Do not** — the mistakes already made once, so they are not made again.

## Why specs at all

Components cannot be shared across Blade, React and Vue — the behaviour layer
is framework-bound and gets rewritten each time. The spec is what keeps three
independent implementations the same component rather than three lookalikes.

## Written so far

| Spec | Covers |
|---|---|
| [color.md](color.md) | Token selection and the accessibility rules |
| [button.md](button.md) | Button, icon button, the FAB |
| [input.md](input.md) | Input, select, textarea, and the two-layer construction |
| [panel.md](panel.md) | Bordered card, header, action, divided rows |
| [stat-tile.md](stat-tile.md) | Metric tile, delta, caption |
| [table.md](table.md) | Table, row, cell, alignment and density |
| [nav-item.md](nav-item.md) | Sidebar item, active state, collapsed state, chip |
| [sheet.md](sheet.md) | Mobile sheet, rows, the three tones |
| [modal.md](modal.md) | Modal, sizes, the focus contract |
| [drawer.md](drawer.md) | Edge panel, and which of the three overlays to reach for |
| [dropdown.md](dropdown.md) | Menu, items, placement, the keyboard contract |
| [select.md](select.md) | Listbox, and when the native select is still right |
| [combobox.md](combobox.md) | Filtering select, and why multiple is a mode of it |
| [switch.md](switch.md) | Switch, and what it is that a checkbox is not |
| [date-picker.md](date-picker.md) | Calendar, ranges, and why dates are strings |
| [file-upload.md](file-upload.md) | Drop zone, previews, and where the line is |
| [accordion.md](accordion.md) | Disclosure sections, and the one piece of state |
| [checkbox.md](checkbox.md) | Checkbox, indeterminate, the styled native input |
| [radio.md](radio.md) | Radio, and why the group is a fieldset |
| [tabs.md](tabs.md) | Tabs, panels, and tablist vs navigation |
| [tooltip.md](tooltip.md) | Tip, placement, and why it is never the only copy |
| [toast.md](toast.md) | Toast, the always-rendered region, timing |
| [avatar.md](avatar.md) | Image, initials, round and square |
| [breadcrumb.md](breadcrumb.md) | Path, separators, the current page |
| [empty-state.md](empty-state.md) | Nothing here, and what to say about it |
| [skeleton.md](skeleton.md) | Loading placeholder, and who announces it |
| [alert.md](alert.md) | Alert, tones, wash-not-tint, the live-region rule |
| [badge.md](badge.md) | Badge, three variants, five tones, the dot |
| [icon.md](icon.md) | Icon, sizes, the v1 name bridge, the a11y default |
| [bottom-nav.md](bottom-nav.md) | Bottom bar, items, the safe area |
| [pagination.md](pagination.md) | Both paginator views |

Sub-components are covered by their parent: `table-cell` and `table-row` in
table.md, `panel-row` in panel.md, `sheet-item` in sheet.md, `dropdown-item` in
dropdown.md, `bottom-nav-item` in bottom-nav.md, `breadcrumb-item` in
breadcrumb.md, `tab` and `tab-panel` in tabs.md, `radio-group` in radio.md, and
`toast-region` in toast.md.

**Every built component now has a spec.** Specs are written **alongside** their
implementation, not before it and not after — a speculative spec for a component
nobody has built yet is fiction, and a component that shipped without one is a
component whose reasons are already lost.
