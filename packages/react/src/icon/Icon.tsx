import type { SVGProps } from 'react'
import { cn } from '../lib/cn'
import { builtInIcons } from './builtin'
import { useIconRegistry } from './IconProvider'
import { resolveIcon, type IconVariant } from './registry'

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

/**
 * Draws one icon from the registry an {@link IconProvider} put in scope.
 *
 * The registry is injected rather than imported because importing all four
 * heroicons namespaces — which is what a runtime `name` lookup used to need —
 * defeats tree-shaking completely and costs about a megabyte for the nine icons
 * a screen actually draws. See `createIconRegistry`.
 */
export function Icon({
  name,
  variant = 'outline',
  size = 4,
  label,
  className,
  ...props
}: IconProps) {
  const registry = useIconRegistry()

  // The application's registry first, then the handful this package's own
  // components draw. That order lets an application override a built-in with a
  // different glyph, and means an <Alert> works before anyone has registered
  // anything — its tone icon was never the application's to declare.
  const Component = resolveIcon(registry, variant, name) ?? resolveIcon(builtInIcons, variant, name)

  // A missing icon renders nothing rather than throwing — a broken glyph should
  // not take a screen down with it — but it is a defect, not a feature, so say
  // so where a developer will see it.
  if (!Component) {
    if (import.meta.env?.DEV) {
      console.warn(
        `[@shipbytes/react] No "${variant}" icon named "${name}" in the registry. ` +
          'Add it to createIconRegistry(), or check that an <IconProvider> is above this tree.',
      )
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
