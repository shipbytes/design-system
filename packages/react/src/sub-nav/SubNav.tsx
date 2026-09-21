import {
  Children,
  Fragment,
  isValidElement,
  useCallback,
  useEffect,
  useId,
  useRef,
  useState,
  type ElementType,
  type KeyboardEvent,
  type PointerEvent,
  type ReactElement,
  type ReactNode,
} from 'react'
import { Icon } from '../icon'
import { cn } from '../lib/cn'
import { mergeRefs, PopoverPortal, useAnchoredPopover } from '../lib/popover'
import { NavItem } from '../nav-item/NavItem'
import { NavRowBody, navRow, navRowBase, navRowCard, navRowHover, navRowResting } from '../nav-item/row'

/*
 * specs/sub-nav.md.
 *
 * A nav item that holds the pages belonging to it. The rail that asked for it
 * had twenty-three entries at one level, four of which were pages INSIDE a
 * fifth. Nothing was wrong with the styling; there were about eight things with
 * parts inside them and one level to say it in.
 *
 * ## One row, two targets
 *
 * The label and icon navigate; the arrow, and only the arrow, opens the list.
 * One press doing both means the reader cannot reach the parent's own page
 * without also opening five rows they did not ask for, and cannot open the list
 * to look without leaving the page they are on.
 *
 * The consequence is structural and it is the thing an implementation gets
 * wrong first: **a link and a button are siblings, never nested.** A `<button>`
 * inside an `<a>` is invalid, and the browser gives the inner control no
 * reliable keyboard behaviour. So the row is a container holding two controls
 * side by side, and the CONTAINER draws the current card, so that the card wraps
 * both targets as one row.
 *
 * ## The appearance comes from `nav-item/row`, not from a copy
 *
 * The spec says the parent row is "the nav item's own recipe … not a second
 * copy that drifts", so `navRow` and `NavRowBody` are shared with `NavItem`
 * rather than restated here. Two rails that merely resemble each other is the
 * failure that prevents — and it is invisible until one of them gets the next
 * fix.
 *
 * ## There is no "this section contains the page you are on"
 *
 * specs/nav-item.md forbids it under Do not, and it is forbidden for a reason
 * that has not changed: a second active-ish treatment has to be distinguishable
 * from the active one and from the resting one at a glance, in two themes, and
 * it ends up competing with the thing it is a weaker version of. The behaviour
 * carries it instead — going to a page inside a closed list opens that list,
 * and the current child's card then says where the reader is.
 *
 * The one exception is a COLLAPSED parent, below, and it is allowed for the
 * single reason that the alternative is impossible.
 */

/**
 * The children, flattened, so the component can count them and read them.
 *
 * `Children.toArray` flattens nested ARRAYS and nothing else — a `<>…</>` around
 * four rows comes back as one opaque node. Both facts this reads off the
 * children are then wrong in the same direction and silently: a parent written
 * with a fragment drew an arrow for a list it thought held one item, and never
 * drew the collapsed dot, because the fragment itself has no `active` prop. A
 * `.map()` happens to work, which is exactly what would have kept it hidden.
 */
function childRows(children: ReactNode): ReactElement<{ active?: boolean }>[] {
  return Children.toArray(children).flatMap((child) => {
    if (!isValidElement(child)) {
      return []
    }

    return child.type === Fragment
      ? childRows((child.props as { children?: ReactNode }).children)
      : [child as ReactElement<{ active?: boolean }>]
  })
}

/**
 * The indent belongs to the LIST, not to the rows in it.
 *
 * In the open list the children align to the PARENT'S LABEL — `px-2` plus a 20px
 * icon plus `gap-3` puts the label 40px in, and that is the indent. A child
 * indented to the ICON would start a second column that nothing else in the rail
 * uses. In the collapsed pop-out there is no parent row above them to line up
 * with, so the same rows are drawn indent-free.
 *
 * `ps-8` and not `ps-10`, because the indent is measured to the LABEL and the
 * row keeps its own `px-2`: the current child's card starts 32px in and its text
 * lands on the parent's label at 40px. Put the whole 40px INSIDE the row instead
 * and the card is full-rail-width — specs/sub-nav.md draws it "smaller and
 * indented", and a card as wide as the rail reads as a second top-level row
 * rather than as something inside the list. `pe-2` is the matching inset on the
 * right, so the card ends where the parent row's own content does.
 *
 * A property of the list rather than a prop on every row, because a consumer
 * writing the rows out by hand cannot be expected to know which of the two lists
 * they are about to land in — the pop-out renders the same children the open
 * list does.
 */
