# Pagination

Two Laravel paginator views, not components.

## Use it

Point the paginator at them once, in a service provider:

```php
use Illuminate\Pagination\Paginator;

public function boot(): void
{
    Paginator::defaultView('ds::pagination');
    Paginator::defaultSimpleView('ds::simple-pagination');
}
```

Then every paginated screen follows, with no change to your views:

```blade
{{ $reports->links() }}
```

Or name one explicitly:

```blade
{{ $reports->links('ds::simple-pagination') }}
```

## Which is which

| View | For |
|---|---|
| `ds::pagination` | `paginate()` — numbered pages, with a total. |
| `ds::simple-pagination` | `simplePaginate()` — previous and next only. |

`simplePaginate()` does not count the total rows, so it is the faster query on a
large table — and the view matches, because there is no page count to show.

## What it replaces

Laravel's stock Tailwind pagination view is built on `gray` and `blue-300` focus
borders — a second neutral ramp and an off-system accent — and carries its own
hand-written `dark:` classes that assume a palette this system does not use.
These two use the system's tokens throughout, so they follow your theme and dark
mode with nothing extra.

## The current page is elevation, not colour

Marked the same way the active [nav item](nav-item.md) is: a raised card, never a
fill. "You are here" means the same thing in both places, and elevation survives
dark mode where a colour would have to be redefined.

More in [specs/pagination.md](../../specs/pagination.md).
