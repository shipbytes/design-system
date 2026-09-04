import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Tab, TabList, TabPanel, Tabs } from './Tabs'

describe('Tabs', () => {
  it('is a tablist when the tabs switch panels', () => {
    render(
      <Tabs label="Report sections" defaultValue="overview">
        <TabList label="Report sections">
          <Tab value="overview">Overview</Tab>
          <Tab value="activity">Activity</Tab>
        </TabList>
        <TabPanel value="overview">Overview body</TabPanel>
        <TabPanel value="activity">Activity body</TabPanel>
      </Tabs>,
    )

    expect(screen.getByRole('tablist', { name: 'Report sections' })).toBeInTheDocument()
    expect(screen.getByRole('tab', { name: 'Overview' })).toHaveAttribute('aria-selected', 'true')
    expect(screen.getByRole('tabpanel')).toHaveTextContent('Overview body')
  })

  it('is a nav when the tabs are links, not a tablist', () => {
    // The single most common tabs bug, and invisible without a screen reader:
    // role="tablist" promises arrow keys and in-place content, and a row of page
    // links delivers neither. specs/tabs.md.
    render(
      <Tabs label="Sections" navigation>
        <Tab href="/overview" active>
          Overview
        </Tab>
        <Tab href="/activity">Activity</Tab>
      </Tabs>,
    )

    expect(screen.getByRole('navigation', { name: 'Sections' })).toBeInTheDocument()
    expect(screen.queryByRole('tablist')).toBeNull()

    // A link tab is a page you are ON — aria-current, not aria-selected, which
    // only means anything inside a tablist.
    const current = screen.getByRole('link', { name: 'Overview' })
    expect(current).toHaveAttribute('aria-current', 'page')
    expect(current).not.toHaveAttribute('aria-selected')
  })

  it('moves between tabs with the arrow keys', async () => {
    // role="tab" is a promise that this works. The Blade version hands it to the
    // host with a warning that two earlier examples of it were wrong.
    const user = userEvent.setup()

    render(
      <Tabs label="Sections" defaultValue="a">
        <TabList label="Sections">
          <Tab value="a">First</Tab>
          <Tab value="b">Second</Tab>
        </TabList>
        <TabPanel value="a">A</TabPanel>
        <TabPanel value="b">B</TabPanel>
      </Tabs>,
    )

    await user.tab()
    expect(screen.getByRole('tab', { name: 'First' })).toHaveFocus()

    await user.keyboard('{ArrowRight}')
    expect(screen.getByRole('tab', { name: 'Second' })).toHaveAttribute('aria-selected', 'true')
    expect(screen.getByRole('tabpanel')).toHaveTextContent('B')
  })

  it('takes the unselected panel out of the tab order entirely', async () => {
    render(
      <Tabs label="Sections" defaultValue="a">
        <TabList label="Sections">
          <Tab value="a">First</Tab>
          <Tab value="b">Second</Tab>
        </TabList>
        <TabPanel value="a">A</TabPanel>
        <TabPanel value="b">
          <button type="button">Hidden action</button>
        </TabPanel>
      </Tabs>,
    )

    // Not merely dimmed or aria-hidden: a control in an unselected panel that
    // Tab can still reach is a control the reader cannot see.
    expect(screen.queryByRole('button', { name: 'Hidden action' })).toBeNull()
  })

  it('renders a count without turning it into a status', () => {
    render(
      <Tabs label="Sections" defaultValue="open">
        <TabList label="Sections">
          <Tab value="open" count={12}>
            Open
          </Tab>
        </TabList>
        <TabPanel value="open">Body</TabPanel>
      </Tabs>,
    )

    expect(screen.getByRole('tab', { name: /Open\s*12/ })).toBeInTheDocument()
  })
})
