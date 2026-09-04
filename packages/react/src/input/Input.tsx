import type {
  InputHTMLAttributes,
  ReactNode,
  SelectHTMLAttributes,
  TextareaHTMLAttributes,
} from 'react'
import { useId } from 'react'
import { cn } from '../lib/cn'
import { Icon } from '../icon/Icon'

/**
 * The Catalyst two-layer construction, kept exactly (specs/input.md):
 *
 *   ::before  inset by 1px — the fill and the shadow, at a radius one pixel
 *             tighter so it nests inside the outline
 *   ::after   inset 0 — the focus ring, drawn INWARD
 *
 * Drawing the ring inward is the whole point: the box never changes size on
 * focus, so nothing reflows and the fields below do not shift.
 */
function shellClasses(disabled: boolean): string {
  return cn(
    'relative block w-full',
    'before:absolute before:inset-px before:rounded-[calc(var(--ds-radius-control)-1px)]',
    'before:bg-surface before:shadow-raised',
    'after:pointer-events-none after:absolute after:inset-0 after:rounded-control',
    'after:ring-transparent after:ring-inset',
    'sm:focus-within:after:ring-2 sm:focus-within:after:ring-focus-ring',
    disabled && 'before:bg-surface-subtle before:shadow-none',
  )
}

function controlClasses(opts: {
  error: boolean
  disabled: boolean
  hasIcon: boolean
  isSelect: boolean
}): string {
  return cn(
    'relative block w-full appearance-none rounded-control bg-transparent',
    // calc(step - 1px) absorbs the border so the control lands on the 44px/36px
    // grid rather than 2px over it.
    'px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)]',
    'sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
    'text-body-touch text-fg placeholder:text-fg-muted sm:text-body',
    'border focus:outline-hidden',
    opts.error ? 'border-danger hover:border-danger' : 'border-border hover:border-fg/20',
    opts.disabled && 'text-fg-subtle',
    opts.hasIcon && 'pl-9 sm:pl-9',
    opts.isSelect && 'pr-9 sm:pr-8',
  )
}

interface FieldProps {
  /** Omit only when an adjacent visible label already names the field. */
  label?: string
  /** Guidance under the field. REPLACED by `error`, never stacked with it. */
  help?: string
  /** Its presence styles the control and sets aria-invalid. */
  error?: string
  /** Heroicon name rendered inside the control, before the value. */
  icon?: string
  /** Class for the wrapper, not the control — the control's own is the recipe. */
  className?: string
  children?: ReactNode
}

export type InputProps = FieldProps &
  Omit<InputHTMLAttributes<HTMLInputElement>, 'className' | 'children'> & { as?: 'input' }

export type SelectProps = FieldProps &
  Omit<SelectHTMLAttributes<HTMLSelectElement>, 'className'> & { as: 'select' }

export type TextareaProps = FieldProps &
  Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'className' | 'children'> & {
    as: 'textarea'
    rows?: number
  }

export function Input(props: InputProps | SelectProps | TextareaProps) {
  const { as = 'input', label, help, error, icon, className, children, ...rest } = props as
    & FieldProps
    & { as?: 'input' | 'select' | 'textarea'; id?: string; disabled?: boolean; rows?: number }
    & Record<string, unknown>

  const generated = useId()
  const id = (rest.id as string | undefined) ?? `ds-${generated}`
  const describedBy = error ? `${id}-error` : help ? `${id}-help` : undefined
  const disabled = Boolean(rest.disabled)

  const shared = {
    ...rest,
    id,
    className: controlClasses({
      error: Boolean(error),
      disabled,
      hasIcon: Boolean(icon),
      isSelect: as === 'select',
    }),
    'aria-describedby': describedBy,
    'aria-invalid': error ? true : undefined,
  }

  return (
    <div className={className}>
      {label ? (
        // A real <label for>. A placeholder is not a label: it vanishes on
        // input and fails contrast.
        <label htmlFor={id} className="block text-body font-medium text-fg">
          {label}
        </label>
      ) : null}

      <span className={cn(shellClasses(disabled), label && 'mt-1.5')}>
        {icon ? (
          <span className="pointer-events-none absolute inset-y-0 left-3 z-10 flex items-center text-fg-muted">
            <Icon name={icon} />
          </span>
        ) : null}

        {as === 'textarea' ? (
          <textarea {...(shared as TextareaHTMLAttributes<HTMLTextAreaElement>)} rows={(rest.rows as number) ?? 3} />
        ) : as === 'select' ? (
          <>
            <select {...(shared as SelectHTMLAttributes<HTMLSelectElement>)}>{children}</select>
            {/* pointer-events-none so a click on the caret still opens the select */}
            <span className="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-fg-muted">
              {/* 20px below sm, 16px above: a touch target on a phone, a hint on a desktop. */}
              <Icon name="chevron-down" variant="mini" size={5} className="sm:size-4" />
            </span>
          </>
        ) : (
          <input {...(shared as InputHTMLAttributes<HTMLInputElement>)} />
        )}
      </span>

      {error ? (
        <p id={`${id}-error`} className="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
          <Icon name="exclamation-circle" size={3.5} className="mt-0.5" />
          <span>{error}</span>
        </p>
      ) : help ? (
        // Help is REPLACED by the error, never stacked with it: two lines of
        // guidance under one field is one line too many.
        <p id={`${id}-help`} className="mt-1.5 text-meta text-fg-muted">
          {help}
        </p>
      ) : null}
    </div>
  )
}
