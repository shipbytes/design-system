# Shipbytes Blade UI — documentation

115 Blade components for Laravel, plus two pagination views. Presentation
only: no state, no framework assumptions beyond Blade itself.

**New here?** Start with [Getting started](getting-started.md) — install, three
lines of CSS, the one line that everything depends on, and the script include.

## Components

Every page has a screenshot showing **light and dark side by side**, the full
prop table, copy-paste examples, and the mistakes worth avoiding.

### Forms

| | |
|---|---|
| [Button](components/button.md) | Does something, or goes somewhere |
| [Input](components/input.md) | Text field, textarea, native select |
| [OTP input](components/otp-input.md) | A one-time code, one box per character — needs Alpine and the script |
| [Tag input](components/tag-input.md) | Typed values kept as chips — needs Alpine and the script |
| [Number field](components/number-field.md) | A bounded number with step buttons — needs Alpine and the script |
| [Slider](components/slider.md) | A number, or a range of two, on a track — needs Alpine and the script |
| [Money input](components/money-input.md) | An amount, grouped for the locale as it is typed; posts a decimal string, never a float — needs Alpine and the script |
| [Phone input](components/phone-input.md) | A country from a searchable list and a number formatted as typed; posts E.164 — needs Alpine and the script |
| [Shortcut input](components/shortcut-input.md) | A keyboard shortcut set by pressing it; posts `mod+shift+k` — needs Alpine and the script |
| [Time picker](components/time-picker.md) | A time of day, typed or picked from a list; posts HH:MM — needs Alpine and the script |
| [Inline edit](components/inline-edit.md) | Text that turns into a field in place: rename a title, edit a cell — needs Alpine and the script |
| [Sortable list](components/sortable-list.md) | Rows put in order by drag, keyboard or buttons; posts the order — needs Alpine and the script |
| [Password strength](components/password-strength.md) | A strength hint for a new password, and `reveal` — needs Alpine and the script |
| [Copy button](components/copy-button.md) | Copies a value and confirms in place — needs Alpine and the script |
| [Swap button](components/swap-button.md) | A button whose face confirms what it did: Save to Saved, Follow to Following — needs Alpine and the script |
| [Confirm button](components/confirm-button.md) | A destructive button that asks in place first — needs Alpine and the script |
| [Hold button](components/hold-button.md) | Confirmed by holding it, or by pressing twice — needs Alpine and the script |
| [Rating](components/rating.md) | Stars to choose a score, or to show one |
| [Chip group](components/chip-group.md) | Pressable filter chips, one or many, or links |
| [Filter bar](components/filter-bar.md) | Search, controls, applied filters as chips, Clear all; folds on a phone |
| [Segmented control](components/segmented-control.md) | One of a few views or modes, side by side |
| [Button group](components/button-group.md) | Related buttons joined into one shape, the split button, and icon buttons that expand to their labels |
| [Toolbar](components/toolbar.md) | A row of tools that is one Tab stop — needs Alpine and the script |
| [Toggle group](components/toggle-group.md) | Buttons that stay pressed, one or many — needs Alpine and the script |
| [Theme switcher](components/theme-switcher.md) | Light, dark or system, remembered, with the pre-paint script — needs Alpine and the script |
| [Radio cards](components/radio-cards.md) | Card-shaped options to compare: plans, shipping |
| [Select](components/select.md) | Styled listbox — needs Alpine |
| [Combobox](components/combobox.md) | Filtering select, single or multiple — needs Alpine |
| [Switch](components/switch.md) | A setting that takes effect |
| [Date picker](components/date-picker.md) | A calendar, one date or a range — needs Alpine |
| [File upload](components/file-upload.md) | Drop zone with local previews — needs Alpine |
| [Checkbox](components/checkbox.md) | One independent yes/no |
| [Radio](components/radio.md) | One of a set, plus the group |

### Layout & content

