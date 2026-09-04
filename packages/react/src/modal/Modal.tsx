import type { ReactNode } from 'react'
import * as Dialog from '@radix-ui/react-dialog'
import { cn } from '../lib/cn'
import { Icon } from '../icon/Icon'

/*
 * specs/modal.md.
 *
 * A surface that blocks the page behind it until the reader deals with it.
 *
 * The accessibility list in the spec is long and every line of it is a real
 * requirement: focus into the panel on open and back to the trigger on close,
 * Tab trapped both directions, Escape, no background scroll, and the VISIBLE
 * title as the accessible name. The Blade version writes the focus trap out by
 * hand rather than using `x-trap`, because Alpine silently skips a directive
 * whose plugin is missing and `aria-modal="true"` then becomes a claim the DOM
 * does not honour. Radix Dialog implements the whole list, so this port gets it
 * from a dependency that cannot be half-installed.
 *
 * What the port keeps verbatim from the spec: the class recipes, the size map,
 * and the rule that the BODY scrolls rather than the panel.
 */

/**
 * A CLOSED set, mapped to literal class strings.
 *
 * Not a style choice — the scanner rule. `max-w-${size}` built at runtime is
 * invisible to Tailwind and generates no rule at all, which is the same
 * blindness that once shipped every alert with a checkmark the height of its
 * panel. A map of literals is scannable text.
 */
const sizes = {
  sm: 'max-w-sm', // 24rem — a confirm, a one-field form
  md: 'max-w-md', // 28rem — the default
  lg: 'max-w-lg', // 32rem
  xl: 'max-w-xl', // 36rem — the widest the source dashboard used
  '2xl': 'max-w-2xl', // 42rem ┐ the sizes a dialog needs when its content is
  '3xl': 'max-w-3xl', // 48rem │ WIDE rather than long: a table preview, a diff
  '4xl': 'max-w-4xl', // 56rem ┘
  /*
   * Near-full-screen, with a ceiling. The panel's own width is already "the
   * container, less the gutter", so `max-w-none` would be enough — measured,
   * that gives 1408px at a 1440 viewport and 2528px at 2560, a dialog wider
   * than anything else in the system at a line length nobody reads. The cap
   * only engages above roughly 1568px.
   */
  full: 'max-w-[96rem]',
} as const

export type ModalSize = keyof typeof sizes

export interface ModalProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  /** Heading. Also becomes the dialog's accessible name. */
  title?: ReactNode
  /** Second line under the title, for the "are you sure" sentence. */
  description?: ReactNode
  size?: ModalSize
  /**
   * Backdrop click, Escape and the close button.
   *
   * Turn it off for a modal the reader must answer. A destructive confirm whose
   * backdrop dismisses it gets dismissed by accident, and an accidental
   * dismissal reads as "cancelled" — which is the safe outcome exactly until it
   * is not. With `dismissible={false}` the footer MUST offer a way out; a modal
   * with no exit is a trap and the component cannot check that for you.
   */
  dismissible?: boolean
  /** Right-aligned actions, primary last. */
  footer?: ReactNode
  className?: string
  children?: ReactNode
}

