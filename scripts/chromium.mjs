/**
 * Find a Chromium to drive.
 *
 * Shared by the documentation screenshots and the behaviour tests, which are the
 * only two things in this repo that need a browser. Looks in DS_CHROME, then the
 * Playwright browser cache, then the usual system locations.
 *
 * Returns null rather than throwing, so a caller can decide whether a missing
 * browser is a failure (build:docs) or a skip (test:behaviour).
 */
import { existsSync } from 'node:fs';
import { readdir } from 'node:fs/promises';
import { join } from 'node:path';
import { homedir } from 'node:os';

export async function findChromium() {
    // Checked, not trusted: a DS_CHROME pointing at nothing should read as "no
    // browser" rather than failing later inside Playwright with a spawn error.
    if (process.env.DS_CHROME) {
        return existsSync(process.env.DS_CHROME) ? process.env.DS_CHROME : null;
    }

    const cache = join(homedir(), '.cache/ms-playwright');
    if (existsSync(cache)) {
        const dirs = (await readdir(cache)).filter((d) => d.startsWith('chromium-')).sort().reverse();
        for (const dir of dirs) {
            for (const bin of [
                'chrome-linux64/chrome',
                'chrome-linux/chrome',
                'chrome-mac/Chromium.app/Contents/MacOS/Chromium',
            ]) {
                const path = join(cache, dir, bin);
                if (existsSync(path)) return path;
            }
        }
    }

    for (const path of [
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    ]) {
        if (existsSync(path)) return path;
    }

    return null;
}

export const NO_BROWSER = `no Chromium found.

Install one, or point DS_CHROME at an existing binary:
  npx playwright install chromium
  DS_CHROME=/usr/bin/chromium npm run …
`;
