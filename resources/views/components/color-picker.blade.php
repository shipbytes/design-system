@props([
    /** Submitted field name. Posts "#rrggbb", "#rrggbbaa" with `alpha`, or the `format` string. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    /** Any colour the field reads: "#336699", "abc", "rgb(51, 102, 153)", "hsl(210 50% 40%)", "teal". */
    'value' => null,
    /** Offer an opacity slider and post the alpha channel too. */
    'alpha' => false,
    /** What posts: hex ("#336699"), rgb ("rgb(51, 102, 153)") or hsl ("hsl(210, 50%, 40%)"). */
    'format' => 'hex',
    /** Preset colours: a list, or colour => name. Each name is what a screen reader says. */
    'swatches' => [],
    /** With the script, a button that picks a colour off the screen, where the browser has EyeDropper. */
    'eyedropper' => false,
    /** A colour to measure against: shows the WCAG contrast ratio with AA and AAA marks. */
    'contrastWith' => null,
    /** Only the swatches, as plain radios: no panel, no text field, and no script. */
    'presetsOnly' => false,
    'placeholder' => '#000000',
    'disabled' => false,
])

@php
    use Shipbytes\BladeUi\Support\Colour;

    $format = in_array($format, ['hex', 'rgb', 'hsl'], true) ? $format : 'hex';
    $alpha = (bool) $alpha;

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    $given = trim((string) $value);
    $colour = Colour::parse($given);
    if ($colour !== null && ! $alpha) {
        $colour[3] = 1.0;
    }
    $posted = $colour === null ? '' : Colour::write($colour, $format, $alpha);

    // Presets as [hex, posted value, name], each checked when it is the value.
    $presets = [];
    foreach ((array) $swatches as $key => $item) {
        [$raw, $word] = is_string($key) ? [$key, (string) $item] : [(string) $item, null];
        $c = Colour::parse($raw);
        if ($c === null) {
            throw new \InvalidArgumentException("ds::color-picker cannot read the swatch [{$raw}]: give #rrggbb, rgb(), hsl() or a basic CSS name.");
        }
        if (! $alpha) {
            $c[3] = 1.0;
        }
        $out = Colour::write($c, $format, $alpha);
        $presets[] = ['hex' => Colour::hex($c, $c[3] < 1), 'value' => $out, 'name' => $word ?? strtoupper(Colour::hex($c, $c[3] < 1)), 'checked' => $out === $posted, 'ink' => \Shipbytes\BladeUi\Support\Ink::pick(Colour::hex($c)) === 'dark' ? 'text-zinc-950' : 'text-white'];
    }

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $others = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    $describedBy = trim(($contrastWith ? "{$id}-contrast " : '').($error ? "{$id}-error" : ($help ? "{$id}-help" : '')));

    // A swatch: a ring for the edge (white on white is otherwise invisible),
    // a ring outside when checked, the system ring on keyboard focus.
    $swatch = 'relative flex size-7 cursor-pointer items-center justify-center rounded-full ring-1 ring-inset ring-fg/15 has-checked:ring-2 has-checked:ring-offset-2 has-checked:ring-offset-surface has-checked:ring-fg has-focus-visible:outline-2 has-focus-visible:outline-offset-4 has-focus-visible:outline-focus-ring has-disabled:cursor-not-allowed has-disabled:opacity-50';
    $checker = 'bg-[conic-gradient(#d4d4d8_25%,#fff_0_50%,#d4d4d8_0_75%,#fff_0)] bg-size-[8px_8px]';
@endphp

