# Phone input

A phone number: a country picked from a searchable list and a national
number formatted as it is typed, posted as E.164. A native select and a tel
field on the field recipe, behind `dsPhoneInput`, with the picker on
ds::popover and the shared listbox keys (`shared/picker.js`).

This is a formatter and a length check, **not libphonenumber**. It knows each
country's dial code, one common way of writing its numbers and roughly how many
digits they have. Whether a number exists, or is a mobile, is the server's
question.

## Anatomy

```
div[data-ds-phone][x-data=dsPhoneInput][x-modelable=dsValue]
├─ label[for=id]
├─ input[type=hidden][data-ds-phone-value]         E.164; name = `name` once dsReady
├─ span (field shell, focus ring)
│  └─ span (frame: border, padding, text; a flex row)
│     ├─ span[x-bind:hidden=dsReady]                 no script
│     │  └─ select[data-ds-phone-select][autocomplete=tel-country-code]
│     │       name = name[country] until dsReady, then `countryName` or none
│     │       option[value=ISO][data-ds-name|dial|mask|min|max|zero|flag]
│     ├─ span[hidden until dsReady]
│     │  └─ ds::popover (label = countryLabel)
│     │     ├─ trigger: button[data-ds-phone-trigger]  sr "Country: India," flag? ISO, +dial, chevron
│     │     └─ panel: search field (> 6 countries), ul[role=listbox][data-ds-phone-list] (empty), status > empty row
│     └─ input[type=tel][inputmode=tel][autocomplete=tel-national][data-ds-phone-input]
│          name = name[number] until dsReady
├─ template[data-ds-phone-template]                   one option, cloned per country
├─ p#id-note[aria-live=polite][data-ds-phone-note]    the length note
└─ p help | p error
```

- **The select is the one store of the country**, in both modes. The list is
  built from its options, through the template, the first time it opens, so the
  countries are sent once.
- **Country data** is `resources/data/countries.php`: ISO ⇒ name, dial code,
  mask (`#` per digit), and optional min and max lengths where the mask's count
  is not the whole story. Only the countries in `countries` are rendered.
  NANP territories are written "1 876"; the space is display only.

## Props

| Prop | Notes |
|---|---|
| `value` | E.164. A number with no "+" is national, in `country` |
| `country` | Default country; else the app locale's region; else US; else the first offered |
| `countries` | ISO codes offered; unknown codes throw |
| `preferred` | Pinned first, in the order given; the rest by name |
| `countryName` | With the script, the ISO code posts under it too |
| `flags` | Emoji flags (regional indicators), off by default: Windows draws them as letters |

## Behaviour (`dsPhoneInput`)

- **Typing** keeps digits only (and what pasted or dictated numbers carry:
  spaces, `+ ( ) . / -`), masks them, and keeps the caret (`shared/caret.js`).
- **The trunk 0.** A leading 0 stays on screen and is dropped from E.164,
  except for the countries in `Support\Phone::KEEPS_ZERO` (CI, IT, SM, VA).
- **International numbers.** Text starting "+" or "00", typed or pasted, picks
  the country by its code: the longest code that fits, then the current
  country, then the default, then `Support\Phone::PRIMARY` for shared codes
  (+1 US, +44 GB, +7 RU…), then list order. The rest stays in the field.
- **Length only.** On blur, a number clearly shorter than the country's min or
  longer than its max gets `aria-invalid` and a note. It never blocks the form
  (no `setCustomValidity`): the bounds are generous guesses.
- **The picker** searches the name (ranked as the command palette ranks), the
  ISO code (an exact one first) and the dial code ("44", "+44"). Choosing sets
  the select, redraws E.164, closes the popover and returns focus to the
  trigger. Each opening starts from the whole list.
- Events: `input` from the hidden input as the number changes; `change` on
  blur, or on choosing a country, when E.164 changed.

## Accessibility

| | |
|---|---|
| Number | `input type=tel`, `inputmode=tel`, `autocomplete=tel-national`, named by the label |
| Country (script) | A button, `aria-haspopup=dialog`; its name is "Country: India, IN +91" |
| Country (no script) | A native select named `countryLabel`, `autocomplete=tel-country-code` |
| List | `role=listbox` named `countryLabel`, `role=option`, `aria-selected`; Up/Down, Home/End, Enter/Space; Escape closes |
| Note | `aria-live=polite`, in the field's `aria-describedby` |
| Focus | The shell's ring on the field; the trigger's own ring; back on the trigger after a choice |

## Do not

- **Do not treat it as validation.** Validate with libphonenumber (for Laravel,
  `propaganistas/laravel-phone`) on the server.
- **Do not show flags without names.** They are decoration and are
  `aria-hidden`.
- **Do not expect `name` without the script.** The no-script form posts
  `name[country]` and `name[number]`; accept both shapes.
