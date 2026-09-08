import { cloneElement, useCallback, useId, useRef, useState, type ReactElement } from 'react'
import { cn } from '../lib/cn'
import { mergeRefs, PopoverPortal, useAnchoredPopover } from '../lib/popover'

/*
 * specs/tooltip.md.
 *
 * A short label that appears on hover OR focus, attached to something that
 * already has a name of its own.
 *
 * The spec's central rule is the one that shapes the whole API: a tooltip is
 * `aria-describedby` and never `aria-label`. It is the least reliable place in
 * an interface to put information — it does not exist on a touch screen, it
 * does not exist in a printout, and it disappears the moment the pointer moves
 * — so the trigger keeps its own accessible name and the tip DESCRIBES it.
 *
 * Two consequences that are easy to get wrong and are enforced here rather than
 * documented:
 *
 * `text` is a plain string and not a slot. The spec forbids anything
 * interactive inside a tip — a link in there is unreachable for everyone not
 * using a mouse, because the tip is not focusable, there is no reliable pointer
 * path into it, and it closes on blur. A `ReactNode` prop would have made that
 * a rule somebody has to remember; a `string` makes it impossible.
 *
 * And the trigger is a single child that the tooltip CLONES rather than wraps.
 * A wrapper `<span>` around a button changes the layout of every toolbar it
 * appears in, and — worse — the tip's `aria-describedby` would land on the
 * wrapper rather than on the control a screen reader announces.
 */

const placements = {
  top: 'top' as const,
  bottom: 'bottom' as const,
  left: 'left' as const,
  right: 'right' as const,
}

export type TooltipPlacement = keyof typeof placements

export interface TooltipProps {
  /**
   * The words. A string, deliberately — see the note above about why this is
   * not a slot.
   *
   * Empty or absent renders the trigger alone with no tip and no
   * `aria-describedby`, which is what a conditionally-explained control wants:
   * the alternative is every caller wrapping the whole thing in a ternary.
   */
  text?: string
  placement?: TooltipPlacement
  /** The control being described. Cloned, never wrapped. */
  children: ReactElement<Record<string, unknown>>
  className?: string
}

export function Tooltip({ text, placement = 'top', children, className }: TooltipProps) {
  const [open, setOpen] = useState(false)
  const id = useId()

  const { setAnchor, setFloating, floatingStyles } = useAnchoredPopover<HTMLElement, HTMLDivElement>({
    open,
    placement: placements[placement],
    gap: 6,
  })

  const childRef = (children as { ref?: unknown }).ref
  const anchorRef = useRef<HTMLElement | null>(null)

  const show = useCallback(() => setOpen(true), [])
  const hide = useCallback(() => setOpen(false), [])

  /*
   * **Hover AND focus, always both.** A tip that only appears on hover does not
   * exist for a keyboard user — and the trigger is usually an icon-only button
   * whose whole meaning is in the tip.
   *
   * Escape dismisses it, which is WCAG 1.4.13: content shown on hover must be
   * dismissible without moving the pointer. It is the part everyone forgets,
   * and it matters most for the case the criterion was written for — a tip
   * covering the thing underneath it.
   */
  const props = children.props as Record<string, unknown>

  const trigger = {
    ...props,
    ref: mergeRefs(setAnchor, anchorRef, childRef as never),
    'aria-describedby': text ? cn(props['aria-describedby'] as string | undefined, id) : props['aria-describedby'],
    onMouseEnter: (event: never) => {
      show()
      ;(props.onMouseEnter as ((e: never) => void) | undefined)?.(event)
    },
    onMouseLeave: (event: never) => {
      hide()
      ;(props.onMouseLeave as ((e: never) => void) | undefined)?.(event)
    },
    onFocus: (event: never) => {
      show()
      ;(props.onFocus as ((e: never) => void) | undefined)?.(event)
    },
    onBlur: (event: never) => {
      hide()
      ;(props.onBlur as ((e: never) => void) | undefined)?.(event)
    },
    onKeyDown: (event: { key: string }) => {
      if (event.key === 'Escape') {
        hide()
      }

      ;(props.onKeyDown as ((e: never) => void) | undefined)?.(event as never)
    },
  }

  return (
    <>
      {cloneElement(children, trigger as never)}

      {open && text ? (
        <PopoverPortal>
          <div
            id={id}
            role="tooltip"
            ref={setFloating}
            style={floatingStyles}
            /*
             * `pointer-events-none` is not a detail. The tip sits over the
             * trigger's edge, and a hover that lands on the tip instead of the
             * button flickers between states — which reads as a broken control
             * rather than as a tooltip.
             *
             * Inverted ground so it reads as an overlay on any background
             * rather than as a small card, and `max-w-56` because anything
             * longer wants to be help text under a field.
             */
            className={cn(
              'pointer-events-none z-50 max-w-56 rounded-md px-2 py-1',
              'bg-surface-inverse text-on-inverse text-meta shadow-float',
              className,
            )}
          >
            {text}
          </div>
        </PopoverPortal>
      ) : null}
    </>
  )
}
