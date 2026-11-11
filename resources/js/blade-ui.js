/*
 * The package's Alpine plugin: every reusable behaviour, registered at once.
 *
 *   import bladeUi from '../../vendor/shipbytes/blade-ui/dist/blade-ui.js';
 *   Alpine.plugin(bladeUi);
 *
 * A host with no build step never sees this file: ds::scripts inlines the
 * classic build (global.js), which calls the same function.
 *
 * Adding a behaviour: one file in behaviours/, one import and one entry in
 * BEHAVIOURS below. The conventions are in CLAUDE.md under "The shared script".
 */
import { clipboard } from './behaviours/clipboard.js';

const BEHAVIOURS = [clipboard];

// Symbol.for, not a module-level flag. Two copies of this script, say the
// inlined one AND a Vite import, are two separate modules with two separate
// flags; the global symbol registry is the one thing they share, so the second
// copy sees the first and stands down.
const REGISTERED = Symbol.for('shipbytes.blade-ui');

export default function bladeUi(Alpine) {
    if (!Alpine || Alpine[REGISTERED]) return;
    Alpine[REGISTERED] = true;

    for (const register of BEHAVIOURS) register(Alpine);
}
