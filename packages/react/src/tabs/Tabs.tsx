import type { AnchorHTMLAttributes, ReactNode } from 'react'
import { createContext, useContext } from 'react'
import * as RadixTabs from '@radix-ui/react-tabs'
import { cn } from '../lib/cn'

/*
 * specs/tabs.md.
 *
 * The decision the spec leads with is the accessibility contract, and it is the
 * one thing this port must not soften: `role="tablist"` PROMISES a screen reader
 * that arrow keys move between the tabs and that the content changes in place.
 * On a row of page links both halves are false — arrow keys do nothing, and
 * following one navigates away from the tablist entirely. Links get a <nav>,
 * which is what they are.
 *
 * `navigation` therefore picks between two genuinely different trees. The items
 * read the mode from context so it is stated once, on the row, and a `Tab`
 * cannot disagree with the row it is in.
 *
 * One thing this port does that the Blade version could not: the arrow keys.
 * Blade has no way to own them, so the spec hands the host a twenty-line Alpine
 * block and a warning that two earlier versions of it were wrong. Radix owns
 * them here — roving tabindex, Home/End, wrapping — so the promise is kept by
 * the component rather than by every consumer.
 */

// `-mb-px` pulls the row onto the divider so the active tab's 2px underline
// covers the 1px rule rather than sitting below it and drawing two lines.
const ROW = 'flex min-w-0 items-center gap-1 overflow-x-auto border-b border-divider'

const ITEM = [
  'group relative -mb-px inline-flex shrink-0 items-center gap-2 whitespace-nowrap',
  'border-b-2 px-3 py-2.5 text-body font-medium transition-colors',
  'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
  'rounded-t-chip',
].join(' ')

// The underline is `fg`, not `accent`. A tab row is structure, not a link —
// colouring the active one accent makes the INACTIVE tabs look like the links,
// which is backwards.
const ACTIVE = 'border-fg text-fg'
const INACTIVE = 'border-transparent text-fg-muted hover:border-border-strong hover:text-fg'
const DISABLED = 'cursor-not-allowed opacity-50 hover:border-transparent hover:text-fg-muted'

// tabular-nums so the row does not reflow as counts change width.
const COUNT = 'rounded-full px-1.5 py-0.5 text-meta font-medium tabular-nums'
const COUNT_INACTIVE = 'bg-neutral-tint text-on-neutral-tint'
const COUNT_ACTIVE = 'bg-surface-inverse text-on-inverse'

const NavigationContext = createContext(false)

export interface TabsProps {
  /** Names the set. Becomes the tablist's or the nav's accessible name. */
  label: string
  /**
   * TRUE when the tabs are LINKS to other pages, false when they switch panels
   * on this one. Not a styling flag — see the note above.
   *
   * In navigation mode `Tabs` IS the row: put `Tab`s straight inside it. In tab
   * mode it is the Radix root, and the row is a `TabList` inside it, because the
   * panels have to sit under the same root.
   */
  navigation?: boolean
  /** Selected tab's value. Ignored in navigation mode. */
  value?: string
  defaultValue?: string
  onValueChange?: (value: string) => void
  className?: string
  children: ReactNode
}

export function Tabs({
  label,
  navigation = false,
  value,
  defaultValue,
  onValueChange,
  className,
  children,
}: TabsProps) {
  if (navigation) {
    return (
      <NavigationContext value={true}>
        <nav className={cn(ROW, className)} aria-label={label}>
          {children}
        </nav>
      </NavigationContext>
    )
  }

  return (
    <NavigationContext value={false}>
      <RadixTabs.Root
        value={value}
        defaultValue={defaultValue}
        onValueChange={onValueChange}
        className={className}
        data-ds-tabs-label={label}
      >
        {children}
      </RadixTabs.Root>
    </NavigationContext>
  )
}

export interface TabListProps {
  /** Names the set for a screen reader. */
  label: string
  className?: string
  children: ReactNode
}

/** The row of tabs, inside a `Tabs`. Tab mode only — see `Tabs.navigation`. */
export function TabList({ label, className, children }: TabListProps) {
  return (
    <RadixTabs.List className={cn(ROW, className)} aria-label={label}>
      {children}
    </RadixTabs.List>
  )
}

export interface TabProps {
  /** Identifies the tab and its panel. Required outside navigation mode. */
  value?: string
  /** Navigation mode only. Its presence is what makes this a link. */
  href?: string
  /** Navigation mode only — a link tab is a page you are ON. */
  active?: boolean
  /** A count beside the label — "Open 12". Not a status: use a badge for that. */
  count?: number | string
  disabled?: boolean
  className?: string
  children: ReactNode
  onClick?: AnchorHTMLAttributes<HTMLAnchorElement>['onClick']
}

export function Tab({
  value,
  href,
  active = false,
  count,
  disabled = false,
  className,
  children,
  onClick,
}: TabProps) {
  const navigation = useContext(NavigationContext)

  if (navigation) {
    return (
      <a
        href={disabled ? undefined : href}
        // A link tab is a page you are ON, so aria-current — not aria-selected,
        // which only means anything inside a tablist.
        aria-current={active ? 'page' : undefined}
        aria-disabled={disabled || undefined}
        className={cn(ITEM, active ? ACTIVE : INACTIVE, disabled && DISABLED, className)}
        onClick={onClick}
      >
        {children}
        {count === undefined ? null : (
          <span className={cn(COUNT, active ? COUNT_ACTIVE : COUNT_INACTIVE)}>{count}</span>
        )}
      </a>
    )
  }

  return (
    <RadixTabs.Trigger
      value={value ?? ''}
      disabled={disabled}
      onClick={onClick as never}
      className={cn(
        ITEM,
        INACTIVE,
        // Radix writes data-state, so the selected look follows the actual
        // selection rather than a prop a consumer has to keep in sync — which
        // is the failure the spec's "why not ::active" section is about.
        'data-[state=active]:border-fg data-[state=active]:text-fg',
        disabled && DISABLED,
        className,
      )}
    >
      {children}
      {count === undefined ? null : (
        <span
          className={cn(
            COUNT,
            COUNT_INACTIVE,
            'group-data-[state=active]:bg-surface-inverse group-data-[state=active]:text-on-inverse',
          )}
        >
          {count}
        </span>
      )}
    </RadixTabs.Trigger>
  )
}

export interface TabPanelProps {
  value: string
  className?: string
  children: ReactNode
}

/**
 * Radix hides an unselected panel by unmounting it, which is stronger than the
 * spec's `hidden` attribute and buys the same two things: nothing focusable
 * behind Tab, and nothing in the accessibility tree.
 *
 * `tabIndex={0}` so a panel with no focusable content is still reachable —
 * without it a keyboard reader tabs straight past the content they just chose.
 */
export function TabPanel({ value, className, children }: TabPanelProps) {
  return (
    <RadixTabs.Content
      value={value}
      tabIndex={0}
      className={cn(
        'py-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        className,
      )}
    >
      {children}
    </RadixTabs.Content>
  )
}
