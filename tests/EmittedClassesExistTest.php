<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * THE test this repo was missing.
 *
 * Renders every component, harvests every class it emits, and asserts each one
 * has a rule in CSS compiled the way a CONSUMER compiles it — from the Blade
 * SOURCE, not from this rendered output.
 *
 * That distinction is the entire point. Tailwind scans source text. A class the
 * component composes at runtime — `size-{$size}` — never appears in the source,
 * so it gets no rule, and the element silently renders unstyled. Compiling from
 * the rendered HTML would generate the class and prove nothing.
 *
 * This is how `size-4.5` shipped: every <x-ds::alert> drew a checkmark the
 * height of its panel, in any app that did not already use size-4.5 for
 * something else, and nothing anywhere reported a problem.
 *
 * The CSS is compiled by scripts/test-render.mjs and handed over in
 * DS_COMPILED_CSS — the Tailwind CLI is a node tool, so `npm test` is the gate.
 */
final class EmittedClassesExistTest extends TestCase
{
    /**
     * Classes that are not utilities and are not meant to be.
     * Anything added here needs a reason, because the default answer is "then
     * it should not be in the markup".
     */
    private const NOT_UTILITIES = [
        // Alpine's own hook, and an ATTRIBUTE rather than a utility — so it
        // never needs a `.x-cloak` rule. The rule it does need, `[x-cloak] {
        // display: none }`, now ships in dist/theme.css and is checked by
        // every_x_cloak_component_has_a_rule_that_hides_it() below.
        'x-cloak',
    ];

    #[Test]
    public function every_class_the_components_emit_has_a_rule(): void
    {
        $css = $this->compiledCss();

        $missing = [];

        foreach (Specimens::all() as $name => $blade) {
            foreach ($this->classesIn(Blade::render($blade)) as $class) {
                if (in_array($class, self::NOT_UTILITIES, true)) {
                    continue;
                }

                if (! $this->cssHasRuleFor($css, $class)) {
                    $missing[$class][] = $name;
                }
            }
        }

        $report = implode("\n", array_map(
            fn ($class, $where) => sprintf(
                '  %-42s emitted by %s',
                $class,
                implode(', ', array_unique($where)),
            ),
            array_keys($missing),
            $missing,
        ));

        $this->assertSame(
            [],
            $missing,
            "these classes are emitted at runtime but have no rule in the compiled CSS,\n"
            ."so the elements carrying them render unstyled and nothing reports it:\n\n"
            .$report."\n\n"
            ."Either the class is built from a prop and needs a literal-class map\n"
            ."(see specs/modal.md), or it is genuinely open-ended and belongs in the\n"
            ."@source inline(...) list in build-tokens.mjs, or it does not exist.\n",
        );
    }

    #[Test]
    public function the_size_that_shipped_broken_is_covered(): void
    {
        // A named regression test as well as a general one. The general check
        // above is only as good as its specimens; this one names the bug.
        $this->assertTrue(
            $this->cssHasRuleFor($this->compiledCss(), 'size-4.5'),
            'size-4.5 has no rule — this is the exact bug that made every alert '
            .'render a checkmark the height of its panel',
        );
    }

