import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterEach } from 'vitest'

afterEach(cleanup)

/*
 * jsdom implements no Pointer Events API at all.
 *
 * Radix's menu and select primitives call `hasPointerCapture` on the element
 * under the pointer, and an undefined method there does not throw somewhere
 * legible — the interaction simply never completes, and the test times out five
 * seconds later pointing at the `it(...)` line. These four stubs are the whole
 * fix, and they are honest ones: capture semantics are not what the tests are
 * about, and every component still runs its real event handling.
 */
if (!Element.prototype.hasPointerCapture) {
  Element.prototype.hasPointerCapture = () => false
  Element.prototype.setPointerCapture = () => {}
  Element.prototype.releasePointerCapture = () => {}
}

// Radix scrolls the highlighted item into view; jsdom has no layout to scroll.
if (!Element.prototype.scrollIntoView) {
  Element.prototype.scrollIntoView = () => {}
}

/*
 * jsdom has no `DataTransfer` at all — not a stub, not a constructor.
 *
 * The file upload needs it for one thing the spec insists on: removing a chosen
 * file has to REBUILD the input's `FileList`, which is read-only, and a
 * `DataTransfer` is the only way to construct one. Without that the chip
 * disappears and the file is still submitted, which is worse than having no
 * remove control at all.
 *
 * So the environment gets the API rather than the component getting a fallback.
 * A fallback would be a second code path that only ever runs in tests, and the
 * behaviour under test is exactly the one it would replace.
 */
if (typeof globalThis.DataTransfer === 'undefined') {
  class TestDataTransfer {
    private readonly chosen: File[] = []

    readonly items = {
      add: (file: File) => {
        this.chosen.push(file)
      },
    }

    get files(): FileList {
      const list = [...this.chosen] as File[] & {
        item(index: number): File | null
      }

      list.item = (index: number) => list[index] ?? null

      return list as unknown as FileList
    }
  }

  globalThis.DataTransfer = TestDataTransfer as unknown as typeof DataTransfer
}
