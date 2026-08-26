# Select

A styled single-choice field. A listbox, not a `<select>`.

## When to use the native one instead

There are **two** selects in this system, and the native one is still there:

```blade
<x-ds::input as="select" name="plan" label="Plan">…</x-ds::input>
```

| | `x-ds::select` (this) | `x-ds::input as="select"` |
|---|---|---|
| Renders | a listbox | the OS control |
| Styling | matches the system exactly | the browser's, on the popup |
| Needs JS | yes | no |
| On a phone | a popup list | the native wheel/sheet |
| Long lists | scrolls in a 240px box | the OS handles it |

**Prefer the native one on anything phone-first, and for long or unfamiliar
lists.** The OS control gets typeahead, the platform's own scrolling, and an
interaction the reader already knows, all for free. This component exists because
the popup of a native `<select>` cannot be styled at all, and a dashboard where
one control looks foreign is the reason it was asked for — not because the native
one is worse.

## Anatomy

```
Plan                          ← label
┌──────────────────────────┐
│ Pro                   ⌄  │  ← trigger: shows the SELECTED LABEL
└──────────────────────────┘
┌──────────────────────────┐
│ Free                     │
│ Pro                  ✓   │  ← selected: tick AND weight
│ Team                     │
└──────────────────────────┘
```

The trigger shares the [input](input.md) geometry — same padding calc, same
`calc(step - 1px)` border absorption — so a select and a text field on the same
row line up exactly.

The listbox is `shadow-float` and `max-h-60`: it hovers, it does not block, and a
long list scrolls inside itself rather than running off the page.

## `options` is an array, not a slot

```blade
<x-ds::select name="plan" label="Plan" :options="['free' => 'Free', 'pro' => 'Pro']" value="pro" />
```

Slot children would be the more Blade-like API, and it does not work: **the
trigger has to render the selected option's label**, which means the component
must be able to look a value up. A slot is opaque markup — the component would
have to parse its own children to find out what "pro" is called.

## What holds state, and what does not

This is the only component in the system that holds anything, so it is worth
being exact.

- **The form value belongs to the host.** It arrives as `value` and leaves through
  a hidden `<input name>`, so this posts in a plain form exactly like a
  `<select>`, with nothing needed on the receiving end.
- **Alpine holds the mirror** the trigger reads, and the open flag. UI state.

A Livewire host binds the hidden input and never touches the rest.

## Keyboard

`role="listbox"` is a promise, the same way `role="menu"` is — see
[dropdown.md](dropdown.md#accessibility).

- **ArrowDown opens a closed listbox.** Without it the only way in is Enter, which
  is not what a select does and not what anyone tries.
- Arrows move; Home and End jump to the ends.
- Enter and Space choose.
- Escape closes; Tab closes and moves on.
- **Focus enters the SELECTED option**, not the first one. Opening a list of forty
  countries at the top when "Zambia" is chosen loses the reader's place — this is
  the detail that separates a listbox from a menu.
- Focus returns to the trigger on close.

Roving `tabindex`: every option is `-1` and the listbox moves focus itself.

## Accessibility

- The trigger is `role="combobox"` + `aria-haspopup="listbox"` + a live
  `aria-expanded` + `aria-controls`.
- Named by `aria-labelledby` pointing at the visible label *and* the trigger, so
  the announcement is "Plan, Pro, combobox".
- Options carry `aria-selected`, kept live by Alpine.
- `error` sets `aria-invalid` and wires `aria-describedby`; `help` is replaced by
  `error`, never stacked — same rule as [input.md](input.md).
- The selected option shows **a tick as well as a weight change.** Weight alone is
  not a reliable signal when the reader is comparing two rows.

## Do not

- **Do not use it for actions.** A menu of verbs is a [dropdown](dropdown.md);
  this holds values and posts them.
- **Do not use it for multi-select.** There is no multi-select variant, and adding
  one to this shape means the trigger has to summarise an arbitrary set. Use
  checkboxes, or build a distinct component.
- **Do not put more than about fifteen options in it.** There is no typeahead and
  no search. Beyond that the reader needs a combobox with filtering — which is a
  different component that does not exist yet.
- **Do not omit `name`.** The hidden input is the only reason this submits.
- **Do not reach for it on a phone-first screen.** See the top of this file.
