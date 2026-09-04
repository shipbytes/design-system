import { useEffect, useId, useRef, type InputHTMLAttributes, type ReactNode } from 'react'
import { cn } from '../lib/cn'

/**
 * Copied from resources/views/components/checkbox.blade.php.
 * See specs/checkbox.md.
 *
 * The native input, styled — not a hidden input beside a decorated <span>. A
 * visually-hidden checkbox with a fake box next to it loses the Windows
 * high-contrast rendering, the browser's own focus behaviour and forced-colors
 * support, and every one of those failures is invisible in a normal browser.
 * `appearance-none` keeps the element and takes only its paint.
 */
export interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  /** Always required — an unlabelled checkbox is unusable and untappable. */
  label: ReactNode
  /** Guidance under the label. Replaced by `error`, never stacked with it. */
  help?: ReactNode
  /** Validation message. Its presence styles the control and sets aria-invalid. */
  error?: ReactNode
  /** Neither checked nor unchecked — a parent whose children disagree. */
  indeterminate?: boolean
}

export function Checkbox({
  label,
  help,
  error,
  indeterminate = false,
  disabled = false,
  className,
  id,
  ...props
}: CheckboxProps) {
  const generated = useId()
  const inputId = id ?? `ds-${generated}`
  const describedBy = error ? `${inputId}-error` : help ? `${inputId}-help` : undefined

  const ref = useRef<HTMLInputElement>(null)

  /*
   * `indeterminate` is a DOM PROPERTY. There is no attribute that sets it, so
   * it has to be written after render — and the styling keys off the
   * `:indeterminate` pseudo-class, i.e. the property, not this prop. A box
   * styled from the prop instead would draw a mixed mark over a control that
   * reports "unchecked" to a screen reader.
   */
  useEffect(() => {
    if (ref.current) {
      ref.current.indeterminate = indeterminate
    }
  }, [indeterminate])

  return (
    <div className={cn('flex gap-2.5', className)}>
      {/* The input and its mark are stacked in ONE grid cell rather than the
          mark being absolutely positioned. Absolute positioning against a label
          that wraps to two lines drifts; a grid cell cannot. */}
      <div className="grid shrink-0 grid-cols-1 grid-rows-1 place-items-center pt-0.5">
        <input
          ref={ref}
          type="checkbox"
          id={inputId}
          disabled={disabled}
          aria-invalid={error ? true : undefined}
          aria-describedby={describedBy}
          className={cn(
            'peer col-start-1 row-start-1 size-4.5 shrink-0 appearance-none rounded-chip',
            'border bg-surface transition-colors',
            'checked:border-accent checked:bg-accent',
            'indeterminate:border-accent indeterminate:bg-accent',
            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
            error ? 'border-danger' : 'border-border-strong',
            disabled
              ? 'cursor-not-allowed bg-surface-subtle checked:bg-fg-subtle checked:border-fg-subtle'
              : 'cursor-pointer hover:border-fg/30',
            // forced-colors: the fill is removed by the OS palette, so the mark
            // has to survive on its own. Without this the box looks empty when
            // checked.
            'forced-colors:appearance-auto',
          )}
          {...props}
        />

        {/* pointer-events-none so the mark never swallows the click that
            belongs to the input underneath it. */}
        <svg
          className="pointer-events-none col-start-1 row-start-1 size-3 text-on-accent opacity-0 peer-checked:opacity-100 peer-indeterminate:opacity-0"
          viewBox="0 0 12 12"
          fill="none"
          stroke="currentColor"
          strokeWidth={2}
          strokeLinecap="round"
          strokeLinejoin="round"
          aria-hidden="true"
        >
          <path d="M2.5 6.5 5 9l4.5-5.5" />
        </svg>

        <svg
          className="pointer-events-none col-start-1 row-start-1 size-3 text-on-accent opacity-0 peer-indeterminate:opacity-100"
          viewBox="0 0 12 12"
          fill="none"
          stroke="currentColor"
          strokeWidth={2}
          strokeLinecap="round"
          aria-hidden="true"
        >
          <path d="M2.5 6h7" />
        </svg>
      </div>

      <div className="min-w-0">
        {/* A real <label for>, so its whole width is a click target. That is
            most of the touch target on a phone. */}
        <label
          htmlFor={inputId}
          className={cn(
            'block text-body text-fg',
            disabled ? 'cursor-not-allowed text-fg-muted' : 'cursor-pointer',
          )}
        >
          {label}
        </label>

        {error ? (
          <p id={`${inputId}-error`} className="mt-0.5 text-meta text-danger">
            {error}
          </p>
        ) : help ? (
          <p id={`${inputId}-help`} className="mt-0.5 text-meta text-fg-muted">
            {help}
          </p>
        ) : null}
      </div>
    </div>
  )
}
