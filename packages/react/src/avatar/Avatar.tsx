import { cva, type VariantProps } from 'class-variance-authority'
import type { HTMLAttributes } from 'react'
import { cn } from '../lib/cn'

export type AvatarSize = 'xs' | 'sm' | 'md' | 'lg'

/**
 * See specs/avatar.md.
 *
 * The fill is the badge's neutral tint pair, which `npm test` already
 * contrast-checks in both themes — so an avatar cannot be the one place a
 * person's initials fail to read.
 *
 * Sizes are literal classes and not `size-{size}`: a runtime-composed class is
 * a class Tailwind never sees, which is the bug icon.md records.
 */
export const avatarRecipe = cva(
  'relative inline-flex shrink-0 items-center justify-center overflow-hidden bg-neutral-tint text-on-neutral-tint font-medium select-none',
  {
    variants: {
      size: {
        // `sm` is control-sm's 32px, so an avatar sits level with a small
        // button in a table row without a nudge.
        xs: 'size-6 text-[0.625rem]',
        sm: 'size-8 text-meta',
        md: 'size-10 text-body',
        lg: 'size-12 text-title',
      },
      // Round reads as a person; a logo in a circle gets its corners eaten.
      shape: { round: 'rounded-full', square: 'rounded-control' },
    },
    defaultVariants: { size: 'sm', shape: 'round' },
  },
)

export interface AvatarProps
  extends Omit<HTMLAttributes<HTMLSpanElement>, 'children'>,
    VariantProps<typeof avatarRecipe> {
  /** The person or thing. Also the image's `alt`, unless `decorative`. */
  name?: string
  src?: string | null
  /** A company, a project or a file — not a person. */
  square?: boolean
  /**
   * Hide the whole thing from assistive technology. Use it whenever the name is
   * written beside the avatar, which is most of the time: a row announcing
   * "Ada Lovelace, Ada Lovelace" is worse than one that announces it once.
   */
  decorative?: boolean
}

/**
 * First and last word, not the first two — "Ada King Lovelace" is AL, because
 * the middle name is the part nobody uses.
 *
 * `[...word]` and not `charAt`: the first letter of a name is the single most
 * likely place in an interface to meet a multi-byte character, and slicing a
 * surrogate pair in half renders a replacement glyph.
 */
export function initialsOf(name: string): string {
  const words = name.trim().split(/\s+/).filter(Boolean)

  if (words.length === 0) return ''

  const first = [...(words[0] ?? '')][0] ?? ''
  const last = words.length > 1 ? ([...(words[words.length - 1] ?? '')][0] ?? '') : ''

  return (first + last).toLocaleUpperCase()
}

export function Avatar({
  name,
  src,
  size = 'sm',
  square = false,
  decorative = false,
  className,
  ...props
}: AvatarProps) {
  const initials = name ? initialsOf(name) : ''

  // Nothing to announce, so announce nothing — and with neither a source nor a
  // name, a mark rather than an empty circle: a blank avatar is a bug the
  // reader cannot report.
  const hidden = decorative || !name

  return (
    <span
      className={cn(avatarRecipe({ size, shape: square ? 'square' : 'round' }), className)}
      aria-hidden={hidden ? true : undefined}
      {...props}
    >
      {src ? (
        <img src={src} alt={decorative ? '' : (name ?? '')} className="size-full object-cover" />
      ) : initials !== '' ? (
        initials
      ) : (
        <svg viewBox="0 0 24 24" fill="currentColor" className="size-3/5" aria-hidden="true">
          <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.5-8 5.5V22h16v-2.5c0-3-3.6-5.5-8-5.5Z" />
        </svg>
      )}
      {/*
       * A hairline over the image rather than a border on the box: a border
       * would shrink the 32px an avatar is supposed to be, and an inset ring
       * lands on top of a photograph whose own edge may be any colour.
       */}
      <span className="pointer-events-none absolute inset-0 rounded-[inherit] ring-1 ring-inset ring-border" />
    </span>
  )
}
