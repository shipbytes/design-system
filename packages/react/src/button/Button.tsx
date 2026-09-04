import { cva, type VariantProps } from 'class-variance-authority'
import type { AnchorHTMLAttributes, ButtonHTMLAttributes, ReactNode } from 'react'
import { cn } from '../lib/cn'

/**
 * Class recipe copied verbatim from resources/views/components/button.blade.php.
 *
 * The `calc(step - 1px)` padding absorbs the always-present 1px border so a
 * `primary` sits exactly where a `secondary` would. Removing the calc makes
 * filled and outlined buttons disagree by 2px. See specs/button.md.
 */
export const buttonRecipe = cva(
  [
    // `isolate` keeps the focus ring from being clipped by a parent stacking
    // context, which is why it is on the base rather than the focus state.
    'relative isolate inline-flex items-center justify-center gap-2',
    'border font-semibold whitespace-nowrap transition-colors',
    // focus-visible only: a mouse click should not leave a ring behind.
    'focus:outline-hidden focus-visible:outline-2 focus-visible:outline-offset-2',
    'focus-visible:outline-focus-ring',
  ],
  {
    variants: {
      variant: {
        primary: 'border-transparent bg-surface-inverse text-on-inverse hover:bg-surface-inverse-hover',
        secondary: 'border-border bg-surface text-fg shadow-raised hover:bg-surface-subtle hover:border-fg/20',
        ghost: 'border-transparent bg-transparent text-fg-body hover:bg-fg/5 hover:text-fg',
        danger: 'border-transparent bg-danger text-on-danger hover:bg-danger-hover',
      },
      size: {
        sm: 'h-control-sm px-[calc(--spacing(2.5)-1px)] text-meta',
        // md is ONE component at two breakpoints, not two components. That is
        // what gets a 44px touch target on a phone without a mobile variant.
        md: 'h-control-lg px-[calc(--spacing(3.5)-1px)] text-body-touch sm:h-control-md sm:px-[calc(--spacing(3)-1px)] sm:text-body',
        lg: 'h-control-lg px-[calc(--spacing(3.5)-1px)] text-body-touch',
        fab: 'size-control-fab',
      },
      iconOnly: { true: '', false: '' },
      pill: { true: 'rounded-full', false: 'rounded-control' },
      loading: {
        // A loading button carries `disabled` to stop double submits, so the
        // disabled: styles would otherwise fade it — and faded reads as
        // "refused" rather than "working", which are opposite meanings.
        true: 'cursor-wait',
        false: 'disabled:opacity-50 disabled:cursor-not-allowed',
      },
    },
    compoundVariants: [
      { iconOnly: true, size: 'sm', class: 'size-control-sm px-0' },
      { iconOnly: true, size: 'md', class: 'size-control-lg px-0 sm:size-control-md' },
      { iconOnly: true, size: 'lg', class: 'size-control-lg px-0' },
    ],
    defaultVariants: {
      variant: 'primary',
      size: 'md',
      iconOnly: false,
      pill: false,
      loading: false,
    },
  },
)

type Recipe = VariantProps<typeof buttonRecipe>

interface CommonProps {
  variant?: Recipe['variant']
  size?: Recipe['size']
  /** Square, no label. Requires an accessible name via aria-label. */
  iconOnly?: boolean
  /** Pill shape. The mobile FAB is this component, not a separate one. */
  pill?: boolean
  /**
   * Shows a spinner and blocks re-clicks. A second submit while the first is in
   * flight is the bug this state exists to prevent. It does NOT replace the
   * label, which would make the button jump.
   */
  loading?: boolean
  children?: ReactNode
}

export type ButtonProps = CommonProps &
  Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'> & { href?: undefined }

export type ButtonLinkProps = CommonProps &
  Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'children'> & { href: string }

function Spinner() {
  return (
    <svg
      className="size-4 shrink-0 animate-spin"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={2.5}
      strokeLinecap="round"
      aria-hidden="true"
    >
      <path d="M12 3a9 9 0 1 0 9 9" />
    </svg>
  )
}

export function Button(props: ButtonProps | ButtonLinkProps) {
  const {
    variant,
    size,
    iconOnly = false,
    pill = false,
    loading = false,
    className,
    children,
    ...rest
  } = props

  const classes = cn(
    buttonRecipe({ variant, size, iconOnly, pill, loading }),
    className,
  )

  if ('href' in rest && rest.href !== undefined) {
    const { href, ...anchorProps } = rest as AnchorHTMLAttributes<HTMLAnchorElement>

    // Anything that navigates is an <a>, never a <button> with a click handler.
    // A disabled link is not a thing the platform has, so the href is dropped
    // instead — the element stays in the tab order and announces as disabled.
    return (
      <a
        {...anchorProps}
        href={loading || anchorProps['aria-disabled'] === true ? undefined : href}
        className={classes}
        aria-disabled={loading || anchorProps['aria-disabled'] === true ? true : undefined}
        aria-busy={loading || undefined}
      >
        {loading ? <Spinner /> : null}
        {children}
      </a>
    )
  }

  const { disabled, type, ...buttonProps } = rest as ButtonHTMLAttributes<HTMLButtonElement>

  return (
    <button
      {...buttonProps}
      // A bare <button> defaults to type=submit, which is how a "Cancel" button
      // ends up saving the form it sits in.
      type={type ?? 'button'}
      disabled={disabled || loading}
      aria-disabled={disabled || loading || undefined}
      aria-busy={loading || undefined}
      className={classes}
    >
      {loading ? <Spinner /> : null}
      {children}
    </button>
  )
}
