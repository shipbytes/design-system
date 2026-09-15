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
    /**
     * Single-select only: this field must hold a value.
     *
     * It hides the clear ✕ and refuses backspace-to-clear — clearing a required
     * field can only produce a state the form rejects — and sets aria-required.
     */
    'required' => false,
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
    /*
     * Rendered by PHP as well as bound, so a settled field carries its label on
     * first paint rather than flashing empty until Alpine boots. Null when the
     * option is not in the list, which is what makes the placeholder show
     * instead of a raw id.
     */
    $chosenLabel = ! $multiple && isset($selected[0])
        ? (collect($options)->firstWhere('value', $selected[0])['label'] ?? null)
        : null;

    $state = json_encode([
        'open' => false,
        'query' => '',
        'selected' => $selected,
        'multiple' => (bool) $multiple,
        'required' => (bool) $required,
        'options' => $options,
    ]);

    /*
     * A chosen value is the field's TEXT, not its placeholder.
     *
     * Rendering it as the placeholder — which both this component and its React
     * port did — draws a settled field as an empty focused text box: faded grey
     * with a blinking caret, read as "nothing chosen yet" when the choice is
     * made and constrained. specs/combobox.md now covers the three states these
     * five methods implement; it covered none of them before, which is how two
     * implementations came to agree on the same wrong answer.
     */
    $methods = <<<'JS'
        chosenLabel() {
            if (this.multiple || ! this.selected.length) return null;
            const option = this.options.find((o) => o.value === this.selected[0]);
            // An unresolved id is not a worse label, it is a wrong one — a
            // glaring 4711 where a name belongs. Fall back to the placeholder.
            return option ? option.label : null;
        },
        showingLabel() {
            return this.query === '' && this.chosenLabel() !== null;
        },
        display() {
            return this.query !== '' ? this.query : (this.chosenLabel() ?? '');
        },
        clearable() {
            return ! this.required && ! this.multiple && this.selected.length > 0;
        },
        type(next) {
            if (! this.showingLabel()) { this.query = next; this.open = true; return }
            /*
             * The label is in the input's value, so the browser hands back the
             * label with the keystroke folded into it — "Main gatex" for one
             * typed "x". What was INSERTED is the query: the common prefix and
             * suffix are the label surviving, and what sits between them is what
             * the user typed. A deletion inserts nothing and so leaves the label
             * alone, which is what makes backspace-to-clear the keydown
             * handler's job rather than this one's.
             */
            const before = this.chosenLabel();
            let head = 0;
            while (head < before.length && head < next.length && before[head] === next[head]) head++;
            let tail = 0;
            while (tail < before.length - head && tail < next.length - head
                && before[before.length - 1 - tail] === next[next.length - 1 - tail]) tail++;
            this.query = next.slice(head, next.length - tail);
            this.open = true;
        },
    JS;

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
    x-data="{{ '{ ...'.$state.', '.$methods.' }' }}"
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
            {{-- NOT x-model. The field shows the query when there is one and the
                 chosen label otherwise, so what the browser hands back on an
                 input event is not the query — `type()` works out what was
                 inserted. The placeholder is the placeholder again. --}}
            value="{{ $chosenLabel ?? '' }}"
            :value="display()"
            @input="type($event.target.value)"
            @focus="open = true"
            @click.stop="open = true"
            @keydown.arrow-down.prevent="open = true; $nextTick(() => $el.closest('[x-data]').querySelector('[role=option]')?.focus())"
            @keydown.escape.stop="open = false"
            {{-- Backspace on an empty query clears the selection: the last chip
                 when multiple, the single value otherwise. Without it the only
                 way to undo a selection is to aim at a 12px ✕, and a single
                 value could not be undone at all. --}}
            @keydown.backspace="if (query === '' && selected.length) { if (multiple) { selected = selected.slice(0, -1) } else if (! required) { selected = [] } }"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open"
            aria-controls="{{ $id }}-listbox"
            aria-required="{{ $required ? 'true' : 'false' }}"
            @if ($label) aria-labelledby="{{ $id }}-label" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @disabled($disabled)
            placeholder="{{ $placeholder }}"
            {{-- The caret is what makes a settled field read as an empty one
                 waiting to be typed into. It goes exactly while the field is
                 showing a label; the focus ring stays, because it is the only
                 thing telling a keyboard user where they are. --}}
            :class="{ 'caret-transparent': showingLabel() }"
            class="min-w-24 flex-1 border-0 bg-transparent px-1 py-0.5 text-body text-fg outline-hidden placeholder:text-fg-muted disabled:cursor-not-allowed"
        />

        {{-- A single value used to be a one-way door: it could be swapped and
             never unset. Hidden on a required field, where clearing could only
             produce a state the form rejects. --}}
        @unless ($multiple || $required)
            <button
                type="button"
                x-cloak
                x-show="clearable()"
                :aria-label="'Clear ' + (chosenLabel() ?? 'selection')"
                class="shrink-0 rounded-chip p-0.5 text-fg-muted opacity-60 transition-opacity hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring"
                @click.stop="selected = []; query = ''; $refs.search.focus()"
            >
                <x-ds::icon name="x-mark" variant="micro" size="3" />
            </button>
        @endunless

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
