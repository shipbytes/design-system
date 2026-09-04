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
 * browser, and CLAUDE.md's known gap 7 records that server-side filtering was
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

export function Combobox({
  options,
  value,
  onChange,
  label,
  placeholder = 'Search…',
  multiple = false,
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

  /** Set when ArrowDown opened the list, so focus lands once it exists. */
  const [entering, setEntering] = useState(false)

  const root = useRef<HTMLDivElement>(null)
  const search = useRef<HTMLInputElement>(null)
  const list = useRef<HTMLUListElement>(null)

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

  useEffect(() => {
    if (!open) {
      return
    }

    const close = (event: MouseEvent) => {
      if (root.current && !root.current.contains(event.target as Node)) {
        setOpen(false)
        setQuery('')
      }
    }

    document.addEventListener('mousedown', close)

    return () => document.removeEventListener('mousedown', close)
  }, [open])

  const firstOption = () =>
    list.current?.querySelector<HTMLElement>('[role="option"]:not([aria-disabled="true"])') ?? null

  useEffect(() => {
    if (!entering || !open) {
      return
    }

    firstOption()?.focus()
    setEntering(false)
  }, [entering, open])

  const type = (next: string) => {
    setQuery(next)
    setOpen(true)
    onQueryChange?.(next)
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

  /*
   * Arrow keys walk the FILTERED list, so the handler reads the DOM rather than
   * the options array — what is on screen is the only correct source once a
   * filter is applied.
   */
  const moveFocus = (event: KeyboardEvent) => {
    const items = [...(list.current?.querySelectorAll<HTMLElement>('[role="option"]:not([aria-disabled="true"])') ?? [])]

    if (items.length === 0) {
      return
    }

    event.preventDefault()

    const at = items.indexOf(document.activeElement as HTMLElement)
    const to =
      event.key === 'Home'
        ? 0
        : event.key === 'End'
          ? items.length - 1
          : event.key === 'ArrowUp'
            ? at <= 0
              ? items.length - 1
              : at - 1
            : at === items.length - 1
              ? 0
              : at + 1

    items[to]?.focus()
  }

  const onListKeyDown = (event: KeyboardEvent) => {
    if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
      moveFocus(event)

      return
    }

    if (event.key === 'Escape') {
      event.stopPropagation()
      setOpen(false)
      search.current?.focus()
    }
  }

  const onSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'ArrowDown') {
      event.preventDefault()

      // Already open: the options are in the DOM, so focus can land now. Closed:
      // there is nothing to focus until React has rendered the list, and the
      // effect above does it.
      if (open && firstOption()) {
        firstOption()?.focus()
      } else {
        setOpen(true)
        setEntering(true)
      }

      return
    }

    if (event.key === 'Escape') {
      event.stopPropagation()
      setOpen(false)

      return
    }

    // Backspace on an empty query removes the last chip. Without it the only
    // way to undo a selection is to aim at a 12px ✕.
    if (event.key === 'Backspace' && multiple && query === '' && selected.length > 0) {
      onChange(selected.slice(0, -1))
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
          value={query}
          onChange={(event) => type(event.target.value)}
          onFocus={() => setOpen(true)}
          onKeyDown={onSearchKeyDown}
          autoComplete="off"
          role="combobox"
          aria-autocomplete="list"
          aria-expanded={open}
          aria-controls={`${fieldId}-listbox`}
          aria-labelledby={label ? `${fieldId}-label` : undefined}
          aria-invalid={error ? true : undefined}
          aria-describedby={describedBy}
          disabled={disabled}
          placeholder={!multiple && selected.length > 0 ? labelFor(selected[0]!) : placeholder}
          className="min-w-24 flex-1 border-0 bg-transparent px-1 py-0.5 text-body text-fg outline-hidden placeholder:text-fg-muted disabled:cursor-not-allowed"
        />

        <Icon
          name="chevron-down"
          variant="mini"
          size="4"
          className={cn('shrink-0 text-fg-muted transition-transform', open && 'rotate-180')}
        />
      </div>

      {open ? (
        <ul
          ref={list}
          id={`${fieldId}-listbox`}
          role="listbox"
          aria-multiselectable={multiple}
          aria-labelledby={label ? `${fieldId}-label` : undefined}
          onKeyDown={onListKeyDown}
          // max-h so a long list scrolls inside itself instead of running off
          // the page.
          className="absolute z-50 mt-1 max-h-60 w-full origin-top overflow-y-auto rounded-control border border-border bg-surface py-1 shadow-float"
        >
          {visible.map((option) => (
            <li
              key={option.value}
              role="option"
              tabIndex={-1}
              data-value={option.value}
              aria-selected={selected.includes(option.value)}
              aria-disabled={option.disabled || undefined}
              onClick={() => choose(option)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                  event.preventDefault()
                  choose(option)
                }
              }}
              className="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-body text-fg-body transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:bg-surface-subtle focus-visible:text-fg focus-visible:outline-hidden aria-disabled:cursor-not-allowed aria-disabled:text-fg-subtle aria-selected:font-medium aria-selected:text-fg"
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
