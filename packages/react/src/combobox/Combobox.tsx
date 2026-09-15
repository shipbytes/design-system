import {
  useCallback,
  useEffect,
  useId,
  useMemo,
  useRef,
  useState,
  type KeyboardEvent,
  type ReactNode,
} from 'react'
import { Icon } from '../icon'
import { cn } from '../lib/cn'
import { mergeRefs, PopoverPortal, useAnchoredPopover } from '../lib/popover'

/**
 * Copied from resources/views/components/combobox.blade.php.
 * See specs/combobox.md.
 *
 * A combobox is a SELECT WITH A TEXT FILTER, and `multiple` is a mode of it
 * rather than a second component. The keyboard handling, the popover, the
 * option list and the aria wiring are identical; chips in the field and
 * aria-multiselectable are the whole difference. Two components would mean two
 * copies of the arrow-key logic, which is exactly the pair that drifts.
 *
 * **Filtering.** The Blade component filters the list it is given, in the
 * browser, and CLAUDE.md's known gap 8 records that server-side filtering was
 * deliberately not built there: "a searchable list too large to send needs a
 * search callback, and a callback is a backend contract — the one thing nothing
 * here has". A React consumer already owns its own fetching, so the seam costs
 * nothing here: `filter={false}` turns this component's own filtering off and
 * `onQueryChange` reports what was typed. The component still fetches nothing.
 * The default is unchanged from the spec.
 */

export interface ComboboxOption {
  value: string
  label: string
  /** Rendered under the label — a code beside a name, a hint beside a choice. */
  meta?: ReactNode
  disabled?: boolean
}

export interface ComboboxProps {
  options: ComboboxOption[]
  /** One value, or several when `multiple`. */
  value: string | string[] | null
  onChange: (value: string | string[] | null) => void
  label?: ReactNode
  /** Shown in the field before anything is typed or chosen. */
  placeholder?: string
  /** Pick several. Chosen values render as removable chips in the field. */
  multiple?: boolean
  /**
   * Single-select only: this field must hold a value.
   *
   * It hides the clear ✕ and refuses backspace-to-clear — clearing a required
   * field can only produce a state the form rejects — and sets aria-required.
   */
  required?: boolean
  help?: ReactNode
  error?: ReactNode
  disabled?: boolean
  /** Shown when the filter matches nothing. */
  emptyText?: ReactNode
  /** Reported on every keystroke, for a consumer that filters server-side. */
  onQueryChange?: (query: string) => void
  /** `false` leaves `options` exactly as given. */
  filter?: false | ((option: ComboboxOption, query: string) => boolean)
  /** Replaces the empty message while a consumer's own fetch is in flight. */
  loading?: boolean
  loadingText?: ReactNode
  id?: string
  className?: string
  name?: string
}

const defaultFilter = (option: ComboboxOption, query: string): boolean =>
  option.label.toLowerCase().includes(query.trim().toLowerCase())

/**
 * What the user just typed into a field that was showing a label.
 *
 * The chosen label lives in the input's `value`, so the browser hands back the
 * label with the keystroke folded into it — `Main gatex` for one typed `x`. The
 * common prefix and suffix are the label surviving; what sits between them is
 * the insertion, wherever the caret happened to be. A deletion inserts nothing
 * and so returns '', which leaves the field settled and makes clearing the
 * keydown handler's job rather than this one's.
 */
const inserted = (before: string, after: string): string => {
  let head = 0

  while (head < before.length && head < after.length && before[head] === after[head]) {
    head++
  }

  let tail = 0

  while (
    tail < before.length - head &&
    tail < after.length - head &&
    before[before.length - 1 - tail] === after[after.length - 1 - tail]
  ) {
    tail++
  }

  return after.slice(head, after.length - tail)
}

