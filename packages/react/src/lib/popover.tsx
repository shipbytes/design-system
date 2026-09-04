import { useMemo, type CSSProperties, type ReactNode, type Ref } from 'react'
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

  return { setAnchor: refs.setReference, setFloating: refs.setFloating, floatingStyles }
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

  return createPortal(children, document.body)
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
