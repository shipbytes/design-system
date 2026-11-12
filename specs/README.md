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
| [chart-series.md](chart-series.md) | The eight series colours, why they mean nothing, and the order that is the safety property |
| [button.md](button.md) | Button, icon button, the FAB |
| [input.md](input.md) | Input, select, textarea, the two-layer construction, and `reveal` |
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
| [progress.md](progress.md) | Bar and ring, indeterminate, the one custom property |
| [usage-meter.md](usage-meter.md) | Allowance, thresholds, segments, meter vs progressbar |
| [stepper.md](stepper.md) | Steps, statuses, the narrow-width collapse |
| [avatar-group.md](avatar-group.md) | Overlapping avatars, the ground ring, the spoken sentence |
| [alert.md](alert.md) | Alert, tones, wash-not-tint, the live-region rule |
| [badge.md](badge.md) | Badge, three variants, five tones, the dot |
| [icon.md](icon.md) | Icon, sizes, the v1 name bridge, the a11y default |
| [bottom-nav.md](bottom-nav.md) | Bottom bar, items, the safe area |
| [pagination.md](pagination.md) | Both paginator views |
| [otp-input.md](otp-input.md) | One box per character, one posted field, the `complete` event |
| [tag-input.md](tag-input.md) | Free-text chips, refusals that stay, separators |
| [number-field.md](number-field.md) | Native number input, step buttons, press-and-hold |
| [password-strength.md](password-strength.md) | The four-level hint and its heuristic |
| [copy-button.md](copy-button.md) | Copy, the in-place confirmation, the failure event |
| [rating.md](rating.md) | Stars over radios, "No rating", the readonly image |
| [chip-group.md](chip-group.md) | Filter chips, inputs or links, binding through the group |
| [radio-cards.md](radio-cards.md) | Card-shaped radios, columns, stable ids |
| [segmented-control.md](segmented-control.md) | Radios or links in a track, the glide directive, why no script is needed |
| [button-group.md](button-group.md) | Joined buttons, the split button, why it is a class |
| [toolbar.md](toolbar.md) | One Tab stop, roving focus, the hover highlight, pressed |
| [toggle-group.md](toggle-group.md) | Single and multiple toggles, what posts, inside a toolbar |
| [announcement-bar.md](announcement-bar.md) | Banner above the chrome, the pre-paint dismissal check, landmark not live region |
| [confirm-button.md](confirm-button.md) | Asking in place, the fail-safe confirm control, the width it grows by |
| [hold-button.md](hold-button.md) | Hold to confirm, the two-press path for keyboard and assistive technology |
| [confirm-popover.md](confirm-popover.md) | Anchored confirmation, type-to-confirm, why alertdialog |
| [popover.md](popover.md) | Anchored panel for anything interactive, placement, the focus contract |
| [hover-card.md](hover-card.md) | Preview on hover or focus, the open and close delays, never on touch |
| [context-menu.md](context-menu.md) | The dropdown's menu at the pointer, Shift+F10, the long press |
| [sub-nav.md](sub-nav.md) | A rail section holding its pages, the indent, the collapsed rail |
| [theme-switcher.md](theme-switcher.md) | Light, dark or system, the pre-paint script, the view transition |

Sub-components are covered by their parent: `accordion-item` in accordion.md,
`table-cell` and `table-row` in
table.md, `panel-row` in panel.md, `sheet-item` in sheet.md, `dropdown-item` in
dropdown.md, `bottom-nav-item` in bottom-nav.md, `breadcrumb-item` in
breadcrumb.md, `tab` and `tab-panel` in tabs.md, `step` in stepper.md, `radio-group` in radio.md,
`chip` in chip-group.md, `radio-card` in radio-cards.md, `segment` in
segmented-control.md, `toolbar-button` and `toolbar-separator` in toolbar.md,
`toggle` in toggle-group.md, and `toast-region` in toast.md.

**Every built component now has a spec.** Specs are written **alongside** their
implementation, not before it and not after — a speculative spec for a component
nobody has built yet is fiction, and a component that shipped without one is a
component whose reasons are already lost.
