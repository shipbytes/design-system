# Shipbytes Blade UI — documentation

Seventy-three Blade components for Laravel, plus two pagination views. Presentation
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
| [Password strength](components/password-strength.md) | A strength hint for a new password, and `reveal` — needs Alpine and the script |
| [Copy button](components/copy-button.md) | Copies a value and confirms in place — needs Alpine and the script |
| [Confirm button](components/confirm-button.md) | A destructive button that asks in place first — needs Alpine and the script |
| [Hold button](components/hold-button.md) | Confirmed by holding it, or by pressing twice — needs Alpine and the script |
| [Rating](components/rating.md) | Stars to choose a score, or to show one |
| [Chip group](components/chip-group.md) | Pressable filter chips, one or many, or links |
| [Segmented control](components/segmented-control.md) | One of a few views or modes, side by side |
| [Button group](components/button-group.md) | Related buttons joined into one shape, and the split button |
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
| [Tooltip](components/tooltip.md) | A short label on hover or focus — Alpine |
| [Toast](components/toast.md) | Something already happened |

### Marks

| | |
|---|---|
| [Icon](components/icon.md) | A Heroicon, sized by the system |
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
needed by twenty-five components, and by five optional props:

| | Needs Alpine |
|---|---|
| `modal` `drawer` `sheet` `dropdown` `select` `combobox` `date-picker` `accordion` `file-upload` `tooltip` | **Always.** Their whole behaviour is state the browser has no markup for. |
| `otp-input` `tag-input` `number-field` `password-strength` `copy-button` `toolbar` `toggle-group` `theme-switcher` | **Always, and the package's script too.** Each renders its value from PHP and posts without JavaScript, but its behaviour is the script's. |
| `popover` `hover-card` `context-menu` | **Always, and the package's script too.** Without them the panel never opens. |
| `confirm-button` `hold-button` `confirm-popover` | **Always, and the package's script too.** Without them each renders its first face and does nothing, so no single press ever carries out the action. |
| `sub-nav` | **Only to open and close.** Without it the section holding the current page is open and the rest are closed, as the server rendered them. |
| `segmented-control` | **Never.** With Alpine and the package's script, its highlight slides; without them, the chosen segment draws its own face, and nothing throws. |
| `input` | Only for `reveal`, which also needs the package's script. |
| `checkbox` | Only for `indeterminate`. Without it the box renders unchecked — which is what a screen reader announces, so the feature is absent rather than lying. |
| `nav-item` | Only for `collapsedWhen`. Omit it for a permanently expanded rail. |
| `toast` | Only for `dismiss`, which takes an expression that removes the toast. |
| `announcement-bar` | Only for `dismissible` and `dismissKey`, which also need the package's script. Without them the bar shows with no close control. |
| `pagination-livewire` `simple-pagination-livewire` | For the scroll back to the top on a page change. Livewire brings its own Alpine, so there is nothing to add. |
| Everything else | **Never.** |

The package also ships a small script of its own, the behaviours several
components share, which goes in the layout as `<x-ds::scripts />` above Alpine.
The fourteen above that need it, and `input`'s `reveal` and `announcement-bar`'s
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
