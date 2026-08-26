# Skeleton

A picture of content that does not exist yet. Shown while a region loads, in
place of the thing that is coming.

## Anatomy

```
▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
▬▬▬▬▬▬▬▬▬          ← the last bar is short
```

## Variants

`text` (default) | `block` | `circle`

`text` takes `lines`; `block` and `circle` take `size` (`sm`/`md`/`lg`). All
closed sets of literal classes.

**The last bar is short.** A stack of equal-length bars reads as a table or a
list of fields; a paragraph ends mid-line, and that single detail is most of what
makes this look like the text it stands in for.

## The fill is `fg/10`, not a surface token

A skeleton has to be visible on the card *and* on the sunken ground behind it,
and a fixed surface token is only ever right on one of them — `surface-subtle` on
`surface` is nearly invisible, and on `surface-sunken` it is invisible.

A translucent foreground adapts to both, and to dark, from one value.

## Motion

`motion-safe:animate-pulse`.

A pulsing block is exactly the kind of thing that triggers vestibular symptoms,
and a loading state is not worth that. With reduced motion it is a static grey
bar, which reads as "not yet" perfectly well.

## Accessibility

**The skeleton is `aria-hidden`. The region it stands in carries `aria-busy`.**

A skeleton has nothing to announce — it is a shape. Left visible to assistive
technology it reads as a run of empty elements, which is worse than silence.

That means the host owns the announcement, and the contract is one attribute:

```blade
<div aria-busy="true" aria-live="polite">
    <x-ds::skeleton :lines="3" />
</div>
```

When the content arrives, `aria-busy` goes to `false` and the live region
announces it. Without this a screen reader reader is told nothing at all — the
page simply goes quiet for as long as the request takes, and there is no way to
tell that from a page that has finished.

## Do not

- **Do not skeleton something you can show.** Cached content with a spinner beside
  it beats a skeleton that replaces content the reader could already be reading.
- **Do not use it for an empty result.** That is [empty-state.md](empty-state.md).
  A skeleton that never resolves is indistinguishable from a hung page.
- **Do not match it to the content shape exactly.** Chasing a pixel-accurate
  outline of the real layout means it drifts the moment the layout changes, and
  the mismatch on load is more distracting than the approximation.
- **Do not show it for under ~200ms.** A flash of grey bars reads as a glitch;
  below that threshold, show nothing.
