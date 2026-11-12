/*
 * dsTheme: light, dark or the system's, behind ds::theme-switcher.
 *
 *   x-data="dsTheme({ storageKey: 'ds-theme', transition: true })"
 *
 * The scope exposes `dsTheme`, one of 'light', 'dark', 'system', and the
 * view binds it: the segmented variant's radios through x-model, the button
 * variant's press through dsCycle(). Choosing:
 *
 *   - stores the choice in localStorage under the key; 'system' removes the
 *     key, so a reader who never chose and one who went back look the same
 *     to the pre-paint script
 *   - puts exactly one of `dark` and `light` on <html>, and color-scheme to
 *     match, so `dark:` utilities, the tokens and the browser's own controls
 *     agree. `light` as well as `dark`, because the tokens fall back to the
 *     system's preference when the root has neither
 *   - moves every other switcher on the page with the same key to the same
 *     choice, and every other tab through the `storage` event
 *   - on 'system', follows prefers-color-scheme as it changes
 *
 * The change runs inside document.startViewTransition() when the browser has
 * it, motion is allowed and `transition` is on: the browser's crossfade,
 * timed by a rule in theme.css under data-ds-theme-changing.
 *
 * <x-ds::theme-script /> applies the stored choice before the first paint
 * with the same rules, without Alpine; this only takes over once it runs.
 */

const CHOICES = ['light', 'dark', 'system'];

// Every live switcher, for the ones on the same page to follow each other.
const switchers = new Set();

// The choice applied to the page, so the system listener knows whether to act.
let applied = null;

let systemListening = false;

const systemDark = () => typeof matchMedia === 'function' && matchMedia('(prefers-color-scheme: dark)').matches;

function read(key) {
    try {
        const value = localStorage.getItem(key);
        return value === 'light' || value === 'dark' ? value : 'system';
    } catch {
        // Storage refused (a private window, blocked site data): the system's.
        return 'system';
    }
}

function write(key, choice) {
    try {
        if (choice === 'system') localStorage.removeItem(key);
        else localStorage.setItem(key, choice);
    } catch {
        // Still applied for this page; only the memory of it is lost.
    }
}

function paint(choice) {
    const dark = choice === 'dark' || (choice === 'system' && systemDark());
    const root = document.documentElement;
    root.classList.toggle('dark', dark);
    root.classList.toggle('light', !dark);
    root.style.colorScheme = dark ? 'dark' : 'light';
}

function apply(choice, animate) {
    const before = document.documentElement.classList.contains('dark');
    applied = choice;
    const after = choice === 'dark' || (choice === 'system' && systemDark());

    const motion = typeof matchMedia !== 'function' || !matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!animate || before === after || !motion || typeof document.startViewTransition !== 'function') {
        paint(choice);
        return;
    }

    const root = document.documentElement;
    root.dataset.dsThemeChanging = '';
    const transition = document.startViewTransition(() => paint(choice));
    transition.finished.finally(() => delete root.dataset.dsThemeChanging);
}

function listenToSystem() {
    if (systemListening || typeof matchMedia !== 'function') return;
    systemListening = true;
    const query = matchMedia('(prefers-color-scheme: dark)');
    const follow = () => {
        if (applied === 'system') paint('system');
    };
    if (query.addEventListener) query.addEventListener('change', follow);
    else query.addListener?.(follow);
}

export function theme(Alpine) {
    Alpine.data('dsTheme', (options = {}) => {
        const key = typeof options.storageKey === 'string' && options.storageKey ? options.storageKey : 'ds-theme';
        const animate = options.transition !== false;
        let onStorage = null;

        return {
            dsTheme: 'system',
            dsKey: key,

            init() {
                this.dsTheme = read(key);
                switchers.add(this);
                listenToSystem();
                // The page's theme is settled once, by the first switcher to
                // start: a later one on another key must not repaint the page
                // it did not choose. And not even then when ds::theme-script
                // has already put a class on the root; this is for a page
                // without it, and it never animates.
                if (applied === null) {
                    applied = this.dsTheme;
                    const root = document.documentElement.classList;
                    if (!root.contains('dark') && !root.contains('light')) paint(this.dsTheme);
                }

                this.$watch('dsTheme', (choice) => {
                    if (!CHOICES.includes(choice)) {
                        this.dsTheme = 'system';
                        return;
                    }
                    if (choice === read(key) && choice === applied) return;
                    write(key, choice);
                    apply(choice, animate);
                    for (const other of switchers) {
                        if (other !== this && other.dsKey === key && other.dsTheme !== choice) other.dsTheme = choice;
                    }
                });

                onStorage = (event) => {
                    // null is localStorage.clear() in the other tab.
                    if (event.key !== key && event.key !== null) return;
                    const choice = read(key);
                    if (choice !== this.dsTheme) this.dsTheme = choice;
                    // The page itself, once for every switcher hearing it.
                    if (applied !== choice) apply(choice, false);
                };
                window.addEventListener('storage', onStorage);
            },

            /** The button variant: light, dark, system, round again. */
            dsCycle() {
                this.dsTheme = CHOICES[(CHOICES.indexOf(this.dsTheme) + 1) % CHOICES.length];
            },

            destroy() {
                switchers.delete(this);
                if (onStorage) window.removeEventListener('storage', onStorage);
            },
        };
    });
}
