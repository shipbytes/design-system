# Money input

An amount of money: grouped as the locale groups it while it is typed,
posted as a machine decimal string. A text field and a hidden input on the
field recipe, behind `dsMoneyInput`.

## Anatomy

```
div[data-ds-money][x-data=dsMoneyInput][x-modelable=dsValue]
├─ label[for=id]
├─ input[type=hidden][data-ds-money-value]     name = `name` once dsReady
├─ span (field shell, focus ring)
│  └─ span[data-ds-money-frame]                 the control's border, padding, text; a flex row
│     ├─ select[data-ds-money-currency]          with `currencies`: posts `currencyName`
│     │    or span[aria-hidden] prefix           the symbol or code, from the locale
│     ├─ input[type=text][inputmode=decimal][pattern][data-ds-money-input]
│     │                                          name = `name` until dsReady
│     └─ span[aria-hidden] suffix                (de: "1.234,50 €")
├─ span.sr-only#id-currency                      the code, the field's description
└─ p help | p error
```

- **The name moves.** Without the script the text field posts what was typed,
  and `pattern` and `inputmode` say what it takes. With it the hidden input
  posts the amount as digits: `"1234.50"`, or `"123450"` with `minor`. Both
  names are bindings to `$data.dsReady`, as on ds::time-picker, so a Livewire
  morph, which writes the server's attributes back, keeps them.
- **No floats anywhere.** The amount is a string of digits in PHP
  (`Support\Money`) and in the browser (`shared/money.js`): rounding is half
  away from zero on the digits, bounds and step compare as BigInt units.
  0.1 + 0.2 is never 0.30000000000000004, and a 20-digit amount keeps every
  digit.

## Props

| Prop | Notes |
|---|---|
| `currency` | ISO 4217, default USD. Its places come from `Support\Money::DECIMALS` (0 for JPY, KRW…; 3 for BHD, KWD…; 2 otherwise) |
| `locale` | Defaults to the app's. Grouping and the decimal separator, including Indian grouping for en-IN (12,34,567.50) |
| `decimals` | Overrides the table |
| `symbol` | `symbol` \| `code` \| `none`, as a prefix or suffix where the locale puts it |
| `currencies` | Codes, or code ⇒ label: a native select at the start, posting `currencyName` (default `{name}_currency`) |
| `min` `max` `step` | In whole units, even with `minor` |
| `allowNegative` | A leading minus |
| `minor` | Post integer minor units; `value` is read as minor units too |

## Behaviour (`dsMoneyInput`)

- `beforeinput`: either `.` or `,` typed becomes the locale's decimal
  separator; letters are refused; a minus only at the start, only with
  `allowNegative`. More than one character (paste, drop, autofill) goes through
  the lenient reader (`read`): symbols, letters and spaces stripped; with both
  `.` and `,` in it, the last is the decimal point; with one mark once, it is
  the point if it is the locale's or is not followed by exactly three digits;
  one mark repeated is grouping. "$1,234.567" in de reads 1234.567, rounded
  on blur to 1.234,57.
- `input`: the text is regrouped and the caret kept, counted in significant
  characters (`shared/caret.js`). Backspace beside a separator deletes the digit
  beyond it. Then the hidden input is set and dispatches `input`.
- `blur`: padded to the places, checked against `min`, `max`, `step`
  (`setCustomValidity` and `aria-invalid`, so the form will not submit), and
  `change` from the hidden input when the amount changed.
- Choosing a currency re-reads the amount at the old places and rounds it to the
  new ones (12.345 BHD to JPY is 12).
- The script re-renders the field on init with `Intl.NumberFormat`; the first
  paint is `Support\Money::format`, ICU's when ext-intl is loaded. The two can
  differ on a detail (es groups 4-digit amounts in one and not the other); the
  digits never do.
- Without ext-intl, `Support\Money` falls back to en style: `,` groups of three,
  `.` decimal, a common symbol or the code as a prefix.

## Accessibility

| | |
|---|---|
| Field | `input type=text`, `inputmode=decimal`, named by its label |
| Currency | The code is the field's description (the symbol is `aria-hidden`); with `currencies`, a native select named "Currency" |
| Errors | `aria-invalid` and the browser's message from `setCustomValidity`; a server `error` is `aria-describedby` |
| Focus | The shell's inward ring, on the field or the select |
| No script | A labelled text field with `inputmode` and `pattern` |

## Do not

- **Do not parse the posted string as a float.** Use `bcmath`, `brick/money`
  or integer minor units.
- **Do not trust it.** The server validates the amount and the currency.
- **Do not use `type=number`.** It drops grouping, locale separators and long
  amounts.
