@props([
    /**
     * Submitted field name. With `range`, this posts "{name}_start" and
     * "{name}_end" — two dates are two fields, and squeezing them into one
     * string means every consumer writing the same parser.
     */
    'name',
    'label' => null,
    /** Y-m-d, a DateTimeInterface, or null. With `range`, an array of two. */
    'value' => null,
    /** Pick a start and an end rather than one day. */
    'range' => false,
    /** Earliest selectable date. Y-m-d or a DateTimeInterface. */
    'min' => null,
    /** Latest selectable date. */
    'max' => null,
    'placeholder' => null,
    'help' => null,
    'error' => null,
    'disabled' => false,
    /** 0 = Sunday, 1 = Monday. Which day a week starts on is regional, not universal. */
    'weekStartsOn' => 1,
])

@php
    $id = $attributes->get('id') ?: 'ds-'.substr(md5($name.$label.uniqid()), 0, 8);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    /** Everything crossing into JS is Y-m-d — one format, no parsing. */
    $iso = function ($date) {
        if ($date === null || $date === '') return null;
        if ($date instanceof \DateTimeInterface) return $date->format('Y-m-d');

        return substr((string) $date, 0, 10);
    };

    $values = $range
        ? array_pad(array_map($iso, array_slice((array) ($value ?? []), 0, 2)), 2, null)
        : [$iso($value), null];

    $weekStartsOn = (int) $weekStartsOn === 0 ? 0 : 1;

    /*
     * Day names come from PHP, not from a hard-coded array, so they follow the
     * app's locale. Built from a known Monday so the rotation is arithmetic
     * rather than a second list to keep in step with `weekStartsOn`.
     */
    $dayNames = collect(range(0, 6))
        ->map(fn ($offset) => \Illuminate\Support\Carbon::parse('2024-01-01')
            ->addDays(($offset + $weekStartsOn + 6) % 7)
            ->isoFormat('dd'))
        ->all();

    $monthNames = collect(range(1, 12))
        ->map(fn ($month) => \Illuminate\Support\Carbon::create(2024, $month, 1)->isoFormat('MMMM'))
        ->all();

    $field = implode(' ', [
        'flex w-full items-center justify-between gap-2 rounded-control border bg-surface',
        'px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] text-left',
        'sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
        'text-body-touch sm:text-body shadow-raised transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        $error ? 'border-danger' : 'border-border',
        $disabled ? 'cursor-not-allowed bg-surface-subtle text-fg-subtle' : 'cursor-pointer hover:border-fg/20',
    ]);

    /*
     * The whole calendar is built in JS from a cursor month.
     *
     * Dates are handled as Y-m-d STRINGS and compared as strings, never as Date
     * objects. `new Date('2026-03-29')` is UTC midnight, and in a timezone west
     * of Greenwich that is the 28th — so a picker built on Date objects selects
     * the day before the one that was clicked, for some users, some of the time.
     * String comparison on Y-m-d is both correct and ordered.
     */
    $scope = <<<'JS'
    {
        open: false,
        start: null,
        end: null,
        hovered: null,
        cursor: null,
        min: null,
        max: null,
        range: false,
        weekStartsOn: 1,
        dayNames: [],
        monthNames: [],

        init() {
            this.cursor = (this.start ?? this.todayIso()).slice(0, 7) + '-01';
        },

        todayIso() {
            const now = new Date();
            return this.key(now.getFullYear(), now.getMonth() + 1, now.getDate());
        },

        key(year, month, day) {
            return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        },

        get cursorYear() { return Number(this.cursor.slice(0, 4)); },
        get cursorMonth() { return Number(this.cursor.slice(5, 7)); },
        get monthLabel() { return this.monthNames[this.cursorMonth - 1] + ' ' + this.cursorYear; },

        shiftMonth(by) {
            const month = this.cursorMonth - 1 + by;
            const year = this.cursorYear + Math.floor(month / 12);
            this.cursor = this.key(year, ((month % 12) + 12) % 12 + 1, 1);
        },

        // Leading blanks, then every day of the month. Day 0 of the next month
        // is the last day of this one, which avoids a leap-year table.
        get days() {
            const first = new Date(Date.UTC(this.cursorYear, this.cursorMonth - 1, 1));
            const length = new Date(Date.UTC(this.cursorYear, this.cursorMonth, 0)).getUTCDate();
            const lead = (first.getUTCDay() - this.weekStartsOn + 7) % 7;
            const cells = Array.from({ length: lead }, () => null);
            for (let day = 1; day <= length; day++) cells.push(this.key(this.cursorYear, this.cursorMonth, day));
            return cells;
        },

        disabledDay(iso) {
            return (this.min && iso < this.min) || (this.max && iso > this.max);
        },

        isSelected(iso) { return iso === this.start || iso === this.end; },

        isBetween(iso) {
            if (! this.range || ! this.start) return false;
            const until = this.end ?? this.hovered;
            if (! until) return false;
            const [from, to] = this.start <= until ? [this.start, until] : [until, this.start];
            return iso > from && iso < to;
        },

        pick(iso) {
            if (this.disabledDay(iso)) return;

            if (! this.range) {
                this.start = iso;
                this.open = false;
                return;
            }

            // A second click BEFORE the first re-opens the range from there,
            // rather than producing a backwards one the reader has to undo.
            if (! this.start || this.end || iso < this.start) {
                this.start = iso;
                this.end = null;
            } else {
                this.end = iso;
                this.open = false;
            }
        },

        clear() { this.start = null; this.end = null; this.hovered = null; },

        // The button's label. Formatting stays in the browser so it follows the
        // reader's locale rather than the server's.
        display(iso) {
            if (! iso) return '';
            const [y, m, d] = iso.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString(undefined, {
                day: 'numeric', month: 'short', year: 'numeric',
            });
        },

        get summary() {
            if (! this.start) return '';
            return this.range
                ? this.display(this.start) + ' – ' + (this.end ? this.display(this.end) : '…')
                : this.display(this.start);
        },

        // Arrow keys move by a day, PageUp/PageDown by a month. A calendar that
        // only answers to Tab makes a keyboard reader press it thirty times.
        move(event) {
            const focused = event.target.dataset.day;
            if (! focused) return;

            const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[event.key];
            const [y, m, d] = focused.split('-').map(Number);

            let next;
            if (step !== undefined) {
                const moved = new Date(Date.UTC(y, m - 1, d + step));
                next = this.key(moved.getUTCFullYear(), moved.getUTCMonth() + 1, moved.getUTCDate());
            } else if (event.key === 'PageUp' || event.key === 'PageDown') {
                this.shiftMonth(event.key === 'PageUp' ? -1 : 1);
                next = this.cursor;
            } else if (event.key === 'Home' || event.key === 'End') {
                next = event.key === 'Home'
                    ? this.key(y, m, 1)
                    : this.key(y, m, new Date(Date.UTC(y, m, 0)).getUTCDate());
            } else {
                return;
            }

            if (next.slice(0, 7) !== this.cursor.slice(0, 7)) this.cursor = next.slice(0, 7) + '-01';
            this.$nextTick(() => this.$el.querySelector(`[data-day="${next}"]`)?.focus());
        },
    }
    JS;

    $initial = json_encode([
        'start' => $values[0],
        'end' => $values[1],
        'min' => $iso($min),
        'max' => $iso($max),
        'range' => (bool) $range,
        'weekStartsOn' => $weekStartsOn,
        'dayNames' => $dayNames,
        'monthNames' => $monthNames,
    ]);

    $data = 'Object.assign('.$scope.', '.$initial.')';

    $startName = $range ? "{$name}_start" : $name;
