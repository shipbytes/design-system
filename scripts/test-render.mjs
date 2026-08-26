/**
 * Render tests.
 *
 * The token tests check values. These check COMPONENTS — that each one renders,
 * and that every class it emits actually has a rule in the stylesheet a consumer
 * ends up with.
 *
 * The order matters and is the whole design:
 *
 *   1. Compile CSS exactly the way a consumer does — `@source` pointed at the
 *      Blade SOURCE, plus theme.css's own `@source inline(...)` list. Not at the
 *      rendered output.
 *   2. Boot Laravel through testbench and render every component.
 *   3. Assert every emitted class exists in the CSS from step 1.
 *
 * Compiling from the rendered HTML instead would generate every class the
 * components emit and prove nothing. Tailwind reads source TEXT, and a class
 * built from a prop — `size-{$size}` — is not in the source. That gap is where
 * `size-4.5` lived: every alert rendered a checkmark the height of its panel and
 * not one of the 43 existing checks noticed.
 *
 *   node scripts/test-render.mjs
 */
import { mkdir, rm, writeFile, readFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const run = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const TMP = join(ROOT, '.render-tmp');

if (!existsSync(join(ROOT, 'vendor/bin/phpunit'))) {
    console.error(
        '\nrender tests need the PHP dev dependencies:\n\n' +
        '  composer install\n\n' +
        'They boot Laravel through testbench so the components can actually be\n' +
        'rendered — which the token tests never do.\n',
    );
    process.exit(1);
}

await rm(TMP, { recursive: true, force: true });
await mkdir(TMP, { recursive: true });

// ------------------------------------------------- 1. the consumer's CSS

console.log('\ncompiling the CSS a consumer gets…');

/*
  This is the consumer contract from README.md, verbatim apart from the paths:

      @import 'tailwindcss';
      @import '.../dist/tokens.css';
      @import '.../dist/theme.css';
      @source '.../resources/views';

  If that ever stops matching what we tell people to write, this test is
  checking a stylesheet nobody has.
*/
/*
  `source(none)` matters more than it looks.

  Tailwind v4 AUTOMATICALLY detects sources from the stylesheet's directory tree,
  on top of any explicit @source. Without this, compiling from anywhere inside
  the repo silently pulls in scripts/gallery.blade.php, dist/gallery.html and
  even the specs — and those mention half the classes the components emit. The
  test then passes because something else in the repo happened to name the class,
  which is the exact accident it exists to detect.

  Measured: with auto-detection on, deliberately breaking a component's class
  composition still passed. With it off, the same break fails.

  A consumer's own `@import "tailwindcss"` does auto-detect, and should — their
  app's views are theirs to scan. What must be isolated is OUR half of the
  contract: the components, and nothing else.
*/
await writeFile(
    join(TMP, 'consumer-in.css'),
    `@import "tailwindcss" source(none);
@import "${join(ROOT, 'dist/tokens.css')}";
@import "${join(ROOT, 'dist/theme.css')}";
@source "${join(ROOT, 'resources/views')}";
`,
);

try {
    await run(
        'npx',
        ['@tailwindcss/cli', '-i', join(TMP, 'consumer-in.css'), '-o', join(TMP, 'consumer.css')],
        { cwd: ROOT, maxBuffer: 32 * 1024 * 1024 },
    );
} catch (err) {
    console.error('could not compile the consumer stylesheet:');
    console.error(String(err.stderr || err).slice(0, 800));
    process.exit(1);
}

const css = await readFile(join(TMP, 'consumer.css'), 'utf8');
console.log(`  ${(css.length / 1024).toFixed(1)} kB, from the Blade source — not from the rendered output`);

// ------------------------------------------------- 2 & 3. render and assert

console.log('rendering every component through Laravel…\n');

let failed = false;
try {
    const { stdout } = await run('vendor/bin/phpunit', ['--colors=always'], {
        cwd: ROOT,
        maxBuffer: 32 * 1024 * 1024,
        env: { ...process.env, DS_COMPILED_CSS: join(TMP, 'consumer.css') },
    });
    console.log(stdout);
} catch (err) {
    console.log(String(err.stdout || ''));
    console.error(String(err.stderr || '').slice(0, 2000));
    failed = true;
}

await rm(TMP, { recursive: true, force: true });

process.exit(failed ? 1 : 0);
