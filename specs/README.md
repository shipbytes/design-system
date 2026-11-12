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
| [input.md](input.md) | Input, select, textarea, the two-layer construction, `reveal` and `clearable` |
| [panel.md](panel.md) | Bordered card, header, action, divided rows, `collapsible` |
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
| [counter.md](counter.md) | The digit roll, its two routes, the hidden text, and how it relates to stat-tile |
| [sparkline.md](sparkline.md) | One viewBox stretched, non-scaling strokes, gaps, the generated summary |
| [gauge.md](gauge.md) | Arcs by dash, bands and their words, meter values past the range |
| [activity-heatmap.md](activity-heatmap.md) | Weeks as rows turned by CSS, the scroll that starts at the end, the one-hue ramp |
| [bar-chart.md](bar-chart.md) | Percent x and px y so text never scales, labels thinned by container query, the keyboard model |
| [line-chart.md](line-chart.md) | A nested stretched plot, the monotone curve, gaps, end labels and the legend rule |
| [donut-chart.md](donut-chart.md) | Arcs by dash, the fold into Other, largest-remainder shares, when not to use one |
| [split-bar.md](split-bar.md) | Flex-basis segments with a least width, the rest as track, inline names that fall back by container query |
| [waffle-chart.md](waffle-chart.md) | Cells by largest remainder, one image not a hundred cells, a mark drawn in pieces, reading order and rtl |
| [activity-rings.md](activity-rings.md) | Dashes shortened by their round caps, rings narrowed to leave a hole, a goal passed said not drawn |
| [slope-chart.md](slope-chart.md) | Two axes in the bar chart's frame, labels spread apart greedily, the change written on every label |
| [funnel-chart.md](funnel-chart.md) | Centred bars on one hue's ramp, the drop written between stages, values inside or beside by container query |
| [radar-chart.md](radar-chart.md) | Polygons from polar points, a marker and a dash per series, names anchored by angle, the table on by default |
| [treemap.md](treemap.md) | The squarified layout, tiles that clip their own labels, ink chosen for contrast, links that stay links |
| [timeline.md](timeline.md) | Timeline, timeline-item and timeline-group: the rail each item draws, time elements, sticky groups |
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
| [slider.md](slider.md) | Native range inputs, one or two on a track; the thumb, the fill, thumbs that do not cross; a histogram whose lit bars and count agree |
| [money-input.md](money-input.md) | Amounts as digit strings in PHP and JS, locale grouping with a stable caret, the moving name, minor units |
| [phone-input.md](phone-input.md) | A select as the country store, the popover picker, masks, the trunk 0, E.164, a length check that is not libphonenumber |
| [shortcut-input.md](shortcut-input.md) | Recording on keydown, refusals and conflicts, Tab never taken, the change event, the platform drawing |
| [mention-input.md](mention-input.md) | A textarea combobox only while a trigger is typed, the list under the trigger by a mirror, Undo-able inserts, ids that stop posting when broken |
| [color-picker.md](color-picker.md) | HSV held, sliders of our own, forgiving parsing shared with PHP, contrast cut not rounded, presets that post nothing, the native fallback |
| [time-picker.md](time-picker.md) | A native time field the script turns into a combobox; forgiving typing that never changes the time |
| [inline-edit.md](inline-edit.md) | Read state as a button, a field in the same box, save and fail as attributes the host sets |
| [command-palette.md](command-palette.md) | The modal's layer, a combobox that keeps focus, ranking by CSS order, options that change under it |
| [sortable-list.md](sortable-list.md) | Pointer drag with capture and FLIP, the keyboard pick-up, the buttons for WCAG 2.5.7, what survives a morph |
| [split.md](split.md) | Sizes as CSS variables for the first paint, the window splitter pattern, which pane a divider controls |
| [password-strength.md](password-strength.md) | The four-level hint and its heuristic |
| [copy-button.md](copy-button.md) | Copy, the in-place confirmation, the failure event |
| [swap-button.md](swap-button.md) | A face that confirms in place, the swap recipe shared with copy, the toggle and its fixed name |
| [icon-swap.md](icon-swap.md) | Two icons, or three bars, turned by the parent's state through one custom property, no script |
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
| [user-menu.md](user-menu.md) | The account menu on the dropdown, the POST sign-out, the theme row, why it stays a dropdown on phones |
| [team-switcher.md](team-switcher.md) | A popover listbox over a no-script select or links, ranked search, the change event x-model can read |
| [tree.md](tree.md) | Navigation, single and checkbox trees, structure counted in PHP, the keyboard model |
| [filter-bar.md](filter-bar.md) | Search, controls and chips as layout, the collapsible fold rendered once, filter-chip |
| [code-block.md](code-block.md) | Chrome without a highlighter, the token colours, the indentation trap, lines that copy |
| [scroll-area.md](scroll-area.md) | Native scrolling, scroll-driven edge shadows with a script fallback, when the region is a Tab stop |
| [kbd.md](kbd.md) | A key sized by its text, and combinations |
| [expandable-text.md](expandable-text.md) | A measured clamp that fails open, the fade, the button that moves no focus |

Sub-components are covered by their parent: `accordion-item` in accordion.md,
`table-cell` and `table-row` in
table.md, `panel-row` in panel.md, `sheet-item` in sheet.md, `dropdown-item` in
dropdown.md, `bottom-nav-item` in bottom-nav.md, `breadcrumb-item` in
breadcrumb.md, `tab` and `tab-panel` in tabs.md, `step` in stepper.md, `radio-group` in radio.md,
`chip` in chip-group.md, `radio-card` in radio-cards.md, `segment` in
segmented-control.md, `toolbar-button` and `toolbar-separator` in toolbar.md,
`toggle` in toggle-group.md, `tree-item` in tree.md, `filter-chip` in
filter-bar.md, and `toast-region` in toast.md.

**Every built component now has a spec.** Specs are written **alongside** their
implementation, not before it and not after — a speculative spec for a component
nobody has built yet is fiction, and a component that shipped without one is a
component whose reasons are already lost.
