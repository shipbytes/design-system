# File upload

A styled file field with drag-and-drop and local previews. It does **not**
upload anything.

## Where the line is

The component owns the field, the drop zone, the chosen-file list and the
thumbnails. It does not own a transport.

**Progress bars need somewhere to upload to**, and the moment a component knows
that, it has a backend contract — which is the one thing nothing else in this
system has. Livewire already solves the transport with `wire:model`; a plain form
POST already solves it with a `<form>`. Neither needs this component's help.

## It is a real `<input type="file">`

Styled, not replaced. The input is stretched over the whole drop zone at zero
opacity, so:

- the entire area is the real control — one focus ring, one click target
- drag-and-drop lands on the element that actually owns the files
- a plain form posts it, and `wire:model` binds it, with nothing extra

A button that opens a hidden input loses the browser's own keyboard behaviour and
has to reimplement the drop target, and both failures are invisible in a normal
browser.

## Anatomy

```
┌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌┐
│            ⬆                  │
│  Choose files or drag them    │  ← dashed, like the empty state:
│  images, PDF · up to 5 MB     │    something belongs here and does not exist yet
└╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌┘
┌──────────────────────────────┐
│ [img] badge.png       32 KB ✕│  ← the list, once files are chosen
└──────────────────────────────┘
```

The hint is written for a **person**: `accept="image/*,.pdf"` becomes
"images, PDF". A machine string in the interface is the interface leaking.

## Two things that are easy to get wrong

**Removing a file must rebuild the input's `FileList`.** A `FileList` is
read-only and a `DataTransfer` is the only way to construct one. Without that,
the chip disappears and **the file is still submitted** — which is worse than
having no remove control at all, because the reader believes they removed it.

**Drag state counts, it does not toggle.** Dragging over a *child* of the zone
fires `dragleave` on the parent, so a boolean flickers the highlight off and on
as the pointer crosses the label. Increment on enter, decrement on leave.

## `maxSize` is a courtesy, not a control

Checked in the browser, for the reader's benefit: it saves them a failed upload.
It is **not** security, and the spec says so where someone will read it —
validate on the server as well, always.

A file dropped for being too large is announced in a `role="alert"`. Silently
discarding it means the reader assumes it uploaded.

## Previews are local

`URL.createObjectURL` on the `File`, for images only. Nothing leaves the browser.
`preview="false"` turns it off for a field where thumbnails are noise.

## Accessibility

- A real `<label for>`, and the input keeps its own focus ring.
- The file list is `aria-live="polite"` — it changes in response to something the
  reader did, while their attention is on a dialog that just closed.
- Each remove button names its file, not just "Remove".
- Rejected files are `role="alert"`, because the reader needs that now.
- Thumbnails are `alt=""`: the filename beside them is the name.

## Do not

- **Do not use it as an upload widget.** It has no transport. See the top.
- **Do not rely on `accept` or `maxSize` for validation.** Both are hints to the
  file dialog and can be bypassed by dragging.
- **Do not use it for a single avatar where a crop is expected.** Cropping is a
  different component that does not exist.
- **Do not forget `[]` on the name when `multiple`.** PHP takes the last file only,
  and nothing reports it.