export function Combobox({
  options,
  value,
  onChange,
  label,
  placeholder = 'Search…',
  multiple = false,
  required = false,
  help,
  error,
  disabled = false,
  emptyText = 'No matches',
  onQueryChange,
  filter = defaultFilter,
  loading = false,
  loadingText = 'Searching…',
  id,
  className,
  name,
}: ComboboxProps) {
  const generated = useId()
  const fieldId = id ?? `ds-${generated}`
  const describedBy = error ? `${fieldId}-error` : help ? `${fieldId}-help` : undefined

  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState('')

  /*
   * The option the keyboard is ON, tracked as a VALUE rather than as an index.
   *
   * This is `aria-activedescendant` and not real focus, which is the ARIA 1.2
   * combobox pattern and the only version that survives a focus trap: inside a
   * `Modal` or a `Drawer` the listbox is portalled to `document.body` and so
   * sits outside the dialog, and Radix's trap pulls any focus that lands there
   * straight back to the input. Measured — with twenty options showing, the only
   * movement ArrowDown produced was the caret returning to the field.
   *
   * A value and not an index because the option list is REPLACED when a
   * consumer's server answers: an index held across that points at whatever row
   * happens to have moved into the slot, and Enter then picks something the
   * reader never arrowed to.
   */
  const [activeValue, setActiveValue] = useState<string | null>(null)

  const root = useRef<HTMLDivElement>(null)
  const search = useRef<HTMLInputElement>(null)
  const list = useRef<HTMLUListElement>(null)

  /*
   * The listbox is portalled and measured against the FIELD, not the root: the
   * root also holds the label and the help text, and a list lined up with the
   * label sits a row too high. See lib/popover.tsx for why it leaves the flow
   * at all — a combobox in a scrollable dialog was being clipped at the footer.
   */
  const popover = useAnchoredPopover({ open, matchWidth: true, maxHeight: 240, interactive: true })

  const selected = useMemo(
    () => (multiple ? (Array.isArray(value) ? value : []) : value == null ? [] : [String(value)]),
    [multiple, value],
  )

  const visible = useMemo(
    () => (filter === false ? options : options.filter((option) => filter(option, query))),
    [options, filter, query],
  )

  const labelFor = useCallback(
    (picked: string) => options.find((option) => option.value === picked)?.label ?? picked,
    [options],
  )

  /*
   * The settled single choice, as text — or undefined when the option has not
   * arrived yet.
   *
   * `labelFor` falls back to the raw value, which as a faded placeholder was
   * merely odd and at full strength is a glaring `4711` sitting where a name
   * belongs. A consumer that resolves selected labels asynchronously has a
   * window between the value arriving and its label arriving; through it the
   * field shows its placeholder, because an id is not a worse label, it is a
   * wrong one.
   */
  const chosenLabel =
    multiple || selected.length === 0
      ? undefined
      : options.find((option) => option.value === selected[0])?.label

  /*
   * A chosen value is the field's TEXT, not its placeholder — which is what
   * this component and the Blade component it was copied from both got wrong,
   * because specs/combobox.md covered only the multi-select case. A settled
   * field rendered its answer in `placeholder:text-fg-muted`, the styling whose
   * entire job is to say "this is a hint, not a value", and so drew itself as
   * an empty focused text box.
   */
  const showingLabel = query === '' && chosenLabel !== undefined
  const clearable = !required && !multiple && selected.length > 0

  const clear = () => {
    onChange(null)
    setQuery('')
    onQueryChange?.('')
  }

  useEffect(() => {
    if (!open) {
      return
    }

    /*
     * The list is portalled to document.body, so it is no longer inside `root`
     * and a click on an option would read as a click outside — closing the list
     * before the option's own handler runs, which looks like the choice being
     * ignored. Both nodes count as inside.
     */
    const close = (event: MouseEvent) => {
      const target = event.target as Node

      if (root.current?.contains(target) || list.current?.contains(target)) {
        return
      }

      setOpen(false)
      setQuery('')
    }

    document.addEventListener('mousedown', close)

    return () => document.removeEventListener('mousedown', close)
  }, [open])

  /* Arrow keys walk what is ON SCREEN and choosable — a disabled row is not a
     stop on the way past, and a filtered-out row is not there at all. */
  const choosable = useMemo(() => visible.filter((option) => !option.disabled), [visible])

  const optionId = (index: number) => `${fieldId}-option-${index}`

  const activeIndex = activeValue === null ? -1 : visible.findIndex((option) => option.value === activeValue)

  /*
   * A new `options` array is a new list, so the active row is reset with it.
   * Without this, Enter after a server answer chooses a stale row — the one
   * thing tracking a value rather than an index cannot prevent on its own,
   * because a value that is simply gone would leave the field with nothing
   * highlighted and Enter doing something invisible.
   */
  useEffect(() => {
    setActiveValue(null)
  }, [options])

  /* Closing forgets where the keyboard was: reopening starts at the top. */
  useEffect(() => {
    if (!open) {
      setActiveValue(null)
    }
  }, [open])

  /*
   * The list scrolls at 240px, and nothing in it is focused any more — so the
   * browser will not bring the active row into view by itself, the way it did
   * when arrowing moved real focus. Optional call: jsdom does not implement it.
   */
  useEffect(() => {
    if (!open || activeIndex < 0) {
      return
    }

    document.getElementById(optionId(activeIndex))?.scrollIntoView?.({ block: 'nearest' })
  }, [open, activeIndex])

  const moveActive = (key: string) => {
    if (choosable.length === 0) {
      return
    }

    const at = choosable.findIndex((option) => option.value === activeValue)
    const to =
      key === 'Home'
        ? 0
        : key === 'End'
          ? choosable.length - 1
          : key === 'ArrowUp'
            ? at <= 0
              ? choosable.length - 1
              : at - 1
            : at === choosable.length - 1
              ? 0
              : at + 1

    setActiveValue(choosable[to]!.value)
  }

  const type = (next: string) => {
    // Typing only. Putting the label into the input's `value` must never reach
    // here: a consumer filtering server-side takes onQueryChange as the record
    // of what was TYPED, and a label arriving as a query would search for it.
    const typed = showingLabel ? inserted(chosenLabel!, next) : next

    setQuery(typed)
    setOpen(true)
    onQueryChange?.(typed)
  }

  const choose = (option: ComboboxOption) => {
    if (option.disabled) {
      return
    }

    if (multiple) {
      const next = selected.includes(option.value)
        ? selected.filter((v) => v !== option.value)
        : [...selected, option.value]

      onChange(next)
      setQuery('')
      onQueryChange?.('')

      return
    }

    onChange(option.value)
    setQuery('')
    onQueryChange?.('')

    /*
     * Focus first, THEN close. Clicking an option moves focus to the option, so
     * returning it to the field fires `focus` — and that handler opens the
     * list. Both state updates are queued inside one event and the last wins,
     * so this order closes; the other order reopens the list the click just
     * chose from.
     */
    search.current?.focus()
    setOpen(false)
  }

  const onSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End') {
      event.preventDefault()

      // Nothing waits for a render any more: `choosable` is derived from the
      // props, so the first press can both open the list and land on a row.
      setOpen(true)
      moveActive(event.key)

      return
    }

    /*
     * Enter chooses the active row — and Enter ONLY.
     *
     * The old handler took `' '` as well, which was safe only because it lived
     * on a focused `<li>`. On the input it would make the space bar unable to
     * type a space into the filter, which is half the words in a master.
     *
     * With nothing active this falls through rather than swallowing the key, so
     * Enter still submits the form around the field.
     */
    if (event.key === 'Enter') {
      const option = activeValue === null ? undefined : choosable.find((o) => o.value === activeValue)

      if (option) {
        event.preventDefault()
        choose(option)
      }

      return
    }

    if (event.key === 'Escape') {
      event.stopPropagation()
      setOpen(false)

      return
    }

    /*
     * Backspace on an empty query clears the selection: the last chip when
     * multiple, the single value otherwise. Without it the only way to undo a
     * selection is to aim at a 12px ✕ — and a single value could not be undone
     * at all, because this was gated on `multiple`. A required field keeps the
     * one-way door on purpose.
     */
    if (event.key === 'Backspace' && query === '' && selected.length > 0) {
      if (multiple) {
        onChange(selected.slice(0, -1))
      } else if (!required) {
        clear()
      }
    }
  }

  return (
    <div ref={root} className={cn('relative block w-full', className)}>
      {label ? (
        <label id={`${fieldId}-label`} htmlFor={fieldId} className="mb-1.5 block text-body font-medium text-fg">
          {label}
        </label>
      ) : null}

      {/* The value posts through hidden inputs, so this submits in a plain form
          exactly like a <select> for a consumer that still uses one. */}
      {name
        ? multiple
          ? selected.map((picked) => <input key={picked} type="hidden" name={`${name}[]`} value={picked} />)
          : <input type="hidden" name={name} value={selected[0] ?? ''} />
        : null}

      {/* Clicking anywhere in the field focuses the text input. A combobox
          whose chips take the click and leave the caret elsewhere feels broken. */}
      <div
        ref={popover.setAnchor}
        className={cn(
          'flex w-full flex-wrap items-center gap-1.5 rounded-control border bg-surface',
          'px-[calc(--spacing(2)-1px)] py-[calc(--spacing(1.5)-1px)] shadow-raised transition-colors',
          'focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-focus-ring',
          error ? 'border-danger' : 'border-border',
          disabled ? 'cursor-not-allowed bg-surface-subtle' : 'hover:border-fg/20',
        )}
        onClick={
          disabled
            ? undefined
            : () => {
                setOpen(true)
                search.current?.focus()
              }
        }
      >
        {/* Chips live INSIDE the field rather than under it: a list of choices
            that sits below the control reads as results, and people try to
            click them to select rather than to remove. */}
        {multiple
          ? selected.map((picked) => (
              <span
                key={picked}
                className="inline-flex max-w-full items-center gap-1 rounded-chip bg-neutral-tint py-0.5 pr-1 pl-2 text-meta font-medium text-on-neutral-tint"
              >
                <span className="truncate">{labelFor(picked)}</span>
                <button
                  type="button"
                  aria-label={`Remove ${labelFor(picked)}`}
                  className="shrink-0 rounded-chip p-0.5 opacity-60 transition-opacity hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring"
                  onClick={(event) => {
                    event.stopPropagation()
                    onChange(selected.filter((v) => v !== picked))
                  }}
                >
                  <Icon name="x-mark" variant="micro" size="3" />
                </button>
              </span>
            ))
          : null}

        <input
          ref={search}
          type="text"
          id={fieldId}
          value={showingLabel ? chosenLabel : query}
          onChange={(event) => type(event.target.value)}
          onFocus={() => setOpen(true)}
          onKeyDown={onSearchKeyDown}
          autoComplete="off"
          role="combobox"
          aria-autocomplete="list"
          aria-expanded={open}
          aria-controls={`${fieldId}-listbox`}
          // Where the keyboard is, without moving the caret out of the field.
          aria-activedescendant={open && activeIndex >= 0 ? optionId(activeIndex) : undefined}
          aria-labelledby={label ? `${fieldId}-label` : undefined}
          aria-invalid={error ? true : undefined}
          aria-required={required}
          aria-describedby={describedBy}
          disabled={disabled}
          placeholder={placeholder}
          className={cn(
            'min-w-24 flex-1 border-0 bg-transparent px-1 py-0.5 text-body text-fg outline-hidden placeholder:text-fg-muted disabled:cursor-not-allowed',
            // The caret is what makes a settled field read as an empty one
            // waiting to be typed into. It goes exactly while the field is
            // showing a label; the focus ring stays, because it is the only
            // thing telling a keyboard user where they are.
            showingLabel && 'caret-transparent',
          )}
        />

        {/* A single value used to be a one-way door: it could be swapped and
            never unset. Hidden on a required field, where clearing could only
            produce a state the form rejects. */}
        {clearable ? (
          <button
            type="button"
            aria-label={`Clear ${chosenLabel ?? 'selection'}`}
            className="shrink-0 rounded-chip p-0.5 text-fg-muted opacity-60 transition-opacity hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring"
            onClick={(event) => {
              // The field's own click handler opens the list and takes the
              // caret; without this, clearing would read as a click on the
              // field. Focus still returns to the input, because the ✕ it was
              // on is about to stop existing.
              event.stopPropagation()
              clear()
              search.current?.focus()
            }}
          >
            <Icon name="x-mark" variant="micro" size="3" />
          </button>
        ) : null}

        <Icon
          name="chevron-down"
          variant="mini"
          size="4"
          className={cn('shrink-0 text-fg-muted transition-transform', open && 'rotate-180')}
        />
      </div>

      {open ? (
        <PopoverPortal>
          <ul
            ref={mergeRefs(list, popover.setFloating)}
            style={popover.floatingStyles}
            id={`${fieldId}-listbox`}
            role="listbox"
            aria-multiselectable={multiple}
            aria-labelledby={label ? `${fieldId}-label` : undefined}
            // Width, position and the height cap all come from floating-ui
            // (lib/popover.tsx); `overflow-y-auto` is what makes the cap scroll
            // the list rather than clip it.
            className="z-50 origin-top overflow-y-auto rounded-control border border-border bg-surface py-1 shadow-float"
          >
            {visible.map((option, index) => (
              <li
                key={option.value}
                id={optionId(index)}
                role="option"
                data-value={option.value}
                // Nothing here is focusable now, so the `focus-visible:` half of
                // the old recipe would never match — this is what draws where
                // the keyboard is, and it has to look like the hover state
                // because it means the same thing.
                data-active={option.value === activeValue || undefined}
                aria-selected={selected.includes(option.value)}
                aria-disabled={option.disabled || undefined}
                onClick={() => choose(option)}
                className="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-body text-fg-body transition-colors hover:bg-surface-subtle hover:text-fg data-active:bg-surface-subtle data-active:text-fg aria-disabled:cursor-not-allowed aria-disabled:text-fg-subtle aria-selected:font-medium aria-selected:text-fg"
              >
                <span className="min-w-0 truncate">
                  {option.label}
                  {option.meta ? <span className="ml-2 text-meta text-fg-muted">{option.meta}</span> : null}
                </span>

                {selected.includes(option.value) ? (
                  <Icon name="check" variant="mini" size="4" className="shrink-0 text-accent" />
                ) : null}
              </li>
            ))}

            {/* An empty list with no message reads as a broken control. */}
            {visible.length === 0 ? (
              <li className="px-3 py-2 text-body text-fg-muted">{loading ? loadingText : emptyText}</li>
            ) : null}
          </ul>
        </PopoverPortal>
      ) : null}

      {error ? (
        <p id={`${fieldId}-error`} className="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
          <Icon name="exclamation-circle" size="3.5" className="mt-0.5" />
          <span>{error}</span>
        </p>
      ) : help ? (
        <p id={`${fieldId}-help`} className="mt-1.5 text-meta text-fg-muted">
          {help}
        </p>
      ) : null}
    </div>
  )
}
