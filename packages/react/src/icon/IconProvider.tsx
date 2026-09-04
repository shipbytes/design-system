import { createContext, useContext, type ReactNode } from 'react'
import { emptyIconRegistry, type IconRegistry } from './registry'

const IconRegistryContext = createContext<IconRegistry>(emptyIconRegistry)

export interface IconProviderProps {
  registry: IconRegistry
  children: ReactNode
}

/**
 * Puts an icon registry in scope for every `Icon` below it.
 *
 * One of these near the root of an application is the whole setup. Nesting a
 * second one replaces the registry for its subtree rather than merging with the
 * outer one — a merge would make a missing icon depend on where in the tree it
 * was rendered, which is a worse thing to debug than a missing icon.
 */
export function IconProvider({ registry, children }: IconProviderProps) {
  return <IconRegistryContext value={registry}>{children}</IconRegistryContext>
}

export function useIconRegistry(): IconRegistry {
  return useContext(IconRegistryContext)
}
