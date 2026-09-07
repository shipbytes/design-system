import { useId, type InputHTMLAttributes, type ReactNode } from 'react'
import { cn } from '../lib/cn'

/**
 * Copied from resources/views/components/radio.blade.php.
 * See specs/radio.md.
 *
 * Construction is the checkbox's, deliberately: the native input, styled, in a
 * grid cell. A visually-hidden input with a decorated <span> beside it loses
 * Windows high-contrast rendering, the browser's own focus behaviour and
 * forced-colors support, and every one of those failures is invisible in a
 * normal browser.
 *
 * **A dot, not a tick.** The shape is the difference between "one of these" and
 * "any of these", and it is the only signal a reader gets before they click.
 * Round and ticked is the worst of both — which is why the mark here is a
 * `::before`-style filled circle rather than the checkbox's path.
 *
 * The dot is drawn with `checked:bg-[radial-gradient(...)]` on the input itself
 * rather than as a sibling element, because a radio's mark has no
 * indeterminate state to coordinate with and a second stacked node would be
 * one more thing to keep from swallowing the click.
 */
export interface RadioProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  /** Always required — an unlabelled radio is unusable and untappable. */
  label: ReactNode
  /** The footnote an individual option sometimes needs. */
  help?: ReactNode
}

export function Radio({ label, help, disabled = false, className, id, ...props }: RadioProps) {
  const generated = useId()
  const inputId = id ?? `ds-${generated}`

  return (
    <div className={cn('flex gap-2.5', className)}>
      {/* One grid cell, as the checkbox's mark is: an absolutely positioned
          dot drifts against a label that wraps to two lines. */}
      <div className="grid shrink-0 grid-cols-1 grid-rows-1 place-items-center pt-0.5">
        <input
          type="radio"
          id={inputId}
          disabled={disabled}
          aria-describedby={help ? `${inputId}-help` : undefined}
          className={cn(
            'peer col-start-1 row-start-1 size-4.5 shrink-0 appearance-none rounded-full',
            'border border-border-strong bg-surface transition-colors',
            'checked:border-accent checked:bg-accent',
            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
            disabled
              ? 'cursor-not-allowed bg-surface-subtle checked:border-fg-subtle checked:bg-fg-subtle'
              : 'cursor-pointer hover:border-fg/30',
            // forced-colors: the OS palette removes the fill, so the mark has
            // to survive on its own or a chosen option looks unchosen.
            'forced-colors:appearance-auto',
          )}
          {...props}
        />

        {/* pointer-events-none so the dot never swallows the click that
            belongs to the input underneath it. */}
        <span
          className="pointer-events-none col-start-1 row-start-1 size-1.5 rounded-full bg-on-accent opacity-0 peer-checked:opacity-100"
          aria-hidden="true"
        />
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

        {help ? (
          <p id={`${inputId}-help`} className="mt-0.5 text-meta text-fg-muted">
            {help}
          </p>
        ) : null}
      </div>
    </div>
  )
}
