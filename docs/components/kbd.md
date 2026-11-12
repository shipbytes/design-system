# Kbd

A key on the keyboard, inline in text.

![Kbd](../images/kbd.png)

> Plain Blade, pure CSS.

## Use it

```blade
<p>Press <x-ds::kbd>/</x-ds::kbd> to search.</p>
<p>Open the palette with <x-ds::kbd :keys="['Ctrl', 'K']" />.</p>
<p>On a Mac: <x-ds::kbd :keys="['⌘', 'K']" separator="" />.</p>
```

It takes the size of the text it sits in, so there is no size prop.

## Props

| Prop | Type | Default | What it does |
|---|---|---|---|
| `keys` | array | — | A combination, one key each, nested as HTML has it. Without it, the slot is the one key. |
| `separator` | string | `+` | Between the keys. `""` for none. |

## Don't

- **Don't use it for a button's name.** "Press **Save**" is bold, not a key.
- **Don't write a symbol alone.** "⌘" can be read out as "place of interest
  sign"; say "Cmd" in the prose too.

More in [specs/kbd.md](../../specs/kbd.md).
