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
