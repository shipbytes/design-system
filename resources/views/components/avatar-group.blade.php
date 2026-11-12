@props([
    /**
     * The people (or things) in the stack, in order: a list of
     * ['name' => …, 'src' => …, 'square' => …], arrays or objects. Only `name`
     * is needed; it gives the initials and the spoken list.
     */
    'avatars' => [],
    /** Show at most this many, then "+K". Null shows them all. */
    'max' => null,
    /** xs | sm | md | lg, passed to every avatar and to the "+K" chip. */
    'size' => 'sm',
    /** Names the group for a screen reader: "Project members". */
    'label' => null,
    /** The real count, when `avatars` holds only the first few. */
    'total' => null,
    /** Makes the "+K" chip a link, to wherever the full list lives. */
    'href' => null,
])

@php
    $avatars = collect($avatars)->values();
    $size = in_array($size, ['xs', 'sm', 'md', 'lg'], true) ? $size : 'sm';

    $shown = $max !== null ? $avatars->take(max(0, (int) $max)) : $avatars;
    $total = max((int) ($total ?? 0), $avatars->count());
    $more = $total - $shown->count();

    /*
     * How far each avatar tucks under the one before: about a fifth of its
     * width. A quarter looked tidier and cut the last letter of every pair of
     * initials, because the ring reaches 2px further than the margin does.
     */
    $overlap = [
        'xs' => '-ml-1',
        'sm' => '-ml-1.5',
        'md' => '-ml-2',
        'lg' => '-ml-2.5',
    ][$size];

    // The chip matches the avatar it follows, size for size.
    $chips = [
        'xs' => 'size-6 text-[0.625rem]',
        'sm' => 'size-8 text-meta',
        'md' => 'size-10 text-meta',
        'lg' => 'size-12 text-body',
    ];

    /*
     * The ring is the ground colour drawn around each avatar, which is what
     * makes the overlap read as a cut-out. surface, because the panel is where
     * a stack nearly always sits; on the sunken ground it shows as a thin light
     * outline, which still separates them. A hard white would glow in dark.
     *
     * Each avatar sits on an opaque surface backing, and the ring is on that.
     * In dark the avatar's tint is translucent, so without one the overlaps
     * showed through as darker lenses, one avatar visible inside the next.
     */
    $backing = fn (bool $first, bool $square = false) => implode(' ', array_filter([
        'relative inline-flex shrink-0 bg-surface ring-2 ring-surface',
        $square ? 'rounded-control' : 'rounded-full',
        $first ? null : $overlap,
    ]));

    /*
     * One sentence for a screen reader instead of N avatars and a "+3": every
     * avatar below is decorative, and this is the only thing announced. The
     * "+K" is in the sentence unless it is a link, which names itself.
     */
    $parts = $shown->map(fn ($a) => (string) data_get($a, 'name', ''))->filter()->values()->all();
    if ($more > 0 && ! $href) {
        $parts[] = "{$more} more";
    }
    $spoken = collect($parts)->join(', ', count($parts) > 2 ? ', and ' : ' and ');

    $groupAttributes = $attributes
        ->class(['inline-flex items-center'])
        ->merge(array_filter([
            'role' => $label ? 'group' : null,
            'aria-label' => $label,
        ], fn ($v) => $v !== null));

    $chipClasses = implode(' ', [
        'inline-flex shrink-0 items-center justify-center rounded-full',
        'bg-neutral-tint font-medium text-on-neutral-tint tabular-nums select-none',
        $chips[$size],
    ]);
@endphp

<div {{ $groupAttributes }}>
    @if ($spoken !== '')
        {{-- text-fg so the sentence has a colour of its own, like every other text. --}}
        <span class="sr-only text-fg">{{ $spoken }}</span>
    @endif

    @foreach ($shown as $avatar)
        @php $square = (bool) data_get($avatar, 'square', false); @endphp
        <span class="{{ $backing($loop->first, $square) }}" aria-hidden="true">
            <x-ds::avatar
                :name="data_get($avatar, 'name')"
                :src="data_get($avatar, 'src')"
                :square="$square"
                :size="$size"
                :decorative="true"
            />
        </span>
    @endforeach

    @if ($more > 0)
        @if ($href)
            <span class="{{ $backing($shown->isEmpty()) }}">
                <a href="{{ $href }}" class="{{ $chipClasses }} outline-offset-2 hover:bg-border-strong focus-visible:outline-2 focus-visible:outline-focus-ring" aria-label="{{ $more }} more">+{{ $more }}</a>
            </span>
        @else
            <span class="{{ $backing($shown->isEmpty()) }}" aria-hidden="true">
                <span class="{{ $chipClasses }}">+{{ $more }}</span>
            </span>
        @endif
    @endif
</div>
