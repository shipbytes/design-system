import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Combobox } from '../combobox'
import { DatePicker } from '../date-picker'
import { Pagination } from '../pagination'

/**
 * Every icon this package's own components draw resolves without the
 * application registering anything.
 *
 * The failure this catches is quiet: `Icon` renders NOTHING for a name it
 * cannot resolve, and warns only in development. A date picker whose trigger has
 * lost its calendar still works, still passes every behavioural test, and looks
 * subtly wrong to whoever opens the screen. So the assertion is on the console:
 * no warning means every glyph was found.
 */
describe('the built-in icon floor', () => {
  const withoutWarnings = (render: () => void) => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {})

    render()

    const missing = warn.mock.calls
      .map((call) => String(call[0]))
      .filter((message) => message.includes('in the registry'))

    warn.mockRestore()

    return missing
  }

  it('covers the date picker, open and closed', () => {
    const missing = withoutWarnings(() => {
      render(<DatePicker value="2026-03-15" onChange={() => {}} />)
      screen.getByRole('button').click()
    })

    expect(missing).toEqual([])
  })

  it('covers pagination', () => {
    const missing = withoutWarnings(() => {
      render(<Pagination page={3} perPage={10} total={100} onChange={() => {}} />)
    })

    expect(missing).toEqual([])
  })

  it('covers the combobox, including a chip and a tick', () => {
    const missing = withoutWarnings(() => {
      render(
        <Combobox
          options={[
            { value: '1', label: 'Kilogram' },
            { value: '2', label: 'Litre' },
          ]}
          value={['1']}
          onChange={() => {}}
          multiple
          error="Required."
        />,
      )
    })

    expect(missing).toEqual([])
  })
})
