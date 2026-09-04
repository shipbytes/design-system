import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Combobox, type ComboboxOption } from './Combobox'

const options: ComboboxOption[] = [
  { value: '1', label: 'Kilogram', meta: 'KG' },
  { value: '2', label: 'Metric tonne', meta: 'MT' },
  { value: '3', label: 'Litre', meta: 'LTR' },
]

describe('Combobox', () => {
  it('filters the list it is given, in the browser', async () => {
    render(<Combobox options={options} value={null} onChange={() => {}} label="Unit" />)

    await userEvent.type(screen.getByRole('combobox'), 'lit')

    expect(screen.getAllByRole('option').map((o) => o.textContent)).toEqual(['LitreLTR'])
  })

  it('says so when the filter matches nothing', async () => {
    render(<Combobox options={options} value={null} onChange={() => {}} />)

    await userEvent.type(screen.getByRole('combobox'), 'zzz')

    // An empty list with no message reads as a broken control.
    expect(screen.getByText('No matches')).toBeInTheDocument()
  })

  it('leaves the options alone when the consumer filters server-side', async () => {
    const onQueryChange = vi.fn()

    render(
      <Combobox
        options={options}
        value={null}
        onChange={() => {}}
        filter={false}
        onQueryChange={onQueryChange}
      />,
    )

    await userEvent.type(screen.getByRole('combobox'), 'zzz')

    // The component fetched nothing and hid nothing — it reported the typing
    // and rendered exactly what it was given.
    expect(onQueryChange).toHaveBeenLastCalledWith('zzz')
    expect(screen.getAllByRole('option')).toHaveLength(3)
  })

  it('reports a single choice as a value and closes', async () => {
    const onChange = vi.fn()
    render(<Combobox options={options} value={null} onChange={onChange} />)

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.click(screen.getByRole('option', { name: /Metric tonne/ }))

    expect(onChange).toHaveBeenCalledWith('2')
    expect(screen.queryByRole('listbox')).toBeNull()
  })

  it('reports several choices as an array and stays open', async () => {
    const onChange = vi.fn()
    const { rerender } = render(<Combobox options={options} value={[]} onChange={onChange} multiple />)

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.click(screen.getByRole('option', { name: /Kilogram/ }))

    expect(onChange).toHaveBeenCalledWith(['1'])

    rerender(<Combobox options={options} value={['1']} onChange={onChange} multiple />)

    expect(screen.getByRole('listbox')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Remove Kilogram' })).toBeInTheDocument()
  })

  it('removes the last chip on backspace with an empty query', async () => {
    const onChange = vi.fn()
    render(<Combobox options={options} value={['1', '2']} onChange={onChange} multiple />)

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.keyboard('{Backspace}')

    // Without it the only way to undo a selection is to aim at a 12px ✕.
    expect(onChange).toHaveBeenCalledWith(['1'])
  })

  it('shows the chosen label as the placeholder, so the field is never blank', () => {
    render(<Combobox options={options} value="3" onChange={() => {}} />)

    expect(screen.getByRole('combobox')).toHaveAttribute('placeholder', 'Litre')
  })

  it('announces multi-select on the listbox', async () => {
    render(<Combobox options={options} value={[]} onChange={() => {}} multiple />)

    await userEvent.click(screen.getByRole('combobox'))

    expect(screen.getByRole('listbox')).toHaveAttribute('aria-multiselectable', 'true')
  })

  it('walks the filtered list with the arrow keys', async () => {
    render(<Combobox options={options} value={null} onChange={() => {}} />)

    const field = screen.getByRole('combobox')
    await userEvent.click(field)
    await userEvent.keyboard('{ArrowDown}')

    expect(document.activeElement).toHaveAttribute('role', 'option')
  })

  it('will not choose a disabled option', async () => {
    const onChange = vi.fn()

    render(
      <Combobox
        options={[{ value: '1', label: 'Retired', disabled: true }]}
        value={null}
        onChange={onChange}
      />,
    )

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.click(screen.getByRole('option', { name: 'Retired' }))

    expect(onChange).not.toHaveBeenCalled()
  })

  it('replaces help with the error and marks the field invalid', () => {
    render(<Combobox options={options} value={null} onChange={() => {}} help="Pick one." error="Required." />)

    expect(screen.getByText('Required.')).toBeInTheDocument()
    expect(screen.queryByText('Pick one.')).toBeNull()
    expect(screen.getByRole('combobox')).toHaveAttribute('aria-invalid', 'true')
  })
})
