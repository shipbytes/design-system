# Inline edit

Text that turns into a field where it stands: rename a project in its
heading, fix a cell in its row. Enter saves, Escape puts it back.

![Inline edit](../images/inline-edit.png)

> **Needs Alpine** and the package's script. See [getting started](../getting-started.md#3-add-the-script).
> Without them it is a plain field, always editable, inside its form.

## Use it

```blade
<h1 class="text-title">
    <x-ds::inline-edit name="title" label="Title" :value="$project->title" placeholder="Add a title" />
</h1>
```

The text is a button. Pressing it opens a field in the same place, in the
same font, with the text selected; Enter or leaving the field saves, Escape
cancels, and focus goes back to the text. A save dispatches `save` with
`{ value, previous }` and shows the new text. What happens next is yours.

## Props

| Prop | Type | Default | What it does |
|---|---|---|---|
| `label` | string | *required* | What the text is: names the field, and the button as "Edit Title: Q3 roadmap". Not shown. |
| `value` | string | — | The text. |
| `name` | string | — | Submitted name, for the form around it or the one `action` draws. |
| `placeholder` | string | — | Shown while the text is empty, "Add a title". |
| `multiline` | bool | `false` | A textarea. Enter is a new line; Ctrl+Enter (Cmd+Enter) saves. |
| `submit` | `enter` `blur` `buttons` | `enter` | `enter`: Enter saves, and so does leaving. `blur`: only leaving saves. `buttons`: Save and Cancel, and leaving keeps editing. |
| `action` | URL | — | Draws a real form posting here; a save submits it. |
| `method` | string | `POST` | With `action`: `POST`, `PUT`, `PATCH`, `DELETE` (spoofed) or `GET`. |
| `saving` | bool | `false` | Rendered saving: a spinner by the text. |
| `error` | string | — | A failed save's message: rendered editing, marked invalid. |
| `size` | `md` `sm` | `md` | `sm` has less room around the text, for a table cell. The text takes the size of where it sits. |
| `disabled` | bool | `false` | |

`wire:model` and `x-model` go on the component and bind the text.
`x-on:save`, `x-on:cancel` and anything else go on the wrapper.

## With a plain form

```blade
<x-ds::inline-edit
    name="title"
    label="Title"
    :value="old('title', $project->title)"
    :action="route('projects.update', $project)"
    method="PATCH"
    :error="$errors->first('title')"
/>
```

A save submits the form and shows the spinner. A failed validation comes
back with `old()` and the message: the field opens on what the reader typed,
with the message under it. Without the script the same markup is a field
with Save and Cancel, posting the same request.

## With Livewire

```blade
<x-ds::inline-edit
    label="Title"
    wire:model="title"
    :value="$title"
    :error="$errors->first('title')"
    x-on:save="$wire.rename()"
    wire:loading.attr="data-ds-saving"
    wire:target="rename"
/>
```

```php
public string $title;

public function rename(): void
{
    $this->validate(['title' => ['required', 'max:80']]);
    $this->project->update(['title' => $this->title]);
}
```

`wire:loading.attr` shows the spinner while the request runs. When
validation fails, the re-render brings the message and the field opens again
on the text the reader tried; when it succeeds, the re-render shows the
saved text.

## With Alpine

The scope's `dsSaving` and `dsError` are yours to set from the handler (a
`{{ }}` echo, not `@js`, inside a component's attribute: a directive there
is not compiled):

```blade
<x-ds::inline-edit
    label="Title"
    :value="$project->title"
    x-on:save="dsSaving = true;
        fetch('{{ route('projects.rename', $project) }}', { method: 'POST', body: JSON.stringify($event.detail) })
            .then((r) => { dsSaving = false; if (! r.ok) dsError = 'Could not save. Try again.' })"
/>
```

## Accessibility

- The text is a `button` named "Edit {label}: {text}", so a screen reader
  says what it is, what it says and that it can be pressed.
- The pencil shows on hover and on keyboard focus, and always on a touch
  screen.
- The field is named by `label` and described by the error message.
- Focus goes back to the text after Enter, Escape, Save or Cancel. After a
  save by leaving the field it stays where the reader went.

## Don't

- **Don't use it for several fields that belong together.** That is a form
  with one Save.
- **Don't use it for rich text.** It edits plain text.
- **Don't drop a failed save on the floor.** Pass the message as `error` (or
  set `dsError`): the field reopens with it. A field that quietly reverts
  loses the reader's edit.
- **Don't rely on the pencil alone** on a page that is nothing but inline
  edits: it shows only on hover and focus.

More in [specs/inline-edit.md](../../specs/inline-edit.md).
