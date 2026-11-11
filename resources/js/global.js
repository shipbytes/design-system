/*
 * The classic-script build: what ds::scripts inlines into the page.
 *
 * It has to work wherever it is placed BEFORE Alpine, and Alpine arrives three
 * ways:
 *
 *   - from a CDN with `defer`. window.Alpine does not exist yet when this runs,
 *     so it waits for `alpine:init`, which Alpine dispatches before it
 *     initialises a single element.
 *   - inside Livewire's own script. That defines window.Alpine as soon as it
 *     runs and starts it on DOMContentLoaded, so if this lands after it,
 *     registering at once is still in time. Before it, `alpine:init` again.
 *   - bundled by the host. Then the host calls Alpine.plugin() on the ESM
 *     build and this file is not involved, unless it is ALSO included, which
 *     the guard in blade-ui.js makes harmless.
 *
 * Both paths below can fire for the same Alpine; the guard makes the second a
 * no-op.
 */
import bladeUi from './blade-ui.js';

if (window.Alpine) bladeUi(window.Alpine);

document.addEventListener('alpine:init', () => bladeUi(window.Alpine));
