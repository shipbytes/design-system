@props([
    /** Submitted field name. With `multiple`, `[]` is appended for you. */
    'name',
    /** value => label. Filtered in the browser, so this is the whole list. */
    'options' => [],
    /** Selected value, or an array of them when `multiple`. */
    'value' => null,
    'label' => null,
    /** Shown in the field before anything is typed or chosen. */
    'placeholder' => 'Search…',
    /** Pick several. Chosen values render as removable chips in the field. */
    'multiple' => false,
    'help' => null,
    'error' => null,
    'disabled' => false,
    /** Shown when the filter matches nothing. */
    'emptyText' => 'No matches',
])

@php
    $id = $attributes->get('id') ?: 'ds-'.substr(md5($name.$label.uniqid()), 0, 8);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    $options = collect($options)
        ->map(fn ($optionLabel, $optionValue) => [
            'value' => (string) $optionValue,
            'label' => (string) $optionLabel,
        ])
        ->values()
        ->all();

    $selected = $multiple
        ? array_values(array_map('strval', (array) ($value ?? [])))
        : ($value === null ? [] : [(string) $value]);

    /*
     * A combobox is a SELECT WITH A TEXT FILTER, and `multiple` is a mode of it
     * rather than a second component. The keyboard handling, the popover, the
     * option list and the aria wiring are identical; chips in the field and
     * aria-multiselectable are the whole difference. Two components would mean
     * two copies of the arrow-key logic, which is exactly the pair that drifts.
     */
    $state = json_encode([
        'open' => false,
        'query' => '',
        'selected' => $selected,
        'multiple' => (bool) $multiple,
        'options' => $options,
    ]);

    $field = implode(' ', [
        'flex w-full flex-wrap items-center gap-1.5 rounded-control border bg-surface',
        'px-[calc(--spacing(2)-1px)] py-[calc(--spacing(1.5)-1px)] shadow-raised transition-colors',
        'focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-focus-ring',
        $error ? 'border-danger' : 'border-border',
        $disabled ? 'cursor-not-allowed bg-surface-subtle' : 'hover:border-fg/20',
    ]);

    /*
     * Arrow keys walk the FILTERED list, so the handler reads the DOM rather
     * than the options array — what is on screen is the only correct source
     * once a filter is applied.
     */
    $moveFocus = <<<'JS'
        const items = [...$el.querySelectorAll('[role="option"]')];
        if (!items.length) return;
        const at = items.indexOf(document.activeElement);
        const to = $event.key === 'Home' ? 0
            : $event.key === 'End' ? items.length - 1
            : $event.key === 'ArrowUp' ? (at <= 0 ? items.length - 1 : at - 1)
            : (at === items.length - 1 ? 0 : at + 1);
        items[to].focus();
    JS;
@endphp

<div
    x-data="{{ $state }}"
    {{ $attributes->only('class')->merge(['class' => 'relative block w-full']) }}
