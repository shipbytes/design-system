@props([
    /** Submitted field name. A hidden input carries the value, so this posts like any field. */
    'name',
    /** Label text. Omit only when an adjacent visible label already names the field. */
    'label' => null,
    /** value => label. An ARRAY, not a slot: the trigger has to render the selected label. */
    'options' => [],
    /** The selected value. */
    'value' => null,
    /** Shown when nothing is selected. Not an option — it cannot be chosen. */
    'placeholder' => 'Select…',
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    /** Validation message. Its presence styles the control and sets aria-invalid. */
    'error' => null,
    'disabled' => false,
])

@php
    $id = $attributes->get('id') ?: 'ds-'.substr(md5($name.$label.uniqid()), 0, 8);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    $options = collect($options)->map(fn ($label, $value) => [
        'value' => (string) $value,
        'label' => (string) $label,
    ])->values()->all();

    $selected = collect($options)->firstWhere('value', (string) $value);

    /*
     * The listbox's own state, scoped here rather than handed in.
     *
     * This is the one component that holds anything, and it is worth being
     * precise about what: the FORM value belongs to the host and arrives as
     * `value`, and leaves through the hidden input below exactly as a native
     * <select> would. What Alpine holds is the mirror the trigger reads and the
     * open/closed flag — UI state, not application state. A host using Livewire
     * binds the hidden input and never touches any of this.
     */
    $state = json_encode([
        'open' => false,
        'value' => $selected['value'] ?? null,
        'label' => $selected['label'] ?? null,
    ]);

    $trigger = implode(' ', [
        'relative flex w-full items-center justify-between gap-2 rounded-control border',
        'bg-surface px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] text-left',
        'sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
        'text-body-touch sm:text-body shadow-raised transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        $error ? 'border-danger' : 'border-border',
        $disabled ? 'cursor-not-allowed bg-surface-subtle text-fg-subtle' : 'cursor-pointer hover:border-fg/20',
    ]);

    /*
     * Arrow keys move the focused option; Enter and Space choose it.
     *
     * role="listbox" promises this the same way role="menu" does — see
     * specs/dropdown.md. Written out in @php because a multi-line expression on
     * the tag is what makes Blade emit the tag as literal text.
     */
    $moveFocus = <<<'JS'
        const items = [...$el.querySelectorAll('[role="option"]:not([aria-disabled="true"])')];
        if (!items.length) return;
        const at = items.indexOf(document.activeElement);
        const to = $event.key === 'Home' ? 0
            : $event.key === 'End' ? items.length - 1
            : $event.key === 'ArrowUp' ? (at <= 0 ? items.length - 1 : at - 1)
            : (at === items.length - 1 ? 0 : at + 1);
        items[to].focus();
    JS;

    // Focus enters the SELECTED option, not the first one — opening a list of
    // forty countries at the top when "Zambia" is chosen loses the reader's place.
    $focusManagement = <<<'JS'
        if (open) {
            $nextTick(() => {
                const list = $el.querySelector('[role="listbox"]');
                (list.querySelector('[aria-selected="true"]')
                    ?? list.querySelector('[role="option"]:not([aria-disabled="true"])'))?.focus();
            });
        } else if ($el.contains(document.activeElement)) {
            $el.querySelector('[data-ds-select-trigger]')?.focus();
        }
    JS;
@endphp

<div
    x-data="{{ $state }}"
    x-effect="{{ $focusManagement }}"
    {{ $attributes->only('class')->merge(['class' => 'relative block w-full']) }}
