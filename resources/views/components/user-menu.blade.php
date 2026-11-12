@props([
    /** Required: the signed-in person's name. The trigger shows it, the panel's header too. */
    'name' => null,
    'email' => null,
    /** The trigger's second line, a role or a plan ("Admin"). The email, left out. */
    'meta' => null,
    /** Avatar image URL. Without one, initials. */
    'src' => null,
    /** The avatar's letters, instead of the two taken from `name`. */
    'initials' => null,
    /** A badge in the panel's header, beside the name: the plan ("Pro"). */
    'plan' => null,
    /** The avatar alone, for a collapsed sidebar or a crowded header. The name moves into the button's label. */
    'compact' => false,
    /** start | end: which edge of the trigger the panel lines up with. It opens below, and above when there is no room. */
    'align' => 'end',
    /**
     * The URL sign-out POSTs to. A real form with a submit button, never a
     * link: signing out changes state, and a GET can be followed by a
     * prefetch or a crawler. Left out, there is no sign-out item.
     */
    'signOutAction' => null,
    'signOutLabel' => 'Sign out',
    /** Alpine expression holding the open state, as ds::dropdown's `open`. Optional: left out, it keeps its own. */
    'open' => null,
])

@php
    if (blank($name)) {
        throw new \InvalidArgumentException(
            'ds::user-menu needs a `name`: it is what the trigger shows and what a screen reader hears it called.'
        );
    }

    $align = $align === 'start' ? 'start' : 'end';
    $line = $meta ?? $email;

    // Hashed from who it is, not random: Livewire keys a morph by id, and the
    // panel's group points at the header by this one.
    $id = 'ds-user-menu-'.substr(md5($name.'|'.$email), 0, 8);

    // A session is what a token is kept in; a host without one gets none,
    // and a host whose token is not the session's passes its own in `csrf`.
    $token = $signOutAction !== null && ! isset($csrf) ? rescue(fn () => csrf_token(), null, false) : null;

    $trigger = implode(' ', [
        'flex min-w-0 items-center text-start motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        $compact
            ? 'rounded-full'
            : 'w-full gap-2.5 rounded-control p-1.5 hover:bg-fg/5',
    ]);

    // Adjacent groups in the default slot part by a hairline; a lone list of
    // items, or a single group, draws none.
    $groups = '*:[[role=group]+[role=group]]:mt-1 *:[[role=group]+[role=group]]:border-t *:[[role=group]+[role=group]]:border-divider *:[[role=group]+[role=group]]:pt-1';
@endphp

{{--
    A ds::dropdown with the avatar for its trigger, rendered once. Below the
    sm breakpoint it stays a dropdown, as wide as the window allows: a sheet
    is a second overlay with its own copy of the items, and the items here
    include a form, which must not exist twice.
--}}
<x-ds::dropdown :placement="'bottom-'.$align" :open="$open" :attributes="$attributes">
    <x-slot:trigger>
        <button type="button" class="{{ $trigger }}" @if ($compact) aria-label="{{ $name }}" @endif data-ds-user-menu-trigger>
            <x-ds::avatar :name="$name" :src="$src" :initials="$initials" size="sm" :decorative="true" />
            @unless ($compact)
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-body font-medium text-fg">{{ $name }}</span>
                    @if ($line)
                        <span class="block truncate text-meta text-fg-muted">{{ $line }}</span>
                    @endif
                </span>
                <x-ds::icon-swap from="chevron-down" preset="rotate" size="4" class="text-fg-muted" />
            @endunless
        </button>
    </x-slot:trigger>

    {{-- One group named by the header, so the first item a screen reader
         lands on says whose menu this is. --}}
    <div role="group" aria-labelledby="{{ $id }}-who" class="w-64 max-w-[calc(100vw-1rem)]">
        <div id="{{ $id }}-who" class="flex items-center gap-3 px-3 pt-2 pb-3">
            <x-ds::avatar :name="$name" :src="$src" :initials="$initials" size="md" :decorative="true" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-body font-medium text-fg">{{ $name }}</p>
                @if ($email)
                    <p class="truncate text-meta text-fg-muted">{{ $email }}</p>
                @endif
            </div>
            @if ($plan)
                <x-ds::badge tone="accent">{{ $plan }}</x-ds::badge>
            @endif
        </div>

        @if ($slot->isNotEmpty())
            <div class="border-t border-divider py-1 {{ $groups }}">{{ $slot }}</div>
        @endif

        @isset ($theme)
            <div class="border-t border-divider py-1">{{ $theme }}</div>
        @endisset

        @if ($signOutAction !== null)
            <form method="POST" action="{{ $signOutAction }}" class="border-t border-divider pt-1" data-ds-user-menu-sign-out>
                @isset ($csrf)
                    {{ $csrf }}
                @elseif ($token)
                    <input type="hidden" name="_token" value="{{ $token }}" autocomplete="off" />
                @endisset
                <x-ds::dropdown-item as="button" icon="arrow-right-start-on-rectangle">{{ $signOutLabel }}</x-ds::dropdown-item>
            </form>
        @endif
    </div>
</x-ds::dropdown>