    /**
     * The other half of the same idea, for an ATTRIBUTE selector.
     *
     * Nine components hide themselves with `x-cloak` until Alpine boots and
     * `x-show` takes over. The attribute does nothing on its own — it is a hook
     * for a CSS rule, and Alpine does not ship that rule. Without one, every
     * overlay on the page renders open, backdrop and all, from first paint
     * until the script runs.
     *
     * That is the same failure as `size-4.5`: a selector with no rule behind it
     * is not an error, it is nothing. The rule now ships in dist/theme.css; this
     * asserts it is still there, and that the set of components relying on it is
     * still covered.
     */
    #[Test]
    public function every_x_cloak_component_has_a_rule_that_hides_it(): void
    {
        $css = $this->compiledCss();

        $cloaked = [];

        foreach (glob(__DIR__.'/../resources/views/components/*.blade.php') as $file) {
            if (preg_match('/(?<![\w:-])x-cloak(?![\w-])/', (string) file_get_contents($file))) {
                $cloaked[] = basename($file, '.blade.php');
            }
        }

        // If this ever hits zero the check has stopped meaning anything —
        // a passing test over an empty set is the failure mode it exists to
        // avoid, so the set itself is asserted first.
        $this->assertNotEmpty(
            $cloaked,
            'no component emits x-cloak any more — either that is wrong, or this '
            .'test is now checking nothing and should go.',
        );

        $this->assertMatchesRegularExpression(
            '/\[x-cloak\][^{]*\{[^}]*display\s*:\s*none/i',
            $css,
            "these components hide themselves with x-cloak and the compiled CSS has\n"
            ."no rule behind it, so every one of them renders FULLY VISIBLE from\n"
            ."first paint until Alpine boots:\n\n  "
            .implode(', ', $cloaked)."\n\n"
            ."The rule is emitted by scripts/build-tokens.mjs into dist/theme.css.\n"
            ."If it was moved back out to the consumer contract, getting-started.md\n"
            ."has to say so as a REQUIRED line, not an aside.\n",
        );
    }

    private function compiledCss(): string
    {
        $path = getenv('DS_COMPILED_CSS') ?: '';

        if ($path === '' || ! is_file($path)) {
            $this->markTestSkipped(
                'no compiled CSS to check against — run `npm test`, which compiles it '
                .'the way a consumer does and then runs this suite. Running phpunit '
                .'alone skips the one check that would have caught the icon-size bug.',
            );
        }

        return (string) file_get_contents($path);
    }

    /**
     * Every class the markup carries, including the ones Alpine applies later.
     *
     * x-transition:* values are class lists too — they are added to the element
     * at runtime, so a missing rule there means a transition that silently does
     * nothing rather than an element that is silently unstyled.
     *
     * @return list<string>
     */
    private function classesIn(string $html): array
    {
        $classes = [];

        $add = function (string $list) use (&$classes): void {
            foreach (preg_split('/\s+/', trim(html_entity_decode($list))) as $class) {
                if ($class !== '') {
                    $classes[$class] = true;
                }
            }
        };

        /*
         * Static lists: `class` and the x-transition stages.
         *
         * The lookbehind matters. Without it, `\bclass=` also matches the
         * `class` in `:class=`, because a colon is a word boundary — and a
         * binding's value is a JS EXPRESSION, so the harvest came back holding
         * `?`, `:`, `&&` and `'text-fg'` complete with quotes, and demanded a CSS
         * rule for each.
         */
        preg_match_all('/(?<![-:\w])class="([^"]*)"/i', $html, $static);
        preg_match_all('/x-transition:[a-z-]+="([^"]*)"/i', $html, $transitions);

        foreach ([...$static[1], ...$transitions[1]] as $list) {
            $add($list);
        }

        /*
         * Bindings: `:class` and `x-bind:class`.
         *
         * Still checked, because Alpine applies these at runtime and a missing
         * rule there is the same silent failure — but only the STRING LITERALS
         * inside the expression are class names. Everything else is JavaScript.
         */
        preg_match_all('/(?::|x-bind:)class="([^"]*)"/i', $html, $bound);

        foreach ($bound[1] as $expression) {
            preg_match_all('/[\'"]([^\'"]*)[\'"]/', html_entity_decode($expression), $literals);

            foreach ($literals[1] as $list) {
                $add($list);
            }
        }

        return array_keys($classes);
    }

    /**
     * Does the stylesheet carry a rule whose selector is this class?
     *
     * Tailwind escapes every character outside [A-Za-z0-9_-], so `size-4.5`
     * becomes the selector `.size-4\.5` and `bg-fg/20` becomes `.bg-fg\/20`.
     * The boundary check stops `.p-4` from matching inside `.p-40`.
     */
    private function cssHasRuleFor(string $css, string $class): bool
    {
        $escaped = preg_replace('/([^A-Za-z0-9_-])/', '\\\\$1', $class);

        return (bool) preg_match(
            '/\.'.preg_quote($escaped, '/').'(?=[\s,{:>+~)\[]|$)/',
            $css,
        );
    }
}