>
    @if ($label)
        <label id="{{ $id }}-label" for="{{ $id }}" class="mb-1.5 block text-body font-medium text-fg">{{ $label }}</label>
    @endif

    {{-- The value posts through hidden inputs, so this submits in a plain form
         exactly like a <select>.

         The single case is ONE static input, rendered by PHP and bound for
         Alpine, so the field still posts the current value in a host where the
         JS never runs. The multiple case cannot be: an unknown number of inputs
         needs x-for, and x-for needs Alpine. That is the honest limit, and
         docs say so — a combobox is an Alpine component either way. --}}
    @if ($multiple)
        <template x-for="picked in selected" :key="picked">
            <input type="hidden" name="{{ $name }}[]" :value="picked" />
        </template>
    @else
        <input
            type="hidden"
            name="{{ $name }}"
            value="{{ $selected[0] ?? '' }}"
            :value="selected[0] ?? ''"
        />
    @endif

    {{-- Clicking anywhere in the field focuses the text input. A combobox whose
         chips take the click and leave the caret elsewhere feels broken. --}}
    <div class="{{ $field }}" @unless ($disabled) @click="open = true; $refs.search.focus()" @endunless>
        @if ($multiple)
            {{-- Chips live INSIDE the field rather than under it: a list of
                 choices that sits below the control reads as results, and people
                 try to click them to select rather than to remove. --}}
            <template x-for="picked in selected" :key="picked">
                <span class="inline-flex max-w-full items-center gap-1 rounded-chip bg-neutral-tint py-0.5 pr-1 pl-2 text-meta font-medium text-on-neutral-tint">
                    <span class="truncate" x-text="options.find((o) => o.value === picked)?.label ?? picked"></span>
                    <button
                        type="button"
                        class="shrink-0 rounded-chip p-0.5 opacity-60 transition-opacity hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring"
                        :aria-label="'Remove ' + (options.find((o) => o.value === picked)?.label ?? picked)"
                        @click.stop="selected = selected.filter((v) => v !== picked)"
                    >
                        <x-ds::icon name="x-mark" variant="micro" size="3" />
                    </button>
                </span>
            </template>
        @endif

        <input
            type="text"
            id="{{ $id }}"
            x-ref="search"
            x-model="query"
            @focus="open = true"
            @click.stop="open = true"
            @keydown.arrow-down.prevent="open = true; $nextTick(() => $el.closest('[x-data]').querySelector('[role=option]')?.focus())"
            @keydown.escape.stop="open = false"
            {{-- Backspace on an empty query removes the last chip. Without it the
                 only way to undo a selection is to aim at a 12px ✕. --}}
            @keydown.backspace="if (multiple && query === '' && selected.length) selected = selected.slice(0, -1)"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open"
            aria-controls="{{ $id }}-listbox"
            @if ($label) aria-labelledby="{{ $id }}-label" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @disabled($disabled)
            :placeholder="selected.length && ! multiple
                ? (options.find((o) => o.value === selected[0])?.label ?? '')
                : @js($placeholder)"
            class="min-w-24 flex-1 border-0 bg-transparent px-1 py-0.5 text-body text-fg outline-hidden placeholder:text-fg-muted disabled:cursor-not-allowed"
        />

        <x-ds::icon
            name="chevron-down"
            variant="mini"
            size="4"
            class="shrink-0 text-fg-muted transition-transform"
            ::class="open && 'rotate-180'"
        />
    </div>

    <ul
        x-show="open"
        x-cloak
        id="{{ $id }}-listbox"
        @click.outside="open = false; query = ''"
        @keydown.escape.stop="open = false; $refs.search.focus()"
        @keydown.arrow-down.prevent="{{ $moveFocus }}"
        @keydown.arrow-up.prevent="{{ $moveFocus }}"
        @keydown.home.prevent="{{ $moveFocus }}"
        @keydown.end.prevent="{{ $moveFocus }}"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        role="listbox"
        :aria-multiselectable="multiple"
        @if ($label) aria-labelledby="{{ $id }}-label" @endif
        class="absolute z-50 mt-1 max-h-60 w-full origin-top overflow-y-auto rounded-control border border-border bg-surface py-1 shadow-float"
    >
        <template
            x-for="option in options.filter((o) => o.label.toLowerCase().includes(query.trim().toLowerCase()))"
            :key="option.value"
        >
            <li
                role="option"
                tabindex="-1"
                :data-value="option.value"
                :aria-selected="selected.includes(option.value)"
                @click="
                    selected = multiple
                        ? (selected.includes(option.value)
                            ? selected.filter((v) => v !== option.value)
                            : [...selected, option.value])
                        : [option.value];
                    query = '';
                    if (! multiple) { open = false; $refs.search.focus() }
                "
                @keydown.enter.prevent="$el.click()"
                @keydown.space.prevent="$el.click()"
                class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-body text-fg-body transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:bg-surface-subtle focus-visible:text-fg focus-visible:outline-hidden aria-selected:font-medium aria-selected:text-fg"
            >
                <span class="truncate" x-text="option.label"></span>
                <x-ds::icon
                    name="check"
                    variant="mini"
                    size="4"
                    class="shrink-0 text-accent"
                    x-show="selected.includes(option.value)"
                />
            </li>
        </template>

        {{-- An empty list with no message reads as a broken control. --}}
        <li
            x-show="! options.filter((o) => o.label.toLowerCase().includes(query.trim().toLowerCase())).length"
            class="px-3 py-2 text-body text-fg-muted"
        >{{ $emptyText }}</li>
    </ul>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="mt-1.5 text-meta text-fg-muted">{{ $help }}</p>
    @endif
</div>
