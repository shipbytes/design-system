@props([
    /**
     * The teams, workspaces or accounts, in order: rows of `id`, `name`, and
     * optionally `meta` (a plan or a role), `src` or `initials` for the
     * avatar, and `href` for a team that is chosen by going to it.
     */
    'teams' => [],
    /** The id of the team in use: the trigger shows it, the list ticks it. */
    'current' => null,
    /** A field name: the chosen id posts under it, in the form this draws for `action` or in one around it. */
    'name' => null,
    /** A URL: a real form posting there, submitted the moment a team is chosen. */
    'action' => null,
    /** With `action`: POST, or PUT, PATCH or DELETE (spoofed with _method). */
    'method' => 'POST',
    /** What the control does, its accessible name and the panel's: "Switch team", "Switch workspace". */
    'label' => 'Switch team',
    /** A search field heads the list when there are MORE teams than this. */
    'searchFrom' => 6,
    /** The team's avatar alone, for a collapsed sidebar. The name moves into the button's label. */
    'compact' => false,
    'placeholder' => 'Find a team…',
    'emptyText' => 'No teams match',
    /** The words on the no-script form's button. */
    'submitLabel' => 'Switch',
    /** Alpine reference holding the open state, as ds::popover's `open`: assignable. Optional: left out, it keeps its own. */
    'open' => null,
])

@php
    $teams = array_values(array_map(function ($team) {
        $team = (array) $team;
        if (! array_key_exists('id', $team) || blank($team['name'] ?? null)) {
            throw new \InvalidArgumentException(
                'ds::team-switcher needs an `id` and a `name` on every team: the id is what is chosen, the name what is shown.'
            );
        }

        return [
            'id' => (string) $team['id'],
            'name' => (string) $team['name'],
            'meta' => isset($team['meta']) ? (string) $team['meta'] : null,
            'src' => $team['src'] ?? null,
            'initials' => $team['initials'] ?? null,
            'href' => $team['href'] ?? null,
        ];
    }, is_iterable($teams) ? (is_array($teams) ? $teams : iterator_to_array($teams)) : []));

    if ($teams === []) {
        throw new \InvalidArgumentException('ds::team-switcher needs at least one team in `teams`.');
    }

    $current = $current === null ? null : (string) $current;
    $active = collect($teams)->firstWhere('id', $current) ?? $teams[0];

    // Every team a link: choosing is going there, and without the script the
    // list is a list of links. Otherwise a value is chosen, and without the
    // script it is a native select.
    $links = collect($teams)->every(fn ($team) => filled($team['href']));
    $searchable = count($teams) > (int) $searchFrom;

    $verb = strtoupper((string) $method);
    $spoof = in_array($verb, ['PUT', 'PATCH', 'DELETE'], true) ? $verb : null;
    $token = $action !== null ? rescue(fn () => csrf_token(), null, false) : null;

    // The host's model binding belongs on the field that holds the value.
    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->filter(fn ($v, $key) => ! $isModel($v, $key));

    // Hashed from the list, not random and not from `current`: Livewire keys
    // a morph by id, and choosing must patch the list, not replace it.
    $id = 'ds-team-'.substr(md5(json_encode([$teams, $name, $label])), 0, 8);

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsTeamSwitcher', ['submit' => $action !== null && ! $links]);

    $listbox = \Shipbytes\BladeUi\Support\Listbox::recipe();
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    $trigger = implode(' ', [
        'flex min-w-0 items-center rounded-control text-start motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        $compact ? '' : 'w-full gap-2.5 p-1.5 hover:bg-fg/5',
    ]);
@endphp

<div
    x-data="{{ $state }}"
    data-ds-team-switcher
    {{ $rest->merge(['class' => $compact ? 'inline-block' : 'block w-full min-w-0']) }}
