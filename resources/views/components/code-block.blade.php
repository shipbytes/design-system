@props([
    /**
     * The code, as a string, printed exactly: the reliable route. The slot
     * works too, but Blade trims it and keeps the indentation of the view it
     * is written in, so slotted code has to start at the left margin.
     */
    'code' => null,
    /**
     * Code already highlighted by the host (Torchlight, Shiki, Phiki…), printed
     * UNESCAPED inside the same frame. Only ever the output of a highlighter
     * the host runs, never text a user typed.
     */
    'html' => null,
    /** What the code is written in, shown in the header. A label only: nothing is highlighted. */
    'language' => null,
    /** The file the code is from, shown in the header. */
    'filename' => null,
    /** The scroll region's accessible name. Left out: the filename, else "<language> code", else "Code". */
    'label' => null,
    /** A copy button in the header (ds::copy-button, which needs Alpine and the package's script). */
    'copy' => true,
    /** Numbers down the side. Drawn by CSS, so they are never selected or copied. */
    'lineNumbers' => false,
    /** Lines to mark, as "3,7-9", a number or an array of them. */
    'highlight' => null,
    /** Long lines wrap rather than scroll sideways. */
    'wrap' => false,
    /** At most this many lines tall; the rest scrolls inside. */
    'maxHeight' => null,
    /** With maxHeight: a "Show more" button under the code that lifts the limit. Needs Alpine and the package's script. */
    'expandable' => false,
    'moreLabel' => 'Show more',
    'lessLabel' => 'Show less',
])

@php
    use Shipbytes\BladeUi\Support\Code;

    if ($html !== null && $code !== null) {
        throw new \InvalidArgumentException(
            'ds::code-block takes `code` (plain text, escaped) or `html` (a highlighter\'s output, printed as given), not both.'
        );
    }

    // The slot is rendered Blade, so a tag written in it arrives as markup:
    // escaped here, it is shown as the text it was written as.
    $source = $code ?? (string) $slot;
    $lines = $html !== null ? Code::htmlLines((string) $html) : Code::lines((string) $source);
    $text = $html !== null ? Code::text((string) $html) : preg_replace('/(\r\n|\r|\n)$/', '', (string) $source, 1);

    $marked = Code::ranges($highlight);
    $digits = strlen((string) count($lines));

    $max = is_numeric($maxHeight) && (int) $maxHeight > 0 ? (int) $maxHeight : null;
    $expandable = $expandable && $max !== null;

    // Hashed from the code rather than random, so a Livewire morph patches
    // the block instead of replacing it.
    $id = ($attributes->get('id') ?? 'ds-code-'.substr(md5($text.$filename.$language), 0, 8)).'-code';
    $name = $label ?? $filename ?? ($language ? "{$language} code" : 'Code');
    $header = $filename !== null || $language !== null;

    /*
     * The token colours a host's highlighter can map to, from theme.css on
     * [data-ds-code]: --ds-code-fg, -bg, -comment, -keyword, -string,
     * -constant, -function, -parameter, -punctuation, -link, -highlight and
     * -highlight-edge. Read here through var() so a highlighter theme
     * written in the same variables follows light and dark with the rest.
     */
    $pre = implode(' ', [
        'block overflow-auto py-3 font-mono text-body text-(--ds-code-fg) [tab-size:4]',
        $wrap ? 'whitespace-pre-wrap break-words' : 'whitespace-pre',
        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring',
        // 1.5rem a line is text-body's line height; 1.5rem more is py-3.
        $max !== null ? 'max-h-[calc(var(--ds-code-lines)*1.5rem+1.5rem)]' : '',
        $expandable ? 'group-data-[ds-state=expanded]/clamp:max-h-none' : '',
        // Room for the copy button when there is no header to hold it.
        $copy && ! $header ? 'pe-12' : '',
    ]);

    /*
     * Each line is a block in a grid, so a highlighted line's ground runs the
     * full width of the scroll, not the width of its text. In a grid the
     * newline between two lines is white space only, which a grid does not
     * render, so it costs no blank line. A browser copying a selection gives
     * each block a line of its own, but a block with no text in it gives
     * nothing, and a blank line in the code would vanish from what a reader
     * copies: so an empty line holds a line break, which it keeps.
     */
    $line = implode(' ', [
        'block min-h-6 px-4',
        $lineNumbers ? 'relative ps-[calc(var(--ds-code-gutter)+2rem)] before:absolute before:start-4 before:w-(--ds-code-gutter) before:text-end before:text-(--ds-code-comment) before:select-none before:content-[counter(ds-line)] before:[counter-increment:ds-line]' : '',
    ]);
    $mark = 'bg-(--ds-code-highlight) text-inherit shadow-[inset_2px_0_0_var(--ds-code-highlight-edge)]';

    $state = $expandable
        ? \Shipbytes\BladeUi\Support\Behaviour::data('dsClamp', ['more' => $moreLabel, 'less' => $lessLabel])
        : null;