const CHILD_INDENT = 'ps-8 pe-2'

export interface SubNavItemProps {
  /** Rendered as this element — a router `Link`, an `a`. */
  as?: ElementType
  href?: string
  label: string
  /** This child is the page the reader is on. */
  active?: boolean
  /**
   * A count, exactly as a nav item's.
   *
   * Worth stating rather than discovering: **a count on a child in a closed list
   * cannot be seen.** If something must be watched from anywhere in the app, it
   * is a top-level entry, not a child.
   */
  count?: number
  countCap?: number
  onClick?: () => void
  className?: string
}

export function SubNavItem({
  as: Component = 'a',
  href,
  label,
  active = false,
  count,
  countCap = 99,
  onClick,
  className,
}: SubNavItemProps) {
  return (
    <li>
      <Component
        href={href}
        onClick={onClick}
        /*
         * Weight, fill and elevation carry nothing to a screen reader — the same
         * omission specs/nav-item.md and specs/breadcrumb.md both record.
         */
        aria-current={active ? 'page' : undefined}
        className={cn(
          navRowBase,
          'w-full gap-2 border px-2 py-2',
          // The parent is `medium`; the child is the same size at normal weight.
          // Weight and indent separate them, never a smaller type size — 13px is
          // not in the scale, and a rail that invents one drifts from every
          // other surface.
          'font-normal',
          // The same card the active nav item and the current pagination cell
          // draw, at child scale: no border-radius or padding change of its own.
          active ? navRowCard : cn(navRowResting, navRowHover),
          className,
        )}
      >
        <NavRowBody label={label} count={count} countCap={countCap} active={active} />
      </Component>
    </li>
  )
}

export interface SubNavProps {
  /** Rendered as this element — a router `Link`, an `a`. */
  as?: ElementType
  /**
   * The parent's own page.
   *
   * **Do not give a parent no page.** The label is a link; a parent that goes
   * nowhere leaves it a target that looks pressable and is not. A group of rows
   * with a title and nothing behind it is the rail's own section heading, which
   * already exists and is not this component.
   */
  href?: string
  label: string
  icon?: ReactNode
  badge?: ReactNode
  /**
   * The parent's OWN page's count, and never a sum of its children's.
   *
   * A component cannot know whether the parent's twelve and the child's twelve
   * are the same twelve, and a number that appears when a list is shut and
   * vanishes when it opens reads as the count having moved.
   */
  count?: number
  countCap?: number
  /**
   * The parent's OWN page is the current one.
   *
   * Never set because a child is current: that is the forbidden fourth state,
   * and the open list's card says it instead.
   */
  active?: boolean
  collapsed?: boolean
  chipped?: boolean
  /**
   * Whether the list is open. **Passed in, with no uncontrolled mode.**
   *
   * Three reasons rather than one. The component is not told the route, so it
   * cannot implement "going to a page inside a closed list opens it". An open
   * list stays open while the reader moves about, including to pages outside it,
   * which is history rather than a derivation from the current page. And the
   * memory is per browser under a key the shell owns — a component that wrote
   * storage itself would pick the key, and two rails on one page would collide
   * in it.
   *
   * **Lists are not exclusive**: several may be open at once, and this component
   * has no way to close another, by construction.
   */
  open: boolean
  /**
   * A press on the arrow, reported. The component never closes itself — it draws
   * what it is told.
   */
  onOpenChange: (open: boolean) => void
  /**
   * The parent's own page, named in full for the collapsed pop-out's first row —
   * "Gate dashboard", not "Gate", because in the panel it sits under an overline
   * that already says GATE.
   *
   * Only the shell knows what that page is called, so it says. Defaulting to
   * `label` keeps the row reachable when it does not.
   */
  ownPageLabel?: string
  /**
   * Also open the collapsed pop-out on hover. **Off by default**: a rail that
   * already peeks on hover would fight it, and a touch screen has no hover at
   * all, so a component that depended on it would have no way to open on the one
   * device that most needs the pop-out.
   */
  openOnHover?: boolean
  /** Short in, longer out — so crossing the icon on the way somewhere else does nothing. */
  hoverOpenDelay?: number
  hoverCloseDelay?: number
  onClick?: () => void
  className?: string
  /** `SubNavItem`s. **Do not nest a sub-nav inside a sub-nav** — two levels is the whole set. */
  children?: ReactNode
}