| | |
|---|---|
| [Panel](components/panel.md) | Bordered card, with rows or free-form |
| [Table](components/table.md) | Rows of records, scrolling in its own box, with sortable headers |
| [Stat tile](components/stat-tile.md) | One number, with its change |
| [Accordion](components/accordion.md) | Sections that expand one at a time — needs Alpine |
| [Empty state](components/empty-state.md) | What a region says when it has nothing |
| [Skeleton](components/skeleton.md) | A picture of content that is still loading |
| [Progress](components/progress.md) | How far along a task is, as a bar or a ring |
| [Usage meter](components/usage-meter.md) | An allowance and what fills it |
| [Code block](components/code-block.md) | Code to read and copy, with line numbers and highlighted lines; bring your own highlighter |
| [Kbd](components/kbd.md) | A key on the keyboard, inline in text |
| [Expandable text](components/expandable-text.md) | Text held to a few lines with Read more — needs Alpine and the script |
| [Scroll area](components/scroll-area.md) | A bounded box that scrolls natively, with a quiet scrollbar and edge shadows |
| [Split](components/split.md) | Panes with a divider to drag between them, nested if need be — needs Alpine and the script |

### Data

| | |
|---|---|
| [Counter](components/counter.md) | A number that rolls its digits when it changes — the roll needs Alpine and the script |
| [Sparkline](components/sparkline.md) | A small trend with no axes: line, area or bars |
| [Gauge](components/gauge.md) | One reading against a range, as an arc, with thresholds |
| [Activity heatmap](components/activity-heatmap.md) | A count for every day, as a grid of shades |
| [Bar chart](components/bar-chart.md) | Values compared across categories: grouped or stacked, vertical or horizontal — the readout needs Alpine and the script |
| [Line chart](components/line-chart.md) | Values in order over time, up to eight series, with gaps — the readout needs Alpine and the script |
| [Donut chart](components/donut-chart.md) | The parts of one whole, with the total in the middle — the readout needs Alpine and the script |
| [Split bar](components/split-bar.md) | The parts of one whole as one bar, with room left if there is a total — the readout needs Alpine and the script |
| [Waffle chart](components/waffle-chart.md) | A share as cells of a 10 × 10 grid, one cell a percent — the readout needs Alpine and the script |
| [Activity rings](components/activity-rings.md) | Up to four goals as concentric rings, each against its own target — the readout needs Alpine and the script |
| [Slope chart](components/slope-chart.md) | A handful of items before and after, the change written on each — the readout needs Alpine and the script |
| [Funnel chart](components/funnel-chart.md) | Stages of a conversion, with the drop between each written out — the readout needs Alpine and the script |
| [Radar chart](components/radar-chart.md) | Up to four items scored across three to eight axes — the readout needs Alpine and the script |
| [Treemap](components/treemap.md) | The parts of a whole as nested rectangles, one level of groups — the readout needs Alpine and the script |
| [Timeline](components/timeline.md) | Events in order on a rail, with groups under sticky headings |

**Which chart?** Start from the question.

| The question | Use |
|---|---|
| One number, and is it changing? | [Counter](components/counter.md), in a stat tile with a [sparkline](components/sparkline.md) for the trend |
| One reading in a range, with bands (CPU, latency)? | [Gauge](components/gauge.md) |
| How busy was each day? | [Activity heatmap](components/activity-heatmap.md) |
| How do these categories compare? | [Bar chart](components/bar-chart.md), sorted; horizontal for long names |
| How did it move over time? | [Line chart](components/line-chart.md) |
| What is one whole made of? | [Split bar](components/split-bar.md) in a line, [donut chart](components/donut-chart.md) with the total in the middle, [waffle chart](components/waffle-chart.md) as a count of 100 |
| What share is one part? | [Waffle chart](components/waffle-chart.md) with one number, or a [progress](components/progress.md) bar |
| How far toward several goals? | [Activity rings](components/activity-rings.md) |
| What changed between two moments, item by item? | [Slope chart](components/slope-chart.md) |
| How many made it through each step? | [Funnel chart](components/funnel-chart.md) |
| How do a few items score across several qualities? | [Radar chart](components/radar-chart.md) for the overall shape; a grouped [bar chart](components/bar-chart.md) to compare exact scores |
| What is a whole made of, when it has many parts or groups of parts? | [Treemap](components/treemap.md) |
| How much of an allowance is used? | Not a chart: a [usage meter](components/usage-meter.md) |

### Navigation

