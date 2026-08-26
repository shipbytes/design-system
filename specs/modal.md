# Modal

A surface that blocks the page behind it until the reader deals with it. The
desktop form of what [sheet.md](sheet.md) does on a phone.

## Modal or sheet

They are the same idea at two widths, and the choice is not taste:

| | Modal | Sheet |
|---|---|---|
| Enters from | the centre, scaled up | the bottom edge |
| Blocks the page | yes | yes |
| Shadow | `overlay` | `overlay` |
| Below `lg` | fine for a confirmation | the right answer for a menu |

A **confirmation** is a modal at every width — it is one sentence and two
buttons, and pushing that up from the bottom edge of a phone makes a yes/no
question look like a navigation surface. A **list of choices** below `lg` is a
sheet, because a menu wants the full width and the thumb reach.

The two never appear at once, which is why they share `z-50`.

## Anatomy

```
┌─────────────────────────────────┐
│ Delete report?              ✕   │  ← title (18/28) + close
│ This cannot be undone.          │  ← description (14/24, fg-muted)
├─────────────────────────────────┤
│                                 │
│  slot — THIS is what scrolls    │
│                                 │
├─────────────────────────────────┤
│              Cancel  [ Delete ] │  ← footer slot, right-aligned
└─────────────────────────────────┘
```

| Part | Token | Why |
|---|---|---|
| Backdrop | `scrim` | Stays dark in both themes. Derived from `fg` it would go white in dark, lighting the page up instead of pushing it back. |
| Panel | `surface` | Paints its own ground. It sits over arbitrary content and cannot inherit one. |
| Corner | `radius-panel` | 12px. The token's own description reads "panels, modals". |
| Elevation | `shadow-overlay` | The scale is the claim: `float` hovers over the page, `overlay` blocks it. A modal is the only component that gets `overlay` besides the sheet. |
| Divider | `divider` | Footer rule only. The header has none — the title and the body are one thought. |

**The body scrolls, not the panel.** The header keeps naming the dialog and the
footer keeps its actions reachable while long content moves underneath. A panel
that scrolls as a single block loses both at exactly the moment a long modal
needs them.

Height is capped at `calc(100dvh - 2rem)`. `dvh` and not `vh`: on a phone with a
visible URL bar `100vh` is taller than the screen, and the footer buttons land
below the fold — the two buttons the modal exists to offer.

## Sizes

`sm` | `md` | `lg` | `xl` — `max-w-sm` through `max-w-xl`. A closed set, mapped
to literal class strings.

That is not a style choice, it is the scanner rule: `max-w-{$size}` built at
runtime is invisible to Tailwind and generates no rule at all. A map of literals
is scannable text. **Any prop with a finite set of values gets a map; only an
open-ended one (icon's `size`) needs the `@source inline` list.**

There is no `full`. A modal that fills the viewport is a page, and a page should
have a URL.

## State

The component owns none. `open` is an **Alpine expression the host declares**,
exactly as the sheet takes one:

```blade
<div x-data="{ confirmOpen: false }">
    <x-ds::button @click="confirmOpen = true">Delete report</x-ds::button>

    <x-ds::modal open="confirmOpen" title="Delete report?"
                 description="This cannot be undone." size="sm">
        The report and its exports are removed immediately.

        <x-slot:footer>
            <x-ds::button variant="ghost" @click="confirmOpen = false">Cancel</x-ds::button>
            <x-ds::button variant="danger">Delete</x-ds::button>
        </x-slot:footer>
    </x-ds::modal>
</div>
```

This is what keeps the same markup usable from plain Blade, a Livewire component
or a Volt page: the host already has somewhere to keep that boolean, and the
design system has no business owning it.

## `dismissible`

Default `true` — backdrop click, Escape, and a close button.

Turn it off for a modal the reader must answer. A destructive confirm whose
backdrop dismisses it **gets dismissed by accident**, and an accidental dismissal
reads as "cancelled" — which is the safe outcome exactly until it is not, when
the reader assumes the opposite happened.

With `dismissible="false"` the footer must offer a way out. A modal with no exit
is a trap, and the component cannot check that for you.

## Accessibility

The non-negotiables, all of them implemented rather than documented as intent:

- `role="dialog"` + `aria-modal="true"`.
- **The visible title is the accessible name.** `aria-labelledby` pointing at the
  rendered `<h2>`, never `aria-label`. An `aria-label` is a second copy of the
  title that nobody can see and nobody updates when the title changes.
- `aria-describedby` on the description, when there is one.
- **Focus moves into the panel on open** — to `[autofocus]` if present, otherwise
  the panel itself, which carries `tabindex="-1"` so it can hold focus when it
  contains no control.
- **Focus returns to the trigger on close.** Without it, dismissing a dialog drops
  the reader at the top of the document with no idea where they were.
- **Tab is trapped** inside the panel, cycling both directions.
- Escape closes, when `dismissible`.
- The page behind does not scroll while it is open.

### Why the focus trap is written out longhand

`x-trap` from `@alpinejs/focus` is the usual answer and it is not used here.

**Alpine skips a directive it has no handler for.** In a host that did not install
the plugin, `x-trap` is not an error — it is nothing at all. The modal looks
completely correct, and `aria-modal="true"` becomes a claim a screen reader
believes and the DOM does not honour. That is the exact shape of every entry in
this repo's "traps that have already bitten" list, and it would have been the
sixth. Ten lines inline depend on nothing.

## Do not

- **Nest a modal in a modal.** Two blocking layers give the reader no way to know
  what Escape means. Same rule as the sheet, for the same reason.
- **Open a modal and a sheet together.** They share `z-50` and the stacking order
  becomes source order, which is not a design.
- **Put a form's only validation errors in a modal that closes on submit.** The
  reader never sees them.
- **Use `dismissible="false"` without an explicit exit** in the footer.
- **Reach for a modal to hold a workflow.** More than one decision belongs on a
  page. The height cap is a hint: if the body scrolls far, it was a page.
