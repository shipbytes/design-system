@props([
    /** Submitted field name. The text posts here; the ids of the rows mentioned in it post as `{name}_mentions[]`. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    'value' => null,
    /** The character that opens the list, "@"; or trigger => rows, ['@' => $people, '#' => $issues]. */
    'triggers' => '@',
    /** The rows for a single trigger: each id, label, and optionally meta, src or initials. */
    'items' => [],
    /** What a chosen row writes: "@" is the trigger, "label" and "id" the row's. "@[label](id)" writes a token a server can parse. */
    'insert' => '@label',
    /** Characters to type after the trigger before the list opens. */
    'minChars' => 0,
    /** The most rows the list shows at once. */
    'max' => 8,
    'rows' => 3,
    /** false: a one-line text field instead of a textarea. */
    'multiline' => true,
    /** Backspace just after a mention deletes all of it, not one letter. */
    'atomic' => false,
    /** The host is fetching rows for the last `search`: the list says so instead of "No matches". */
    'loading' => false,
    /** Posts the ids under this name instead of `{name}_mentions[]`. */
    'mentionsName' => null,
    'placeholder' => null,
    'disabled' => false,
    'listLabel' => 'Suggestions',
    'emptyText' => 'No matches',
    'loadingText' => 'Loading…',
])

@php
    use Shipbytes\BladeUi\Support\Mention;

    $lists = is_string($triggers)
        ? [$triggers => $items]
        : (array_is_list((array) $triggers) ? array_fill_keys((array) $triggers, $items) : (array) $triggers);

    foreach (array_keys($lists) as $trigger) {
        if (mb_strlen((string) $trigger) !== 1 || trim((string) $trigger) === '') {
            throw new \InvalidArgumentException("ds::mention-input triggers are single characters, like @ or #; got [{$trigger}].");
        }
    }

    // Rows as plain arrays, from arrays or objects.
    $lists = array_map(fn ($rows) => collect($rows)->map(fn ($row) => [
        'id' => (string) data_get($row, 'id'),
        'label' => (string) data_get($row, 'label'),
        'meta' => data_get($row, 'meta'),
        'src' => data_get($row, 'src'),
        'initials' => data_get($row, 'initials'),
    ])->values()->all(), $lists);

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $listbox = \Shipbytes\BladeUi\Support\Listbox::recipe();

    $text = (string) $value;
    $idsName = $mentionsName ?? (str_ends_with($name, ']') ? substr($name, 0, -1).'_mentions][]' : "{$name}_mentions[]");
    $found = Mention::found($text, $lists, (string) $insert);

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $others = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsMention', [
        'triggers' => array_map('strval', array_keys($lists)),
        'insert' => (string) $insert,
        'minChars' => max(0, (int) $minChars),
        'max' => max(1, (int) $max),
        'atomic' => (bool) $atomic,
    ]);

    $js = fn ($v) => \Illuminate\Support\Js::from($v);
    $listId = "{$id}-list";

    /*
     * A combobox only once the script is there to open its list: without it
     * this is an ordinary field, and saying "combobox, collapsed" would
     * promise a list that never comes. Bindings, so a morph keeps them.
     */
    $control = $others->merge((new \Illuminate\View\ComponentAttributeBag(array_filter([
        'id' => $id,
        'name' => $name,
        'type' => $multiline ? null : 'text',
        'value' => $multiline ? null : $text,
        'rows' => $multiline ? (int) $rows : null,
        'placeholder' => $placeholder,
        'autocomplete' => 'off',
        'aria-describedby' => $describedBy,
        'aria-invalid' => $error ? 'true' : null,
        'disabled' => $disabled ? true : null,
        'x-bind:role' => "\$data.dsReady ? 'combobox' : null",
        'x-bind:aria-autocomplete' => "\$data.dsReady ? 'list' : null",
        'x-bind:aria-expanded' => '$data.dsReady ? String(dsOpen) : null',
        'x-bind:aria-controls' => "\$data.dsReady ? {$js($listId)} : null",
        'class' => $field['control']((bool) $error, false, (bool) $disabled).($multiline ? ' resize-y' : ''),
    ], fn ($v) => $v !== null)))->getAttributes());

    // Hangs off the caret with the script; under the field, at its start,
    // until then. Not w-full: a caret list is about a name, not the field.
    // No enter or leave animation: it opens and closes with keystrokes, a
    // few milliseconds apart, and a list still fading out from the last
    // close while the next opens is noise (and was once left showing).
    // Placed at the trigger, not the caret as it moves: it would slide a
    // character's width with every letter typed.
    $panel = 'absolute start-0 top-full z-50 mt-1 w-64 max-w-[calc(100vw-1rem)] overflow-y-auto rounded-control border border-border bg-surface py-1 shadow-float origin-top data-[side=top]:origin-bottom';
@endphp

<div x-data="{{ $state }}" x-modelable="dsValue" {{ $model }} data-ds-mention {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <span @class([$field['shell']((bool) $disabled), 'mt-1.5' => (bool) $label])>
        @if ($multiline)
            <textarea {{ $control }} data-ds-mention-input>{{ $text }}</textarea>
        @else
            <input {{ $control }} data-ds-mention-input />
        @endif

        <div
            x-cloak
            x-show="dsOpen"
            x-ds-anchor="{ open: dsOpen, reference: $el.previousElementSibling, at: dsCaret, placement: 'bottom-start', offset: 4 }"
            class="{{ $panel }}"
            data-ds-mention-panel
        >
            <div role="listbox" id="{{ $listId }}" aria-label="{{ $listLabel }}" aria-busy="{{ $loading ? 'true' : 'false' }}" class="flex max-h-60 flex-col" data-ds-mention-list>
                @foreach ($lists as $trigger => $rows)
                    @foreach ($rows as $row)
                        <div
                            role="option"
                            id="{{ $id }}-option-{{ $loop->parent->index }}-{{ $loop->index }}"
                            aria-selected="false"
                            data-value="{{ $row['id'] }}"
                            data-ds-label="{{ $row['label'] }}"
                            data-ds-keywords="{{ $row['meta'] }}"
                            data-ds-trigger="{{ $trigger }}"
                            data-ds-mention-option
                            class="{{ $listbox['activeOption'] }}"
                        >
                            @if ($row['src'] || $row['initials'])
                                <x-ds::avatar size="xs" :src="$row['src']" :initials="$row['initials']" :name="$row['label']" :decorative="true" />
                            @endif
                            <span class="min-w-0 truncate">{{ $row['label'] }}</span>
                            @if ($row['meta'])
                                <span class="ms-auto min-w-0 shrink truncate text-meta text-fg-muted">{{ $row['meta'] }}</span>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
            <div role="status">
                <p class="{{ $listbox['empty'] }}" hidden data-ds-mention-empty>{{ $emptyText }}</p>
                @if ($loading)
                    <p class="{{ $listbox['empty'] }}">{{ $loadingText }}</p>
                @endif
            </div>
        </div>
    </span>

    {{-- The ids of the rows the text mentions, one hidden input each. The
         script adds one from the template when a row is chosen and takes it
         away when its text is broken; the server renders those it finds. --}}
    <span data-ds-mention-ids>
        @foreach ($found as $mention)
            <input type="hidden" name="{{ $idsName }}" value="{{ $mention['id'] }}" data-ds-mention-text="{{ $mention['text'] }}" @disabled($disabled) />
        @endforeach
    </span>
    <template data-ds-mention-template><input type="hidden" name="{{ $idsName }}" /></template>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
