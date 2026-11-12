# Colour picker

Choose a colour: a swatch that opens a panel, and a hex text field beside it.
The panel is a saturation and brightness area, a hue slider and, with `alpha`,
an opacity slider, each built from our own elements with Pointer Events and
each a keyboard `role="slider"`; format tabs with HEX, RGB and HSL fields;
preset swatches; and, where the browser has it, an eyedropper. Behind
`dsColour`, on ds::popover.

The component is `ds::color-picker`, in the web platform's spelling, like
`input type="color"`; its props and prose use the repo's (`contrastWith`,
"colour"). sRGB only: no other colour space and no OKLCH editing.

## Anatomy

```
div[data-ds-colour][x-data=dsColour][x-modelable=dsValue]   style: --ds-colour, -solid, -hue, -h, -s, -v, -a
├─ label[for=id]
├─ input[type=hidden][data-ds-colour-value]          the posted colour; name = `name` once dsReady
├─ div (a flex row)
│  ├─ input[type=color][data-ds-colour-native]       no script: posts #rrggbb under `name`; hidden once dsReady
│  ├─ span[hidden until dsReady]
│  │  └─ ds::popover (label "{label} picker")
│  │     ├─ trigger: button[data-ds-colour-trigger]  checkerboard + the colour; sr "{label} picker: #336699"
│  │     └─ panel (dir=ltr)
│  │        ├─ div[role=slider][data-ds-colour-slider=area]    saturation → right, brightness → up
│  │        ├─ div[role=slider][data-ds-colour-slider=hue]     0–360
│  │        ├─ div[role=slider][data-ds-colour-slider=alpha]   with `alpha`, 0–100 %
│  │        ├─ div[role=tablist] HEX | RGB | HSL, button[data-ds-colour-eyedropper]?
│  │        ├─ div[role=tabpanel][data-ds-colour-pane=hex]  input[data-ds-colour-text=panel]
│  │        ├─ div[role=tabpanel][data-ds-colour-pane=rgb]  input[type=number][data-ds-colour-part=r|g|b|a]
│  │        ├─ div[role=tabpanel][data-ds-colour-pane=hsl]  input[type=number][data-ds-colour-part=h|s|l|a]
│  │        └─ div[role=radiogroup] presets: input[type=radio][form=no form][data-ds-colour-preset]
│  └─ span (field shell) > input[type=text][data-ds-colour-text=field]
├─ p#id-contrast[data-ds-colour-contrast]?            with contrastWith: ratio, AA and AAA marks
└─ p help | p error
```

`presetsOnly` renders something else entirely: a label, a
`div[role=radiogroup]` of native radios named `name`, and help or error. No
Alpine, no script.

- **Where every thumb is, and the colour itself, are CSS variables on the
  root**, written by PHP from the value and rewritten by the script. A class
  built from a colour would never reach a consumer's Tailwind.
- **The panel's sliders do not turn round in a right-to-left page** (`dir=ltr`
  on the panel): saturation grows to the right and hue runs red to red on every
  picker, and the arrow keys follow the picture.
- **The presets' radios post nothing**: their `form` attribute names no form,
  so they are a radio group (arrows, one tab stop) that is in no form. The
  colour posts once, from the hidden input.

## Props

| Prop | Notes |
|---|---|
| `value` | Read forgivingly (below). Unreadable text is shown as given, marked invalid, and posts `""` |
| `alpha` | The opacity slider and an A field; posts `#rrggbbaa`, `rgba()` or `hsla()` |
| `format` | `hex` (default), `rgb` or `hsl`: what posts. Also the tab the panel opens on |
| `swatches` | A list of colours, or colour ⇒ name. Unreadable ones throw |
| `eyedropper` | Offer the browser's EyeDropper, where it exists (Chromium) |
| `contrastWith` | A colour; shows the WCAG ratio with AA (4.5) and AAA (7) marks |
| `presetsOnly` | The swatches as plain radios, posting the chosen one in `format` |
| `placeholder` | The text field's, `#000000` |

## Reading and writing a colour

`Support\Colour` (PHP) and `shared/colour.js` (the browser) read and write
colours the same way, and `tests/Fixtures/colour.json` holds both to it
(ColourTest on one side, test-js on the other):

