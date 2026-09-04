import type { ComponentType, SVGProps } from 'react'
import * as Outline24 from '@heroicons/react/24/outline'
import * as Solid24 from '@heroicons/react/24/solid'
import * as Mini20 from '@heroicons/react/20/solid'
import * as Micro16 from '@heroicons/react/16/solid'
import { cn } from '../lib/cn'
import { iconAliases } from './aliases'

export type IconVariant = 'outline' | 'solid' | 'mini' | 'micro'

export interface IconProps extends Omit<SVGProps<SVGSVGElement>, 'ref'> {
  /** Heroicon v2 name, kebab-case. v1 names in the alias map also resolve. */
  name: string
  /** outline (24) | solid (24) | mini (20) | micro (16) */
  variant?: IconVariant
  /**
   * Tailwind size step. 4 = 16px, the dominant size in the source dashboard.
   *
   * The class is composed at runtime, which Tailwind's scanner cannot see — it
   * reads source text, and `size={4.5}` is not the string `size-4.5`. The
   * `@source inline("size-{…}")` list in theme.css is what generates the rules;
   * a size outside it renders an SVG that expands to fill its container, with
   * no error anywhere. See specs/icon.md.
   */
  size?: number | string
  /**
   * Icons are decorative by default: most sit beside a label that already says
   * the same thing, and announcing both is noise. Pass a label ONLY when the
   * icon is the only thing carrying the meaning.
   */
  label?: string
}

type HeroIcon = ComponentType<SVGProps<SVGSVGElement> & { title?: string }>

const sets: Record<IconVariant, Record<string, HeroIcon>> = {
  outline: Outline24 as unknown as Record<string, HeroIcon>,
  solid: Solid24 as unknown as Record<string, HeroIcon>,
  mini: Mini20 as unknown as Record<string, HeroIcon>,
  micro: Micro16 as unknown as Record<string, HeroIcon>,
}

/** `chevron-double-down` → `ChevronDoubleDownIcon`, which is how the package exports them. */
function componentName(kebab: string): string {
  const pascal = kebab
    .split('-')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')

  // Heroicons prefixes a digit-leading name with `Icon` instead of suffixing it
  // (`24/outline` has no such names today, but `1`-leading names exist in v2).
  return /^\d/.test(pascal) ? `Icon${pascal}` : `${pascal}Icon`
}

export function Icon({
  name,
  variant = 'outline',
  size = 4,
  label,
  className,
  ...props
}: IconProps) {
  const resolved = iconAliases[name] ?? name
  const Component = sets[variant][componentName(resolved)]

  // A missing icon renders nothing rather than throwing — but it is a defect,
  // not a feature, so say so where a developer will see it.
  if (!Component) {
    if (import.meta.env?.DEV) {
      console.warn(`[@shipbytes/react] No "${variant}" Heroicon named "${resolved}".`)
    }
    return null
  }

  return (
    <Component
      className={cn(`size-${size}`, 'shrink-0', className)}
      aria-hidden={label ? undefined : true}
      role={label ? 'img' : undefined}
      aria-label={label}
      {...props}
    />
  )
}
