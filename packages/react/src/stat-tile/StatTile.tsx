import { useEffect, useRef, useState, type ReactNode } from 'react'
import { cn } from '../lib/cn'

/**
 * Copied from resources/views/components/stat-tile.blade.php.
 * See specs/stat-tile.md.
 *
 * Roughly fifteen instances across the original application, with the count-up
 * animation duplicated verbatim five times in one file.
 *
 * Three things the spec insists on and this implements:
 *
 * **`tabular-nums`, always.** Digits line up across the tiles in a row — and,
 * the reason people forget, the count-up does not change width as it climbs.
 * Proportional digits make the number jitter while it counts.
 *
 * **A missing comparison is not a zero.** "No change since last week" and
 * "nothing to compare against yet" are different statements, and a `0%` chip
 * asserts the first. `delta` absent means no chip at all, and the caption takes
 * the line instead.
 *
 * **A tile that goes nowhere has no hover state.** A hover affordance on
 * something unclickable is a lie, so the element is an `<a>` when `href` is
 * given and a `<div>` when it is not — and only the anchor lifts.
 */
export interface StatTileProps {
  /** The noun. `My Resumes`, `Pending approvals` — never inside the value. */
  label: ReactNode
  /**
   * The number, final and correct without JavaScript.
   *
   * A string is rendered as given (`1,204`, `12.5%`); a number is counted up
   * to. The count-up is an enhancement over a value that is already right, not
   * the source of it.
   */
  value: number | string
  /**
   * The comparison, as a percentage. Positive gets a success chip, negative a
   * danger one, **absent gets no chip** — which is the case the original got
   * wrong by rendering `0%`.
   */
  delta?: number
  /** What the delta is against, or what to say when there is none. */
  caption?: ReactNode
  /** Makes the tile a link, and the only thing that earns it a hover state. */
  href?: string
  className?: string
}

/** Counts from zero over 600ms, unless the reader has asked it not to. */
const DURATION_MS = 600

export function StatTile({ label, value, delta, caption, href, className }: StatTileProps) {
  const shown = useCountUp(value)

  const body = (
    <>
      <p
        className={cn(
          'text-body font-medium text-fg-muted transition-colors',
          href ? 'group-hover:text-fg' : undefined,
        )}
      >
        {label}
      </p>

      {/* tabular-nums: the digits line up across a row of tiles, and the
          count-up does not change width as it climbs. */}
      <p className="mt-1 text-display tabular-nums text-fg">{shown}</p>

      {(delta !== undefined || caption) && (
        <p className="mt-2 flex items-center gap-2 text-body text-fg-muted">
          {delta !== undefined && (
            <span
              className={cn(
                'inline-flex items-center rounded-chip px-1.5 py-0.5 text-meta font-medium tabular-nums',
                delta >= 0 ? 'bg-success-tint text-on-success-tint' : 'bg-danger-tint text-on-danger-tint',
              )}
            >
              {/* A real minus sign, not a hyphen: the hyphen is narrower than
                  a digit and breaks the tabular alignment it sits in. */}
              {delta >= 0 ? '+' : '−'}
              {Math.abs(delta)}%
            </span>
          )}
          {caption}
        </p>
      )}
    </>
  )

  const shell = cn(
    'block rounded-control border border-border-strong bg-surface p-4',
    // `border-strong` rather than `border`: a stat tile has to read as a raised
    // object in a row of them, and the default hairline is too quiet at this
    // size.
    href ? 'group transition hover:border-fg/20 hover:shadow-raised' : undefined,
    className,
  )

  return href ? (
    <a href={href} className={shell}>
      {body}
    </a>
  ) : (
    <div className={shell}>{body}</div>
  )
}

/**
 * The count-up, in one place rather than five.
 *
 * **Skipped when the reader has asked for reduced motion**, which the
 * hand-written original did not do. This is the class of bug that survives
 * review indefinitely because it only affects people who are not in the room:
 * everything looks right to everybody who checks it.
 *
 * The starting value is decided in the LAZY INITIALISER rather than by setting
 * state from an effect, and both halves of that matter.
 *
 *   Rendered without a browser — a server, a snapshot — `window` is undefined
 *   and the hook returns the final number. That is the spec's "if JavaScript
 *   never runs, the tile is still correct": the animation is an enhancement
 *   over a value that is already right, not the source of it.
 *
 *   Rendered in a browser that welcomes motion, the first paint is already `0`,
 *   so the number climbs from nothing. Setting `0` from an effect instead would
 *   paint the final figure and then snap back to zero, which is a flash on
 *   every dashboard load.
 *
 * A string value is never counted — `1,204` and `12.5%` are formatted by the
 * caller, and re-deriving a format here would be a second answer to a question
 * the application has already answered.
 */
function useCountUp(value: number | string): string {
  const [shown, setShown] = useState<string>(() => (willAnimate(value) ? '0' : String(value)))
  const frame = useRef<number | null>(null)

  useEffect(() => {
    if (!willAnimate(value)) {
      setShown(String(value))

      return
    }

    const started = performance.now()

    const step = (now: number): void => {
      const progress = Math.min((now - started) / DURATION_MS, 1)

      setShown(String(Math.round(value * progress)))

      if (progress < 1) {
        frame.current = requestAnimationFrame(step)
      }
    }

    frame.current = requestAnimationFrame(step)

    return () => {
      if (frame.current !== null) {
        cancelAnimationFrame(frame.current)
      }
    }
  }, [value])

  return shown
}

/**
 * Whether this value counts, at all.
 *
 * Three ways to answer no, and each is a different reader: a formatted string
 * the caller owns, a context with no browser in it, and a person who has asked
 * their system for less movement.
 */
function willAnimate(value: number | string): value is number {
  if (typeof value !== 'number') {
    return false
  }

  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false
  }

  return !window.matchMedia('(prefers-reduced-motion: reduce)').matches
}