- **Read:** hex in 3, 4, 6 or 8 digits with or without `#`; `rgb()`/`rgba()`
  with commas or spaces, `/` alpha, numbers or percentages; `hsl()`/`hsla()`
  with or without `deg`; the 16 basic CSS names and `grey`. Channels out of
  range are clamped, as CSS clamps them. Not the 148 extended names: a table of
  them costs more than the field is worth, and a canvas is not something to
  create on every key.
- **Write:** `#rrggbb`, `#rrggbbaa` (always eight digits with `alpha`),
  `rgb(51, 102, 153)`, `rgba(51, 102, 153, 0.5)`, `hsl(210, 50%, 40%)`,
  `hsla(…, 0.5)`. The comma syntax, which every parser reads.
- **Held as HSV.** The panel keeps hue, saturation and brightness, so a grey
  keeps the hue its thumb was on and black keeps its saturation; HSL is shown
  for editing only.
- **Contrast** is `Support\Ink::contrast()`, the arithmetic the chart labels
  are chosen by, mirrored in the browser. A translucent colour is laid over
  `contrastWith` first. The ratio is **cut, not rounded**, to two places, and
  the marks are read from that number, so "4.50:1" never fails AA.

## Behaviour (`dsColour`)

- **Typing** in either text field sets the colour as soon as the text reads as
  one; text that does not is left exactly as typed, gets `aria-invalid`, and
  `""` posts until it reads again (the swatch says "No colour"), so a form
  never sends a colour the field no longer shows. Leaving a field with a
  colour in it writes it back as hex.
- **Area:** press or drag (captured, so it follows outside the box). Keys:
  Left/Right saturation, Up/Down brightness, Shift for 10, Page Up/Down
  brightness by 10, Home/End saturation to 0/100.
- **Hue and opacity:** press or drag; arrows by 1 (Shift 10), Page Up/Down by
  10, Home/End to the ends.
- **Number fields** set the colour as they are typed; a field left empty is
  ignored until it is not.
- **Tabs** are a roving tablist (`shared/roving.js`), activated as the arrows
  reach them. They choose which fields edit the colour, not what posts.
- **Presets** check the radio whose colour is the value, whatever set it.
- **Eyedropper**, shown where `window.EyeDropper` exists, keeps the opacity.
- Events: `input` from the hidden input on every change, `change` at the end of
  one (a release, a key, leaving a field, a preset), both plain `Event`s.
  `dsValue` is the posted string; a value the host sets or a morph writes is
  drawn without events.

## Without the script

The browser's own `input type="color"` stands in for the swatch and posts
`#rrggbb` under `name`; the two fields copy into each other with an inline
`oninput` (a valid six-digit hex only). **There is no alpha** in a native
colour input, so `alpha` posts six digits, and `format` is ignored: it is
always hex. The contrast line shows the server's ratio and does not move.

## Accessibility

| | |
|---|---|
| Text field | Named by the label; described by the contrast line, then help or error; `aria-invalid` while unreadable |
| Swatch | A button, `aria-haspopup=dialog`, named "{label} picker: #336699"; opens a dialog named the same |
| Area | `role=slider` named "Saturation, Brightness", `aria-valuenow` the saturation, `aria-valuetext` "Saturation 62%, Brightness 80%"; the keys above |
| Hue, opacity | `role=slider`, `aria-valuetext` "210°", "50%" |
| Format | `role=tablist`, `tab` with `aria-selected` and `aria-controls`, `tabpanel`; each number field named in full ("Red"), the letter beside it hidden |
| Presets | `role=radiogroup` named "Presets", native radios named by the colour's name (or its hex) |
| Contrast | "Contrast with #FFFFFF: 5.99:1", each mark's text saying "passes" or "fails"; colour and icon are extras. Not a live region: it changes with every pixel of a drag |
| Focus | Into the panel on the area; back on the swatch on Escape. Every control draws the system ring; a preset draws it outside its swatch |

## Do not

- **Do not trust the posted string on the server.** Validate it as you would
  any text: the no-script field posts what the browser gives.
- **Do not use the contrast marks as the only check.** They measure one pair
  of colours; the text drawn on them may be large, bold or something else.
- **Do not use it for a few fixed colours.** `presetsOnly` is a radio group
  and needs nothing at all.
