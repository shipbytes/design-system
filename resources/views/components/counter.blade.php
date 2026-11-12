@props([
    /**
     * The number. Rendered, formatted, from PHP, so it is right with no
     * script. Bind it from Alpine with `::value="count"` (or
     * `x-bind:value`), and the counter follows the host's state; that
     * route needs the package's script.
     */
    'value' => 0,
    /**
     * How it reads: ['decimals' => 1, 'grouping' => true, 'prefix' => '$',
     * 'suffix' => '%', 'compact' => true, 'locale' => 'en']. All optional; see
     * Support\Chart::format().
     */
    'format' => [],
    /** sm | md | lg | xl — the meta, body, title or display size. Default: the surrounding text's. */
    'size' => null,
    /** hint | popover | dialog | slide — the motion token the roll is timed by. */
    'duration' => 'slide',
    /** Roll up from zero the first time it scrolls into view. */
    'animateIn' => false,
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    $format = is_array($format) ? $format : [];
    $options = Chart::formatOptions($format);
    $shown = Chart::format($value, $format);

    /*
     * An Alpine binding on `value` is lifted off the attributes, where it
     * would only set an attribute nothing reads, and handed to the behaviour
     * as a getter, evaluated in the host's scope.
     */
    $bound = $attributes->get('x-bind:value') ?? $attributes->get(':value');
    $attributes = $attributes->except(['x-bind:value', ':value']);

    $scope = $bound !== null
        ? ['x-data' => (string) \Shipbytes\BladeUi\Support\Behaviour::data('dsRoll', '{ value: () => ('.$bound.'), in: '.($animateIn ? 'true' : 'false').' }')]
        : ['x-data' => '', ($animateIn ? 'x-ds-roll.in' : 'x-ds-roll') => ''];

    // Literal classes, looked up: a class built from a prop is invisible to
    // Tailwind's scanner (the icon trap in CLAUDE.md).
    $sizes = ['sm' => 'text-meta', 'md' => 'text-body', 'lg' => 'text-title', 'xl' => 'text-display'];
    $durations = [
        'hint' => 'motion-safe:duration-hint-enter',
        'popover' => 'motion-safe:duration-popover-enter',
        'dialog' => 'motion-safe:duration-dialog-enter',
        'slide' => 'motion-safe:duration-slide-enter',
    ];

    $root = $attributes
        ->merge([
            'data-ds-counter' => '',
            'data-ds-value' => is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.') : '',
            'data-ds-format' => json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ] + $scope)
        ->class(['inline-block whitespace-nowrap tabular-nums', $sizes[$size] ?? '']);
@endphp

<span {{ $root }}>
    {{-- The one number assistive technology reads; the face is for the eye.
         Not selectable, so copying the face does not copy it twice. --}}
    <span class="sr-only select-none" data-ds-counter-text>{{ $shown }}</span>
    <span aria-hidden="true" data-ds-counter-face>{{ $shown }}</span>
    {{-- One rolling column, cloned by the script per digit: a strip of 0 to
         9, slid by --ds-digit, in a box one digit high that an invisible
         digit sizes, so a column is exactly as wide as the digit it ends on. --}}
    <template data-ds-counter-digit><span class="relative inline-block overflow-hidden align-bottom"><span class="invisible">0</span><span data-ds-counter-strip class="absolute inset-x-0 top-0 flex translate-y-(--ds-digit) flex-col motion-safe:transition-[translate] motion-safe:ease-enter {{ $durations[$duration] ?? $durations['slide'] }}"><span>0</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span></span></span></template>
</span>
