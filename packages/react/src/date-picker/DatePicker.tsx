import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent, type ReactNode } from 'react'
import { addDays, addMonths, format, parseISO, startOfMonth } from 'date-fns'
import { Icon } from '../icon'
import { cn } from '../lib/cn'

/**
 * Rewritten from resources/views/components/date-picker.blade.php against
 * specs/date-picker.md, with date-fns doing the month arithmetic the Blade
 * version does by hand.
 *
 * **Dates are Y-m-d STRINGS and are compared as strings**, exactly as the Blade
 * component insists. `new Date('2026-03-29')` is UTC midnight, and in a timezone
 * west of Greenwich that is the 28th — so a picker built on Date objects selects
 * the day before the one that was clicked, for some users, some of the time.
 * String comparison on Y-m-d is both correct and ordered.
 *
 * date-fns changes nothing about that. A Date exists inside `monthGrid` and
 * nowhere else, built from `parseISO` (which reads Y-m-d as LOCAL midnight,
 * unlike the Date constructor) and turned straight back into a string.
 */

const ISO = 'yyyy-MM-dd'

const iso = (date: Date): string => format(date, ISO)

const todayIso = (): string => iso(new Date())

export interface DatePickerProps {
  /** Y-m-d, or null. With `range`, a two-element tuple. */
  value: string | null | [string | null, string | null]
  onChange: (value: string | null | [string | null, string | null]) => void
  label?: ReactNode
  /** Pick a start and an end rather than one day. */
  range?: boolean
  /** Earliest selectable date, Y-m-d. */
  min?: string
  /** Latest selectable date, Y-m-d. */
  max?: string
  placeholder?: string
  help?: ReactNode
  error?: ReactNode
  disabled?: boolean
  /** 0 = Sunday, 1 = Monday. Which day a week starts on is regional, not universal. */
  weekStartsOn?: 0 | 1
  /** Adds a "Clear" control. Off where the field is required. */
  clearable?: boolean
  /**
   * How a chosen date reads in the trigger. Default is the Y-m-d it stores.
   *
   * The package has no locale opinion and should not acquire one: an
   * application's display format is its own setting — in the ERP consuming this
   * it is a row in `system_parameters` — and a component that formatted dates
   * itself would be a second answer to a question the application has already
   * answered everywhere else. The value handed out is unaffected; this is the
   * label only.
   */
  formatValue?: (date: string) => string
  id?: string
  className?: string
}

/**
 * The cells of one month: leading nulls for the blank days, then Y-m-d strings.
 *
 * Exported because it is the only arithmetic in the component and it is worth
 * testing without a DOM.
 */
export function monthGrid(cursor: string, weekStartsOn: 0 | 1): (string | null)[] {
  const first = startOfMonth(parseISO(cursor))
  const lead = (first.getDay() - weekStartsOn + 7) % 7

  const cells: (string | null)[] = Array.from({ length: lead }, () => null)

  for (let day = first; day.getMonth() === first.getMonth(); day = addDays(day, 1)) {
    cells.push(iso(day))
  }

  return cells
}