>
    @if ($action !== null)
        <form method="POST" action="{{ $action }}" data-ds-team-form>
            @if ($token)<input type="hidden" name="_token" value="{{ $token }}" autocomplete="off" />@endif
            @if ($spoof)<input type="hidden" name="_method" value="{{ $spoof }}" />@endif
    @endif

    {{-- Without the script: links, or a native select and a button. With
         it, hidden, and the select still holds the value the form posts. --}}
    <div data-ds-team-fallback x-bind:hidden="$data.dsReady">
        @if ($links)
            <nav aria-label="{{ $label }}">
                <ul class="space-y-0.5">
                    @foreach ($teams as $team)
                        <li>
                            <a
                                href="{{ $team['href'] }}"
                                @if ($team['id'] === $active['id']) aria-current="page" @endif
                                class="flex items-center gap-2 rounded-control px-2 py-1.5 text-body text-fg-body hover:bg-fg/5 aria-[current=page]:font-medium aria-[current=page]:text-fg focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring"
                            >
                                <x-ds::avatar :name="$team['name']" :src="$team['src']" :initials="$team['initials']" size="xs" :square="true" :decorative="true" />
                                <span class="min-w-0 truncate">{{ $team['name'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @else
            <div class="flex items-end gap-2">
                <x-ds::input as="select" :label="$label" :name="$name" :attributes="$model->merge(['data-ds-team-select' => true, 'id' => $id.'-select'])">
                    @foreach ($teams as $team)
                        <option value="{{ $team['id'] }}" @selected($team['id'] === $active['id'])>{{ $team['name'] }}</option>
                    @endforeach
                </x-ds::input>
                @if ($action !== null)
                    <x-ds::button type="submit" variant="secondary">{{ $submitLabel }}</x-ds::button>
                @endif
            </div>
        @endif
    </div>

    <div hidden x-bind:hidden="! $data.dsReady">
        <x-ds::popover :label="$label" :open="$open" placement="bottom-start" width="md" :class="$compact ? '' : 'w-full'">
            <x-slot:trigger>
                <button type="button" aria-haspopup="dialog" class="{{ $trigger }}" data-ds-team-trigger>
                    <x-ds::avatar :name="$active['name']" :src="$active['src']" :initials="$active['initials']" size="sm" :square="true" :decorative="true" data-ds-team-avatar />
                    <span @class(['min-w-0 flex-1', 'sr-only' => $compact])>
                        <span class="block truncate text-body font-medium text-fg" data-ds-team-name>{{ $active['name'] }}</span>
                        <span class="block truncate text-meta text-fg-muted empty:hidden" data-ds-team-meta>{{ $active['meta'] }}</span>
                    </span>
                    @unless ($compact)
                        <x-ds::icon name="chevron-up-down" variant="mini" size="4" class="text-fg-muted" />
                    @endunless
                </button>
            </x-slot:trigger>

            {{-- The surface's padding is for prose; a list runs to its edges. --}}
            <div class="-m-4">
                @if ($searchable)
                    <div class="border-b border-divider p-2">
                        <span class="{{ $field['shell'](false) }}">
                            <input
                                type="search"
                                aria-label="{{ $placeholder }}"
                                aria-controls="{{ $id }}-list"
                                placeholder="{{ $placeholder }}"
                                autocomplete="off"
                                data-ds-team-search
                                data-ds-autofocus
                                {{-- Its own change is not a team chosen: a host
                                     listening on the switcher hears only that. --}}
                                x-on:change.stop
                                class="{{ $field['control'](false, false, false, 'sm') }}"
                            />
                        </span>
                    </div>
                @endif

                <ul role="listbox" id="{{ $id }}-list" aria-label="{{ $label }}" class="flex max-h-72 flex-col overflow-y-auto py-1" data-ds-team-list>
                    @foreach ($teams as $team)
                        @php
                            $isCurrent = $team['id'] === $active['id'];
                            $tag = $team['href'] ? 'a' : 'div';
                        @endphp
                        {{-- The item is presentational, so the listbox owns the
                             option inside it, a link or not. --}}
                        <li role="none" class="flex flex-col">
                        <{{ $tag }}
                            role="option"
                            tabindex="-1"
                            @if ($team['href']) href="{{ $team['href'] }}" @endif
                            aria-selected="{{ $isCurrent ? 'true' : 'false' }}"
                            @if ($isCurrent && ! $searchable) data-ds-autofocus @endif
                            data-ds-team-option
                            data-value="{{ $team['id'] }}"
                            data-ds-label="{{ $team['name'] }}"
                            data-ds-keywords="{{ $team['meta'] }}"
                            class="group/team {{ $listbox['option'] }}"
                        >
                            <x-ds::avatar :name="$team['name']" :src="$team['src']" :initials="$team['initials']" size="sm" :square="true" :decorative="true" data-ds-team-avatar />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-body font-medium text-fg" data-ds-team-name>{{ $team['name'] }}</span>
                                <span class="block truncate text-meta text-fg-muted empty:hidden" data-ds-team-meta>{{ $team['meta'] }}</span>
                            </span>
                            <x-ds::icon name="check" variant="mini" size="4" class="invisible text-accent group-aria-selected/team:visible" />
                        </{{ $tag }}>
                        </li>
                    @endforeach
                </ul>

                <div role="status">
                    <p class="{{ $listbox['empty'] }}" hidden data-ds-team-empty>{{ $emptyText }}</p>
                </div>

                @isset ($footer)
                    <div class="border-t border-divider p-1">{{ $footer }}</div>
                @endisset
            </div>
        </x-ds::popover>
    </div>

    @if ($action !== null)
        </form>
    @endif
</div>
