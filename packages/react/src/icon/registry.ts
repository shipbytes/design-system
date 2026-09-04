import type { ComponentType, SVGProps } from 'react'
import { iconAliases } from './aliases'

export type IconVariant = 'outline' | 'solid' | 'mini' | 'micro'

/** The shape `@heroicons/react` exports: an SVG component that accepts a title. */
export type HeroIcon = ComponentType<SVGProps<SVGSVGElement> & { title?: string }>

/**
 * A set of icons, keyed the way heroicons exports them — `TrashIcon`,
 * `ArrowRightIcon`. Keyed by the export name and not by the kebab-case `name`
 * prop on purpose: it lets a consumer write the registry with object shorthand
 * and never repeat a name.
 */
export type IconSet = Record<string, HeroIcon>

export type IconRegistry = Readonly<Record<IconVariant, Readonly<IconSet>>>

const VARIANTS: readonly IconVariant[] = ['outline', 'solid', 'mini', 'micro']

/**
 * Builds the registry an {@link IconProvider} hands to `Icon`.
 *
 * `Icon` used to import all four heroicons namespaces so that its runtime
 * `name` lookup could find anything. That works, and it costs about a megabyte:
 * a namespace import is a use of every export, so nothing tree-shakes and an
 * application that draws nine icons ships eleven hundred. On a plant LAN nobody
 * notices; at a gate terminal on a degraded link (FRD-01 W.5) it is the
 * difference between a screen that opens and one that does not.
 *
 * So the application declares what it uses, with named imports, and only those
 * reach the bundle:
 *
 * ```ts
 * import { ArrowRightIcon, TrashIcon } from '@heroicons/react/24/outline'
 * import { CheckCircleIcon } from '@heroicons/react/20/solid'
 *
 * export const icons = createIconRegistry({
 *   outline: { ArrowRightIcon, TrashIcon },
 *   mini: { CheckCircleIcon },
 * })
 * ```
 *
 * Object shorthand means each icon is named exactly twice — once in the import,
 * once in the registry — and never spelled a third way.
 *
 * A tool, a playground or a prototype that genuinely wants everything can still
 * pass a namespace (`import * as Outline from '@heroicons/react/24/outline'`)
 * and get the old behaviour back, at the old size. That is a deliberate choice
 * rather than the default.
 */
export function createIconRegistry(sets: Partial<Record<IconVariant, IconSet>>): IconRegistry {
  const registry = {} as Record<IconVariant, IconSet>

  for (const variant of VARIANTS) {
    registry[variant] = Object.freeze({ ...sets[variant] })
  }

  return Object.freeze(registry)
}

/** What `Icon` resolves against when no provider is above it: nothing. */
export const emptyIconRegistry: IconRegistry = createIconRegistry({})

/**
 * `chevron-double-down` → `ChevronDoubleDownIcon`, which is how the package
 * exports them — and therefore how a shorthand registry is keyed.
 */
export function iconComponentName(kebab: string): string {
  const pascal = kebab
    .split('-')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')

  // Heroicons prefixes a digit-leading name with `Icon` instead of suffixing it
  // (`24/outline` has no such names today, but `1`-leading names exist in v2).
  return /^\d/.test(pascal) ? `Icon${pascal}` : `${pascal}Icon`
}

/** Resolves a `name` prop — v1 aliases included — against a registry. */
export function resolveIcon(
  registry: IconRegistry,
  variant: IconVariant,
  name: string,
): HeroIcon | undefined {
  return registry[variant][iconComponentName(iconAliases[name] ?? name)]
}