export function SubNav({
  as = 'a',
  href,
  label,
  icon,
  badge,
  count,
  countCap = 99,
  active = false,
  collapsed = false,
  chipped = false,
  open,
  onOpenChange,
  ownPageLabel,
  openOnHover = false,
  hoverOpenDelay = 150,
  hoverCloseDelay = 300,
  onClick,
  className,
  children,
}: SubNavProps) {
  const Component = as
  const generated = useId()
  const listId = `ds-${generated}-list`
  const overlineId = `ds-${generated}-section`

  const rows = childRows(children)
  const hasChildren = rows.length > 0

  /*
   * Read off the children rather than taken as a prop.
   *
   * The alternative is a `containsCurrent` the shell sets beside the `active` it
   * already sets on one child — two statements of one fact, which is the shape
   * that goes out of step. The component is already given every child's own
   * answer; this is the same answer, counted.
   */
  const containsCurrent = rows.some((row) => row.props.active === true)

  /*
   * The pop-out's open state is LOCAL, and the list's is not. The spec's three
   * reasons for passing `open` in are all about the persistent list: the route,
   * staying open afterwards, and a storage key. None of them is true of a panel
   * that closes on Escape, on an outside press and on Tab — behaviour the spec
   * takes verbatim from dropdown.md, and behaviour a component cannot honour if
   * it may not close itself. Tying the two together would also mean a rail whose
   * lists were remembered open flew a panel open the moment it collapsed.
   */
  const [panelOpen, setPanelOpen] = useState(false)

  const icon_ = useRef<HTMLButtonElement>(null)
  const panel = useRef<HTMLDivElement>(null)
  const hoverTimer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined)

  /*
   * `right-start`, which lib/popover.tsx has taken since known gap 1 was closed.
   * A rail is a scroll container, so an absolutely positioned panel is clipped
   * by it; the portal plus `strategy: 'fixed'` is what gets the panel out, and
   * `flip`/`shift` are what keep it on screen when the icon is near the bottom.
   * It is there to be clicked, so it says so.
   */
  const popover = useAnchoredPopover<HTMLButtonElement, HTMLDivElement>({
    open: panelOpen,
    placement: 'right-start',
    gap: 8,
    interactive: true,
  })

  const closePanel = useCallback((returnFocus: boolean) => {
    setPanelOpen(false)

    if (returnFocus) {
      icon_.current?.focus()
    }
  }, [])

  // Focus enters the panel's first row on open — dropdown.md, cited rather than
  // redesigned.
  useEffect(() => {
    if (!panelOpen) {
      return
    }

    panel.current?.querySelector<HTMLElement>('a, [href], button')?.focus()
  }, [panelOpen])

  useEffect(() => {
    if (!panelOpen) {
      return
    }

    /*
     * An outside press closes it by naming its own nodes, never with a
     * full-screen backdrop that swallows the next click anywhere on the page.
     * The panel is portalled out of the rail, so a press inside it would read as
     * a press outside and close the panel before the row's own handler ran —
     * measured on this package's own combobox, where aiming at a filter selected
     * something else entirely.
     */
    const close = (event: MouseEvent) => {
      const target = event.target as Node

      if (icon_.current?.contains(target) || panel.current?.contains(target)) {
        return
      }

      setPanelOpen(false)
    }

    document.addEventListener('mousedown', close)

    return () => document.removeEventListener('mousedown', close)
  }, [panelOpen])

  useEffect(() => () => clearTimeout(hoverTimer.current), [])

  const hoverIn = (event: PointerEvent) => {
    // A touch that lands on the icon reports `pointerenter` too, and treating it
    // as hover would open the panel and then immediately toggle it shut on the
    // click that follows.
    if (!openOnHover || event.pointerType !== 'mouse') {
      return
    }

    clearTimeout(hoverTimer.current)
    hoverTimer.current = setTimeout(() => setPanelOpen(true), hoverOpenDelay)
  }

  const hoverOut = (event: PointerEvent) => {
    if (!openOnHover || event.pointerType !== 'mouse') {
      return
    }

    clearTimeout(hoverTimer.current)
    hoverTimer.current = setTimeout(() => setPanelOpen(false), hoverCloseDelay)
  }

  const onPanelKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (event.key === 'Escape') {
      // Stopped, so a rail inside a drawer does not lose the drawer as well.
      event.stopPropagation()
      closePanel(true)

      return
    }

    if (event.key === 'Tab') {
      /*
       * Tab closes and moves on rather than leaving the panel hanging. The panel
       * is portalled to the end of `document.body`, so letting the default run
       * would walk the reader out of the rail and into nothing; focus goes back
       * to the icon instead and the next Tab carries on down the rail.
       */
      event.preventDefault()
      closePanel(true)
    }
  }

  /*
   * A parent with no children is a plain row, with no arrow at all.
   *
   * Not a disabled arrow and not an arrow that opens nothing: a control that
   * looks pressable and does nothing is worse than no control, and a rail of
   * mostly-childless entries would grow a column of dead chevrons. It is the nav
   * item itself, so there is nothing here that can drift from one.
   */
  if (!hasChildren) {
    return (
      <li className={className}>
        <NavItem
          as={as}
          href={href}
          label={label}
          icon={icon}
          badge={badge}
          count={count}
          countCap={countCap}
          active={active}
          collapsed={collapsed}
          chipped={chipped}
          onClick={onClick}
        />
      </li>
    )
  }

  /*
   * Collapsed, there is no label left to press, so the row stops being a link
   * and becomes ONE control: pressing the icon opens a panel beside it, and the
   * parent's own page is the panel's first row.
   */
  if (collapsed) {
    return (
      <li className={className}>
        <button
          ref={mergeRefs(icon_, popover.setAnchor)}
          type="button"
          aria-label={label}
          aria-expanded={panelOpen}
          /*
           * `dialog` and not `menu`. dropdown.md forbids a menu for navigation
           * between pages, and `role="menu"` is a promise of arrow-key roving
           * that replaces Tab — these rows are destinations, not verbs, and a
           * list of links announced as menu items is the wrong thing announced.
           * What `dialog` promises is exactly what is implemented below: focus
           * moves in, Escape closes, focus comes back.
           */
          aria-haspopup="dialog"
          /*
           * No tooltip. The panel's overline already names the section, and a tip
           * and a panel opening from the same press land on top of each other.
           */
          title={undefined}
          onClick={() => setPanelOpen((was) => !was)}
          onKeyDown={(event) => {
            if (event.key === 'Escape') {
              event.stopPropagation()
              closePanel(false)
            }
          }}
          onPointerEnter={hoverIn}
          onPointerLeave={hoverOut}
          className={cn(
            navRow({ active, collapsed: true }),
            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
          )}
        >
          <NavRowBody
            label={label}
            icon={icon}
            count={count}
            countCap={countCap}
            active={active}
            collapsed
            chipped={chipped}
          />

          {/*
           * The one mark that is a deliberate exception to "do not mark the
           * parent", and it is allowed for the single reason that the
           * alternative is impossible: the list is not on screen, so there is
           * nowhere to put the card.
           *
           * `surface-inverse`, the same ink as the collapsed count pip and for
           * the same reason — a mark this size in a light tint disappears, and
           * the accent is spoken for by links. Bottom-right, so it never collides
           * with a count pip on the top-right. Drawn only when the current page
           * is a CHILD: when the parent's own page is current the icon draws the
           * card, and the dot would be saying it twice.
           */}
          {!active && containsCurrent ? (
            <span
              aria-hidden="true"
              className="pointer-events-none absolute -right-1 -bottom-1 size-1.5 rounded-full bg-surface-inverse"
            />
          ) : null}
        </button>

        {panelOpen ? (
          <PopoverPortal>
            <div
              ref={mergeRefs(panel, popover.setFloating)}
              style={popover.floatingStyles}
              role="dialog"
              aria-modal={false}
              aria-labelledby={overlineId}
              onKeyDown={onPanelKeyDown}
              onPointerEnter={hoverIn}
              onPointerLeave={hoverOut}
              // `float`, not `overlay`: it hovers over the page, it does not
              // block it. The shadow scale is where the system says which.
              className="z-50 min-w-52 rounded-control border border-border bg-surface p-1 shadow-float"
            >
              {/* `px-2`, the rows' own padding, so the section name starts on
                  the column its rows do. */}
              <p id={overlineId} className="px-2 py-1.5 text-overline text-fg-muted uppercase">
                {label}
              </p>

              {/* Indent-free: there is no parent row above them to line up with. */}
              <ul className="flex flex-col gap-0.5">
                {/*
                 * First, and named in full, because otherwise it is unreachable:
                 * collapsed, the icon opens the panel instead of going anywhere.
                 */}
                <SubNavItem as={as} href={href} label={ownPageLabel ?? label} active={active} onClick={onClick} />
                {children}
              </ul>
            </div>
          </PopoverPortal>
        ) : null}
      </li>
    )
  }

  return (
    <li className={className}>
      {/*
       * The container draws the card and nothing else — no padding and no hover
       * fill. Two targets that highlight as one are one target as far as the
       * reader can tell, so each of the two below lights on its own.
       */}
      <div className={navRow({ active, part: 'card' })}>
        <Component
          href={href}
          onClick={onClick}
          aria-current={active ? 'page' : undefined}
          /*
           * The link carries the row's padding, and that is load-bearing rather
           * than tidy: its 8px of right padding is the gap between the two
           * targets, and a press there lands on the LINK. Every pixel of doubt
           * goes to the link, because a mis-press there lands on a page the
           * reader asked for and a mis-press on the arrow changes the shape of
           * the rail under their hand. The container therefore sets no `gap`.
           */
          className={navRow({ active, part: 'press' })}
        >
          <NavRowBody
            label={label}
            icon={icon}
            badge={badge}
            count={count}
            countCap={countCap}
            active={active}
            chipped={chipped}
          />
        </Component>

        <button
          type="button"
          aria-expanded={open}
          aria-controls={listId}
          /*
           * The accessible name names its SECTION. A rail has one arrow per
           * section, and a column of identical "Expand" buttons is a list of
           * controls a screen reader user cannot tell apart.
           */
          aria-label={`${label} pages`}
          onClick={() => onOpenChange(!open)}
          className={cn(
            'flex size-9 shrink-0 items-center justify-center rounded-control',
            // `fg-subtle` is allowed because the label beside it says the same
            // thing — the redundant-information exception in specs/color.md.
            'text-fg-subtle transition-colors hover:bg-surface-subtle hover:text-fg-body',
            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
            /*
             * Where the pointer is coarse the pressable box is 44×44 and the row
             * grows to match. The first consumer's rail runs on a touch screen at
             * a gate, operated by someone wearing gloves in a hurry.
             *
             * It grows as a real box and never leftward into the label: no
             * negative margin, no expanded pseudo-element, nothing that would put
             * the arrow's hit area over pixels that look like the link.
             */
            'pointer-coarse:size-11',
          )}
        >
          <Icon
            name="chevron-down"
            variant="mini"
            size={4}
            className={cn('transition-transform', open ? 'rotate-0' : '-rotate-90')}
          />
        </button>
      </div>

      {/*
       * `grid-rows-[0fr]` → `grid-rows-[1fr]`, as in specs/accordion.md, so the
       * height transition is pure CSS.
       *
       * The accessibility half is `invisible`, and it is not interchangeable with
       * the `overflow-hidden` below it: `visibility: hidden` takes the children
       * out of the tab order AND out of the accessibility tree; `overflow:
       * hidden` takes them out of neither. A rail whose hidden rows are still
       * tabbable is the classic broken disclosure — it looks perfect and walks a
       * keyboard user through five links that are not on screen.
       */}
      <div
        className={cn(
          'grid transition-all duration-200 ease-out',
          open ? 'visible grid-rows-[1fr]' : 'invisible grid-rows-[0fr]',
        )}
      >
        <div className="overflow-hidden">
          {/*
           * A real list nested inside the parent's own list item, so assistive
           * technology announces "list, 4 items" and the nesting is in the markup
           * rather than in the indent.
           */}
          <ul id={listId} className={cn('mt-0.5 flex flex-col gap-0.5', CHILD_INDENT)}>
            {children}
          </ul>
        </div>
      </div>
    </li>
  )
}
