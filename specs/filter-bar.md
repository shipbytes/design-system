# Filter bar

The row above a list that narrows it: a search, a few controls, the filters
applied as chips, and a way to clear them. Covers `filter-bar` and
`filter-chip`, and the `clearable` field it usually holds (see
[input.md](input.md)).

## It is layout, not state

The bar owns nothing. The controls are the host's own fields, posting their own
names; the chips and the count are what the host says is applied. That is how
it works the same as a GET form (the query string is the state) and as a
Livewire component (the properties are), and why there is no "saved views"
here: what a filter means is the application's.

## Anatomy

```
┌───────────────────────────────────────────────────────────────────────┐
│ [🔍 Search      ✕]  ⚲ Filters (2)  [Status ▾] [Size ▾]   [Export] │ ← row
│ Status: Open ✕   Owner: Ada ✕   Clear all                          │ ← applied
└───────────────────────────────────────────────────────────────────────┘
  search slot        summary        default slot             actions slot
```

The root is `role="search"`, named by `label`: a landmark a reader can jump to.
The applied chips are a `role="group"` named by `appliedLabel`. `active` is the
count the summary and the reset read; at 0 the reset is not drawn, because
"Clear all" with nothing to clear is a dead control.

## Collapsible

Below `md` a row of controls does not fit, and wrapping them pushes the list
down the screen. `collapsible` folds them behind a "Filters (n)" button that
opens them in a popover (dsPopover and x-ds-anchor, as the popover recipe). At
`md` and up the same controls are a plain row again, and the button is gone.

The controls are rendered **once**: one panel that is a popover below `md` and
`display: contents` above it, by `md:` classes that beat the popover's inline
style. Rendering them twice, once per layout, would post every field twice and
give two elements the same `id`.

The panel is `role="group"`, not a dialog: it holds form fields the reader
moves through and leaves, not a task that must be finished.

Before Alpine starts, a phone sees the button and not the controls (they are
`x-show`'s to show), and a desktop sees the row. Without the script the button
does nothing on a phone; the search and the chips still work.

## Filter chip

A chip says one applied filter and removes it. `label` is the field ("Status"),
`value` the setting ("Open"). The remove control is a link with `removeHref`
(the URL without that filter, for a GET form) or a button that dispatches
`remove` with `{ label, value }`, bubbling, for Livewire or Alpine.

## Accessibility

- `role="search"` with a name; the applied chips are a named group.
- The count is said as "2 applied", not a bare number.
- Each chip's remove control is named for what it removes: "Remove filter
  Status: Open". An unnamed ✕ is the commonest failure of this pattern.
- Collapsible: the trigger is a real button with `aria-expanded` and
  `aria-controls`; opening moves focus into the panel, Escape closes it and
  returns focus, and focus leaving it closes it (dsPopover).
- Removing a chip is the host's to follow: a full reload, or Livewire's
  re-render. Where focus goes then is the host's; the docs recipe returns it to
  the bar.

## Do not

- **Do not put more than four controls in the row.** Past that, a filter
  drawer (`ds::drawer`) with a form is easier to use than a wrapping row.
- **Do not hide the search in the collapsed panel.** It is the one control
  everyone uses; it stays in the row.
- **Do not show chips for defaults.** A chip says what the reader changed.
- **Do not filter on every keystroke without a debounce.** `wire:model.live`
  on the search re-renders the list per letter; use `.debounce`.