export function Modal({
  open,
  onOpenChange,
  title,
  description,
  size = 'md',
  dismissible = true,
  footer,
  className,
  children,
}: ModalProps) {
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
         * `scrim` stays dark in both themes. Derived from `fg` it would go white
         * in dark, lighting the page up instead of pushing it back.
         */}
        {/*
         * Motion comes from the theme's named animations, driven by the
         * `data-state` attribute Radix puts on both parts.
         *
         * ANIMATIONS, not transitions, and that is the whole reason this
         * needed a design-system change rather than a component one. Radix
         * keeps a closing dialog mounted until `animationend` fires and only
         * then removes it — an element being removed never completes a
         * `transition`, so the Alpine spec's `x-transition` utility classes
         * would have produced an enter that worked and a leave that was never
         * seen. `animate-overlay-out` / `animate-dialog-out` are what Radix
         * waits for.
         */}
        <Dialog.Overlay className="fixed inset-0 z-50 bg-scrim data-[state=open]:animate-overlay-in data-[state=closed]:animate-overlay-out" />

        {/*
         * Centred by the panel itself, not by a flex wrapper around it.
         *
         * The Blade version centres with a `fixed inset-0 p-4 flex` root, and
         * the port did too until the leave animation went in. Dialog.Portal
         * wraps EACH of its direct children in its own Presence: a wrapper div
         * has no animation, so Presence unmounts it — and the panel inside it —
         * the instant `open` goes false, and the exit animation it was waiting
         * on never runs. The panel has to be the Portal's own child for Radix to
         * wait for it.
         *
         * `calc(100% - 2rem)` reproduces exactly what the wrapper's `p-4` gave:
         * a percentage resolves against the containing block, so this is still
         * "the container, less the gutter", and it stays right when the
         * containing block is not the viewport — which is what a transformed
         * ancestor makes it. 100vw/100dvh would not: on a phone with a visible
         * URL bar the footer buttons, the two the modal exists to offer, would
         * land below the fold.
         */}
        <Dialog.Content
          onEscapeKeyDown={block}
          onPointerDownOutside={block}
          onInteractOutside={block}
          className={cn(
            'fixed top-1/2 left-1/2 z-50 -translate-x-1/2 -translate-y-1/2',
            'flex w-[calc(100%-2rem)] max-h-[calc(100%-2rem)] flex-col overflow-hidden',
            'rounded-panel bg-surface shadow-overlay outline-hidden',
            // Fades and scales from 95%; see the overlay above for why these
            // are animations rather than transitions. The keyframes animate
            // `scale`, which is its own property in CSS and in Tailwind v4 —
            // so it composes with the centring `translate` instead of
            // overwriting it, which a `transform: scale()` would have done.
            'data-[state=open]:animate-dialog-in data-[state=closed]:animate-dialog-out',
            width,
            className,
          )}
        >
          {hasHeader ? (
            <div className="flex items-start justify-between gap-4 px-5 pt-5 pb-4">
              <div className="min-w-0">
                {/*
                 * The visible heading IS the accessible name — Radix wires
                 * aria-labelledby to this element. Never an aria-label: that
                 * is a second copy of the title nobody can see and nobody
                 * updates when the title changes.
                 */}
                {title ? (
                  <Dialog.Title className="text-title text-fg">{title}</Dialog.Title>
                ) : (
                  // Radix warns without a Title, and a dialog with no
                  // accessible name is the thing it is warning about. A
                  // titleless modal is rare and still needs one.
                  <Dialog.Title className="sr-only">Dialog</Dialog.Title>
                )}
                {/*
                 * Always rendered, hidden when empty. Radix warns about a
                 * dialog with no description, and the warning is right: the
                 * alternative is suppressing it with an aria-describedby
                 * override that would also break the case that HAS one.
                 */}
                <Dialog.Description
                  className={description ? 'mt-1 text-body text-fg-muted' : 'sr-only'}
                >
                  {description ?? 'Dialog'}
                </Dialog.Description>
              </div>

              {dismissible ? (
                <Dialog.Close
                  className="-mt-1 -mr-2 shrink-0 rounded-control p-2 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                  aria-label="Close"
                >
                  <Icon name="x-mark" size={5} />
                </Dialog.Close>
              ) : null}
            </div>
          ) : (
            <>
              <Dialog.Title className="sr-only">Dialog</Dialog.Title>
              <Dialog.Description className="sr-only">Dialog</Dialog.Description>
            </>
          )}

          {/*
           * The BODY scrolls, not the panel: the header keeps naming the
           * dialog and the footer keeps its actions reachable while long
           * content moves underneath. A panel that scrolls as one loses both,
           * at exactly the moment a long modal needs them.
           *
           * Top padding only when no header supplies the gap, bottom padding
           * only when no footer does.
           */}
          <div
            className={cn(
              'min-h-0 flex-1 overflow-y-auto px-5 text-body text-fg-body',
              !hasHeader && 'pt-5',
              !footer && 'pb-5',
            )}
          >
            {children}
          </div>

          {footer ? (
            // Actions right-aligned, primary last: the reading order of a
            // confirmation is the question, then the way out, then the answer.
            <div className="flex items-center justify-end gap-2 border-t border-divider px-5 py-4">
              {footer}
            </div>
          ) : null}
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  )
}