| | |
|---|---|
| [Nav item](components/nav-item.md) | A row in the sidebar rail |
| [Sub-nav](components/sub-nav.md) | A rail section holding its pages — needs Alpine |
| [Bottom nav](components/bottom-nav.md) | The phone's primary navigation |
| [Tabs](components/tabs.md) | One row, several views |
| [Breadcrumb](components/breadcrumb.md) | Where this page sits |
| [Stepper](components/stepper.md) | Where someone is in an ordered flow |
| [Pagination](components/pagination.md) | Two Laravel paginator views, each with a Livewire variant |
| [Tree](components/tree.md) | Nested items: a file browser, a docs sidebar, a checkbox tree — needs Alpine and the script |
| [User menu](components/user-menu.md) | The account menu: avatar, who is signed in, your items, theme, a sign-out that POSTs — Alpine |
| [Team switcher](components/team-switcher.md) | Switch workspace or team, with a search when the list is long — needs Alpine and the script; a select or links without |

### Overlays

| | |
|---|---|
| [Modal](components/modal.md) | Blocks the page until it is answered — Alpine |
| [Drawer](components/drawer.md) | An edge panel to work in — Alpine |
| [Sheet](components/sheet.md) | The mobile form of either — Alpine |
| [Dropdown](components/dropdown.md) | A menu of actions — Alpine |
| [Popover](components/popover.md) | Anything else anchored to a button: fields, a form — needs Alpine and the script |
| [Confirm popover](components/confirm-popover.md) | A question anchored to its button, with optional type-to-confirm — needs Alpine and the script |
| [Hover card](components/hover-card.md) | A preview of a person or a link on hover or focus — needs Alpine and the script |
| [Context menu](components/context-menu.md) | The dropdown's menu at the pointer: right-click, long press, Shift+F10 — needs Alpine and the script |
| [Command palette](components/command-palette.md) | Actions and destinations, searched from a shortcut (Cmd+K) — needs Alpine and the script |
| [Tooltip](components/tooltip.md) | A short label on hover or focus — Alpine |
| [Toast](components/toast.md) | Something already happened |

### Marks

| | |
|---|---|
| [Icon](components/icon.md) | A Heroicon, sized by the system |
| [Icon swap](components/icon-swap.md) | Two icons that turn into each other with their button's state: menu and close, play and pause |
| [Avatar](components/avatar.md) | A person or a thing, at a glance |
| [Avatar group](components/avatar-group.md) | A few faces from a larger set, overlapping |
| [Badge](components/badge.md) | A small label that annotates |
| [Alert](components/alert.md) | Something about the page you are on |
| [Announcement bar](components/announcement-bar.md) | A banner above the app for everyone: maintenance, a trial, news; dismissal needs Alpine and the script |

## See everything at once

`dist/gallery.html` is a single self-contained page rendering **every** component
in both themes, with a token and type reference. Open it in a browser.

It is generated from the real components rather than drawn to look like them, so
if one is broken, the gallery is broken too.

## Which components need JavaScript

**No Livewire, ever.** The package requires `php`, `illuminate/support`,
`illuminate/view` and `blade-heroicons` — nothing else. Everything works in a
plain Blade view. The one opt-in exception is the pagination views' Livewire
variants, which emit `wire:click` for a component that uses `WithPagination`.