@if ($presetsOnly)
    {{-- Native radios and nothing else: arrows, Tab, the form and x-model
         are the browser's. --}}
    <div {{ $attributes->only('class') }}>
        @if ($label)
            <span id="{{ $id }}-label" class="{{ $field['label'] }}">{{ $label }}</span>
        @endif
        <div
            role="radiogroup"
            id="{{ $id }}"
            @if ($label) aria-labelledby="{{ $id }}-label" @endif
            @if ($error || $help) aria-describedby="{{ $error ? "{$id}-error" : "{$id}-help" }}" @endif
            @if ($error) aria-invalid="true" @endif
            @class(['flex flex-wrap gap-3 p-1', 'mt-1.5' => (bool) $label])
        >
            @foreach ($presets as $preset)
                <label class="{{ $swatch }} {{ $preset['ink'] }}" style="background-color: {{ $preset['hex'] }}">
                    <input type="radio" class="peer sr-only" name="{{ $name }}" value="{{ $preset['value'] }}" {{ $model }} {{ $others }} @checked($preset['checked']) @disabled($disabled) />
                    <x-ds::icon name="check" variant="mini" size="4" class="hidden peer-checked:block" />
                    <span class="sr-only">{{ $preset['name'] }}</span>
                </label>
            @endforeach
        </div>
        @if ($error)
            <p id="{{ $id }}-error" class="{{ $field['error'] }}">
                <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
                <span>{{ $error }}</span>
            </p>
        @elseif ($help)
            <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
        @endif
    </div>
@else
    @php
        $hsv = $colour ? Colour::toHsv($colour) : [0.0, 0.0, 0.0];
        $a = $colour[3] ?? 1.0;
        $hsl = $colour ? Colour::toHsl($colour) : [0.0, 0.0, 0.0];
        $text = $colour ? Colour::hex($colour, $alpha && $a < 1) : $given;
        $n = fn (float $v, int $places = 4) => rtrim(rtrim(number_format($v, $places, '.', ''), '0'), '.') ?: '0';
        $rgbCss = $colour ? "rgb({$colour[0]} {$colour[1]} {$colour[2]})" : 'transparent';

        // Where the thumbs sit and what the swatch is, as variables the
        // panel's classes read and the script rewrites.
        $vars = implode('; ', [
            '--ds-colour: '.($colour ? "rgb({$colour[0]} {$colour[1]} {$colour[2]} / {$n($a)})" : 'transparent'),
            "--ds-colour-solid: {$rgbCss}",
            "--ds-colour-hue: hsl({$n($hsv[0])} 100% 50%)",
            '--ds-colour-h: '.$n($hsv[0] / 360),
            '--ds-colour-s: '.$n($hsv[1] / 100),
            '--ds-colour-v: '.$n($hsv[2] / 100),
            '--ds-colour-a: '.$n($a),
        ]);

        $against = $contrastWith !== null ? Colour::parse((string) $contrastWith) : null;
        if ($contrastWith !== null && $against === null) {
            throw new \InvalidArgumentException("ds::color-picker cannot read contrastWith [{$contrastWith}].");
        }
        $ratio = $against && $colour ? Colour::ratio(Colour::contrast($colour, $against)) : null;

        $words = [
            'saturation' => 'Saturation',
            'brightness' => 'Brightness',
            'hue' => 'Hue',
            'alpha' => 'Opacity',
            'none' => 'No colour',
        ];

        $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsColour', array_filter([
            'alpha' => $alpha,
            'format' => $format,
            'invalid' => (bool) $error,
            'contrastWith' => $against ? Colour::hex($against) : null,
            'words' => $words,
        ], fn ($v) => $v !== null && $v !== false));

        $js = fn ($v) => \Illuminate\Support\Js::from($v);
        $pick = ($label ?? 'Colour').' picker';

        $textField = $others->merge((new \Illuminate\View\ComponentAttributeBag(array_filter([
            'type' => 'text',
            'id' => $id,
            'value' => $text,
            'placeholder' => $placeholder,
            'autocomplete' => 'off',
            'spellcheck' => 'false',
            'aria-describedby' => $describedBy ?: null,
            'aria-invalid' => $error || ($given !== '' && $colour === null) ? 'true' : null,
            'disabled' => $disabled ? true : null,
            'class' => $field['control']((bool) $error, false, (bool) $disabled).' font-mono',
        ], fn ($v) => $v !== null)))->getAttributes());

        $hidden = new \Illuminate\View\ComponentAttributeBag(array_filter([
            'type' => 'hidden',
            'value' => e($posted),
            'disabled' => $disabled ? true : null,
            'x-bind:name' => "\$data.dsReady ? {$js((string) $name)} : null",
        ], fn ($v) => $v !== null));

        $thumb = 'pointer-events-none absolute size-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow-raised ring-1 ring-black/40';
        $track = 'relative touch-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring aria-disabled:cursor-not-allowed';
        $tab = 'flex-1 rounded-[calc(var(--ds-radius-control)-2px)] px-2 py-1 text-meta font-medium text-fg-muted hover:text-fg aria-selected:bg-surface aria-selected:text-fg aria-selected:shadow-raised focus-visible:outline-2 focus-visible:outline-focus-ring motion-safe:transition-colors motion-safe:duration-hint-enter motion-safe:ease-enter';
        $part = $field['control'](false, false, (bool) $disabled, 'sm').' tabular-nums [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';
        $parts = [
            'rgb' => [['r', 'R', 'Red', 255], ['g', 'G', 'Green', 255], ['b', 'B', 'Blue', 255]],
            'hsl' => [['h', 'H', 'Hue', 360], ['s', 'S', 'Saturation', 100], ['l', 'L', 'Lightness', 100]],
        ];
        $partValues = [
            'r' => $colour[0] ?? '', 'g' => $colour[1] ?? '', 'b' => $colour[2] ?? '',
            'h' => $colour ? (int) round($hsl[0]) : '', 's' => $colour ? (int) round($hsl[1]) : '', 'l' => $colour ? (int) round($hsl[2]) : '',
            'a' => $colour ? (int) round($a * 100) : '',
        ];
        $valuetext = fn () => "{$words['saturation']} ".round($hsv[1])."%, {$words['brightness']} ".round($hsv[2]).'%';
    @endphp

    <div x-data="{{ $state }}" x-modelable="dsValue" {{ $model }} data-ds-colour style="{{ $vars }}" {{ $attributes->only('class') }}>
        @if ($label)
            <label for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
        @endif

        <input {{ $hidden }} data-ds-colour-value />

        <div @class(['flex items-center gap-2', 'mt-1.5' => (bool) $label])>
            {{-- Without the script the browser's own picker, which posts
                 #rrggbb under the name and copies into the text field; with
                 it, hidden and nameless. The text field copies a valid hex
                 back, so either can be used with no script at all. --}}
            <input
                type="color"
                name="{{ $name }}"
                value="{{ $colour ? Colour::hex($colour) : '#000000' }}"
                aria-label="{{ $pick }}"
                x-bind:name="$data.dsReady ? null : {{ $js((string) $name) }}"
                x-bind:hidden="$data.dsReady"
                oninput="this.parentNode.querySelector('[data-ds-colour-text=field]').value = this.value"
                class="h-11 w-12 shrink-0 cursor-pointer rounded-control border border-border bg-surface p-1 sm:h-9 sm:w-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed"
                data-ds-colour-native
                @disabled($disabled)
            />

            <span class="contents" hidden x-bind:hidden="! $data.dsReady">
                <x-ds::popover :label="$pick" placement="bottom-start" width="md">
                    <x-slot:trigger>
                        <button type="button" aria-haspopup="dialog" class="relative size-11 shrink-0 overflow-hidden rounded-control border border-border bg-clip-padding {{ $checker }} sm:size-9 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-50" data-ds-colour-trigger @disabled($disabled)>
                            <span class="absolute inset-0 bg-(--ds-colour)"></span>
                            <span class="sr-only">{{ $pick }}: <span data-ds-colour-said>{{ $colour ? strtoupper($text) : $words['none'] }}</span></span>
                        </button>
                    </x-slot:trigger>

                    {{-- The picture of the colour does not turn round in a
                         right-to-left page: saturation grows to the right
                         and hue runs red to red, as on every picker. --}}
                    <div class="flex flex-col gap-4" dir="ltr">
                        <div
                            role="slider"
                            tabindex="{{ $disabled ? '-1' : '0' }}"
                            aria-label="{{ $words['saturation'] }}, {{ $words['brightness'] }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="{{ round($hsv[1]) }}"
                            aria-valuetext="{{ $valuetext() }}"
                            @if ($disabled) aria-disabled="true" @endif
                            class="{{ $track }} h-36 cursor-crosshair rounded-control bg-(--ds-colour-hue) bg-[linear-gradient(to_top,#000,transparent),linear-gradient(to_right,#fff,transparent)]"
                            data-ds-colour-slider="area"
                        >
                            <span class="{{ $thumb }} left-[calc(var(--ds-colour-s)*100%)] top-[calc((1-var(--ds-colour-v))*100%)] bg-(--ds-colour-solid)"></span>
                        </div>

                        <div
                            role="slider"
                            tabindex="{{ $disabled ? '-1' : '0' }}"
                            aria-label="{{ $words['hue'] }}"
                            aria-valuemin="0"
                            aria-valuemax="360"
                            aria-valuenow="{{ round($hsv[0]) }}"
                            aria-valuetext="{{ round($hsv[0]) }}°"
                            @if ($disabled) aria-disabled="true" @endif
                            class="{{ $track }} h-3 cursor-pointer rounded-full bg-[linear-gradient(to_right,#f00,#ff0,#0f0,#0ff,#00f,#f0f,#f00)]"
                            data-ds-colour-slider="hue"
                        >
                            <span class="{{ $thumb }} top-1/2 left-[calc(var(--ds-colour-h)*100%)] bg-(--ds-colour-hue)"></span>
                        </div>

                        @if ($alpha)
                            <div
                                role="slider"
                                tabindex="{{ $disabled ? '-1' : '0' }}"
                                aria-label="{{ $words['alpha'] }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ round($a * 100) }}"
                                aria-valuetext="{{ round($a * 100) }}%"
                                @if ($disabled) aria-disabled="true" @endif
                                class="{{ $track }} h-3 cursor-pointer rounded-full {{ $checker }}"
                                data-ds-colour-slider="alpha"
                            >
                                <span class="absolute inset-0 rounded-full bg-linear-to-r from-transparent to-(--ds-colour-solid)"></span>
                                <span class="{{ $thumb }} top-1/2 left-[calc(var(--ds-colour-a)*100%)] bg-(--ds-colour)"></span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            <div role="tablist" aria-label="Format" class="flex flex-1 gap-0.5 rounded-control bg-surface-subtle p-0.5" data-ds-colour-tabs>
                                @foreach (['hex' => 'HEX', 'rgb' => 'RGB', 'hsl' => 'HSL'] as $key => $word)
                                    <button type="button" role="tab" id="{{ $id }}-tab-{{ $key }}" aria-controls="{{ $id }}-pane-{{ $key }}" aria-selected="{{ $key === $format ? 'true' : 'false' }}" tabindex="{{ $key === $format ? '0' : '-1' }}" class="{{ $tab }}" data-ds-colour-tab="{{ $key }}">{{ $word }}</button>
                                @endforeach
                            </div>
                            @if ($eyedropper)
                                {{-- Shown where the browser has EyeDropper, by a binding a morph keeps. --}}
                                <button type="button" hidden x-bind:hidden="! ($data.dsReady &amp;&amp; typeof window.EyeDropper === 'function')" aria-label="Pick a colour from the screen" class="flex size-8 shrink-0 items-center justify-center rounded-control text-fg-muted hover:bg-fg/5 hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring motion-safe:transition-colors motion-safe:duration-hint-enter motion-safe:ease-enter" data-ds-colour-eyedropper>
                                    <x-ds::icon name="eye-dropper" size="4" />
                                </button>
                            @endif
                        </div>

                        <div role="tabpanel" id="{{ $id }}-pane-hex" aria-labelledby="{{ $id }}-tab-hex" @if ($format !== 'hex') hidden @endif data-ds-colour-pane="hex">
                            <span class="{{ $field['shell']((bool) $disabled) }}">
                                <input type="text" aria-label="Hex" value="{{ $text }}" autocomplete="off" spellcheck="false" class="{{ $field['control'](false, false, (bool) $disabled, 'sm') }} font-mono" data-ds-colour-text="panel" @disabled($disabled) />
                            </span>
                        </div>
                        @foreach ($parts as $key => $row)
                            <div role="tabpanel" id="{{ $id }}-pane-{{ $key }}" aria-labelledby="{{ $id }}-tab-{{ $key }}" @if ($format !== $key) hidden @endif @class(['grid gap-2', 'grid-cols-4' => $alpha, 'grid-cols-3' => ! $alpha]) data-ds-colour-pane="{{ $key }}">
                                @foreach ($alpha ? [...$row, ['a', 'A', $words['alpha'], 100]] : $row as [$k, $letter, $word, $max])
                                    <label class="flex flex-col gap-1 text-meta text-fg-muted">
                                        <span aria-hidden="true">{{ $letter }}</span>
                                        <span class="{{ $field['shell']((bool) $disabled) }}">
                                            <input type="number" min="0" max="{{ $max }}" step="1" aria-label="{{ $word }}" value="{{ $partValues[$k] }}" inputmode="numeric" class="{{ $part }}" data-ds-colour-part="{{ $k }}" @disabled($disabled) />
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach

                        @if ($presets)
                            {{-- Radios for the arrows, kept out of the form by a
                                 form attribute naming no form: the colour posts
                                 once, from the hidden input. --}}
                            <div role="radiogroup" aria-label="Presets" class="flex flex-wrap gap-2.5 p-1">
                                @foreach ($presets as $preset)
                                    <label class="{{ $swatch }} {{ $preset['ink'] }} size-6" style="background-color: {{ $preset['hex'] }}">
                                        <input type="radio" class="peer sr-only" name="{{ $id }}-preset" form="{{ $id }}-no-form" value="{{ $preset['hex'] }}" data-ds-colour-preset @checked($preset['checked']) @disabled($disabled) />
                                        <x-ds::icon name="check" variant="micro" size="3" class="hidden peer-checked:block" />
                                        <span class="sr-only">{{ $preset['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-ds::popover>
            </span>

            <span class="{{ $field['shell']((bool) $disabled) }}">
                <input {{ $textField }} data-ds-colour-text="field" oninput="/^#[\da-f]{6}$/i.test(this.value) &amp;&amp; (this.closest('[data-ds-colour]').querySelector('[data-ds-colour-native]').value = this.value)" />
            </span>
        </div>

        @if ($against)
            {{-- Read from the shown ratio, so a mark and its number never
                 disagree. Not a live region: it changes with every pixel of
                 a drag. The text field is described by it. --}}
            <p id="{{ $id }}-contrast" class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-meta text-fg-muted" data-ds-colour-contrast>
                <span>Contrast with {{ strtoupper(Colour::hex($against)) }}: <span class="font-medium text-fg tabular-nums" data-ds-colour-ratio>{{ $ratio !== null ? "{$ratio}:1" : '–' }}</span></span>
                @foreach (['AA' => 4.5, 'AAA' => 7] as $level => $needs)
                    @php $passes = $ratio !== null && (float) $ratio >= $needs; @endphp
                    <span class="group/mark inline-flex items-center gap-0.5 rounded-chip bg-danger-tint px-1.5 py-px font-medium text-on-danger-tint data-pass:bg-success-tint data-pass:text-on-success-tint" data-ds-colour-mark="{{ $needs }}" @if ($passes) data-pass @endif>
                        <x-ds::icon name="check" variant="micro" size="3" class="hidden group-data-pass/mark:block" />
                        <x-ds::icon name="x-mark" variant="micro" size="3" class="group-data-pass/mark:hidden" />
                        {{ $level }}<span class="sr-only"> <span class="group-data-pass/mark:hidden">fails</span><span class="hidden group-data-pass/mark:inline">passes</span></span>
                    </span>
                @endforeach
            </p>
        @endif

        @if ($error)
            <p id="{{ $id }}-error" class="{{ $field['error'] }}">
                <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
                <span>{{ $error }}</span>
            </p>
        @elseif ($help)
            <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
        @endif
    </div>
@endif
