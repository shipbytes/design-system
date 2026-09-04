import type { ReactNode } from 'react'
import * as Menu from '@radix-ui/react-dropdown-menu'
import { cn } from '../lib/cn'
import { Icon } from '../icon/Icon'

/*
 * specs/dropdown.md.
 *
 * A menu of VERBS anchored to the control that opened it. Not a select: an item
 * that does something is a menu item, an item that becomes a field's value is an
 * option, and reaching for this to build a form control produces a control that
 * submits nothing.
 *
 * Radix DropdownMenu supplies the whole accessibility list the spec sets out —
 * role="menu", roving tabindex, arrow keys, Home/End, disabled items skipped
 * rather than merely dimmed, focus into the first item and back to the trigger,
 * Escape, Tab-closes, click-outside without a click-swallowing backdrop — plus
 * the live `aria-expanded` on the CONSUMER'S trigger element, which the Blade
 * version has to set by hand at runtime because it cannot know what the slot
 * contained.
 *
 * It also brings collision detection, which the Blade version explicitly does
 * not have ("a menu near the bottom of the page takes top-* from whoever placed
 * it"). That is the placement prop becoming a preference rather than a
 * commitment, which is the better of the two behaviours.
 */

/** A closed set, mapped onto Radix's side/align pair. */
const placements = {
  'bottom-start': { side: 'bottom', align: 'start' },
  'bottom-end': { side: 'bottom', align: 'end' },
  'top-start': { side: 'top', align: 'start' },
  'top-end': { side: 'top', align: 'end' },
} as const

export type DropdownPlacement = keyof typeof placements

export interface DropdownProps {
  /** The consumer's own element — usually a Button. */
  trigger: ReactNode
  /**
   * `bottom-end` is the default because the overwhelming case is a trigger on
   * the right of a row, where a menu opening left-aligned runs off the page.
   */
  placement?: DropdownPlacement
  /**
   * Optional, unlike modal's. A dropdown's state is local to its own trigger and
   * shared with nothing, and a kebab menu on every row of a table would
   * otherwise need a wrapper per row. Pass it and the host stays in charge.
   */
  open?: boolean
  onOpenChange?: (open: boolean) => void
  className?: string
  children: ReactNode
}

export function Dropdown({
  trigger,
  placement = 'bottom-end',
  open,
  onOpenChange,
  className,
  children,
}: DropdownProps) {
  const { side, align } = placements[placement] ?? placements['bottom-end']

  return (
    <Menu.Root open={open} onOpenChange={onOpenChange}>
      {/*
       * asChild so the trigger is the consumer's element rather than a button
       * wrapped in a button — and so Radix puts aria-haspopup and a live
       * aria-expanded on it. Without those, nothing announces that the control
       * owns a menu or that the menu is open: the accessibility equivalent of
       * the unstyled-vendor bug — looks fine, is broken, reports nothing.
       */}
      <Menu.Trigger asChild>{trigger}</Menu.Trigger>

      <Menu.Portal>
        <Menu.Content
          side={side}
          align={align}
          sideOffset={8}
          className={cn(
            // `float`, not `overlay`: this hovers over the page, it does not
            // block it. The shadow scale is where the system says which.
            'z-50 min-w-52 overflow-hidden rounded-control border border-border bg-surface py-1 shadow-float',
            className,
          )}
        >
          {children}
        </Menu.Content>
      </Menu.Portal>
    </Menu.Root>
  )
}

const tones = {
  neutral: 'text-fg-body hover:bg-surface-subtle hover:text-fg data-highlighted:bg-surface-subtle data-highlighted:text-fg',
  danger: 'text-danger hover:bg-danger-wash data-highlighted:bg-danger-wash',
} as const

export type DropdownItemTone = keyof typeof tones

export interface DropdownItemProps {
  /** Heroicon name for the leading mark. Optional — a menu of plain verbs is fine. */
  icon?: string
  /** Navigates. Anything that goes somewhere should be an <a>, not a button. */
  href?: string
  /**
   * neutral | danger. Two tones only: a tone is a claim about what the item IS,
   * not a way to tell items apart. `danger` is the one that destroys something.
   */
  tone?: DropdownItemTone
  disabled?: boolean
  onSelect?: () => void
  className?: string
  children: ReactNode
}

export function DropdownItem({
  icon,
  href,
  tone = 'neutral',
  disabled = false,
  onSelect,
  className,
  children,
}: DropdownItemProps) {
  const toneClasses = tones[tone] ?? tones.neutral

  const body = (
    <>
      {icon ? (
        // `fg-subtle` is allowed here because the label beside it says the same
        // thing — the redundant-information exception. It would not be allowed
        // on an icon that was the only thing carrying the meaning.
        <Icon
          name={icon}
          size={4}
          className={tone === 'danger' ? 'text-danger' : 'text-fg-subtle'}
        />
      ) : null}
      <span className="min-w-0 flex-1 truncate">{children}</span>
    </>
  )

  const classes = cn(
    'flex w-full cursor-default items-center gap-2.5 px-3 py-2 text-left text-body',
    // No focus RING — a ring inside a menu with 4px of vertical padding clips
    // against the rounded edge. The hover fill doubles as the focus fill, which
    // is why `data-highlighted` styles to the same value.
    'outline-hidden',
    disabled ? 'cursor-not-allowed text-fg-subtle' : toneClasses,
    className,
  )

  return (
    <Menu.Item
      disabled={disabled}
      onSelect={onSelect}
      className={classes}
      // A disabled item is never a link: a disabled <a> is still focusable and
      // still followable by keyboard, so the only reliable way to stop it going
      // anywhere is for it not to be a link at all.
      asChild={Boolean(href) && !disabled}
    >
      {href && !disabled ? <a href={href}>{body}</a> : body}
    </Menu.Item>
  )
}
