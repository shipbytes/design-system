# Counter

A number that turns its digits to a new value, like a mechanical meter. The
number is complete in the HTML the server sends; the roll is an enhancement.

## Anatomy

```
<span data-ds-counter data-ds-value="12480.5" data-ds-format="{…}">   ← root, tabular-nums
    <span class="sr-only">$12,480.50</span>                          ← the text that is read
    <span aria-hidden="true">$12,480.50</span>                       ← the face; columns while rolling
    <template data-ds-counter-digit>…</template>                    ← one column, cloned per digit
</span>
```

| Part | Token | Why |
|---|---|---|
| Root | `tabular-nums`, `whitespace-nowrap` | Every digit the same width, so a roll never moves what follows it. |
| Size | `text-meta` / `text-body` / `text-title` / `text-display`, or none | A counter usually sits in a sentence or a tile that already sets the size; `size` is for when it does not. |
| Column | `overflow-hidden`, an invisible `0` | The box is one digit high and exactly one digit wide. |
| Strip | `translate-y-(--ds-digit)`, `motion-safe:transition-[translate]`, `ease-enter`, `duration-{token}-enter` | The slide is CSS, timed by a motion token; the script only sets the custom property. |

## At rest, and rolling

At rest the face is plain text, so it selects, copies and wraps like the words
around it. On a change the script replaces the face with one column per digit
of the NEW value and slides each strip from the digit that stood in that place.
Digits are matched from the right: 999 → 1,000 turns three nines to zeros and
rolls a one in from zero. Separators, prefix and suffix are plain text between
the columns. When the slide ends (the strip's computed transition duration),
the face is plain text again.

The hidden span holds the new value from the start of the roll, so a screen
reader asked mid-roll hears the destination, never a blur of digits.

## Two routes

| | Server value | Alpine-bound value |
|---|---|---|
| Write | `<x-ds::counter :value="$n" />` | `<x-ds::counter ::value="count" />` |
| Behaviour | `x-ds-roll` directive | `dsRoll`, through `Behaviour::data()` |
| What changes it | a re-render: Livewire's morph rewrites the text and `data-ds-value` | the bound expression, through `Alpine.effect` |
| Script missing | the number the server drew; a morph still changes it | the console names `dsRoll`; the number the server drew stays |

`x-ds-roll` is an ENHANCEMENT in test-js.mjs's sense: skipping it costs the
roll and nothing else, and test-behaviour.mjs morphs a counter on a page with
no include to prove it. A MutationObserver on the root hears the morph; it
reads the hidden span, because the face may be mid-roll.

A bound value is different: nothing but the script writes it, so a missing
script would leave a stale number in silence. That is why that route asks
through `Behaviour::data()`, which reports. The view lifts `:value` /
`x-bind:value` off the attributes (as an attribute it would set nothing that is
read) and hands it to `dsRoll` as a getter evaluated in the host's scope.

The script formats the bound value with the same options the server used,
written into `data-ds-format` by `Support\Chart::formatOptions()`, so the
number reads identically whichever side wrote it. PHP's NumberFormatter rounds
half up to match Intl's `halfExpand`; the minus is U+2212 on both sides.

## animateIn

`animateIn` sets the face to zeros of the final shape and rolls up the first
time half of the counter is in view (IntersectionObserver, threshold 0.5),
once. Without an IntersectionObserver, or under reduced motion, the server's
number simply stays. The hidden span always holds the real value.

## Motion

`motion-safe:` on the transition, and `prefers-reduced-motion` checked in the
script as well: under reduced motion a change is an instant swap and
`animateIn` does nothing. `duration` chooses among the `hint`, `popover`,
`dialog` and `slide` enter tokens; the default `slide` is long enough to see a
digit pass and short enough not to hold up reading.

## Stat tile

`ds::stat-tile` has its own count-up: a plain inline script it pushes to a
`scripts` stack, which counts from zero once on load with `toLocaleString`.
It predates the shared script and is deliberately not Alpine. This batch does
not change it. The two differ:

| | stat-tile count-up | counter |
|---|---|---|
| When | once, on load | on every change, and optionally on entering the view |
| How | rewrites the text every frame | slides digit strips; the text is untouched until it settles |
| Format | integer, `toLocaleString` | Chart::format's keys, identical in PHP and JS |
| Read by AT | the moving text | one hidden, settled value |

Whether stat-tile should render a `ds::counter` for its value is a proposal
for the polish batch, not a change here.

## Accessibility

| | |
|---|---|
| Role | none; it is text |
| Name | n/a; the number is read in place, from the hidden span |
| Value | the formatted number, settled |
| Keyboard | none; not focusable |
| Focus | never takes or moves focus |

The face is `aria-hidden` and the hidden span is `select-none`, so a copy of
the visible number copies it once. It is not a live region: a number changing
by itself is not announced, which is right for a dashboard figure and wrong
for, say, a cart total the user just changed, where the host should announce
it.

## Do not

- **Do not add classes from the script.** Every class is in the view, the
  column's in its `<template>`, so a consumer's Tailwind compiles them.
- **Do not make it a live region.** The host decides whether a change is news.
- **Do not animate the width.** The columns are tabular digits; a jitter is a
  bug.
- **Do not count through intermediate values.** A roll turns each digit once,
  from old to new; it does not tick through every number between.
