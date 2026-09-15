import { Children, cloneElement, isValidElement, useMemo, type CSSProperties, type ReactNode, type Ref } from 'react'
import { createPortal } from 'react-dom'
import { autoUpdate, flip, offset, shift, size, useFloating, type Placement } from '@floating-ui/react-dom'

/**
 * Where every overlay in this package puts itself — known gap 1, closed.
 *
 * The gap was that `dropdown`, `select`, `combobox`, `date-picker` and
 * `tooltip` each took a placement and trusted it. Two things go wrong with
 * that, and only one of them is the one the gap describes:
 *
 *  1. **The viewport edge.** `test:behaviour` measured the calendar running
 *     32px below the fold when its trigger sat near the bottom of the window.
 *     `flip` is what closes that.
 *  2. **The scroll container.** An absolutely positioned popover is clipped by
 *     any ancestor with `overflow: hidden|auto`. The ERP consuming this hit it
 *     first: a combobox opening from the last control of a scrollable modal
 *     body is cut off at the footer, and the workaround was to put the picker
 *     at the TOP of the dialog so the list opened into the body rather than out
 *     of it. That is a layout rule imposed on a screen by a component's
 *     limitation, which is the wrong way round.
 *
 * Both have the same fix and it is not a placement tweak: the popover has to
 * leave its ancestors. `strategy: 'fixed'` takes it out of the containing
 * block, a portal to `document.body` takes it out of the clip, and `flip` +
 * `shift` keep it on screen once it is out there.
 *
 * `autoUpdate` is what keeps the two in touch afterwards: fixed positioning
 * means the popover no longer moves with its anchor when an ancestor scrolls,
 * so something has to re-measure. It subscribes to scroll and resize on every
 * ancestor between the two and unsubscribes when the popover unmounts.
 *
 * Positioning only. Dismissal, focus and keyboard handling stay in each
 * component, where they were written from `specs/<name>.md` and are tested —
 * this changes where the box IS, and nothing about what it does.
 *
 * `@floating-ui/react-dom` rather than `@floating-ui/react`: the larger package
 * adds an interaction layer (dismiss, roving focus, list navigation) that these
 * components already have, written from their specs. Taking it would mean
 * rewriting behaviour to close a positioning gap.
 */

export interface AnchoredPopoverOptions {
  /** Measure and follow only while it is open. */
  open: boolean
  /** A preference, not a commitment: `flip` overrules it near an edge. */
  placement?: Placement
  /** Distance from the anchor, in pixels. */
  gap?: number
  /**
   * Give the popover the anchor's width — what a listbox under a field wants,
   * and what a calendar (a seven-column grid with a natural size) does not.
   */
  matchWidth?: boolean
  /**
   * Cap the height at this many pixels, or at the room actually available,
   * whichever is smaller. Without the second half, a long list near the bottom
   * of the window is flipped upward and then runs off the top instead.
   */
  maxHeight?: number
  /** Keep at least this far from the viewport edge. */
  padding?: number
  /**
   * The popover is meant to be clicked, so it sets its own `pointer-events`.
   *
   * Default `false`, and the default is the whole point. A portalled popover is
   * a child of `document.body`, and Radix's `DismissableLayer` — under `Modal`
   * and `Drawer` — sets `document.body { pointer-events: none }` while a modal
   * layer is open, restoring `auto` on its own content element and on nothing
   * else. The popover is not that element, so it inherits `none`: the click
   * passes THROUGH the list and lands on whatever sits behind it. Measured on
   * the ERP's item picker, where aiming at a category filter selected a
   * chemical and closed the dialog.
   *
   * It is opt-in rather than always-on because `Tooltip` uses this same hook
   * and carries `pointer-events-none` as a Tailwind class. An inline style beats
   * a class, so setting this unconditionally would silently make every tooltip
   * capture the pointer — see the comment at tooltip/Tooltip.tsx, which says why
   * a tip that takes the hover reads as a broken control. A tooltip is not
   * interactive and does not want this.
   */
  interactive?: boolean
}

/**
 * Generic in both elements so `setAnchor` goes onto whatever the component's
 * anchor happens to be — a div for a combobox's field, a button for the date
 * picker's trigger — without a cast at every call site.
 */