@endphp

<figure
    data-ds-code
    @if ($state) x-data="{{ $state }}" @endif
    {{ $attributes->merge(['class' => 'group/clamp relative min-w-0 overflow-hidden rounded-panel border border-border bg-(--ds-code-bg) text-fg-body']) }}
>
    @if ($header)
        <figcaption class="flex min-h-10 items-center gap-3 border-b border-border py-1.5 ps-4 pe-1.5">
            @if ($filename !== null)
                <span class="min-w-0 truncate font-mono text-meta text-fg">{{ $filename }}</span>
            @endif
            @if ($language !== null)
                <span class="ms-auto shrink-0 text-meta text-fg-muted">{{ $language }}</span>
            @endif
            @if ($copy)
                <x-ds::copy-button :value="$text" label="Copy code" :iconOnly="true" variant="ghost" size="sm" class="shrink-0 {{ $language === null ? 'ms-auto' : '' }}" />
            @endif
        </figcaption>
    @elseif ($copy)
        <div class="absolute end-1.5 top-1.5 z-10">
            <x-ds::copy-button :value="$text" label="Copy code" :iconOnly="true" variant="ghost" size="sm" />
        </div>
    @endif

    {{-- One line from pre to /pre: anything between the tags here would be
         part of the code. dir=ltr because code reads left to right on a
         right-to-left page too. tabindex so a keyboard can scroll it. --}}
    <pre id="{{ $id }}" role="region" aria-label="{{ $name }}" tabindex="0" dir="ltr" data-ds-clamp-body class="{{ $pre }}" @if ($max !== null) style="--ds-code-lines: {{ $max }}" @endif><code @class(['grid min-w-full [counter-reset:ds-line]', 'w-max' => ! $wrap]) @if ($lineNumbers) style="--ds-code-gutter: {{ $digits }}ch" @endif>@foreach ($lines as $i => $content)@if (isset($marked[$i + 1]))<mark class="{{ $line }} {{ $mark }}">{!! $content !!}{!! strip_tags($content) === '' ? '<br>' : '' !!}</mark>@else<span class="{{ $line }}">{!! $content !!}{!! strip_tags($content) === '' ? '<br>' : '' !!}</span>@endif{{ $loop->last ? '' : "\n" }}@endforeach</code></pre>

    @if ($expandable)
        {{-- hidden until dsClamp has measured that the code runs past the
             limit: without the script there is nothing to press, and the
             code still scrolls inside its limit. --}}
        <div class="border-t border-border px-1.5 py-1.5 has-[[hidden]]:hidden">
            <x-ds::button variant="ghost" size="sm" hidden data-ds-clamp-toggle aria-expanded="false" aria-controls="{{ $id }}">
                <span data-ds-clamp-label>{{ $moreLabel }}</span>
            </x-ds::button>
        </div>
    @endif
</figure>
