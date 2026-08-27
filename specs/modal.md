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

| `size` | Width | When |
|---|---|---|
| `sm` | 24rem / 384px | A confirm. One sentence and two buttons. |
| `md` | 28rem / 448px | **Default.** A short form. |
| `lg` | 32rem / 512px | A form with a second column of help text. |
| `xl` | 36rem / 576px | The widest the source dashboard ever used. |
| `2xl` | 42rem / 672px | Content that is **wide** rather than long. |
| `3xl` | 48rem / 768px | A table preview, a diff, a side-by-side compare. |
| `4xl` | 56rem / 896px | A log excerpt, an image with its metadata beside it. |
| `full` | the container less its `p-4`, capped at 96rem | A near-full-screen reader. |

A closed set, mapped to literal class strings. That is not a style choice, it is
the scanner rule: `max-w-{$size}` built at runtime is invisible to Tailwind and
generates no rule at all. A map of literals is scannable text. **Any prop with a
finite set of values gets a map; only an open-ended one (icon's `size`) needs the
`@source inline` list.**

`sm` through `xl` are the sizes the source dashboard actually used, and they are
still the right answer for the things a dialog is usually for. The four above
them exist for the shape of content the dashboard never had in one: something
whose natural measure is horizontal. A table at 28rem is not a preview of a
table, it is a column of wrapped cells.

**Past roughly `3xl`, ask whether it wants to be a page.** The reason is the same
one that used to be written here as "there is no `full`": a dialog is a thing you
answer and leave, and something that needs 900px of horizontal room usually also
wants a URL, a back button, and somewhere to link to. The widths are here because
"preview this file" is a real case that a page would be too heavy for — not
because a bigger modal is a better modal. The height cap is the same hint from
the other direction: if the body scrolls far, it was a page.

`full` is `max-w-[96rem]`, not a viewport unit and not `max-w-none`.

The root is `fixed inset-0 p-4` and the panel is `w-full`, so 100% of the
container is *already* "the viewport, less the gutter" — the `p-4` is the margin,
and it stays right when the root is not the viewport, which is what a transformed
ancestor makes it. That is the same argument as `max-h-full`, and `max-w-none`
would have been enough on its own.

The ceiling is there because `max-w` only *caps*: below it, `w-full` still
supplies the container less the gutter, so nothing changes. Measured, the
uncapped version gives 1408px at a 1440 viewport and **2528px at 2560** — a
dialog wider than any content in the system, at a line length nobody reads. The
cap engages above roughly a 1568px viewport and is invisible below it:

| Viewport | `full` |
|---|---|
| 380 | 348 |
| 1440 | 1408 |
| 2560 | 1536 |

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


### `open` must be assignable

`open` is a **reference**, not a condition. The component does not just read it —
the close button, the backdrop and Escape all write to it:

```blade
@click="{{ $open }} = false"
```

```blade
{{-- Broken: opens, and then nothing closes it --}}
<x-ds::modal open="mode === 'confirm'">

{{-- Works --}}
<div x-data="{ show: { confirm: false, rename: false } }">
    <x-ds::modal open="show.confirm">
</div>
```

The broken form compiles to `mode === 'confirm' = false`. Reading the expression is fine,
so the modal **opens correctly**, and then every dismiss path silently does
nothing. HTTP 200, a clean server log, and one `Uncaught SyntaxError: Invalid
left-hand side in assignment` in a console nobody had open. It cost a consumer
half an hour, which is why it is a render-time exception now
(`Support\OpenState`) rather than a docblock alone.

## Do not

- **Nest a modal in a modal.** Two blocking layers give the reader no way to know
  what Escape means. Same rule as the sheet, for the same reason.
- **Open a modal and a sheet together.** They share `z-50` and the stacking order
  becomes source order, which is not a design.
- **Put a form's only validation errors in a modal that closes on submit.** The
  reader never sees them.
- **Use `dismissible="false"` without an explicit exit** in the footer.
- **Give `open` a comparison.** `open="mode === 'confirm'"` opens and then
  ignores the ✕, the backdrop and Escape. It has to be assignable — see above.
- **Reach for a modal to hold a workflow.** More than one decision belongs on a
  page. The height cap is a hint: if the body scrolls far, it was a page.
