import type { ReactNode } from 'react'
import * as Dialog from '@radix-ui/react-dialog'
import { cn } from '../lib/cn'
import { Icon } from '../icon/Icon'

/*
 * specs/drawer.md.
 *
 * A panel that slides in from an edge and blocks the page behind it — the third
 * member of the family the modal and the sheet belong to. The difference
 * between them is not where they enter from but HOW LONG THE READER STAYS: a
 * modal is answered and gone, a drawer is worked in and then closed.
 *
 * Radix Dialog again, and for the reason the modal gives: the accessibility
 * list is identical (focus into the panel and back to the trigger, Tab trapped
 * both directions, Escape, no background scroll, the visible title as the
 * accessible name), and a dependency that implements all of it cannot be
 * half-installed the way a missing Alpine plugin can.
 *
 * ## What is different from the modal, and why
 *
 * **Motion.** 300ms in, 200ms out against the modal's 200/150. A panel
 * travelling the full height of the screen at the modal's speed reads as a
 * flinch. The four animations are theme tokens — one per side and direction —
 * because a drawer slides from the edge it belongs to and the direction is part
 * of the motion.
 *
 * **`full` is `calc(100vw - 3rem)` and not `max-w-none`, and the sliver is the
 * point.** A panel covering everything is a screen, not a drawer; the strip of
 * page still showing is what says the thing you came from is still there and
 * one click away. That is the whole difference between the two, and a
 * full-bleed drawer would quietly become a worse page.
 *
 * **Position, border side and both slide directions come from ONE map.** Split
 * across three lookups, a `side` change silently keeps the previous slide
 * direction and the panel flies in from the wrong edge.
 */

const sizes = {
  sm: 'sm:max-w-sm', // 24rem — a filter list
  md: 'sm:max-w-md', // 28rem — the default
  lg: 'sm:max-w-lg', // 32rem — the widest the source dashboard used
  xl: 'sm:max-w-xl', // 36rem — a record shown beside the list it came from
  '2xl': 'sm:max-w-2xl', // 42rem — a detail pane with a table in it
  /*
   * Not `max-w-none`. See the note above: the 3rem strip of page is what keeps
   * this a drawer rather than a screen.
   */
  full: 'sm:max-w-[calc(100vw-3rem)]',
} as const

export type DrawerSize = keyof typeof sizes

/**
 * Position, border and motion together, so a `side` change cannot take three
 * lookups and get two of them.
 */
const sides = {
  right: {
    position: 'inset-y-0 right-0 border-l',
    in: 'data-[state=open]:animate-drawer-in-right',
    out: 'data-[state=closed]:animate-drawer-out-right',
  },
  left: {
    position: 'inset-y-0 left-0 border-r',
    in: 'data-[state=open]:animate-drawer-in-left',
    out: 'data-[state=closed]:animate-drawer-out-left',
  },
} as const

export type DrawerSide = keyof typeof sides

export interface DrawerProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  /** Heading. Also becomes the dialog's accessible name. */
  title?: ReactNode
  description?: ReactNode
  /**
   * `right` for filters and detail — it is where the reader's attention already
   * is after they clicked something on the right. `left` for navigation,
   * because that is where navigation lives.
   */
  side?: DrawerSide
  size?: DrawerSize
  /**
   * Backdrop click, Escape and the close button. Turn it off for a drawer the
   * reader must answer — and then the footer MUST offer a way out, because a
   * panel with no exit is a trap and the component cannot check that for you.
   */
  dismissible?: boolean
  /** Fixed at the bottom, so a long filter list cannot scroll Apply off. */
  footer?: ReactNode
  className?: string
  children?: ReactNode
}

export function Drawer({
  open,
  onOpenChange,
  title,
  description,
  side = 'right',
  size = 'md',
  dismissible = true,
  footer,
  className,
  children,
}: DrawerProps) {
  const edge = sides[side] ?? sides.right
  const width = sizes[size] ?? sizes.md
  const hasHeader = Boolean(title) || dismissible

  const block = (event: Event) => {
    if (!dismissible) {
      event.preventDefault()
    }
  }

  return (
    <Dialog.Root open={open} onOpenChange={onOpenChange}>
      <Dialog.Portal>
        {/*
         * `scrim` stays dark in both themes — derived from `fg` it would go
         * white in dark, lighting the page up instead of pushing it back.
         */}
        <Dialog.Overlay className="fixed inset-0 z-50 bg-scrim data-[state=open]:animate-overlay-in data-[state=closed]:animate-overlay-out" />

        {/*
         * The panel is the Portal's own child, not wrapped: Dialog.Portal wraps
         * EACH direct child in its own Presence, and a wrapper div with no
         * animation is unmounted the instant `open` goes false — taking the
         * panel with it before its exit animation runs. The modal learned this
         * the same way.
         *
         * `w-full` under every size, so a drawer is full width on a phone and a
         * panel on a desktop at every one of them.
         */}
        <Dialog.Content
          onEscapeKeyDown={block}
          onPointerDownOutside={block}
          onInteractOutside={block}
          className={cn(
            'fixed z-50 flex w-full flex-col',
            'border-border bg-surface shadow-overlay outline-hidden',
            edge.position,
            edge.in,
            edge.out,
            width,
            className,
          )}
        >
          {hasHeader ? (
            <div className="flex shrink-0 items-start justify-between gap-4 border-b border-border px-5 py-4">
              <div className="min-w-0">
                {/*
                 * The visible heading IS the accessible name — Radix wires
                 * `aria-labelledby` to this element. Never an `aria-label`,
                 * which would be a second name free to drift from the first.
                 */}
                {title ? (
                  <Dialog.Title className="text-section font-medium text-fg-body">{title}</Dialog.Title>
                ) : (
                  <Dialog.Title className="sr-only">Panel</Dialog.Title>
                )}
                {description ? (
                  <Dialog.Description className="mt-1 text-meta text-fg-muted">{description}</Dialog.Description>
                ) : null}
              </div>

              {dismissible ? (
                <Dialog.Close
                  aria-label="Close"
                  className="-mr-1 shrink-0 rounded-md p-1 text-fg-subtle hover:bg-surface-subtle hover:text-fg-body"
                >
                  <Icon name="x-mark" size={5} />
                </Dialog.Close>
              ) : null}
            </div>
          ) : (
            <Dialog.Title className="sr-only">Panel</Dialog.Title>
          )}

          {/*
           * **The BODY scrolls, not the panel.** The header keeps naming the
           * dialog and the footer keeps its actions reachable — which matters
           * most in exactly the case a drawer is usually for, a long list of
           * filters with an Apply button at the bottom.
           */}
          <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4">{children}</div>

          {footer ? (
            <div className="flex shrink-0 items-center justify-end gap-2 border-t border-border px-5 py-4">
              {footer}
            </div>
          ) : null}
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  )
}
