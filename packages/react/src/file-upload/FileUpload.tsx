import { useId, useRef, useState, type ChangeEvent, type DragEvent, type ReactNode } from 'react'
import { Icon } from '../icon'
import { cn } from '../lib/cn'

/**
 * Copied from resources/views/components/file-upload.blade.php.
 * See specs/file-upload.md.
 *
 * **It does not upload anything.** The component owns the field, the drop zone,
 * the chosen-file list and the local thumbnails; it does not own a transport.
 * Progress bars need somewhere to upload TO, and the moment a component knows
 * that it has a backend contract — which is the one thing nothing else in this
 * package has. `onChange` hands back the chosen `File`s and the caller posts
 * them however it posts anything else.
 *
 * **It is a real `<input type="file">`, styled.** The input is stretched over
 * the whole zone at zero opacity, so the entire area is the actual control —
 * one focus ring, one click target — and drag-and-drop lands on the element
 * that owns the files. A button that opens a hidden input loses the browser's
 * own keyboard behaviour and has to reimplement the drop target, and both
 * failures are invisible in a normal browser.
 *
 * Two things the spec calls out as easy to get wrong, both of them here:
 *
 * **Removing a file rebuilds the input's `FileList`.** A `FileList` is
 * read-only and a `DataTransfer` is the only way to construct one. Without it
 * the chip disappears and the file is still submitted — worse than having no
 * remove control at all, because the reader believes they removed it.
 *
 * **Drag state counts; it does not toggle.** Dragging over a CHILD of the zone
 * fires `dragleave` on the parent, so a boolean flickers the highlight off and
 * on as the pointer crosses the label.
 */

export interface FileUploadProps {
  /** Submitted field name. With `multiple`, pass it as `attachments[]`. */
  name: string
  label?: ReactNode
  multiple?: boolean
  /** Passed through to the input — `image/*`, `.pdf,.csv`. */
  accept?: string
  /**
   * Largest file the reader should choose, in bytes. Checked in the BROWSER for
   * their benefit only — it saves them a failed upload, and it is not a security
   * control. Validate on the server as well, always.
   */
  maxSize?: number
  /** Guidance under the drop zone. Replaced by `error`, never stacked. */
  help?: ReactNode
  error?: ReactNode
  disabled?: boolean
  /** Show a thumbnail for image files. Read locally; nothing is uploaded. */
  preview?: boolean
  /** The files the reader has chosen, after `maxSize` has dropped any. */
  onChange?: (files: File[]) => void
  id?: string
  className?: string
}

interface Chosen {
  name: string
  size: number
  url: string | null
}

/**
 * "image/*,.pdf" is a machine string. The reader gets "images, PDF" — the hint
 * exists to tell a person what to look for on their own disk, and a machine
 * string in the interface is the interface leaking.
 */
function readableTypes(accept: string | undefined): string | null {
  if (!accept) {
    return null
  }

  const names = accept
    .split(',')
    .map((type) => type.trim())
    .filter(Boolean)
    .map((type) => {
      if (type === 'image/*') return 'images'
      if (type === 'video/*') return 'video'
      if (type === 'audio/*') return 'audio'
      if (type.startsWith('.')) return type.slice(1).toUpperCase()
      if (type.includes('/')) return type.slice(type.lastIndexOf('/') + 1).toUpperCase()

      return type
    })

  return [...new Set(names)].join(', ') || null
}

const megabytes = (bytes: number) => Math.round((bytes / 1024 / 1024) * 10) / 10

