@props([
    /** The link being shared, in a read-only field with a copy button. */
    'url',
    /** The panel's heading, and the name of the region it is. */
    'label' => 'Share',
    /** For the device's own share sheet: its title and text. The button shows only where the browser has one. */
    'title' => null,
    'text' => null,
    /** General access: the current value, and the choices as value => label. The values are yours. No options, no row. */
    'access' => null,
    'accessOptions' => [],
    /** Words over the access field. */
    'accessLabel' => 'General access',
    /**
     * The people with access, one row each: ['id' => …, 'name' => …,
     * 'email' => …, 'avatar' => url, 'role' => value, 'locked' => true].
     * `locked` shows the role as words with no remove (the owner, say). A
     * `peopleList` slot replaces the rows with list items of your own.
     */
    'people' => [],
    /** The roles a person can have, as value => label. Empty: each role is shown as words. */
    'roles' => [],
    /** Words over the people list. */
    'peopleLabel' => 'People with access',
    /** Words over the link field. */
    'linkLabel' => 'Link',
    'copyLabel' => 'Copy link',
    'shareLabel' => 'Share…',
])

@php
    /*
     * Lays out, labels and reports; holds nothing. Every control is an
     * ordinary form control with a name (`access`, `roles[id]`, a `remove`
     * submit carrying the id), so inside a form it posts with no script and no
     * Alpine; with Alpine each change is also dispatched (`access-change`,
     * `role-change`, `remove`) for a host that keeps the state itself, as a
     * Livewire component does. Native selects through ds::input, not
     * ds::select: a native select fires `change` and takes wire:model
     * directly, and these are short lists.
     */
    $base = $attributes->get('id') ?? 'ds-share-'.substr(md5($url.$label), 0, 8);
    $share = \Illuminate\Support\Js::from(array_filter(['url' => $url, 'title' => $title, 'text' => $text], fn ($v) => $v !== null));
    $accessChange = "\$dispatch('access-change', { value: \$event.target.value })";
    $roleLabels = collect($roles)->map(fn ($l) => (string) $l)->all();
@endphp

<section
    x-data="{ dsCanShare: false }"
    x-init="dsCanShare = typeof navigator.share === 'function'"
    aria-labelledby="{{ $base }}-title"
    {{ $attributes->merge(['class' => 'flex flex-col gap-5']) }}
>
    <h2 id="{{ $base }}-title" class="text-title text-fg">{{ $label }}</h2>

    @isset($invite)
        <div class="flex flex-wrap items-end gap-2 *:min-w-0">{{ $invite }}</div>
    @endisset

    @if (isset($peopleList) || count($people))
        <div>
            <h3 class="text-meta font-medium text-fg-muted">{{ $peopleLabel }}</h3>
            <ul role="list" class="mt-2 flex flex-col">
                @isset($peopleList)
                    {{ $peopleList }}
                @else
                    @foreach ($people as $person)
                        @php
                            $pid = $person['id'];
                            $name = (string) ($person['name'] ?? '');
                            $role = isset($person['role']) ? (string) $person['role'] : null;
                            $locked = (bool) ($person['locked'] ?? false);
                            $roleChange = "\$dispatch('role-change', { id: ".json_encode($pid).', role: $event.target.value })';
                            $remove = "\$dispatch('remove', { id: ".json_encode($pid).' })';
                        @endphp
                        <li class="flex items-center gap-3 py-2">
                            <x-ds::avatar :src="$person['avatar'] ?? null" :name="$name" size="sm" :decorative="true" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-body font-medium text-fg">{{ $name }}</p>
                                @if (! empty($person['email']))
                                    <p class="truncate text-meta text-fg-muted">{{ $person['email'] }}</p>
                                @endif
                            </div>
                            @if ($locked || ! count($roleLabels))
                                <span class="shrink-0 text-meta text-fg-muted">{{ $roleLabels[$role] ?? $role }}</span>
                            @else
                                <x-ds::input as="select" name="roles[{{ $pid }}]" aria-label="Role for {{ $name }}" x-on:change="{{ $roleChange }}" class="w-32 shrink-0">
                                    @foreach ($roleLabels as $value => $words)
                                        <option value="{{ $value }}" @selected((string) $value === $role)>{{ $words }}</option>
                                    @endforeach
                                </x-ds::input>
                            @endif
                            @unless ($locked)
                                <button
                                    type="submit"
                                    name="remove"
                                    value="{{ $pid }}"
                                    x-on:click="{{ $remove }}"
                                    aria-label="Remove {{ $name }}"
                                    class="-mr-1 shrink-0 rounded-control p-2 text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-danger focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                                >
                                    <x-ds::icon name="x-mark" size="5" />
                                </button>
                            @else
                                <span class="w-9 shrink-0" aria-hidden="true"></span>
                            @endunless
                        </li>
                    @endforeach
                @endisset
            </ul>
        </div>
    @endif

    @isset($accessField)
        <div>{{ $accessField }}</div>
    @elseif (count($accessOptions))
        <x-ds::input as="select" name="access" :label="$accessLabel" x-on:change="{{ $accessChange }}">
            @foreach ($accessOptions as $value => $words)
                <option value="{{ $value }}" @selected((string) $value === (string) $access)>{{ $words }}</option>
            @endforeach
        </x-ds::input>
    @endisset

    <div class="flex flex-wrap items-end gap-2">
        <x-ds::input id="{{ $base }}-link" :label="$linkLabel" value="{{ $url }}" readonly class="min-w-48 flex-1" />
        <x-ds::copy-button target="{{ $base }}-link" :label="$copyLabel" />
        {{-- Where the browser has a share sheet of its own. Hidden until
             Alpine has looked, so a browser without one never shows it. --}}
        <x-ds::button
            x-show="dsCanShare"
            x-cloak
            variant="secondary"
            x-on:click="navigator.share({{ $share }}).catch(() => {})"
        >{{ $shareLabel }}</x-ds::button>
    </div>
</section>
