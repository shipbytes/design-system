import { describe, expect, it, vi } from 'vitest'
import { fireEvent, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { FileUpload } from './FileUpload'

const file = (name: string, bytes = 10, type = 'text/csv') =>
  new File([new Uint8Array(bytes)], name, { type })

describe('FileUpload', () => {
  it('is a real file input, stretched over the zone', () => {
    // Not a button that opens a hidden one: that loses the browser's own
    // keyboard behaviour and has to reimplement the drop target, and both
    // failures are invisible in a normal browser.
    const { container } = render(<FileUpload name="attachment" label="Attachment" />)

    const input = container.querySelector('input[type="file"]')

    expect(input).toBeInTheDocument()
    expect(input?.className).toContain('absolute')
    expect(input?.className).toContain('opacity-0')
    expect(screen.getByLabelText('Attachment')).toBe(input)
  })

  it('says what to look for in words, not in a machine string', () => {
    render(<FileUpload name="attachment" accept="image/*,.pdf" maxSize={5 * 1024 * 1024} />)

    expect(screen.getByText('images, PDF · up to 5 MB')).toBeInTheDocument()
    expect(screen.queryByText(/image\/\*/)).toBeNull()
  })

  it('hands the caller the files and nothing else — it has no transport', async () => {
    const chosen = vi.fn()

    const { container } = render(<FileUpload name="attachment" onChange={chosen} />)

    await userEvent.upload(container.querySelector('input[type="file"]')!, file('opening.csv'))

    expect(chosen).toHaveBeenCalledTimes(1)
    expect(chosen.mock.calls[0]?.[0]?.[0]?.name).toBe('opening.csv')
    expect(screen.getByText('opening.csv')).toBeInTheDocument()
  })

  it('rebuilds the input FileList when a file is removed', async () => {
    /*
     * The failure this prevents is the worst kind: the chip disappears and the
     * file is STILL submitted, so the reader believes they removed it. A
     * FileList is read-only and a DataTransfer is the only way to construct one.
     */
    const chosen = vi.fn()

    const { container } = render(<FileUpload name="attachments[]" multiple onChange={chosen} />)

    const input = container.querySelector('input[type="file"]') as HTMLInputElement

    await userEvent.upload(input, [file('one.csv'), file('two.csv')])

    expect(input.files).toHaveLength(2)

    /*
     * `user-event` installs `files` as a GETTER on the element, so the
     * component's assignment throws where a browser's would simply work. Make
     * it writable again, which is what every real `HTMLInputElement` is — the
     * component is unchanged and the rebuild is still what is being asserted.
     */
    Object.defineProperty(input, 'files', { configurable: true, writable: true, value: input.files })

    await userEvent.click(screen.getByRole('button', { name: 'Remove one.csv' }))

    expect(input.files).toHaveLength(1)
    expect(input.files?.[0]?.name).toBe('two.csv')
    expect(chosen).toHaveBeenLastCalledWith([expect.objectContaining({ name: 'two.csv' })])
    expect(screen.queryByText('one.csv')).toBeNull()
  })

  it('announces a file it dropped for being too large', async () => {
    // Silently discarding it means the reader assumes it uploaded. `alert`
    // rather than `status`, because they need to know now.
    const chosen = vi.fn()

    const { container } = render(<FileUpload name="attachment" maxSize={8} onChange={chosen} />)

    await userEvent.upload(container.querySelector('input[type="file"]')!, file('huge.csv', 64))

    expect(screen.getByRole('alert')).toHaveTextContent('huge.csv')
    expect(chosen).toHaveBeenCalledWith([])
  })

  it('counts drag enter and leave rather than toggling a boolean', () => {
    /*
     * Dragging over a CHILD of the zone fires dragleave on the parent, so a
     * plain toggle flickers the highlight off and on as the pointer crosses the
     * label. Enter twice, leave once: still highlighted.
     */
    const { container } = render(<FileUpload name="attachment" />)

    const zone = container.querySelector('.border-dashed') as HTMLElement

    // The exact class, not a substring: `hover:border-accent` is on the zone at
    // all times, so `toContain` would pass whatever the counter did.
    const highlighted = () => zone.classList.contains('border-accent')

    fireEvent.dragEnter(zone)
    fireEvent.dragEnter(zone)
    fireEvent.dragLeave(zone)

    expect(highlighted()).toBe(true)

    fireEvent.dragLeave(zone)

    expect(highlighted()).toBe(false)
  })

  it('describes itself by its error, or by its help, never both', () => {
    const { rerender, container } = render(
      <FileUpload name="attachment" id="up" help="One file per upload." />,
    )

    const input = () => container.querySelector('input[type="file"]')

    expect(input()).toHaveAttribute('aria-describedby', 'up-help')

    rerender(<FileUpload name="attachment" id="up" help="One file per upload." error="Too large." />)

    expect(input()).toHaveAttribute('aria-describedby', 'up-error')
    expect(input()).toHaveAttribute('aria-invalid', 'true')
    expect(screen.queryByText('One file per upload.')).toBeNull()
  })
})