function readableSize(bytes: number): string {
  return bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

export function FileUpload({
  name,
  label,
  multiple = false,
  accept,
  maxSize,
  help,
  error,
  disabled = false,
  preview = true,
  onChange,
  id,
  className,
}: FileUploadProps) {
  const generated = useId()
  const inputId = id ?? `ds-${generated}`
  const describedBy = error ? `${inputId}-error` : help ? `${inputId}-help` : undefined

  const input = useRef<HTMLInputElement>(null)
  const [files, setFiles] = useState<Chosen[]>([])
  const [rejected, setRejected] = useState<string[]>([])
  // Counted, not toggled — see the docblock.
  const [dragging, setDragging] = useState(0)

  const take = (list: FileList | null) => {
    const all = [...(list ?? [])]
    const tooBig = maxSize == null ? [] : all.filter((file) => file.size > maxSize)
    const kept = maxSize == null ? all : all.filter((file) => file.size <= maxSize)

    setRejected(tooBig.map((file) => file.name))
    setFiles(
      kept.map((file) => ({
        name: file.name,
        size: file.size,
        url: preview && file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
      })),
    )

    onChange?.(kept)
  }

  const onInputChange = (event: ChangeEvent<HTMLInputElement>) => take(event.target.files)

  const onDrop = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault()
    setDragging(0)

    if (disabled || !input.current) {
      return
    }

    input.current.files = event.dataTransfer.files
    take(event.dataTransfer.files)
  }

  /**
   * Removing one file means rebuilding the input's FileList, which is read-only
   * — a DataTransfer is the only way to construct one.
   */
  const remove = (fileName: string) => {
    const element = input.current

    if (!element) {
      return
    }

    const transfer = new DataTransfer()

    for (const file of [...(element.files ?? [])]) {
      if (file.name !== fileName) {
        transfer.items.add(file)
      }
    }

    element.files = transfer.files
    take(transfer.files)
  }

  const types = readableTypes(accept)
  const hint = [types, maxSize ? `up to ${megabytes(maxSize)} MB` : null].filter(Boolean).join(' · ')

  return (
    <div className={cn('block w-full', className)}>
      {label ? (
        <label htmlFor={inputId} className="mb-1.5 block text-body font-medium text-fg">
          {label}
        </label>
      ) : null}

      <div
        className={cn(
          'relative flex flex-col items-center justify-center gap-1 rounded-control',
          'border-2 border-dashed px-6 py-8 text-center transition-colors',
          error ? 'border-danger' : 'border-border-strong',
          disabled
            ? 'cursor-not-allowed bg-surface-subtle'
            : 'cursor-pointer bg-surface hover:border-accent hover:bg-accent-wash',
          dragging > 0 && 'border-accent bg-accent-wash',
        )}
        onDragEnter={(event) => {
          event.preventDefault()
          setDragging((count) => count + 1)
        }}
        onDragLeave={(event) => {
          event.preventDefault()
          setDragging((count) => Math.max(0, count - 1))
        }}
        onDragOver={(event) => event.preventDefault()}
        onDrop={onDrop}
      >
        <input
          ref={input}
          type="file"
          id={inputId}
          name={name}
          accept={accept}
          multiple={multiple}
          disabled={disabled}
          aria-invalid={error ? true : undefined}
          aria-describedby={describedBy}
          onChange={onInputChange}
          className="absolute inset-0 size-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
        />

        <Icon name="arrow-up-tray" size="6" className="text-fg-muted" />

        <p className="text-body text-fg-body">
          <span className="font-medium text-fg">Choose {multiple ? 'files' : 'a file'}</span> or drag{' '}
          {multiple ? 'them' : 'it'} here
        </p>

        {hint ? <p className="text-meta text-fg-muted">{hint}</p> : null}
      </div>

      {/* Announced politely: the list changes in response to something the
          reader did, and they are not looking at it while the file dialog
          closes. */}
      {files.length > 0 ? (
        <ul className="mt-2 flex flex-col gap-1.5" aria-live="polite">
          {files.map((file) => (
            <li
              key={file.name}
              className="flex items-center gap-3 rounded-control border border-border bg-surface px-3 py-2"
            >
              {file.url ? (
                // The filename beside it is the name, so the image has none.
                <img src={file.url} alt="" className="size-9 shrink-0 rounded-chip object-cover" />
              ) : (
                <span className="flex size-9 shrink-0 items-center justify-center rounded-chip bg-neutral-tint text-on-neutral-tint">
                  <Icon name="document-text" size="4" />
                </span>
              )}

              <span className="min-w-0 flex-1">
                <span className="block truncate text-body text-fg">{file.name}</span>
                <span className="block text-meta tabular-nums text-fg-muted">
                  {readableSize(file.size)}
                </span>
              </span>

              <button
                type="button"
                // Names its file, not just "Remove": a list of three identical
                // buttons is three identical announcements.
                aria-label={`Remove ${file.name}`}
                className="shrink-0 rounded-control p-1.5 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                onClick={() => remove(file.name)}
              >
                <Icon name="x-mark" size="4" />
              </button>
            </li>
          ))}
        </ul>
      ) : null}

      {/* A file silently dropped for being too large is the reader assuming it
          uploaded. `alert` rather than `status`: they need to know now. */}
      {rejected.length > 0 ? (
        <p className="mt-1.5 text-meta text-danger" role="alert">
          {rejected.join(', ')}{' '}
          {maxSize ? `is over ${megabytes(maxSize)} MB and was not added.` : 'was not added.'}
        </p>
      ) : null}

      {error ? (
        <p id={`${inputId}-error`} className="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
          <Icon name="exclamation-circle" size="3.5" className="mt-0.5" />
          <span>{error}</span>
        </p>
      ) : help ? (
        <p id={`${inputId}-help`} className="mt-1.5 text-meta text-fg-muted">
          {help}
        </p>
      ) : null}
    </div>
  )
}