export interface AnchoredPopover<A extends HTMLElement = HTMLElement, F extends HTMLElement = HTMLElement> {
  /** Ref for the element the popover is measured against. */
  setAnchor: (node: A | null) => void
  /** Ref for the popover itself. */
  setFloating: (node: F | null) => void
  /** Spread onto the popover: `position`, `top`, `left`. */
  floatingStyles: CSSProperties
}

export function useAnchoredPopover<A extends HTMLElement = HTMLElement, F extends HTMLElement = HTMLElement>({
  open,
  placement = 'bottom-start',
  gap = 4,
  matchWidth = false,
  maxHeight,
  padding = 8,
  interactive = false,
}: AnchoredPopoverOptions): AnchoredPopover<A, F> {
  const middleware = useMemo(
    () => [
      offset(gap),
      flip({ padding }),
      shift({ padding }),
      size({
        padding,
        apply({ rects, availableHeight, elements }) {
          if (matchWidth) {
            elements.floating.style.width = `${rects.reference.width}px`
          }

          // `availableHeight` already accounts for the flip: after flipping to
          // `top` it is the room above. Letting the list scroll inside that is
          // what the spec asks for.
          if (maxHeight !== undefined) {
            elements.floating.style.maxHeight = `${Math.max(0, Math.min(maxHeight, availableHeight))}px`
          }
        },
      }),
    ],
    [gap, matchWidth, maxHeight, padding],
  )

  const { refs, floatingStyles } = useFloating<A>({
    open,
    placement,
    // Out of the containing block, so no positioned ancestor can move it — and
    // with the portal below, no `overflow` ancestor can clip it.
    strategy: 'fixed',
    // Only while it is mounted. The listener set is not free, and a closed
    // popover has nothing to keep in touch with.
    whileElementsMounted: autoUpdate,
    middleware,
  })

  /*
   * Memoised rather than spread fresh on every render: `floatingStyles` is
   * itself a stable object from floating-ui, and a new object here would be a
   * new `style` prop on every render of every consumer.
   */
  const style = useMemo(
    () => (interactive ? { ...floatingStyles, pointerEvents: 'auto' as const } : floatingStyles),
    [floatingStyles, interactive],
  )

  return { setAnchor: refs.setReference, setFloating: refs.setFloating, floatingStyles: style }
}

/**
 * Renders a popover at the end of `document.body`.
 *
 * Separate from the hook because the two are separable — a consumer may want
 * collision-aware placement without leaving the flow — and because
 * `createPortal` has no meaning before a document exists. Guarded rather than
 * assumed.
 */
export function PopoverPortal({ children }: { children: ReactNode }) {
  if (typeof document === 'undefined') {
    return null
  }

  /*
   * `data-ds-overlay` is part of the consumer contract — see README.md.
   *
   * An overlay leaves the flow, so it is no longer inside whatever opened it.
   * Every hand-rolled outside-press handler in every consumer therefore reads a
   * press on this popover as a press OUTSIDE, and dismisses the thing the
   * popover belongs to before the popover's own click handler runs. That is not
   * hypothetical: the ERP's filters panel closed on `pointerdown` and so ate
   * every choice made with a mouse for eight days.
   *
   * The two dismiss handlers inside this package name their own nodes and do
   * not need this. A consumer cannot, so it is marked here — once, on whatever
   * is portalled — rather than left to each consumer to match on `role` and
   * guess.
   */
  return createPortal(
    Children.map(children, (child) =>
      isValidElement(child) ? cloneElement(child, { 'data-ds-overlay': '' } as never) : child,
    ),
    document.body,
  )
}

/**
 * One element, several refs.
 *
 * The combobox's listbox is both floating-ui's floating element and the node
 * its own arrow-key handler reads. React takes one `ref` per element, so the
 * two are combined rather than one of them being dropped.
 */
export function mergeRefs<T>(...refs: (Ref<T> | undefined)[]): (node: T | null) => void {
  return (node: T | null) => {
    for (const ref of refs) {
      if (typeof ref === 'function') {
        ref(node)
      } else if (ref && typeof ref === 'object') {
        ;(ref as { current: T | null }).current = node
      }
    }
  }
}
