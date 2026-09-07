import { Children, cloneElement, isValidElement, useId, type ReactElement, type ReactNode } from 'react'
import { cn } from '../lib/cn'
import type { RadioProps } from './Radio'

/**
 * Copied from resources/views/components/radio-group.blade.php.
 * See specs/radio.md.
 *
 * **A `<fieldset>` and a `<legend>`, not a `<div>` with a heading**, and this
 * is the whole reason the component exists. A radio announces its own label and
 * nothing else, so "Monthly" is read out without ever saying what is being
 * chosen; the legend is what a screen reader repeats as the group is entered,
 * and it is the only thing that turns four options into a question.
 *
 * Two consequences the spec draws out, both implemented here:
 *
 * **The error belongs to the group.** "Choose a plan" is about the question.
 * Attaching it to the first radio would say the first radio is invalid, which
 * it is not — so `error` is a prop of this and not of {@see Radio}, and there
 * is nowhere to put a per-option one.
 *
 * **One `name` across the group.** That is what makes them a group to the
 * browser, and it is what makes the arrow keys work — the browser provides
 * arrow-key navigation for nothing, which is a large part of why radios are not
 * reimplemented as buttons. The group hands its name down to each child rather
 * than asking every call site to repeat it, because a group whose options
 * carried different names would look right and behave as several groups of one.
 */
export interface RadioGroupProps {
  /** The question. Rendered as a `<legend>` — never omit it. */
  legend: ReactNode
  /**
   * The shared `name`. Generated when absent, which is right for an
   * uncontrolled group and wrong for a form that posts: give it the field name.
   */
  name?: string
  /** Guidance under the legend. Replaced by `error`, never stacked with it. */
  help?: ReactNode
  /** Validation message for the QUESTION. Sets `aria-invalid` on the group. */
  error?: ReactNode
  /** Lay the options out in a row. For two short options only. */
  inline?: boolean
  disabled?: boolean
  className?: string
  children: ReactNode
}

export function RadioGroup({
  legend,
  name,
  help,
  error,
  inline = false,
  disabled = false,
  className,
  children,
}: RadioGroupProps) {
  const generated = useId()
  const groupId = `ds-${generated}`
  const groupName = name ?? groupId
  const describedBy = error ? `${groupId}-error` : help ? `${groupId}-help` : undefined

  return (
    <fieldset
      className={cn('min-w-0', className)}
      disabled={disabled}
      aria-invalid={error ? true : undefined}
      aria-describedby={describedBy}
    >
      <legend className="text-body font-medium text-fg">{legend}</legend>

      {/* Announced once on entry, from the fieldset, rather than repeated by
          every option. */}
      {error ? (
        <p id={`${groupId}-error`} className="mt-0.5 text-meta text-danger">
          {error}
        </p>
      ) : help ? (
        <p id={`${groupId}-help`} className="mt-0.5 text-meta text-fg-muted">
          {help}
        </p>
      ) : null}

      <div className={cn('mt-2', inline ? 'flex flex-wrap gap-x-6 gap-y-2' : 'space-y-2')}>
        {/*
          The name is pushed down rather than asked for at each call site. A
          child that names itself keeps its own name, because a group of one
          question sometimes contains a deliberately separate control.
        */}
        {Children.map(children, (child) =>
          isValidElement<RadioProps>(child) && child.props.name === undefined
            ? cloneElement(child as ReactElement<RadioProps>, { name: groupName })
            : child,
        )}
      </div>
    </fieldset>
  )
}
