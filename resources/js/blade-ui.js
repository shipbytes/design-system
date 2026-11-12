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
 * BEHAVIOURS below. A helper two behaviours share goes in shared/, which
 * registers nothing. The conventions are in CLAUDE.md under "The shared script".
 */
import { anchorDirective } from './behaviours/anchor.js';
import { chart } from './behaviours/chart.js';
import { clamp } from './behaviours/clamp.js';
import { clear } from './behaviours/clear.js';
import { clipboard } from './behaviours/clipboard.js';
import { commandPalette } from './behaviours/command-palette.js';
import { contextMenu } from './behaviours/context-menu.js';
import { confirmDialog } from './behaviours/confirm-dialog.js';
import { dismiss } from './behaviours/dismiss.js';
import { hold } from './behaviours/hold.js';
import { inlineConfirm } from './behaviours/confirm.js';
import { inlineEdit } from './behaviours/inline-edit.js';
import { glide } from './behaviours/glide.js';
import { hoverCard } from './behaviours/hover-card.js';
import { numberField } from './behaviours/number-field.js';
import { otp } from './behaviours/otp.js';
import { password } from './behaviours/password.js';
import { popover } from './behaviours/popover.js';
import { roll } from './behaviours/roll.js';
import { scrollShadows } from './behaviours/scroll-shadows.js';
import { slider } from './behaviours/slider.js';
import { sortable } from './behaviours/sortable.js';
import { split } from './behaviours/split.js';
import { swap } from './behaviours/swap.js';
import { tagInput } from './behaviours/tag-input.js';
import { teamSwitcher } from './behaviours/team-switcher.js';
import { theme } from './behaviours/theme.js';
import { timePicker } from './behaviours/time-picker.js';
import { toggleGroup } from './behaviours/toggle-group.js';
import { toolbar } from './behaviours/toolbar.js';
import { tree } from './behaviours/tree.js';

const BEHAVIOURS = [clipboard, otp, tagInput, numberField, password, glide, toolbar, toggleGroup, anchorDirective, popover, hoverCard, contextMenu, theme, dismiss, inlineConfirm, hold, confirmDialog, roll, chart, clamp, clear, tree, slider, timePicker, inlineEdit, scrollShadows, commandPalette, sortable, split, swap, teamSwitcher];

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
