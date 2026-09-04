import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline'
import { ChevronDownIcon } from '@heroicons/react/20/solid'
import { createIconRegistry, type IconRegistry } from './registry'

/**
 * The icons this package's own components draw.
 *
 * An alert's tone icon, an input's error mark, a select's chevron, a dismiss
 * cross — none of these were asked for by the application, so requiring it to
 * register them would be a contract nobody could guess. They are named imports,
 * so they tree-shake individually and cost a few kilobytes rather than the
 * megabyte a namespace import costs.
 *
 * This is a floor, not a ceiling: `Icon` looks in the injected registry first,
 * so an application that registers its own `x-mark` gets its own. Adding an
 * entry here is only correct for an icon a component in this package renders by
 * default.
 */
export const builtInIcons: IconRegistry = createIconRegistry({
  outline: {
    CheckCircleIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
  },
  mini: { ChevronDownIcon },
})
