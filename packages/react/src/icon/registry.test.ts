import { describe, expect, it } from 'vitest'
import { TrashIcon } from '@heroicons/react/24/outline'
import { createIconRegistry, iconComponentName, resolveIcon } from './registry'

describe('createIconRegistry', () => {
  it('names every variant, so a lookup never reads undefined', () => {
    const registry = createIconRegistry({ outline: { TrashIcon } })

    expect(Object.keys(registry)).toEqual(['outline', 'solid', 'mini', 'micro'])
    expect(registry.solid).toEqual({})
  })

  it('is frozen, so a stray write is not a way to smuggle icons in at runtime', () => {
    const registry = createIconRegistry({ outline: { TrashIcon } })

    expect(Object.isFrozen(registry)).toBe(true)
    expect(Object.isFrozen(registry.outline)).toBe(true)
  })

  it('accepts a whole namespace for a tool that genuinely wants everything', () => {
    // The escape hatch: same behaviour as before the registry, same size cost,
    // now an explicit choice rather than the default.
    const namespace = { TrashIcon, ArchiveBoxIcon: TrashIcon }
    const registry = createIconRegistry({ outline: namespace })

    expect(resolveIcon(registry, 'outline', 'archive-box')).toBe(TrashIcon)
  })
})

describe('iconComponentName', () => {
  it('matches how heroicons exports its components', () => {
    expect(iconComponentName('chevron-double-down')).toBe('ChevronDoubleDownIcon')
    expect(iconComponentName('trash')).toBe('TrashIcon')
  })

  it('prefixes rather than suffixes a digit-leading name, as heroicons does', () => {
    expect(iconComponentName('1-percent')).toBe('Icon1Percent')
  })
})
