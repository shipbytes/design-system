# Avatar upload

A profile photo or a logo: the current avatar, Change and Remove, and a crop
dialog that turns whatever was chosen into a square of a known size.

## What it is, and what it is not

The component **chooses and crops**; it does not upload. There is no `fetch`
and no XHR in it. The cropped photo goes into the real
`<input type="file" name>`, so the host's form posts it and Livewire's
`wire:model` uploads it, as either would any file. The photo is read only in
the browser, as an object URL and a bitmap; nothing leaves the page except
through the host's own submit.

Host-driven state, `uploading`, `progress` and `error`, is drawn by the
component: a `ds::progress` circle over the avatar, an error under the
buttons.

## Anatomy

```
 ┌────┐  Photo
 │ AL │  [📷 Change]  Remove
 └────┘  JPEG, PNG or WebP, up to 2 MB.

 Crop photo                              ✕
 ┌──────────────────────────────┐
 │░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│ ← scrim outside the crop
 │░░░░░░░╭──────────────╮░░░░░░░│
 │░░░░░░░│    photo     │░░░░░░░│ ← the mask: circle or square, 80% of the stage
 │░░░░░░░╰──────────────╯░░░░░░░│
 └──────────────────────────────┘
 Drag the photo, or use the arrow keys…
 Zoom ───●────────── 140%     [⟳ Rotate]
                       Cancel   [Apply]
```

| Part | Token | Why |
|---|---|---|
| Avatar | `ds::avatar`, `lg` (48px) or `xl` (64px) | The avatar the rest of the app shows, so the preview is the real thing. `xl` was added to `ds::avatar` for this. |
| Change | the secondary button's recipe | The main action. |
| Remove | quiet text button | Destructive but undoable until the form posts. |
| Stage | `surface-sunken` | The photo's ground, where the mask's scrim reads as dimming. |
| Outside the crop | `scrim` (as a 100vmax shadow of the mask) | The page's own modal scrim colour: "not this part". |
| Mask edge | a 2px white ring at 90% | Visible on dark and light photos alike; it sits on the photo, not the page. |
| Dragging a file over the avatar | `focus-ring` outline | "This will take it". |
| Uploading | `surface/80` over the avatar, `ds::progress` circle `sm` | The host's upload; a spinner when `progress` is null. |

The dialog is **`ds::modal`** itself (`size="sm"`): its focus trap, the focus
back out, Escape, the backdrop and the scroll lock. The component owns its
open state as `dsCropping`, which is assignable, as the modal requires.

## The flow, with the script

1. **Choose**: Change opens a nameless picker input; or drop a file on the
   avatar; or paste an image while focus is anywhere in the component.
2. **Check**: against `accept` (MIME types, `type/*` and `.ext`, as a file
   input reads it) and `maxSize`. A failure is said under the buttons
   ("Choose a JPEG, PNG or WebP image.", "Choose an image of 2 MB or less.",
   "That file could not be read as an image.") in a polite live region, and
   nothing opens.
3. **Crop**: the photo is drawn to cover the mask at 100% and can never be
   moved or zoomed so the mask shows past its edge. Drag moves it (Pointer
   Events with capture), two pointers pinch, the wheel and the `ds::slider`
   zoom (100–400%, about the middle of the crop), the arrow keys move it 8px
   (40 with Shift), + and − zoom 10%, and Rotate turns it a quarter.
4. **Apply**: the crop is drawn to a canvas at `outputSize` square, exported
   with `toBlob(outputType, quality)`, and put into the real file input with
   `DataTransfer`, which then dispatches `input` and `change`. `crop` is
   dispatched from the root with `{ blob, width, height }`. The crop shows in
   the avatar at once, as an object URL.

Cancel, Escape, the ✕ and the backdrop drop the photo; focus goes back to
Change (a drop or a paste leaves the modal nothing else to return to).

**Why a separate picker input.** The input that posts must only ever hold the
crop: Livewire uploads a `wire:model` file input the moment it changes, so if
the reader chose straight into it, Livewire would upload the uncropped
original first. The picker has no name and no binding.

**The output is a square.** A circle is a display mask only; the avatar is
drawn round by CSS everywhere it appears, and a square image keeps the corners
for a square avatar later. A JPEG or WebP crop of a transparent PNG is drawn
on white, because a canvas's transparent pixels export as black.

**EXIF orientation**: `createImageBitmap(file, { imageOrientation:
'from-image' })`, falling back to an `<img>`, which browsers draw upright
themselves.

Where `DataTransfer` cannot be constructed (old Safari), the original file is
put into the input instead and the server crops it, as without the script.

## Without the script

A **plain file input** beside the avatar, named `name`, posting the original
photo for the server to crop; and, when there is a photo, a **Remove photo
checkbox** posting `{name}_remove=1`. Fully usable: it is the form a 2010
profile page had. With the script both are hidden; the input is still the one
that posts, and the Remove button ticks the same checkbox.

## Remove

Remove ticks `{name}_remove` (or `removeName`), clears the file input and
shows the initials (`ds::avatar` without `src`). Choosing a new photo after
unticks it. The server decides what removing means.

## Accessibility

- The component is a `group` named by its label; Change and Remove carry the
  label for a screen reader ("Change Photo").
- The crop dialog is named "Crop photo", traps focus and returns it.
- The stage is a focusable `group`, "Crop area", described by the
  instructions under it; **nothing depends on dragging**: the keys and the
  slider do all of it, and Rotate is a button.
- Zoom is a real `ds::slider`, a native range input with its value shown.
- Errors are said in a polite live region; the host's `error` marks the file
  input `aria-invalid`.

## Do not

- **Do not crop on the server only when the script is there.** Validate
  dimensions and type on the server always; the browser's checks are for the
  reader's sake.
- **Do not upload from the `crop` event and also post the form.** Pick one.
- **Do not use it for documents or several files.** That is
  [file-upload](file-upload.md).