export function DatePicker({
  value,
  onChange,
  label,
  range = false,
  min,
  max,
  placeholder,
  help,
  error,
  disabled = false,
  weekStartsOn = 1,
  clearable = true,
  formatValue = (date) => date,
  id,
  className,
}: DatePickerProps) {
  const generated = useId()
  const fieldId = id ?? `ds-${generated}`
  const describedBy = error ? `${fieldId}-error` : help ? `${fieldId}-help` : undefined

  const [start, end] = useMemo<[string | null, string | null]>(
    () =>
      range
        ? Array.isArray(value)
          ? [value[0] ?? null, value[1] ?? null]
          : [null, null]
        : [(Array.isArray(value) ? value[0] : value) ?? null, null],
    [range, value],
  )

  const [open, setOpen] = useState(false)
  const [cursor, setCursor] = useState<string>(start ?? todayIso())
  const [hovered, setHovered] = useState<string | null>(null)

  const root = useRef<HTMLDivElement>(null)
  const trigger = useRef<HTMLButtonElement>(null)

  // Reopening on the month the value is in, not on the month last browsed to.
  useEffect(() => {
    if (open) {
      setCursor(start ?? todayIso())
    }
  }, [open, start])

  useEffect(() => {
    if (!open) {
      return
    }

    const close = (event: MouseEvent) => {
      if (root.current && !root.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }

    document.addEventListener('mousedown', close)

    return () => document.removeEventListener('mousedown', close)
  }, [open])

  const days = useMemo(() => monthGrid(cursor, weekStartsOn), [cursor, weekStartsOn])

  const dayNames = useMemo(() => {
    // Built from a known Monday so the rotation is arithmetic rather than a
    // second list to keep in step with `weekStartsOn`.
    const monday = parseISO('2024-01-01')

    return Array.from({ length: 7 }, (_, offset) =>
      format(addDays(monday, (offset + weekStartsOn + 6) % 7), 'EEEEEE'),
    )
  }, [weekStartsOn])

  const isDisabled = (day: string) => (min != null && day < min) || (max != null && day > max)

  const isSelected = (day: string) => day === start || (range && day === end)

  const isBetween = (day: string) => {
    if (!range || start == null) {
      return false
    }

    const other = end ?? hovered

    return other != null && ((day > start && day < other) || (day < start && day > other))
  }

  const pick = (day: string) => {
    if (isDisabled(day)) {
      return
    }

    if (!range) {
      onChange(day)
      setOpen(false)
      trigger.current?.focus()

      return
    }

    // A second click completes the period; a third starts a new one. Clicking
    // before the start swaps them rather than refusing, which is what someone
    // who picked the wrong end first expects.
    if (start == null || end != null) {
      onChange([day, null])

      return
    }

    onChange(day < start ? [day, start] : [start, day])
    setHovered(null)
  }

  const summary = range
    ? start == null
      ? null
      : end == null
        ? `${formatValue(start)} – …`
        : `${formatValue(start)} – ${formatValue(end)}`
    : start == null
      ? null
      : formatValue(start)

  const onGridKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    const steps: Record<string, number> = {
      ArrowLeft: -1,
      ArrowRight: 1,
      ArrowUp: -7,
      ArrowDown: 7,
      PageUp: -30,
      PageDown: 30,
    }

    const focused = (document.activeElement as HTMLElement | null)?.dataset.day

    if (event.key === 'Escape') {
      event.stopPropagation()
      setOpen(false)
      trigger.current?.focus()

      return
    }

    const step = steps[event.key]

    if (step === undefined || focused == null) {
      return
    }

    event.preventDefault()

    const target = iso(addDays(parseISO(focused), step))

    // Walking off the edge of the month moves the grid with the reader rather
    // than stopping them at the 1st.
    setCursor(target)

    requestAnimationFrame(() => {
      root.current?.querySelector<HTMLElement>(`[data-day="${target}"]`)?.focus()
    })
  }

  return (
    <div ref={root} className={cn('relative block w-full', className)}>
      {label ? (
        <label id={`${fieldId}-label`} htmlFor={fieldId} className="mb-1.5 block text-body font-medium text-fg">
          {label}
        </label>
      ) : null}

      <button
        ref={trigger}
        type="button"
        id={fieldId}
        disabled={disabled}
        onClick={() => setOpen((was) => !was)}
        onKeyDown={(event) => {
          if (event.key === 'ArrowDown') {
            event.preventDefault()
            setOpen(true)
          }
        }}
        aria-haspopup="dialog"
        aria-expanded={open}
        aria-labelledby={label ? `${fieldId}-label ${fieldId}` : undefined}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy}
        className={cn(
          'flex w-full items-center justify-between gap-2 rounded-control border bg-surface',
          'px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] text-left',
          'sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
          'text-body-touch sm:text-body shadow-raised transition-colors',
          'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
          error ? 'border-danger' : 'border-border',
          disabled ? 'cursor-not-allowed bg-surface-subtle text-fg-subtle' : 'cursor-pointer hover:border-fg/20',
        )}
      >
        <span className={cn('truncate', summary ? 'text-fg' : 'text-fg-muted')}>
          {summary ?? placeholder ?? (range ? 'Choose a period' : 'Choose a date')}
        </span>
        <Icon name="calendar" variant="mini" size="5" className="shrink-0 text-fg-muted sm:size-4" />
      </button>

      {open ? (
        <div
          role="dialog"
          aria-modal={false}
          aria-labelledby={label ? `${fieldId}-label` : undefined}
          className="absolute z-50 mt-1 w-max origin-top rounded-control border border-border bg-surface p-3 shadow-float"
        >
          <div className="mb-2 flex items-center justify-between gap-2">
            <button
              type="button"
              aria-label="Previous month"
              onClick={() => setCursor(iso(addMonths(parseISO(cursor), -1)))}
              className="rounded-control p-1.5 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
              <Icon name="chevron-left" variant="mini" size="4" />
            </button>

            {/* A live region, because pressing the arrows changes the grid the
                reader is standing in and nothing else announces it. */}
            <span className="text-body font-medium text-fg" aria-live="polite">
              {format(parseISO(cursor), 'MMMM yyyy')}
            </span>

            <button
              type="button"
              aria-label="Next month"
              onClick={() => setCursor(iso(addMonths(parseISO(cursor), 1)))}
              className="rounded-control p-1.5 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
              <Icon name="chevron-right" variant="mini" size="4" />
            </button>
          </div>

          <div role="grid" onKeyDown={onGridKeyDown} onMouseLeave={() => setHovered(null)}>
            <div role="row" className="grid grid-cols-7">
              {dayNames.map((day) => (
                <div key={day} role="columnheader" className="py-1 text-center text-meta font-medium text-fg-muted">
                  {day}
                </div>
              ))}
            </div>

            <div className="grid grid-cols-7 gap-0.5">
              {days.map((day, index) => (
                <div key={day ?? `blank-${index}`} role="gridcell" className="flex">
                  {day === null ? (
                    <span className="size-9" />
                  ) : (
                    <button
                      type="button"
                      data-day={day}
                      disabled={isDisabled(day)}
                      // Exactly one day is in the tab order, so Tab enters the
                      // grid once and the arrows do the walking.
                      tabIndex={day === (start ?? cursor) ? 0 : -1}
                      aria-selected={isSelected(day)}
                      aria-current={day === todayIso() ? 'date' : undefined}
                      onClick={() => pick(day)}
                      onMouseEnter={() => setHovered(day)}
                      className={cn(
                        'size-9 rounded-control text-body tabular-nums transition-colors',
                        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring',
                        'disabled:cursor-not-allowed disabled:text-fg-subtle',
                        isSelected(day)
                          ? 'bg-surface-inverse font-medium text-on-inverse'
                          : isBetween(day)
                            ? 'bg-accent-wash text-fg'
                            : day === todayIso()
                              ? 'font-semibold text-fg'
                              : 'text-fg-body hover:bg-surface-subtle',
                      )}
                    >
                      {Number(day.slice(8))}
                    </button>
                  )}
                </div>
              ))}
            </div>
          </div>

          <div className="mt-2 flex items-center justify-between gap-2 border-t border-divider pt-2">
            {clearable ? (
              <button
                type="button"
                onClick={() => {
                  onChange(range ? [null, null] : null)
                  setHovered(null)
                }}
                className="rounded-control px-2 py-1 text-meta font-medium text-fg-muted transition-colors hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
              >
                Clear
              </button>
            ) : (
              <span />
            )}

            <button
              type="button"
              onClick={() => {
                setOpen(false)
                trigger.current?.focus()
              }}
              className="rounded-control px-2 py-1 text-meta font-medium text-fg transition-colors hover:bg-surface-subtle focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
              Done
            </button>
          </div>
        </div>
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
