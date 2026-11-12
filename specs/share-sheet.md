# Share sheet

Who has access to a thing and how to send its link: the people with their
roles, general access, the link with a copy button, and the device's own share
sheet where the browser has one.

## What it is, and what it is not

It **lays out, labels and reports**; it holds nothing. Who has access is the
host's: the rows are drawn from `people`, the access field from `access`, and
a change is a form post or an event, never a list the component keeps.

Every control is an ordinary form control with a name, so inside a form the
whole thing works with no script and no Alpine:

| Control | Posts |
|---|---|
| A person's role | `roles[<id>]` = the role's value |
| General access | `access` = the value |
| A person's remove button (a submit) | `remove` = the id |

With Alpine each change is also dispatched, bubbling, for a host that keeps
the state itself (a Livewire component):

| Event | `detail` |
|---|---|
| `role-change` | `{ id, role }` |
| `access-change` | `{ value }` |
| `remove` | `{ id }` |

None of them is called `change`. A `change` CustomEvent's `detail` is taken
as the value by `x-model` and `wire:model` on an ancestor; these names never
collide with that.

It is a panel, not a dialog. Put it in a `ds::modal`, a `ds::sheet` or a
page; it is a `<section>` named by its heading wherever it goes.

## Anatomy

```
Share Q3 report                           ← label, the section's name
[ Add people …            ] [Invite]      ← `invite` slot (optional)
People with access
 (MP) Maya Patel  maya@…        Owner      ← locked: role as words, no remove
 (OM) Omar Malik  omar@…   [Editor ▾] ✕   ← a native select and a submit
General access
 [ Restricted               ▾ ]
Link
 [ https://example.com/r/q3 ] [Copy link] [Share…]
```

## Decisions

- **Native selects through `ds::input as="select"`, not `ds::select`.** The
  lists are two or three roles long, a native select fires `change` and takes
  `wire:model` directly, and it posts with no script. `ds::select` is the
  styled listbox; it is not needed here. (It also drops `wire:model` today:
  it passes only `class` through to its hidden input. Reported separately.)
- **`locked`** shows the role as words and leaves out the remove button: the
  owner cannot be demoted or removed from this panel. The space where the
  button would be is kept, so the roles line up.
- **The device share button shows only where the browser has one**:
  `navigator.share` checked by Alpine once, the button `x-show` and `x-cloak`.
  A browser without it, or a page without Alpine, never shows a button that
  does nothing. A cancelled share (the reader closed the device sheet) is not
  an error, so the rejection is swallowed.
- **The link is a read-only `ds::input` and `ds::copy-button`**, so copying is
  the copy button's: its confirmation, its fallback.
- **The avatars are decorative.** The name is written next to them.

## Accessibility

- **Role and name**: a `<section>` named by its `<h2>` (`aria-labelledby`).
  People are a `<ul role="list">`.
- Each role select is named `Role for <name>`; each remove button
  `Remove <name>`. A list of people with three "Remove" buttons is three
  identical names.
- **Focus**: nothing moves it. After a remove the host re-renders; a host
  that removes the row in place should put focus on the next row's control
  (or the list's heading), not leave it on nothing.

| Key | Does |
|---|---|
| Tab | Each role select, each remove button, the access select, the link field, Copy link, Share… |
| Arrow keys, Space | The native select's own |
| Enter / Space on a remove button | Submits `remove=<id>` (and dispatches `remove`) |
| Enter / Space on Copy link | Copies, and says so |

## Do not

- **Keep who has access in the browser.** The component reports; the server
  decides. A remove posted or dispatched is a request.
- **Give it a role list of more than a handful.** A long list is a select of
  its own, outside the row.
- **Name an event handler for `change` on an ancestor** expecting the
  component's events. They are `role-change`, `access-change`, `remove`.
