@props([
    /** The id of the password input it measures. */
    'for',
    /** Shorter than this, a password is the weakest level whatever else it has. */
    'minLength' => 8,
    /** The four level names, weakest first. Pass fewer to replace only the first ones. */
    'labels' => [],
])

@php
    // A hint for the person choosing a password, not a security control: the
    // score is a small heuristic in the package's script, and the server's own
    // rules are the ones that decide. The docs say so in as many words.
    $labels = array_replace(['Weak', 'Fair', 'Good', 'Strong'], array_slice(array_values((array) $labels), 0, 4));

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsPasswordStrength', [
        'for' => (string) $for,
        'minLength' => max(1, (int) $minLength),
        'labels' => array_map('strval', $labels),
    ]);

    /*
     * One bar per level, lit up to the current one. The script sets data-level
     * on the root and nothing else; every colour is chosen here, off that
     * attribute, so the classes are literals the consumer's Tailwind can see.
     * Red, then amber, then green for the top two: the count of lit bars and
     * the words tell Good from Strong, so colour is never the only signal.
     */
    $bars = [
        'group-data-[level=1]:bg-danger group-data-[level=2]:bg-warning group-data-[level=3]:bg-success group-data-[level=4]:bg-success',
        'group-data-[level=2]:bg-warning group-data-[level=3]:bg-success group-data-[level=4]:bg-success',
        'group-data-[level=3]:bg-success group-data-[level=4]:bg-success',
        'group-data-[level=4]:bg-success',
    ];
@endphp

<div x-data="{{ $state }}" data-level="0" {{ $attributes->class(['group mt-2']) }}>
    <div class="flex gap-1" aria-hidden="true">
        @foreach ($bars as $lit)
            <span class="h-1 flex-1 rounded-full bg-fg/10 motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter {{ $lit }}"></span>
        @endforeach
    </div>

    {{-- Atomic, so a change is read as the whole sentence ("Password strength:
         Good") rather than the one word that changed. --}}
    <p class="mt-1.5 min-h-4 text-meta text-fg-muted" aria-live="polite" aria-atomic="true">
        <span class="sr-only">Password strength:</span>
        <span class="font-medium text-fg" data-ds-strength-text></span>
    </p>
</div>