[Alpine](https://alpinejs.dev) — a single 15 kB script, not a framework — is
needed by thirty-nine components, and by eleven optional props:

| | Needs Alpine |
|---|---|
| `modal` `drawer` `sheet` `dropdown` `select` `combobox` `date-picker` `accordion` `file-upload` `tooltip` `user-menu` | **Always.** Their whole behaviour is state the browser has no markup for. |
| `otp-input` `tag-input` `number-field` `password-strength` `copy-button` `toolbar` `toggle-group` `theme-switcher` | **Always, and the package's script too.** Each renders its value from PHP and posts without JavaScript, but its behaviour is the script's. |
| `popover` `hover-card` `context-menu` | **Always, and the package's script too.** Without them the panel never opens. |
| `confirm-button` `hold-button` `confirm-popover` | **Always, and the package's script too.** Without them each renders its first face and does nothing, so no single press ever carries out the action. |
| `tree` | **Always, and the package's script too.** Without them it is drawn as PHP rendered it (open folders, the selection, every link reachable) and posts what it shows, but does not open, close or choose. |
| `expandable-text` | **Always, and the package's script too.** Without them the text is shown in full and no button appears. |
| `slider` `time-picker` `inline-edit` | **Always, and the package's script too.** Without them each is the native field it is drawn on and posts its value: a single slider works fully (its fill stays put) and a range's two thumbs can cross, the time picker is the browser's own time field, and the inline edit is a plain field inside its form. |
| `command-palette` | **Always, and the package's script too.** Without them it stays closed and does nothing; keep a visible route to everything in it. |
| `sortable-list` | **Always, and the package's script too.** Without them it is a plain list in the server's order, which posts that order, with no handles. |
| `split` | **Always, and the package's script too.** Without them the panes are fixed at their sizes and the dividers are plain lines. |
| `swap-button` | **Always, and the package's script too.** Without them it shows the face the server drew and is an ordinary button that still submits or links. |
| `team-switcher` | **Always, and the package's script too.** Without them it is a native select in a form with a submit button, or a list of links. |
| `money-input` `phone-input` `shortcut-input` | **Always, and the package's script too.** Without them each is a plain field that posts: the money input a text field posting what was typed, the phone input a select and a tel field posting `name[country]` and `name[number]` rather than E.164, the shortcut input a text field holding the string. |
| `icon-swap` | **Never.** It reads its button's state from CSS. |
| `scroll-area` | **Never.** It scrolls natively. With Alpine and the package's script it also draws its edge shadows in a browser that cannot from CSS. |
| `sub-nav` | **Only to open and close.** Without it the section holding the current page is open and the rest are closed, as the server rendered them. |
| `counter` | **Never**, unless its `value` is bound to Alpine state. With Alpine and the package's script its digits roll; without them it shows the number the server rendered, and a re-render changes it. Bound with `::value`, it needs both. |
| `bar-chart` `line-chart` `donut-chart` `split-bar` `waffle-chart` `activity-rings` `slope-chart` `funnel-chart` `radar-chart` `treemap` | **Never.** With Alpine and the package's script, a readout on hover and focus, every mark reachable by keyboard, and legend toggles; without them each is a static SVG with a generated text alternative, which is complete. A treemap's links are links either way. |
| `segmented-control` | **Never.** With Alpine and the package's script, its highlight slides; without them, the chosen segment draws its own face, and nothing throws. |
| `input` | Only for `reveal` and `clearable`, which also need the package's script, and for `expanding`'s Escape and `shortcut`; an expanding search opens and closes from CSS without them. |
| `kbd` | Only for `shortcut`, and then only to draw it for a Mac: without Alpine and the script it shows Ctrl + K. |
| `code-block` | Only for its copy button (on by default) and `expandable`, which also need the package's script. Without them the code shows and scrolls. |
| `filter-bar` | Only for `collapsible`, which also needs the package's script. |
| `panel` | Only for `collapsible`. Without it the body shows as `open` says. |
| `checkbox` | Only for `indeterminate`. Without it the box renders unchecked — which is what a screen reader announces, so the feature is absent rather than lying. |
| `nav-item` | Only for `collapsedWhen`. Omit it for a permanently expanded rail. |
| `toast` | Only for `dismiss`, which takes an expression that removes the toast. |
| `announcement-bar` | Only for `dismissible` and `dismissKey`, which also need the package's script. Without them the bar shows with no close control. |
| `pagination-livewire` `simple-pagination-livewire` | For the scroll back to the top on a page change. Livewire brings its own Alpine, so there is nothing to add. |
| Everything else | **Never.** |

The package also ships a small script of its own, the behaviours several
components share, which goes in the layout as `<x-ds::scripts />` above Alpine.
The twenty-four above that need it, and `input`'s `reveal` and `announcement-bar`'s
dismissal, say so at the top of
their pages. See [Add the script](getting-started.md#3-add-the-script).

`stat-tile` pushes its own count-up script to a `scripts` stack — plain
JavaScript, no framework. Without `@stack('scripts')` in your layout the tile
still renders the final value correctly.

## Docs, or specs?

Two sets of documents, for two questions.

| | Answers | Audience |
|---|---|---|
| **`docs/`** (here) | *How do I use this?* | Anyone building with the package |
| **[`specs/`](../specs)** | *Why does it work that way?* | Anyone changing the package, or porting it |

Every page here links to its spec. The specs carry the anatomy, the tokens
behind each part, the accessibility contract, and the reasoning — including the
bugs that produced each rule. Read one when a component surprises you, or before
you change it.

## Reporting something

If a component renders wrongly, the fastest useful bug report is a screenshot in
both themes and the Blade that produced it. Most visual bugs in this system's
history were invisible in the source and obvious in a picture.
