import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline'
import {
  CalendarIcon,
  CheckIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/react/20/solid'
import { XMarkIcon as XMarkMicroIcon } from '@heroicons/react/16/solid'
import { createIconRegistry, type IconRegistry } from './registry'

/**
 * The icons this package's own components draw.
 *
 * An alert's tone icon, an input's error mark, a select's chevron, a dismiss
 * cross, a date picker's calendar, pagination's arrows, the tick beside a chosen
 * option — none of these were asked for by the application, so requiring it to
 * register them would be a contract nobody could guess. They are named imports,
 * so they tree-shake individually and cost a few kilobytes rather than the
 * megabyte a namespace import costs.
 *
 * This is a floor, not a ceiling: `Icon` looks in the injected registry first,
 * so an application that registers its own `x-mark` gets its own. Adding an
 * entry here is only correct for an icon a component in this package renders by
 * default — and the test for whether it belongs is whether an application could
 * have known to register it.
 */
export const builtInIcons: IconRegistry = createIconRegistry({
  outline: {
    CheckCircleIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
  },
  mini: {
    // Combobox's tick beside a chosen option.
    CheckIcon,
    // Select, combobox and dropdown triggers.
    ChevronDownIcon,
    // Pagination's arrows and the date picker's month steppers.
    ChevronLeftIcon,
    ChevronRightIcon,
    // The date picker's trigger.
    CalendarIcon,
  },
  // The combobox chip's remove button, which is the one micro glyph the package
  // draws by itself.
  micro: { XMarkIcon: XMarkMicroIcon },
})
