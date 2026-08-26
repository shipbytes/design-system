# Shipbytes Blade UI — documentation

Forty-one Blade components for Laravel, plus two pagination views. Presentation
only: no state, no framework assumptions beyond Blade itself.

**New here?** Start with [Getting started](getting-started.md) — install, three
lines of CSS, and the one line that everything depends on.

## Components

Every page has a screenshot showing **light and dark side by side**, the full
prop table, copy-paste examples, and the mistakes worth avoiding.

### Forms

| | |
|---|---|
| [Button](components/button.md) | Does something, or goes somewhere |
| [Input](components/input.md) | Text field, textarea, native select |
| [Select](components/select.md) | Styled listbox — needs Alpine |
| [Combobox](components/combobox.md) | Filtering select, single or multiple — needs Alpine |
| [Switch](components/switch.md) | A setting that takes effect |
| [Date picker](components/date-picker.md) | A calendar, one date or a range — needs Alpine |
| [File upload](components/file-upload.md) | Drop zone with local previews |
| [Checkbox](components/checkbox.md) | One independent yes/no |
| [Radio](components/radio.md) | One of a set, plus the group |

### Layout & content

| | |
|---|---|
| [Panel](components/panel.md) | Bordered card, with rows or free-form |
| [Table](components/table.md) | Rows of records, scrolling in its own box |
| [Stat tile](components/stat-tile.md) | One number, with its change |
| [Accordion](components/accordion.md) | Sections that expand one at a time — needs Alpine |
| [Empty state](components/empty-state.md) | What a region says when it has nothing |
| [Skeleton](components/skeleton.md) | A picture of content that is still loading |

### Navigation

| | |
|---|---|
| [Nav item](components/nav-item.md) | A row in the sidebar rail |
| [Bottom nav](components/bottom-nav.md) | The phone's primary navigation |
| [Tabs](components/tabs.md) | One row, several views |
| [Breadcrumb](components/breadcrumb.md) | Where this page sits |
| [Pagination](components/pagination.md) | Two Laravel paginator views |

### Overlays

| | |
|---|---|
| [Modal](components/modal.md) | Blocks the page until it is answered — Alpine |
| [Drawer](components/drawer.md) | An edge panel to work in — Alpine |
| [Sheet](components/sheet.md) | The mobile form of either — Alpine |
| [Dropdown](components/dropdown.md) | A menu of actions — Alpine |
| [Tooltip](components/tooltip.md) | A short label on hover or focus — Alpine |
| [Toast](components/toast.md) | Something already happened |

### Marks

| | |
|---|---|
| [Icon](components/icon.md) | A Heroicon, sized by the system |
| [Avatar](components/avatar.md) | A person or a thing, at a glance |
| [Badge](components/badge.md) | A small label that annotates |
| [Alert](components/alert.md) | Something about the page you are on |

## See everything at once

`dist/gallery.html` is a single self-contained page rendering **every** component
in both themes, with a token and type reference. Open it in a browser.

It is generated from the real components rather than drawn to look like them, so
if one is broken, the gallery is broken too.

## Which components need JavaScript

**No Livewire, ever.** The package requires `php`, `illuminate/support`,
`illuminate/view` and `blade-heroicons` — nothing else. Everything works in a
plain Blade view.

[Alpine](https://alpinejs.dev) — a single 15 kB script, not a framework — is
needed by six components, and by four optional props:

| | Needs Alpine |
|---|---|
| `modal` `drawer` `sheet` `dropdown` `select` `combobox` `date-picker` `accordion` `file-upload` `tooltip` | **Always.** Their whole behaviour is state the browser has no markup for. |
| `checkbox` | Only for `indeterminate`. Without it the box renders unchecked — which is what a screen reader announces, so the feature is absent rather than lying. |
| `nav-item` | Only for `collapsedWhen`. Omit it for a permanently expanded rail. |
| `toast` | Only for `dismiss`, which takes an expression that removes the toast. |
| Everything else | **Never.** |

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