@endphp

<div x-data="{{ $data }}" {{ $attributes->only('class')->merge(['class' => 'relative block w-full']) }}>
    @if ($label)
        <label id="{{ $id }}-label" for="{{ $id }}" class="mb-1.5 block text-body font-medium text-fg">{{ $label }}</label>
    @endif

    {{-- Posts Y-m-d, which is what a database column and Carbon::parse both
         want. Rendered by PHP and bound for Alpine, so the field carries its
         value before the JS runs. --}}
    <input type="hidden" name="{{ $startName }}" value="{{ $values[0] }}" :value="start ?? ''" />
    @if ($range)
        <input type="hidden" name="{{ $name }}_end" value="{{ $values[1] }}" :value="end ?? ''" />
    @endif

    <button
        type="button"
        id="{{ $id }}"
        data-ds-date-trigger
        class="{{ $field }}"
        @click="open = ! open"
        @keydown.escape.stop="open = false"
        @keydown.arrow-down.prevent="open = true"
        @disabled($disabled)
        aria-haspopup="dialog"
        :aria-expanded="open"
        @if ($label) aria-labelledby="{{ $id }}-label {{ $id }}" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    >
        <span class="truncate" :class="start ? 'text-fg' : 'text-fg-muted'">
            <span x-text="summary || @js($placeholder ?? ($range ? 'Choose a period' : 'Choose a date'))">{{ $placeholder ?? ($range ? 'Choose a period' : 'Choose a date') }}</span>
        </span>
        <x-ds::icon name="calendar" variant="mini" size="5" class="shrink-0 text-fg-muted sm:size-4" />
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        @keydown.escape.stop="open = false; $el.closest('[x-data]').querySelector('[data-ds-date-trigger]').focus()"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        role="dialog"
        aria-modal="false"
        @if ($label) aria-labelledby="{{ $id }}-label" @endif
        class="absolute z-50 mt-1 w-max origin-top rounded-control border border-border bg-surface p-3 shadow-float"
    >
        <div class="mb-2 flex items-center justify-between gap-2">
            <button
                type="button"
                @click="shiftMonth(-1)"
                aria-label="Previous month"
                class="rounded-control p-1.5 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
                <x-ds::icon name="chevron-left" variant="mini" size="4" />
            </button>

            {{-- A live region, because pressing the arrows changes the grid the
                 reader is standing in and nothing else announces it. --}}
            <span class="text-body font-medium text-fg" aria-live="polite" x-text="monthLabel"></span>

            <button
                type="button"
                @click="shiftMonth(1)"
                aria-label="Next month"
                class="rounded-control p-1.5 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
                <x-ds::icon name="chevron-right" variant="mini" size="4" />
            </button>
        </div>

        <div role="grid" @keydown="move($event)" @mouseleave="hovered = null">
            <div role="row" class="grid grid-cols-7">
                <template x-for="day in dayNames" :key="day">
                    {{-- abbr carries the full name; the column head shows two
                         letters, which on its own announces as nonsense. --}}
                    <div role="columnheader" class="py-1 text-center text-meta font-medium text-fg-muted">
                        <span x-text="day"></span>
                    </div>
                </template>
            </div>

            <div class="grid grid-cols-7 gap-0.5">
                <template x-for="(day, index) in days" :key="index">
                    <div role="gridcell" class="flex">
                        <template x-if="day === null">
                            <span class="size-9"></span>
                        </template>

                        <template x-if="day !== null">
                            <button
                                type="button"
                                :data-day="day"
                                :disabled="disabledDay(day)"
                                :tabindex="day === (start ?? cursor) ? 0 : -1"
                                :aria-selected="isSelected(day)"
                                :aria-current="day === todayIso() ? 'date' : null"
                                @click="pick(day)"
                                @mouseenter="hovered = day"
                                class="size-9 rounded-control text-body tabular-nums transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:text-fg-subtle"
                                :class="isSelected(day)
                                    ? 'bg-surface-inverse text-on-inverse font-medium'
                                    : isBetween(day)
                                        ? 'bg-accent-wash text-fg'
                                        : day === todayIso()
                                            ? 'text-fg font-semibold'
                                            : 'text-fg-body hover:bg-surface-subtle'"
                                x-text="Number(day.slice(8))"
                            ></button>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="mt-2 flex items-center justify-between gap-2 border-t border-divider pt-2">
            <button
                type="button"
                @click="clear()"
                class="rounded-control px-2 py-1 text-meta font-medium text-fg-muted transition-colors hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >Clear</button>

            <button
                type="button"
                @click="open = false; $el.closest('[x-data]').querySelector('[data-ds-date-trigger]').focus()"
                class="rounded-control px-2 py-1 text-meta font-medium text-fg transition-colors hover:bg-surface-subtle focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >Done</button>
        </div>
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="mt-1.5 text-meta text-fg-muted">{{ $help }}</p>
    @endif
</div>
