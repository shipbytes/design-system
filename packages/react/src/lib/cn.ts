import { clsx, type ClassValue } from 'clsx'
import { extendTailwindMerge } from 'tailwind-merge'

/*
 * tailwind-merge resolves conflicts by recognising the VALUE half of a utility,
 * and it only ships Tailwind's own scales. Every token this system adds —
 * `rounded-control`, `text-body`, `bg-surface-subtle`, `h-control-md` — is a
 * value it has never heard of, so it treats the class as unknown and merges
 * nothing.
 *
 * The failure is quiet and specific: `<Button className="rounded-full">` emits
 * both `rounded-control` and `rounded-full`, and which one wins depends on the
 * order Tailwind happened to emit the rules in — not on the caller's intent.
 *
 * These lists are the design system's scales, from tokens/ (the same source
 * dist/theme.css is generated from). A new token needs adding here as well, or
 * a caller cannot override the class that uses it.
 */

const radii = ['control', 'panel', 'chip', 'sheet'] as const

const fontSizes = [
  'display',
  'title',
  'heading',
  'section',
  'body',
  'body-touch',
  'meta',
  'overline',
] as const

const colors = [
  'accent', 'accent-hover', 'accent-tint', 'accent-wash',
  'success', 'success-tint', 'success-wash',
  'warning', 'warning-tint', 'warning-wash',
  'danger', 'danger-hover', 'danger-tint', 'danger-wash',
  'neutral-tint',
  'on-accent', 'on-accent-tint', 'on-danger', 'on-danger-tint',
  'on-inverse', 'on-neutral-tint', 'on-success-tint', 'on-warning-tint',
  'fg', 'fg-body', 'fg-muted', 'fg-subtle',
  'surface', 'surface-subtle', 'surface-sunken', 'surface-inverse', 'surface-inverse-hover',
  'ground', 'border', 'border-strong', 'divider', 'focus-ring', 'scrim',
] as const

// --spacing-* entries, which Tailwind exposes through every size utility.
const controlSizes = ['control-sm', 'control-md', 'control-lg', 'control-fab'] as const
const spacings = [...controlSizes, 'base', 'gutter', 'section', 'page'] as const

const shadows = ['raised', 'float', 'overlay'] as const

const twMerge = extendTailwindMerge({
  extend: {
    classGroups: {
      rounded: [{ rounded: [...radii] }],
      'font-size': [{ text: [...fontSizes] }],
      'text-color': [{ text: [...colors] }],
      'bg-color': [{ bg: [...colors] }],
      'border-color': [{ border: [...colors] }],
      'divide-color': [{ divide: [...colors] }],
      'ring-color': [{ ring: [...colors] }],
      'outline-color': [{ outline: [...colors] }],
      shadow: [{ shadow: [...shadows] }],
      w: [{ w: [...spacings] }],
      h: [{ h: [...spacings] }],
      size: [{ size: [...spacings] }],
    },
  },
})

/**
 * Merge class names, letting a caller's class win over the recipe's.
 *
 * `clsx` flattens conditionals; the extended `twMerge` above resolves the
 * conflicts, including the ones involving this system's own tokens.
 */
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs))
}