>
    @if ($label)
        <label id="{{ $id }}-label" for="{{ $id }}" class="block text-body font-medium text-fg">{{ $label }}</label>
    @endif

    {{-- The value posts through a hidden input, so this submits in a plain form
         exactly like a <select> and needs no JavaScript on the receiving end. --}}
    <input type="hidden" name="{{ $name }}" :value="value" @if ($selected) value="{{ $selected['value'] }}" @endif />

    <button
        type="button"
        id="{{ $id }}"
        data-ds-select-trigger
        @class([$trigger, 'mt-1.5' => (bool) $label])
        @click="open = ! open"
        @keydown.escape.stop="open = false"
        {{-- ArrowDown opens a closed listbox. Without it the keyboard way into the
             control is Enter only, which is not what a select does. --}}
        @keydown.arrow-down.prevent="open = true"
        @disabled($disabled)
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="open"
        aria-controls="{{ $id }}-listbox"
        @if ($label) aria-labelledby="{{ $id }}-label {{ $id }}" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    >
        {{-- The placeholder is fg-muted and the value is fg, so "nothing chosen"
             is visible without reading the words.

             The colour is rendered by PHP *and* bound for Alpine. Binding alone
             leaves the server's HTML with no colour at all until Alpine boots,
             which is a visible flash on first paint and simply wrong output for
             anything that never runs the JS at all. Same principle as the
             aria-selected and the tick below: the markup is correct standing
             still, and Alpine only keeps it correct once it moves. --}}
        <span
            class="truncate {{ $selected ? 'text-fg' : 'text-fg-muted' }}"
            :class="label ? 'text-fg' : 'text-fg-muted'"
        >
            <span x-text="label ?? {{ json_encode($placeholder) }}">{{ $selected['label'] ?? $placeholder }}</span>
        </span>

        <x-ds::icon
            name="chevron-down"
            variant="mini"
            size="5"
            class="shrink-0 text-fg-muted transition-transform sm:size-4"
            ::class="open && 'rotate-180'"
        />
    </button>

    <ul
        x-show="open"
        x-cloak
        @click.outside="open = false"
        {{-- Just closes. Returning focus to the trigger is x-effect's job, and
             doing it here as well fights it. --}}
        @keydown.escape.stop="open = false"
        @keydown.tab="open = false"
        @keydown.arrow-down.prevent="{{ $moveFocus }}"
        @keydown.arrow-up.prevent="{{ $moveFocus }}"
        @keydown.home.prevent="{{ $moveFocus }}"
        @keydown.end.prevent="{{ $moveFocus }}"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        id="{{ $id }}-listbox"
        role="listbox"
        @if ($label) aria-labelledby="{{ $id }}-label" @endif
        {{-- max-h so a long list scrolls inside itself instead of running off the
             page. `float`, not `overlay`: it hovers, it does not block. --}}
        class="absolute z-50 mt-1 max-h-60 w-full origin-top overflow-y-auto rounded-control border border-border bg-surface py-1 shadow-float"
    >
        @foreach ($options as $option)
            @php
                /*
                 * The JS literals are built HERE and echoed, rather than written
                 * as @js(...) on the tags below.
                 *
                 * A Blade directive inside a COMPONENT TAG's attribute is not
                 * compiled at the call site — it is handed to the child as a
                 * literal string and evaluated in the CHILD's scope, where
                 * $option does not exist. It fails as "Undefined variable
                 * $option" pointing at a compiled file in storage, with nothing
                 * naming this loop. `{{ }}` echoes are fine; directives are not.
                 */
                $optionValueJs = json_encode($option['value']);
                $optionLabelJs = json_encode($option['label']);
                $isSelected = ($selected['value'] ?? null) === $option['value'];
            @endphp

            <li
                role="option"
                tabindex="-1"
                data-value="{{ $option['value'] }}"
                :aria-selected="value === {{ $optionValueJs }}"
                aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                @click="value = {{ $optionValueJs }}; label = {{ $optionLabelJs }}; open = false"
                @keydown.enter.prevent="$el.click()"
                @keydown.space.prevent="$el.click()"
                class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-body text-fg-body transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:bg-surface-subtle focus-visible:text-fg focus-visible:outline-hidden aria-selected:font-medium aria-selected:text-fg"
            >
                <span class="truncate">{{ $option['label'] }}</span>

                {{-- A tick as well as the weight change: weight alone is not a
                     reliable signal of "chosen" for anyone comparing two rows. --}}
                {{-- Hidden with an inline `display:none` rather than a class or
                     x-cloak, because that is the one form x-show can take back:
                     it sets and clears `style.display` itself, so the server's
                     initial state and Alpine's later state use the same channel
                     and never disagree. A `hidden` CLASS would fight it — x-show
                     clears the inline style and the class reasserts itself.

                     A `{{ }}` echo and not an @unless: a Blade DIRECTIVE inside a
                     component tag makes the tag parser give up and print the tag
                     as literal text. Echoes are fine there; directives are not. --}}
                <x-ds::icon
                    name="check"
                    variant="mini"
                    size="4"
                    class="shrink-0 text-accent"
                    x-show="value === {{ $optionValueJs }}"
                    style="{{ $isSelected ? '' : 'display: none' }}"
                />
            </li>
        @endforeach
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
