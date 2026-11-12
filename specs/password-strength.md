# Password strength

A four-level meter for a new password, and the `reveal` toggle on ds::input
that it is usually paired with (specified in [input.md](input.md#reveal)).

**It is a hint, not a security control.** The server's rules decide what is
accepted, and only a server can check a breach list.

## Anatomy

```
New password
┌──────────────────────────────┬──┐
│ ••••••••••                   │ 👁│   ← ds::input with reveal
└──────────────────────────────┴──┘
▬▬▬▬ ▬▬▬▬ ▬▬▬▬ ▬▬▬▬                    ← four bars, aria-hidden
Good                                   ← the words, a polite live region
```

Bound to the field by `for`, the field's `id`, like a label. The bars light up
to the level, coloured off `data-level` on the root: the script sets the
attribute and nothing else, so every class is a literal in the Blade file.

## Levels

| Level | Bars | Colour | Default word |
|---|---|---|---|
| 0 | none | `fg/10` | (none) |
| 1 | 1 | `danger` | Weak |
| 2 | 2 | `warning` | Fair |
| 3 | 3 | `success` | Good |
| 4 | 4 | `success` | Strong |

Good and Strong share a colour on purpose: the bar count and the word tell them
apart, so colour is never the only signal, and a fourth hue would only be a
weaker green.

`labels` replaces the words in order; fewer than four replaces only the first.

## The heuristic (`dsPasswordStrength`)

The package's own, in the script, no library:

1. Empty is level 0.
2. Shorter than `minLength`, or a common password, is Weak. Common is checked
   against a short list of the most-used passwords after lower-casing, undoing
   the usual swaps (`@`→a, `0`→o, `1`→i, `3`→e, `$`→s and the like) and
   trimming digits and symbols off the ends, so `P@ssw0rd!` is caught.
3. Otherwise one point, plus one each for 12, 16 and 20 characters, one for
   three kinds of character (lower, upper, digit, other) and one more for all
   four.
4. Minus one for a character three times running, minus one for a run of four
   along the alphabet, the digits or a keyboard row (either way), minus one
   when fewer than half the characters, up to six, are different.
5. Clamped to 1 through 4.

Length is worth the most because it is. A four-word passphrase reaches Strong
on length alone; `Tr0ub4dor&3` reaches Good on variety.

## Accessibility

| | |
|---|---|
| Role | No widget role: a `p` with `aria-live="polite"` and `aria-atomic="true"` |
| Name | A visually hidden "Password strength:" before the word, read as one sentence |
| Updates | Spoken when the level changes, not on every keystroke |
| Bars | `aria-hidden`; the words carry the meaning |
| Keyboard, focus | None of its own; focus stays in the password field |

## Do not

- **Do not block submit on it.** The server decides.
- **Do not show it on sign-in.** It measures a password being chosen.
- **Do not use it as the only statement of the rules.** Put them in the
  field's `help`.
